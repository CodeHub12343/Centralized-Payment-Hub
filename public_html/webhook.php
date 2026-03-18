<?php
/**
 * Payment Hub - Webhook Handler
 * URL: https://pay.pivotpointinv.com/webhook.php
 * 
 * Receives webhook callbacks from PawaPay and updates transaction status
 * Implements idempotency and signature verification
 */

// ============================================
// BOOTSTRAP & INITIALIZATION
// ============================================

$basePath = dirname(__file__);
require_once $basePath . '/../app/Config/config.php';
require_once $basePath . '/../app/Core/Database.php';
require_once $basePath . '/../app/Utils/SecurityHelper.php';
require_once $basePath . '/../app/Utils/Logger.php';
require_once $basePath . '/../app/Utils/HttpHelper.php';
require_once $basePath . '/../app/Modules/Webhook/WebhookProcessor.php';
require_once $basePath . '/../app/Modules/Payment/PawaPayClient.php';
require_once $basePath . '/../app/Modules/Payment/PaymentManager.php';

use App\Utils\Logger;
use App\Modules\Webhook\WebhookProcessor;

// ============================================
// SECURITY CHECKS
// ============================================

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Log webhook receipt
$clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
Logger::logWebhook('received', 'raw_request', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'ip' => $clientIp,
    'time' => date('Y-m-d H:i:s')
]);

// ============================================
// MAIN LOGIC
// ============================================

try {
    // Get raw request body
    $rawBody = file_get_contents('php://input');

    if (!$rawBody) {
        throw new \Exception('Empty webhook body');
    }

    // Get signature from header
    $signatureHeader = $_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? 
                       $_SERVER['HTTP_SIGNATURE'] ?? 
                       null;

    if (!$signatureHeader) {
        throw new \Exception('Missing webhook signature header');
    }

    Logger::logWebhook('init', 'raw_data_received', [
        'body_length' => strlen($rawBody),
        'has_signature' => !empty($signatureHeader)
    ]);

    // ============================================
    // PROCESS WEBHOOK
    // ============================================

    $processor = new WebhookProcessor();
    $result = $processor->process($rawBody, $signatureHeader);

    // Return success response
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Webhook processed',
        'event_id' => $result['event_id'] ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    Logger::logWebhook('complete', 'webhook_processed', $result);
    exit;

} catch (\Exception $e) {
    $errorMessage = APP_DEBUG ? $e->getMessage() : 'Webhook processing failed';
    
    Logger::logError("Webhook Processing Error: " . $e->getMessage(), $e);

    // Return error response
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
