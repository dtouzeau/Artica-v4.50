<?php
/**
 * HTTP Audit Configuration Manager
 *
 * PHP class to create and read http-audit JSON configuration files.
 *
 * Usage:
 *   require_once 'http-audit.inc';
 *
 *   // Create new config
 *   $config = HttpAuditConfig::create('https://example.com');
 *   $config->setBasicAuth('user', 'pass');
 *   $config->save('/path/to/config.json');
 *
 *   // Load existing config
 *   $config = HttpAuditConfig::load('/path/to/config.json');
 *   echo $config->getTargetUrl();
 */
class HttpAuditConfig
{
    private array $config;

    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Create a new configuration with defaults
     */
    public static function create(string $url, string $method = 'GET'): self
    {
        $config = [
            'target' => [
                'url' => $url,
                'method' => $method,
                'headers' => new stdClass(),
                'body' => '',
            ],
            'network' => [
                'interface' => '',
                'timeout_connect' => '10s',
                'timeout_read' => '30s',
                'timeout_total' => '60s',
            ],
            'dns' => [
                'enabled' => true,
                'servers' => [],
                'expect_ips' => [],
            ],
            'proxy' => [
                'enabled' => false,
                'url' => '',
                'auth' => [
                    'type' => 'none',
                    'username' => '',
                    'password' => '',
                ],
            ],
            'auth' => [
                'type' => 'none',
                'basic' => [
                    'username' => '',
                    'password' => '',
                ],
                'kerberos' => [
                    'username' => '',
                    'password' => '',
                    'kdc_server' => '',
                    'realm' => '',
                    'keytab_path' => '',
                    'generate_keytab' => false,
                    'service_name' => 'HTTP',
                ],
            ],
            'ssl' => [
                'verify' => true,
                'check_protocols' => true,
                'check_ciphers' => false,
                'min_version' => 'TLS1.2',
            ],
            'http' => [
                'user_agent' => 'http-audit/1.0',
                'follow_redirects' => true,
                'max_redirects' => 10,
                'accept_language' => 'fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3',
                'accept_encoding' => 'gzip, deflate, br',
                'connection' => 'keep-alive',
                'referer' => '',
                'x_forwarded_for' => '',
            ],
            'output' => [
                'html_path' => './report.html',
                'json_path' => './report.json',
                'chartjs_url' => 'https://cdn.jsdelivr.net/npm/chart.js',
            ],
        ];

        return new self($config);
    }

