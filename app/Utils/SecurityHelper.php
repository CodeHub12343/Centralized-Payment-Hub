<?php
/**
 * Security & Crypto Utilities
 * Handles token generation, signature validation, and encryption
 */

namespace App\Utils;

class SecurityHelper {
    
    /**
     * Generate SHA256 signature for token validation
     * 
     * @param string $payload The payload to sign (usually base64 encoded)
     * @param string $secret The secret key for signing
     * @return string The signature in hex format
     */
    public static function generateSignature($payload, $secret) {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify SHA256 signature
     * 
     * @param string $payload The payload to verify
     * @param string $signature The signature to verify against
     * @param string $secret The secret key used for signing
     * @return bool True if signature is valid
     */
    public static function verifySignature($payload, $signature, $secret) {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate random transaction ID
     * Format: TX-TIMESTAMP-RANDOM
     * 
     * @return string Unique transaction ID
     */
    public static function generateTransactionId() {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "TX-" . $timestamp . "-" . $random;
    }

    /**
     * Generate random token
     * 
     * @param int $length Length of token
     * @return string Random token
     */
    public static function generateRandomToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Hash password using bcrypt
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Verify password against hash
     * 
     * @param string $password Plain text password
     * @param string $hash Password hash
     * @return bool True if password matches
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize string to prevent XSS attacks
     * 
     * @param string $str Input string
     * @return string Sanitized string
     */
    public static function sanitize($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool True if valid email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @return bool True if valid URL
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate HTTPS URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid HTTPS URL
     */
    public static function isValidHttpsUrl($url) {
        return strpos($url, 'https://') === 0 && self::isValidUrl($url);
    }

    /**
     * Check if token is expired
     * 
     * @param int $timestamp Token creation timestamp
     * @param int $expirationTime Expiration time in seconds
     * @return bool True if token is expired
     */
    public static function isTokenExpired($timestamp, $expirationTime = TOKEN_EXPIRATION) {
        return (time() - $timestamp) > $expirationTime;
    }

    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if token is valid
     */
    public static function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Check if request is from HTTPS
     * 
     * @return bool True if HTTPS
     */
    public static function isHttps() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }

    /**
     * Rate limit check using IP address
     * 
     * @param string $ip IP address
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $window Time window in seconds
     * @return bool True if within rate limit
     */
    public static function checkRateLimit($ip, $maxAttempts = RATE_LIMIT_ATTEMPTS, $window = RATE_LIMIT_WINDOW) {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }

        // Check if APCu is available
        if (!extension_loaded('apcu')) {
            // Fallback: Allow request if APCu not available (will be added to Docker)
            Logger::log('APCu extension not available, skipping rate limiting', 'warning');
            return true;
        }

        $cacheKey = 'rate_limit_' . $ip;
        $attemptCount = apcu_fetch($cacheKey);

        if ($attemptCount === false) {
            apcu_store($cacheKey, 1, $window);
            return true;
        }

        if ($attemptCount >= $maxAttempts) {
            return false;
        }

        apcu_inc($cacheKey);
        return true;
    }
}
