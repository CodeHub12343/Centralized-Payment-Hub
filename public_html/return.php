<?php
/**
 * Payment Hub - Return Handler
 * URL: https://pay.pivotpointinv.com/return.php
 * 
 * Handles user returns from PawaPay payment flow
 * Returns user to CMS with payment status
 */

// ============================================
// BOOTSTRAP & INITIALIZATION
// ============================================

$basePath = dirname(__FILE__);
require_once $basePath . '/../app/Config/config.php';
require_once $basePath . '/../app/Core/Database.php';
require_once $basePath . '/../app/Utils/SecurityHelper.php';
require_once $basePath . '/../app/Utils/Logger.php';

use App\Utils\SecurityHelper;
use App\Utils\Logger;
use App\Core\Database;

// ============================================
// SECURITY CHECKS
// ============================================

// Enforce HTTPS
if (FORCE_HTTPS && !SecurityHelper::isHttps()) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// ============================================
// MAIN LOGIC
// ============================================

try {
    $clientIp = SecurityHelper::getClientIp();
    
    // Get transaction ID from URL parameter
    $txId = isset($_GET['tx_id']) ? trim($_GET['tx_id']) : null;
    
    if (!$txId) {
        throw new \Exception('No transaction ID provided');
    }

    Logger::logPayment($txId, 'return_handler_called', ['ip' => $clientIp]);

    // ============================================
    // 1. LOOKUP TRANSACTION
    // ============================================

    $db = Database::getInstance();
    $transaction = $db->selectOne(
        "SELECT * FROM transactions WHERE tx_id = ? LIMIT 1",
        [$txId]
    );

    if (!$transaction) {
        throw new \Exception("Transaction not found: $txId");
    }

    Logger::logPayment($txId, 'transaction_located', [
        'site' => $transaction['site'],
        'order_id' => $transaction['order_id'],
        'status' => $transaction['status']
    ]);

    // ============================================
    // 2. GET WEBSITE CONFIG
    // ============================================

    $website = $db->selectOne(
        "SELECT * FROM websites WHERE site_code = ? LIMIT 1",
        [$transaction['site']]
    );

    if (!$website) {
        throw new \Exception("Website not found: {$transaction['site']}");
    }

    // ============================================
    // 3. BUILD RETURN URL
    // ============================================

    // Determine which URL to use based on payment status
    $status = strtolower($transaction['status']);
    $isSuccess = $status === 'success';
    
    $returnUrl = $isSuccess ? $website['success_url'] : $website['fail_url'];

    if (!SecurityHelper::isValidHttpsUrl($returnUrl)) {
        Logger::logError("Invalid return URL for transaction: $txId", new \Exception("URL: $returnUrl"));
        throw new \Exception('Invalid return URL configured');
    }

    // ============================================
    // 4. BUILD RETURN REDIRECT WITH PARAMETERS
    // ============================================

    // Build query parameters to append
    $params = [
        'tx_id' => $txId,
        'order_id' => $transaction['order_id'],
        'status' => $status,
        'amount' => $transaction['amount'],
        'currency' => $transaction['currency'],
        'timestamp' => time()
    ];

    // Build separator based on URL structure
    $separator = strpos($returnUrl, '?') !== false ? '&' : '?';
    $finalUrl = $returnUrl . $separator . http_build_query($params);

    Logger::logPayment($txId, 'return_redirect_prepared', [
        'return_url' => $returnUrl,
        'status' => $status,
        'final_url' => $finalUrl
    ]);

    // ============================================
    // 5. REDIRECT USER BACK TO CMS
    // ============================================

    header('Location: ' . $finalUrl);
    exit;

} catch (\Exception $e) {
    $errorMessage = APP_DEBUG ? $e->getMessage() : 'Return processing failed';
    
    Logger::logError("Return Handler Error: " . $e->getMessage(), $e);

    // Display error page
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Return Error</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; }
            .container { max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #d32f2f; }
            .error { background: #ffebee; padding: 10px; border-left: 4px solid #d32f2f; margin: 20px 0; }
            .info { color: #666; margin: 20px 0; }
            a { color: #1976d2; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Payment Return Error</h1>
            <div class="error">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
            <div class="info">
                <p>If you need assistance, please contact support with your transaction ID.</p>
                <p><a href="javascript:history.back()">← Go Back</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
