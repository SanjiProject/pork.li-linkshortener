<?php
// Start session first to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/captcha.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Rate limiting
if (!checkRateLimit('create_link', 30, 3600)) {
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

// Verify captcha
$captchaAnswer = $_POST['captcha_answer'] ?? '';
if (!verifyCaptcha($captchaAnswer)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid captcha answer. Please try again.']);
    exit;
}

// Get form data
$destinations = $_POST['destinations'] ?? [];
$rotationType = sanitizeInput($_POST['rotation_type'] ?? 'round_robin');
$customCode = trim($_POST['custom_code'] ?? '');

// Validate inputs
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
        $cleanDestinations[] = $dest;
    }
}

if (empty($cleanDestinations)) {
    echo json_encode(['success' => false, 'error' => 'At least one valid destination URL is required']);
    exit;
}

// Get current user
$user = getCurrentUser();
$userId = $user ? $user['id'] : null;

// Set expiration for guest users (7 days)
$expiresIn = $userId ? null : (7 * 24 * 60 * 60); // 7 days in seconds

// Clean custom code
if (empty($customCode)) {
    $customCode = null;
}

// Create the link
$result = createLink($cleanDestinations, $userId, $rotationType, $expiresIn, $customCode);

// Return response
echo json_encode($result);
?> 