<?php
/**
 * Siege Daemon PHP Client
 *
 * A PHP class for interacting with siege-daemon via Unix socket.
 *
 * Usage:
 *   $siege = new SiegeDaemon();
 *   $siege = new SiegeDaemon('/custom/path/to/siege-daemon.sock');
 *
 *   // Create and start a test
 *   $test = $siege->createTest([
 *       'name' => 'my-test',
 *       'urls' => [['url' => 'http://example.com/']],
 *       'concurrent_users' => 50,
 *       'duration' => '60s'
 *   ]);
 *   $siege->startTest($test['id']);
 *
 *   // Monitor progress
 *   $progress = $siege->getTestProgress($test['id']);
 */

class SiegeDaemon
{
    /** @var string */
    private $socketPath;
    /** @var int */
    private $timeout;
    /** @var string|null */
    private $lastError = null;
    /** @var int */
    private $lastHttpCode = 0;

    /**
     * Constructor
     *
     * @param string $socketPath Path to the Unix socket
     * @param int $timeout Request timeout in seconds
     */
    public function __construct($socketPath = '/run/siege-daemon.sock', $timeout = 30)
    {
        $this->socketPath = $socketPath;
        $this->timeout = $timeout;
    }

    // =========================================================================
    // Core HTTP Methods
    // =========================================================================

    /**
     * Perform a GET request
     *
     * @param string $endpoint API endpoint (e.g., '/api/v1/tests')
     * @param array $query Optional query parameters
     * @return array|null Response data or null on error
     */
    public function get(string $endpoint, array $query = []): ?array
    {
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }
        return $this->request('GET', $endpoint);
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array|null Response data or null on error
     */
    public function post(string $endpoint, array $data = []): ?array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Perform a PUT request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array|null Response data or null on error
     */
    public function put(string $endpoint, array $data = []): ?array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Perform a DELETE request
     *
     * @param string $endpoint API endpoint
     * @param array $query Optional query parameters
     * @return array|null Response data or null on error
     */
    public function delete(string $endpoint, array $query = []): ?array
    {
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Internal request method
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request body data
     * @return array|null Response data or null on error
     */
    private function request(string $method, string $endpoint, ?array $data = null): ?array
    {
        $this->lastError = null;
        $this->lastHttpCode = 0;

        $ch = curl_init();

        if ($ch === false) {
            $this->lastError = 'Failed to initialize curl';
            return null;
        }

        // Build URL - use localhost as host (required by HTTP protocol)
        $url = 'http://localhost' . $endpoint;

        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'JSON decode error: ' . json_last_error_msg();
            return null;
        }

        // Check for API error response
        if (isset($decoded['error'])) {
            $this->lastError = $decoded['error'];
        }

        return $decoded;
    }

    // =========================================================================
    // Health & Status
    // =========================================================================

    /**
     * Check daemon health
     *
     * @return array|null Health status
     */
    public function health(): ?array
    {
        return $this->get('/health');
    }

    /**
     * Check if daemon is running
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        $health = $this->health();
        return $health !== null && ($health['status'] ?? '') === 'healthy';
    }

    /**
     * Get daemon version information
     *
     * @return array|null Version info with keys: version, build_time, git_commit
     */
    public function getVersion(): ?array
    {
        return $this->get('/version');
    }

    /**
     * Get daemon version string
     *
     * @return string|null Version string (e.g., "1.2.2")
     */
    public function getVersionString(): ?string
    {
        $version = $this->getVersion();
        return $version['version'] ?? null;
    }

    // =========================================================================
    // Test Management
    // =========================================================================

    /**
     * Create a new test configuration
     *
     * @param array $config Test configuration
     * @return array|null Created test config with ID
     */
    public function createTest(array $config): ?array
    {
        return $this->post('/api/v1/tests', $config);
    }

    /**
     * List all test configurations
     *
     * @return array|null List of tests
     */
    public function listTests(): ?array
    {
        return $this->get('/api/v1/tests');
    }

