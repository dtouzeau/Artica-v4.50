-- Enhanced Balancer with Sticky Sessions and Active Health Checking
-- Combines session persistence with real-time health status
-- v1.1.0 - Added response-phase cookie setting support

local healthcheck = require "healthcheck_active"
local sticky = require "sticky"
local balancer = require "ngx.balancer"
local cjson = require "cjson.safe"

local _M = {}

-- Balancer configuration
_M.config = {
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

    -- Fallback behavior when all servers unhealthy
    failover_to_backup = true,
    failover_to_down = false,  -- Use down servers as last resort

    -- State file configuration
    state_file = "/var/run/nginx-healthcheck/state.json",
    state_cache_ttl = 1,  -- Cache state file for 1 second
}

-- Cached state data
local state_cache = {
    data = nil,
    timestamp = 0,
}

-- Load upstream state from JSON file
local function load_state_file(state_file_path)
    local now = ngx.now()

    -- Return cached data if still valid
    if state_cache.data and (now - state_cache.timestamp) < _M.config.state_cache_ttl then
        return state_cache.data
    end

    -- Read state file
    local file = io.open(state_file_path, "r")
    if not file then
        ngx.log(ngx.WARN, "Cannot open state file: ", state_file_path)
        return nil
    end

    local content = file:read("*all")
    file:close()

    if not content or content == "" then
        ngx.log(ngx.WARN, "State file is empty: ", state_file_path)
        return nil
    end

    -- Parse JSON
    local state, err = cjson.decode(content)
    if not state then
        ngx.log(ngx.ERR, "Failed to parse state file JSON: ", err)
        return nil
    end

    -- Cache the parsed data
    state_cache.data = state
    state_cache.timestamp = now

    return state
end

-- Get servers from upstream state file
local function get_upstream_servers(upstream_name, state_file_path)
    local servers = {}

    -- Load state file
    state_file_path = state_file_path or _M.config.state_file
    local state = load_state_file(state_file_path)

    if not state or not state.upstreams then
        ngx.log(ngx.WARN, "No upstream state data available")
        return servers
    end

    -- Find upstream in state
    local upstream_state = state.upstreams[upstream_name]
    if not upstream_state or not upstream_state.servers then
        ngx.log(ngx.WARN, "Upstream not found in state: ", upstream_name)
        return servers
    end

    -- Build server list from state file
    for _, server_state in ipairs(upstream_state.servers) do
        local server = {
            address = server_state.address,
            weight = server_state.weight or 1,
            backup = server_state.backup or false,
            down = server_state.down or false,
            healthy = server_state.healthy,
            available = false,
        }

        -- Server is available if:
        -- 1. Not marked down AND
        -- 2. Healthy (from state file)
        if not server.down then
            server.available = server.healthy
        end

        table.insert(servers, server)
    end

    return servers
end

-- Filter servers by criteria
local function filter_servers(servers, include_backup, include_down)
    local filtered = {}

    for _, server in ipairs(servers) do
        local should_include = server.available

        -- Exclude backup servers unless requested
        if not include_backup and server.backup then
            should_include = false
        end

        -- Exclude down servers unless requested
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

    -- Get all servers with health status from state file
    local all_servers = get_upstream_servers(upstream_name, state_file_path)

    -- Try primary servers first (non-backup, non-down, healthy)
    local primary_servers = filter_servers(all_servers, false, false)

    if #primary_servers > 0 then
        -- Use sticky session if enabled
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
            -- Default weighted round-robin
            return sticky.select_with_cookie(primary_servers, nil)
        end
    end

    -- Fallback to backup servers if primary all unhealthy
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

    -- Last resort: use down servers if configured
    if sticky_config.failover_to_down then
        ngx.log(ngx.ERR, "All servers unhealthy, using down servers as last resort")
        return sticky.select_with_cookie(all_servers, sticky_config.cookie_name)
    end

    return nil
end

-- Balance request (called by ngx.balancer)
function _M.balance(upstream_name, sticky_config, state_file_path)
    sticky_config = sticky_config or _M.config

    local server = _M.select_server(upstream_name, sticky_config, state_file_path)

    if not server then
        ngx.log(ngx.ERR, "No available servers for upstream: ", upstream_name)
        return ngx.exit(503)  -- Service Unavailable
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
    balancer.set_timeouts(5, 5, 5)  -- connect, send, read (seconds)

    return ngx.OK
end

return _M
