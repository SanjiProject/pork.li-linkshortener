<?php
// Start session first to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/captcha.php';

header('Content-Type: application/json');

try {
    $captcha = generateCaptcha();
    
    echo json_encode([
        'success' => true,
        'equation' => $captcha['equation']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate captcha'
    ]);
}
?>