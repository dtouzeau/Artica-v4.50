-- Load Balancer Module for Health-Checked Backends
-- Implements algorithms matching nginx's native behavior
-- Supports: round-robin, weighted round-robin, slow start, least_conn, ip_hash, random, uri_hash
--
-- RECOMMENDED Usage (balancer_by_lua_block inside upstream):
--   upstream mybackend {
--       zone mybackend 64k;
--       server 0.0.0.1;  # Placeholder
--
--       balancer_by_lua_block {
--           local balancer = require "balancer"
--           local healthcheck = require "healthcheck_active"
--           local servers = healthcheck.get_healthy_servers_with_config("mybackend")
--           local backend = balancer.least_conn(servers, "mybackend")
--           local ok, err = require("ngx.balancer").set_current_peer(backend)
--       }
--   }
--
--   server {
--       location / {
--           proxy_pass http://mybackend;
--
--           # IMPORTANT: For least_conn, add this to release connection counters:
--           log_by_lua_block {
--               local balancer = require "balancer"
--               balancer.release_connection()
--           }
--       }
--   }
--
-- Alternative Usage (access_by_lua_block in location):
--   location / {
--       access_by_lua_block {
--           local balancer = require "balancer"
--           local healthcheck = require "healthcheck_active"
--           local servers = healthcheck.get_healthy_servers("myupstream")
--           local backend = balancer.round_robin(servers, "myupstream")
--           ngx.var.backend = backend
--       }
--       proxy_pass http://$backend;
--
--       log_by_lua_block {
--           local balancer = require "balancer"
--           balancer.release_connection()
--       }
--   }

local _M = {
    _VERSION = '3.6.0',  -- Added: strict mode (mutex) for WRR and least_conn
}

-- Shared dictionaries (configure in nginx.conf)
-- lua_shared_dict balancer_stats 1m;      -- for least_conn
-- lua_shared_dict balancer_counters 1m;   -- for round-robin counters
local stats_dict = ngx.shared.balancer_stats
local counter_dict = ngx.shared.balancer_counters

-- Internal scale factor for sub-integer weight precision during slow_start ramp.
-- Callers needing integer arithmetic (counter_dict:incr, math.random bounds) multiply
-- the fractional weight by this before use. Lets weight=1 servers ramp smoothly.
local WEIGHT_SCALE = 100

-- Random seed initialized flag (per worker)
local random_initialized = false
local function ensure_seeded()
    if not random_initialized then
        math.randomseed(ngx.now() * 1000 + (ngx.worker.pid() or 0))
        random_initialized = true
    end
end

-- Forward declarations for slow_start helpers. Callers like round_robin,
-- ip_hash, uri_hash, and hash appear earlier than the helper definitions,
-- so without these forward decls the closures would resolve the names as
-- globals (nil) and error at call time.
local should_skip_for_ramp
local apply_ramp_skip_to_hash_selection
---
-- Helper: extract host and port from server object
---
local function extract_host_port(srv)
    if type(srv) == "table" then
        if srv.host and srv.port then
            return srv.host, srv.port
        elseif srv.addr then
            local host, port_str = srv.addr:match("([^:]+):(%d+)")
            return host, tonumber(port_str)
        end
    elseif type(srv) == "string" then
        local host, port_str = srv:match("([^:]+):(%d+)")
        return host, tonumber(port_str)
    end
    return nil, nil
end
---
-- DJB2 hash function (better distribution than simple addition)
-- Used for ip_hash, uri_hash, and custom hash methods
---
local function hash_string(str)
    local hash = 5381
    for i = 1, #str do
        hash = ((hash * 33) + string.byte(str, i)) % 4294967296
    end
    return hash
end

---
-- MD5-based hash for virtual node keys (better distribution than DJB2)
-- Uses lua-resty-string for MD5 hashing
-- Falls back to DJB2 if MD5 not available
---
local hash_vnode_key
do
    local md5_available, resty_string = pcall(require, "resty.string")
    local md5_lib_available, resty_md5 = pcall(require, "resty.md5")

    if md5_lib_available and md5_available then
        -- Use resty.md5 + resty.string (OpenResty standard)
        hash_vnode_key = function(str)
            local md5 = resty_md5:new()
            if not md5 then
                -- Fallback to DJB2 if MD5 object creation fails
                return hash_string(str)
            end

            local ok = md5:update(str)
            if not ok then
                return hash_string(str)
            end

            local digest = md5:final()
            if not digest then
                return hash_string(str)
            end

            -- Convert first 4 bytes to uint32
            local hex = resty_string.to_hex(digest)
            local hash = 0
            for i = 1, 8, 2 do  -- First 8 hex chars = 4 bytes
                local byte_val = tonumber(hex:sub(i, i+1), 16)
                hash = hash + byte_val * (256 ^ ((i-1)/2))
            end
            return hash
        end
    else
        -- Fallback to DJB2 if MD5 not available
        ngx.log(ngx.WARN, "lua-resty-md5 not available, using DJB2 for virtual nodes (may cause uneven distribution)")
        hash_vnode_key = hash_string
    end
