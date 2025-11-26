<?php
/**
 * 数据库配置文件 - UTF-8支持
 * Student Q&A System Database Configuration
 */

// 确保输出UTF-8编码
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'uonask');
define('DB_USER', 'uonask');           // 请修改为实际的数据库用户名
define('DB_PASS', 'eEPKHG86xmfybeaP');               // 请修改为实际的数据库密码
define('DB_CHARSET', 'utf8mb4');     // 使用utf8mb4支持完整的UTF-8字符集

/**
 * 创建数据库连接
 * @return PDO|null
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // 确保连接使用UTF-8
            $pdo->exec("SET character_set_client = " . DB_CHARSET);
            $pdo->exec("SET character_set_connection = " . DB_CHARSET);
            $pdo->exec("SET character_set_results = " . DB_CHARSET);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * 测试数据库连接
 * @return array
 */
function testDatabaseConnection() {
    $result = [
        'success' => false,
        'message' => '',
        'charset' => '',
        'collation' => ''
    ];
    
    try {
        $pdo = getDbConnection();
        if ($pdo) {
            // 测试字符集设置
            $stmt = $pdo->query("SELECT @@character_set_connection, @@collation_connection");
            $charset_info = $stmt->fetch();
            
            $result['success'] = true;
            $result['message'] = '数据库连接成功！';
            $result['charset'] = $charset_info['@@character_set_connection'];
            $result['collation'] = $charset_info['@@collation_connection'];
        } else {
            $result['message'] = '无法建立数据库连接';
        }
    } catch (Exception $e) {
        $result['message'] = '连接错误: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * 执行SQL查询
 * @param string $sql
 * @param array $params
 * @return array|bool
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDbConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // 如果是SELECT查询，返回结果
        if (stripos(trim($sql), 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        
        // 对于INSERT/UPDATE/DELETE，返回true表示成功
        return true;
        
    } catch (PDOException $e) {
        error_log("SQL执行错误: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取最后插入的ID
 * @return string|false
 */
function getLastInsertId() {
    $pdo = getDbConnection();
    return $pdo ? $pdo->lastInsertId() : false;
}

/**
 * 安全地输出JSON响应
 * @param mixed $data
 * @param int $status_code
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 获取客户端IP地址
 * @return string
 */
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // 处理多个IP的情况（负载均衡）
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * 生成会话ID
 * @return string
 */
function generateSessionId() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['qa_session_id'])) {
        $_SESSION['qa_session_id'] = bin2hex(random_bytes(16));
    }
    
    return $_SESSION['qa_session_id'];
}

/**
 * 清理和验证输入数据
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * 记录调试信息
 * @param mixed $data
 * @param string $label
 */
function debugLog($data, $label = 'DEBUG') {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] $label: " . print_r($data, true) . "\n";
        error_log($message, 3, __DIR__ . '/../debug.log');
    }
}

// 设置调试模式（生产环境请设置为false）
define('DEBUG_MODE', true);

// 设置错误报告
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

?> 