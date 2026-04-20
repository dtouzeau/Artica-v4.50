-- Sticky Session Module for Nginx Load Balancing
-- Supports sticky cookie, sticky route, and consistent hashing
-- Compatible with active health checking
--
-- Version: 3.0.0 - TRUE consistent hashing with resty.chash (minimal session disruption)
--                   Fixes: CRC32 is NOT a hash function + massive remapping on server changes
--
-- Breaking changes from v2.x:
--   - Now uses jump consistent hash (1/N remapping vs N-1/N with modulo)
--   - Requires lua-resty-chash (install: luarocks install lua-resty-chash)
--   - Better distribution and session persistence during server pool changes

local _M = {}
local cjson = require "cjson"
local resty_chash = require "resty.chash"

-- Configuration
_M.cookie_name = "nginx_route"
_M.cookie_expires = 3600  -- 1 hour
_M.cookie_path = "/"
_M.cookie_domain = nil
_M.cookie_secure = false
_M.cookie_httponly = true

-- Worker-local cache for consistent hash rings
-- Avoids rebuilding on every request (performance optimization)
local chash_cache = {}

-- Build cache key from server list (to detect changes)
local function build_cache_key(servers)
    local parts = {}
    for _, server in ipairs(servers) do
        local weight = server.weight or 1
        table.insert(parts, server.address .. ":" .. weight)
    end
    table.sort(parts)  -- Sort for consistent key
    return table.concat(parts, "|")
end

-- Get or create consistent hash ring (cached per worker)
local function get_chash_ring(servers)
    local cache_key = build_cache_key(servers)

    -- Return cached ring if server list unchanged
    if chash_cache[cache_key] then
        return chash_cache[cache_key]
    end

    -- Build new ring
    -- resty.chash:new() expects {node_name = weight} format (dictionary, not array)
    -- Weights scaled by 100 to preserve sub-integer slow_start ramp granularity
    local nodes = {}
    for _, server in ipairs(servers) do
        local weight = server.weight or 1

        -- Apply slow start if configured (may return a fractional weight during ramp)
        if server.slow_start and server.slow_start > 0 then
            local balancer = require "balancer"
            weight = balancer.apply_slow_start(server.address, weight, server.slow_start, "sticky")
        end

        nodes[server.address] = math.max(1, math.floor((weight or 1) * 100 + 0.5))
    end

    local chash_instance = resty_chash:new(nodes)

    -- Cache for this worker (cleared when server list changes)
    chash_cache[cache_key] = chash_instance

    -- Limit cache size (prevent memory leak if server list changes frequently)
    local cache_size = 0
    for _ in pairs(chash_cache) do
        cache_size = cache_size + 1
    end

    if cache_size > 100 then
        -- Clear old cache entries (simple LRU: clear all)
        chash_cache = {[cache_key] = chash_instance}
    end

    return chash_instance
end

-- Get sticky cookie value
local function get_sticky_cookie(cookie_name)
    local cookie = ngx.var["cookie_" .. cookie_name]
    return cookie
end

-- Set sticky cookie
local function set_sticky_cookie(cookie_name, value, expires, path, domain, secure, httponly)
    local cookie_parts = {cookie_name .. "=" .. value}

    if expires then
        table.insert(cookie_parts, "Max-Age=" .. expires)
    end

    if path then
        table.insert(cookie_parts, "Path=" .. path)
    end

    if domain then
        table.insert(cookie_parts, "Domain=" .. domain)
    end

    if secure then
        table.insert(cookie_parts, "Secure")
    end

    if httponly then
        table.insert(cookie_parts, "HttpOnly")
    end

    local cookie_string = table.concat(cookie_parts, "; ")
    ngx.header["Set-Cookie"] = cookie_string
end

-- Hash function for route-based hashing
-- Used for select_with_route() where input is diverse (UUIDs, session IDs)
-- For IP-based hashing, we delegate to balancer.ip_hash() which uses MD5 for better distribution
local function hash_string(str)
    local hash = 5381
    for i = 1, #str do
        hash = ((hash * 33) + string.byte(str, i)) % 4294967296
    end
    return hash
end

