<?php
// Start session first to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$linkId = $input['link_id'] ?? '';

if (empty($linkId)) {
    echo json_encode(['success' => false, 'error' => 'Link ID is required']);
    exit;
}

$userId = $user['role'] === 'admin' ? null : $user['id'];
if (deleteLink($linkId, $userId)) {
    echo json_encode(['success' => true, 'message' => 'Link deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete link or link not found']);
}
?> 
 