-- sticky_response.lua v1.0.0
-- Response-phase cookie setting for NGINX Plus-like sticky behavior
--
-- This module sets cookies in responses when the load balancer selects a backend.
-- Works in conjunction with balancer_sticky.lua to provide full sticky cookie functionality.
local bit = require "bit"  -- Add this
local lxor = bit.bxor
local _M = {
    _VERSION = '1.0.0'
}

-- DJB2 hash function (same as balancer.lua for consistency)
local function hash_string(str)
    local hash = 5381
    for i = 1, #str do
        hash = lxor(bit.lshift(hash, 5) + hash, string.byte(str, i))
    end
    return hash
end

-- Store cookie configurations per upstream
local cookie_configs = {}

-- Register cookie configuration for an upstream
function _M.register_config(upstream_name, config)
    cookie_configs[upstream_name] = {
        name = config.cookie_name or "route",
        expires = config.cookie_expires,
        domain = config.cookie_domain,
        path = config.cookie_path or "/",
        httponly = config.cookie_httponly ~= false,  -- default true
        secure = config.cookie_secure or false,
        samesite = config.cookie_samesite  -- "Strict", "Lax", or "None"
    }
end

-- Set cookie in response if not already present
function _M.set_cookie_if_missing(upstream_name)
    if not upstream_name then
        ngx.log(ngx.WARN, "sticky_response: upstream_name is required")
        return
    end

    -- Get cookie configuration for this upstream
    local config = cookie_configs[upstream_name]
    if not config then
        -- No configuration registered, skip
        return
    end

    local cookie_name = config.name

    -- Check if cookie already exists in request
    local existing_cookie = ngx.var["cookie_" .. cookie_name]
    if existing_cookie then
        -- Cookie already exists, don't override
        return
    end

    -- Get selected backend from ngx.ctx (set by balancer phase)
    local selected_backend = ngx.ctx.selected_backend_for_cookie
    if not selected_backend then
        ngx.log(ngx.WARN, "sticky_response: no selected backend in ngx.ctx for upstream: ", upstream_name)
        return
    end

    -- Create cookie value from backend address hash
    local hash = hash_string(selected_backend)
    local cookie_value = string.format("%08x", hash)

    -- Build Set-Cookie header
    local cookie_parts = {cookie_name .. "=" .. cookie_value}

    if config.expires then
        table.insert(cookie_parts, "Max-Age=" .. config.expires)
    end

    if config.domain then
        table.insert(cookie_parts, "Domain=" .. config.domain)
    end

    if config.path then
        table.insert(cookie_parts, "Path=" .. config.path)
    end

    if config.httponly then
        table.insert(cookie_parts, "HttpOnly")
    end

    if config.secure then
        table.insert(cookie_parts, "Secure")
    end

    if config.samesite then
        table.insert(cookie_parts, "SameSite=" .. config.samesite)
    end

    local cookie_header = table.concat(cookie_parts, "; ")

    -- Set cookie in response
    -- Note: This preserves any existing Set-Cookie headers
    local existing_set_cookie = ngx.header["Set-Cookie"]
    if existing_set_cookie then
        if type(existing_set_cookie) == "table" then
            table.insert(existing_set_cookie, cookie_header)
            ngx.header["Set-Cookie"] = existing_set_cookie
        else
            ngx.header["Set-Cookie"] = {existing_set_cookie, cookie_header}
        end
    else
        ngx.header["Set-Cookie"] = cookie_header
    end

    ngx.log(ngx.DEBUG, "sticky_response: set cookie for upstream ", upstream_name,
            ": ", cookie_name, "=", cookie_value, " (backend: ", selected_backend, ")")
end

-- Handle response for all configured upstreams
-- Call this in header_filter_by_lua_block
function _M.handle()
    -- Get upstream name from ngx.ctx (set by balancer phase)
    local upstream_name = ngx.ctx.sticky_upstream_name
    if upstream_name then
        _M.set_cookie_if_missing(upstream_name)
    end
end

return _M
