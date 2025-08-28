<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

$user = getCurrentUser();

// Pagination and search for links
$linksPage = isset($_GET['links_page']) ? max(1, (int)$_GET['links_page']) : 1;
$linksSearch = isset($_GET['links_search']) ? trim($_GET['links_search']) : '';
$linksLimit = 10;

$allLinks = getAllLinks($linksPage, $linksLimit, $linksSearch);
$totalLinksCount = getAllLinksCount($linksSearch);
$totalLinksPages = ceil($totalLinksCount / $linksLimit);

// Pagination and search for users
$usersPage = isset($_GET['users_page']) ? max(1, (int)$_GET['users_page']) : 1;
$usersSearch = isset($_GET['users_search']) ? trim($_GET['users_search']) : '';
$usersLimit = 10;

$allUsers = getAllUsers($usersPage, $usersLimit, $usersSearch);
$totalUsersCount = getAllUsersCount($usersSearch);
$totalUsersPages = ceil($totalUsersCount / $usersLimit);

// Get system stats
$pdo = getConnection();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM links");
$stmt->execute();
$totalLinks = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM link_clicks");
$stmt->execute();
$totalClicks = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM links WHERE expires_at IS NULL OR expires_at > NOW()");
$stmt->execute();
$activeLinks = $stmt->fetchColumn();

// Get custom links count
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM links 
    WHERE LENGTH(short_code) > 6 OR 
          short_code LIKE '%-%' OR 
          short_code LIKE '%_%'
");
$stmt->execute();
$customLinks = $stmt->fetchColumn();

