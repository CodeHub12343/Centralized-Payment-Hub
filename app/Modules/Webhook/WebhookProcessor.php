<?php
/**
 * Webhook Processor Module
 * Handles PawaPay webhook callbacks with idempotency and signature verification
 */

namespace App\Modules\Webhook;

use App\Utils\Logger;
use App\Core\Database;
use App\Modules\Payment\PawaPayClient;
use App\Modules\Payment\PaymentManager;

class WebhookProcessor {
    
    private $db;
    private $pawaPayClient;
    private $paymentManager;

    /**
     * Initialize Webhook Processor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->pawaPayClient = new PawaPayClient();
        $this->paymentManager = new PaymentManager();
    }

    /**
     * Process incoming webhook from PawaPay
     * 
     * @param string $rawBody Raw request body
     * @param string $signatureHeader Signature from x-pawapay-signature header
     * @return array Processing result
     * @throws \Exception
     */
    public function process($rawBody, $signatureHeader) {
        try {
            Logger::logWebhook('start', 'webhook_received', ['body' => $rawBody]);

            // 1. Verify webhook signature
            if (!$this->pawaPayClient->verifyWebhookSignature($rawBody, $signatureHeader)) {
                throw new \Exception('Webhook signature verification failed');
            }

            Logger::logWebhook('signature_verified', 'verification_passed');

            // 2. Parse webhook payload
            $payload = json_decode($rawBody, true);
            if (!is_array($payload)) {
                throw new \Exception('Invalid webhook JSON');
            }

            Logger::logWebhook('payload_parsed', 'json_decoded', $payload);

            // 3. Check for idempotency (prevent duplicate processing)
            $eventId = $payload['eventId'] ?? null;
            if ($eventId && $this->isEventProcessed($eventId)) {
                Logger::logWebhook($eventId, 'duplicate_ignored', ['already_processed' => true]);
                return [
                    'success' => true,
                    'message' => 'Event already processed (idempotent)',
                    'duplicate' => true
                ];
            }

            // 4. Process the webhook based on type
            $eventType = $payload['eventType'] ?? 'unknown';
            $result = $this->handleEvent($payload, $eventType);

            // 5. Record webhook event as processed
            if ($eventId) {
                $this->recordWebhookEvent($eventId, $eventType, $payload, true);
            }

            return array_merge(
                $result,
                [
                    'success' => true,
                    'event_id' => $eventId,
                    'timestamp' => time()
                ]
            );

        } catch (\Exception $e) {
            Logger::logError("Webhook Processing Error: " . $e->getMessage(), $e);
            $this->recordWebhookEvent($payload['eventId'] ?? 'unknown', 'error', $payload, false);
            throw $e;
        }
    }

    /**
     * Handle specific webhook event types
     * 
     * @param array $payload Webhook payload
     * @param string $eventType Type of event
     * @return array Event processing result
     * @throws \Exception
     */
    private function handleEvent($payload, $eventType) {
        switch (strtoupper($eventType)) {
            case 'DEPOSIT_COMPLETED':
            case 'DEPOSIT_SUCCESSFUL':
                return $this->handleDepositCompleted($payload);

            case 'DEPOSIT_FAILED':
                return $this->handleDepositFailed($payload);

            case 'DEPOSIT_PENDING':
                return $this->handleDepositPending($payload);

            case 'DEPOSIT_REJECTED':
                return $this->handleDepositRejected($payload);

            default:
                Logger::logWebhook('unknown_event', $eventType, $payload);
                return ['message' => 'Unknown event type: ' . $eventType];
        }
    }

    /**
     * Handle successful deposit
     * 
     * @param array $payload Webhook payload
     * @return array Result
     * @throws \Exception
     */
    private function handleDepositCompleted($payload) {
        $externalId = $payload['externalId'] ?? null;

        if (!$externalId) {
            throw new \Exception('Missing externalId in webhook payload');
        }

        // Find transaction by TX ID (externalId)
        $transaction = $this->db->selectOne(
            "SELECT * FROM transactions WHERE tx_id = ?",
            [$externalId]
        );

        if (!$transaction) {
            throw new \Exception("Transaction not found: $externalId");
        }

        // Update transaction status to success
        $this->paymentManager->completePayment(
            $externalId,
            'success',
            $payload['depositId'] ?? null
        );

        Logger::logPayment($externalId, 'deposit_completed_via_webhook', $payload);

        return [
            'message' => 'Deposit completed',
            'tx_id' => $externalId,
            'deposit_id' => $payload['depositId'] ?? null
        ];
    }

