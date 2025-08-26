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

    // Generate sitemap
    $result = generateSitemap();
    
    if ($result['success']) {
        // Get sitemap file stats
        $sitemapPath = __DIR__ . '/../sitemap.xml';
        $fileSize = file_exists($sitemapPath) ? filesize($sitemapPath) : 0;
        $content = file_exists($sitemapPath) ? file_get_contents($sitemapPath) : '';
        $urlCount = substr_count($content, '<url>');
        
        echo json_encode([
            'success' => true,
            'message' => 'Sitemap regenerated successfully',
            'stats' => [
                'file_size' => $fileSize,
                'url_count' => $urlCount,
                'generated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Sitemap regeneration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}
?>
