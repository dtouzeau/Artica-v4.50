<?php
/**
 * DecisionIP Charts Class
 * Provides data fetching for DecisionIP real-time statistics
 *
 * Used by: fw.nginx.DecisionIP.statistics.php
 * API Endpoint: /decisionip/chart/stats
 */

class DecisionIPCharts {

    public function __construct() {
        // Ensure socket class is available
        if (!isset($GLOBALS["CLASS_SOCKETS"])) {
            if (class_exists('sockets')) {
                $GLOBALS["CLASS_SOCKETS"] = new sockets();
            }
        }
    }

    /**
     * Fetch chart statistics from REST API
     * @return array|null Returns stats array or null on failure
     */
    public function getChartStats() {
        if (!isset($GLOBALS["CLASS_SOCKETS"])) {
            return null;
        }

        $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/chart/stats");
        if (empty($data)) {
            return null;
        }

        $json = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!isset($json['Status']) || $json['Status'] !== true) {
            return null;
        }

        return $json['Data'] ?? null;
    }

    /**
     * Flush statistics to disk
     * @return bool Success status
     */
    public function flushStats() {
        if (!isset($GLOBALS["CLASS_SOCKETS"])) {
            return false;
        }

        $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/chart/flush");
        if (empty($data)) {
            return false;
        }

        $json = json_decode($data, true);
        return isset($json['Status']) && $json['Status'] === true;
    }

    /**
     * Flush statistics and reset counters
     * @return bool Success status
     */
    public function flushAndResetStats() {
        if (!isset($GLOBALS["CLASS_SOCKETS"])) {
            return false;
        }

        $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/chart/flush/reset");
        if (empty($data)) {
            return false;
        }

        $json = json_decode($data, true);
        return isset($json['Status']) && $json['Status'] === true;
    }

    /**
     * Get prefix color for charts
     * @param string $prefix The prefix name
     * @return string Hex color code
     */
    public static function getPrefixColor($prefix) {
        $colors = [
            'dipaa'    => '#FF6384',
            'dipspam'  => '#36A2EB',
            'dipentor' => '#FFCE56',
            'dippx'    => '#4BC0C0',
            'dipvpn'   => '#9966FF',
            'diphoney' => '#FF9F40'
        ];
        return $colors[$prefix] ?? '#888888';
    }

    /**
     * Get prefix human-readable label
     * @param string $prefix The prefix name
     * @return string Human-readable label
     */
    public static function getPrefixLabel($prefix) {

        $labels=[
            "dipaa"=>"DecisionIP Active Attacks",
            "dipspam"=>"DecisionIP SPAM",
            "dipentor"=>"TOR Exit nodes",
            "dippx"=>"Proxies Exit nodes",
            "dipvpn"=>"VPN Exit nodes",
            "diphoney"=>"DecisionIP HoneyPots"
        ];
        return $labels[$prefix] ?? strtoupper($prefix);
    }

    /**
     * Get all prefix colors as array
     * @return array Associative array of prefix => color
     */
    public static function getAllPrefixColors() {
        return [
            'dipaa'    => '#FF6384',
            'dipspam'  => '#36A2EB',
            'dipentor' => '#FFCE56',
            'dippx'    => '#4BC0C0',
            'dipvpn'   => '#9966FF',
            'diphoney' => '#FF9F40'
        ];
    }

    /**
     * Get all prefix labels as array
     * @return array Associative array of prefix => label
     */
    public static function getAllPrefixLabels() {
        return [
            'dipaa'    => 'Active-Attack',
            'dipspam'  => 'Spam',
            'dipentor' => 'Tor',
            'dippx'    => 'Proxy',
            'dipvpn'   => 'VPN',
            'diphoney' => 'Honeypot'
        ];
    }
}
