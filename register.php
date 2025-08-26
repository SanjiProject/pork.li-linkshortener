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
    <title>Register - Pork.li | Free URL Shortener & Link Rotation Account</title>
    <meta name="description" content="Create a free Pork.li account for unlimited link shortening, custom URLs, and detailed analytics. Sign up now!">
    <meta name="keywords" content="register, sign up, create account, pork.li, free account, permanent links, custom URLs, analytics">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://pork.li/register.php">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Register - Pork.li | Create Free Account">
    <meta property="og:description" content="Create a free account for unlimited link shortening, custom URLs, and detailed analytics.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://pork.li/register.php">
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
                    <a href="/login.php">Login</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="card auth-card">
                <div class="card-header">
                    <h1 class="card-title">Create Account</h1>
                    <p class="card-subtitle">Join Pork.li for unlimited link shortening and advanced features.</p>
                </div>

                <form id="register-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Create a password" minlength="6" required>
                        <small class="text-secondary">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-input" placeholder="Confirm your password" minlength="6" required>
                    </div>

                    <!-- Captcha -->
                    <div class="form-group">
                        <div class="captcha-container">
                            <div class="captcha-question">
                                <strong>Are You Human??:</strong> 
                                <span id="captcha-equation">
                                    <?php
                                    // Generate server-side captcha as fallback
                                    require_once 'includes/captcha.php';
                                    $captcha = generateCaptcha();
                                    echo $captcha['equation'];
                                    ?> = ?
                                </span>
                            </div>
                            <div class="captcha-input-container">
                                <input type="number" id="captcha_answer" name="captcha_answer" 
                                       class="form-input captcha-input" placeholder="Answer" required>
                                <button type="button" id="refresh-captcha" class="captcha-refresh" 
                                        title="Get new question">🔄</button>
                            </div>
                            <small class="form-help">Please solve the math problem to verify you're human</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" data-original-text="Create Account">
                        Create Account
                    </button>
                </form>

                <div class="text-center mt-3">
                    <p>Already have an account? <a href="/login.php">Sign in here</a></p>
                </div>

                <div class="card mt-3" style="background: #f0f9ff; padding: 1rem; border-left: 4px solid #0ea5e9;">
                    <h4>Benefits of Registering</h4>
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                        <li>Unlimited link shortening</li>
                        <li>Permanent links that never expire</li>
                        <li>Custom branded short links</li>
                        <li>Advanced link rotation features</li>
                        <li>Detailed click analytics and statistics</li>
                        <li>Personal dashboard to manage all your links</li>
                        <li>Edit and delete your links anytime</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="public/script.js"></script>
</body>
</html> 