<?php
/**
 * Payment Hub API Entry Point
 * Route all requests to api.php
 */

// Redirect root to API health check
if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '' || $_SERVER['REQUEST_URI'] === '/index.php') {
    header('Location: /api/health');
    exit;
}

// Include the main API router
require_once __DIR__ . '/api.php';
?>
