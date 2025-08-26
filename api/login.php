<?php
// Start session first to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!checkRateLimit('login', 5, 900)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many login attempts. Please try again later.']);
    exit;
}

$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (loginUser($email, $password)) {
    $user = getCurrentUser();
    $redirect = '/dashboard/';
    
    if ($user['role'] === 'admin') {
        $redirect = '/admin/';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'redirect' => $redirect
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
}
?> 