    /**
     * Get a specific test configuration
     *
     * @param string $testId Test ID
     * @return array|null Test configuration
     */
    public function getTest(string $testId): ?array
    {
        return $this->get("/api/v1/tests/{$testId}");
    }

    /**
     * Delete a test configuration
     *
     * @param string $testId Test ID
     * @return array|null Deletion result
     */
    public function deleteTest(string $testId): ?array
    {
        return $this->delete("/api/v1/tests/{$testId}");
    }

    // =========================================================================
    // Test Control
    // =========================================================================

    /**
     * Start a test
     *
     * @param string $testId Test ID
     * @return array|null Start result
     */
    public function startTest(string $testId): ?array
    {
        return $this->post("/api/v1/tests/{$testId}/start");
    }

    /**
     * Stop a running test
     *
     * @param string $testId Test ID
     * @return array|null Final statistics
     */
    public function stopTest(string $testId): ?array
    {
        return $this->post("/api/v1/tests/{$testId}/stop");
    }

    /**
     * Pause a running test
     *
     * @param string $testId Test ID
     * @return array|null Pause result
     */
    public function pauseTest(string $testId): ?array
    {
        return $this->post("/api/v1/tests/{$testId}/pause");
    }

    /**
     * Resume a paused test
     *
     * @param string $testId Test ID
     * @return array|null Resume result
     */
    public function resumeTest(string $testId): ?array
    {
        return $this->post("/api/v1/tests/{$testId}/resume");
    }

    // =========================================================================
    // Statistics & Progress
    // =========================================================================

    /**
     * Get current statistics for a running test
     *
     * @param string $testId Test ID
     * @return array|null Current statistics
     */
    public function getTestStats(string $testId): ?array
    {
        return $this->get("/api/v1/tests/{$testId}/stats");
    }

    /**
     * Get progress information for a running test
     *
     * @param string $testId Test ID
     * @return array|null Progress information
     */
    public function getTestProgress(string $testId): ?array
    {
        return $this->get("/api/v1/tests/{$testId}/progress");
    }

