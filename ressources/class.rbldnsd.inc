<?php
class RbldnsdClient
{
    /** @var string Unix socket path */
    private $socketPath;

    /** @var int Request timeout in seconds */
    private $timeout;

    /** @var string|null Last error message */
    private $lastError;

    /** @var int|null Last HTTP status code */
    private $lastStatusCode;

    /**
     * Constructor
     *
     * @param string $socketPath Path to Unix socket (default: /run/rbldnsd.sock)
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(string $socketPath = '/run/dnscatz/dnscatz.sock', int $timeout = 30)
    {
        $this->socketPath = $socketPath;
        $this->timeout = $timeout;
        $this->lastError = null;
        $this->lastStatusCode = null;
    }

    /**
     * Get server statistics
     *
     * @return array|null Statistics array or null on error
     */
    public function getStats(): ?array
    {
        return $this->request('GET', '/stats');
    }

    /**
     * Reload all zone files
     *
     * @return array|null Response array or null on error
     */
    public function reload(): ?array
    {
        return $this->request('POST', '/reload');
    }

    /**
     * Reset statistics counters
     *
     * @return array|null Response array or null on error
     */
    public function reset(): ?array
    {
        return $this->request('POST', '/reset');
    }

    /**
     * Health check
     *
     * @return array|null Health status or null on error
     */
    public function health(): ?array
    {
        return $this->request('GET', '/health');
    }

    /**
     * Check if server is healthy
     *
     * @return bool True if healthy
     */
    public function isLoading(): bool
    {
        $result = $this->health();
        return $result !== null && isset($result['status']) && $result['status'] === 'loading';
    }

    public function isHealthy(): bool
    {
        $result = $this->health();
        return $result !== null && isset($result['status']) && $result['status'] === 'healthy';
    }

    /**
     * Force dump statistics to file
     *
     * @return array|null Response array or null on error
     */
    public function dumpStats(): ?array
    {
        return $this->request('POST', '/dump-stats');
    }

    /**
     * Get cache statistics
     *
     * @return array|null Cache stats or null on error
     */
    public function getCacheStats(): ?array
    {
        return $this->request('GET', '/cache');
    }

    /**
     * Clear response cache
     *
     * @return array|null Response array or null on error
     */
    public function clearCache(): ?array
    {
        return $this->request('POST', '/cache/clear');
    }

    /**
     * Compile all categories from PostgreSQL
     *
     * @return array|null Compilation result or null on error
     */
    public function compile(): ?array{
        return $this->request('POST', '/compile');
    }

    public function getCompileProgress():?array{
        VERBOSE("compileCompress",__LINE__);
        return $this->request('GET', '/compile-progress');
    }
    public function isCompiling(): bool{
        $progress = $this->getCompileProgress();
        return $progress !== null && ($progress['running'] ?? false);
    }

    /**
     * Compile specific category by ID
     *
     * @param int $categoryId Category ID to compile
     * @return array|null Compilation result or null on error
     */
    public function compileCategory(int $categoryId): ?array{
        return $this->request('POST', '/compile/' . $categoryId);
    }
    public function ReloadConfig():?array{
        return $this->request('POST', '/reload-config');
    }

    /**
     * Get export status for all categories
     *
     * @return array|null Export status or null on error
     */
    public function getExportStatus(): ?array
    {
        return $this->request('GET', '/export/status');
    }

    /**
     * Get export status for specific category
     *
     * @param int $categoryId Category ID
     * @return array|null Export status or null on error
     */
    public function getExportStatusCategory(int $categoryId): ?array
    {
        return $this->request('GET', '/export/status/' . $categoryId);
    }

    /**
     * Get last error message
     *
     * @return string|null Last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get last HTTP status code
     *
     * @return int|null Last HTTP status code
     */
    public function getLastStatusCode(): ?int
    {
        return $this->lastStatusCode;
    }

