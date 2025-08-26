<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $linkId = $_GET['link_id'] ?? '';
    if (empty($linkId) || !is_numeric($linkId)) {
        echo json_encode(['success' => false, 'error' => 'Invalid link ID']);
        exit;
    }

    $user = getCurrentUser();
    $pdo = getConnection();

    // Check if link exists and user has permission to view it
    $stmt = $pdo->prepare("
        SELECT l.*, u.email as user_email 
        FROM links l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();

    if (!$link) {
        echo json_encode(['success' => false, 'error' => 'Link not found']);
        exit;
    }

    // Check permissions - admin can view all, users can only view their own
    if ($user['role'] !== 'admin' && $link['user_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Get total clicks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM link_clicks WHERE link_id = ?");
    $stmt->execute([$linkId]);
    $totalClicks = $stmt->fetchColumn();

    // Get unique IPs
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ip_address) 
        FROM link_clicks 
        WHERE link_id = ?
    ");
    $stmt->execute([$linkId]);
    $uniqueIps = $stmt->fetchColumn();

    // Get today's clicks
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM link_clicks 
        WHERE link_id = ? 
        AND DATE(clicked_at) = CURDATE()
    ");
    $stmt->execute([$linkId]);
    $todayClicks = $stmt->fetchColumn();

    // Get this week's clicks
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM link_clicks 
        WHERE link_id = ? 
        AND clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$linkId]);
    $weekClicks = $stmt->fetchColumn();

    // Get recent clicks (last 10)
    $stmt = $pdo->prepare("
        SELECT 
            clicked_at,
            ip_address,
            user_agent
        FROM link_clicks 
        WHERE link_id = ? 
        ORDER BY clicked_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$linkId]);
    $clicks = $stmt->fetchAll();
    
    // Process time ago in PHP for better accuracy
    $recentClicks = [];
    foreach ($clicks as $click) {
        $clickTime = new DateTime($click['clicked_at']);
        $now = new DateTime();
        $diff = $now->diff($clickTime);
        
        if ($diff->days > 0) {
            $timeAgo = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            $timeAgo = $diff->h . ' hr' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            $timeAgo = $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            $timeAgo = 'Just now';
        }
        
        $recentClicks[] = [
            'clicked_at' => $click['clicked_at'],
            'ip_address' => $click['ip_address'],
            'user_agent' => $click['user_agent'],
            'time_ago' => $timeAgo
        ];
    }

    // Get clicks per day for last 7 days
    $stmt = $pdo->prepare("
        SELECT 
            DATE(clicked_at) as click_date,
            COUNT(*) as click_count
        FROM link_clicks 
        WHERE link_id = ? 
        AND clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(clicked_at)
        ORDER BY click_date DESC
    ");
    $stmt->execute([$linkId]);
    $dailyStats = $stmt->fetchAll();

    // Get top user agents (browsers)
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                WHEN user_agent LIKE '%Opera%' THEN 'Opera'
                ELSE 'Other'
            END as browser,
            COUNT(*) as click_count
        FROM link_clicks 
        WHERE link_id = ? 
        GROUP BY browser
        ORDER BY click_count DESC
        LIMIT 5
    ");
    $stmt->execute([$linkId]);
    $browserStats = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'link_info' => [
            'short_code' => $link['short_code'],
            'created_at' => $link['created_at'],
            'rotation_type' => $link['rotation_type'],
            'destination_count' => count(json_decode($link['destinations'], true))
        ],
        'total_clicks' => $totalClicks,
        'unique_ips' => $uniqueIps,
        'today_clicks' => $todayClicks,
        'week_clicks' => $weekClicks,
        'recent_clicks' => $recentClicks,
        'daily_stats' => $dailyStats,
        'browser_stats' => $browserStats
    ]);

} catch (Exception $e) {
    error_log("Link analytics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?> 