<?php

class ValkeyClient
{
    private const SOCK   = '/run/redis/redis.sock';
    private const TCP_HOST = '127.0.0.1';  // TCP fallback host
    private const TCP_PORT = 6379;          // TCP fallback port
    private const DB     = 0;             // already selected by connect arg
    private const PREFIX = 'SET:';        // your convention
    private const TTL_D  = 172800;        // 2 days default
    private const HTTP_SOCK = '/usr/share/artica-postfix/bin/run/articarest.sock'; // HTTP fallback
    private const MEMCACHE_SOCK = '/run/artmem.sock';  // Memcache Unix socket
    private const MEMCACHE_HOST = '127.0.0.1';  // Memcache TCP fallback host
    private const MEMCACHE_PORT = 11155;         // Memcache TCP fallback port
    /** @var bool|null */
    private static $unlinkSupported = null;
    /** @var Redis|null */
    private static $r = null;
    /** @var Memcached|null */
    private static $mc = null;
    /** @var bool */
    private static $useHttpFallback = false;

    /** Get a persistent, shared Redis instance (UNIX socket or TCP fallback).
     * @return Redis|null
     */
    private static function r()
    {
        if (self::$r instanceof Redis) {
            // Check if connection is still alive
            try {
                self::$r->ping();
                return self::$r;
            } catch (\RedisException $e) {
                // Connection is broken, reconnect
                self::$r = null;
            }
        }

        // Try Unix socket first
        try {
            $r = new Redis();

            // persistent connect over UNIX socket
            // phpredis: pconnect($host, $port, $timeout, $persistent_id, $retry_interval, $read_timeout, $context)
            // For UNIX socket, pass host = socket path & port = 0
            if ($r->pconnect(self::SOCK, 0, 1.0, 'valkey-unix-sock')) {
                // timeouts
                $r->setOption(Redis::OPT_READ_TIMEOUT, 1.0);  // seconds
                $r->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

                // If you need a DB, you can ensure with select() (not needed if server default is 0)
                if (self::DB !== 0) {
                    $r->select(self::DB);
                }

                self::$r = $r;
                return self::$r;
            }
        } catch (\RedisException $e) {
            // Unix socket failed, will try TCP fallback
            if (function_exists('writelogs')) {
                writelogs('Unix socket failed: ' . $e->getMessage() . ', trying TCP fallback', __FUNCTION__, __FILE__, __LINE__);
            }
        }

        // Try TCP connection as fallback
        try {
            $r = new Redis();

            if (function_exists('writelogs')) {
                writelogs('Attempting TCP connection to ' . self::TCP_HOST . ':' . self::TCP_PORT, __FUNCTION__, __FILE__, __LINE__);
            }

            if (!$r->pconnect(self::TCP_HOST, self::TCP_PORT, 1.0, 'valkey-tcp')) {
                if (function_exists('writelogs')) {
                    writelogs('TCP connection failed to ' . self::TCP_HOST . ':' . self::TCP_PORT, __FUNCTION__, __FILE__, __LINE__);
                }
                return null;
            }

            // timeouts
            $r->setOption(Redis::OPT_READ_TIMEOUT, 1.0);
            $r->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

            // Select database
            if (self::DB !== 0) {
                $r->select(self::DB);
            }

            if (function_exists('writelogs')) {
                writelogs('TCP connection successful to ' . self::TCP_HOST . ':' . self::TCP_PORT, __FUNCTION__, __FILE__, __LINE__);
            }

            self::$r = $r;
            return self::$r;
        } catch (\RedisException $e) {
            // TCP connection also failed - log but don't crash
            if (function_exists('VERBOSE')) {
                VERBOSE(sprintf('TCP connection failed: %s' . $e->getMessage(), __LINE__));
            }
            return null;
        }
    }

