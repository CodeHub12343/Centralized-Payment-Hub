<?php
/**
 * Token Generator Module
 * Handles creation of secure payment tokens for CMS integration
 */

namespace App\Modules\Token;

use App\Utils\SecurityHelper;

class TokenGenerator {
    
    /**
     * Generate a secure payment token
     * 
     * @param string $siteCode The CMS website code
     * @param string $orderId Order ID from CMS
     * @param float $amount Payment amount
     * @param string $currency Currency code (default: USD)
     * @param string $secretKey Secret key from website configuration
     * @return string The complete token (base64_payload.signature)
     * @throws \Exception
     */
    public static function generate($siteCode, $orderId, $amount, $currency = 'USD', $secretKey) {
        // Validate inputs
        if (empty($siteCode) || empty($orderId) || empty($amount) || empty($secretKey)) {
            throw new \Exception('Missing required token parameters');
        }

        if ($amount < MIN_TRANSACTION_AMOUNT || $amount > MAX_TRANSACTION_AMOUNT) {
            throw new \Exception('Invalid amount: must be between ' . MIN_TRANSACTION_AMOUNT . ' and ' . MAX_TRANSACTION_AMOUNT);
        }

        // Create payload
        $payload = [
            'site' => $siteCode,
            'order_id' => $orderId,
            'amount' => floatval($amount),
            'currency' => strtoupper($currency),
            'timestamp' => time()
        ];

        // Encode payload in base64
        $payloadJson = json_encode($payload);
        $encodedPayload = base64_encode($payloadJson);

        // Generate signature
        $signature = SecurityHelper::generateSignature($encodedPayload, $secretKey);

        // Combine payload and signature
        $token = $encodedPayload . '.' . $signature;

        return $token;
    }

    /**
     * Extract and validate token components
     * 
     * @param string $token The complete token
     * @return array Token components ['payload' => string, 'signature' => string]
     * @throws \Exception
     */
    public static function parse($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            throw new \Exception('Invalid token format');
        }

        return [
            'payload' => $parts[0],
            'signature' => $parts[1]
        ];
    }

    /**
     * Decode the payload from a token
     * 
     * @param string $encodedPayload Base64 encoded payload
     * @return array Decoded payload
     * @throws \Exception
     */
    public static function decodePayload($encodedPayload) {
        $decoded = base64_decode($encodedPayload, true);

        if ($decoded === false) {
            throw new \Exception('Invalid payload encoding');
        }

        $payload = json_decode($decoded, true);

        if (!is_array($payload)) {
            throw new \Exception('Invalid payload JSON');
        }

        return $payload;
    }

    /**
     * Extract site code and order ID from token for URL parsing
     * Useful for construction like /pay/TOKEN will extract these details
     * 
     * @param string $token Full token
     * @return array ['site' => string, 'order_id' => string]
     * @throws \Exception
     */
    public static function getTokenInfo($token) {
        $parts = self::parse($token);
        $payload = self::decodePayload($parts['payload']);

        return [
            'site' => $payload['site'] ?? null,
            'order_id' => $payload['order_id'] ?? null
        ];
    }

    /**
     * Generate token redirect URL
     * 
     * @param string $token Payment token
     * @param string $domain Payment hub domain
     * @return string Complete redirect URL
     */
    public static function getRedirectUrl($token, $domain = APP_DOMAIN) {
        return $domain . '/pay/' . urlencode($token);
    }
}
