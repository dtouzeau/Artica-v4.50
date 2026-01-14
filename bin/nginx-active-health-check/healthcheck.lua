-- Nginx Lua module for reading health check status
-- Integrates with nginx-active-health-check Go service

local cjson = require "cjson"
local io = require "io"

local _M = {
    _VERSION = '1.0.0',
    state_file = '/var/run/nginx-healthcheck/state.json',
    cache_ttl = 1,  -- Cache state for 1 second
    last_read = 0,
    cached_state = nil
}

-- Read and parse the health state file
local function read_state_file(file_path)
    local file = io.open(file_path, "r")
    if not file then
        ngx.log(ngx.ERR, "Failed to open health state file: ", file_path)
        return nil
    end

    local content = file:read("*all")
    file:close()

    local ok, state = pcall(cjson.decode, content)
    if not ok then
        ngx.log(ngx.ERR, "Failed to parse health state JSON: ", state)
        return nil
    end

    return state
end

-- Get current health state (with caching)
function _M.get_state()
    local now = ngx.now()

    -- Return cached state if still valid
    if _M.cached_state and (now - _M.last_read) < _M.cache_ttl then
        return _M.cached_state
    end

    -- Read fresh state
    local state = read_state_file(_M.state_file)
    if state then
        _M.cached_state = state
        _M.last_read = now
    end

    return state
end

-- Check if a specific server is healthy
function _M.is_healthy(upstream_name, server_addr)
    local state = _M.get_state()
    if not state or not state.upstreams then
        return nil  -- Unknown state
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

-- Get all healthy servers for an upstream
function _M.get_healthy_servers(upstream_name)
    local state = _M.get_state()
    if not state or not state.upstreams then
        return {}
    end

    local upstream = state.upstreams[upstream_name]
    if not upstream or not upstream.servers then
        return {}
    end

    local healthy = {}
    for addr, server in pairs(upstream.servers) do
        if server.healthy then
            table.insert(healthy, addr)
        end
    end

    return healthy
end

-- Get all servers with their health status
function _M.get_all_servers(upstream_name)
    local state = _M.get_state()
    if not state or not state.upstreams then
        return {}
    end

    local upstream = state.upstreams[upstream_name]
    if not upstream or not upstream.servers then
        return {}
    end

    return upstream.servers
end

-- Set custom state file path
function _M.set_state_file(path)
    _M.state_file = path
end

-- Set cache TTL
function _M.set_cache_ttl(ttl)
    _M.cache_ttl = ttl
end

return _M
