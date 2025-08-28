<?php
require_once __DIR__ . '/../config/database.php';

// Generate unique short code
function generateShortCode($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $pdo = getConnection();
    
    do {
        $shortCode = '';
        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM links WHERE short_code = ?");
        $stmt->execute([$shortCode]);
        $exists = $stmt->fetchColumn() > 0;
        
    } while ($exists);
    
    return $shortCode;
}

// Validate custom short code
function validateCustomCode($code) {
    // Must be 3-50 characters, only letters, numbers, hyphens, underscores
    if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $code)) {
        return false;
    }
    
    // Reserved codes that cannot be used
    $reserved = ['api', 'admin', 'dashboard', 'login', 'register', 'logout', 'public', 'includes', 'config', 'www', 'mail', 'ftp', 'test'];
    
    if (in_array(strtolower($code), $reserved)) {
        return false;
    }
    
    return true;
}

// Check if short code is available
function isShortCodeAvailable($code) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM links WHERE short_code = ?");
    $stmt->execute([$code]);
    return $stmt->fetchColumn() == 0;
}

// Create new link
function createLink($destinations, $userId = null, $rotationType = 'round_robin', $expiresIn = null, $customCode = null) {
    $pdo = getConnection();
    
    // Check user link limit (100 links maximum per user)
    if ($userId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM links WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userLinkCount = $stmt->fetchColumn();
        
        if ($userLinkCount >= 100) {
            return ['success' => false, 'error' => 'Maximum 100 links allowed per user. Please delete some links before creating new ones.'];
        }
    }
    
    // Validate destinations
    $destinationList = [];
    foreach ($destinations as $dest) {
        $dest = trim($dest);
        if (!empty($dest)) {
            if (!validateUrl($dest)) {
                return ['success' => false, 'error' => 'Invalid URL: ' . $dest];
            }
            $destinationList[] = $dest;
        }
    }
    
    if (empty($destinationList)) {
        return ['success' => false, 'error' => 'At least one valid destination URL is required'];
    }
    
    if (count($destinationList) > 20) {
        return ['success' => false, 'error' => 'Maximum 20 destination URLs allowed'];
    }
    
    // Handle custom short code
    $shortCode = '';
    if (!empty($customCode)) {
        // Only allow custom codes for logged-in users
        if (!$userId) {
            return ['success' => false, 'error' => 'Custom links are only available for registered users'];
        }
        
        $customCode = trim($customCode);
        if (!validateCustomCode($customCode)) {
            return ['success' => false, 'error' => 'Invalid custom code. Use 3-50 characters: letters, numbers, hyphens, and underscores only'];
        }
        
        if (!isShortCodeAvailable($customCode)) {
            return ['success' => false, 'error' => 'This custom link is already taken. Please choose another one'];
        }
        
        $shortCode = $customCode;
    } else {
        $shortCode = generateShortCode();
    }
    
    $destinationsJson = json_encode($destinationList);
    $expiresAt = null;
    
    if ($expiresIn) {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
    }
    
    $stmt = $pdo->prepare("INSERT INTO links (user_id, short_code, destinations, rotation_type, expires_at) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$userId, $shortCode, $destinationsJson, $rotationType, $expiresAt])) {
        // Update sitemap automatically
        updateSitemap();
        
        return [
            'success' => true, 
            'short_code' => $shortCode,
            'short_url' => getBaseUrl() . '/' . $shortCode,
            'is_custom' => !empty($customCode)
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to create link'];
}

// Get link by short code
function getLinkByShortCode($shortCode) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$shortCode]);
    return $stmt->fetch();
}

// Get next destination URL for rotation
function getNextDestination($linkId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();
    
    if (!$link) return null;
    
    $destinations = json_decode($link['destinations'], true);
    if (empty($destinations)) return null;
    
    $nextUrl = '';
    $newIndex = 0;
    
    if ($link['rotation_type'] === 'random') {
        $nextUrl = $destinations[array_rand($destinations)];
    } else {
        // Round robin
        $currentIndex = $link['current_index'];
        $nextUrl = $destinations[$currentIndex];
        $newIndex = ($currentIndex + 1) % count($destinations);
        
        // Update current index
        $stmt = $pdo->prepare("UPDATE links SET current_index = ? WHERE id = ?");
        $stmt->execute([$newIndex, $linkId]);
    }
    
    return $nextUrl;
}

// Log click
function logClick($linkId, $ipAddress = null, $userAgent = null) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO link_clicks (link_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$linkId, $ipAddress, $userAgent]);
}