    /**
     * Check if socket file exists
     *
     * @return bool True if socket exists
     */
    public function socketExists(): bool
    {
        return file_exists($this->socketPath);
    }

    /**
     * Make HTTP request to Unix socket
     *
     * @param string $method HTTP method (GET, POST)
     * @param string $endpoint API endpoint path
     * @param array|null $data Optional data for POST requests
     * @return array|null Decoded JSON response or null on error
     */
    private function request(string $method, string $endpoint, ?array $data = null): ?array
    {
        $this->lastError = null;
        $this->lastStatusCode = null;

        if (!$this->socketExists()) {
            $this->lastError = "Socket not found: $this->socketPath";
            return null;
        }

        $ch = curl_init();

        if ($ch === false) {
            $this->lastError = "Failed to initialize curl";
            return null;
        }

        try {
            // Configure curl for Unix socket
            curl_setopt_array($ch, [
                CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
                CURLOPT_URL => 'http://localhost' . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);

            VERBOSE("Opening $method endpoint: $endpoint",__LINE__);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
                }
            }

            $response = curl_exec($ch);
            $this->lastStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $this->lastError = curl_error($ch);
                VERBOSE("Response from curl_exec endpoint: FALSE -> $this->lastError",__LINE__);
                return null;
            }

            if ($this->lastStatusCode >= 400) {
                $decoded = json_decode($response, true);
                if ($decoded && isset($decoded['error'])) {
                    $this->lastError = $decoded['error'];
                } else {
                    $this->lastError = "HTTP error: {$this->lastStatusCode}";
                }
                return $decoded;
            }

            VERBOSE("Response from $method endpoint: $endpoint",__LINE__);

            $decoded = json_decode($response, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = "JSON decode error: " . json_last_error_msg();
                return null;
            }

            return $decoded;

        } finally {
            if(function_exists("curl_close")) {
                curl_close($ch);
            }
        }
    }

    /**
     * Get formatted statistics summary
     *
     * @return string Formatted statistics
     */
    public function getStatsSummary(): string
    {
        $stats = $this->getStats();
        if ($stats === null) {
            return "Error: " . ($this->lastError ?? "Unknown error");
        }

        $lines = [];
        $lines[] = "RBLDNSD Statistics";
        $lines[] = str_repeat("-", 40);
        $lines[] = sprintf("Uptime: %s", $stats['uptime'] ?? 'N/A');
        $lines[] = sprintf("Total Queries: %d", $stats['queries_total'] ?? 0);
        $lines[] = sprintf("  - OK: %d", $stats['queries_ok'] ?? 0);
        $lines[] = sprintf("  - NXDOMAIN: %d", $stats['queries_nxdomain'] ?? 0);
        $lines[] = sprintf("  - Errors: %d", $stats['queries_error'] ?? 0);
        $lines[] = sprintf("  - Refused: %d", $stats['queries_refused'] ?? 0);
        $lines[] = sprintf("Queries/sec: %.2f", $stats['queries_per_second'] ?? 0);
        $lines[] = sprintf("Unique Clients: %d", $stats['unique_clients'] ?? 0);
        $lines[] = sprintf("Zones Loaded: %d", $stats['zones_loaded'] ?? 0);

        return implode("\n", $lines);
    }

    /**
     * Get formatted cache summary
     *
     * @return string Formatted cache statistics
     */
    public function getCacheSummary(): string
    {
        $cache = $this->getCacheStats();
        if ($cache === null) {
            return "Error: " . ($this->lastError ?? "Unknown error");
        }

        if (!($cache['enabled'] ?? false)) {
            return "Cache: Disabled";
        }

        $lines = [];
        $lines[] = "Cache Statistics";
        $lines[] = str_repeat("-", 40);
        $lines[] = sprintf("Size: %d / %d", $cache['size'] ?? 0, $cache['max_size'] ?? 0);
        $lines[] = sprintf("Hits: %d", $cache['hits'] ?? 0);
        $lines[] = sprintf("Misses: %d", $cache['misses'] ?? 0);
        $lines[] = sprintf("Hit Rate: %.2f%%", $cache['hit_rate'] ?? 0);
        $lines[] = sprintf("TTL: %d seconds", $cache['ttl_seconds'] ?? 0);

        return implode("\n", $lines);
    }

    /**
     * Get formatted export status summary
     *
     * @return string Formatted export status
     */
    public function getExportSummary(): string
    {
        $export = $this->getExportStatus();
        if ($export === null) {
            return "Error: " . ($this->lastError ?? "Unknown error");
        }

        if (isset($export['error'])) {
            return "Error: " . $export['error'];
        }

        $lines = [];
        $lines[] = "Export Status";
        $lines[] = str_repeat("-", 60);
        $lines[] = sprintf("Total Categories: %d", $export['total'] ?? 0);
        $lines[] = "";

        if (isset($export['categories']) && is_array($export['categories'])) {
            foreach ($export['categories'] as $cat) {
                $lines[] = sprintf(
                    "[%d] %s (%s)",
                    $cat['category_id'] ?? 0,
                    $cat['category_name'] ?? 'Unknown',
                    $cat['category_key'] ?? ''
                );
                $lines[] = sprintf(
                    "    Domains: %d | IP: %s | Exported: %s",
                    $cat['domain_count'] ?? 0,
                    $cat['ip'] ?? 'N/A',
                    $cat['exported_at'] ?? 'Never'
                );
            }
        }

        return implode("\n", $lines);
    }
}