    /**
     * Get historical results for a test
     *
     * @param string $testId Test ID
     * @param int $limit Maximum results to return
     * @param int $offset Pagination offset
     * @return array|null Historical results
     */
    public function getTestResults(string $testId, int $limit = 50, int $offset = 0): ?array
    {
        return $this->get("/api/v1/tests/{$testId}/results", [
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Get test history across all tests
     *
     * @param int $limit Maximum results to return
     * @param int $offset Pagination offset
     * @return array|null Test history
     */
    public function getHistory(int $limit = 50, int $offset = 0): ?array
    {
        return $this->get('/api/v1/history', [
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }
    /**
     * Get list of currently running tests
     *
     * @return array|null Running tests info
     */
    public function getRunningTests(): ?array
    {
        return $this->get('/api/v1/running');
    }

    // =========================================================================
    // Startup Errors
    // =========================================================================

    /**
     * Get startup error for a test that failed to start
     *
     * When a test fails during startup (e.g., Kerberos auth failure, invalid config),
     * the error is stored and can be retrieved using this method.
     *
     * @param string $testId Test ID
     * @return array|null Startup error info with keys: test_id, has_error, error, error_message, timestamp
     *
     * Example response when error exists:
     *   {
     *     "test_id": "abc123",
     *     "has_error": true,
     *     "error": {"test_id": "abc123", "error": "...", "timestamp": "..."},
     *     "error_message": "failed to create HTTP client: kerberos setup failed: ...",
     *     "timestamp": "2025-12-17T04:05:50+01:00"
     *   }
     */
    public function getTestStartupError(string $testId): ?array
    {
        return $this->get("/api/v1/tests/{$testId}/startup-error");
    }

    /**
     * Get all startup errors for tests that failed to start
     *
     * @return array|null All startup errors with keys: errors, count
     */
    public function getAllStartupErrors(): ?array
    {
        return $this->get('/api/v1/startup-errors');
    }

    /**
     * Clear the startup error for a test
     *
     * @param string $testId Test ID
     * @return array|null Clear result
     */
    public function clearTestStartupError(string $testId): ?array
    {
        return $this->delete("/api/v1/tests/{$testId}/startup-error");
    }

    /**
     * Check if a test has a startup error
     *
     * @param string $testId Test ID
     * @return bool True if the test has a startup error
     */
    public function hasTestStartupError(string $testId): bool
    {
        $result = $this->getTestStartupError($testId);
        return $result !== null && ($result['has_error'] ?? false);
    }

    /**
     * Get the startup error message for a test
     *
     * @param string $testId Test ID
     * @return string|null Error message or null if no error
     */
    public function getTestStartupErrorMessage(string $testId): ?string
    {
        $result = $this->getTestStartupError($testId);
        if ($result !== null && ($result['has_error'] ?? false)) {
            return $result['error_message'] ?? null;
        }
        return null;
    }

    // =========================================================================
    // Reports
    // =========================================================================

    /**
     * Get JSON report for a test (running or completed)
     *
     * @param string $testId Test ID
     * @return array|null Report data
     */
    public function getTestReport(string $testId): ?array
    {
        return $this->get("/api/v1/tests/{$testId}/report");
    }

    /**
     * Get HTML report for a test
     *
     * @param string $testId Test ID
     * @return string|null HTML report content
     */
    public function getTestReportHtml(string $testId): ?string
    {
        $this->lastError = null;
        $this->lastHttpCode = 0;

        $ch = curl_init();

        if ($ch === false) {
            $this->lastError = 'Failed to initialize curl';
            return null;
        }

        $url = 'http://localhost/api/v1/tests/' . $testId . '/report.html';

        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html',
            ],
        ]);

        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($this->lastHttpCode >= 400) {
            $this->lastError = "HTTP error: " . $this->lastHttpCode;
            return null;
        }

        return $response;
    }

    /**
     * Get PDF report for a test (with charts and graphs)
     *
     * @param string $testId Test ID
     * @return string|null PDF binary content
     */
    public function getTestReportPdf(string $testId): ?string
    {
        $this->lastError = null;
        $this->lastHttpCode = 0;

        $ch = curl_init();

        if ($ch === false) {
            $this->lastError = 'Failed to initialize curl';
            return null;
        }

        $url = 'http://localhost/api/v1/tests/' . $testId . '/report.pdf';

        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: application/pdf',
            ],
        ]);

        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($this->lastHttpCode >= 400) {
            $this->lastError = "HTTP error: " . $this->lastHttpCode;
            return null;
        }

