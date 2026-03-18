<?php
/**
 * Payment Manager
 * Orchestrates payment creation, lock management, and transaction flow
 */

namespace App\Modules\Payment;

use App\Utils\SecurityHelper;
use App\Utils\Logger;
use App\Core\Database;

class PaymentManager {
    
    private $db;
    private $pawaPayClient;

    /**
     * Initialize Payment Manager
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->pawaPayClient = new PawaPayClient();
    }

    /**
     * Create a payment lock to prevent duplicate payments
     * 
     * @param string $site Website code
     * @param string $orderId Order ID
     * @return bool True if lock created successfully
     * @throws \Exception
     */
    public function createPaymentLock($site, $orderId) {
        try {
            // Check if lock already exists
            $existingLock = $this->db->selectOne(
                "SELECT id FROM payment_locks WHERE site = ? AND order_id = ?",
                [$site, $orderId]
            );

            if ($existingLock) {
                throw new \Exception('Payment lock already exists for this order');
            }

            // Create new lock
            $this->db->insert('payment_locks', [
                'site' => $site,
                'order_id' => $orderId
            ]);

            Logger::logPayment(
                'LOCK',
                'payment_lock_created',
                ['site' => $site, 'order_id' => $orderId]
            );

            return true;

        } catch (\Exception $e) {
            Logger::logError("Create Payment Lock Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Release a payment lock
     * 
     * @param string $site Website code
     * @param string $orderId Order ID
     * @return bool True if lock released
     */
    public function releasePaymentLock($site, $orderId) {
        try {
            $this->db->delete(
                'payment_locks',
                'site = ? AND order_id = ?',
                [$site, $orderId]
            );

            Logger::logPayment(
                'LOCK',
                'payment_lock_released',
                ['site' => $site, 'order_id' => $orderId]
            );

            return true;

        } catch (\Exception $e) {
            Logger::logError("Release Payment Lock Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new transaction in the database
     * 
     * @param string $txId Transaction ID
     * @param string $site Website code
     * @param string $orderId Order ID
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @param string $status Initial status
     * @return int Database ID of created transaction
     * @throws \Exception
     */
    public function createTransaction($txId, $site, $orderId, $amount, $currency, $status = 'pending') {
        try {
            $id = $this->db->insert('transactions', [
                'tx_id' => $txId,
                'site' => $site,
                'order_id' => $orderId,
                'amount' => floatval($amount),
                'currency' => strtoupper($currency),
                'status' => $status
            ]);

            Logger::logPayment(
                $txId,
                'transaction_created',
                [
                    'site' => $site,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'currency' => $currency
                ]
            );

            return $id;

        } catch (\Exception $e) {
            Logger::logError("Create Transaction Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update transaction with PawaPay deposit ID
     * 
     * @param string $txId Transaction ID
     * @param string $depositId PawaPay deposit ID
     * @return bool True if updated
     */
    public function updateTransactionWithDeposit($txId, $depositId) {
        try {
            $this->db->update(
                'transactions',
                ['pawapay_deposit_id' => $depositId],
                'tx_id = ?',
                [$txId]
            );

            Logger::logPayment($txId, 'deposit_id_recorded', ['deposit_id' => $depositId]);

            return true;

        } catch (\Exception $e) {
            Logger::logError("Update Transaction Deposit Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update transaction status
     * 
     * @param string $txId Transaction ID
     * @param string $newStatus New status
     * @param string $providerRef Provider reference (optional)
     * @return bool True if updated
     */
    public function updateTransactionStatus($txId, $newStatus, $providerRef = null) {
        try {
            // Get current transaction
            $transaction = $this->db->selectOne(
                "SELECT * FROM transactions WHERE tx_id = ?",
                [$txId]
            );

            if (!$transaction) {
                throw new \Exception("Transaction not found: $txId");
            }

            $oldStatus = $transaction['status'];

            // Prepare update data
            $updateData = ['status' => $newStatus];
            if ($providerRef) {
                $updateData['provider_ref'] = $providerRef;
            }

            // Update transaction
            $this->db->update(
                'transactions',
                $updateData,
                'tx_id = ?',
                [$txId]
            );

            // Log status change
            Logger::logStatusChange($txId, $oldStatus, $newStatus);

            return true;

        } catch (\Exception $e) {
            Logger::logError("Update Transaction Status Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get transaction by ID
     * 
     * @param string $txId Transaction ID
     * @return array|null Transaction data or null if not found
     */
    public function getTransaction($txId) {
        try {
            return $this->db->selectOne(
                "SELECT * FROM transactions WHERE tx_id = ?",
                [$txId]
            );
        } catch (\Exception $e) {
            Logger::logError("Get Transaction Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process complete payment flow
     * 
     * @param string $token Payment token (from CMS)
     * @return array Result with redirect URL
     * @throws \Exception
     */
    public function processPayment($txId, $site, $orderId, $amount, $currency) {
        try {
            // Start transaction (database)
            $this->db->beginTransaction();

            // Create payment lock
            $this->createPaymentLock($site, $orderId);

            // Create transaction record
            $this->createTransaction($txId, $site, $orderId, $amount, $currency, 'pending');

            // Initiate deposit with PawaPay
            $depositResponse = $this->pawaPayClient->initiateDeposit(
                $txId,
                $orderId,
                $amount,
                $currency,
                $site
            );

            // Update transaction with deposit ID
            if (isset($depositResponse['id'])) {
                $this->updateTransactionWithDeposit($txId, $depositResponse['id']);
            }

            // Commit database transaction
            $this->db->commit();

            // Get checkout URL from PawaPay
            $checkoutUrl = $this->pawaPayClient->getCheckoutUrl($depositResponse);

            Logger::logPayment(
                $txId,
                'payment_processing_complete',
                ['checkout_url' => $checkoutUrl]
            );

            return [
                'success' => true,
                'tx_id' => $txId,
                'checkout_url' => $checkoutUrl,
                'deposit_id' => $depositResponse['id']
            ];

        } catch (\Exception $e) {
            // Rollback on error
            try {
                $this->db->rollback();
            } catch (\Exception $rollbackError) {
                Logger::logError("Rollback Error: " . $rollbackError->getMessage());
            }

            // Release lock on error
            $this->releasePaymentLock($site, $orderId);

            Logger::logError("Payment Processing Error: " . $e->getMessage(), $e);

            throw $e;
        }
    }

    /**
     * Handle payment completion (from webhook)
     * 
     * @param string $txId Transaction ID
     * @param string $newStatus Payment status
     * @param string $providerRef PawaPay provider reference
     * @return bool True if successful
     */
    public function completePayment($txId, $newStatus, $providerRef = null) {
        try {
            // Update transaction status
            $this->updateTransactionStatus($txId, $newStatus, $providerRef);

            // Get transaction details
            $transaction = $this->getTransaction($txId);

            if ($transaction && in_array($newStatus, ['success', 'failed', 'cancelled'])) {
                // Release lock when payment is completed
                $this->releasePaymentLock($transaction['site'], $transaction['order_id']);
            }

            Logger::logPayment(
                $txId,
                'payment_completed',
                ['status' => $newStatus, 'provider_ref' => $providerRef]
            );

            return true;

        } catch (\Exception $e) {
            Logger::logError("Complete Payment Error: " . $e->getMessage());
            return false;
        }
    }
}
