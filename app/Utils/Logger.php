<?php
/**
 * Logger Utility
 * Handles application logging to files
 */

namespace App\Utils;

class Logger {
    
    /**
     * Log payment transaction
     * 
     * @param string $tx_id Transaction ID
     * @param string $action Action performed
     * @param array $data Additional data
     * @param string $ip IP address
     */
    public static function logPayment($tx_id, $action, $data = [], $ip = '') {
        if (!$ip) {
            $ip = SecurityHelper::getClientIp();
        }

        $log = sprintf(
            "[%s] TX: %s | Action: %s | IP: %s | Data: %s\n",
            date('Y-m-d H:i:s'),
            $tx_id,
            $action,
            $ip,
            json_encode($data)
        );

        error_log($log, 3, LOG_PAYMENT_FILE);
    }

    /**
     * Log webhook event
     * 
     * @param string $event_id PawaPay event ID
     * @param string $event_type Type of event
     * @param array $payload Webhook payload
     */
    public static function logWebhook($event_id, $event_type, $payload = []) {
        $log = sprintf(
            "[%s] Event: %s | Type: %s | Payload: %s\n",
            date('Y-m-d H:i:s'),
            $event_id,
            $event_type,
            json_encode($payload)
        );

        error_log($log, 3, LOG_WEBHOOK_FILE);
    }

    /**
     * Log error
     * 
     * @param string $message Error message
     * @param \Exception|null $exception Exception object
     */
    public static function logError($message, \Exception $exception = null) {
        $log = sprintf(
            "[%s] ERROR: %s\n",
            date('Y-m-d H:i:s'),
            $message
        );

        if ($exception) {
            $log .= "Exception: " . $exception->getMessage() . "\n";
            $log .= "File: " . $exception->getFile() . "\n";
            $log .= "Line: " . $exception->getLine() . "\n";
            $log .= "Trace: " . $exception->getTraceAsString() . "\n";
        }

        error_log($log, 3, LOG_ERROR_FILE);
    }

    /**
     * Log transaction state change
     * 
     * @param string $tx_id Transaction ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     */
    public static function logStatusChange($tx_id, $oldStatus, $newStatus) {
        $log = sprintf(
            "[%s] TX: %s | Status: %s -> %s\n",
            date('Y-m-d H:i:s'),
            $tx_id,
            $oldStatus,
            $newStatus
        );

        error_log($log, 3, LOG_PAYMENT_FILE);
    }
}
