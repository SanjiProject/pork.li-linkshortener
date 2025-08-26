<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>Become a Sponsor - Pork.li | Sponsorship Opportunities</title>
    <meta name="description" content="Sponsor Pork.li and reach thousands of users. Contact us for sponsorship opportunities and partnerships.">
    <meta name="keywords" content="sponsor, sponsorship, partnership, advertising, pork.li">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://pork.li/sponsor.php">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Become a Sponsor - Pork.li">
    <meta property="og:description" content="Sponsor Pork.li and reach thousands of users. Contact us for sponsorship opportunities.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://pork.li/sponsor.php">
    <meta property="og:site_name" content="Pork.li">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Become a Sponsor - Pork.li">
    <meta name="twitter:description" content="Sponsor Pork.li and reach thousands of users. Contact us for sponsorship opportunities.">
    
    <!-- Theme color -->
    <meta name="theme-color" content="#ff1493">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    
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
                        <a href="<?php echo getCurrentBaseUrl(); ?>/register.php">Get started</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="sponsor-page">
                <div class="sponsor-content">
                    <h1 class="sponsor-title">Become a Sponsor</h1>
                    
                    <div class="sponsor-info">
                        <p class="sponsor-text">For sponsorship inquiries, please contact</p>
                        <a href="mailto:contact@kennethaaron.com" class="sponsor-email">contact@kennethaaron.com</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="sponsor-footer">
        <div class="container">
        <p>Pork.li | Made with ❤️ by <a href="https://github.com/SanjiProject" target="_blank">SanjiProject</a></p>
        </div>
    </footer>

    <script src="public/script.js"></script>
</body>
</html>

