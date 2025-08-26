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

    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Validate input
    $destinations = $_POST['destinations'] ?? [];
    $rotationType = $_POST['rotation_type'] ?? 'round_robin';
    $customCode = trim($_POST['custom_code'] ?? '');

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

    // Validate custom code if provided
    if (!empty($customCode)) {
        if (!isValidCustomCode($customCode)) {
            echo json_encode(['success' => false, 'error' => 'Invalid custom link. Use only letters, numbers, hyphens, and underscores (3-50 characters)']);
            exit;
        }

        // Check if custom code is already taken
        if (isCustomCodeTaken($customCode)) {
            echo json_encode(['success' => false, 'error' => 'Custom link already taken']);
            exit;
        }
    }

    // Create the link with admin privileges (no expiration)
    $linkId = createLink(
        $user['id'],
        $validDestinations,
        $rotationType,
        $customCode,
        null // No expiration for admin links
    );

    if ($linkId) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT short_code FROM links WHERE id = ?");
        $stmt->execute([$linkId]);
        $shortCode = $stmt->fetchColumn();

        $shortUrl = getCurrentBaseUrl() . '/' . $shortCode;

        echo json_encode([
            'success' => true,
            'short_url' => $shortUrl,
            'short_code' => $shortCode,
            'link_id' => $linkId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create link']);
    }

} catch (Exception $e) {
    error_log("Admin link creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?> 