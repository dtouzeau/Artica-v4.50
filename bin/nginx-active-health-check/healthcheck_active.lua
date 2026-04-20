-- Active Health Check Module with Real-Time Updates
-- Stores health status in shared memory for instant access
-- Receives real-time updates from health checker via HTTP API

local cjson = require "cjson"

local _M = {
    _VERSION = '2.1.0',  -- Updated with performance improvements
    state_file = '/var/run/nginx-healthcheck/state.json',
}

-- Shared dictionary for health status (configured in nginx.conf)
local health_dict = ngx.shared.healthcheck_status

-- Background sync timer handle
local sync_timer

-- Worker-local cache for state file (reduces file I/O)
local state_cache = {
    data = nil,
    timestamp = 0,
    ttl = 0.1,  -- 100ms cache (reduces file reads by 10x)
}

-- Update health status in shared dict
local function set_server_health(upstream_name, server_addr, healthy, timestamp)
    if not health_dict then
        ngx.log(ngx.ERR, "Shared dict 'healthcheck_status' not configured!")
        return false
    end

    local key = upstream_name .. ":" .. server_addr
    local value = cjson.encode({
        healthy = healthy,
        timestamp = timestamp or ngx.time()
    })

    local ok, err = health_dict:set(key, value)
    if not ok then
        ngx.log(ngx.ERR, "Failed to set health status: ", err)
        return false
    end

    return true
end

-- Get health status from shared dict
local function get_server_health(upstream_name, server_addr)
    if not health_dict then
        ngx.log(ngx.WARN, "Shared dict not configured, using file fallback")
        return nil
    end

    local key = upstream_name .. ":" .. server_addr
    local value, flags = health_dict:get(key)

    if not value then
        return nil
    end

    local ok, data = pcall(cjson.decode, value)
    if not ok then
        ngx.log(ngx.ERR, "Failed to decode health data: ", data)
        return nil
    end

    return data.healthy
end

-- Get all healthy servers from shared dict
local function get_healthy_servers_from_dict(upstream_name)
    if not health_dict then
        return nil
    end

    local healthy = {}
    local keys = health_dict:get_keys(0)  -- Get all keys

    for _, key in ipairs(keys) do
        -- Check if key belongs to this upstream
        local up, server = key:match("^([^:]+):(.+)$")
        if up == upstream_name then
            local value = health_dict:get(key)
            if value then
                local ok, data = pcall(cjson.decode, value)
                if ok and data.healthy then
                    table.insert(healthy, server)
                end
            end
        end
    end

    return healthy
end

-- Sync from state file to shared dict (background task)
local function sync_from_file()
    local file = io.open(_M.state_file, "r")
    if not file then
        return
    end

    local content = file:read("*all")
    file:close()

    local ok, state = pcall(cjson.decode, content)
    if not ok or not state or not state.upstreams then
        return
    end

    -- Resolve balancer module once for ramp-up reset on recovery transitions.
    -- Without this, a missed real-time POST from the Go notifier leaves the
    -- old slow_start timer intact — a recovered server would skip ramp-up.
    local balancer_ok, balancer = pcall(require, "balancer")

    -- Update shared dict with file data
    local count = 0
    for upstream_name, upstream_data in pairs(state.upstreams) do
        if upstream_data.servers then
            for server_addr, server_data in pairs(upstream_data.servers) do
                -- Detect unhealthy→healthy transition and reset slow_start timer
                -- so ramp-up restarts cleanly even when the real-time notifier
                -- POST didn't reach us.
                if server_data.healthy and balancer_ok and balancer.reset_slow_start then
                    local prev_healthy = get_server_health(upstream_name, server_addr)
                    if prev_healthy ~= true then
                        balancer.reset_slow_start(server_addr, upstream_name)
                    end
                end

                if set_server_health(upstream_name, server_addr, server_data.healthy, server_data.last_check) then
                    count = count + 1
                end
            end
        end
    end

    if count > 0 then
        ngx.log(ngx.INFO, "Synced ", count, " server health statuses from file")
    end
end

-- Background timer to sync from file periodically
local function start_sync_timer()
    if sync_timer then
        return  -- Already running
    end

    local function timer_callback(premature)
        if premature then
            return
        end

        -- Sync from file
        sync_from_file()

        -- Schedule next run (every 5 seconds)
        local ok, err = ngx.timer.at(5, timer_callback)
        if not ok then
            ngx.log(ngx.ERR, "Failed to create timer: ", err)
            sync_timer = nil
        end
    end

    -- Start the timer
    local ok, err = ngx.timer.at(5, timer_callback)
    if not ok then
        ngx.log(ngx.ERR, "Failed to start sync timer: ", err)
    else
        sync_timer = true
        ngx.log(ngx.INFO, "Started background health status sync timer")
    end
end

-- Public API: Get healthy servers (uses shared dict)
function _M.get_healthy_servers(upstream_name)
    -- Try shared dict first (real-time data)
    local healthy = get_healthy_servers_from_dict(upstream_name)

    if healthy and #healthy > 0 then
        return healthy
    end

    -- Fallback to file if shared dict is empty
    ngx.log(ngx.WARN, "Shared dict empty for ", upstream_name, ", using file fallback")

    -- Read from file as fallback
    local file = io.open(_M.state_file, "r")
    if not file then
        return {}
    end

    local content = file:read("*all")
    file:close()

    local ok, state = pcall(cjson.decode, content)
    if not ok or not state or not state.upstreams then
        return {}
    end

    local upstream = state.upstreams[upstream_name]
    if not upstream or not upstream.servers then
        return {}
    end

    healthy = {}
    for addr, server in pairs(upstream.servers) do
        if server.healthy then
            table.insert(healthy, addr)
        end
    end

    return healthy
