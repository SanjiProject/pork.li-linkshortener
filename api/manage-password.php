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

// Require login
requireLogin();
$user = getCurrentUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Get form data
$linkId = (int)($_POST['link_id'] ?? 0);
$action = $_POST['action'] ?? '';
$newPassword = trim($_POST['new_password'] ?? '');

// Validate inputs
if (empty($linkId)) {
    echo json_encode(['success' => false, 'error' => 'Link ID is required']);
    exit;
}

if (!in_array($action, ['add', 'change', 'remove'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Check if user owns this link
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM links WHERE id = ? AND user_id = ?");
$stmt->execute([$linkId, $user['id']]);
$link = $stmt->fetch();

if (!$link) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Link not found or access denied']);
    exit;
}

try {
    if ($action === 'remove') {
        // Remove password protection
        $stmt = $pdo->prepare("UPDATE links SET password = NULL WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$linkId, $user['id']]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Password protection removed successfully',
                'action' => 'remove'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove password protection']);
        }
        
    } else {
        // Add or change password
        if (empty($newPassword)) {
            echo json_encode(['success' => false, 'error' => 'Password is required']);
            exit;
        }
        
        // Validate password strength (minimum 4 characters)
        if (strlen($newPassword) < 4) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 4 characters long']);
            exit;
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE links SET password = ? WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$hashedPassword, $linkId, $user['id']]);
        
        if ($result) {
            $message = $action === 'add' ? 'Password protection added successfully' : 'Password updated successfully';
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'action' => $action
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update password']);
        }
    }
    
} catch (Exception $e) {
    error_log('Password management error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while managing password']);
}
?>
