<?php
// Sitemap.php - Generate XML sitemap for Link Rotator
// Ensure no output before XML declaration

// Start output buffering to catch any unwanted output
ob_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Clean any potential output from includes
ob_end_clean();

// Set XML headers only if headers haven't been sent
if (!headers_sent()) {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}

// Generate sitemap data first
$urls = [];

// Add static pages
$baseUrl = getCurrentBaseUrl();
$urls[] = [
    'loc' => $baseUrl . '/',
    'lastmod' => date('Y-m-d\TH:i:s+00:00'),
    'changefreq' => 'daily',
    'priority' => '1.0'
];

$urls[] = [
    'loc' => $baseUrl . '/login',
    'lastmod' => date('Y-m-d\TH:i:s+00:00'),
    'changefreq' => 'monthly',
    'priority' => '0.6'
];

$urls[] = [
    'loc' => $baseUrl . '/register',
    'lastmod' => date('Y-m-d\TH:i:s+00:00'),
    'changefreq' => 'monthly',
    'priority' => '0.6'
];

// Add dynamic links from database
try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT l.short_code, l.created_at, l.updated_at
        FROM links l 
        LEFT JOIN users u ON l.user_id = u.id
        WHERE (l.expires_at IS NULL OR l.expires_at > NOW())
        AND (l.user_id IS NULL OR u.allow_public_indexing = 1)
        ORDER BY l.updated_at DESC
        LIMIT 1000
    ");
    
    $stmt->execute();
    $links = $stmt->fetchAll();
    
    foreach ($links as $link) {
        $lastmod = !empty($link['updated_at']) ? $link['updated_at'] : $link['created_at'];
        $urls[] = [
            'loc' => $baseUrl . '/' . $link['short_code'],
            'lastmod' => date('Y-m-d\TH:i:s+00:00', strtotime($lastmod)),
            'changefreq' => 'weekly',
            'priority' => '0.4'
        ];
    }
    
} catch (Exception $e) {
    // Log error but continue with static pages
    error_log("Sitemap error: " . $e->getMessage());
}

// Now output the XML - ensure this is the first output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $url) {
    echo '    <url>' . "\n";
    echo '        <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
    echo '        <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
    echo '        <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
    echo '        <priority>' . $url['priority'] . '</priority>' . "\n";
    echo '    </url>' . "\n";
}

echo '</urlset>' . "\n";
?> 