    /**
     * Load configuration from a JSON file
     */
    public static function load(string $path): self
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Configuration file not found: $path");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read configuration file: $path");
        }

        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in configuration file: " . json_last_error_msg());
        }

        return new self($config);
    }

    /**
     * Save configuration to a JSON file
     */
    public function save(string $path): void
    {
        $json = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException("Failed to encode configuration to JSON");
        }

        if (file_put_contents($path, $json . "\n") === false) {
            throw new RuntimeException("Failed to write configuration file: $path");
        }
    }

    /**
     * Get the full configuration array
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Get JSON string
     */
    public function toJson(bool $pretty = true): string
    {
        $flags = JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return json_encode($this->config, $flags);
    }

    // --- Target Configuration ---

    public function getTargetUrl(): string
    {
        return $this->config['target']['url'] ?? '';
    }

    public function setTargetUrl(string $url): self
    {
        $this->config['target']['url'] = $url;
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->config['target']['method'] = strtoupper($method);
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->config['target']['body'] = $body;
        return $this;
    }

    public function addHeader(string $name, string $value): self
    {
        if (!is_array($this->config['target']['headers'])) {
            $this->config['target']['headers'] = [];
        }
        $this->config['target']['headers'][$name] = $value;
        return $this;
    }

    // --- Network Configuration ---

    public function setInterface(string $interface): self
    {
        $this->config['network']['interface'] = $interface;
        return $this;
    }

    public function setTimeouts(string $connect = '10s', string $read = '30s', string $total = '60s'): self
    {
        $this->config['network']['timeout_connect'] = $connect;
        $this->config['network']['timeout_read'] = $read;
        $this->config['network']['timeout_total'] = $total;
        return $this;
    }

    // --- DNS Configuration ---

    public function setDnsEnabled(bool $enabled): self
    {
        $this->config['dns']['enabled'] = $enabled;
        return $this;
    }

    public function setDnsServers(array $servers): self
    {
        $this->config['dns']['servers'] = $servers;
        return $this;
    }

    public function setExpectedIPs(array $ips): self
    {
        $this->config['dns']['expect_ips'] = $ips;
        return $this;
    }

    // --- Proxy Configuration ---

    public function setProxy(string $url, ?string $username = null, ?string $password = null): self
    {
        $this->config['proxy']['enabled'] = true;
        $this->config['proxy']['url'] = $url;

        if ($username !== null) {
            $this->config['proxy']['auth']['type'] = 'basic';
            $this->config['proxy']['auth']['username'] = $username;
            $this->config['proxy']['auth']['password'] = $password ?? '';
        }

        return $this;
    }

    public function disableProxy(): self
    {
        $this->config['proxy']['enabled'] = false;
        return $this;
    }

    // --- Authentication ---

    public function setBasicAuth(string $username, string $password): self
    {
        $this->config['auth']['type'] = 'basic';
        $this->config['auth']['basic']['username'] = $username;
        $this->config['auth']['basic']['password'] = $password;
        return $this;
    }

    public function setKerberosAuth(
        string $realm,
        string $keytabPath,
        ?string $username = null,
        ?string $password = null,
        ?string $kdcServer = null,
        bool $generateKeytab = false
    ): self {
        $this->config['auth']['type'] = 'kerberos';
        $this->config['auth']['kerberos']['realm'] = $realm;
        $this->config['auth']['kerberos']['keytab_path'] = $keytabPath;
        $this->config['auth']['kerberos']['generate_keytab'] = $generateKeytab;

        if ($username !== null) {
            $this->config['auth']['kerberos']['username'] = $username;
        }
        if ($password !== null) {
            $this->config['auth']['kerberos']['password'] = $password;
        }
        if ($kdcServer !== null) {
            $this->config['auth']['kerberos']['kdc_server'] = $kdcServer;
        }

        return $this;
    }

    public function disableAuth(): self
    {
        $this->config['auth']['type'] = 'none';
        return $this;
    }

    // --- SSL Configuration ---

    public function setSslVerify(bool $verify): self
    {
        $this->config['ssl']['verify'] = $verify;
        return $this;
    }

    public function setMinTlsVersion(string $version): self
    {
        $allowed = ['TLS1.0', 'TLS1.1', 'TLS1.2', 'TLS1.3'];
        if (!in_array($version, $allowed)) {
            throw new InvalidArgumentException("Invalid TLS version. Allowed: " . implode(', ', $allowed));
        }
        $this->config['ssl']['min_version'] = $version;
        return $this;
    }

    public function setCheckProtocols(bool $check): self
    {
        $this->config['ssl']['check_protocols'] = $check;
        return $this;
    }

    public function setCheckCiphers(bool $check): self
    {
        $this->config['ssl']['check_ciphers'] = $check;
        return $this;
    }

    // --- HTTP Configuration ---

    public function setUserAgent(string $userAgent): self
    {
        $this->config['http']['user_agent'] = $userAgent;
        return $this;
    }

    public function setFollowRedirects(bool $follow, int $maxRedirects = 10): self
    {
        $this->config['http']['follow_redirects'] = $follow;
        $this->config['http']['max_redirects'] = $maxRedirects;
        return $this;
    }

    public function setReferer(string $referer): self
    {
        $this->config['http']['referer'] = $referer;
        return $this;
    }

    public function setXForwardedFor(string $ip): self
    {
        $this->config['http']['x_forwarded_for'] = $ip;
        return $this;
    }

    // --- Output Configuration ---

    public function setOutputPaths(string $htmlPath, string $jsonPath): self
    {
        $this->config['output']['html_path'] = $htmlPath;
        $this->config['output']['json_path'] = $jsonPath;
        return $this;
    }

    public function setChartJsUrl(string $url): self
    {
        $this->config['output']['chartjs_url'] = $url;
        return $this;
    }
}
