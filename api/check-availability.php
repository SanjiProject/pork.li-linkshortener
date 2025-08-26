<?php
// Start session first to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get the code to check
$code = trim($_POST['code'] ?? '');

if (empty($code)) {
    echo json_encode(['available' => false, 'error' => 'Code is required']);
    exit;
}

// Validate the custom code format
if (!validateCustomCode($code)) {
    echo json_encode(['available' => false, 'error' => 'Invalid custom code format']);
    exit;
}

// Check if code is available
$available = isShortCodeAvailable($code);

echo json_encode(['available' => $available]);
?> 