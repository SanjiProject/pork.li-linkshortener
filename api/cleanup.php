<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cleanupExpiredLinks();
    echo json_encode(['success' => true, 'message' => 'Cleanup completed']);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?> 