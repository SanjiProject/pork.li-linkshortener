<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Rate limiting
if (!checkRateLimit('password_change', 5, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please try again later.']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Get form data
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

// Validate inputs
if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long']);
    exit;
}

// Get current user
$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Verify current password
if (!password_verify($currentPassword, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit;
}

// Hash new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password in database
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt->execute([$hashedPassword, $user['id']])) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update password']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?> 