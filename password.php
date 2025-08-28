<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/captcha.php';

// Get the short code from the URL parameter
$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Check if the link exists and is password protected
$link = getLinkWithPasswordInfo($shortCode);

if (!$link) {
    http_response_code(404);
    include '404.php';
    exit;
}

if (!$link['has_password']) {
    // Link is not password protected, redirect to normal handling
    header('Location: /' . $shortCode);
    exit;
}

$error = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $captchaAnswer = $_POST['captcha_answer'] ?? '';
    
    // Verify captcha
    if (!verifyCaptcha($captchaAnswer)) {
        $error = 'Invalid captcha answer. Please try again.';
    } elseif (empty($password)) {
        $error = 'Please enter the password.';
    } elseif (verifyLinkPassword($shortCode, $password)) {
        // Password correct, store in session and redirect
        session_start();
        $_SESSION['verified_links'] = $_SESSION['verified_links'] ?? [];
        $_SESSION['verified_links'][$shortCode] = time();
        
        // Redirect to the actual link
        header('Location: /' . $shortCode);
        exit;
    } else {
        $error = 'Incorrect password. Please try again.';
    }
}

// Parse the destination URL for better display
$destinations = json_decode($link['destinations'], true);
$primaryUrl = $destinations[0] ?? '';
$parsedUrl = parse_url($primaryUrl);
$domain = $parsedUrl['host'] ?? 'website';
$cleanDomain = str_replace('www.', '', $domain);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    
    <!-- SEO Meta Tags -->
    <title>🔒 Password Required - Pork.li Private Link</title>
    <meta name="description" content="This is a password-protected private link. Enter the password to continue.">
    <meta name="keywords" content="private link, password protected, secure link, pork.li">
    <meta name="author" content="Pork.li">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Theme color -->
    <meta name="theme-color" content="#ff1493">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/style.css">
    
    <style>
        .password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background);
            padding: 1rem;
        }
        
        .password-card {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        
        .password-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .password-title {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .password-subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .destination-info {
            background: var(--background-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .destination-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .destination-url {
            color: var(--primary-color);
            font-weight: 600;
            word-break: break-all;
        }
        
        .password-form {
            text-align: left;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            background: var(--background-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 20, 147, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn:hover {
            background: var(--primary-dark);
        }
        
        .btn:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
        }
        
        .error-message {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-color);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .captcha-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .captcha-question {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .captcha-input {
            width: 80px;
            text-align: center;
        }
        
        .captcha-refresh {
            background: var(--background-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .captcha-refresh:hover {
            background: var(--border-color);
        }
        
        .footer-link {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .footer-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-card">
            <span class="password-icon">🔒</span>
            <h1 class="password-title">Private Link</h1>
            <p class="password-subtitle">This link is password protected. Please enter the password to continue.</p>
            
            <div class="destination-info">
                <div class="destination-label">Destination:</div>
                <div class="destination-url"><?php echo htmlspecialchars($cleanDomain); ?></div>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="password-form">
                <div class="form-group">
                    <label for="password" class="form-label">🔑 Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter password" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Verify you're human</label>
                    <div class="captcha-container">
                        <span class="captcha-question">
                            <?php
                            $captcha = generateCaptcha();
                            echo $captcha['equation'];
                            ?> = ?
                        </span>
                        <input type="text" name="captcha_answer" class="form-input captcha-input" 
                               placeholder="?" maxlength="3" required>
                        <button type="button" class="captcha-refresh" onclick="location.reload()" title="New question">🔄</button>
                    </div>
                </div>
                
                <button type="submit" class="btn">Access Link</button>
            </form>
            
            <div class="footer-link">
                <a href="/">← Back to Pork.li</a>
            </div>
        </div>
    </div>
    
    <script src="mobile-touch-fix.js"></script>
</body>
</html>
