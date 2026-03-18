<?php
/**
 * Payment Connector for CMS Websites
 * 
 * This script should be integrated into your CMS to generate payment tokens
 * and redirect users to the centralized payment hub.
 * 
 * INSTALLATION:
 * 1. Copy this file to your CMS root directory (e.g., payment_connector.php)
 * 2. Update the configuration variables below
 * 3. Integrate into your checkout flow
 * 
 * USAGE:
 * 
 * // Example in your checkout page:
 * require_once 'payment_connector.php';
 * 
 * $connector = new PaymentConnector('YOUR_SITE_CODE', 'YOUR_SECRET_KEY');
 * $paymentUrl = $connector->generatePaymentLink(
 *     $orderId,      // Your order ID
 *     $amount,       // Payment amount (e.g., 99.99)
 *     'USD'          // Currency code
 * );
 * 
 * // Redirect user
 * header('Location: ' . $paymentUrl);
 */

// ============================================
// CONFIGURATION
// ============================================

class PaymentConnector {
    
    // Payment hub configuration
    private $hubDomain = 'https://pay.pivotpointinv.com';
    private $paymentEndpoint = '/pay';
    
    // CMS website configuration
    private $siteCode;
    private $secretKey;

    /**
     * Initialize payment connector
     * 
     * @param string $siteCode CMS website code (provided by payment hub admin)
     * @param string $secretKey Secret key for token signing (provided by payment hub admin)
     * @param string|null $hubDomain Optional: override payment hub domain
     */
    public function __construct($siteCode, $secretKey, $hubDomain = null) {
        if (empty($siteCode) || empty($secretKey)) {
            throw new \Exception('Site code and secret key are required');
        }

        $this->siteCode = $siteCode;
        $this->secretKey = $secretKey;
        
        if ($hubDomain) {
            $this->hubDomain = rtrim($hubDomain, '/');
        }
    }

    /**
     * Generate secure payment token
     * 
     * @param string $orderId Order/Transaction ID from your CMS
     * @param float $amount Payment amount (e.g., 99.99)
     * @param string $currency Currency code (default: USD)
     * @return string Secure payment token
     * @throws \Exception
     */
    public function generateToken($orderId, $amount, $currency = 'USD') {
        // Validate inputs
        if (empty($orderId)) {
            throw new \Exception('Order ID is required');
        }

        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than 0');
        }

        // Create payload
        $payload = [
            'site' => $this->siteCode,
            'order_id' => $orderId,
            'amount' => floatval($amount),
            'currency' => strtoupper($currency),
            'timestamp' => time()
        ];

        // Encode payload in base64
        $payloadJson = json_encode($payload);
        $encodedPayload = base64_encode($payloadJson);

        // Generate HMAC-SHA256 signature
        $signature = hash_hmac('sha256', $encodedPayload, $this->secretKey);

        // Combine payload and signature with dot separator
        $token = $encodedPayload . '.' . $signature;

        return $token;
    }

    /**
     * Generate complete payment URL for redirect
     * 
     * @param string $orderId Order/Transaction ID
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @return string Complete URL to redirect user to
     * @throws \Exception
     */
    public function generatePaymentLink($orderId, $amount, $currency = 'USD') {
        $token = $this->generateToken($orderId, $amount, $currency);
        
        // Build payment URL
        $paymentUrl = $this->hubDomain . $this->paymentEndpoint . '?token=' . urlencode($token);
        
        return $paymentUrl;
    }

    /**
     * Get payment status from hub (Optional API call)
     * 
     * Note: This would require the payment hub to expose a transaction status endpoint
     * For now, use the return URL callback parameters to determine status
     * 
     * @param string $txId Transaction ID
     * @return array|null Transaction status
     */
    public function getPaymentStatus($txId) {
        // TODO: Implement once payment hub exposes API endpoint
        // For now, status is returned via callback URL parameters
        return null;
    }

    /**
     * Validate order_id parameter from payment hub return
     * 
     * Call this function on your return/callback page to process the result
     * 
     * @param string $orderId Order ID from URL parameter
     * @param string $status Payment status from URL parameter
     * @return array Result with order details
     */
    public static function handleReturn($orderId, $status) {
        return [
            'order_id' => $orderId,
            'status' => $status,
            'timestamp' => time()
        ];
    }
}

// ============================================
// EXAMPLE USAGE
// ============================================

/*
// Example 1: Generate payment link and redirect
try {
    $connector = new PaymentConnector(
        'demo_site',           // Your site code from admin dashboard
        'abc123def456ghi789'   // Your secret key from admin dashboard
    );

    $paymentUrl = $connector->generatePaymentLink(
        'ORDER-12345',    // Your order ID
        99.99,            // Amount
        'USD'             // Currency
    );

    // Send to browser
    header('Location: ' . $paymentUrl);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}


// Example 2: Process return from payment hub (on your return page)
if (isset($_GET['order_id']) && isset($_GET['status'])) {
    $result = PaymentConnector::handleReturn(
        $_GET['order_id'],
        $_GET['status']
    );

    if ($result['status'] === 'success') {
        // Payment successful - update order in your system
        echo "Payment successful for order: " . $result['order_id'];
    } else {
        // Payment failed
        echo "Payment failed for order: " . $result['order_id'];
    }
}


// Example 3: Generate token only (for API usage)
$connector = new PaymentConnector('demo_site', 'abc123def456ghi789');
$token = $connector->generateToken('ORDER-12345', 99.99, 'USD');
echo "Payment Token: " . $token;
*/

// ============================================
// WORDPRESS INTEGRATION EXAMPLE
// ============================================

/*
// Add to your WordPress plugin or theme:

add_action('wp_enqueue_scripts', function() {
    // Enqueue payment connector
    wp_localize_script('my-plugin', 'paymentConfig', [
        'siteCode' => 'wordpress_site',
        'secretKey' => 'get_from_admin_dashboard'
    ]);
});

add_action('wp_ajax_process_payment', function() {
    $connector = new PaymentConnector(
        $_POST['siteCode'],
        $_POST['secretKey']
    );

    $url = $connector->generatePaymentLink(
        $_POST['orderId'],
        $_POST['amount'],
        $_POST['currency'] ?? 'USD'
    );

    wp_send_json(['redirect' => $url]);
});
*/

// ============================================
// ERROR HANDLING
// ============================================

// Enable error handling if connector file is accessed directly
if (php_sapi_name() === 'cli' || isset($_GET['test'])) {
    try {
        $test = new PaymentConnector('test_site', 'test_secret_key_123');
        echo "Payment Connector initialized successfully!\n";
        echo "URL: " . $test->generatePaymentLink('TEST-001', 10.00, 'USD') . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