    /**
     * Get a persistent, shared Memcached instance (UNIX socket or TCP fallback).
     * @return Memcached|null
     */
    private static function mc()
    {
        if (self::$mc instanceof Memcached) {
            // Check if connection is still alive with a quick operation
            try {
                @self::$mc->get('__health_check__');
                return self::$mc;
            } catch (\Throwable $e) {
                // Connection is broken, reconnect
                self::$mc = null;
            }
        }

        // Try Unix socket first
        try {
            $mc = new Memcached('valkey-memcached-persistent');

            // Check if this persistent instance already has servers configured
            $servers = $mc->getServerList();
            if (empty($servers)) {
                // Attempt to add Unix socket server
                // addServer($host, $port, $weight) - for Unix socket, use the socket path as host
                if (!@$mc->addServer(self::MEMCACHE_SOCK, 0)) {
                    throw new \Exception('Failed to add Unix socket server');
                }
            }
            // Test connection with a ping
            @$mc->get('__health_check__');

            self::$mc = $mc;
            if (function_exists('VERBOSE')) {
                VERBOSE('Memcached connected via Unix socket (persistent): ' . self::MEMCACHE_SOCK,  __LINE__);
            }
            return self::$mc;
        } catch (\Throwable $e) {
            // Unix socket failed, will try TCP fallback
            if (function_exists('writelogs')) {
                writelogs('Memcached Unix socket failed: ' . $e->getMessage() . ', trying TCP fallback', __FUNCTION__, __FILE__, __LINE__);
            }
            self::$mc = null;
        }

        // Try TCP connection as fallback
        try {
            $mc = new Memcached('valkey-memcached-tcp-persistent');

            if (function_exists('writelogs')) {
                writelogs('Attempting Memcached TCP connection to ' . self::MEMCACHE_HOST . ':' . self::MEMCACHE_PORT, __FUNCTION__, __FILE__, __LINE__);
            }

            // Check if this persistent instance already has servers configured
            $servers = $mc->getServerList();
            if (empty($servers)) {
                if (!@$mc->addServer(self::MEMCACHE_HOST, self::MEMCACHE_PORT)) {
                    if (function_exists('writelogs')) {
                        writelogs('Memcached TCP connection failed to ' . self::MEMCACHE_HOST . ':' . self::MEMCACHE_PORT, __FUNCTION__, __FILE__, __LINE__);
                    }
                    return null;
                }
            }

            // Test connection with a ping
            @$mc->get('__health_check__');

            if (function_exists('writelogs')) {
                writelogs('Memcached TCP connection successful (persistent) to ' . self::MEMCACHE_HOST . ':' . self::MEMCACHE_PORT, __FUNCTION__, __FILE__, __LINE__);
            }

            self::$mc = $mc;
            return self::$mc;
        } catch (\Throwable $e) {
            // TCP connection also failed - log but don't crash
            if (function_exists('writelogs')) {
                writelogs('Memcached TCP connection failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return null;
        }
    }

    /**
     * HTTP fallback: Send request to Unix socket HTTP server
     * @param string $path
     * @return string|false
     */
    private static function httpRequest($path)
    {
        if (!file_exists(self::HTTP_SOCK)) {
            return false;
        }

        $client = stream_socket_client('unix://' . self::HTTP_SOCK, $errno, $errstr, 5);
        if (!$client) {
            if (function_exists('writelogs')) {
                writelogs("HTTP fallback failed: $errstr ($errno)", __FUNCTION__, __FILE__, __LINE__);
            }
            return false;
        }

        $request = "GET $path HTTP/1.1\r\n";
        $request .= "Host: localhost\r\n";
        $request .= "Connection: close\r\n\r\n";

        fwrite($client, $request);

        $response = '';
        while (!feof($client)) {
            $response .= fgets($client, 1024);
        }
        fclose($client);

        // Extract body from HTTP response
        $parts = explode("\r\n\r\n", $response, 2);
        if (count($parts) === 2) {
            return $parts[1];
        }

        return false;
    }

    /**
     * HTTP fallback: Get key value from HTTP server
     * @param string $key
     * @return string
     */
    public static function httpGetInfo($key)
    {
        $encodedKey = urlencode($key);
        $response = self::httpRequest("/getinfo/$encodedKey");

        if ($response === false) {
            return '';
        }

        $data = json_decode($response, true);
        if (isset($data['Status']) && $data['Status'] === true && isset($data['Data'])) {
            if (isset($data['Encoded']) && $data['Encoded'] === true) {
                return base64_decode($data['Data']);
            }
            return $data['Data'];
        }

        return '';
    }

    /**
     * HTTP fallback: Set key value via HTTP server
     * @param string $key
     * @param string $value
     * @return bool
     */
    private static function httpSetInfo($key, $value)
    {
        $encodedKey = urlencode(base64_encode($key));
        $encodedValue = urlencode(base64_encode($value));
        $response = self::httpRequest("/setinfo/$encodedKey/$encodedValue");

        if ($response === false) {
            return false;
        }

        $data = json_decode($response, true);
        return isset($data['Status']) && $data['Status'] === true;
    }

    /** Normalize key with SET: prefix once. */
    private static function norm(string $key): string
    {
        if (strpos($key, self::PREFIX) === 0) return $key;
        return self::PREFIX . $key;
    }
    private static function unlinkOrDel(\Redis $r, string $key): bool
    {
        // If we don't yet know, or we think UNLINK is supported, try it.
        if (self::$unlinkSupported !== false) {
            try {
                $res = $r->rawCommand('UNLINK', $key); // returns int(0/1) on success, or false on error
                if ($res !== false) {
                    self::$unlinkSupported = true;
                    return (bool)$res;
                }
                // Error path (no exception): check last error for "unknown command"
                $err = method_exists($r, 'getLastError') ? (string)$r->getLastError() : '';
                if (stripos($err, 'unknown') !== false) {
                    self::$unlinkSupported = false; // remember: UNLINK not supported
                } else {
                    // Some other error: propagate as failure
                    return false;
                }
            } catch (\RedisException $e) {
                if (stripos($e->getMessage(), 'unknown') !== false) {
                    self::$unlinkSupported = false; // not supported
                } else {
                    // Real runtime error
                    return false;
                }
            }
        }

        // Fallback (or known unsupported): use DEL
        return (bool)$r->del($key);
    }

    /** SET string with TTL (seconds). Empty value => delete. */
    public static function setSetting(string $key, string $value, int $ttl = self::TTL_D): bool
    {
        $key = self::norm($key);

        // Check if using Memcache protocol
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            $mc = self::mc();
            if ($mc === null) {
                return false; // Connection failed
            }

            try {
                if ($value === '') {
                    return $mc->delete($key);
                }

                // Memcached set with TTL (0 = no expiration)
                $expiration = ($ttl <= 0) ? 0 : $ttl;
                return $mc->set($key, $value, $expiration);
            } catch (\Throwable $e) {
                if (function_exists('writelogs')) {
                    writelogs('Memcached set failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
                }
                return false;
            }
        }

        // Use Redis/Valkey protocol
        $r   = self::r();

        if ($r === null) {
            // Connection failed, try HTTP fallback
            return self::httpSetInfo($key, $value);
        }

        try {
            if ($value === '') {
                return self::unlinkOrDel($r, $key);
            }

            if ($ttl <= 0) {
                return $r->set($key, $value);
            }
            // Use SET with EX for atomic TTL
            return $r->set($key, $value, ['ex' => $ttl]);
        } catch (\RedisException $e) {
            // Connection lost during operation - invalidate and try HTTP fallback
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis set failed: ' . $e->getMessage() . ', trying HTTP fallback', __FUNCTION__, __FILE__, __LINE__);
            }
            return self::httpSetInfo($key, $value);
        }
    }

    /** GET string; returns "" when key is missing (no extra EXISTS round trip). */
    public static function getSetting(string $key): string{
        if(!isset($GLOBALS["UseMemCacheClient"])){CheckMemCached();}
        $key = self::norm($key);

        // Check if using Memcache protocol
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            $mc = self::mc();
            if ($mc === null) {
                return ''; // Connection failed
            }

            try {
                $val = $mc->get($key);
                if ($val === false) {
                    // key missing → return empty string
                    return '';
                }
                return is_string($val) ? $val : (string)$val;
            } catch (\Throwable $e) {
                if (function_exists('writelogs')) {
                    writelogs('Memcached get failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
                }
                return '';
            }
        }

        // Use Redis/Valkey protocol
        $r   = self::r();

        if ($r === null) {
            // Connection failed, try HTTP fallback
            return self::httpGetInfo($key);
        }

        try {
            $val = $r->get($key);
            if ($val === false) {
                // key missing → return empty string (don't log spam)
                return '';
            }
            return is_string($val) ? $val : (string)$val;
        } catch (\RedisException $e) {
            // Connection lost during operation - invalidate and try HTTP fallback
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis get failed: ' . $e->getMessage() . ', trying HTTP fallback', __FUNCTION__, __FILE__, __LINE__);
            }
            return self::httpGetInfo($key);
        }
    }

