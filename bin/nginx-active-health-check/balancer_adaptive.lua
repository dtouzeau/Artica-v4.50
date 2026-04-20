-- Adaptive Load Balancer with Backend Performance Metrics
-- Combines health checking with real-time performance data for optimal routing
-- Performance-optimized with worker-local caching and EWMA for fast reaction
--
-- Version: 2.9.1 - Documentation polish
--   v2.9.1:
--   - Added reset_all_metrics to usage comment block (Grok)
--   - Return upstream_name from reset_all_metrics for chaining (Grok)
--   v2.9.0:
--   - Added reset_all_metrics() for entire upstream reset (ChatGPT)
--   v2.8.0:
--   - Fixed Unix socket safety in get_performance_for_servers (ChatGPT)
--   - Added one-time jitter activation log (Grok)
--   - Ensured safe dict access with nil checks everywhere (ChatGPT)
--   v2.7.0:
--   - Rate-limit 5xx failure logging to prevent spam (ChatGPT)
--   - Fixed reset_metrics to only delete specific server, not entire cache (ChatGPT)
--   - Added jitter to default_rt_ms for cold servers (ChatGPT)
--   - Improved IPv6 validation in extract_host_port (ChatGPT)
--   v2.6.0:
--   - Enhanced filtered log with total count + median (Grok)
--   - Added rate-limit for missing metrics warning to prevent spam (Grok)
--   v2.5.0:
--   - Added logging of filtered servers in adaptive_round_robin (ChatGPT)
--   - Added get_all_upstreams() for metrics dashboards (ChatGPT)
--   - Added optional warning when metrics missing (ChatGPT)
--   v2.4.0:
--   - CRITICAL: Use normalize_addr consistently in ALL EWMA lookups (ChatGPT)
--   - Added tie_threshold to _M.config for global tuning (Grok)
--   - Added normalized_addr to debug output (Grok)
--   v2.3.0:
--   - CRITICAL: Added address normalization to prevent EWMA poisoning (ChatGPT)
--     [10.0.0.5]:443 and 10.0.0.5:443 now map to same EWMA entry
--   - CRITICAL: Fixed release_connection() IPv6 parsing bug (ChatGPT)
--   - Improved random seed entropy (Grok)
--   v2.2.0:
--   - CRITICAL: Fixed IPv6/Unix socket address parsing in record_response() (ChatGPT)
--   - Fixed retry parsing to avoid double-counting EWMA updates
--   - Added exponential decay for very stale EWMA (>1h idle) (Grok)
--   - Added weighted random tie-breaking for equal scores (Grok)
--   v2.1.0:
--   - Added failure penalty: 5xx responses push synthetic high RT to EWMA (ChatGPT)
--   - Added age penalty for stale EWMA data (Grok)
--   - Added cache invalidation on server list change (Grok)
--   - Added configurable queue_factor for score tuning (Grok)
--   - Fixed score formula: (ewma_rt / weight) * (1 + conn_count) (Grok)
--   v2.0.0:
--   - Added EWMA (exponentially weighted moving average) for fast reaction
--   - Added record_response() for metrics collection
--   - Fixed get_all_performance (no more get_keys(0) - O(N) → O(servers))
--   - Fixed cache timestamp per-upstream (was shared/corrupted)
--   - Made connection penalty adaptive (Little's Law: conn × avg_rt)
--   - Changed default RT to pessimistic 500ms (was 50ms - overloaded cold servers)
--   - Fixed release TTL to 60s (was 10s - leaked on long requests)
--
-- Features:
-- - EWMA-based response time tracking (reacts in seconds, not minutes)
-- - Failure penalty for 5xx responses (prevents flapping backends)
-- - Age penalty for stale metrics (prevents stale data from favoring idle servers)
-- - Adaptive routing based on backend response times
-- - Weighted least response time algorithm (similar to Envoy/NGINX Plus)
-- - Fallback to standard algorithms when metrics unavailable
-- - Worker-local cache with server list invalidation
-- - Slow start support for gradual ramp-up
--
-- Usage:
--   balancer_by_lua_block {
--       local balancer = require "balancer_adaptive"
--       local healthcheck = require "healthcheck_active"
--       local servers = healthcheck.get_healthy_servers_with_config("myupstream")
--       local backend = balancer.select(servers, "adaptive_least_response", "myupstream")
--       require("ngx.balancer").set_current_peer(backend.host, backend.port)
--   }
--
--   log_by_lua_block {
--       local balancer = require "balancer_adaptive"
--       balancer.record_response()  -- CRITICAL: records response time metrics + failure penalty
--       balancer.release_connection()
--   }
--
--   -- Reset all metrics for an upstream (useful after deploy/restart):
--   local adaptive = require "balancer_adaptive"
--   local upstream, deleted = adaptive.reset_all_metrics("myupstream")

local cjson = require "cjson"
local _M = {
    _VERSION = '2.9.1',  -- Documentation polish
}

-- Configuration
_M.config = {
    ewma_alpha = 0.3,              -- EWMA smoothing factor (0.1=slow, 0.5=fast reaction)
    default_rt_ms = 500,           -- Pessimistic default for cold servers
    failure_penalty_ms = 5000,     -- Synthetic RT for 5xx responses (penalize failures)
    cache_ttl = 1.0,               -- Worker-local cache TTL (seconds)
    metrics_ttl = 3600,            -- Metrics TTL in shared dict (1 hour)
    release_ttl = 60,              -- Connection release tracking TTL (seconds)
    queue_factor = 1.0,            -- Queue penalty multiplier (tune: 0.5-2.0)
    age_penalty_max = 1.0,         -- Max age penalty (+100% after age_penalty_window)
    age_penalty_window = 300,      -- Age window in seconds (5 min)
    -- EWMA decay for very stale data (Grok recommendation)
    ewma_decay_start = 3600,       -- Start decay after 1 hour idle
    ewma_decay_halflife = 7200,    -- Halve EWMA every 2 hours after decay_start
    -- Tie-breaking configuration (Grok recommendation v2.4.0)
    tie_threshold = 0.01,          -- Servers within 1% of best score are "tied"
    -- Debugging configuration (ChatGPT v2.5.0, Grok v2.6.0, ChatGPT v2.7.0)
    log_filtered_servers = false,  -- Log servers filtered by adaptive_round_robin
    warn_missing_metrics = false,  -- Warn when metrics not found for server
    warn_missing_interval = 60,    -- Rate-limit missing metrics warnings (seconds per addr)
    log_failure_penalty = true,    -- Log 5xx failure penalties (v2.7.0)
    log_failure_interval = 10,     -- Rate-limit failure penalty logs (seconds per addr)
    -- Cold server jitter (v2.7.0 ChatGPT)
    default_rt_jitter = 0.1,       -- Add ±10% jitter to default_rt_ms to prevent tie bias
}

-- Shared dictionaries (configure in nginx.conf)
-- lua_shared_dict balancer_stats 1m;
-- lua_shared_dict balancer_counters 1m;
-- lua_shared_dict backend_metrics 2m;
local stats_dict = ngx.shared.balancer_stats
local counter_dict = ngx.shared.balancer_counters
local metrics_dict = ngx.shared.backend_metrics

-- Worker-local performance cache (per-upstream with server hash)
local perf_cache = {
    data = {},           -- {cache_key => {addr => ewma_ms}}
    timestamps = {},     -- {cache_key => timestamp}
    server_hashes = {},  -- {upstream_name => server_hash} for invalidation
}

-- v2.6.0: Rate-limiter for missing metrics warnings (Grok)
local missing_metrics_logged = {}  -- {upstream:addr => last_log_time}

-- v2.7.0: Rate-limiter for 5xx failure penalty logs (ChatGPT)
local failure_penalty_logged = {}  -- {upstream:addr => last_log_time}

-- Random seed initialized flag
local random_initialized = false

-- v2.8.0: One-time jitter activation log flag (Grok)
local jitter_logged = false

-- Initialize random seed with high entropy (Grok recommendation)
local function init_random()
    if not random_initialized then
        -- Use microseconds + worker pid + initial random for entropy
        local seed = ngx.now() * 1e6 + (ngx.worker.pid() or 0)
        math.randomseed(seed)
        -- Warm up the generator (first few values can be correlated)
        for _ = 1, 10 do math.random() end
        random_initialized = true
    end
end

---
-- Get default RT with optional jitter (v2.7.0 ChatGPT, v2.8.0 Grok log)
-- Adds ±jitter% to default_rt_ms to prevent tie bias for cold servers
-- Without jitter, all cold servers get identical scores → first in list always wins
---
local function get_default_rt_with_jitter()
    local default_rt = _M.config.default_rt_ms
    local jitter = _M.config.default_rt_jitter

    if jitter and jitter > 0 then
        init_random()
        -- Random factor between (1 - jitter) and (1 + jitter)
        local factor = 1 + (math.random() * 2 - 1) * jitter
        local jittered = default_rt * factor

        -- v2.8.0: One-time log to confirm jitter is active (Grok)
        if not jitter_logged then
            ngx.log(ngx.INFO, "adaptive: cold server jitter enabled (±", jitter * 100, "%) → default=", default_rt, "ms")
            jitter_logged = true
        end

        return jittered
    end

    return default_rt
end

---
-- DJB2 hash function (better distribution than simple addition)
---
local function hash_string(str)
    local hash = 5381
    for i = 1, #str do
        hash = ((hash * 33) + string.byte(str, i)) % 4294967296
    end
    return hash
end

---
-- Normalize address to prevent EWMA poisoning (ChatGPT v2.3.0)
-- NGINX may format same backend differently in retries:
--   [10.0.0.5]:443 vs 10.0.0.5:443
--   [::1]:8080 vs ::1:8080
-- This normalizes to consistent format for EWMA key matching
---
local function normalize_addr(addr)
    if not addr then
        return nil
    end

    -- Remove brackets from IPv4-in-brackets: [1.2.3.4]:80 → 1.2.3.4:80
    local bracketed_ipv4, port = addr:match("^%[(%d+%.%d+%.%d+%.%d+)%]:(%d+)$")
    if bracketed_ipv4 then
        return bracketed_ipv4 .. ":" .. port
    end

    -- Keep IPv6 brackets: [2001:db8::1]:80 → keep as-is (needed for valid format)
    -- But normalize: [::1]:80 stays [::1]:80

    -- Trim whitespace
    addr = addr:match("^%s*(.-)%s*$")

    return addr
end

---
-- Compute server list hash for cache invalidation
---
local function compute_server_hash(servers)
    local parts = {}
    for _, srv in ipairs(servers) do
        local addr
        if type(srv) == "table" then
            addr = srv.addr or (srv.host and srv.port and (srv.host .. ":" .. srv.port)) or ""
        else
            addr = tostring(srv)
        end
        table.insert(parts, addr)
    end
    table.sort(parts)  -- Sort for consistent hash
    return table.concat(parts, "|")
end

---
-- Extract host and port from server object (v2.7.0: improved IPv6 support)
-- Handles: IPv4 (10.0.0.1:8080), IPv6 with brackets ([::1]:8080)
-- Note: Raw IPv6 without brackets (::1:8080) is ambiguous and not supported
---
local function extract_host_port(srv)
    if type(srv) == "table" then
        if srv.host and srv.port then
            return srv.host, srv.port
        elseif srv.addr then
            -- Try IPv6 with brackets first: [::1]:8080
            local ipv6_host, port_str = srv.addr:match("^%[([^%]]+)%]:(%d+)$")
            if ipv6_host then
                return "[" .. ipv6_host .. "]", tonumber(port_str)
            end
            -- Fallback to IPv4: 10.0.0.1:8080
            local host, port_str2 = srv.addr:match("^([^:]+):(%d+)$")
            return host, tonumber(port_str2)
        end
    elseif type(srv) == "string" then
        -- Try IPv6 with brackets first: [::1]:8080
        local ipv6_host, port_str = srv:match("^%[([^%]]+)%]:(%d+)$")
        if ipv6_host then
            return "[" .. ipv6_host .. "]", tonumber(port_str)
        end
        -- Fallback to IPv4: 10.0.0.1:8080
        local host, port_str2 = srv:match("^([^:]+):(%d+)$")
        return host, tonumber(port_str2)
    end
    return nil, nil
end

---
-- Safe address parser for NGINX upstream_addr
-- Handles: IPv4 (10.0.0.1:8080), IPv6 ([::1]:8080), Unix (unix:/tmp/sock)
-- NGINX format: "addr1, addr2" for retries, "addr1 : addr2" for upstream chains
---
local function parse_upstream_addrs(upstream_addrs)
    if not upstream_addrs then
        return {}
    end

    local addrs = {}

    -- Split by comma first (retries)
    for segment in string.gmatch(upstream_addrs, "[^,]+") do
        segment = string.match(segment, "^%s*(.-)%s*$")  -- trim

        -- Split by " : " (upstream chains) - note the spaces
        for addr in string.gmatch(segment, "[^:]+:[^,]+") do
            addr = string.match(addr, "^%s*(.-)%s*$")  -- trim

            -- Skip empty or "-" (failed connection marker)
            if addr and addr ~= "" and addr ~= "-" then
                -- Handle IPv6: [::1]:8080 -> keep as-is
                -- Handle IPv4: 10.0.0.1:8080 -> keep as-is
                -- Handle Unix: unix:/tmp/sock -> keep as-is
                table.insert(addrs, addr)
            end
        end

        -- If no colon-split worked, try the whole segment (single address)
        if #addrs == 0 and segment ~= "" and segment ~= "-" then
            table.insert(addrs, segment)
        end
    end

    return addrs
end

---
-- Parse upstream times/statuses (simpler - just numbers)
---
local function parse_upstream_values(str, converter)
    if not str then
        return {}
    end

    local values = {}

    -- Split by comma, then by colon
    for segment in string.gmatch(str, "[^,]+") do
        segment = string.match(segment, "^%s*(.-)%s*$")

        for val in string.gmatch(segment, "[^:]+") do
            val = string.match(val, "^%s*(.-)%s*$")
            local converted = converter(val)
            if converted then
                table.insert(values, converted)
            end
        end
    end

    return values
end

---
-- Record response time metrics (CRITICAL - call in log_by_lua_block)
-- Uses EWMA for fast reaction to performance changes
-- Also handles failure penalty for 5xx responses (ChatGPT recommendation)
--
-- v2.2.0: Fixed IPv6/Unix socket parsing (ChatGPT)
-- v2.2.0: Fixed retry double-counting - only update LAST attempt per address
--
-- Usage in log_by_lua_block:
--   local balancer = require "balancer_adaptive"
--   balancer.record_response()
---
function _M.record_response()
    if not metrics_dict then
        return
    end

    local now = ngx.now()

    -- Get response time and status from nginx variables
    local upstream_times = ngx.var.upstream_response_time
    local upstream_statuses = ngx.var.upstream_status
    local upstream_addrs = ngx.var.upstream_addr

    if not upstream_times then
        return
    end

    local upstream_name = ngx.ctx.balancer_upstream or "default"

    -- Parse values (safe for IPv6/Unix sockets)
    local times = parse_upstream_values(upstream_times, function(v)
        local rt = tonumber(v)
        return rt and (rt * 1000) or nil  -- Convert to ms
    end)

    local statuses = parse_upstream_values(upstream_statuses, function(v)
        return tonumber(v)
    end)

    local addrs = parse_upstream_addrs(upstream_addrs)

    -- Validate array alignment
    local min_len = math.min(#times, #addrs)
    if #times ~= #addrs then
        ngx.log(ngx.WARN, "adaptive: times/addrs mismatch: ", #times, " vs ", #addrs)
    end

    -- Collect LAST measurement per address (avoid double-counting retries)
    -- If same server was tried multiple times, use the last attempt
    -- v2.3.0: Normalize addresses to prevent EWMA poisoning (ChatGPT)
    local addr_metrics = {}  -- {normalized_addr => {rt_ms, status}}

    for i = 1, min_len do
        local raw_addr = addrs[i]
        local rt_ms = times[i]
        local status = statuses[i]  -- May be nil if fewer statuses

        if raw_addr then
            -- Normalize: [10.0.0.5]:443 → 10.0.0.5:443 (prevents EWMA poisoning)
            local addr = normalize_addr(raw_addr)
            if addr then
                addr_metrics[addr] = {rt_ms = rt_ms, status = status}
            end
        end
    end

    -- Update EWMA for each unique address (last attempt only)
    local alpha = _M.config.ewma_alpha

    for addr, metrics in pairs(addr_metrics) do
        local rt_ms = metrics.rt_ms
        local status = metrics.status

        -- Determine effective RT (penalize failures)
        local effective_rt = rt_ms
        if status and status >= 500 then
            -- 5xx response: use failure penalty (ChatGPT recommendation)
            effective_rt = math.max(rt_ms, _M.config.failure_penalty_ms)
            -- v2.7.0: Rate-limited failure logging (ChatGPT)
            if _M.config.log_failure_penalty then
                local log_key = upstream_name .. ":" .. addr
                local last_logged = failure_penalty_logged[log_key] or 0
                local now = ngx.now()
                if (now - last_logged) >= _M.config.log_failure_interval then
                    ngx.log(ngx.INFO, "adaptive: failure penalty for ", addr, " status=", status, " rt=", effective_rt, "ms")
                    failure_penalty_logged[log_key] = now
                end
            end
        end

        local ewma_key = upstream_name .. ":" .. addr .. ":ewma"
        local update_key = upstream_name .. ":" .. addr .. ":last_update"

        -- Update EWMA
        local old_ewma = metrics_dict:get(ewma_key)
        local new_ewma

        if old_ewma then
            new_ewma = alpha * effective_rt + (1 - alpha) * old_ewma
        else
            new_ewma = alpha * effective_rt + (1 - alpha) * _M.config.default_rt_ms
        end

        metrics_dict:set(ewma_key, new_ewma, _M.config.metrics_ttl)
        metrics_dict:set(update_key, now, _M.config.metrics_ttl)

        -- Store last raw value for debugging
        local raw_key = upstream_name .. ":" .. addr .. ":last_rt"
        metrics_dict:set(raw_key, rt_ms, _M.config.metrics_ttl)
    end
end

---
-- Get backend EWMA response time with age penalty and decay
-- Returns: ewma_ms, age_penalty
--
-- v2.2.0: Added exponential decay for very stale data (Grok recommendation)
--         After ewma_decay_start (1h), EWMA decays toward default_rt_ms
---
local function get_backend_ewma(upstream_name, server_addr)
    if not metrics_dict then
        return _M.config.default_rt_ms, 0
    end

    local ewma_key = upstream_name .. ":" .. server_addr .. ":ewma"
    local update_key = upstream_name .. ":" .. server_addr .. ":last_update"

    local ewma = metrics_dict:get(ewma_key)
    local last_update = metrics_dict:get(update_key)

    if not ewma then
        return _M.config.default_rt_ms, 0
    end

    local age = 0
    local age_penalty = 0

    if last_update then
        age = ngx.now() - last_update

        -- Calculate age penalty (Grok recommendation v2.1.0)
        if age > 0 and _M.config.age_penalty_window > 0 then
            -- Linear penalty: 0 at age=0, age_penalty_max at age=age_penalty_window
            age_penalty = math.min(_M.config.age_penalty_max, age / _M.config.age_penalty_window)
        end

        -- Exponential decay for VERY stale data (Grok recommendation v2.2.0)
        -- After decay_start (1h), EWMA decays exponentially toward default_rt_ms
        -- This prevents ancient good metrics from biasing toward idle servers
        local decay_start = _M.config.ewma_decay_start
        local decay_halflife = _M.config.ewma_decay_halflife

        if decay_start and decay_halflife and age > decay_start then
            -- Decay factor: halves every decay_halflife seconds after decay_start
            -- decay = e^(-ln(2) * (age - decay_start) / decay_halflife)
            local decay_time = age - decay_start
            local decay_factor = math.exp(-0.693147 * decay_time / decay_halflife)  -- ln(2) ≈ 0.693147

            -- Blend EWMA toward default: decayed_ewma = ewma * decay + default * (1 - decay)
            local default_rt = _M.config.default_rt_ms
            ewma = ewma * decay_factor + default_rt * (1 - decay_factor)
        end
    end

    return ewma, age_penalty
end

---
-- Get performance metrics for specific servers with cache invalidation
-- Only fetches metrics for the provided servers, not all keys in dict
-- Returns: table {server_addr => {ewma_ms, age_penalty}}
---
local function get_performance_for_servers(servers, upstream_name)
    local now = ngx.now()

    -- Compute server hash for cache invalidation (Grok recommendation)
    local server_hash = compute_server_hash(servers)
    local cache_key = upstream_name .. ":" .. server_hash

    -- Check if server list changed (invalidate cache)
    local old_hash = perf_cache.server_hashes[upstream_name]
    if old_hash and old_hash ~= server_hash then
        -- Server list changed, invalidate cache
        perf_cache.data[cache_key] = nil
        perf_cache.timestamps[cache_key] = nil
    end
    perf_cache.server_hashes[upstream_name] = server_hash

    -- Check worker-local cache
    local cache_ts = perf_cache.timestamps[cache_key] or 0
    if perf_cache.data[cache_key] and (now - cache_ts) < _M.config.cache_ttl then
        return perf_cache.data[cache_key]
    end

    -- Refresh from shared dict (only for provided servers!)
    -- v2.4.0: Use normalize_addr for consistent EWMA key lookup (ChatGPT)
    local metrics = {}

    for _, srv in ipairs(servers) do
        local host, port = extract_host_port(srv)
        -- v2.8.0: Handle Unix sockets (port may be nil) and skip invalid entries
        if host and port then
            local raw_addr = host .. ":" .. port
            local addr = normalize_addr(raw_addr)  -- Canonical key (v2.4.0)
            local ewma, age_penalty = get_backend_ewma(upstream_name, addr)
            metrics[addr] = {
                ewma = ewma,
                age_penalty = age_penalty,
            }
        end
    end

    -- Update cache
    perf_cache.data[cache_key] = metrics
    perf_cache.timestamps[cache_key] = now

    return metrics
end

---
-- Adaptive Weighted Least Response Time (Envoy-style)
-- Selects server with lowest weighted score using Little's Law
--
-- Formula: score = (ewma_rt / weight) * (1 + conn_count * queue_factor) * (1 + age_penalty)
--
-- This models: effective_latency = service_time * queue_multiplier * staleness_penalty
--
-- v2.2.0: Added weighted random tie-breaking for equal scores (Grok recommendation)
---
function _M.adaptive_least_response(servers, upstream_name, options)
    if not servers or #servers == 0 then
        ngx.log(ngx.ERR, "adaptive_least_response: no servers available")
        return nil
    end

    if #servers == 1 then
        local host, port = extract_host_port(servers[1])
        return host and {host = host, port = port} or nil
    end

    options = options or {}
    local tie_threshold = options.tie_threshold or _M.config.tie_threshold  -- from config (v2.4.0)

    -- Get performance metrics for these servers only (efficient!)
    local perf_metrics = get_performance_for_servers(servers, upstream_name)

    -- Collect all servers with scores for tie-breaking
    local scored_servers = {}
    local best_score = math.huge

    for _, srv in ipairs(servers) do
        local host, port = extract_host_port(srv)
        -- v2.8.0: Check both host and port (Unix sockets not supported for scoring)
        if not host or not port then goto continue end

        local weight = (type(srv) == "table" and srv.weight) or 1

        -- Apply slow start if configured (returns fractional weight during ramp)
        if type(srv) == "table" and srv.slow_start and srv.slow_start > 0 then
            local addr = srv.addr or (host .. ":" .. port)
            local ok, balancer = pcall(require, "balancer")
            if ok and balancer.apply_slow_start then
                weight = balancer.apply_slow_start(addr, weight, srv.slow_start, upstream_name)
            end
        end

        -- Preserve sub-integer ramp granularity; floor at 0.01 to avoid zero division below.
        if not weight or weight < 0.01 then weight = 0.01 end

        -- v2.4.0: Normalize address for consistent EWMA lookup (ChatGPT)
        local raw_addr = host .. ":" .. port
        local server_addr = normalize_addr(raw_addr)

        -- Get EWMA response time and age penalty
        local metrics = perf_metrics[server_addr]
        if not metrics then
            -- v2.7.0: Use jitter for cold servers to prevent tie bias (ChatGPT)
            local default_with_jitter = get_default_rt_with_jitter()
            metrics = {ewma = default_with_jitter, age_penalty = 0}
            -- v2.6.0: Rate-limited warning for missing metrics (Grok)
            if _M.config.warn_missing_metrics then
                local log_key = (upstream_name or "default") .. ":" .. server_addr
                local last_logged = missing_metrics_logged[log_key] or 0
                local now = ngx.now()
                if (now - last_logged) >= _M.config.warn_missing_interval then
                    ngx.log(ngx.WARN, "adaptive: no metrics for ", server_addr, " using default=", string.format("%.0f", default_with_jitter), "ms")
                    missing_metrics_logged[log_key] = now
                end
            end
        end
        local ewma_rt = metrics.ewma
        local age_penalty = metrics.age_penalty

        -- Get active connections
        local conn_count = 0
        if stats_dict then
            local key = (upstream_name or "default") .. ":conn:" .. host .. ":" .. port
            conn_count = stats_dict:get(key) or 0
        end

        -- Calculate score using Little's Law with improvements (Grok + ChatGPT)
        -- score = (ewma_rt / weight) * (1 + conn_count * queue_factor) * (1 + age_penalty)
        local base_score = ewma_rt / weight
        local queue_multiplier = 1 + (conn_count * _M.config.queue_factor)
        local staleness_multiplier = 1 + age_penalty
        local score = base_score * queue_multiplier * staleness_multiplier

        table.insert(scored_servers, {
            host = host,
            port = port,
            weight = weight,
            score = score,
        })

        if score < best_score then
            best_score = score
        end

        ::continue::
    end

    if #scored_servers == 0 then
        local host, port = extract_host_port(servers[1])
        return host and {host = host, port = port} or nil
    end

    -- Collect tied servers (within tie_threshold of best score) (Grok recommendation)
    local tie_limit = best_score * (1 + tie_threshold)
    local tied_servers = {}
    local total_weight = 0

    for _, srv in ipairs(scored_servers) do
        if srv.score <= tie_limit then
            table.insert(tied_servers, srv)
            total_weight = total_weight + srv.weight
        end
    end

    -- Weighted random selection among tied servers
    local best
    if #tied_servers == 1 then
        best = tied_servers[1]
    else
        -- Initialize random with high entropy (v2.3.0)
        init_random()

        -- Weighted random: pick based on weight proportion
        local rand = math.random() * total_weight
        local cumulative = 0

        for _, srv in ipairs(tied_servers) do
            cumulative = cumulative + srv.weight
            if rand <= cumulative then
                best = srv
                break
            end
        end

        -- Fallback (shouldn't happen)
        if not best then
            best = tied_servers[1]
        end
    end

    -- Increment connection counter
    if stats_dict then
        local key = (upstream_name or "default") .. ":conn:" .. best.host .. ":" .. best.port
        stats_dict:incr(key, 1, 0)

        -- Store for connection release (TTL 60s for long requests)
        -- v2.3.0: Use | delimiter instead of : to support IPv6 (ChatGPT)
        local req_id = ngx.var.connection .. "-" .. ngx.var.connection_requests
        local release_key = "release:" .. req_id
        local server_info_str = best.host .. "|" .. best.port .. "|" .. (upstream_name or "default")
        stats_dict:set(release_key, server_info_str, _M.config.release_ttl)
    end

    ngx.ctx.balancer_server = best
    ngx.ctx.balancer_upstream = upstream_name

    return best
end

---
-- Adaptive Round Robin with Performance Bias (Outlier Detection)
-- Standard round-robin but excludes servers with poor performance
-- Similar to Google Maglev / Envoy outlier detection
--
-- Skips servers with ewma_rt > (median * outlier_threshold)
---
function _M.adaptive_round_robin(servers, upstream_name, options)
    if not servers or #servers == 0 then
        return nil
    end

    options = options or {}
    local outlier_threshold = options.outlier_threshold or 2.0

    -- Get performance metrics
    local perf_metrics = get_performance_for_servers(servers, upstream_name)

    -- Calculate median response time (v2.5.0: use normalized addresses)
    local response_times = {}
    for _, srv in ipairs(servers) do
        local host, port = extract_host_port(srv)
        -- v2.8.0: Check both host and port
        if host and port then
            local raw_addr = host .. ":" .. port
            local addr = normalize_addr(raw_addr)
            local metrics = perf_metrics[addr] or {ewma = _M.config.default_rt_ms}
            table.insert(response_times, metrics.ewma)
        end
    end

    table.sort(response_times)
    local median = response_times[math.ceil(#response_times / 2)] or _M.config.default_rt_ms

    -- Filter out slow servers (> threshold × median) - outlier detection
    local threshold = median * outlier_threshold
    local good_servers = {}
    local filtered_servers = {}  -- v2.5.0: track filtered for logging

    for _, srv in ipairs(servers) do
        local host, port = extract_host_port(srv)
        -- v2.8.0: Check both host and port
        if host and port then
            local raw_addr = host .. ":" .. port
            local addr = normalize_addr(raw_addr)
            local metrics = perf_metrics[addr] or {ewma = _M.config.default_rt_ms}
            if metrics.ewma <= threshold then
                table.insert(good_servers, srv)
            else
                table.insert(filtered_servers, {addr = addr, ewma = metrics.ewma})
            end
        end
    end

    -- v2.6.0: Enhanced log with count/total + median (Grok improvement)
    if _M.config.log_filtered_servers and #filtered_servers > 0 then
        local filtered_list = {}
        for _, f in ipairs(filtered_servers) do
            table.insert(filtered_list, f.addr .. "(" .. string.format("%.0f", f.ewma) .. "ms)")
        end
        ngx.log(ngx.INFO, "adaptive_rr: filtered ", #filtered_servers, "/", #servers,
                " slow servers (median=", string.format("%.0f", median), "ms, threshold=",
                string.format("%.0f", threshold), "ms): ", table.concat(filtered_list, ", "))
    end

    -- If all servers are slow, use all (don't starve traffic)
    if #good_servers == 0 then
        good_servers = servers
    end

    -- Standard round-robin on filtered servers
    local selected_index = 1

    if #good_servers == 1 then
        selected_index = 1
    else
        if not counter_dict then
            -- Fallback: use time-based
            local now_ms = ngx.now() * 1000
            selected_index = (math.floor(now_ms) % #good_servers) + 1
        else
            -- Atomic increment
            local key = (upstream_name or "default") .. ":adaptive_rr_counter"
            local counter, err = counter_dict:incr(key, 1, 0)
            if not counter then
                selected_index = 1
            else
                selected_index = (counter % #good_servers) + 1
            end
        end
    end

    local server = good_servers[selected_index]
    local host, port = extract_host_port(server)

    if not host then
        return nil
    end

    local result = {host = host, port = port}
    ngx.ctx.balancer_server = result
    ngx.ctx.balancer_upstream = upstream_name

    return result
end

---
-- Release connection counter (call in log_by_lua_block)
---
function _M.release_connection()
    local dict = ngx.shared.balancer_stats
    if not dict then
        return
    end

    local req_id = ngx.var.connection .. "-" .. ngx.var.connection_requests
    local release_key = "release:" .. req_id
    local server_info = dict:get(release_key)

    if not server_info then
        return
    end

    -- Parse server info: "host|port|upstream_name" (v2.3.0: | delimiter for IPv6 support)
    local host, port, upstream_name = server_info:match("^([^|]+)|([^|]+)|(.+)$")
    if not host then
        -- Fallback: try old format for backwards compatibility during upgrade
        host, port, upstream_name = server_info:match("^([^:]+):(%d+):(.+)$")
        if not host then
            return
        end
    end

    -- Delete release key (prevent double-release)
    dict:delete(release_key)

    -- Decrement connection counter
    local key = upstream_name .. ":conn:" .. host .. ":" .. port
    dict:incr(key, -1, 0)
end

---
-- Get performance statistics for monitoring
---
function _M.get_performance_stats(upstream_name, servers)
    if not servers then
        return {}
    end

    local perf_metrics = get_performance_for_servers(servers, upstream_name)
    local stats = {}

    for addr, metrics in pairs(perf_metrics) do
        stats[addr] = {
            ewma_ms = metrics.ewma,
            age_penalty = metrics.age_penalty,
            adjusted_ewma = metrics.ewma * (1 + metrics.age_penalty),
        }
    end

    return stats
end

---
-- Get all known upstreams from cache (v2.5.0 - for metrics dashboards)
-- Returns: table {upstream_name => {backends => {addr => metrics}}}
--
-- Usage in content_by_lua for Prometheus:
--   local adaptive = require "balancer_adaptive"
--   local upstreams = adaptive.get_all_upstreams()
--   for upstream, data in pairs(upstreams) do
--       for addr, m in pairs(data.backends) do
--           ngx.say(string.format('adaptive_ewma{upstream="%s",backend="%s"} %f', upstream, addr, m.ewma))
--       end
--   end
---
function _M.get_all_upstreams()
    local result = {}

    -- Iterate worker-local cache (reflects recently accessed upstreams)
    for cache_key, metrics in pairs(perf_cache.data) do
        -- cache_key format: "upstream_name:server_hash"
        local upstream_name = cache_key:match("^([^:]+):")
        if upstream_name and metrics then
            if not result[upstream_name] then
                result[upstream_name] = {backends = {}}
            end
            for addr, m in pairs(metrics) do
                result[upstream_name].backends[addr] = {
                    ewma = m.ewma,
                    age_penalty = m.age_penalty,
                    adjusted_ewma = m.ewma * (1 + (m.age_penalty or 0)),
                }
            end
        end
    end

    return result
end

---
-- Get raw metrics for debugging
---
function _M.get_debug_metrics(upstream_name, server_addr)
    if not metrics_dict then
        return nil
    end

    -- v2.4.0: Normalize address and show in output (Grok recommendation)
    local normalized = normalize_addr(server_addr)

    local ewma_key = upstream_name .. ":" .. normalized .. ":ewma"
    local raw_key = upstream_name .. ":" .. normalized .. ":last_rt"
    local update_key = upstream_name .. ":" .. normalized .. ":last_update"

    local last_update = metrics_dict:get(update_key)
    local age = last_update and (ngx.now() - last_update) or nil

    return {
        input_addr = server_addr,           -- Original input (v2.4.0)
        normalized_addr = normalized,        -- Canonical key used (v2.4.0)
        ewma_ms = metrics_dict:get(ewma_key),
        last_rt_ms = metrics_dict:get(raw_key),
        last_update = last_update,
        age_seconds = age,
        default_rt_ms = _M.config.default_rt_ms,
        failure_penalty_ms = _M.config.failure_penalty_ms,
    }
end

---
-- Reset metrics for a server (useful when server restarts)
---
function _M.reset_metrics(upstream_name, server_addr)
    if not metrics_dict then
        return
    end

    -- v2.4.0: Normalize address for consistent key deletion
    local normalized = normalize_addr(server_addr)

    local ewma_key = upstream_name .. ":" .. normalized .. ":ewma"
    local raw_key = upstream_name .. ":" .. normalized .. ":last_rt"
    local update_key = upstream_name .. ":" .. normalized .. ":last_update"

    metrics_dict:delete(ewma_key)
    metrics_dict:delete(raw_key)
    metrics_dict:delete(update_key)

    -- v2.7.0: Only remove specific server from cache, not entire upstream (ChatGPT fix)
    -- This preserves metrics for other servers in the same upstream
    for cache_key, cache_data in pairs(perf_cache.data) do
        if string.match(cache_key, "^" .. upstream_name .. ":") then
            if cache_data[normalized] then
                cache_data[normalized] = nil
                -- Invalidate timestamp to force refresh on next access
                perf_cache.timestamps[cache_key] = 0
            end
        end
    end
    -- Note: Don't clear server_hashes - let cache rebuild naturally on next request
end

---
-- Reset ALL metrics for an entire upstream (v2.9.0 ChatGPT)
-- Useful for rolling restarts or deployments
--
-- Usage:
--   local adaptive = require "balancer_adaptive"
--   adaptive.reset_all_metrics("myupstream")
---
function _M.reset_all_metrics(upstream_name)
    if not metrics_dict then
        return 0
    end

    local deleted_count = 0
    local prefix = upstream_name .. ":"

    -- Get all keys from metrics_dict that match this upstream
    -- Note: get_keys(0) can be slow for large dicts, but reset is rare operation
    local keys = metrics_dict:get_keys(0)
    for _, key in ipairs(keys) do
        if string.sub(key, 1, #prefix) == prefix then
            metrics_dict:delete(key)
            deleted_count = deleted_count + 1
        end
    end

    -- Clear all caches for this upstream
    for cache_key, _ in pairs(perf_cache.data) do
        if string.match(cache_key, "^" .. upstream_name .. ":") then
            perf_cache.data[cache_key] = nil
            perf_cache.timestamps[cache_key] = nil
        end
    end
    perf_cache.server_hashes[upstream_name] = nil

    -- Clear rate-limit logs for this upstream
    for log_key, _ in pairs(missing_metrics_logged) do
        if string.match(log_key, "^" .. upstream_name .. ":") then
            missing_metrics_logged[log_key] = nil
        end
    end
    for log_key, _ in pairs(failure_penalty_logged) do
        if string.match(log_key, "^" .. upstream_name .. ":") then
            failure_penalty_logged[log_key] = nil
        end
    end

    ngx.log(ngx.INFO, "adaptive: reset all metrics for upstream '", upstream_name, "' (", deleted_count, " keys deleted)")
    -- v2.9.1: Return upstream_name for chaining (Grok)
    return upstream_name, deleted_count
end

---
-- Main select function
-- Methods: "adaptive_least_response", "adaptive_round_robin", "least_time"
---
function _M.select(servers, method, upstream_name, options)
    method = method or "adaptive_least_response"

    if method == "adaptive_least_response" or method == "adaptive" or method == "least_time" then
        return _M.adaptive_least_response(servers, upstream_name, options)
    elseif method == "adaptive_round_robin" or method == "adaptive_rr" then
        return _M.adaptive_round_robin(servers, upstream_name, options)
    else
        ngx.log(ngx.WARN, "Unknown adaptive method: ", method, ", using adaptive_least_response")
        return _M.adaptive_least_response(servers, upstream_name, options)
    end
end

return _M
