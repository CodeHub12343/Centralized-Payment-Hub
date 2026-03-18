<?php
/**
 * PawaPay API Integration Module
 * Handles communication with PawaPay Merchant API
 */

namespace App\Modules\Payment;

use App\Utils\HttpHelper;
use App\Utils\Logger;

class PawaPayClient {
    
    private $apiToken;
    private $apiUrl;
    private $merchantId;

    /**
     * Initialize PawaPay client
     * 
     * @param string $token API token
     * @param string $url API base URL
     * @param string $merchantId Merchant ID
     */
    public function __construct($token = PAWAPAY_API_TOKEN, $url = PAWAPAY_API_URL, $merchantId = PAWAPAY_MERCHANT_ID) {
        $this->apiToken = $token;
        $this->apiUrl = rtrim($url, '/');
        $this->merchantId = $merchantId;
    }

    /**
     * Initiate a deposit (payment) request to PawaPay
     * 
     * @param string $txId Payment hub transaction ID
     * @param string $orderId Order ID from CMS
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @param string $siteCode Website code
     * @return array PawaPay response with deposit ID
     * @throws \Exception
     */
    public function initiateDeposit($txId, $orderId, $amount, $currency, $siteCode) {
        try {
            // Build request payload for PawaPay
            $payload = [
                'amount' => intval($amount * 100), // Convert to cents
                'currency' => strtoupper($currency),
                'externalId' => $txId, // Our transaction ID as reference
                'correspondentId' => $this->merchantId,
                'statementDescription' => 'Order: ' . htmlspecialchars($orderId),
                'callbackUrl' => APP_WEBHOOK_URL,
                'returnUrl' => APP_RETURN_URL,
                'metadata' => [
                    'site' => $siteCode,
                    'order_id' => $orderId,
                    'tx_id' => $txId
                ]
            ];

            Logger::logPayment($txId, 'pawapay_deposit_initiated', $payload);

            // Make request to PawaPay
            $headers = [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ];

            $response = HttpHelper::post(
                $this->apiUrl . '/api/' . PAWAPAY_API_VERSION . '/deposits',
                $payload,
                $headers
            );

            // Check for errors
            if (!HttpHelper::isSuccess($response['status'])) {
                throw new \Exception(
                    'PawaPay API Error: ' . $response['status'] . ' - ' . $response['body']
                );
            }

            // Decode response
            $result = HttpHelper::decodeJson($response['body']);

            if (!isset($result['id'])) {
                throw new \Exception('Invalid PawaPay response: missing deposit ID');
            }

            Logger::logPayment($txId, 'pawapay_deposit_created', ['deposit_id' => $result['id']]);

            return $result;

        } catch (\Exception $e) {
            Logger::logError("PawaPay Deposit Error: " . $e->getMessage(), $e);
            throw $e;
        }
    }

    /**
     * Get checkout URL from PawaPay for the deposit
     * 
     * @param array $depositResponse PawaPay deposit response
     * @return string Checkout URL for user redirect
     * @throws \Exception
     */
    public function getCheckoutUrl($depositResponse) {
        if (!isset($depositResponse['checkoutUrl'])) {
            throw new \Exception('No checkout URL in PawaPay response');
        }

        return $depositResponse['checkoutUrl'];
    }

    /**
     * Verify webhook signature from PawaPay
     * 
     * @param string $payload Raw webhook body
     * @param string $signature Signature header from PawaPay
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature($payload, $signature) {
        // Generate expected signature
        // PawaPay uses HMAC-SHA256 for webhook verification
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            $this->apiToken
        );

        // Use constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get deposit status from PawaPay
     * 
     * @param string $depositId PawaPay deposit ID
     * @return array Deposit details
     * @throws \Exception
     */
    public function getDepositStatus($depositId) {
        try {
            $headers = [
                'Authorization: Bearer ' . $this->apiToken,
            ];

            $response = HttpHelper::get(
                $this->apiUrl . '/api/' . PAWAPAY_API_VERSION . '/deposits/' . $depositId,
                [],
                $headers
            );

            if (!HttpHelper::isSuccess($response['status'])) {
                throw new \Exception('Failed to fetch deposit status: ' . $response['body']);
            }

            return HttpHelper::decodeJson($response['body']);

        } catch (\Exception $e) {
            Logger::logError("PawaPay Get Status Error: " . $e->getMessage(), $e);
            throw $e;
        }
    }

    /**
     * Process webhook callback from PawaPay
     * 
     * @param array $webhookData Webhook payload
     * @return array Processed webhook data with standardized status
     * @throws \Exception
     */
    public function processWebhookCallback($webhookData) {
        if (!isset($webhookData['depositId']) || !isset($webhookData['status'])) {
            throw new \Exception('Invalid webhook data structure');
        }

        // Map PawaPay statuses to our system
        $statusMap = [
            'COMPLETED' => 'success',
            'SUCCESSFUL' => 'success',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'REJECTED' => 'failed',
            'CANCELLED' => 'cancelled'
        ];

        $pawapayStatus = strtoupper($webhookData['status']);
        $mappedStatus = $statusMap[$pawapayStatus] ?? 'pending';

        return [
            'deposit_id' => $webhookData['depositId'],
            'status' => $mappedStatus,
            'external_id' => $webhookData['externalId'] ?? null,
            'raw_status' => $pawapayStatus,
            'timestamp' => $webhookData['timestamp'] ?? time()
        ];
    }

    /**
     * Build return URL for redirecting user back to CMS
     * 
     * @param string $returnUrl CMS return URL
     * @param string $orderId Order ID
     * @param string $status Payment status
     * @return string Complete return URL with parameters
     */
    public static function buildReturnUrl($returnUrl, $orderId, $status) {
        $params = [
            'order_id' => $orderId,
            'status' => $status,
            'timestamp' => time()
        ];

        $separator = strpos($returnUrl, '?') !== false ? '&' : '?';
        return $returnUrl . $separator . http_build_query($params);
    }
}