-- Select server using sticky cookie
-- NGINX Plus compatible: Uses TRUE consistent hashing (resty.chash)
-- Minimizes session disruption when servers are added/removed/fail
--
-- Benefits over v2.x (CRC32 + modulo):
--   - Proper hash function (not CRC32 which is for error detection!)
--   - Minimal remapping: only 1/N sessions move when pool changes (vs ~100% with modulo)
--   - Better distribution: no clustering/collisions
function _M.select_with_cookie(servers, cookie_name)
    if not servers or #servers == 0 then
        return nil
    end

    -- Get existing cookie
    local cookie_value = get_sticky_cookie(cookie_name or _M.cookie_name)

    if cookie_value then
        -- Get cached consistent hash ring (rebuilds only when server list changes)
        local chash_instance = get_chash_ring(servers)

        -- Find server for this cookie (consistent hashing!)
        local selected_id = chash_instance:find(cookie_value)

        -- Find server object by address
        for _, server in ipairs(servers) do
            if server.address == selected_id then
                ngx.log(ngx.INFO, "Sticky cookie '", cookie_value,
                        "' → chash → server=", server.address)
                return server, false  -- false = existing session
            end
        end

        -- Fallback (should never happen)
        ngx.log(ngx.WARN, "Consistent hash returned unknown server: ", selected_id)
        return servers[1], false
    end

    -- No cookie: select new server using weighted round-robin.
    -- Precompute scaled weights (x100) once so fractional slow_start ramps
    -- are integer-precise under math.random bounds.
    local scaled_weights = {}
    local total_weight = 0
    for i, server in ipairs(servers) do
        local weight = server.weight or 1

        -- Apply slow start if configured (may return fractional weight during ramp)
        if server.slow_start and server.slow_start > 0 then
            local balancer = require "balancer"
            weight = balancer.apply_slow_start(server.address, weight, server.slow_start, "sticky")
        end

        local scaled = math.max(1, math.floor((weight or 1) * 100 + 0.5))
        scaled_weights[i] = scaled
        total_weight = total_weight + scaled
    end

    local random_weight = math.random(total_weight)
    local cumulative_weight = 0

    for i, server in ipairs(servers) do
        cumulative_weight = cumulative_weight + scaled_weights[i]
        if random_weight <= cumulative_weight then
            -- Note: We don't set cookie here anymore
            -- Application manages cookie (set_cookie=false mode)
            -- Or use sticky_response.lua in header_filter_by_lua_block for set_cookie=true
            return server, true  -- true = new session
        end
    end

    return servers[1], true
end

-- Select server using sticky route (from URI parameter or header)
-- Uses same consistent hashing as cookies for minimal remapping
function _M.select_with_route(servers, route_param, route_header)
    if not servers or #servers == 0 then
        return nil
    end

    local route_value = nil

    -- Try to get route from URI parameter
    if route_param then
        route_value = ngx.var["arg_" .. route_param]
    end

    -- Fall back to header if parameter not found
    if not route_value and route_header then
        route_value = ngx.var["http_" .. route_header:lower():gsub("-", "_")]
    end

    if route_value then
        -- Get cached consistent hash ring (same as cookie method)
        local chash_instance = get_chash_ring(servers)
        local selected_id = chash_instance:find(route_value)

        -- Find server by address
        for _, server in ipairs(servers) do
            if server.address == selected_id then
                ngx.log(ngx.INFO, "Sticky route '", route_value, "' → server=", server.address)
                return server
            end
        end

        return servers[1]  -- Fallback
    end

    -- No route found, use weighted round-robin
    return _M.select_with_cookie(servers, _M.cookie_name)
end

-- Select server using consistent hashing based on client IP
-- Delegates to balancer.ip_hash() for MD5-based distribution (v3.4.0+)
-- Supports both simple modulo and consistent hashing
--
-- Options:
--   consistent: boolean - Use consistent hashing (default: false)
--   use_binary: boolean - Use binary_remote_addr (default: true)
--
-- Examples:
--   Simple modulo:       select_with_ip_hash(servers)
--   Consistent hashing:  select_with_ip_hash(servers, {consistent = true})
function _M.select_with_ip_hash(servers, options)
    if not servers or #servers == 0 then
        return nil
    end

    -- Convert sticky.lua server format (server.address) to balancer format (server.addr)
    local balancer_servers = {}
    for i, server in ipairs(servers) do
        balancer_servers[i] = {
            addr = server.address,
            weight = server.weight
        }
    end

    -- Delegate to main ip_hash function (benefits from v3.4.0 MD5 improvements)
    local balancer = require "balancer"
    local selected_addr = balancer.ip_hash(balancer_servers, "sticky", options)

    if not selected_addr then
        return nil
    end

    -- Find and return the original server object that matches the selected address
    for _, server in ipairs(servers) do
        if server.address == selected_addr then
            return server
        end
    end

    -- Fallback: return first server if no match found
    return servers[1]
end

-- Select server with session persistence (auto-detect method)
-- Config options:
--   sticky_cookie: boolean - Use cookie-based persistence
--   sticky_route: boolean - Use route-based persistence
--   ip_hash: boolean - Use IP hash persistence
--   ip_hash_consistent: boolean - Use consistent hashing for IP hash (default: false)
--   ip_hash_use_binary: boolean - Use binary_remote_addr for IP hash (default: true)
function _M.select_server(servers, config)
    config = config or {}

    if config.sticky_cookie then
        return _M.select_with_cookie(servers, config.cookie_name)
    elseif config.sticky_route then
        return _M.select_with_route(servers, config.route_param, config.route_header)
    elseif config.ip_hash then
        -- Pass options to select_with_ip_hash for consistent hashing support
        local ip_hash_options = {
            consistent = config.ip_hash_consistent or false,
            use_binary = config.ip_hash_use_binary
        }
        return _M.select_with_ip_hash(servers, ip_hash_options)
    else
        -- Default: weighted round-robin without stickiness
        return _M.select_with_cookie(servers, nil)
    end
end

-- Get session information for monitoring
function _M.get_session_info()
    local cookie_value = get_sticky_cookie(_M.cookie_name)

    return {
        has_session = cookie_value ~= nil,
        session_id = cookie_value,
        client_ip = ngx.var.remote_addr,
        user_agent = ngx.var.http_user_agent
    }
end

return _M