// Get user links with search and pagination
function getUserLinks($userId, $page = 1, $limit = 10, $search = '') {
    $pdo = getConnection();
    $limit = (int) $limit;
    $offset = (int) (($page - 1) * $limit);
    
    $whereClause = "WHERE l.user_id = ?";
    $params = [$userId];
    
    if (!empty($search)) {
        $whereClause .= " AND (l.short_code LIKE ? OR l.destinations LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $stmt = $pdo->prepare("
        SELECT l.*, COUNT(lc.id) as click_count, (l.password IS NOT NULL AND l.password != '') as has_password 
        FROM links l 
        LEFT JOIN link_clicks lc ON l.id = lc.link_id 
        $whereClause
        GROUP BY l.id 
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $links = $stmt->fetchAll();
    
    // Process links data
    foreach ($links as &$link) {
        $link['destinations'] = json_decode($link['destinations'], true);
        $link['is_expired'] = $link['expires_at'] && strtotime($link['expires_at']) < time();
        $link['short_url'] = getBaseUrl() . '/' . $link['short_code'];
    }
    
    return $links;
}

// Get user links count for pagination
function getUserLinksCount($userId, $search = '') {
    $pdo = getConnection();
    
    $whereClause = "WHERE user_id = ?";
    $params = [$userId];
    
    if (!empty($search)) {
        $whereClause .= " AND (short_code LIKE ? OR destinations LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM links $whereClause");
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Get all links (admin) with search and pagination
function getAllLinks($page = 1, $limit = 10, $search = '') {
    $pdo = getConnection();
    $limit = (int) $limit;
    $offset = (int) (($page - 1) * $limit);
    
    $whereClause = "";
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE (l.short_code LIKE ? OR l.destinations LIKE ? OR u.email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    $stmt = $pdo->prepare("
        SELECT l.*, u.email as user_email, COUNT(lc.id) as click_count 
        FROM links l 
        LEFT JOIN users u ON l.user_id = u.id 
        LEFT JOIN link_clicks lc ON l.id = lc.link_id 
        $whereClause
        GROUP BY l.id 
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $links = $stmt->fetchAll();
    
    // Process links data
    foreach ($links as &$link) {
        $link['destinations'] = json_decode($link['destinations'], true);
        $link['is_expired'] = $link['expires_at'] && strtotime($link['expires_at']) < time();
        $link['short_url'] = getBaseUrl() . '/' . $link['short_code'];
        $link['user_type'] = $link['user_id'] ? 'Registered' : 'Guest';
    }
    
    return $links;
}

// Get all links count for pagination (admin)
function getAllLinksCount($search = '') {
    $pdo = getConnection();
    
    $whereClause = "";
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE (l.short_code LIKE ? OR l.destinations LIKE ? OR u.email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT l.id) 
        FROM links l 
        LEFT JOIN users u ON l.user_id = u.id 
        $whereClause
    ");
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Delete link
function deleteLink($linkId, $userId = null) {
    $pdo = getConnection();
    
    $result = false;
    if ($userId) {
        $stmt = $pdo->prepare("DELETE FROM links WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$linkId, $userId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
        $result = $stmt->execute([$linkId]);
    }
    
    // Update sitemap automatically if deletion was successful
    if ($result) {
        updateSitemap();
    }
    
    return $result;
}

// Update link
function updateLink($linkId, $destinations, $rotationType, $userId = null) {
    $pdo = getConnection();
    
    // Validate destinations
    $destinationList = [];
    foreach ($destinations as $dest) {
        $dest = trim($dest);
        if (!empty($dest)) {
            if (!validateUrl($dest)) {
                return ['success' => false, 'error' => 'Invalid URL: ' . $dest];
            }
            $destinationList[] = $dest;
        }
    }
    
    if (empty($destinationList)) {
        return ['success' => false, 'error' => 'At least one valid destination URL is required'];
    }
    
    $destinationsJson = json_encode($destinationList);
    
    if ($userId) {
        $stmt = $pdo->prepare("UPDATE links SET destinations = ?, rotation_type = ?, current_index = 0 WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$destinationsJson, $rotationType, $linkId, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE links SET destinations = ?, rotation_type = ?, current_index = 0 WHERE id = ?");
        $result = $stmt->execute([$destinationsJson, $rotationType, $linkId]);
    }
    
    // Update sitemap automatically if update was successful
    if ($result) {
        updateSitemap();
    }
    
    return ['success' => $result];
}

// Get base URL with domain compatibility
function getBaseUrl() {
    // ALWAYS return production domain for pork.li - this overrides everything else
    // Check multiple indicators that we're working with pork.li
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    
    // Primary check: if HTTP_HOST or SERVER_NAME is pork.li
    if ($host === 'pork.li' || $serverName === 'pork.li') {
        return 'https://pork.li';
    }
    
    // Secondary check: if we're in CLI mode or HOST is not set, assume production
    if (!isset($_SERVER['HTTP_HOST']) || empty($host)) {
        return 'https://pork.li';
    }
    
    // Third check: look for pork.li indicators in paths or URIs
    if (strpos($requestUri, '/pork.li') !== false || 
        strpos($scriptName, '/pork.li') !== false || 
        strpos($documentRoot, '/pork.li') !== false) {
        return 'https://pork.li';
    }
    
    // Fourth check: if the host contains 'pork' (subdomain scenarios)
    if (strpos($host, 'pork') !== false) {
        return 'https://pork.li';
    }
    
    // For true local development environments only
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    
    // Handle XAMPP subdirectory installations
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = rtrim(dirname($scriptPath), '/');
    
    // Remove common XAMPP patterns
    $path = str_replace('/htdocs', '', $path);
    
    // Handle case where we're in a subdirectory like /link-rotator/
    if (basename($path) === 'api' || basename($path) === 'dashboard' || basename($path) === 'admin' || basename($path) === 'settings') {
        $path = dirname($path);
    }
    
    // Ensure no double slashes and proper formatting
    $path = '/' . trim($path, '/');
    if ($path === '/') $path = '';
    
    return $protocol . '://' . $host . $path;
}

// Get current base URL (alias for getBaseUrl)
function getCurrentBaseUrl() {
    return getBaseUrl();
}

// Format time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

// Get link stats
function getLinkStats($linkId) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM link_clicks WHERE link_id = ?");
    $stmt->execute([$linkId]);
    $totalClicks = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT DATE(clicked_at) as date, COUNT(*) as clicks 
        FROM link_clicks 
        WHERE link_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(clicked_at) 
        ORDER BY date DESC
    ");
    $stmt->execute([$linkId]);
    $dailyClicks = $stmt->fetchAll();
    
    return [
        'total_clicks' => $totalClicks,
        'daily_clicks' => $dailyClicks
    ];
}

// Get all users with search and pagination (admin)
function getAllUsers($page = 1, $limit = 10, $search = '') {
    $pdo = getConnection();
    $limit = (int) $limit;
    $offset = (int) (($page - 1) * $limit);
    
    $whereClause = "";
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE (u.email LIKE ? OR u.role LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm];
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT l.id) as total_links,
               COUNT(DISTINCT lc.id) as total_clicks,
               'Unknown' as register_location
        FROM users u 
        LEFT JOIN links l ON u.id = l.user_id 
        LEFT JOIN link_clicks lc ON l.id = lc.link_id 
        $whereClause
        GROUP BY u.id 
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get all users count for pagination (admin)
function getAllUsersCount($search = '') {
    $pdo = getConnection();
    
    $whereClause = "";
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE (email LIKE ? OR role LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm];
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Generate sitemap.xml automatically
function generateSitemap() {
    try {
        $pdo = getConnection();
        $baseUrl = getBaseUrl();
        
        // Get all active links (not expired)
        $stmt = $pdo->prepare("
            SELECT short_code, created_at, updated_at 
            FROM links 
            WHERE expires_at IS NULL OR expires_at > NOW()
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $links = $stmt->fetchAll();
        
        // Start building sitemap XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add homepage
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($baseUrl . '/') . '</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";
        
        // Add all short links
        foreach ($links as $link) {
            $lastmod = $link['updated_at'] ? $link['updated_at'] : $link['created_at'];
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($baseUrl . '/' . $link['short_code']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s+00:00', strtotime($lastmod)) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.6</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        // Write sitemap to file
        $sitemapPath = __DIR__ . '/../sitemap.xml';
        if (file_put_contents($sitemapPath, $xml) !== false) {
            return ['success' => true, 'message' => 'Sitemap generated successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to write sitemap file'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error generating sitemap: ' . $e->getMessage()];
    }
}

// Trigger sitemap regeneration (called after link operations)
function updateSitemap() {
    // Run sitemap generation in background to avoid slowing down the user experience
    try {
        return generateSitemap();
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log('Sitemap update failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Check if a link is password protected
function isLinkPasswordProtected($shortCode) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT password FROM links WHERE short_code = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$shortCode]);
    $result = $stmt->fetch();
    
    return $result && !empty($result['password']);
}

// Verify password for a link
function verifyLinkPassword($shortCode, $password) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT password FROM links WHERE short_code = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$shortCode]);
    $result = $stmt->fetch();
    
    if (!$result || empty($result['password'])) {
        return false;
    }
    
    return password_verify($password, $result['password']);
}

// Get link with password info (for display purposes)
function getLinkWithPasswordInfo($shortCode) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT *, (password IS NOT NULL AND password != '') as has_password FROM links WHERE short_code = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$shortCode]);
    return $stmt->fetch();
}

