<?php
/**
 * Admin Dashboard Controller
 * Handles all admin dashboard operations
 */

namespace App\Modules\Admin;

use App\Core\Database;
use App\Utils\SecurityHelper;
use App\Utils\Logger;

class AdminController {
    
    private $db;
    private $adminId = null;

    /**
     * Initialize Admin Controller
     */
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Check admin authentication
        session_start();
        if ($this->isAdminLoggedIn()) {
            $this->adminId = $_SESSION['admin_id'];
        }
    }

    /**
     * Check if admin is logged in
     * 
     * @return bool True if admin session exists
     */
    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }

    /**
     * Create admin session (login)
     * 
     * @param string $username Username
     * @param string $password Password
     * @return bool True if login successful
     */
    public function login($username, $password) {
        try {
            $admin = $this->db->selectOne(
                "SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1",
                [$username]
            );

            if (!$admin) {
                Logger::logError("Login attempt with invalid username: $username");
                return false;
            }

            // Verify password
            if (!SecurityHelper::verifyPassword($password, $admin['password_hash'])) {
                Logger::logError("Login attempt with wrong password for: $username");
                return false;
            }

            // Create session
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['logged_in_at'] = time();
            $_SESSION['csrf_token'] = SecurityHelper::generateCsrfToken();

            // Update last login
            $this->db->update(
                'admin_users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?',
                [$admin['id']]
            );

            Logger::logPayment('ADMIN', 'login_successful', ['username' => $username]);

            return true;

        } catch (\Exception $e) {
            Logger::logError("Login Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Destroy admin session (logout)
     */
    public function logout() {
        if (isset($_SESSION['admin_username'])) {
            Logger::logPayment('ADMIN', 'logout', ['username' => $_SESSION['admin_username']]);
        }

        session_destroy();
    }

    /**
     * Get transactions with filtering
     * 
     * @param array $filters Filter parameters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Paginated transactions
     */
    public function getTransactions($filters = [], $page = 1, $perPage = 50) {
        try {
            $where = "1=1";
            $params = [];

            // Search by TX ID or Order ID
            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $where .= " AND (tx_id LIKE ? OR order_id LIKE ?)";
                $params[] = $search;
                $params[] = $search;
            }

            // Filter by site
            if (!empty($filters['site'])) {
                $where .= " AND site = ?";
                $params[] = $filters['site'];
            }

            // Filter by status
            if (!empty($filters['status'])) {
                $where .= " AND status = ?";
                $params[] = $filters['status'];
            }

            // Date range filter
            if (!empty($filters['from_date'])) {
                $where .= " AND created_at >= ?";
                $params[] = $filters['from_date'] . ' 00:00:00';
            }
            if (!empty($filters['to_date'])) {
                $where .= " AND created_at <= ?";
                $params[] = $filters['to_date'] . ' 23:59:59';
            }

            // Calculate pagination
            $offset = ($page - 1) * $perPage;

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM transactions WHERE $where";
            $countResult = $this->db->selectOne($countQuery, $params);
            $totalCount = $countResult['total'];
            $totalPages = ceil($totalCount / $perPage);

            // Get paginated results
            $query = "SELECT * FROM transactions WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $transactions = $this->db->select($query, $params);

            return [
                'data' => $transactions,
                'total' => $totalCount,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];

        } catch (\Exception $e) {
            Logger::logError("Get Transactions Error: " . $e->getMessage());
            return ['data' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get single transaction
     * 
     * @param string $txId Transaction ID
     * @return array|null Transaction data
     */
    public function getTransaction($txId) {
        try {
            return $this->db->selectOne(
                "SELECT * FROM transactions WHERE tx_id = ? LIMIT 1",
                [$txId]
            );
        } catch (\Exception $e) {
            Logger::logError("Get Transaction Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all websites
     * 
     * @return array List of websites
     */
    public function getWebsites() {
        try {
            return $this->db->select(
                "SELECT * FROM websites ORDER BY created_at DESC"
            );
        } catch (\Exception $e) {
            Logger::logError("Get Websites Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add new website
     * 
     * @param array $data Website data
     * @return int|false Website ID or false on error
     */
    public function addWebsite($data) {
        try {
            // Validate data
            $required = ['site_code', 'secret_key', 'success_url', 'fail_url'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Missing required field: $field");
                }
            }

            // Validate URLs
            if (!SecurityHelper::isValidHttpsUrl($data['success_url'])) {
                throw new \Exception("Invalid success URL format");
            }
            if (!SecurityHelper::isValidHttpsUrl($data['fail_url'])) {
                throw new \Exception("Invalid fail URL format");
            }

            // Check if site code already exists
            $existing = $this->db->selectOne(
                "SELECT id FROM websites WHERE site_code = ? LIMIT 1",
                [$data['site_code']]
            );

            if ($existing) {
                throw new \Exception("Site code already exists: {$data['site_code']}");
            }

            // Insert website
            $id = $this->db->insert('websites', [
                'site_code' => $data['site_code'],
                'secret_key' => $data['secret_key'],
                'success_url' => $data['success_url'],
                'fail_url' => $data['fail_url'],
                'is_active' => 1
            ]);

            Logger::logPayment('ADMIN', 'website_added', ['site_code' => $data['site_code']]);

            return $id;

        } catch (\Exception $e) {
            Logger::logError("Add Website Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update website
     * 
     * @param int $id Website ID
     * @param array $data Updated data
     * @return bool True if updated
     */
    public function updateWebsite($id, $data) {
        try {
            // Validate URLs if provided
            if (isset($data['success_url']) && !SecurityHelper::isValidHttpsUrl($data['success_url'])) {
                throw new \Exception("Invalid success URL format");
            }
            if (isset($data['fail_url']) && !SecurityHelper::isValidHttpsUrl($data['fail_url'])) {
                throw new \Exception("Invalid fail URL format");
            }

            $this->db->update('websites', $data, 'id = ?', [$id]);

            Logger::logPayment('ADMIN', 'website_updated', ['website_id' => $id]);

            return true;

        } catch (\Exception $e) {
            Logger::logError("Update Website Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete website
     * 
     * @param int $id Website ID
     * @return bool True if deleted
     */
    public function deleteWebsite($id) {
        try {
            $website = $this->db->selectOne(
                "SELECT site_code FROM websites WHERE id = ?",
                [$id]
            );

            if (!$website) {
                throw new \Exception("Website not found");
            }

            $this->db->delete('websites', 'id = ?', [$id]);

            Logger::logPayment('ADMIN', 'website_deleted', ['site_code' => $website['site_code']]);

            return true;

        } catch (\Exception $e) {
            Logger::logError("Delete Website Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get dashboard statistics
     * 
     * @return array Dashboard stats
     */
    public function getStats() {
        try {
            $stats = [];

            // Total transactions
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM transactions");
            $stats['total_transactions'] = $result['count'];

            // Total amount processed
            $result = $this->db->selectOne("SELECT SUM(amount) as total FROM transactions WHERE status = 'success'");
            $stats['total_amount_processed'] = floatval($result['total'] ?? 0);

            // Successful transactions
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM transactions WHERE status = 'success'");
            $stats['successful_transactions'] = $result['count'];

            // Failed transactions
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM transactions WHERE status = 'failed'");
            $stats['failed_transactions'] = $result['count'];

            // Pending transactions
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM transactions WHERE status = 'pending'");
            $stats['pending_transactions'] = $result['count'];

            // Total websites
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM websites WHERE is_active = 1");
            $stats['total_websites'] = $result['count'];

            // Success rate
            $total = $stats['total_transactions'];
            $successful = $stats['successful_transactions'];
            $stats['success_rate'] = $total > 0 ? round(($successful / $total) * 100, 2) : 0;

            return $stats;

        } catch (\Exception $e) {
            Logger::logError("Get Stats Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export transactions to CSV
     * 
     * @param array $filters Filter parameters
     * @return string CSV content
     */
    public function exportTransactionsToCSV($filters = []) {
        try {
            // Get transactions
            $transactionResult = $this->getTransactions($filters, 1, 100000);
            $transactions = $transactionResult['data'];

            // Build CSV content
            $csv = "TX ID,Site,Order ID,Amount,Currency,Status,Provider Ref,Created At\n";

            foreach ($transactions as $tx) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $tx['tx_id'],
                    $tx['site'],
                    $tx['order_id'],
                    $tx['amount'],
                    $tx['currency'],
                    $tx['status'],
                    $tx['provider_ref'] ?? '',
                    $tx['created_at']
                );
            }

            Logger::logPayment('ADMIN', 'csv_export', ['count' => count($transactions)]);

            return $csv;

        } catch (\Exception $e) {
            Logger::logError("Export CSV Error: " . $e->getMessage());
            throw $e;
        }
    }
}
