-- Centralized Payment Hub Database Schema
-- PawaPay Integration
-- Created: March 16, 2026

-- Table 1: Websites (Dynamic Site Management)
-- Stores configuration for each CMS website using the payment hub
CREATE TABLE IF NOT EXISTS websites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_code VARCHAR(50) UNIQUE NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    success_url TEXT NOT NULL,
    fail_url TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_code (site_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: Transactions (Complete Payment Records)
-- Stores detailed information about every payment processed
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_id VARCHAR(60) UNIQUE NOT NULL,
    site VARCHAR(50) NOT NULL,
    order_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    provider_ref VARCHAR(100),
    pawapay_deposit_id VARCHAR(100),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site) REFERENCES websites(site_code) ON DELETE RESTRICT,
    UNIQUE KEY unique_provider_ref (provider_ref),
    INDEX idx_tx_id (tx_id),
    INDEX idx_site (site),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_site_order (site, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: Payment Locks (Double Payment Prevention)
-- Ensures only one payment per site + order_id combination
CREATE TABLE IF NOT EXISTS payment_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site VARCHAR(50) NOT NULL,
    order_id VARCHAR(100) NOT NULL,
    tx_id VARCHAR(60),
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site) REFERENCES websites(site_code) ON DELETE CASCADE,
    UNIQUE KEY unique_order (site, order_id),
    INDEX idx_locked_at (locked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: Webhook Events (Idempotency & Audit)
-- Tracks all webhook events from PawaPay to prevent duplicates
CREATE TABLE IF NOT EXISTS webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(100) UNIQUE NOT NULL,
    tx_id VARCHAR(60),
    event_type VARCHAR(50) NOT NULL,
    payload JSON,
    processed TINYINT(1) DEFAULT 0,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_id (event_id),
    INDEX idx_tx_id (tx_id),
    INDEX idx_processed (processed),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 5: Admin Users (Simple Authentication)
-- Stores admin dashboard credentials
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 6: Transaction Logs (Audit Trail)
-- Logs all significant operations for security & debugging
CREATE TABLE IF NOT EXISTS transaction_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_id VARCHAR(60),
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tx_id) REFERENCES transactions(tx_id) ON DELETE SET NULL,
    INDEX idx_tx_id (tx_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial seed data for admin user (password: admin123 - hash generated)
INSERT IGNORE INTO admin_users (username, password_hash, email, is_active) 
VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36ddqKFm', 'admin@pawapay.local', 1);
