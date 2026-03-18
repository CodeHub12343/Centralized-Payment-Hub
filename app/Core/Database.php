<?php
/**
 * Database Connection Class
 * Handles PDO connections with prepared statements
 */

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * Get singleton instance of Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish PDO connection
     */
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );

            // Log successful connection
            error_log("[" . date('Y-m-d H:i:s') . "] Database connection established", 3, LOG_ERROR_FILE);

        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            die(APP_DEBUG ? "Database Error: " . $e->getMessage() : "Database connection failed");
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute SELECT query with parameters
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Select Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            throw $e;
        }
    }

    /**
     * Execute SELECT query and return first row
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SelectOne Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            throw $e;
        }
    }

    /**
     * Execute INSERT query
     */
    public function insert($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array_values($data));
            
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Insert Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            throw $e;
        }
    }

    /**
     * Execute UPDATE query
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
            $query = "UPDATE $table SET $set WHERE $where";
            
            $params = array_merge(array_values($data), $whereParams);
            $stmt = $this->connection->prepare($query);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            throw $e;
        }
    }

    /**
     * Execute DELETE query
     */
    public function delete($table, $where, $params = []) {
        try {
            $query = "DELETE FROM $table WHERE $where";
            $stmt = $this->connection->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            throw $e;
        }
    }

    /**
     * Execute raw query with parameters (for complex queries)
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage(), 3, LOG_ERROR_FILE);
            throw $e;
        }
    }

    /**
     * Start transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $query = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
        $result = $this->selectOne($query, [$table]);
        return $result !== false;
    }

    /**
     * Count rows in table
     */
    public function count($table, $where = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM $table";
        if ($where) {
            $query .= " WHERE $where";
        }
        
        $result = $this->selectOne($query, $params);
        return $result['count'] ?? 0;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
