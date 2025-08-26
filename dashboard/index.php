<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

$user = getCurrentUser();

// Pagination and search
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$userLinks = getUserLinks($user['id'], $page, $limit, $search);
$totalLinks = getUserLinksCount($user['id'], $search);
$totalPages = ceil($totalLinks / $limit);

// Get user stats  
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT COUNT(*) as total_links FROM links WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalLinksCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_clicks 
    FROM link_clicks lc 
    JOIN links l ON lc.link_id = l.id 
    WHERE l.user_id = ?
");
$stmt->execute([$user['id']]);
$totalClicks = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as active_links 
    FROM links 
    WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())
");
$stmt->execute([$user['id']]);
$activeLinks = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>Dashboard - Pork.li | Manage Your Short Links</title>
    <meta name="description" content="Manage your short links, view analytics, and create new rotating URLs. Access your Pork.li dashboard to track link performance.">
    <meta name="keywords" content="dashboard, link management, analytics, pork.li, track links, manage campaigns">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://pork.li/dashboard/">
    
    <!-- Additional SEO -->
    <meta name="theme-color" content="#ff1493">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <img src="../img/porkli.png" alt="Pork.li" style="height: 32px; width: auto; vertical-align: middle; margin-right: 8px;" onerror="this.style.display='none';">
                    Pork.li
                </a>
                <nav class="nav">
                    <span>Welcome, <?php echo htmlspecialchars($user['email']); ?></span>
                    <a href="../">Home</a>
                    <a href="../settings/">Settings</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="../admin/">Admin</a>
                    <?php endif; ?>
                    <a href="../api/logout.php">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="card-header">
                <h1 class="card-title">My Dashboard</h1>
                <p class="card-subtitle">Manage your short links and view analytics</p>
            </div>

            <!-- Stats -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalLinksCount; ?></div>
                    <div class="stat-label">Total Links</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $activeLinks; ?></div>
                    <div class="stat-label">Active Links</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalClicks; ?></div>
                    <div class="stat-label">Total Clicks</div>
                </div>
            </div>

            <!-- Quick Link Creator -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Create New Link</h2>
                    <p class="card-subtitle">Create a short link or add multiple URLs for rotation</p>
                </div>

                <form id="link-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Long ass URLs</label>
                        <div id="destinations-container">
                            <div class="destination-input">
                                <input type="url" name="destinations[]" class="form-input destination-url" 
                                       placeholder="https://example.com" required>
                            </div>
                        </div>
                        <a href="#" id="add-destination" class="add-destination">
                            <span>+</span> Add Another Destination (For Rotation)
                        </a>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="rotation_type">Rotation Type</label>
                        <select name="rotation_type" id="rotation_type" class="form-input form-select">
                            <option value="round_robin">Round Robin (Sequential)</option>
                            <option value="random">Random</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="custom_code">Custom Short Link (Only if you want to)</label>
                        <div class="custom-link-container">
                            <div class="custom-link-preview">
                                <span class="base-url"><?php echo getCurrentBaseUrl(); ?>/</span>
                                <input type="text" id="custom_code" name="custom_code" 
                                       class="form-input custom-code-input" 
                                       placeholder="your-custom-link" 
                                       pattern="[a-zA-Z0-9_-]+" 
                                       maxlength="50">
                            </div>
                            <small class="form-help">Leave empty for random code. Use only letters, numbers, hyphens, and underscores.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">We need to check if you’re human. Oink twice if you’re a pig.🐷</label>
                        <div class="captcha-container">
                            <div class="captcha-question">
                                <span>Solve this noob equation </span>
                                <strong id="captcha-equation">
                                    <?php
                                    // Generate server-side captcha as fallback
                                    require_once '../includes/captcha.php';
                                    $captcha = generateCaptcha();
                                    echo $captcha['equation'];
                                    ?>
                                </strong>
                                <span> = ?</span>
                            </div>
                            <div class="captcha-input-container">
                                <input type="text" id="captcha_answer" name="captcha_answer" 
                                       class="form-input captcha-input" 
                                       placeholder="Your answer" 
                                       maxlength="3" 
                                       required>
                                <button type="button" id="refresh-captcha" class="captcha-refresh" title="New question">
                                    🔄
                                </button>
                            </div>
                            <small class="form-help">Please solve the simple math problem above</small>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" data-original-text="Create Link" style="min-width: 200px;">
                            Create Link
                        </button>
                    </div>
                </form>

                <div id="link-result" class="mt-3"></div>
            </div>

            <!-- My Links -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Links</h2>
                    <p class="card-subtitle">Manage and track your rotator links</p>
                </div>

                <!-- Search Bar -->
                <div class="search-container" style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search links by short code or destination..." 
                               class="form-input" style="flex: 1;">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="?" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (empty($userLinks)): ?>
                    <div class="text-center" style="padding: 2rem;">
                        <p>You haven't created any links yet. Use the form above to create your first rotator link!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="links-table">
                            <thead>
                                <tr>
                                    <th>Short Link</th>
                                    <th>Destinations</th>
                                    <th>Type</th>
                                    <th>Clicks</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userLinks as $link): ?>
                                    <tr data-link-id="<?php echo $link['id']; ?>">
                                        <td>
                                            <span class="short-link clickable-link" 
                                                  data-url="<?php echo $link['short_url']; ?>" 
                                                  title="Click to copy link">
                                                <?php echo $link['short_code']; ?>
                                            </span>
                                            <?php 
                                            $isCustom = (strlen($link['short_code']) > 6) || 
                                                       (strpos($link['short_code'], '-') !== false) || 
                                                       (strpos($link['short_code'], '_') !== false);
                                            if ($isCustom): ?>
                                                <span class="custom-badge">Custom</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="destinations-preview" 
                                                 title="<?php echo implode(', ', $link['destinations']); ?>">
                                                <?php echo count($link['destinations']); ?> destination(s)
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge">
                                                <?php echo ucfirst(str_replace('_', ' ', $link['rotation_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $link['click_count']; ?></td>
                                        <td><?php echo timeAgo($link['created_at']); ?></td>
                                        <td>
                                            <?php if ($link['is_expired']): ?>
                                                <span class="status-badge status-expired">Expired</span>
                                            <?php else: ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-analytics" 
                                                    data-link-id="<?php echo $link['id']; ?>"
                                                    data-short-code="<?php echo $link['short_code']; ?>">
                                                Analytics
                                            </button>
                                            <button class="btn btn-sm btn-secondary edit-link" 
                                                    data-link-id="<?php echo $link['id']; ?>"
                                                    data-destinations='<?php echo json_encode($link['destinations']); ?>'
                                                    data-rotation-type="<?php echo $link['rotation_type']; ?>">
                                                Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-link" 
                                                    data-link-id="<?php echo $link['id']; ?>">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-container" style="padding: 1rem; border-top: 1px solid #e5e7eb;">
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="btn btn-sm btn-secondary">← Previous</a>
                                <?php endif; ?>

                                <span class="pagination-info">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                                    (<?php echo $totalLinks; ?> total links)
                                </span>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="btn btn-sm btn-secondary">Next →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../public/script.js"></script>
    <script>
        // Initialize dashboard analytics
        document.addEventListener('DOMContentLoaded', function() {
            // View analytics buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-analytics')) {
                    const linkId = e.target.dataset.linkId;
                    const shortCode = e.target.dataset.shortCode;
                    showAnalytics(linkId, shortCode);
                }
            });
        });

        function showAnalytics(linkId, shortCode) {
            const baseUrl = getBaseUrl();
            
            // Create analytics modal
            const modal = document.createElement('div');
            modal.className = 'edit-modal-overlay';
            modal.innerHTML = `
                <div class="edit-modal analytics-modal">
                    <div class="edit-modal-header">
                        <h3>Analytics for /${shortCode}</h3>
                        <button class="edit-modal-close">&times;</button>
                    </div>
                    <div class="edit-modal-body">
                        <div id="analytics-content">
                            <div class="loading-spinner" style="text-align: center; padding: 2rem;">
                                <div>Loading analytics...</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Close modal functionality
            const closeModal = () => document.body.removeChild(modal);
            modal.querySelector('.edit-modal-close').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            // Load analytics data
            fetch(baseUrl + '/api/link-analytics.php?link_id=' + linkId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = document.getElementById('analytics-content');
                        content.innerHTML = `
                            <div class="analytics-overview">
                                <div class="analytics-stats">
                                    <div class="stat-row">
                                        <span>Total Clicks:</span>
                                        <strong>${data.total_clicks}</strong>
                                    </div>
                                    <div class="stat-row">
                                        <span>Unique Visitors:</span>
                                        <strong>${data.unique_ips}</strong>
                                    </div>
                                    <div class="stat-row">
                                        <span>Today's Clicks:</span>
                                        <strong>${data.today_clicks}</strong>
                                    </div>
                                    <div class="stat-row">
                                        <span>This Week:</span>
                                        <strong>${data.week_clicks}</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="analytics-section">
                                <h4>Recent Activity</h4>
                                <div class="recent-clicks">
                                    ${data.recent_clicks.length > 0 ? 
                                        data.recent_clicks.map(click => `
                                            <div class="click-item">
                                                <span class="click-time">${click.time_ago}</span>
                                                <span class="click-ip">${click.ip_address}</span>
                                            </div>
                                        `).join('') :
                                        '<div class="no-data">No clicks yet</div>'
                                    }
                                </div>
                            </div>

                            ${data.browser_stats.length > 0 ? `
                                <div class="analytics-section">
                                    <h4>Popular Browsers</h4>
                                    <div class="browser-stats">
                                        ${data.browser_stats.map(browser => `
                                            <div class="browser-item">
                                                <span class="browser-name">${browser.browser}</span>
                                                <span class="browser-count">${browser.click_count} clicks</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        `;
                    } else {
                        document.getElementById('analytics-content').innerHTML = 
                            '<div class="alert alert-error">Failed to load analytics</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('analytics-content').innerHTML = 
                        '<div class="alert alert-error">Network error loading analytics</div>';
                });
        }

        function getBaseUrl() {
            let path = window.location.pathname;
            if (path.endsWith('/dashboard/') || path.endsWith('/dashboard')) {
                path = path.replace(/\/dashboard\/?$/, '');
            }
            return path || '';
        }
    </script>
</body>
</html> 