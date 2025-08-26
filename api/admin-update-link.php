<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    $user = getCurrentUser();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Validate input
    $linkId = $_POST['link_id'] ?? '';
    $destinations = $_POST['destinations'] ?? [];
    $rotationType = $_POST['rotation_type'] ?? 'round_robin';

    if (empty($linkId) || !is_numeric($linkId)) {
        echo json_encode(['success' => false, 'error' => 'Invalid link ID']);
        exit;
    }

    // Validate destinations
    if (empty($destinations) || !is_array($destinations)) {
        echo json_encode(['success' => false, 'error' => 'At least one destination URL is required']);
        exit;
    }

    // Filter and validate URLs
    $validDestinations = [];
    foreach ($destinations as $url) {
        $url = trim($url);
        if (!empty($url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid URL: ' . htmlspecialchars($url)]);
                exit;
            }
            $validDestinations[] = $url;
        }
    }

    if (empty($validDestinations)) {
        echo json_encode(['success' => false, 'error' => 'At least one valid destination URL is required']);
        exit;
    }

    if (count($validDestinations) > 20) {
        echo json_encode(['success' => false, 'error' => 'Maximum 20 destination URLs allowed']);
        exit;
    }

    // Validate rotation type
    if (!in_array($rotationType, ['round_robin', 'random'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid rotation type']);
        exit;
    }

    $pdo = getConnection();

    // Check if link exists (admin can edit any link)
    $stmt = $pdo->prepare("SELECT id, short_code FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();

    if (!$link) {
        echo json_encode(['success' => false, 'error' => 'Link not found']);
        exit;
    }

    // Update the link - admin can edit any link
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            UPDATE links 
            SET destinations = ?, 
                rotation_type = ?, 
                current_index = 0,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            json_encode($validDestinations),
            $rotationType,
            $linkId
        ]);

        if ($result) {
            $pdo->commit();
            
            // Update sitemap automatically
            updateSitemap();
            
            echo json_encode([
                'success' => true,
                'message' => 'Link updated successfully',
                'link_id' => $linkId,
                'short_code' => $link['short_code']
            ]);
        } else {
            $pdo->rollback();
            echo json_encode(['success' => false, 'error' => 'Failed to update link']);
        }

    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Admin link update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }

} catch (Exception $e) {
    error_log("Admin link update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?> 