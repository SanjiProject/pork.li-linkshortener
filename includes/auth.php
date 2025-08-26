<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Login user
function loginUser($email, $password) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Register new user
function registerUser($email, $password) {
    $pdo = getConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        return false;
    }
    
    // Create new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    return $stmt->execute([$email, $hashedPassword]);
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: /');
    exit;
}

// Require login
function requireLogin($redirectTo = '/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

// Require admin
function requireAdmin($redirectTo = '/') {
    if (!isAdmin()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

// Sanitize input
function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate URL
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting (simple implementation)
function checkRateLimit($action, $limit = 10, $timeWindow = 3600) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'reset' => $now + $timeWindow];
        return true;
    }
    
    if ($now > $_SESSION['rate_limit'][$key]['reset']) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'reset' => $now + $timeWindow];
        return true;
    }
    
    if ($_SESSION['rate_limit'][$key]['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
?> 