// Get recent click activity (last 24 hours)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM link_clicks 
    WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$recentClicks = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>Admin Panel - Pork.li | System Management</title>
    <meta name="description" content="Admin panel for Pork.li system management. Monitor users, links, and analytics.">
    <meta name="keywords" content="admin panel, system management, pork.li admin, user management, analytics">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://pork.li/admin/">
    
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
                    <span>Admin: <?php echo htmlspecialchars($user['email']); ?></span>
                    <a href="../">Home</a>
                    <a href="../dashboard/">Dashboard</a>
                    <a href="../api/logout.php">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="card-header">
                <h1 class="card-title">Admin Panel</h1>
                <p class="card-subtitle">Manage users, links, and system statistics</p>
            </div>

            <!-- System Stats -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalLinks; ?></div>
                    <div class="stat-label">Total Links</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $customLinks; ?></div>
                    <div class="stat-label">Custom Links</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $activeLinks; ?></div>
                    <div class="stat-label">Active Links</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalClicks; ?></div>
                    <div class="stat-label">Total Clicks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $recentClicks; ?></div>
                    <div class="stat-label">Clicks Today</div>
                </div>
            </div>

            <!-- Users Management -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Users Management</h2>
                    <p class="card-subtitle">View and manage all registered users</p>
                </div>

                <!-- Users Search Bar -->
                <div class="search-container" style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <?php if (!empty($linksSearch) || $linksPage > 1): ?>
                            <input type="hidden" name="links_search" value="<?php echo htmlspecialchars($linksSearch); ?>">
                            <input type="hidden" name="links_page" value="<?php echo $linksPage; ?>">
                        <?php endif; ?>
                        <input type="text" name="users_search" value="<?php echo htmlspecialchars($usersSearch); ?>" 
                               placeholder="Search users by email or role..." 
                               class="form-input" style="flex: 1;">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($usersSearch)): ?>
                            <a href="?<?php echo !empty($linksSearch) || $linksPage > 1 ? 'links_search=' . urlencode($linksSearch) . '&links_page=' . $linksPage : ''; ?>" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="links-table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Links</th>
                                <th>Clicks</th>
                                <th>Location</th>
                                <th>Registered</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $u['role'] === 'admin' ? 'status-expired' : 'status-active'; ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $u['total_links']; ?></td>
                                    <td><?php echo $u['total_clicks']; ?></td>
                                    <td><?php echo $u['register_location']; ?></td>
                                    <td><?php echo timeAgo($u['created_at']); ?></td>
                                    <td><?php echo timeAgo($u['updated_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Users Pagination -->
                <?php if ($totalUsersPages > 1): ?>
                    <div class="pagination-container" style="padding: 1rem; border-top: 1px solid #e5e7eb;">
                        <div class="pagination">
                            <?php if ($usersPage > 1): ?>
                                <a href="?users_page=<?php echo $usersPage - 1; ?><?php echo !empty($usersSearch) ? '&users_search=' . urlencode($usersSearch) : ''; ?><?php echo !empty($linksSearch) || $linksPage > 1 ? '&links_search=' . urlencode($linksSearch) . '&links_page=' . $linksPage : ''; ?>" 
                                   class="btn btn-sm btn-secondary">← Previous</a>
                            <?php endif; ?>

                            <span class="pagination-info">
                                Page <?php echo $usersPage; ?> of <?php echo $totalUsersPages; ?> 
                                (<?php echo $totalUsersCount; ?> total users)
                            </span>

                            <?php if ($usersPage < $totalUsersPages): ?>
                                <a href="?users_page=<?php echo $usersPage + 1; ?><?php echo !empty($usersSearch) ? '&users_search=' . urlencode($usersSearch) : ''; ?><?php echo !empty($linksSearch) || $linksPage > 1 ? '&links_search=' . urlencode($linksSearch) . '&links_page=' . $linksPage : ''; ?>" 
                                   class="btn btn-sm btn-secondary">Next →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- All Links Management -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Links</h2>
                    <p class="card-subtitle">View and manage all links in the system</p>
                </div>

                <!-- Links Search Bar -->
                <div class="search-container" style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <?php if (!empty($usersSearch) || $usersPage > 1): ?>
                            <input type="hidden" name="users_search" value="<?php echo htmlspecialchars($usersSearch); ?>">
                            <input type="hidden" name="users_page" value="<?php echo $usersPage; ?>">
                        <?php endif; ?>
                        <input type="text" name="links_search" value="<?php echo htmlspecialchars($linksSearch); ?>" 
                               placeholder="Search links by short code, destination, or user email..." 
                               class="form-input" style="flex: 1;">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($linksSearch)): ?>
                            <a href="?<?php echo !empty($usersSearch) || $usersPage > 1 ? 'users_search=' . urlencode($usersSearch) . '&users_page=' . $usersPage : ''; ?>" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (empty($allLinks)): ?>
                    <div class="text-center" style="padding: 2rem;">
                        <p>No links found in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="links-table">
                            <thead>
                                <tr>
                                    <th>Short Link</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Destinations</th>
                                    <th>Clicks</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allLinks as $link): ?>
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
                                            <?php if ($link['user_email']): ?>
                                                <?php echo htmlspecialchars($link['user_email']); ?>
                                            <?php else: ?>
                                                <span class="text-secondary">Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge">
                                                <?php echo ucfirst(str_replace('_', ' ', $link['rotation_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="destinations-preview" 
                                                 title="<?php echo implode(', ', $link['destinations']); ?>">
                                                <?php echo count($link['destinations']); ?> URLs
                                            </div>
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
                                            <button class="btn btn-sm btn-secondary view-analytics" 
                                                    data-link-id="<?php echo $link['id']; ?>"
                                                    data-short-code="<?php echo $link['short_code']; ?>">
                                                Analytics
                                            </button>
                                            <button class="btn btn-sm btn-primary edit-link" 
                                                    data-link-id="<?php echo $link['id']; ?>"
                                                    data-short-code="<?php echo $link['short_code']; ?>"
                                                    data-destinations='<?php echo htmlspecialchars(json_encode($link['destinations'])); ?>'
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

                    <!-- Links Pagination -->
                    <?php if ($totalLinksPages > 1): ?>
                        <div class="pagination-container" style="padding: 1rem; border-top: 1px solid #e5e7eb;">
                            <div class="pagination">
                                <?php if ($linksPage > 1): ?>
                                    <a href="?links_page=<?php echo $linksPage - 1; ?><?php echo !empty($linksSearch) ? '&links_search=' . urlencode($linksSearch) : ''; ?><?php echo !empty($usersSearch) || $usersPage > 1 ? '&users_search=' . urlencode($usersSearch) . '&users_page=' . $usersPage : ''; ?>" 
                                       class="btn btn-sm btn-secondary">← Previous</a>
                                <?php endif; ?>

                                <span class="pagination-info">
                                    Page <?php echo $linksPage; ?> of <?php echo $totalLinksPages; ?> 
                                    (<?php echo $totalLinksCount; ?> total links)
                                </span>

                                <?php if ($linksPage < $totalLinksPages): ?>
                                    <a href="?links_page=<?php echo $linksPage + 1; ?><?php echo !empty($linksSearch) ? '&links_search=' . urlencode($linksSearch) : ''; ?><?php echo !empty($usersSearch) || $usersPage > 1 ? '&users_search=' . urlencode($usersSearch) . '&users_page=' . $usersPage : ''; ?>" 
                                       class="btn btn-sm btn-secondary">Next →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- System Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">System Actions</h2>
                    <p class="card-subtitle">Administrative tools and maintenance</p>
                </div>

                <div class="d-flex gap-3">
                    <button class="btn btn-secondary" onclick="cleanupExpiredLinks()">
                        Clean Up Expired Links
                    </button>
                    <button class="btn btn-secondary" onclick="exportData()">
                        Export System Data
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script src="../public/script.js"></script>
    <script src="../mobile-touch-fix.js"></script>
    <script>
        // Get base URL for API calls
        function getBaseUrl() {
            let path = window.location.pathname;
            if (path.endsWith('/admin/') || path.endsWith('/admin')) {
                path = path.replace(/\/admin\/?$/, '');
            }
            return path || '';
        }

        // Initialize admin panel
        document.addEventListener('DOMContentLoaded', function() {
            // Admin link form submission
            const adminForm = document.getElementById('admin-link-form');
            if (adminForm) {
                adminForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const resultDiv = document.getElementById('admin-link-result');
                    
                    // Show loading state
                    submitBtn.textContent = 'Creating...';
                    submitBtn.disabled = true;
                    
                    try {
                        const baseUrl = getBaseUrl();
                        const response = await fetch(baseUrl + '/api/create-admin-link.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            resultDiv.innerHTML = '<div class="alert alert-success">Admin link created successfully! <br><strong>URL:</strong> ' + result.short_url + '</div>';
                            this.reset();
                            setTimeout(() => window.location.reload(), 2000);
                        } else {
                            resultDiv.innerHTML = '<div class="alert alert-error">' + (result.error || 'Failed to create link') + '</div>';
                        }
                    } catch (error) {
                        resultDiv.innerHTML = '<div class="alert alert-error">Network error. Please try again.</div>';
                    } finally {
                        submitBtn.textContent = 'Create Admin Link';
                        submitBtn.disabled = false;
                    }
                });
            }

            // Admin add destination button
            const adminAddBtn = document.getElementById('admin-add-destination');
            if (adminAddBtn) {
                adminAddBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const container = document.getElementById('admin-destinations-container');
                    const div = document.createElement('div');
                    div.className = 'destination-input';
                    div.innerHTML = `
                        <input type="url" name="destinations[]" class="form-input destination-url" 
                               placeholder="https://example.com" required>
                        <button type="button" class="remove-destination remove-btn" title="Remove">×</button>
                    `;
                    container.appendChild(div);
                });
            }

            // View analytics buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-analytics')) {
                    const linkId = e.target.dataset.linkId;
                    const shortCode = e.target.dataset.shortCode;
                    showAnalytics(linkId, shortCode);
                }
                
                // Edit link buttons
                if (e.target.classList.contains('edit-link')) {
                    const linkId = e.target.dataset.linkId;
                    const shortCode = e.target.dataset.shortCode;
                    const destinations = JSON.parse(e.target.dataset.destinations);
                    const rotationType = e.target.dataset.rotationType;
                    showEditModal(linkId, shortCode, destinations, rotationType);
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
                            <div class="loading-spinner">Loading analytics...</div>
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
                            <div class="analytics-stats">
                                <div class="stat-row">
                                    <span>Total Clicks:</span>
                                    <strong>${data.total_clicks}</strong>
                                </div>
                                <div class="stat-row">
                                    <span>Unique IPs:</span>
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
                            
                            <h4>Recent Clicks</h4>
                            <div class="recent-clicks">
                                ${data.recent_clicks.map(click => `
                                    <div class="click-item">
                                        <span class="click-time">${click.time_ago}</span>
                                        <span class="click-ip">${click.ip_address}</span>
                                    </div>
                                `).join('')}
                            </div>
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

        function cleanupExpiredLinks() {
            if (confirm('Are you sure you want to clean up all expired links?')) {
                const baseUrl = getBaseUrl();
                fetch(baseUrl + '/api/cleanup.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Cleanup completed successfully!');
                            window.location.reload();
                        } else {
                            alert('Cleanup failed: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Cleanup error:', error);
                        alert('Network error during cleanup: ' + error.message);
                    });
            }
        }

        function showEditModal(linkId, shortCode, destinations, rotationType) {
            const baseUrl = getBaseUrl();
            
            // Create edit modal
            const modal = document.createElement('div');
            modal.className = 'edit-modal-overlay';
            modal.innerHTML = `
                <div class="edit-modal">
                    <div class="edit-modal-header">
                        <h3>Edit Link: /${shortCode}</h3>
                        <button class="edit-modal-close">&times;</button>
                    </div>
                    <div class="edit-modal-body">
                        <form id="edit-link-form">
                            <input type="hidden" name="link_id" value="${linkId}">
                            
                            <div class="form-group">
                                <label class="form-label">Long ass URLs</label>
                                <div id="edit-destinations-container">
                                    ${destinations.map(url => `
                                        <div class="destination-input">
                                            <input type="url" name="destinations[]" class="form-input destination-url" 
                                                   value="${url}" required>
                                            <button type="button" class="remove-destination remove-btn" title="Remove">×</button>
                                        </div>
                                    `).join('')}
                                </div>
                                <a href="#" id="edit-add-destination" class="add-destination">
                                    <span>+</span> Add Another Destination
                                </a>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="edit_rotation_type">Rotation Type</label>
                                <select name="rotation_type" id="edit_rotation_type" class="form-input form-select">
                                    <option value="round_robin" ${rotationType === 'round_robin' ? 'selected' : ''}>Round Robin (Sequential)</option>
                                    <option value="random" ${rotationType === 'random' ? 'selected' : ''}>Random</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="edit-modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.edit-modal-overlay').remove()">Cancel</button>
                        <button type="button" class="btn btn-primary" id="save-link-changes">Save Changes</button>
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

            // Add destination functionality
            modal.querySelector('#edit-add-destination').addEventListener('click', function(e) {
                e.preventDefault();
                const container = modal.querySelector('#edit-destinations-container');
                const div = document.createElement('div');
                div.className = 'destination-input';
                div.innerHTML = `
                    <input type="url" name="destinations[]" class="form-input destination-url" 
                           placeholder="https://example.com" required>
                    <button type="button" class="remove-destination remove-btn" title="Remove">×</button>
                `;
                container.appendChild(div);
            });

            // Remove destination functionality
            modal.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-destination')) {
                    const container = modal.querySelector('#edit-destinations-container');
                    if (container.children.length > 1) {
                        e.target.parentElement.remove();
                    } else {
                        alert('At least one destination URL is required');
                    }
                }
            });

            // Save changes functionality
            modal.querySelector('#save-link-changes').addEventListener('click', async function() {
                const form = modal.querySelector('#edit-link-form');
                const formData = new FormData(form);
                
                // Get all destination URLs
                const destinationInputs = form.querySelectorAll('input[name="destinations[]"]');
                const destinationUrls = Array.from(destinationInputs).map(input => input.value.trim()).filter(url => url);
                
                if (destinationUrls.length === 0) {
                    alert('At least one destination URL is required');
                    return;
                }

                // Validate URLs
                for (let url of destinationUrls) {
                    try {
                        new URL(url);
                    } catch {
                        alert('Invalid URL: ' + url);
                        return;
                    }
                }

                this.textContent = 'Saving...';
                this.disabled = true;

                try {
                    const updateData = new FormData();
                    updateData.append('link_id', linkId);
                    destinationUrls.forEach(url => updateData.append('destinations[]', url));
                    updateData.append('rotation_type', form.querySelector('[name="rotation_type"]').value);

                    const response = await fetch(baseUrl + '/api/admin-update-link.php', {
                        method: 'POST',
                        body: updateData
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Link updated successfully!');
                        closeModal();
                        window.location.reload();
                    } else {
                        alert('Failed to update link: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Network error. Please try again.');
                } finally {
                    this.textContent = 'Save Changes';
                    this.disabled = false;
                }
            });
        }

        function exportData() {
            alert('Export functionality would be implemented here. This could generate CSV/JSON exports of system data.');
        }
    </script>
</body>
</html> 