/**
 * Example usage and CLI interface
 */
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $socketPath = $argv[1] ?? '/run/rbldnsd.sock';
    $command = $argv[2] ?? 'stats';

    $client = new RbldnsdClient($socketPath);

    if (!$client->socketExists()) {
        fwrite(STDERR, "Error: Socket not found at {$socketPath}\n");
        exit(1);
    }

    switch ($command) {
        case 'stats':
            echo $client->getStatsSummary() . "\n";
            break;

        case 'cache':
            echo $client->getCacheSummary() . "\n";
            break;

        case 'health':
            $health = $client->health();
            echo json_encode($health, JSON_PRETTY_PRINT) . "\n";
            exit($client->isHealthy() ? 0 : 1);

        case 'reload':
            $result = $client->reload();
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            exit(($result['status'] ?? '') === 'ok' ? 0 : 1);

        case 'reset':
            $result = $client->reset();
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            break;

        case 'compile':
            $categoryId = isset($argv[3]) ? (int)$argv[3] : null;
            if ($categoryId !== null) {
                $result = $client->compileCategory($categoryId);
            } else {
                $result = $client->compile();
            }
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            exit(($result['status'] ?? '') === 'ok' ? 0 : 1);

        case 'export':
            echo $client->getExportSummary() . "\n";
            break;

        case 'clear-cache':
            $result = $client->clearCache();
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            break;

        case 'dump-stats':
            $result = $client->dumpStats();
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            break;

        default:
            echo "Usage: php " . basename(__FILE__) . " [socket_path] <command> [args]\n";
            echo "\nCommands:\n";
            echo "  stats        - Show server statistics\n";
            echo "  cache        - Show cache statistics\n";
            echo "  health       - Health check\n";
            echo "  reload       - Reload zone files\n";
            echo "  reset        - Reset statistics\n";
            echo "  compile [id] - Compile zones (optionally specific category)\n";
            echo "  export       - Show export status\n";
            echo "  clear-cache  - Clear response cache\n";
            echo "  dump-stats   - Dump stats to file\n";
            echo "\nDefault socket: /run/rbldnsd.sock\n";
            exit(1);
    }

    if ($error = $client->getLastError()) {
        fwrite(STDERR, "Error: {$error}\n");
        exit(1);
    }

    exit(0);
}
