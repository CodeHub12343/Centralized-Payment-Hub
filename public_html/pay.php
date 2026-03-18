<?php
/**
 * Payment Hub - Main Payment Entry Point
 * URL: https://pay.pivotpointinv.com/pay/{token}
 * 
 * This endpoint:
 * 1. Receives token from CMS
 * 2. Validates token
 * 3. Creates transaction
 * 4. Initiates payment with PawaPay
 * 5. Redirects user to PawaPay checkout
 */

// ============================================
// BOOTSTRAP & INITIALIZATION
// ============================================

// Get the correct base path for includes
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/app/Config/config.php';
require_once $basePath . '/app/Core/Database.php';
require_once $basePath . '/app/Utils/SecurityHelper.php';
require_once $basePath . '/app/Utils/Logger.php';
require_once $basePath . '/app/Utils/HttpHelper.php';
require_once $basePath . '/app/Modules/Token/TokenGenerator.php';
require_once $basePath . '/app/Modules/Token/TokenValidator.php';
require_once $basePath . '/app/Modules/Payment/PawaPayClient.php';
require_once $basePath . '/app/Modules/Payment/PaymentManager.php';

use App\Utils\SecurityHelper;
use App\Utils\Logger;
use App\Modules\Token\TokenValidator;
use App\Modules\Token\TokenGenerator;
use App\Modules\Payment\PaymentManager;

// ============================================
// SECURITY CHECKS
// ============================================

// Enforce HTTPS
if (FORCE_HTTPS && !SecurityHelper::isHttps()) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate limiting
$clientIp = SecurityHelper::getClientIp();
if (!SecurityHelper::checkRateLimit($clientIp)) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded']));
}

// ============================================
// MAIN LOGIC
// ============================================

try {
    // Get token from URL
    $token = isset($_GET['token']) ? trim($_GET['token']) : null;
    
    // Also check if token is passed as direct URL segment: /pay/TOKEN
    if (!$token && isset($_SERVER['REQUEST_URI'])) {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($uri, '/'));
        
        if (count($parts) >= 2 && $parts[0] === 'pay') {
            $token = isset($parts[1]) ? urldecode($parts[1]) : null;
        }
    }

    if (!$token) {
        throw new \Exception('No payment token provided');
    }

    Logger::logPayment('NEW', 'payment_request_received', ['token_received' => true], $clientIp);

    // ============================================
    // 1. VALIDATE TOKEN
    // ============================================
    
    $payload = TokenValidator::validate($token);
    $safePayload = TokenValidator::getSafePayload($payload);

    Logger::logPayment('NEW', 'token_validated', $safePayload);

    // ============================================
    // 2. EXTRACT PAYMENT DETAILS
    // ============================================

    $siteCode = $safePayload['site'];
    $orderId = $safePayload['order_id'];
    $amount = $safePayload['amount'];
    $currency = $safePayload['currency'];

    // Generate transaction ID
    $txId = SecurityHelper::generateTransactionId();

    Logger::logPayment($txId, 'payment_hub_entry', $safePayload);

    // ============================================
    // 3. INITIATE PAYMENT
    // ============================================

    $paymentManager = new PaymentManager();
    $result = $paymentManager->processPayment($txId, $siteCode, $orderId, $amount, $currency);

    Logger::logPayment($txId, 'payment_processing_initiated', $result);

    // ============================================
    // 4. REDIRECT TO PAWAPAY CHECKOUT
    // ============================================

    $checkoutUrl = $result['checkout_url'];
    
    // Log redirect
    Logger::logPayment($txId, 'redirecting_to_checkout', ['url' => $checkoutUrl]);
    
    // Redirect to PawaPay
    header('Location: ' . $checkoutUrl);
    exit;

} catch (\Exception $e) {
    $errorMessage = APP_DEBUG ? $e->getMessage() : 'Payment processing failed';
    
    Logger::logError("Payment Entry Point Error: " . $e->getMessage(), $e);
    
    // Return error response
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $errorMessage,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $clientIp
    ]);
    exit;
}
