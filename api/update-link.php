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
if (!isLoggedIn()) {
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
$linkId = (int) ($_POST['link_id'] ?? 0);
$destinations = $_POST['destinations'] ?? [];
$rotationType = sanitizeInput($_POST['rotation_type'] ?? 'round_robin');

// Validate inputs
if (empty($linkId)) {
    echo json_encode(['success' => false, 'error' => 'Link ID is required']);
    exit;
}

if (empty($destinations)) {
    echo json_encode(['success' => false, 'error' => 'At least one destination URL is required']);
    exit;
}

if (!in_array($rotationType, ['round_robin', 'random'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid rotation type']);
    exit;
}

// Clean and validate destinations
$cleanDestinations = [];
foreach ($destinations as $dest) {
    $dest = trim($dest);
    if (!empty($dest)) {
        if (!validateUrl($dest)) {
            echo json_encode(['success' => false, 'error' => 'Invalid URL: ' . $dest]);
            exit;
        }
        $cleanDestinations[] = $dest;
    }
}

if (empty($cleanDestinations)) {
    echo json_encode(['success' => false, 'error' => 'At least one valid destination URL is required']);
    exit;
}

if (count($cleanDestinations) > 20) {
    echo json_encode(['success' => false, 'error' => 'Maximum 20 destination URLs allowed']);
    exit;
}

// Get current user
$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Verify user owns this link
    $stmt = $pdo->prepare("SELECT id FROM links WHERE id = ? AND user_id = ?");
    $stmt->execute([$linkId, $user['id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Link not found or access denied']);
        exit;
    }
    
    // Update the link
    $destinationsJson = json_encode($cleanDestinations);
    $stmt = $pdo->prepare("
        UPDATE links 
        SET destinations = ?, rotation_type = ?, current_index = 0, updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$destinationsJson, $rotationType, $linkId, $user['id']])) {
        // Update sitemap automatically
        updateSitemap();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Link updated successfully',
            'destinations_count' => count($cleanDestinations)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update link']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?> 