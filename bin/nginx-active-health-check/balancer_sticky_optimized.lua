-- HIGH-PERFORMANCE Balancer with Sticky Sessions
-- Uses nginx shared memory instead of file I/O for 100x+ speed improvement
-- v1.2.1 - Fixed server format normalization (handles 'addr' vs 'address')
-- v1.2.0 - Added support for passing servers in config (healthcheck integration)
-- v1.1.0 - Added response-phase cookie setting support

local sticky = require "sticky"
local balancer = require "ngx.balancer"
local cjson = require "cjson.safe"

local _M = {}

-- Configuration
_M.config = {
    -- Server configuration
    servers = nil,  -- Optional: Pass servers directly (e.g., from healthcheck.get_healthy_servers())
                    -- If nil, will fetch from shared_dict_name

    -- Sticky session settings
    sticky_cookie = false,
    sticky_route = false,
    ip_hash = false,
    cookie_name = "nginx_route",
    route_param = "route",
    route_header = "X-Route",

    -- Response-phase cookie setting (NGINX Plus-like behavior)
    set_cookie = false,  -- Enable automatic Set-Cookie in responses
    cookie_expires = nil,  -- Cookie expiry in seconds (e.g., 3600 for 1 hour)
    cookie_domain = nil,  -- Cookie domain (e.g., ".example.com")
    cookie_path = "/",  -- Cookie path
    cookie_httponly = true,  -- HttpOnly flag (default: true)
    cookie_secure = false,  -- Secure flag (default: false)
    cookie_samesite = nil,  -- SameSite attribute ("Strict", "Lax", or "None")

    -- Fallback behavior
    failover_to_backup = true,
    failover_to_down = false,

    -- Shared memory configuration
    shared_dict_name = "health_state",
    cache_ttl = 0.1,  -- Cache parsed state for 100ms (10x faster than 1s)
}

-- Worker-local cache (per-worker process cache, no shared memory access needed)
local worker_cache = {
    state = nil,
    timestamp = 0,
}

-- Get upstream state from shared memory (FAST - no file I/O!)
local function get_state_from_shared_memory()
    local now = ngx.now()

    -- Check worker-local cache first (fastest - pure Lua table lookup)
    if worker_cache.state and (now - worker_cache.timestamp) < _M.config.cache_ttl then
        return worker_cache.state
    end

    -- Get from shared memory (fast - in-process memory access)
    local health_state = ngx.shared[_M.config.shared_dict_name]
    if not health_state then
        ngx.log(ngx.ERR, "Shared dict '", _M.config.shared_dict_name, "' not found")
        return nil
    end

    local json_str = health_state:get("state")
    if not json_str then
        ngx.log(ngx.WARN, "No health state in shared memory")
        return nil
    end

    -- Parse JSON (only once per cache TTL)
    local state, err = cjson.decode(json_str)
    if not state then
        ngx.log(ngx.ERR, "Failed to parse health state: ", err)
        return nil
    end

    -- Cache in worker-local memory
    worker_cache.state = state
    worker_cache.timestamp = now

    return state
end

-- Get servers for upstream (optimized)
local function get_upstream_servers(upstream_name)
    local servers = {}

    -- Load state from shared memory
    local state = get_state_from_shared_memory()

    if not state or not state.upstreams then
        return servers
    end

    -- Find upstream
    local upstream_state = state.upstreams[upstream_name]
    if not upstream_state or not upstream_state.servers then
        return servers
    end

    -- Build server list
    for _, server_state in pairs(upstream_state.servers) do
        local server = {
            address = server_state.address,
            weight = server_state.weight or 1,
            backup = server_state.backup or false,
            down = server_state.down or false,
            healthy = server_state.healthy,
            available = false,
        }

        -- Server available if not down and healthy
        if not server.down then
            server.available = server.healthy
        end

        table.insert(servers, server)
    end

    return servers
end

-- Normalize server format (handles both healthcheck and shared memory formats)
local function normalize_servers(servers)
    local normalized = {}

    for _, server in ipairs(servers) do
        local normalized_server = {
            -- Handle both 'addr' (from healthcheck) and 'address' (from shared memory)
            address = server.address or server.addr,
            weight = server.weight or 1,
            backup = server.backup or false,
            down = server.down or false,
            slow_start = server.slow_start or 0,
            max_fails = server.max_fails or 0,
            fail_timeout = server.fail_timeout or 0,
            -- Set health status (default to healthy if not specified)
            healthy = server.healthy ~= nil and server.healthy or true,
            available = false,
        }

        -- Server is available if not down and healthy
        if not normalized_server.down then
            normalized_server.available = normalized_server.healthy
        end

        table.insert(normalized, normalized_server)
    end

    return normalized