        return $response;
    }

    /**
     * Download PDF report to a file
     *
     * @param string $testId Test ID
     * @param string $filePath Destination file path
     * @return bool Success
     */
    public function downloadTestReportPdf(string $testId, string $filePath): bool
    {
        $pdf = $this->getTestReportPdf($testId);

        if ($pdf === null) {
            return false;
        }

        $result = file_put_contents($filePath, $pdf);
        return $result !== false;
    }

    // =========================================================================
    // Webhooks
    // =========================================================================

    /**
     * Create a webhook
     *
     * @param string $url Webhook URL
     * @param array $events Events to notify (start, complete, error)
     * @param string|null $testId Optional test ID (null for all tests)
     * @param string|null $secret Optional webhook secret
     * @return array|null Created webhook
     */
    public function createWebhook(
        string $url,
        array $events = ['start', 'complete', 'error'],
        ?string $testId = null,
        ?string $secret = null
    ): ?array {
        $data = [
            'url' => $url,
            'events' => $events,
        ];

        if ($testId !== null) {
            $data['test_id'] = $testId;
        }

        if ($secret !== null) {
            $data['secret'] = $secret;
        }

        return $this->post('/api/v1/webhooks', $data);
    }

    /**
     * List all webhooks
     *
     * @return array|null List of webhooks
     */
    public function listWebhooks(): ?array
    {
        return $this->get('/api/v1/webhooks');
    }

    /**
     * Delete a webhook
     *
     * @param string $webhookId Webhook ID
     * @return array|null Deletion result
     */
    public function deleteWebhook(string $webhookId): ?array
    {
        return $this->delete("/api/v1/webhooks/{$webhookId}");
    }

    // =========================================================================
    // Scheduling
    // =========================================================================

    /**
     * Create a scheduled test
     *
     * @param string $testId Test ID to schedule
     * @param string $cronExpression Cron expression (6 fields: sec min hour day month weekday)
     * @return array|null Created schedule
     */
    public function createSchedule(string $testId, string $cronExpression): ?array
    {
        return $this->post('/api/v1/schedules', [
            'test_id' => $testId,
            'cron_expression' => $cronExpression,
        ]);
    }

    /**
     * List all schedules
     *
     * @return array|null List of schedules
     */
    public function listSchedules(): ?array
    {
        return $this->get('/api/v1/schedules');
    }

    /**
     * Get a specific schedule
     *
     * @param string $scheduleId Schedule ID
     * @return array|null Schedule details
     */
    public function getSchedule(string $scheduleId): ?array
    {
        return $this->get("/api/v1/schedules/{$scheduleId}");
    }

    /**
     * Delete a schedule
     *
     * @param string $scheduleId Schedule ID
     * @return array|null Deletion result
     */
    public function deleteSchedule(string $scheduleId): ?array
    {
        return $this->delete("/api/v1/schedules/{$scheduleId}");
    }

    // =========================================================================
    // Kerberos Management
    // =========================================================================

    /**
     * Create a keytab using msktutil
     *
     * @param array $config Keytab configuration
     * @return array|null Creation result
     */
    public function createKeytab(array $config): ?array
    {
        return $this->post('/api/v1/kerberos/keytab', $config);
    }

    /**
     * Update an existing keytab
     *
     * @param array $config Keytab configuration
     * @return array|null Update result
     */
    public function updateKeytab(array $config): ?array
    {
        return $this->put('/api/v1/kerberos/keytab', $config);
    }

    /**
     * Auto-update keytab (password rotation if needed)
     *
     * @param array $config Keytab configuration
     * @return array|null Auto-update result
     */
    public function autoUpdateKeytab(array $config = []): ?array
    {
        return $this->post('/api/v1/kerberos/keytab/auto-update', $config);
    }

    /**
     * Get Kerberos status (keytab and ticket cache)
     *
     * @param string|null $keytabPath Optional keytab path to check
     * @return array|null Kerberos status
     */
    public function getKerberosStatus(?string $keytabPath = null): ?array
    {
        $query = [];
        if ($keytabPath !== null) {
            $query['keytab_path'] = $keytabPath;
        }
        return $this->get('/api/v1/kerberos/status', $query);
    }

    /**
     * Delete a keytab file
     *
     * @param string|null $keytabPath Optional keytab path to delete
     * @return array|null Deletion result
     */
    public function deleteKeytab(?string $keytabPath = null): ?array
    {
        $query = [];
        if ($keytabPath !== null) {
            $query['keytab_path'] = $keytabPath;
        }
        return $this->delete('/api/v1/kerberos/keytab', $query);
    }

    /**
     * Obtain a Kerberos ticket using a keytab
     *
     * @param string $principal Kerberos principal
     * @param string|null $keytabPath Optional keytab path
     * @param string|null $lifetime Optional ticket lifetime (e.g., "24h")
     * @param string|null $renewable Optional renewable lifetime (e.g., "7d")
     * @return array|null Kinit result
     */
    public function kinit(
        string $principal,
        ?string $keytabPath = null,
        ?string $lifetime = null,
        ?string $renewable = null
    ): ?array {
        $data = ['principal' => $principal];

        if ($keytabPath !== null) {
            $data['keytab_path'] = $keytabPath;
        }
        if ($lifetime !== null) {
            $data['lifetime'] = $lifetime;
        }
        if ($renewable !== null) {
            $data['renewable'] = $renewable;
        }

        return $this->post('/api/v1/kerberos/kinit', $data);
    }

    /**
     * Destroy the current Kerberos ticket cache
     *
     * @return array|null Kdestroy result
     */
    public function kdestroy()
    {
        return $this->post('/api/v1/kerberos/kdestroy');
    }

    /**
     * Validate a Kerberos keytab configuration
     *
     * Performs comprehensive validation including:
     * - Keytab file exists and is readable
     * - Principal exists in keytab
     * - Can authenticate with KDC
     * - Can obtain TGT
     *
     * @param string $keytabPath Path to the keytab file
     * @param string $principal Kerberos principal
     * @param string $realm Kerberos realm
     * @param string|null $kdcHost Optional KDC host
     * @param string|null $testSpn Optional SPN to test service ticket
     * @return array|null Validation result with 'valid' boolean and 'checks' array
     */
    public function validateKeytab(
        $keytabPath,
        $principal,
        $realm,
        $kdcHost = null,
        $testSpn = null
    ) {
        $data = [
            'keytab_path' => $keytabPath,
            'principal' => $principal,
            'realm' => $realm,
        ];

        if ($kdcHost !== null) {
            $data['kdc_host'] = $kdcHost;
        }

        if ($testSpn !== null) {
            $data['test_spn'] = $testSpn;
        }

        return $this->post('/api/v1/kerberos/validate', $data);
    }

    /**
     * Check if a keytab is valid
     *
     * @param string $keytabPath Path to the keytab file
     * @param string $principal Kerberos principal
     * @param string $realm Kerberos realm
     * @param string|null $kdcHost Optional KDC host
     * @return bool True if valid
     */
    public function isKeytabValid($keytabPath, $principal, $realm, $kdcHost = null)
    {
        $result = $this->validateKeytab($keytabPath, $principal, $realm, $kdcHost);
        return $result !== null && ($result['valid'] ?? false);
    }

    /**
     * Create a keytab using ktutil from username/password
     *
     * This method creates a keytab file without requiring AD administrator access.
     * It uses the ktutil command to generate the keytab from the user's password.
     *
     * @param string $principal Username (without @REALM)
     * @param string $password User password
     * @param string $realm Kerberos realm (uppercase, e.g., "DOMAIN.COM")
     * @param string|null $keytabPath Optional output keytab path (defaults to /etc/siege-daemon/siege.keytab)
     * @param array|null $enctypes Optional encryption types array (defaults to AES256, AES128, RC4)
     * @param int $kvno Optional key version number (defaults to 1)
     * @return array|null Result with 'success', 'keytab_path', 'principal', 'realm', 'enctypes', 'message'
     *
     * Example:
     *   $result = $siege->createKeytabWithKtutil(
     *       'Administrateur',
     *       'P@$$word180872',
     *       'ARTICATECH.NUX',
     *       '/etc/siege-daemon/user.keytab'
     *   );
     *   if ($result && $result['success']) {
     *       echo "Keytab created: " . $result['keytab_path'];
     *   }
     */
    public function createKeytabWithKtutil(
        $principal,
        $password,
        $realm,
        $keytabPath = null,
        $enctypes = null,
        $kvno = 1
    ) {
        $data = array(
            'principal' => $principal,
            'password' => $password,
            'realm' => $realm
        );

        if ($keytabPath !== null) {
            $data['keytab_path'] = $keytabPath;
        }

        if ($enctypes !== null && is_array($enctypes)) {
            $data['enctypes'] = $enctypes;
        }

        if ($kvno > 0) {
            $data['kvno'] = $kvno;
        }

        return $this->post('/api/v1/kerberos/ktutil', $data);
    }

    /**
     * Create a keytab and validate it in one step
     *
     * @param string $principal Username (without @REALM)
     * @param string $password User password
     * @param string $realm Kerberos realm
     * @param string $kdcHost KDC/AD server hostname or IP
     * @param string|null $keytabPath Optional output keytab path
     * @return array Result with 'success', 'keytab_created', 'keytab_valid', 'error'
     */
    public function createAndValidateKeytab(
        $principal,
        $password,
        $realm,
        $kdcHost,
        $keytabPath = null
    ) {
        $result = array(
            'success' => false,
            'keytab_created' => false,
            'keytab_valid' => false,
            'error' => null
        );

        // Create keytab
        $createResult = $this->createKeytabWithKtutil($principal, $password, $realm, $keytabPath);

        if ($createResult === null) {
            $result['error'] = $this->getLastError();
            return $result;
        }

        if (!isset($createResult['success']) || !$createResult['success']) {
            $result['error'] = isset($createResult['error']) ? $createResult['error'] : 'Keytab creation failed';
            return $result;
        }

        $result['keytab_created'] = true;
        $result['keytab_path'] = isset($createResult['keytab_path']) ? $createResult['keytab_path'] : $keytabPath;

        // Validate keytab
        $validateResult = $this->validateKeytab(
            $result['keytab_path'],
            $principal,
            $realm,
            $kdcHost
        );

        if ($validateResult !== null && isset($validateResult['valid']) && $validateResult['valid']) {
            $result['keytab_valid'] = true;
            $result['success'] = true;
        } else {
            $result['error'] = 'Keytab created but validation failed';
            if ($validateResult !== null && isset($validateResult['error'])) {
                $result['error'] .= ': ' . $validateResult['error'];
            }
        }

        return $result;
    }

    // =========================================================================
    // Error Handling
    // =========================================================================

    /**
     * Get the last error message
     *
     * @return string|null Last error message or null if no error
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get the last HTTP status code
     *
     * @return int Last HTTP status code (0 if request failed)
     */
    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    /**
     * Check if the last request was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->lastHttpCode >= 200 && $this->lastHttpCode < 300;
    }

    // =========================================================================
    // Convenience Methods
    // =========================================================================

    /**
     * Create and immediately start a test
     *
     * @param array $config Test configuration
     * @return array|null Test info with ID
     */
    public function runTest(array $config): ?array
    {
        $test = $this->createTest($config);

        if ($test === null || !isset($test['id'])) {
            return null;
        }

        $result = $this->startTest($test['id']);

        if ($result === null) {
            return null;
        }

        return $test;
    }

    /**
     * Wait for a test to complete (polling)
     *
     * @param string $testId Test ID
     * @param int $pollInterval Poll interval in seconds
     * @param int $maxWait Maximum wait time in seconds (0 = unlimited)
     * @param callable|null $progressCallback Optional callback for progress updates
     * @return array|null Final statistics or null on timeout/error
     */
    public function waitForTest(
        string $testId,
        int $pollInterval = 2,
        int $maxWait = 0,
        ?callable $progressCallback = null
    ): ?array {
        $startTime = time();

        while (true) {
            $progress = $this->getTestProgress($testId);

            if ($progress === null) {
                // Test might have ended, try to get final stats
                return $this->getTestResults($testId, 1);
            }

            if ($progressCallback !== null) {
                $progressCallback($progress);
            }

            $state = $progress['state'] ?? '';

            if (in_array($state, ['completed', 'stopped', 'error'])) {
                return $this->getTestResults($testId, 1);
            }

            if ($maxWait > 0 && (time() - $startTime) >= $maxWait) {
                $this->lastError = 'Timeout waiting for test to complete';
                return null;
            }

            sleep($pollInterval);
        }
    }

    /**
     * Quick load test with minimal configuration
     *
     * @param string|array $urls URL(s) to test
     * @param int $concurrentUsers Number of concurrent users
     * @param string $duration Test duration (e.g., "30s", "1m")
     * @return array|null Test configuration with ID
     */
    public function quickTest($urls, $concurrentUsers = 10, $duration = '30s')
    {
        if (is_string($urls)) {
            $urls = [['url' => $urls]];
        } elseif (isset($urls[0]) && is_string($urls[0])) {
            $urls = array_map(function($url) { return ['url' => $url]; }, $urls);
        }

        return $this->runTest([
            'urls' => $urls,
            'concurrent_users' => $concurrentUsers,
            'duration' => $duration,
        ]);
    }
}
