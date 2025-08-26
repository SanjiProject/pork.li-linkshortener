<?php
// Start session first to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!checkRateLimit('register', 3, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many registration attempts. Please try again later.']);
    exit;
}

$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$captchaAnswer = $_POST['captcha_answer'] ?? '';

if (empty($email) || empty($password) || empty($captchaAnswer)) {
    echo json_encode(['success' => false, 'error' => 'All fields including captcha are required']);
    exit;
}

// Verify captcha
if (!verifyCaptcha($captchaAnswer)) {
    echo json_encode(['success' => false, 'error' => 'Invalid captcha answer. Please try again.']);
    exit;
}

if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
    exit;
}

if (registerUser($email, $password)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful! You can now log in.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Email already exists or registration failed']);
}
?> 