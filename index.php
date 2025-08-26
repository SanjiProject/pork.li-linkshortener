<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize database
initializeDatabase();

// Clean up expired links
cleanupExpiredLinks();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>Pork.li - URL Shortener & Smart Link Rotation Tool</title>
    <meta name="description" content="Free URL shortener with smart link rotation. Perfect for social media, marketing, and A/B testing. Create short links instantly.">
    <meta name="keywords" content="pork.li, URL shortener, link shortener, URL rotator, short links, link rotation, A/B testing, marketing tools, affiliate marketing, link management, analytics">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://pork.li/">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Pork.li - URL Shortener & Smart Link Rotation Tool">
    <meta property="og:description" content="Free URL shortener with smart link rotation. Perfect for social media, marketing, and A/B testing.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://pork.li/">
    <meta property="og:site_name" content="Pork.li">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pork.li - URL Shortener & Smart Link Rotation Tool">
    <meta name="twitter:description" content="Free URL shortener with smart link rotation. Perfect for social media, marketing, and A/B testing.">
    <meta name="twitter:site" content="@porkli">
    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="img/porkli.png">
    <link rel="shortcut icon" type="image/webp" href="img/porkli.png">
    
    <!-- For Apple devices -->
    <link rel="apple-touch-icon" href="img/porkli.png">
    
    <!-- For Microsoft Windows tiles -->
    <meta name="msapplication-TileColor" content="#ff1493">
    <meta name="msapplication-TileImage" content="img/porkli.png">
    
    <!-- Theme color -->
    <meta name="theme-color" content="#ff1493">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo getCurrentBaseUrl(); ?>/" class="logo">
                    <img src="<?php echo getCurrentBaseUrl(); ?>/img/porkli.png" alt="Pork.li" style="height: 32px; width: auto; vertical-align: middle; margin-right: 8px;" onerror="this.style.display='none';">
                    Pork.li
                </a>
                <nav class="nav">
                    <?php if ($user): ?>
                        <a href="<?php echo getCurrentBaseUrl(); ?>/dashboard/">Dashboard</a>
                        <a href="<?php echo getCurrentBaseUrl(); ?>/settings/">Settings</a>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?php echo getCurrentBaseUrl(); ?>/admin/">Admin</a>
                        <?php endif; ?>
                        <a href="<?php echo getCurrentBaseUrl(); ?>/api/logout.php">Logout</a>
                    <?php else: ?>
                                            <a href="<?php echo getCurrentBaseUrl(); ?>/login.php">Login</a>
                    <a href="<?php echo getCurrentBaseUrl(); ?>/register.php">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background">
            <div class="hero-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        <div class="container">
            <div class="hero-content">
                <img src="img/swag.webp" alt="Pork.li" style="height: 80px; width: auto; margin-bottom: 1rem;">
                <h1 class="hero-title">
                    <span class="gradient-text">Shorten Links</span><br>
                    <span class="typewriter">Smart Link Rotation</span>
                </h1>
                <p class="hero-subtitle">Shorten any URL instantly or rotate between multiple links. Perfect for social media, marketing, and A/B testing.</p>
                
                <div class="hero-cta">
                    <?php if ($user): ?>
                        <!-- Logged in user buttons -->
                        <a href="<?php echo getCurrentBaseUrl(); ?>/dashboard/" class="cta-button">
                            <span>Go to Dashboard</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9,22 9,12 15,12 15,22"/>
                            </svg>
                        </a>
                    <?php else: ?>
                        <!-- Guest user buttons -->
                        <button id="hero-start-creating-btn" class="cta-button">
                            <span>Start Shortening Links</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7V17"/>
                            </svg>
                        </button>
                        <a href="<?php echo getCurrentBaseUrl(); ?>/register.php" class="secondary-cta">
                            Sign up for free
                            <span class="arrow">→</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <!-- Link Creator (Initially Hidden) -->
            <div class="link-creator" id="link-creator" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">Create Your Short Link</h2>
                    <p class="card-subtitle">
                        Add one URL for shortening, or multiple URLs for smart rotation.
                        <?php if (!$user): ?>
                            Guest links expire in 7 days. <a href="<?php echo getCurrentBaseUrl(); ?>/register.php">Register</a> for permanent links.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Form -->
                <form id="link-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <br>
                        <label class="form-label">Long ass URL(s)</label>
                        <div id="destinations-container">
                            <div class="destination-input">
                                <input type="url" name="destinations[]" class="form-input destination-url" 
                                       placeholder="https://example.com" required>
                            </div>
                        </div>
                        <a href="#" id="add-destination" class="add-destination">
                            <span>+</span> Add Another URL (for rotation)
                        </a>
                        <small class="form-help">Add one URL to shorten it, or multiple URLs to create a rotating link</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="rotation_type">Rotating Behavior (only if needed)</label>
                        <select name="rotation_type" id="rotation_type" class="form-input form-select">
                            <option value="round_robin">Round Robin (for multiple URLs)</option>
                            <option value="random">Random (for multiple URLs)</option>
                        </select>
                        <small class="form-help">Only applies when you have multiple destination URLs</small>
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
                                       maxlength="50"
                                       <?php echo !$user ? 'disabled' : ''; ?>>
                            </div>
                            <?php if (!$user): ?>
                                <div class="premium-notice">
                                    <div class="premium-icon">🔒</div>
                                    <div class="premium-text">
                                        <strong>Custom links are only available for registered users</strong>
                                        <div class="auth-links">
                                                                    <a href="login.php" class="premium-link">Login</a> or
                        <a href="register.php" class="premium-link">Sign up</a> to unlock this feature
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <small class="form-help">Leave empty for random code. Use only letters, numbers, hyphens, and underscores.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">We need to check if you’re human. Oink twice if you’re a pig.🐷</label>
                        <div class="captcha-container">
                            <div class="captcha-question">
                                <span>Solve this noob equation </span>
                                <strong id="captcha-equation">Loading...</strong>
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

                    <button type="submit" class="btn btn-primary btn-block" data-original-text="Create Short Link">
                        Create Short Link
                    </button>
                </form>

                <div id="link-result" class="mt-3"></div>
            </div>

            <!-- How It Works -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">How It Works</h2>
                </div>
                <div class="dashboard-grid">
                    <div>
                        <h3>1. Add Your URL(s)</h3>
                        <p>Enter one URL to shorten it, or multiple URLs for smart rotation.</p>
                    </div>
                    <div>
                        <h3>2. Get Short Link</h3>
                        <p>Receive a clean pork.li short link that's easy to share anywhere.</p>
                    </div>
                    <div>
                        <h3>3. Share & Track</h3>
                        <p>Share your link and monitor performance with detailed analytics.</p>
                    </div>
                </div>
            </div>

            <!-- Use Cases -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Perfect For</h2>
                </div>
                <div class="dashboard-grid">
                    <div>
                        <h3>📱 Social Media</h3>
                        <p>Share clean, short links on Twitter, Instagram, TikTok, and other platforms.</p>
                    </div>
                    <div>
                        <h3>🧪 A/B Testing</h3>
                        <p>Test different landing pages with smart rotation and traffic distribution.</p>
                    </div>
                    <div>
                        <h3>📈 Marketing Campaigns</h3>
                        <p>Create branded short links for email campaigns, ads, and affiliate marketing.</p>
                    </div>
                    <div>
                        <h3>💼 Business Use</h3>
                        <p>Professional link management for teams, presentations, and customer communications.</p>
                    </div>
                    <div>
                        <h3>🔄 Load Balancing</h3>
                        <p>Distribute traffic across multiple servers or mirror sites automatically.</p>
                    </div>
                    <div>
                        <h3>📊 Analytics</h3>
                        <p>Track link performance, user engagement, and optimize your strategies.</p>
                    </div>
                </div>
            </div>

            <!-- Sponsors Section -->
            <?php
            $sponsorsFile = 'config/sponsors.json';
            if (file_exists($sponsorsFile)) {
                $sponsorsData = json_decode(file_get_contents($sponsorsFile), true);
                if ($sponsorsData && $sponsorsData['settings']['enabled']) {
            ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo htmlspecialchars($sponsorsData['settings']['title']); ?></h2>
                    <p class="card-subtitle"><?php echo htmlspecialchars($sponsorsData['settings']['subtitle']); ?></p>
                </div>
                <div class="sponsors-grid">
                    <?php foreach ($sponsorsData['sponsors'] as $sponsor): ?>
                        <a href="<?php echo htmlspecialchars($sponsor['url']); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           class="sponsor-card"
                           title="<?php echo htmlspecialchars($sponsor['name']); ?>">
                            <img src="<?php echo htmlspecialchars($sponsor['logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($sponsor['name']); ?>"
                                 loading="lazy">
                            <span class="sponsor-name"><?php echo htmlspecialchars($sponsor['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
                }
            }
            ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="card" style="margin-top: 3rem;">
        <div class="container">
            <div class="text-center">
                <p>Pork.li | Made with ❤️ by <a href="https://github.com/SanjiProject" target="_blank">SanjiProject</a></p>
     
            </div>
        </div>
    </footer>

    <script src="public/script.js"></script>
</body>
</html> 