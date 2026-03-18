<?php
/**
 * Payment Hub Admin API
 * RESTful JSON API for frontend dashboard
 * 
 * Base URL: https://pay.yourdomain.com/api
 * Authentication: Bearer JWT Token
 */

// ============================================
// BOOTSTRAP & INITIALIZATION
// ============================================

$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/app/Config/config.php';
require_once $basePath . '/app/Core/Database.php';
require_once $basePath . '/app/Utils/SecurityHelper.php';
require_once $basePath . '/app/Utils/Logger.php';
require_once $basePath . '/app/Utils/HttpHelper.php';
require_once $basePath . '/app/Modules/Admin/AdminController.php';
require_once $basePath . '/app/Modules/Payment/PaymentManager.php';
require_once $basePath . '/app/Modules/Payment/PawaPayClient.php';

use App\Utils\SecurityHelper;
use App\Utils\Logger;
use App\Modules\Admin\AdminController;
use App\Modules\Payment\PaymentManager;
use App\Core\Database;

// ============================================
// SECURITY & CORS
// ============================================

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// CORS Headers
header('Access-Control-Allow-Origin: ' . (CORS_ALLOWED_ORIGINS ?? '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enforce HTTPS
if (FORCE_HTTPS && !SecurityHelper::isHttps()) {
    http_response_code(403);
    echo json_encode(['error' => 'HTTPS required']);
    exit;
}

// Rate limiting
$clientIp = SecurityHelper::getClientIp();
if (!SecurityHelper::checkRateLimit($clientIp, RATE_LIMIT_ATTEMPTS * 2, RATE_LIMIT_WINDOW)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// ============================================
// JWT TOKEN FUNCTIONS
// ============================================

class JWTHandler {
    private static $secret = '';
    
    public static function init() {
        self::$secret = JWT_SECRET;
        if (!self::$secret) {
            throw new Exception('JWT_SECRET not configured');
        }
    }
    
    /**
     * Generate JWT token
     */
    public static function generate($adminId, $username, $expiresIn = 86400) {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        
        $payload = [
            'admin_id' => $adminId,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $expiresIn
        ];
        $payload = base64_encode(json_encode($payload));
        
        $signature = base64_encode(
            hash_hmac('sha256', "$header.$payload", self::$secret, true)
        );
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Verify and decode JWT token
     */
    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        [$header, $payload, $signature] = $parts;
        
        // Verify signature
        $expectedSignature = base64_encode(
            hash_hmac('sha256', "$header.$payload", self::$secret, true)
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }
        
        $decoded = json_decode(base64_decode($payload), true);
        
        // Check expiration
        if ($decoded['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        return $decoded;
    }
}

JWTHandler::init();

// ============================================
// MIDDLEWARE: VERIFY JWT
// ============================================

$currentUser = null;
$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'];

// Routes that don't require authentication
$publicRoutes = ['auth/login', 'health'];

if (!in_array($route, $publicRoutes)) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing or invalid authorization header']);
        exit;
    }
    
    try {
        $token = str_replace('Bearer ', '', $authHeader);
        $currentUser = JWTHandler::verify($token);
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// ROUTE HANDLER
// ============================================

try {
    $response = ['success' => false, 'error' => 'Route not found'];
    $statusCode = 404;
    
    switch (true) {
        // ============= HEALTH CHECK =============
        case $route === 'health' && $method === 'GET':
            $health = [
                'status' => 'healthy',
                'timestamp' => date('c'),
                'checks' => []
            ];
            
            // Check database
            try {
                $db = Database::getInstance();
                $db->query('SELECT 1');
                $health['checks']['database'] = true;
            } catch (Exception $e) {
                $health['checks']['database'] = false;
                $health['status'] = 'degraded';
            }
            
            // Check JWT secret
            if (getenv('JWT_SECRET')) {
                $health['checks']['jwt_secret'] = true;
            } else {
                $health['checks']['jwt_secret'] = false;
                $health['status'] = 'unhealthy';
            }
            
            // Check environment
            $health['checks']['environment'] = APP_ENV;
            
            $response = $health;
            $statusCode = ($health['status'] === 'healthy') ? 200 : 503;
            break;
        
        // ============= AUTH ROUTES =============
        case $route === 'auth/login' && $method === 'POST':
            $response = handleLogin();
            $statusCode = 200;
            break;
            
        case $route === 'auth/logout' && $method === 'POST':
            $response = handleLogout();
            $statusCode = 200;
            break;
            
        // ============= TRANSACTION ROUTES =============
        case $route === 'transactions' && $method === 'GET':
            $response = handleGetTransactions();
            $statusCode = 200;
            break;
            
        // ============= WEBSITE ROUTES =============
        case $route === 'websites' && $method === 'GET':
            $response = handleGetWebsites();
            $statusCode = 200;
            break;
            
        case $route === 'websites' && $method === 'POST':
            $response = handleCreateWebsite();
            $statusCode = 201;
            break;
            
        case preg_match('#^websites/([^/]+)$#', $route, $matches) && $method === 'PUT':
            $response = handleUpdateWebsite($matches[1]);
            $statusCode = 200;
            break;
            
        case preg_match('#^websites/([^/]+)$#', $route, $matches) && $method === 'DELETE':
            $response = handleDeleteWebsite($matches[1]);
            $statusCode = 200;
            break;
            
        // ============= DASHBOARD ROUTES =============
        case $route === 'dashboard/metrics' && $method === 'GET':
            $response = handleGetDashboardMetrics();
            $statusCode = 200;
            break;
            
        case $route === 'gateways/status' && $method === 'GET':
            $response = handleGetGatewayStatus();
            $statusCode = 200;
            break;
            
        default:
            $statusCode = 404;
            $response = ['error' => 'Route not found: ' . $route];
    }
    
    http_response_code($statusCode);
    echo json_encode($response);
    
} catch (Exception $e) {
    $errorMessage = APP_DEBUG ? $e->getMessage() : 'An error occurred';
    Logger::logError("API Error on route: $route - " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $errorMessage
    ]);
}

exit;

// ============================================
// HANDLER FUNCTIONS
// ============================================

function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * POST /api/auth/login
 */
function handleLogin() {
    $data = getJsonInput();
    
    if (empty($data['username']) || empty($data['password'])) {
        http_response_code(400);
        return ['error' => 'Username and password required'];
    }
    
    try {
        $db = Database::getInstance();
        $admin = $db->selectOne(
            "SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1",
            [$data['username']]
        );
        
        if (!$admin || !SecurityHelper::verifyPassword($data['password'], $admin['password_hash'])) {
            return ['error' => 'Invalid credentials'];
        }
        
        // Update last login
        $db->update(
            'admin_users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$admin['id']]
        );
        
        $token = JWTHandler::generate($admin['id'], $admin['username']);
        
        Logger::logPayment('ADMIN', 'api_login_successful', ['username' => $admin['username']]);
        
        return [
            'success' => true,
            'token' => $token,
            'admin' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email']
            ]
        ];
    } catch (Exception $e) {
        Logger::logError("Login Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Login failed'];
    }
}

/**
 * POST /api/auth/logout
 */
function handleLogout() {
    global $currentUser;
    Logger::logPayment('ADMIN', 'api_logout', ['username' => $currentUser['username']]);
    return ['success' => true, 'message' => 'Logged out successfully'];
}

/**
 * GET /api/transactions
 */
function handleGetTransactions() {
    global $currentUser;
    
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $site = isset($_GET['site']) ? $_GET['site'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    try {
        $db = Database::getInstance();
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            $search = '%' . $search . '%';
            $where .= " AND (tx_id LIKE ? OR order_id LIKE ?)";
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($site)) {
            $where .= " AND site = ?";
            $params[] = $site;
        }
        
        if (!empty($status)) {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        // Get total count
        $countResult = $db->selectOne("SELECT COUNT(*) as total FROM transactions WHERE $where", $params);
        $totalCount = $countResult['total'];
        
        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM transactions WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $transactions = $db->select($query, $params);
        
        return [
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $perPage)
            ]
        ];
    } catch (Exception $e) {
        Logger::logError("Get Transactions Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Failed to fetch transactions'];
    }
}

/**
 * GET /api/websites
 */
function handleGetWebsites() {
    global $currentUser;
    
    try {
        $db = Database::getInstance();
        $websites = $db->select("SELECT id, site_code, secret_key, success_url, fail_url, is_active, created_at FROM websites ORDER BY created_at DESC");
        
        return [
            'success' => true,
            'data' => $websites
        ];
    } catch (Exception $e) {
        Logger::logError("Get Websites Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Failed to fetch websites'];
    }
}

/**
 * POST /api/websites
 */
function handleCreateWebsite() {
    global $currentUser;
    
    $data = getJsonInput();
    
    // Validate required fields
    if (empty($data['site_code']) || empty($data['secret_key']) || empty($data['success_url']) || empty($data['fail_url'])) {
        http_response_code(400);
        return ['error' => 'Missing required fields: site_code, secret_key, success_url, fail_url'];
    }
    
    try {
        $db = Database::getInstance();
        
        // Check if site code already exists
        $existing = $db->selectOne("SELECT id FROM websites WHERE site_code = ? LIMIT 1", [$data['site_code']]);
        if ($existing) {
            http_response_code(409);
            return ['error' => 'Site code already exists'];
        }
        
        // Validate URLs
        if (!SecurityHelper::isValidHttpsUrl($data['success_url']) || !SecurityHelper::isValidHttpsUrl($data['fail_url'])) {
            http_response_code(400);
            return ['error' => 'Invalid URLs. Must be valid HTTPS URLs'];
        }
        
        $db->insert('websites', [
            'site_code' => $data['site_code'],
            'secret_key' => $data['secret_key'],
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'is_active' => 1
        ]);
        
        Logger::logPayment('ADMIN', 'website_created', ['site_code' => $data['site_code'], 'admin_id' => $currentUser['admin_id']]);
        
        return [
            'success' => true,
            'message' => 'Website created successfully',
            'site_code' => $data['site_code']
        ];
    } catch (Exception $e) {
        Logger::logError("Create Website Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Failed to create website'];
    }
}

/**
 * PUT /api/websites/{siteCode}
 */
function handleUpdateWebsite($siteCode) {
    global $currentUser;
    
    $data = getJsonInput();
    
    try {
        $db = Database::getInstance();
        
        $website = $db->selectOne("SELECT id FROM websites WHERE site_code = ? LIMIT 1", [$siteCode]);
        if (!$website) {
            http_response_code(404);
            return ['error' => 'Website not found'];
        }
        
        $updateData = [];
        
        if (!empty($data['secret_key'])) {
            $updateData['secret_key'] = $data['secret_key'];
        }
        if (!empty($data['success_url'])) {
            if (!SecurityHelper::isValidHttpsUrl($data['success_url'])) {
                http_response_code(400);
                return ['error' => 'Invalid success URL'];
            }
            $updateData['success_url'] = $data['success_url'];
        }
        if (!empty($data['fail_url'])) {
            if (!SecurityHelper::isValidHttpsUrl($data['fail_url'])) {
                http_response_code(400);
                return ['error' => 'Invalid fail URL'];
            }
            $updateData['fail_url'] = $data['fail_url'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }
        
        if (empty($updateData)) {
            http_response_code(400);
            return ['error' => 'No fields to update'];
        }
        
        $db->update('websites', $updateData, 'site_code = ?', [$siteCode]);
        
        Logger::logPayment('ADMIN', 'website_updated', ['site_code' => $siteCode, 'admin_id' => $currentUser['admin_id']]);
        
        return [
            'success' => true,
            'message' => 'Website updated successfully'
        ];
    } catch (Exception $e) {
        Logger::logError("Update Website Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Failed to update website'];
    }
}

/**
 * DELETE /api/websites/{siteCode}
 */
function handleDeleteWebsite($siteCode) {
    global $currentUser;
    
    try {
        $db = Database::getInstance();
        
        $website = $db->selectOne("SELECT id FROM websites WHERE site_code = ? LIMIT 1", [$siteCode]);
        if (!$website) {
            http_response_code(404);
            return ['error' => 'Website not found'];
        }
        
        $db->delete('websites', 'site_code = ?', [$siteCode]);
        
        Logger::logPayment('ADMIN', 'website_deleted', ['site_code' => $siteCode, 'admin_id' => $currentUser['admin_id']]);
        
        return [
            'success' => true,
            'message' => 'Website deleted successfully'
        ];
    } catch (Exception $e) {
        Logger::logError("Delete Website Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Failed to delete website'];
    }
}

/**
 * GET /api/dashboard/metrics
 */
function handleGetDashboardMetrics() {
    global $currentUser;
    
    try {
        $db = Database::getInstance();
        
        // Get metrics
        $totalRevenue = $db->selectOne(
            "SELECT SUM(amount) as total FROM transactions WHERE status = 'success'"
        );
        
        $totalTransactions = $db->selectOne(
            "SELECT COUNT(*) as total FROM transactions"
        );
        
        $successfulPayments = $db->selectOne(
            "SELECT COUNT(*) as total FROM transactions WHERE status = 'success'"
        );
        
        $pendingPayments = $db->selectOne(
            "SELECT COUNT(*) as total FROM transactions WHERE status = 'pending'"
        );
        
        $failedPayments = $db->selectOne(
            "SELECT COUNT(*) as total FROM transactions WHERE status = 'failed'"
        );
        
        // Revenue by day (last 7 days)
        $revenueByDay = $db->select(
            "SELECT DATE(created_at) as date, SUM(amount) as revenue FROM transactions WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC"
        );
        
        return [
            'success' => true,
            'metrics' => [
                'totalRevenue' => floatval($totalRevenue['total'] ?? 0),
                'totalTransactions' => intval($totalTransactions['total'] ?? 0),
                'successfulPayments' => intval($successfulPayments['total'] ?? 0),
                'pendingPayments' => intval($pendingPayments['total'] ?? 0),
                'failedPayments' => intval($failedPayments['total'] ?? 0)
            ],
            'revenueByDay' => $revenueByDay
        ];
    } catch (Exception $e) {
        Logger::logError("Get Dashboard Metrics Error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Failed to fetch metrics'];
    }
}

/**
 * GET /api/gateways/status
 */
function handleGetGatewayStatus() {
    global $currentUser;
    
    // This is a placeholder - you would integrate with actual gateway status checks
    return [
        'success' => true,
        'gateways' => [
            [
                'name' => 'Pawapay',
                'status' => 'active',
                'uptime' => '99.9%',
                'lastCheck' => date('Y-m-d H:i:s'),
                'responseTime' => 245
            ]
        ]
    ];
}
