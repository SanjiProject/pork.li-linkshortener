<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirect = $user['role'] === 'admin' ? '/admin/' : '/dashboard/';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>Login - Pork.li | Access Your Link Management Dashboard</title>
    <meta name="description" content="Sign in to your Pork.li account to manage short links, view analytics, and access advanced features. Secure login.">
    <meta name="keywords" content="login, sign in, pork.li, account access, dashboard, link management">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://pork.li/login.php">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Login - Pork.li">
    <meta property="og:description" content="Sign in to your Pork.li account to manage short links and view analytics.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://pork.li/login.php">
    <meta property="og:site_name" content="Pork.li">
    
    <!-- Additional SEO -->
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
    <!-- Header matching dashboard -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <img src="img/porkli.png" alt="Pork.li" style="height: 32px; width: auto; vertical-align: middle; margin-right: 8px;" onerror="this.style.display='none';">
                    Pork.li
                </a>
                <nav class="nav">
                    <a href="/">Home</a>
                    <a href="/register.php">Register</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="card auth-card">
                <div class="card-header">
                    <h1 class="card-title">Login</h1>
                    <p class="card-subtitle">Welcome back! Please sign in to your account.</p>
                </div>

                <form id="login-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" data-original-text="Sign In">
                        Sign In
                    </button>
                </form>

                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="/register.php">Create one here</a></p>
                </div>
            </div>
        </div>
    </main>

    <script src="public/script.js"></script>
</body>
</html> 