    /**
     * Handle failed deposit
     * 
     * @param array $payload Webhook payload
     * @return array Result
     * @throws \Exception
     */
    private function handleDepositFailed($payload) {
        $externalId = $payload['externalId'] ?? null;

        if (!$externalId) {
            throw new \Exception('Missing externalId in webhook payload');
        }

        // Update transaction status to failed
        $this->paymentManager->completePayment(
            $externalId,
            'failed',
            $payload['depositId'] ?? null
        );

        Logger::logPayment($externalId, 'deposit_failed_via_webhook', $payload);

        return [
            'message' => 'Deposit failed',
            'tx_id' => $externalId,
            'reason' => $payload['reason'] ?? 'Unknown'
        ];
    }

    /**
     * Handle pending deposit
     * 
     * @param array $payload Webhook payload
     * @return array Result
     * @throws \Exception
     */
    private function handleDepositPending($payload) {
        $externalId = $payload['externalId'] ?? null;

        if (!$externalId) {
            throw new \Exception('Missing externalId in webhook payload');
        }

        // Just log, don't change status
        Logger::logPayment($externalId, 'deposit_pending_via_webhook', $payload);

        return [
            'message' => 'Deposit pending',
            'tx_id' => $externalId
        ];
    }

    /**
     * Handle rejected deposit
     * 
     * @param array $payload Webhook payload
     * @return array Result
     * @throws \Exception
     */
    private function handleDepositRejected($payload) {
        $externalId = $payload['externalId'] ?? null;

        if (!$externalId) {
            throw new \Exception('Missing externalId in webhook payload');
        }

        // Update transaction status to failed
        $this->paymentManager->completePayment(
            $externalId,
            'failed',
            $payload['depositId'] ?? null
        );

        Logger::logPayment($externalId, 'deposit_rejected_via_webhook', $payload);

        return [
            'message' => 'Deposit rejected',
            'tx_id' => $externalId,
            'reason' => $payload['reason'] ?? 'Unknown'
        ];
    }

    /**
     * Check if webhook event has already been processed (idempotency)
     * 
     * @param string $eventId Event ID
     * @return bool True if already processed
     */
    private function isEventProcessed($eventId) {
        try {
            $event = $this->db->selectOne(
                "SELECT processed FROM webhook_events WHERE event_id = ?",
                [$eventId]
            );

            return $event && $event['processed'];

        } catch (\Exception $e) {
            Logger::logError("Check Event Processed Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record webhook event in database for audit trail
     * 
     * @param string $eventId Event ID
     * @param string $eventType Event type
     * @param array $payload Event payload
     * @param bool $processed Whether successfully processed
     */
    private function recordWebhookEvent($eventId, $eventType, $payload, $processed = false) {
        try {
            // Check if event already exists
            $existing = $this->db->selectOne(
                "SELECT id FROM webhook_events WHERE event_id = ?",
                [$eventId]
            );

            if ($existing) {
                // Update existing record
                $this->db->update(
                    'webhook_events',
                    [
                        'processed' => $processed ? 1 : 0,
                        'processed_at' => $processed ? date('Y-m-d H:i:s') : null,
                        'payload' => json_encode($payload)
                    ],
                    'event_id = ?',
                    [$eventId]
                );
            } else {
                // Insert new record
                $this->db->insert('webhook_events', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'payload' => json_encode($payload),
                    'processed' => $processed ? 1 : 0,
                    'processed_at' => $processed ? date('Y-m-d H:i:s') : null
                ]);
            }

        } catch (\Exception $e) {
            Logger::logError("Record Webhook Event Error: " . $e->getMessage());
            // Don't throw - webhook processing shouldn't fail just because we can't record it
        }
    }
}
