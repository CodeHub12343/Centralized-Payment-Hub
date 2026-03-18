<?php
/**
 * Token Validator Module
 * Handles validation of payment tokens at the payment hub
 */

namespace App\Modules\Token;

use App\Utils\SecurityHelper;
use App\Utils\Logger;
use App\Core\Database;

class TokenValidator {
    
    /**
     * Validate a complete payment token
     * 
     * @param string $token The token to validate
     * @return array Validated payload
     * @throws \Exception
     */
    public static function validate($token) {
        $db = Database::getInstance();

        try {
            // 1. Parse the token
            $parts = TokenGenerator::parse($token);
            $encodedPayload = $parts['payload'];
            $signature = $parts['signature'];

            // 2. Decode payload
            $payload = TokenGenerator::decodePayload($encodedPayload);

            // 3. Validate payload structure
            self::validatePayloadStructure($payload);

            // 4. Check token expiration
            self::validateExpiration($payload['timestamp']);

            // 5. Verify signature
            $siteCode = $payload['site'];
            $website = self::getWebsiteConfig($siteCode, $db);
            self::verifySignature($encodedPayload, $signature, $website['secret_key']);

            // 6. Check for duplicate/locked payments
            self::checkPaymentLock($siteCode, $payload['order_id'], $db);

            // 7. Validate website is active
            if (!$website['is_active']) {
                throw new \Exception('Website is inactive');
            }

            return $payload;

        } catch (\Exception $e) {
            Logger::logError("Token validation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate token payload structure
     * 
     * @param array $payload Decoded payload
     * @throws \Exception
     */
    private static function validatePayloadStructure($payload) {
        $required = ['site', 'order_id', 'amount', 'currency', 'timestamp'];

        foreach ($required as $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                throw new \Exception("Missing required field: $field");
            }
        }

        // Validate data types
        if (!is_string($payload['site'])) {
            throw new \Exception("Invalid site code format");
        }

        if (!is_string($payload['order_id'])) {
            throw new \Exception("Invalid order ID format");
        }

        if (!is_numeric($payload['amount'])) {
            throw new \Exception("Invalid amount format");
        }

        if (!is_numeric($payload['timestamp']) || !is_int($payload['timestamp'])) {
            throw new \Exception("Invalid timestamp format");
        }
    }

    /**
     * Check if token is expired
     * 
     * @param int $timestamp Token creation timestamp
     * @throws \Exception
     */
    private static function validateExpiration($timestamp) {
        if (SecurityHelper::isTokenExpired($timestamp)) {
            throw new \Exception('Token has expired');
        }
    }

    /**
     * Get website configuration from database
     * 
     * @param string $siteCode Website code
     * @param Database $db Database instance
     * @return array Website configuration
     * @throws \Exception
     */
    private static function getWebsiteConfig($siteCode, $db) {
        $website = $db->selectOne(
            "SELECT * FROM websites WHERE site_code = ? LIMIT 1",
            [$siteCode]
        );

        if (!$website) {
            throw new \Exception("Website not found: $siteCode");
        }

        return $website;
    }

    /**
     * Verify token signature
     * 
     * @param string $payload Encoded payload
     * @param string $signature Signature to verify
     * @param string $secretKey Secret key for verification
     * @throws \Exception
     */
    private static function verifySignature($payload, $signature, $secretKey) {
        if (!SecurityHelper::verifySignature($payload, $signature, $secretKey)) {
            throw new \Exception('Invalid token signature');
        }
    }

    /**
     * Check for existing payment lock (prevent duplicate payments)
     * 
     * @param string $site Site code
     * @param string $orderId Order ID
     * @param Database $db Database instance
     * @throws \Exception
     */
    private static function checkPaymentLock($site, $orderId, $db) {
        $lock = $db->selectOne(
            "SELECT * FROM payment_locks WHERE site = ? AND order_id = ?",
            [$site, $orderId]
        );

        if ($lock) {
            throw new \Exception('Payment already in progress for this order');
        }
    }

    /**
     * Extract and return safe token data
     * 
     * @param array $payload Validated payload
     * @return array Safe payload for use in application
     */
    public static function getSafePayload($payload) {
        return [
            'site' => htmlspecialchars($payload['site']),
            'order_id' => htmlspecialchars($payload['order_id']),
            'amount' => floatval($payload['amount']),
            'currency' => strtoupper($payload['currency']),
            'timestamp' => intval($payload['timestamp'])
        ];
    }
}