    /** Delete key (async). */
    public static function delKey(string $key): bool
    {
        $key = self::norm($key);

        // Check if using Memcache protocol
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            $mc = self::mc();
            if ($mc === null) {
                return false; // Connection failed
            }

            try {
                return $mc->delete($key);
            } catch (\Throwable $e) {
                if (function_exists('writelogs')) {
                    writelogs('Memcached delete failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
                }
                return false;
            }
        }

        // Use Redis/Valkey protocol
        $r   = self::r();

        if ($r === null) {
            return false; // Connection failed
        }

        try {
            return (bool)$r->rawCommand('UNLINK', $key);
        } catch (\RedisException $e) {
            // Connection lost during operation
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis delete failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return false;
        }
    }

    /** Scan all keys matching a pattern, efficiently. Returns array of keys. */
    public static function listKeys(string $pattern = '*'): array
    {
        // Memcache doesn't support key scanning
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            if (function_exists('writelogs')) {
                writelogs('listKeys() not supported in Memcache mode', __FUNCTION__, __FILE__, __LINE__);
            }
            return []; // Not supported in Memcache
        }

        $r = self::r();

        if ($r === null) {
            return []; // Connection failed, return empty array
        }

        try {
            $it = null;
            $out = [];
            while (true) {
                $chunk = $r->scan($it, $pattern, 1000);
                if ($chunk === false) break;
                // $chunk is an array of keys
                foreach ($chunk as $k) {
                    $out[] = $k;
                }
            }
            return $out;
        } catch (\RedisException $e) {
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis listKeys failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return [];
        }
    }

    /** Count keys by pattern without materializing the full list. */
    public static function countKeys(string $pattern = '*'): int
    {
        // Memcache doesn't support key scanning
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            if (function_exists('writelogs')) {
                writelogs('countKeys() not supported in Memcache mode', __FUNCTION__, __FILE__, __LINE__);
            }
            return 0; // Not supported in Memcache
        }

        $r = self::r();

        if ($r === null) {
            return 0; // Connection failed, return 0
        }

        try {
            $it = null;
            $count = 0;
            while (true) {
                $chunk = $r->scan($it, $pattern, 2000);
                if ($chunk === false) break;
                $count += count($chunk);
            }
            return $count;
        } catch (\RedisException $e) {
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis countKeys failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return 0;
        }
    }

    /** Bulk delete by pattern using SCAN + UNLINK in batches. */
    public static function unlinkByPattern(string $pattern, int $batch = 1000): int
    {
        // Memcache doesn't support key scanning
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            if (function_exists('writelogs')) {
                writelogs('unlinkByPattern() not supported in Memcache mode', __FUNCTION__, __FILE__, __LINE__);
            }
            return 0; // Not supported in Memcache
        }

        $r = self::r();

        if ($r === null) {
            return 0; // Connection failed, return 0
        }

        try {
            $it = null;
            $buf = [];
            $deleted = 0;

            while (true) {
                $chunk = $r->scan($it, $pattern, 2000);
                if ($chunk === false) break;

                foreach ($chunk as $k) {
                    // Skip your PHPREDIS* keys if needed
                    if (strpos($k, 'PHPREDIS') === 0) continue;

                    $buf[] = $k;
                    if (count($buf) >= $batch) {
                        $deleted += self::unlinkBatch($r, $buf);
                        $buf = [];
                    }
                }
            }
            if ($buf) {
                $deleted += self::unlinkBatch($r, $buf);
            }
            return $deleted;
        } catch (\RedisException $e) {
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis unlinkByPattern failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return 0;
        }
    }

    private static function unlinkBatch(Redis $r, array $keys): int
    {
        try {
            // pipeline UNLINK for lower RTT
            $r->multi(Redis::PIPELINE);
            foreach ($keys as $k) {
                $r->rawCommand('UNLINK', $k);
            }
            $res = $r->exec();
            // each entry is int removed (0/1)
            return array_sum(array_map('intval', $res));
        } catch (\RedisException $e) {
            if (function_exists('writelogs')) {
                writelogs('Redis unlinkBatch failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return 0;
        }
    }

    /** TTL helper (returns seconds, -1=no expiry, -2=missing). */
    public static function ttl(string $key): int
    {
        $key = self::norm($key);

        // Memcache doesn't support TTL query
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            if (function_exists('writelogs')) {
                writelogs('ttl() not supported in Memcache mode', __FUNCTION__, __FILE__, __LINE__);
            }
            return -2; // Not supported in Memcache
        }

        $r = self::r();

        if ($r === null) {
            return -2; // Connection failed, return -2 (missing)
        }

        try {
            return (int) $r->ttl($key);
        } catch (\RedisException $e) {
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis ttl failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return -2;
        }
    }

    /** Optional: get INFO stats as array (phpredis has no getStats for Redis). */
    public static function info(string $section = 'all'): array
    {
        // Memcache has different stats
        if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
            $mc = self::mc();
            if ($mc === null) {
                return []; // Connection failed
            }

            try {
                // Get Memcache stats
                $stats = $mc->getStats();
                return $stats ?: [];
            } catch (\Throwable $e) {
                if (function_exists('writelogs')) {
                    writelogs('Memcached getStats failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
                }
                return [];
            }
        }

        $r = self::r();

        if ($r === null) {
            return []; // Connection failed, return empty array
        }

        try {
            return $r->info($section) ?: [];
        } catch (\RedisException $e) {
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis info failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return [];
        }
    }

    public static function Statistics(): array
    {
        try {
            // Memcache stats
            if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
                $mc = self::mc();
                if ($mc === null) {
                    return []; // Connection failed
                }

                $stats = $mc->getStats();
                return [
                    'memcache' => $stats ?: [],
                ];
            }

            // Redis/Valkey stats
            $r = self::r();

            if ($r === null) {
                return []; // Connection failed, return empty array
            }

            // You can fetch a specific section or 'all'
            $server  = $r->info('server')   ?: [];
            $clients = $r->info('clients')  ?: [];
            $memory  = $r->info('memory')   ?: [];
            $persistence = $r->info('persistence') ?: [];
            $stats   = $r->info('stats')    ?: [];
            $cpu     = $r->info('cpu')      ?: [];
            $replica = $r->info('replication') ?: [];
            $keyspace = $r->info('keyspace') ?: [];

            // Optionally include commandstats (expensive on very old servers)
            $commandstats = $r->info('commandstats') ?: [];

            return [
                'server'       => $server,
                'clients'      => $clients,
                'memory'       => $memory,
                'persistence'  => $persistence,
                'stats'        => $stats,
                'cpu'          => $cpu,
                'replication'  => $replica,
                'keyspace'     => $keyspace,
                'commandstats' => $commandstats,
            ];
        } catch (\RedisException $e) {
            // Invalidate connection on Redis errors
            self::$r = null;
            if (function_exists('writelogs')) {
                writelogs('Redis Statistics failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return [];
        } catch (\Throwable $e) {
            // Return empty array on failure (keeps old contract)
            if (function_exists('writelogs')) {
                writelogs('Statistics failed: ' . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return [];
        }
    }
    public static function getKey(string $key): string
    {
        try {
            // Keep your SET: prefix convention
            if (strpos($key, self::PREFIX) !== 0) {
                $key = self::PREFIX . $key;
            }

            // Check if using Memcache protocol
            if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
                $mc = self::mc();
                if ($mc === null) {
                    return ''; // Connection failed
                }

                $val = $mc->get($key);
                if ($val === false) {
                    // treat as "not found" -> empty string
                    return '';
                }

                if (is_array($val)) {
                    return serialize($val);
                }

                $value = is_string($val) ? trim($val) : (string)$val;

                // Preserve your special marker semantics
                if ($value === '!nil') {
                    return '';
                }
                return $value;
            }

            // Use Redis/Valkey protocol
            $r = self::r();

            if ($r === null) {
                // Connection failed, try HTTP fallback
                return self::httpGetInfo($key);
            }

            try {
                // Fast GET; missing key returns false in phpredis
                $val = $r->get($key);
                if ($val === false) {
                    // treat as "not found" -> empty string (no logging spam, no EXISTS)
                    return '';
                }

                if (is_array($val)) {
                    // Shouldn't normally happen (GET returns string), but stay defensive
                    return serialize($val);
                }

                $value = is_string($val) ? trim($val) : (string)$val;

                // Preserve your special marker semantics
                if ($value === '!nil') {
                    return '';
                }
                return $value;
            } catch (\RedisException $e) {
                // Connection lost during operation - invalidate and try HTTP fallback
                self::$r = null;
                if (function_exists('writelogs')) {
                    writelogs('Redis getKey failed: ' . $e->getMessage() . ', trying HTTP fallback', __FUNCTION__, __FILE__, __LINE__);
                }
                return self::httpGetInfo($key);
            }

        } catch (\Throwable $e) {
            if (function_exists('writelogs')) {
                writelogs("Fatal ".$e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            // On any error, behave like old code: mark as not found
            return '';
        }
    }
    public static function saveKey(string $key, $object, int $ttl = 0): bool
    {
        try {
            // Normalize to SET: prefix once
            if (strpos($key, self::PREFIX) !== 0) {
                $key = self::PREFIX . $key;
            }

            // Do not accept arrays (matches old behavior)
            if (is_array($object)) {
                // your logger:
                if (function_exists('writelogs')) {
                    writelogs("Fatal \"$key\" try to save an Array??", __FUNCTION__, __FILE__, __LINE__);
                }
                return false;
            }

            // Check if using Memcache protocol
            if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
                $mc = self::mc();
                if ($mc === null) {
                    return false; // Connection failed
                }

                // Default TTL like your old code
                if ($ttl === 0) {
                    $ttl = self::TTL_D; // 172800
                }

                // Empty value => delete key
                if ($object === '' || $object === null || strlen((string)$object) === 0) {
                    return $mc->delete($key);
                }

                $value = trim((string)$object);

                // Memcached set with TTL
                return $mc->set($key, $value, $ttl);
            }

            // Use Redis/Valkey protocol
            $r = self::r();

            if ($r === null) {
                // Connection failed, try HTTP fallback
                $value = ($object === '' || $object === null) ? '' : trim((string)$object);
                return self::httpSetInfo($key, $value);
            }

            try {
                // Default TTL like your old code
                if ($ttl === 0) {
                    $ttl = self::TTL_D; // 172800
                }

                // Empty value => delete key (async to avoid blocking)
                if ($object === '' || $object === null || strlen((string)$object) === 0) {
                    // Prefer UNLINK; fallback to DEL if needed
                    // return (bool)$r->rawCommand('UNLINK', $key);
                    $r->rawCommand('UNLINK', $key);
                    return true;
                }

                $value = trim((string)$object);

                // Atomic set with TTL
                if ($ttl > 0) {
                    // phpredis: set($key, $value, ['ex' => $seconds])
                    return $r->set($key, $value, ['ex' => $ttl]);
                } else {
                    // (Shouldn't happen with our default, but keep for completeness)
                    return $r->set($key, $value);
                }
            } catch (\RedisException $e) {
                // Connection lost during operation - invalidate and try HTTP fallback
                self::$r = null;
                if (function_exists('writelogs')) {
                    writelogs('Redis saveKey failed: ' . $e->getMessage() . ', trying HTTP fallback', __FUNCTION__, __FILE__, __LINE__);
                }
                $value = ($object === '' || $object === null) ? '' : trim((string)$object);
                return self::httpSetInfo($key, $value);
            }

        } catch (\Throwable $e) {
            if (function_exists('writelogs')) {
                writelogs("Fatal ".$e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
            }
            return false;
        }
    }
}
