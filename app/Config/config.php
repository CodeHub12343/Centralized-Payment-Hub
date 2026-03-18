<?php
/**
 * Centralized Payment Hub - Configuration
 * PawaPay Integration
 */

// ============================================
// ENVIRONMENT VARIABLE HELPER
// ============================================

/**
 * Get environment variable with fallback to /etc/environment
 */
function getEnvVar($key, $default = null) {
    $value = getenv($key);
    
    // If getenv() fails, try reading from /etc/environment (Docker use case)
    if (!$value && file_exists('/etc/environment')) {
        $lines = file('/etc/environment', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                [$envKey, $envValue] = explode('=', $line, 2);
                $envKey = trim($envKey);
                $envValue = trim($envValue, '\'"');
                if ($envKey === $key) {
                    return $envValue;
                }
            }
        }
    }
    
    return $value ?: $default;
}

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', getEnvVar('DB_HOST'));
define('DB_USER', getEnvVar('DB_USER'));
define('DB_PASS', getEnvVar('DB_PASS'));
define('DB_NAME', getEnvVar('DB_NAME'));
define('DB_PORT', getEnvVar('DB_PORT') ?: 3306);
define('DB_CHARSET', 'utf8mb4');

// ============================================
// APPLICATION CONFIGURATION
// ============================================
define('APP_NAME', 'Centralized Payment Hub');
define('APP_ENV', getEnvVar('APP_ENV', 'production'));
define('APP_DEBUG', getEnvVar('APP_DEBUG', false));

// Domain Configuration
define('APP_DOMAIN', getEnvVar('APP_DOMAIN', 'https://pay.pivotpointinv.com'));
define('APP_WEBHOOK_URL', APP_DOMAIN . '/webhook.php');
define('APP_RETURN_URL', APP_DOMAIN . '/return.php');

// ============================================
// PAWAPAY CONFIGURATION
// ============================================
define('PAWAPAY_API_TOKEN', getEnvVar('PAWAPAY_API_TOKEN', 'your_sandbox_token_here'));
define('PAWAPAY_API_URL', getEnvVar('PAWAPAY_API_URL', 'https://sandbox.pawapay.io'));
define('PAWAPAY_MERCHANT_ID', getEnvVar('PAWAPAY_MERCHANT_ID', '303')); // From your token
define('PAWAPAY_API_VERSION', 'v1');

// ============================================
// SECURITY CONFIGURATION
// ============================================
// Token Expiration Time (in seconds)
define('TOKEN_EXPIRATION', 30 * 60); // 30 minutes

// Session Configuration
define('SESSION_TIMEOUT', 60 * 60); // 1 hour
define('SESSION_NAME', 'ph_admin_session');

// HTTPS Enforcement
define('FORCE_HTTPS', getEnvVar('FORCE_HTTPS', true));

// ============================================
// LOGGING CONFIGURATION
// ============================================
define('LOG_DIR', dirname(dirname(dirname(__FILE__))) . '/logs');
define('LOG_ERROR_FILE', LOG_DIR . '/errors.log');
define('LOG_PAYMENT_FILE', LOG_DIR . '/payments.log');
define('LOG_WEBHOOK_FILE', LOG_DIR . '/webhooks.log');

// ============================================
// CORS & SECURITY HEADERS
// ============================================
define('ALLOWED_ORIGINS', [
    'https://pay.pivotpointinv.com',
    // Add your CMS domain origins here
]);

// ============================================
// JWT CONFIGURATION (ADMIN API)
// ============================================
define('JWT_SECRET', getEnvVar('JWT_SECRET'));
if (!JWT_SECRET) {
    throw new Exception('JWT_SECRET environment variable is required for production');
}
define('JWT_EXPIRATION', 7200); // 2 hours (reduced from 24 for security)
define('CORS_ALLOWED_ORIGINS', getEnvVar('CORS_ALLOWED_ORIGINS'));

// ============================================
// RATE LIMITING
// ============================================
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_ATTEMPTS', 100); // Requests per window
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// ============================================
// TRANSACTION SETTINGS
// ============================================
define('DEFAULT_CURRENCY', 'USD');
define('TRANSACTION_TIMEOUT', 15 * 60); // 15 minutes before marking as failed
define('MAX_TRANSACTION_AMOUNT', 999999.99);
define('MIN_TRANSACTION_AMOUNT', 0.01);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// ============================================
// FEATURE FLAGS
// ============================================
define('ENABLE_IPV6', false);
define('ENABLE_HTTPS_REDIRECT', true);
define('ENABLE_PAYMENT_NOTIFICATIONS', true);

// ============================================
// TIME CONFIGURATION
// ============================================
define('APP_TIMEZONE', 'UTC');
date_default_timezone_set(APP_TIMEZONE);

// ============================================
// ERROR HANDLING
// ============================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_ERROR_FILE);
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $error = "[" . date('Y-m-d H:i:s') . "] Error: $errstr in $errfile on line $errline\n";
    error_log($error, 3, LOG_ERROR_FILE);
    
    if (APP_DEBUG) {
        die($error);
    }
    
    http_response_code(500);
    die(json_encode(['error' => 'Internal Server Error']));
});

// ============================================
// ENVIRONMENT VALIDATION
// ============================================
if (!function_exists('validateEnvironment')) {
    function validateEnvironment() {
        $required = ['DB_HOST', 'DB_USER', 'DB_NAME', 'PAWAPAY_API_TOKEN'];
        $missing = [];
        
        foreach ($required as $var) {
            if (!getenv(str_replace('_', '', $var)) && !defined($var)) {
                $missing[] = $var;
            }
        }
        
        if (!empty($missing)) {
            die("Missing environment variables: " . implode(', ', $missing));
        }
    }
}

return [
    'database' => [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'name' => DB_NAME,
    ],
    'pawapay' => [
        'token' => PAWAPAY_API_TOKEN,
        'url' => PAWAPAY_API_URL,
        'merchant_id' => PAWAPAY_MERCHANT_ID,
    ],
    'app' => [
        'domain' => APP_DOMAIN,
        'env' => APP_ENV,
        'debug' => APP_DEBUG,
    ]
];