end

-- Filter servers by criteria
local function filter_servers(servers, include_backup, include_down)
    local filtered = {}

    for _, server in ipairs(servers) do
        local should_include = server.available

        if not include_backup and server.backup then
            should_include = false
        end

        if not include_down and server.down then
            should_include = false
        end

        if should_include then
            table.insert(filtered, server)
        end
    end

    return filtered
end

-- Select server with sticky session support
function _M.select_server(upstream_name, sticky_config, state_file_path)
    sticky_config = sticky_config or _M.config

    -- Get servers: use provided servers or fetch from shared memory
    local all_servers
    if sticky_config.servers and #sticky_config.servers > 0 then
        -- Use servers passed in config (e.g., from healthcheck.get_healthy_servers())
        -- Normalize format (handles 'addr' vs 'address', adds missing fields)
        all_servers = normalize_servers(sticky_config.servers)
        -- ngx.log(ngx.INFO, "Using ", #all_servers, " servers from config for ", upstream_name)
    else
        -- Fallback: get from shared memory (FAST!)
        all_servers = get_upstream_servers(upstream_name)
        -- ngx.log(ngx.INFO, "Using ", #all_servers, " servers from shared memory for ", upstream_name)
    end

    -- Try primary servers first
    local primary_servers = filter_servers(all_servers, false, false)

    if #primary_servers > 0 then
        if sticky_config.sticky_cookie then
            return sticky.select_with_cookie(primary_servers, sticky_config.cookie_name)
        elseif sticky_config.sticky_route then
            return sticky.select_with_route(
                primary_servers,
                sticky_config.route_param,
                sticky_config.route_header
            )
        elseif sticky_config.ip_hash then
            -- Pass consistent hashing options to select_with_ip_hash (v3.4.0+)
            local ip_hash_options = {
                consistent = sticky_config.ip_hash_consistent or false,
                use_binary = sticky_config.ip_hash_use_binary
            }
            return sticky.select_with_ip_hash(primary_servers, ip_hash_options)
        else
            return sticky.select_with_cookie(primary_servers, nil)
        end
    end

    -- Fallback to backup servers
    if sticky_config.failover_to_backup then
        local backup_servers = {}
        for _, server in ipairs(all_servers) do
            if server.backup and server.available then
                table.insert(backup_servers, server)
            end
        end

        if #backup_servers > 0 then
            ngx.log(ngx.WARN, "All primary servers unhealthy, using backup")
            return sticky.select_with_cookie(backup_servers, sticky_config.cookie_name)
        end
    end

    -- Last resort: down servers
    if sticky_config.failover_to_down then
        ngx.log(ngx.ERR, "All servers unhealthy, using down servers")
        return sticky.select_with_cookie(all_servers, sticky_config.cookie_name)
    end

    return nil
end

-- Balance request
function _M.balance(upstream_name, sticky_config, state_file_path)
    sticky_config = sticky_config or _M.config

    local server = _M.select_server(upstream_name, sticky_config, state_file_path)

    if not server then
        ngx.log(ngx.ERR, "No available servers for upstream: ", upstream_name)
        return ngx.exit(503)
    end

    -- Parse server address
    local host, port = server.address:match("^([^:]+):(%d+)$")
    if not host or not port then
        ngx.log(ngx.ERR, "Invalid server address: ", server.address)
        return ngx.exit(500)
    end

    -- If set_cookie is enabled, store info for response phase
    if sticky_config.set_cookie and sticky_config.sticky_cookie then
        ngx.ctx.selected_backend_for_cookie = server.address
        ngx.ctx.sticky_upstream_name = upstream_name

        -- Register cookie configuration for this upstream (first time only)
        local sticky_response = require "sticky_response"
        sticky_response.register_config(upstream_name, sticky_config)
    end

    -- Set upstream server
    local ok, err = balancer.set_current_peer(host, tonumber(port))
    if not ok then
        ngx.log(ngx.ERR, "Failed to set peer: ", err)
        return ngx.exit(500)
    end

    -- Set timeouts
    balancer.set_timeouts(5, 5, 5)

    return ngx.OK
end

return _M
