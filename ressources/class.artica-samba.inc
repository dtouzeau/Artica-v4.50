<?php

/**
 * ArticaSamba - PHP client for artica-samba orchestrator.
 *
 * Communicates with the artica-samba daemon over its Unix socket API.
 * Uses libcurl with CURLOPT_UNIX_SOCKET_PATH for local socket access.
 */
class ArticaSamba
{
    private string $socketPath;
    private int $timeout;

    /**
     * @param string $socketPath Path to Unix socket (default: /run/samba/artica-samba.sock)
     * @param int    $timeout    Request timeout in seconds (default: 30)
     */
    public function __construct(string $socketPath = '/run/samba/artica-samba.sock', int $timeout = 30)
    {
        $this->socketPath = $socketPath;
        $this->timeout = $timeout;
    }

    // ── Instances ──────────────────────────────────────────────

    public function listInstances(): array
    {
        return $this->get('/v1/instances');
    }

    public function createInstance(array $params): array
    {
        return $this->post('/v1/instances', $params);
    }

    public function getInstance(string $id): array
    {
        return $this->get("/v1/instances/{$id}");
    }

    public function startInstance(string $id): array
    {
        return $this->post("/v1/instances/{$id}/start");
    }

    public function stopInstance(string $id, bool $force = false): array
    {
        $query = $force ? '?force=true' : '';
        return $this->post("/v1/instances/{$id}/stop{$query}");
    }

    public function restartInstance(string $id): array
    {
        return $this->post("/v1/instances/{$id}/restart");
    }

    public function deleteInstance(string $id, bool $force = false): array
    {
        $query = $force ? '?force=true' : '';
        return $this->delete("/v1/instances/{$id}{$query}");
    }

    // ── Resources ─────────────────────────────────────────────

    public function updateResources(string $id, array $limits): array
    {
        return $this->put("/v1/instances/{$id}/resources", $limits);
    }

    // ── Shares ────────────────────────────────────────────────

    public function listShares(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/shares");
    }

    public function createShare(string $instanceId, array $params): array
    {
        return $this->post("/v1/instances/{$instanceId}/shares", $params);
    }

    public function updateShare(string $instanceId, string $name, array $params): array
    {
        return $this->put("/v1/instances/{$instanceId}/shares/{$name}", $params);
    }

    public function deleteShare(string $instanceId, string $name): array
    {
        return $this->delete("/v1/instances/{$instanceId}/shares/{$name}");
    }

    // ── Users ─────────────────────────────────────────────────

    public function listUsers(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/users");
    }

    public function createUser(string $instanceId, string $name, string $password): array
    {
        return $this->post("/v1/instances/{$instanceId}/users", [
            'name' => $name,
            'password' => $password,
        ]);
    }

    public function updateUser(string $instanceId, string $name, array $params): array
    {
        return $this->put("/v1/instances/{$instanceId}/users/{$name}", $params);
    }

    public function deleteUser(string $instanceId, string $name): array
    {
        return $this->delete("/v1/instances/{$instanceId}/users/{$name}");
    }

    // ── Metrics ───────────────────────────────────────────────

    public function getSessions(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/metrics/sessions");
    }

    public function getIO(string $instanceId, string $period = '1h', string $user = ''): array
    {
        $query = "?period={$period}";
        if ($user !== '') {
            $query .= "&user=" . urlencode($user);
        }
        return $this->get("/v1/instances/{$instanceId}/metrics/io{$query}");
    }

    public function getTop(string $instanceId, int $limit = 10, string $sort = 'total_bytes'): array
    {
        return $this->get("/v1/instances/{$instanceId}/metrics/top?limit={$limit}&sort={$sort}");
    }

    public function getPrometheus(string $instanceId): string
    {
        return $this->rawGet("/v1/instances/{$instanceId}/metrics/prometheus");
    }

    // ── Health ────────────────────────────────────────────────

    public function health(): array
    {
        return $this->get('/health');
    }

    // ── Log Helpers ──────────────────────────────────────────

    public function listLogFiles(string $instanceId): array
    {
        $dir = "/var/lib/artica-samba/instances/{$instanceId}/logs";
        if (!is_dir($dir)) { return []; }
        $files = [];
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') { continue; }
            if (is_file("$dir/$f")) {
                $files[] = ['name' => $f, 'size' => filesize("$dir/$f"), 'mtime' => filemtime("$dir/$f")];
            }
        }
        usort($files, fn($a, $b) => $b['mtime'] - $a['mtime']);
        return $files;
    }

    public function readLogFile(string $instanceId, string $filename, int $lines = 500): string
    {
        $path = "/var/lib/artica-samba/instances/{$instanceId}/logs/" . basename($filename);
        if (!is_file($path)) { return ""; }
        $all = file($path);
        if ($all === false) { return ""; }
        $tail = array_slice($all, -$lines);
        return implode("", $tail);
    }

    // ── HTTP Methods ──────────────────────────────────────────

    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    private function post(string $path, ?array $body = null): array
    {
        return $this->request('POST', $path, $body);
    }

    private function put(string $path, ?array $body = null): array
    {
        return $this->request('PUT', $path, $body);
    }

    private function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    private function rawGet(string $path): string
    {
        $ch = $this->initCurl($path);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("curl error: {$error}");
        }

        return $response;
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = $this->initCurl($path);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($body !== null) {
            $json = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("curl error: {$error}");
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new \RuntimeException("invalid JSON response: {$response}");
        }

        if (!($decoded['ok'] ?? false)) {
            throw new \RuntimeException(
                "API error ({$httpCode}): " . ($decoded['error'] ?? 'unknown')
            );
        }

        return $decoded['data'] ?? [];
    }

    private function initCurl(string $path): \CurlHandle
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL => "http://localhost{$path}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        return $ch;
    }
}
