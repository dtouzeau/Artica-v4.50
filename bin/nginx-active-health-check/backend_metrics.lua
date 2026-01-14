-- Backend Performance Metrics Module
-- Tracks actual backend response times and exports to health check daemon
-- Enables adaptive load balancing based on real traffic performance

local cjson = require "cjson"
local http = require "resty.http"

local _M = {
    _VERSION = '1.0.0',
}

-- Configuration
local daemon_api_url
local export_interval = 1 -- Export metrics every 1 second
local metrics_dict = ngx.shared.backend_metrics

-- Initialize module with daemon API URL
function _M.init(api_url)
    if not api_url then
        ngx.log(ngx.ERR, "[backend_metrics] API URL not provided")
        return false
    end

    daemon_api_url = api_url

    if not metrics_dict then
        ngx.log(ngx.ERR, "[backend_metrics] Shared dict 'backend_metrics' not configured!")
        return false
    end

    ngx.log(ngx.INFO, "[backend_metrics] Initialized with API URL: ", api_url)
    return true
end

-- Log backend request response time
function _M.log_request()
    -- Get upstream information
    local upstream_addr = ngx.var.upstream_addr
    local upstream_response_time = ngx.var.upstream_response_time
    local upstream_name = ngx.var.proxy_host or ngx.var.upstream_name

    -- Skip if no upstream was contacted
    if not upstream_addr or not upstream_response_time then
        return
    end

    -- Handle multiple upstream attempts (use first one)
    if string.find(upstream_addr, ",") then
        upstream_addr = string.match(upstream_addr, "^([^,]+)")
        upstream_response_time = string.match(upstream_response_time, "^([^,]+)")
    end

    -- Skip if still invalid
    if not upstream_addr or not upstream_response_time then
        return
    end

    -- Convert response time to milliseconds
    local response_time_ms = tonumber(upstream_response_time) * 1000
    if not response_time_ms then
        return
    end

    -- Build metric key: upstream_name:server_addr
    local metric_key = (upstream_name or "unknown") .. ":" .. upstream_addr

    -- Update metrics in shared dict
    -- Store: count and total_ms (for calculating average)
    local count_key = metric_key .. ":count"
    local total_key = metric_key .. ":total_ms"

    -- Atomically increment count and total
    local new_count, err = metrics_dict:incr(count_key, 1, 0)
    if not new_count then
        ngx.log(ngx.WARN, "[backend_metrics] Failed to increment count: ", err)
        return
    end

    local new_total, err = metrics_dict:incr(total_key, response_time_ms, 0)
    if not new_total then
        ngx.log(ngx.WARN, "[backend_metrics] Failed to increment total: ", err)
        return
    end
end

-- Export metrics to daemon via HTTP POST
local function export_metrics(premature)
    if premature then
        return
    end

    if not daemon_api_url then
        ngx.log(ngx.WARN, "[backend_metrics] Daemon API URL not configured, skipping export")
        -- Reschedule anyway to avoid stopping
        local ok, err = ngx.timer.at(export_interval, export_metrics)
        if not ok then
            ngx.log(ngx.ERR, "[backend_metrics] Failed to reschedule export timer: ", err)
        end
        return
    end

    -- Collect all metrics from shared dict
    local upstreams = {}
    local keys = metrics_dict:get_keys(0) -- Get all keys

    for _, key in ipairs(keys) do
        -- Only process count keys
        if string.match(key, ":count$") then
            local base_key = string.gsub(key, ":count$", "")
            local count = metrics_dict:get(key)
            local total_ms = metrics_dict:get(base_key .. ":total_ms")

            if count and total_ms and count > 0 then
                -- Calculate average
                local avg_ms = total_ms / count

                -- Parse upstream and server from key
                local upstream_name, server_addr = string.match(base_key, "^(.+):([^:]+:%d+)$")

                if upstream_name and server_addr then
                    -- Initialize upstream table if needed
                    if not upstreams[upstream_name] then
                        upstreams[upstream_name] = {}
                    end

                    -- Add server metrics
                    table.insert(upstreams[upstream_name], {
                        address = server_addr,
                        avg_response_ms = math.floor(avg_ms + 0.5), -- Round to nearest integer
                        count = count
                    })

                    -- Clear metrics for next interval
                    metrics_dict:delete(key)
                    metrics_dict:delete(base_key .. ":total_ms")
                end
            end
        end
    end

    -- Send metrics to daemon for each upstream
    for upstream_name, servers in pairs(upstreams) do
        local payload = {
            upstream = upstream_name,
            servers = servers
        }

        local payload_json = cjson.encode(payload)

        -- Send HTTP POST to daemon
        local httpc = http.new()
        httpc:set_timeout(500) -- 500ms timeout

        local res, err = httpc:request_uri(daemon_api_url, {
            method = "POST",
            body = payload_json,
            headers = {
                ["Content-Type"] = "application/json",
            }
        })

        if not res then
            ngx.log(ngx.WARN, "[backend_metrics] Failed to send metrics for ", upstream_name, ": ", err)
        elseif res.status ~= 200 then
            ngx.log(ngx.WARN, "[backend_metrics] Daemon returned status ", res.status, " for ", upstream_name)
        end
    end

    -- Reschedule for next interval
    local ok, err = ngx.timer.at(export_interval, export_metrics)
    if not ok then
        ngx.log(ngx.ERR, "[backend_metrics] Failed to reschedule export timer: ", err)
    end
end

-- Start periodic export timer (called in init_worker_by_lua)
function _M.start_exporter()
    if not daemon_api_url then
        ngx.log(ngx.WARN, "[backend_metrics] Daemon API URL not configured, exporter not started")
        return false
    end

    -- Start timer in first worker only to avoid duplicates
    if ngx.worker.id() == 0 then
        ngx.log(ngx.INFO, "[backend_metrics] Starting metrics exporter (interval: ", export_interval, "s)")
        local ok, err = ngx.timer.at(export_interval, export_metrics)
        if not ok then
            ngx.log(ngx.ERR, "[backend_metrics] Failed to start export timer: ", err)
            return false
        end
    end

    return true
end

return _M
