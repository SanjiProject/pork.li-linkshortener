<?php
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
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Get current user
$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First, delete all click data for user's links
    $stmt = $pdo->prepare("
        DELETE lc FROM link_clicks lc 
        JOIN links l ON lc.link_id = l.id 
        WHERE l.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Then delete all user's links
    $stmt = $pdo->prepare("DELETE FROM links WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    $deletedLinks = $stmt->rowCount();
    
    // Commit transaction
    $pdo->commit();
    
    // Update sitemap automatically if links were deleted
    if ($deletedLinks > 0) {
        updateSitemap();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully deleted $deletedLinks links and all associated data"
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode(['success' => false, 'error' => 'Failed to delete links']);
}
?> 