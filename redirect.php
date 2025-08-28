<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get the short code from the URL
$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Clean up expired links first
cleanupExpiredLinks();

// Get the link with password info
$link = getLinkWithPasswordInfo($shortCode);

if (!$link) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Check if link is password protected
if ($link['has_password']) {
    session_start();
    
    // Check if user has already verified this link in current session
    $verifiedLinks = $_SESSION['verified_links'] ?? [];
    $isVerified = isset($verifiedLinks[$shortCode]) && 
                  (time() - $verifiedLinks[$shortCode]) < 3600; // 1 hour validity
    
    if (!$isVerified) {
        // Redirect to password verification page
        header('Location: /password.php?code=' . urlencode($shortCode));
        exit;
    }
}

// Get the next destination URL
$nextUrl = getNextDestination($link['id']);

if (!$nextUrl) {
    http_response_code(500);
    echo "Error: No valid destination found";
    exit;
}

// Log the click
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
logClick($link['id'], $ipAddress, $userAgent);

// Check if this is a bot/crawler request
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = preg_match('/bot|crawler|spider|crawling/i', $userAgent);

// If it's a bot, show SEO-friendly page with meta tags
if ($isBot) {
    $destinations = json_decode($link['destinations'], true);
    $primaryUrl = $destinations[0] ?? $nextUrl;
    $baseUrl = getBaseUrl();
    $shortUrl = $baseUrl . '/' . $shortCode;
    
    // Parse the destination URL for better meta information
    $parsedUrl = parse_url($primaryUrl);
    $domain = $parsedUrl['host'] ?? 'website';
    $cleanDomain = str_replace('www.', '', $domain);
    
    // Generate SEO-friendly title and description
    $seoTitle = "Visit " . ucfirst($cleanDomain) . " - Pork.li Short Link";
    $seoDescription = "This Pork.li short link redirects to " . $cleanDomain . ". Fast, reliable URL shortening and link rotation service.";
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <!-- SEO Meta Tags -->
        <title><?php echo htmlspecialchars($seoTitle); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($seoDescription); ?>">
        <meta name="keywords" content="url shortener, link shortener, short link, redirect, <?php echo htmlspecialchars($cleanDomain); ?>">
        <meta name="author" content="Pork.li">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="<?php echo htmlspecialchars($shortUrl); ?>">
        
        <!-- Open Graph Meta Tags -->
        <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($seoDescription); ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo htmlspecialchars($shortUrl); ?>">
        <meta property="og:site_name" content="Pork.li">
        
        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
        <meta name="twitter:description" content="<?php echo htmlspecialchars($seoDescription); ?>">
        
        <!-- Theme color -->
        <meta name="theme-color" content="#ff1493">
        
        <!-- Structured Data for SEO -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "<?php echo htmlspecialchars($seoTitle); ?>",
            "description": "<?php echo htmlspecialchars($seoDescription); ?>",
            "url": "<?php echo htmlspecialchars($shortUrl); ?>",
            "publisher": {
                "@type": "Organization",
                "name": "Pork.li",
                "url": "<?php echo htmlspecialchars($baseUrl); ?>"
            }
        }
        </script>
        
        <style>
            body { font-family: Arial, sans-serif; background: #000; color: #fff; text-align: center; padding: 2rem; }
            .container { max-width: 600px; margin: 0 auto; }
            .link-info { background: #111; padding: 2rem; border-radius: 8px; margin: 2rem 0; }
            .btn { background: #ff1493; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; display: inline-block; margin: 1rem; }
            .destination { color: #ff69b4; word-break: break-all; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🐷 Pork.li Short Link</h1>
            <div class="link-info">
                <h2>Redirecting to <?php echo htmlspecialchars($cleanDomain); ?></h2>
                <p>Short URL: <strong><?php echo htmlspecialchars($shortUrl); ?></strong></p>
                <p>Destination: <span class="destination"><?php echo htmlspecialchars($primaryUrl); ?></span></p>
                <p>This link redirects to <?php echo htmlspecialchars($cleanDomain); ?>. If you are not redirected automatically, click the button below.</p>
                <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="btn">Continue to <?php echo htmlspecialchars($cleanDomain); ?></a>
            </div>
            <p><a href="<?php echo htmlspecialchars($baseUrl); ?>" style="color: #ff69b4;">Create your own short links at Pork.li</a></p>
        </div>
    </body>
    </html>
    <?php
} else {
    // For regular users, redirect immediately
    header('Location: ' . $nextUrl, true, 302);
}
exit;
?> 