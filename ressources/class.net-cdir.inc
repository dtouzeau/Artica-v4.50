<?php

class NetworkMasks{

    public function __construct(){

    }
    /**
     * Network utility functions for converting various network formats to CIDR notation
     */

    /**
     * Convert network address to CIDR notation
     *
     * Accepts:
     * - IP address:           8.8.8.8            → 8.8.8.8/32
     * - CIDR notation:        192.168.1.0/24     → 192.168.1.0/24
     * - Subnet mask notation: 10.0.0.0/255.0.0.0 → 10.0.0.0/8
     *
     * @param string $network Network address in any supported format
     * @return string|false CIDR notation or false on invalid input
     */
    public function networkToCIDR($network) {
        $network = trim($network);

        if (empty($network)) {
            return false;
        }

        // Check if already in CIDR notation (192.168.1.0/24)
        if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $network, $matches)) {
            list(, $oct1, $oct2, $oct3, $oct4, $cidr) = $matches;

            // Validate octets
            if (!$this->isValidOctet($oct1) || !$this->isValidOctet($oct2) || !$this->isValidOctet($oct3) || !$this->isValidOctet($oct4)) {
                return false;
            }

            // Validate CIDR
            $cidr = (int)$cidr;
            if ($cidr < 0 || $cidr > 32) {
                return false;
            }

            return "$oct1.$oct2.$oct3.$oct4/$cidr";
        }

        // Check if subnet mask notation (192.168.1.0/255.255.255.0)
        if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $network, $matches)) {
            list(, $oct1, $oct2, $oct3, $oct4, $mask1, $mask2, $mask3, $mask4) = $matches;

            // Validate IP octets
            if (!$this->isValidOctet($oct1) || !$this->isValidOctet($oct2) || !$this->isValidOctet($oct3) || !$this->isValidOctet($oct4)) {
                return false;
            }

            // Validate subnet mask octets
            if (!$this->isValidOctet($mask1) || !$this->isValidOctet($mask2) || !$this->isValidOctet($mask3) || !$this->isValidOctet($mask4)) {
                return false;
            }

            // Convert subnet mask to CIDR
            $cidr = $this->subnetMaskToCIDR($mask1, $mask2, $mask3, $mask4);
            if ($cidr === false) {
                return false;
            }

            return "$oct1.$oct2.$oct3.$oct4/$cidr";
        }

        // Check if plain IP address (8.8.8.8)
        if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $network, $matches)) {
            list(, $oct1, $oct2, $oct3, $oct4) = $matches;

            // Validate octets
            if (!$this->isValidOctet($oct1) || !$this->isValidOctet($oct2) || !$this->isValidOctet($oct3) || !$this->isValidOctet($oct4)) {
                return false;
            }

            // Single IP = /32
            return "$oct1.$oct2.$oct3.$oct4/32";
        }

        // Invalid format
        return false;
    }

    /**
     * Check if an octet value is valid (0-255)
     *
     * @param string $octet Octet value to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidOctet($octet) {
        $num = (int)$octet;
        return $num >= 0 && $num <= 255;
    }

    /**
     * Convert subnet mask to CIDR prefix length
     *
     * @param int $oct1 First octet
     * @param int $oct2 Second octet
     * @param int $oct3 Third octet
     * @param int $oct4 Fourth octet
     * @return int|false CIDR prefix (0-32) or false if invalid
     */
    private function subnetMaskToCIDR($oct1, $oct2, $oct3, $oct4) {
        // Convert to 32-bit integer
        $mask = ($oct1 << 24) | ($oct2 << 16) | ($oct3 << 8) | $oct4;

        // Convert to binary string (32 bits)
        $binary = str_pad(decbin($mask), 32, '0', STR_PAD_LEFT);

        // Check if it's a valid subnet mask (contiguous 1s followed by 0s)
        if (!preg_match('/^1*0*$/', $binary)) {
            return false; // Non-contiguous bits
        }

        // Count the number of 1s (this is the CIDR prefix)
        $cidr = substr_count($binary, '1');

        return $cidr;
    }

    /**
     * Validate if a network string is in valid format
     *
     * @param string $network Network address to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidNetwork($network) {
        return $this->networkToCIDR($network) !== false;
    }
}