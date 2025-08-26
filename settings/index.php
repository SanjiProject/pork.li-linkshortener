<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>Settings - Pork.li | Account Management</title>
    <meta name="description" content="Manage your Pork.li account settings, change password, and view link statistics.">
    <meta name="keywords" content="settings, account management, password change, user preferences, pork.li settings">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://pork.li/settings/">
    
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
                <a href="../" class="logo">
                    <img src="../img/porkli.png" alt="Pork.li" style="height: 32px; width: auto; vertical-align: middle; margin-right: 8px;" onerror="this.style.display='none';">
                    Pork.li
                </a>
                <nav class="nav">
                    <span>Welcome, <?php echo htmlspecialchars($user['email']); ?></span>
                    <a href="../">Home</a>
                    <a href="../dashboard/">Dashboard</a>
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
                <h1 class="card-title">Account Settings</h1>
                <p class="card-subtitle">Manage your account preferences and security</p>
            </div>

            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Account Information</h2>
                </div>
                <div class="settings-info">
                    <div class="info-row">
                        <label>Email Address:</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Account Type:</label>
                        <span><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Member Since:</label>
                        <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Change Password</h2>
                    <p class="card-subtitle">Update your password to keep your account secure</p>
                </div>

                <form id="password-change-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-input" required minlength="6">
                        <small class="form-help">Password must be at least 6 characters long</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary" data-original-text="Change Password">
                        Change Password
                    </button>
                </form>

                <div id="password-result" class="mt-3"></div>
            </div>

            <!-- Account Statistics -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Account Statistics</h2>
                </div>
                
                <?php
                $pdo = getConnection();
                
                // Get user stats
                $stmt = $pdo->prepare("SELECT COUNT(*) as total_links FROM links WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $totalLinks = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total_clicks 
                    FROM link_clicks lc 
                    JOIN links l ON lc.link_id = l.id 
                    WHERE l.user_id = ?
                ");
                $stmt->execute([$user['id']]);
                $totalClicks = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as custom_links 
                    FROM links 
                    WHERE user_id = ? AND (
                        LENGTH(short_code) > 6 OR 
                        short_code LIKE '%-%' OR 
                        short_code LIKE '%_%'
                    )
                ");
                $stmt->execute([$user['id']]);
                $customLinks = $stmt->fetchColumn();
                ?>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalLinks; ?></div>
                        <div class="stat-label">Total Links Created</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $customLinks; ?></div>
                        <div class="stat-label">Custom Links</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalClicks; ?></div>
                        <div class="stat-label">Total Clicks</div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card danger-zone">
                <div class="card-header">
                    <h2 class="card-title">Danger Zone</h2>
                    <p class="card-subtitle">Irreversible and destructive actions</p>
                </div>
                
                <div class="danger-actions">
                    <div class="danger-action">
                        <div class="danger-info">
                            <strong>Delete All Links</strong>
                            <p>Permanently delete all your links and their analytics data.</p>
                        </div>
                        <button class="btn btn-danger" onclick="deleteAllLinks()">
                            Delete All Links
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../public/script.js"></script>
    <script>
        // Handle password change form
        document.getElementById('password-change-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const resultDiv = document.getElementById('password-result');
            
            // Validate password confirmation
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword !== confirmPassword) {
                resultDiv.innerHTML = '<div class="alert alert-error">New passwords do not match</div>';
                return;
            }
            
            // Show loading state
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Changing...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/change-password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">Password changed successfully!</div>';
                    this.reset();
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-error">' + (result.error || 'Failed to change password') + '</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="alert alert-error">Network error. Please try again.</div>';
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
        
        // Delete all links function
        function deleteAllLinks() {
            if (confirm('Are you sure you want to delete ALL your links? This action cannot be undone!')) {
                if (confirm('This will permanently delete all your links and analytics data. Are you absolutely sure?')) {
                    fetch('../api/delete-all-links.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            csrf_token: '<?php echo generateCSRFToken(); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('All links have been deleted successfully.');
                            window.location.reload();
                        } else {
                            alert('Failed to delete links: ' + (result.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        alert('Network error occurred while deleting links.');
                    });
                }
            }
        }
    </script>
</body>
</html> 