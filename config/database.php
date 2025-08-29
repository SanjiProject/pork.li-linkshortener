<?php
// Database configuration for aaPanel
// Update these settings according to your aaPanel database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'porkli'); // Update with your aaPanel MySQL username
define('DB_PASS', 'USEYOUROWNPASSWORD'); // Update with your aaPanel MySQL password
define('DB_NAME', 'porkli'); // Update with your database name
define('DB_PORT', '3306'); // Default MySQL port

// aaPanel specific notes:
// 1. Create database 'porkli' in aaPanel Database Manager
// 2. Update DB_USER and DB_PASS with your MySQL credentials
// 3. If using a different database name, update DB_NAME
// 4. Some aaPanel setups use socket connections instead of TCP

// Create connection with aaPanel compatibility
function getConnection() {
    try {
        // Build DSN with optional port for aaPanel flexibility
        $dsn = "mysql:host=" . DB_HOST;
        if (defined('DB_PORT') && DB_PORT != '3306') {
            $dsn .= ";port=" . DB_PORT;
        }
        $dsn .= ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Set timezone for aaPanel compatibility
        $pdo->exec("SET time_zone = '+00:00'");
        
        return $pdo;
    } catch(PDOException $e) {
        // More detailed error for aaPanel troubleshooting
        $error_msg = "Database connection failed: " . $e->getMessage();
        
        // Common aaPanel issues
        if (strpos($e->getMessage(), 'Connection refused') !== false) {
            $error_msg .= "\n\naaPanel Troubleshooting:\n";
            $error_msg .= "- Make sure MySQL service is running in aaPanel\n";
            $error_msg .= "- Check database credentials in aaPanel Database Manager\n";
            $error_msg .= "- Verify database 'porkli' exists\n";
            $error_msg .= "- Check MySQL port configuration";
        }
        
        error_log($error_msg);
        die("Database connection failed. Please check aaPanel MySQL service and database configuration.");
    }
}

// Initialize database tables
function initializeDatabase() {
    $pdo = getConnection();
    
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Links table
    $pdo->exec("CREATE TABLE IF NOT EXISTS links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        short_code VARCHAR(20) UNIQUE NOT NULL,
        destinations TEXT NOT NULL,
        password VARCHAR(255) NULL,
        rotation_type ENUM('round_robin', 'random') DEFAULT 'round_robin',
        current_index INT DEFAULT 0,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Link clicks table for analytics
    $pdo->exec("CREATE TABLE IF NOT EXISTS link_clicks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        link_id INT NOT NULL,
        clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
    )");
    
    // Create default admin user if doesn't exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute(['admin@linkrotator.com', $adminPassword]);
    }
}

// Clean up expired links
function cleanupExpiredLinks() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM links WHERE expires_at IS NOT NULL AND expires_at < NOW()");
        $result = $stmt->execute();
        
        if ($result) {
            $deletedCount = $stmt->rowCount();
            if ($deletedCount > 0) {
                // Update sitemap if expired links were removed
                // Only call updateSitemap if functions.php is loaded
                if (function_exists('updateSitemap')) {
                    updateSitemap();
                }
            }
            return $deletedCount;
        }
        return 0;
    } catch (Exception $e) {
        error_log('Cleanup expired links error: ' . $e->getMessage());
        return 0;
    }
}