end

-- Load state from file with worker-local caching (PERFORMANCE OPTIMIZATION)
local function load_state_cached()
    local now = ngx.now()

    -- Check worker-local cache first (fastest - pure Lua table lookup)
    if state_cache.data and (now - state_cache.timestamp) < state_cache.ttl then
        return state_cache.data
    end

    -- Read from file
    local file = io.open(_M.state_file, "r")
    if not file then
        return nil
    end

    local content = file:read("*all")
    file:close()

    local ok, state = pcall(cjson.decode, content)
    if not ok or not state then
        return nil
    end

    -- Cache in worker-local memory
    state_cache.data = state
    state_cache.timestamp = now

    return state
end

-- Public API: Get healthy servers with full configuration (weight, slow_start, etc.)
-- Returns array of {addr, weight, slow_start, ...}
-- OPTIMIZED: Uses worker-local cache to reduce file I/O by 10x
function _M.get_healthy_servers_with_config(upstream_name)
    -- Load state with caching (reduces file I/O)
    local state = load_state_cached()

    if not state or not state.upstreams then
        ngx.log(ngx.ERR, "Failed to load state file")
        return {}
    end

    local upstream = state.upstreams[upstream_name]
    if not upstream or not upstream.servers then
        ngx.log(ngx.WARN, "Upstream not found or no servers: ", upstream_name)
        return {}
    end

    local healthy = {}
    for addr, server in pairs(upstream.servers) do
        -- Check health from shared dict (real-time) or fall back to file
        local is_healthy = get_server_health(upstream_name, addr)
        if is_healthy == nil then
            is_healthy = server.healthy
        end

        if is_healthy and not server.down then
            table.insert(healthy, {
                addr = addr,
                weight = server.weight or 1,
                slow_start = server.slow_start or 0,  -- in seconds
                max_fails = server.max_fails or 0,
                fail_timeout = server.fail_timeout or 0,
                backup = server.backup or false
            })
        end
    end

    return healthy
end

-- Public API: Check if specific server is healthy
function _M.is_healthy(upstream_name, server_addr)
    -- Try shared dict first
    local healthy = get_server_health(upstream_name, server_addr)

    if healthy ~= nil then
        return healthy
    end

    -- Fallback to file
    local file = io.open(_M.state_file, "r")
    if not file then
        return nil
    end

    local content = file:read("*all")
    file:close()

    local ok, state = pcall(cjson.decode, content)
    if not ok or not state or not state.upstreams then
        return nil
    end

    local upstream = state.upstreams[upstream_name]
    if not upstream or not upstream.servers then
        return nil
    end

    local server = upstream.servers[server_addr]
    if not server then
        return nil
    end

    return server.healthy
end

-- Public API: Get all servers with status
function _M.get_all_servers(upstream_name)
    local servers = {}

    if health_dict then
        local keys = health_dict:get_keys(0)
        for _, key in ipairs(keys) do
            local up, server_addr = key:match("^([^:]+):(.+)$")
            if up == upstream_name then
                local value = health_dict:get(key)
                if value then
                    local ok, data = pcall(cjson.decode, value)
                    if ok then
                        servers[server_addr] = {
                            healthy = data.healthy,
                            last_check = data.timestamp
                        }
                    end
                end
            end
        end
    end

    return servers
end

-- Public API: Get slow start ramp-up status for all servers in an upstream
-- Returns map of server_addr -> ramp-up status (only servers currently in ramp-up)
function _M.get_ramp_up_status(upstream_name)
    local balancer_ok, balancer = pcall(require, "balancer")
    if not balancer_ok or not balancer.get_slow_start_status then
        return {}
    end

    local servers = _M.get_healthy_servers_with_config(upstream_name)
    local status = {}

    for _, server in ipairs(servers) do
        if server.slow_start and server.slow_start > 0 then
            local info = balancer.get_slow_start_status(
                server.addr, server.weight, server.slow_start, upstream_name)
            if info then
                status[server.addr] = info
            end
        end
    end

    return status
end

-- Public API: Handle real-time update from health checker
function _M.handle_update(upstream_name, server_addr, healthy, timestamp)
    local ok = set_server_health(upstream_name, server_addr, healthy, timestamp)

    if ok then
        local status = healthy and "HEALTHY" or "UNHEALTHY"
        ngx.log(ngx.INFO, "Real-time update: ", upstream_name, "/", server_addr, " is ", status)

        -- Reset slow start timer when server becomes healthy
        -- This ensures proper ramp-up on recovery (fixes missing reset_slow_start call)
        if healthy then
            local balancer_ok, balancer = pcall(require, "balancer")
            if balancer_ok and balancer.reset_slow_start then
                balancer.reset_slow_start(server_addr, upstream_name)
                ngx.log(ngx.DEBUG, "Reset slow_start timer for ", upstream_name, "/", server_addr)
            end
        end
    end

    return ok
end

-- Initialize: called in init_by_lua phase
function _M.init()
    -- Just log initialization, don't start timers here
    ngx.log(ngx.INFO, "Active health check module loaded")
end

-- Initialize worker: called in init_worker_by_lua phase
function _M.init_worker()
    local worker_id = ngx.worker.id()
    if worker_id == 0 then  -- Only in first worker
        -- Initial sync from file
        sync_from_file()

        -- Start background sync timer
        start_sync_timer()

        ngx.log(ngx.INFO, "Active health check worker initialized (worker ", worker_id, ")")
    end
end

return _M