end

---
-- True Round-Robin (exactly like nginx)
-- Uses atomic counter in shared memory for even distribution
-- Each request increments counter and selects next server in sequence
---
function _M.round_robin(servers, upstream_name)
    if not servers or #servers == 0 then
        return nil
    end

    local selected_index = 1

    if #servers == 1 then
        selected_index = 1
    else
        -- Use shared counter for true round-robin across all workers
        local key = (upstream_name or "default") .. ":rr_counter"

        if not counter_dict then
            -- Fallback: use time-based if no shared dict
            ngx.log(ngx.WARN, "balancer_counters dict not configured, using time-based fallback")
            local now_ms = ngx.now() * 1000
            selected_index = (math.floor(now_ms) % #servers) + 1
        else
            -- Atomic increment and get
            local counter, err = counter_dict:incr(key, 1, 0)
            if not counter then
                ngx.log(ngx.ERR, "failed to increment counter: ", err)
                selected_index = 1
            else
                selected_index = (counter % #servers) + 1
            end
        end
    end

    -- Probabilistic slow_start skip: if the chosen server is ramping,
    -- skip with probability (1 - progress) and advance to the next server.
    -- This gives recovering servers a partial share even though plain RR
    -- doesn't honor weights.
    for attempt = 0, #servers - 1 do
        local idx = ((selected_index - 1 + attempt) % #servers) + 1
        local server = servers[idx]
        if not should_skip_for_ramp(server, upstream_name) then
            local addr = type(server) == "table" and server.addr or server
            ngx.ctx.balancer_server = addr
            ngx.ctx.balancer_upstream = upstream_name
            return addr
        end
    end

    -- Every server hit a ramp-skip (very unlikely). Fall back to originally
    -- selected server to preserve availability.
    local server = servers[selected_index]
    local addr = type(server) == "table" and server.addr or server

    -- Store for connection release (used by least_conn tracking)
    ngx.ctx.balancer_server = addr
    ngx.ctx.balancer_upstream = upstream_name

    return addr
end

---
-- Weighted Round-Robin (smooth weighted round-robin, like nginx)
-- Implements the same algorithm as nginx for smooth distribution
--
-- Input: array of {addr = "ip:port", weight = N, slow_start = seconds}
-- Options:
--   strict: boolean - Use mutex for perfect per-request fairness (default: false)
--                     Enable for extreme weight ratios (1000:1+) or when strict fairness required
--                     Adds ~0.1-1ms latency due to lock contention
--   max_retries: number - Max lock acquisition retries in strict mode (default: 3)
--
-- Example:
--   servers = {
--       {addr = "192.168.1.10:8080", weight = 5},
--       {addr = "192.168.1.11:8080", weight = 1}
--   }
--
--   -- Normal mode (99.9% fair, zero latency):
--   balancer.weighted_round_robin(servers, "myupstream")
--
--   -- Strict mode (100% fair, slight latency):
--   balancer.weighted_round_robin(servers, "myupstream", {strict = true})
---
function _M.weighted_round_robin(servers, upstream_name, options)
    if not servers or #servers == 0 then
        return nil
    end

    if #servers == 1 then
        return type(servers[1]) == "table" and servers[1].addr or servers[1]
    end

    if not counter_dict then
        ngx.log(ngx.WARN, "balancer_counters dict not configured, falling back to simple round-robin")
        return _M.round_robin(servers, upstream_name)
    end

    options = options or {}
    local use_strict = options.strict or false
    local max_retries = options.max_retries or 3

    local key_prefix = (upstream_name or "default") .. ":wrr:"
    local lock_key = key_prefix .. "lock"
    local lock_acquired = false

    -- Strict mode: acquire mutex for perfect fairness
    if use_strict then
        for attempt = 1, max_retries do
            -- Try to acquire lock (TTL 50ms to prevent deadlocks)
            local ok, err = counter_dict:add(lock_key, 1, 0.05)
            if ok then
                lock_acquired = true
                break
            end
            -- Lock held by another worker, wait briefly and retry
            if attempt < max_retries then
                ngx.sleep(0.001)  -- 1ms backoff
            end
        end

        if not lock_acquired then
            -- Failed to acquire lock after retries, fall back to non-strict mode
            ngx.log(ngx.WARN, "WRR strict mode: lock acquisition failed, using non-strict")
        end
    end

    -- Precompute effective weights (with slow start adjustments).
    -- All weights are scaled by WEIGHT_SCALE so fractional ramp values
    -- (e.g. 0.5 for a weight=1 server at 50% ramp) become integer-precise.
    local effective_weights = {}
    local total_weight = 0
    for i, server in ipairs(servers) do
        local addr = type(server) == "table" and server.addr or server
        local weight = type(server) == "table" and (server.weight or 1) or 1

        -- Apply slow start if configured
        if type(server) == "table" and server.slow_start and server.slow_start > 0 then
            weight = _M.apply_slow_start(addr, weight, server.slow_start, upstream_name)
        end

        local scaled = math.max(1, math.floor(weight * WEIGHT_SCALE + 0.5))
        effective_weights[i] = scaled
        total_weight = total_weight + scaled
    end

    -- Smooth weighted round-robin algorithm (nginx algorithm)
    -- Uses atomic incr() to avoid race conditions between workers
    local max_current_weight = -1
    local selected = nil
    local selected_idx = nil

    for i, server in ipairs(servers) do
        local addr = type(server) == "table" and server.addr or server
        local weight = effective_weights[i]

        local current_key = key_prefix .. addr .. ":current"

        -- Atomically add effective weight to current weight
        local current_weight, err = counter_dict:incr(current_key, weight, 0)
        if not current_weight then
            ngx.log(ngx.ERR, "WRR incr failed: ", err)
            current_weight = weight
        end

        -- Select server with highest current weight
        if current_weight > max_current_weight then
            max_current_weight = current_weight
            selected = addr
            selected_idx = i
        end
    end

    if selected then
        -- Atomically reduce current weight of selected server by total weight
        local current_key = key_prefix .. selected .. ":current"
        local _, err = counter_dict:incr(current_key, -total_weight, 0)
        if err then
            ngx.log(ngx.ERR, "WRR decr failed: ", err)
        end
    end

    -- Release lock if acquired (strict mode)
    if lock_acquired then
        counter_dict:delete(lock_key)
    end

    local final_selection = selected or (type(servers[1]) == "table" and servers[1].addr or servers[1])

    -- Store for connection release (used by least_conn tracking)
    ngx.ctx.balancer_server = final_selection
    ngx.ctx.balancer_upstream = upstream_name

    return final_selection
end

---
-- Slow Start Implementation
-- Gradually increases server weight from a small fraction to full weight over
-- the configured time. Returns two values:
--   effective_weight (number, may be fractional) — scaled 1/WEIGHT_SCALE .. target_weight
--   progress (number) — 0..1 during ramp, 1 when ramp is complete or disabled
-- Callers that need integer arithmetic should multiply by WEIGHT_SCALE.
---
function _M.apply_slow_start(server_addr, target_weight, slow_start_seconds, upstream_name)
    if not counter_dict or not slow_start_seconds or slow_start_seconds <= 0 then
        return target_weight, 1
    end

    local key = (upstream_name or "default") .. ":ss:" .. server_addr .. ":start_time"

    -- Atomically set start time if not already set (prevents multi-worker race)
    local start_time = ngx.now()
    local ok = counter_dict:add(key, start_time, slow_start_seconds + 60)
    if not ok then
        -- Key already exists (another worker set it first, or previous recovery)
        start_time = counter_dict:get(key)
        if not start_time then
            -- Shouldn't happen, but handle defensively
            start_time = ngx.now()
            counter_dict:set(key, start_time, slow_start_seconds + 60)
        end
    end

    local elapsed = ngx.now() - start_time

    if elapsed >= slow_start_seconds then
        return target_weight, 1
    end

    local progress = elapsed / slow_start_seconds
    -- Linear ramp from 1/WEIGHT_SCALE up to target_weight.
    -- Using a sub-integer floor ensures weight=1 servers still visibly ramp
    -- when callers scale by WEIGHT_SCALE.
    local min_weight = 1 / WEIGHT_SCALE
    local effective_weight = min_weight + (target_weight - min_weight) * progress

    return effective_weight, progress
end

---
-- Probabilistic slow_start skip for balancer methods that don't natively
-- respect weights (round_robin, ip_hash, uri_hash, hash).
-- A server at ramp progress p is kept with probability p. Side-effect:
-- initializes the slow_start timer on first call so the ramp clock starts
-- even if the server is never "selected" via a weight-aware path.
---
should_skip_for_ramp = function(server, upstream_name)
    if type(server) ~= "table" or not server.slow_start or server.slow_start <= 0 then
        return false
    end
    local _, progress = _M.apply_slow_start(server.addr, server.weight or 1, server.slow_start, upstream_name)
    if not progress or progress >= 1 then
        return false
    end
    ensure_seeded()
    return math.random() > progress
end

---
-- Reset slow start timer for a server (call when server becomes healthy)
---
function _M.reset_slow_start(server_addr, upstream_name)
    if not counter_dict then
        return
    end

    local key = (upstream_name or "default") .. ":ss:" .. server_addr .. ":start_time"
    counter_dict:delete(key)
end

---
-- Get slow start ramp-up status for a server (observability)
-- Returns table with progress info, or nil if not in ramp-up
---
function _M.get_slow_start_status(server_addr, target_weight, slow_start_seconds, upstream_name)
    if not counter_dict or not slow_start_seconds or slow_start_seconds <= 0 then
        return nil
    end

    local key = (upstream_name or "default") .. ":ss:" .. server_addr .. ":start_time"
    local start_time = counter_dict:get(key)

    if not start_time then
        return nil  -- Not in ramp-up
    end

    local elapsed = ngx.now() - start_time
    local completed = elapsed >= slow_start_seconds

    if completed then
        return {
            in_progress = false,
            elapsed = elapsed,
            remaining = 0,
            progress = 1.0,
            effective_weight = target_weight,
            target_weight = target_weight,
        }
    end

    local progress = elapsed / slow_start_seconds
    local min_weight = 1 / WEIGHT_SCALE
    local effective_weight = min_weight + (target_weight - min_weight) * progress

    return {
        in_progress = true,
        elapsed = math.floor(elapsed),
        remaining = math.ceil(slow_start_seconds - elapsed),
        progress = math.floor(progress * 100) / 100,
        effective_weight = math.floor(effective_weight * 100 + 0.5) / 100,
        target_weight = target_weight,
    }
end

---
-- Hash Ring Cache for Consistent Hashing
-- Cached per upstream to avoid rebuilding on every request
---
local hash_rings = {}
local hash_ring_version = {}

---
-- Build consistent hash ring with virtual nodes
-- Uses ketama-style algorithm for minimal reshuffling when servers change
---
local function build_hash_ring(servers, upstream_name, vnodes_per_server)
    vnodes_per_server = vnodes_per_server or 160  -- nginx default

    local ring = {}

    for i, server in ipairs(servers) do
        local server_addr = type(server) == "table" and server.addr or server
        local weight = (type(server) == "table" and server.weight) or 1

        -- More vnodes for higher weight servers
        local num_vnodes = vnodes_per_server * weight

        for v = 1, num_vnodes do
            local vnode_key = server_addr .. "-" .. v
            -- Use MD5-based hash for better distribution of virtual nodes
            local vnode_hash = hash_vnode_key(vnode_key)
            table.insert(ring, {
                hash = vnode_hash,
                server = server_addr,
                server_index = i
            })
        end
    end

    -- Sort ring by hash value for binary search
    table.sort(ring, function(a, b) return a.hash < b.hash end)

    return ring
end

---
-- Get or build hash ring for upstream (cached)
---
local function get_hash_ring(servers, upstream_name)
    -- Generate version based on server list (includes weight for proper cache invalidation)
    local version_key = upstream_name or "default"
    local current_version = ""
    for _, server in ipairs(servers) do
        local addr = type(server) == "table" and server.addr or server
        local weight = type(server) == "table" and (server.weight or 1) or 1
        current_version = current_version .. addr .. ":" .. weight .. "|"
    end

    -- Check if cached ring is still valid
    if hash_rings[version_key] and hash_ring_version[version_key] == current_version then
        return hash_rings[version_key]
    end

    -- Build new ring
    local ring = build_hash_ring(servers, upstream_name)
    hash_rings[version_key] = ring
    hash_ring_version[version_key] = current_version

    return ring
end

---
-- Binary search on hash ring to find server
---
local function find_server_on_ring(ring, client_hash)
    if not ring or #ring == 0 then
        return nil
    end

    -- Binary search for first node with hash >= client_hash
    local left, right = 1, #ring
    local result = ring[1]  -- Wrap around to first node by default

    while left <= right do
        local mid = math.floor((left + right) / 2)

        if ring[mid].hash >= client_hash then
            result = ring[mid]
            right = mid - 1
        else
            left = mid + 1
        end
    end

    return result.server
end

---
-- Apply probabilistic slow_start skip to a hash-based selection.
-- If the hashed server is ramping and the probabilistic check says "skip",
-- fall through to the first non-ramping peer in the servers list. This
-- trades sticky consistency for correct ramp-up while the server recovers;
-- after ramp completes, routing returns to pure hash-based.
---
apply_ramp_skip_to_hash_selection = function(selected_addr, servers, upstream_name)
    -- Find the selected server's table entry
    local selected_server = nil
    for _, s in ipairs(servers) do
        local addr = type(s) == "table" and s.addr or s
        if addr == selected_addr then
            selected_server = s
            break
        end
    end

    if not should_skip_for_ramp(selected_server, upstream_name) then
        return selected_addr
    end

    -- Walk the servers list for a non-ramping fallback
    for _, s in ipairs(servers) do
        local addr = type(s) == "table" and s.addr or s
        if addr ~= selected_addr and not should_skip_for_ramp(s, upstream_name) then
            return addr
        end
    end

    -- No non-ramping peer available; stick with original selection
    return selected_addr
end

---
-- IP Hash - same client IP always goes to same backend (consistent hashing)
-- Provides session stickiness based on client IP
--
-- Supports both simple modulo and consistent hashing (ketama-style)
-- Options:
--   consistent: boolean - Use consistent hashing (default: false for backwards compatibility)
--   use_binary: boolean - Use binary_remote_addr (default: true if available)
--
-- Example:
--   Simple (backwards compatible):   balancer.ip_hash(servers, "myupstream")
--   Consistent hashing:              balancer.ip_hash(servers, "myupstream", {consistent = true})
---
function _M.ip_hash(servers, upstream_name, options)
    if not servers or #servers == 0 then
        return nil
    end

    options = options or {}
    local use_consistent = options.consistent or false
    local use_binary = options.use_binary
    if use_binary == nil then use_binary = true end  -- Default to true

    -- Get client IP
    -- Convert binary_remote_addr to hex for safe hashing (ChatGPT recommendation)
    -- Binary data may cause issues with some string operations; hex is predictable
    local remote_addr
    if use_binary and ngx.var.binary_remote_addr then
        -- Convert binary to hex string for consistent hashing behavior
        remote_addr = ngx.var.binary_remote_addr:gsub(".", function(c)
            return string.format("%02x", string.byte(c))
        end)
    else
        remote_addr = ngx.var.remote_addr
    end

    if not remote_addr then
        -- Fallback to first server
        local server = servers[1]
        local addr = type(server) == "table" and server.addr or server
        ngx.ctx.balancer_server = addr
        ngx.ctx.balancer_upstream = upstream_name
        return addr
    end

    local selected_addr

    if use_consistent then
        -- Consistent hashing with virtual nodes (ketama-style)
        -- Minimal reshuffling when servers are added/removed
        local ring = get_hash_ring(servers, upstream_name)
        local client_hash = hash_string(remote_addr)
        selected_addr = find_server_on_ring(ring, client_hash)
    else
        -- Simple modulo (backwards compatible)
        -- Note: Adding/removing servers causes significant reshuffling
        local hash = hash_string(remote_addr)
        local index = (hash % #servers) + 1
        local server = servers[index]
        selected_addr = type(server) == "table" and server.addr or server
    end

    -- Probabilistic slow_start reroute for ramping servers
    selected_addr = apply_ramp_skip_to_hash_selection(selected_addr, servers, upstream_name)

    -- Store for connection release
    ngx.ctx.balancer_server = selected_addr
    ngx.ctx.balancer_upstream = upstream_name

    return selected_addr
end

---
-- Least Connections - requires shared dict for tracking
-- Sends requests to the server with fewest active connections
-- Supports weights: weighted least connections
--
-- Options:
--   strict: boolean - Use mutex for perfect selection fairness (default: false)
--                     Less critical than WRR since connection counts reset frequently
--   max_retries: number - Max lock acquisition retries in strict mode (default: 3)
--
-- IMPORTANT: Must use log_by_lua_block to decrement counters:
--   log_by_lua_block {
--       local balancer = require "balancer"
--       balancer.release_connection()
--   }
--
-- Example:
--   -- Normal mode (recommended for most cases):
--   balancer.least_conn(servers, "myupstream")
--
--   -- Strict mode (for critical systems):
--   balancer.least_conn(servers, "myupstream", {strict = true})
---
function _M.least_conn(servers, upstream_name, options)
    if not servers or #servers == 0 then
        ngx.log(ngx.ERR, "least_conn: no servers available")
        return nil
    end

    if #servers == 1 then
        -- Single server: skip connection tracking overhead (Grok optimization)
        local host, port = extract_host_port(servers[1])
        if not host then return nil end
        local selected = { host = host, port = port }
        ngx.ctx.balancer_server = selected
        ngx.ctx.balancer_upstream = upstream_name
        return selected
    end

    if not stats_dict then
        ngx.log(ngx.WARN, "balancer_stats dict not configured, falling back to round-robin")
        return _M.round_robin and _M.round_robin(servers, upstream_name) or nil
    end

    options = options or {}
    local use_strict = options.strict or false
    local max_retries = options.max_retries or 3

    local lock_key = (upstream_name or "default") .. ":lc:lock"
    local lock_acquired = false

    -- Strict mode: acquire mutex for perfect fairness
    if use_strict then
        for attempt = 1, max_retries do
            -- Try to acquire lock (TTL 50ms to prevent deadlocks)
            local ok, err = stats_dict:add(lock_key, 1, 0.05)
            if ok then
                lock_acquired = true
                break
            end
            -- Lock held by another worker, wait briefly and retry
            if attempt < max_retries then
                ngx.sleep(0.001)  -- 1ms backoff
            end
        end

        if not lock_acquired then
            -- Failed to acquire lock after retries, fall back to non-strict mode
            ngx.log(ngx.WARN, "least_conn strict mode: lock acquisition failed, using non-strict")
        end
    end

    local best = nil
    local best_ratio = nil
    local best_weight = 0

    for _, srv in ipairs(servers) do
        local host, port = extract_host_port(srv)
        if not host then goto continue end

        local weight = (type(srv) == "table" and srv.weight) or 1

        -- Apply slow start if configured (returns fractional weight during ramp)
        if type(srv) == "table" and srv.slow_start and srv.slow_start > 0 then
            local addr = srv.addr or (host .. ":" .. port)
            weight = _M.apply_slow_start(addr, weight, srv.slow_start, upstream_name)
        end

        -- Floor at 1/WEIGHT_SCALE to preserve sub-integer ramp granularity
        -- while avoiding division by zero in the ratio below.
        if not weight or weight < (1 / WEIGHT_SCALE) then
            weight = 1 / WEIGHT_SCALE
        end

        local key = (upstream_name or "default") .. ":conn:" .. host .. ":" .. port
        local conn_count = stats_dict:get(key) or 0
        local ratio = conn_count / weight

        if not best or ratio < best_ratio or (ratio == best_ratio and weight > best_weight) then
            best = { host = host, port = port }
            best_ratio = ratio
            best_weight = weight
        end

        ::continue::
    end

    if not best then
        local host, port = extract_host_port(servers[1])
        if not host then return nil end
        best = { host = host, port = port }
    end

    local key = (upstream_name or "default") .. ":conn:" .. best.host .. ":" .. best.port
    stats_dict:incr(key, 1, 0)

    -- Release lock if acquired (strict mode)
    if lock_acquired then
        stats_dict:delete(lock_key)
    end

    -- Store server selection in shared dict for connection release tracking
    -- Use connection + request number (request_id differs between balancer and log phases)
    -- TTL 60s to handle long requests, streaming, websockets (ChatGPT recommendation)
    local req_id = ngx.var.connection .. "-" .. ngx.var.connection_requests
    local release_key = "release:" .. req_id
    local server_info_str = best.host .. ":" .. best.port .. ":" .. (upstream_name or "default")
    stats_dict:set(release_key, server_info_str, 60)

    ngx.ctx.balancer_server = best
    ngx.ctx.balancer_upstream = upstream_name

    return best
end

---
-- Release connection counter (call in log_by_lua_block or header_filter_by_lua_block)
---
function _M.release_connection()
    local dict = ngx.shared.balancer_stats
    if not dict then
        return
    end

    -- Get request ID and look up server from shared dict
    -- Use connection + request number (request_id differs between balancer and log phases)
    local req_id = ngx.var.connection .. "-" .. ngx.var.connection_requests
    local release_key = "release:" .. req_id
    local server_info = dict:get(release_key)

    if not server_info then
        return
    end
    -- Parse server info: "host:port:upstream_name"
    local host, port, upstream_name = server_info:match("^([^:]+):([^:]+):(.+)$")
    if not host then
        return
    end

    -- Delete release key (prevent double-release)
    dict:delete(release_key)

    -- Decrement connection counter (use incr with -1 since this nginx version lacks decr method)
    local key = upstream_name .. ":conn:" .. host .. ":" .. port
    dict:incr(key, -1, 0)
end
-- Random selection
-- Randomly picks one of the healthy servers
-- Supports weights: weighted random
---
function _M.random(servers, upstream_name)
    if not servers or #servers == 0 then
        return nil
    end

    local selected_addr = nil

    if #servers == 1 then
        selected_addr = type(servers[1]) == "table" and servers[1].addr or servers[1]
    else
        -- Check if servers have weights
        local has_weights = type(servers[1]) == "table" and servers[1].weight

        ensure_seeded()

        if not has_weights then
            -- Simple random with probabilistic slow_start skip
            for _ = 1, #servers do
                local index = math.random(1, #servers)
                local server = servers[index]
                if not should_skip_for_ramp(server, upstream_name) then
                    selected_addr = type(server) == "table" and server.addr or server
                    break
                end
            end
            if not selected_addr then
                local server = servers[math.random(1, #servers)]
                selected_addr = type(server) == "table" and server.addr or server
            end
        else
            -- Weighted random: compute once, scale by WEIGHT_SCALE so
            -- fractional ramp weights (e.g. 0.5) become integer-precise.
            local scaled_weights = {}
            local total_weight = 0
            for i, server in ipairs(servers) do
                local weight = server.weight or 1
                if server.slow_start and server.slow_start > 0 then
                    weight = _M.apply_slow_start(server.addr, weight, server.slow_start, upstream_name)
                end
                local scaled = math.max(1, math.floor(weight * WEIGHT_SCALE + 0.5))
                scaled_weights[i] = scaled
                total_weight = total_weight + scaled
            end

            local random_weight = math.random(0, total_weight - 1)
            local cumulative = 0
            for i, server in ipairs(servers) do
                cumulative = cumulative + scaled_weights[i]
                if random_weight < cumulative then
                    selected_addr = server.addr
                    break
                end
            end

            if not selected_addr then
                selected_addr = servers[1].addr
            end
        end
    end

    -- Store for connection release
    ngx.ctx.balancer_server = selected_addr
    ngx.ctx.balancer_upstream = upstream_name

    return selected_addr
end

---
-- URI Hash - same URI always goes to same backend
-- Useful for caching
--
-- Supports both simple modulo and consistent hashing (ketama-style)
-- Options:
--   consistent: boolean - Use consistent hashing (default: false for backwards compatibility)
--   use_request_uri: boolean - Use $request_uri instead of $uri (includes query string, default: false)
--
-- Example:
--   Simple (backwards compatible):   balancer.uri_hash(servers, "myupstream")
--   With query string:               balancer.uri_hash(servers, "myupstream", {use_request_uri = true})
--   Consistent hashing:              balancer.uri_hash(servers, "myupstream", {consistent = true})
--   Full features:                   balancer.uri_hash(servers, "myupstream", {consistent = true, use_request_uri = true})
---
function _M.uri_hash(servers, upstream_name, options)
    if not servers or #servers == 0 then
        return nil
    end

    options = options or {}
    local use_consistent = options.consistent or false
    local use_request_uri = options.use_request_uri or false

    -- Get URI (can use $request_uri or $uri)
    -- $uri = path only (e.g., /api/users)
    -- $request_uri = path + query string (e.g., /api/users?id=123)
    local key = use_request_uri and ngx.var.request_uri or ngx.var.uri
    key = key or ""

    local selected_addr

    if use_consistent then
        -- Consistent hashing with virtual nodes (ketama-style)
        -- Minimal reshuffling when servers are added/removed
        local ring = get_hash_ring(servers, upstream_name)
        local hash_val = hash_string(key)
        selected_addr = find_server_on_ring(ring, hash_val)
    else
        -- Simple modulo (backwards compatible)
        -- Note: Adding/removing servers causes significant reshuffling
        local hash_val = hash_string(key)
        local index = (hash_val % #servers) + 1
        local server = servers[index]
        selected_addr = type(server) == "table" and server.addr or server
    end

    -- Probabilistic slow_start reroute for ramping servers
    selected_addr = apply_ramp_skip_to_hash_selection(selected_addr, servers, upstream_name)

    -- Store for connection release
    ngx.ctx.balancer_server = selected_addr
    ngx.ctx.balancer_upstream = upstream_name

    return selected_addr
end
---
-- Generic Hash - hash based on any variable
-- Provides maximum flexibility for custom hashing strategies
--
-- Supports both simple modulo and consistent hashing (ketama-style)
-- Options:
--   consistent: boolean - Use consistent hashing (default: false for backwards compatibility)
--
-- Examples:
--   Hash by user ID:         balancer.hash(servers, ngx.var.arg_user_id, "myupstream")
--   Hash by request URI:     balancer.hash(servers, ngx.var.request_uri, "myupstream")
--   Hash by remote addr+port: balancer.hash(servers, ngx.var.remote_addr..ngx.var.remote_port, "myupstream")
--   With consistent hashing: balancer.hash(servers, ngx.var.request_uri, "myupstream", {consistent = true})
--   Custom combo:            balancer.hash(servers, ngx.var.http_x_tenant_id..ngx.var.uri, "myupstream", {consistent = true})
--
-- Equivalent to NGINX's:
--   hash $request_uri;           →  balancer.hash(servers, ngx.var.request_uri, "name")
--   hash $request_uri consistent; →  balancer.hash(servers, ngx.var.request_uri, "name", {consistent = true})
--   hash $arg_user_id;           →  balancer.hash(servers, ngx.var.arg_user_id, "name")
---
function _M.hash(servers, key, upstream_name, options)
    if not servers or #servers == 0 then
        return nil
    end

    options = options or {}
    local use_consistent = options.consistent or false

    -- Handle empty key
    key = key or ""

    local selected_addr

    if use_consistent then
        -- Consistent hashing with virtual nodes (ketama-style)
        -- Minimal reshuffling when servers are added/removed
        if key == "" then
            -- Empty key: use first server
            selected_addr = type(servers[1]) == "table" and servers[1].addr or servers[1]
        else
            local ring = get_hash_ring(servers, upstream_name)
            local hash_val = hash_string(key)
            selected_addr = find_server_on_ring(ring, hash_val)
        end
    else
        -- Simple modulo (backwards compatible)
        -- Note: Adding/removing servers causes significant reshuffling
        local index = 1
        if key and key ~= "" then
            local hash_val = hash_string(key)
            index = (hash_val % #servers) + 1
        end

        local server = servers[index]
        selected_addr = type(server) == "table" and server.addr or server
    end

    -- Probabilistic slow_start reroute for ramping servers
    selected_addr = apply_ramp_skip_to_hash_selection(selected_addr, servers, upstream_name)

    -- Store for connection release
    ngx.ctx.balancer_server = selected_addr
    ngx.ctx.balancer_upstream = upstream_name

    return selected_addr
end

---
-- Main select function - auto-detects method
-- Methods: "round-robin", "weighted", "ip_hash", "least_conn", "random", "uri_hash"
---
function _M.select(servers, method, upstream_name)
    method = method or "round-robin"

    -- Auto-detect if servers have weights
    local has_weights = type(servers) == "table" and #servers > 0 and
                        type(servers[1]) == "table" and servers[1].weight

    if method == "round-robin" or method == "rr" then
        if has_weights then
            return _M.weighted_round_robin(servers, upstream_name)
        else
            return _M.round_robin(servers, upstream_name)
        end
    elseif method == "weighted" or method == "wrr" then
        return _M.weighted_round_robin(servers, upstream_name)
    elseif method == "ip_hash" or method == "ip-hash" then
        return _M.ip_hash(servers)
    elseif method == "least_conn" or method == "least-conn" then
        return _M.least_conn(servers, upstream_name)
    elseif method == "random" then
        return _M.random(servers)
    elseif method == "uri_hash" or method == "uri-hash" then
        return _M.uri_hash(servers)
    else
        ngx.log(ngx.WARN, "Unknown balancing method: ", method, ", using round-robin")
        return _M.round_robin(servers, upstream_name)
    end
end

---
-- Get connection stats for debugging
---
function _M.get_stats(upstream_name, servers)
    if not stats_dict or not servers then
        return {}
    end

    local stats = {}
    for _, server in ipairs(servers) do
        local addr = type(server) == "table" and server.addr or server
        local key = (upstream_name or "default") .. ":conn:" .. addr
        stats[addr] = stats_dict:get(key) or 0
    end

    return stats
end

---
-- Get round-robin counter (for debugging)
---
function _M.get_rr_counter(upstream_name)
    if not counter_dict then
        return nil
    end

    local key = (upstream_name or "default") .. ":rr_counter"
    return counter_dict:get(key) or 0
end

---
-- Reset round-robin counter (for testing)
---
function _M.reset_rr_counter(upstream_name)
    if not counter_dict then
        return
    end

    local key = (upstream_name or "default") .. ":rr_counter"
    counter_dict:delete(key)
end

return _M