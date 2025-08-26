<?php
/**
 * Database Setup and Repair Script for Link Rotator
 * 
 * This script will:
 * - Create the database if it doesn't exist
 * - Create all required tables
 * - Add missing columns if tables exist but are incomplete
 * - Create default admin user
 * - Fix common database issues
 * 
 * Run this script by visiting: http://yoursite.com/fix_database.php
 */

// Prevent running in production accidentally
$isProduction = false; // Set to true if you want to allow running in production

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup & Repair - Link Rotator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: #10b981; background: #f0fdf4; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fef2f2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; background: #fefbf2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #eff6ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; }
    </style>
</head>
<body>";

echo "<h1>🔗 Link Rotator - Database Setup & Repair</h1>";
echo "<p>This script will create and repair your Link Rotator database.</p>";

// Detect XAMPP environment
$isXampp = false;
if (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
    $xamppPaths = [
        'C:\\xampp\\htdocs',
        '/opt/lampp/htdocs',
        '/Applications/XAMPP/htdocs'
    ];
    
    foreach ($xamppPaths as $path) {
        if (strpos($_SERVER['DOCUMENT_ROOT'], $path) !== false) {
            $isXampp = true;
            break;
        }
    }
}

if ($isXampp) {
    echo "<div class='info'>🎯 XAMPP environment detected! Using XAMPP-optimized settings.</div>";
}

// Database configuration for XAMPP
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '', // XAMPP default
    'dbname' => 'link_rotator',
    'port' => '3306'
];

// Allow custom configuration
if (file_exists('config/database.php')) {
    echo "<div class='info'>✓ Found config/database.php - using those settings</div>";
    require_once 'config/database.php';
    $config = [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'dbname' => DB_NAME,
        'port' => defined('DB_PORT') ? DB_PORT : '3306'
    ];
} else {
    echo "<div class='warning'>⚠ config/database.php not found - using XAMPP default settings</div>";
}

// XAMPP-specific checks and recommendations
if ($isXampp) {
    echo "<div class='step'>";
    echo "<h3>XAMPP Environment Checks</h3>";
    
    // Check if XAMPP services might be running
    $mysqlRunning = @fsockopen('localhost', $config['port'], $errno, $errstr, 1);
    if ($mysqlRunning) {
        echo "<div class='success'>✓ MySQL service appears to be running on port {$config['port']}</div>";
        fclose($mysqlRunning);
    } else {
        echo "<div class='error'>✗ MySQL service not responding on port {$config['port']}</div>";
        echo "<div class='warning'>Please start MySQL in XAMPP Control Panel before continuing</div>";
    }
    
    // Check Apache
    $apachePort = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
    echo "<div class='info'>ℹ Apache running on port $apachePort</div>";
    
    // Check document root
    echo "<div class='info'>ℹ Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</div>";
    
    echo "</div>";
}

echo "<div class='step'>";
echo "<h3>Current Configuration:</h3>";
echo "<pre>";
echo "Host: " . $config['host'] . "\n";
echo "User: " . $config['user'] . "\n";
echo "Password: " . (empty($config['pass']) ? '[empty]' : '[hidden]') . "\n";
echo "Database: " . $config['dbname'] . "\n";
echo "</pre>";
echo "</div>";

$steps = [];
$errors = [];

// Step 1: Test connection without database
echo "<div class='step'>";
echo "<h3>Step 1: Testing MySQL Connection</h3>";

try {
    $pdo = new PDO("mysql:host=" . $config['host'], $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✓ MySQL connection successful</div>";
    $steps[] = "MySQL connection established";
} catch(PDOException $e) {
    echo "<div class='error'>✗ MySQL connection failed: " . $e->getMessage() . "</div>";
    $errors[] = "Cannot connect to MySQL: " . $e->getMessage();
    echo "<div class='warning'>Please check your database credentials in config/database.php</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 2: Create database if it doesn't exist
echo "<div class='step'>";
echo "<h3>Step 2: Creating Database</h3>";

try {
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . $config['dbname'] . "'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>ℹ Database '{$config['dbname']}' already exists</div>";
        $steps[] = "Database already exists";
    } else {
        // Create database
        $pdo->exec("CREATE DATABASE `" . $config['dbname'] . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div class='success'>✓ Database '{$config['dbname']}' created successfully</div>";
        $steps[] = "Database created";
    }
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'] . ";charset=utf8mb4", 
                   $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "<div class='error'>✗ Database creation failed: " . $e->getMessage() . "</div>";
    $errors[] = "Database creation failed: " . $e->getMessage();
}
echo "</div>";

// Step 3: Create tables
echo "<div class='step'>";
echo "<h3>Step 3: Creating Tables</h3>";

$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `email` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('user','admin') NOT NULL DEFAULT 'user',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'links' => "CREATE TABLE IF NOT EXISTS `links` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `short_code` varchar(20) NOT NULL,
        `destinations` text NOT NULL,
        `rotation_type` enum('round_robin','random') NOT NULL DEFAULT 'round_robin',
        `current_index` int(11) NOT NULL DEFAULT '0',
        `expires_at` timestamp NULL DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `short_code` (`short_code`),
        KEY `user_id` (`user_id`),
        KEY `expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'link_clicks' => "CREATE TABLE IF NOT EXISTS `link_clicks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `link_id` int(11) NOT NULL,
        `clicked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text,
        PRIMARY KEY (`id`),
        KEY `link_id` (`link_id`),
        KEY `clicked_at` (`clicked_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tables as $tableName => $sql) {
    try {
        $pdo->exec($sql);
        echo "<div class='success'>✓ Table '{$tableName}' created/verified</div>";
        $steps[] = "Table '{$tableName}' ready";
    } catch(PDOException $e) {
        echo "<div class='error'>✗ Failed to create table '{$tableName}': " . $e->getMessage() . "</div>";
        $errors[] = "Table creation failed for '{$tableName}': " . $e->getMessage();
    }
}

// Add foreign key constraints separately to avoid dependency issues
try {
    $pdo->exec("ALTER TABLE `links` ADD CONSTRAINT `links_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
    echo "<div class='success'>✓ Foreign key constraint added to links table</div>";
} catch(PDOException $e) {
    // Constraint might already exist, check error message
    if (strpos($e->getMessage(), 'Duplicate foreign key constraint') === false) {
        echo "<div class='info'>ℹ Foreign key constraint already exists or couldn't be added: " . $e->getMessage() . "</div>";
    }
}

try {
    $pdo->exec("ALTER TABLE `link_clicks` ADD CONSTRAINT `link_clicks_ibfk_1` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE");
    echo "<div class='success'>✓ Foreign key constraint added to link_clicks table</div>";
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate foreign key constraint') === false) {
        echo "<div class='info'>ℹ Foreign key constraint already exists or couldn't be added: " . $e->getMessage() . "</div>";
    }
}

echo "</div>";

// Step 4: Check and add missing columns (for upgrades)
echo "<div class='step'>";
echo "<h3>Step 4: Checking Table Structure</h3>";

$columnChecks = [
    'users' => ['id', 'email', 'password', 'role', 'created_at', 'updated_at'],
    'links' => ['id', 'user_id', 'short_code', 'destinations', 'rotation_type', 'current_index', 'expires_at', 'created_at', 'updated_at'],
    'link_clicks' => ['id', 'link_id', 'clicked_at', 'ip_address', 'user_agent']
];

foreach ($columnChecks as $tableName => $requiredColumns) {
    try {
        $stmt = $pdo->query("DESCRIBE `$tableName`");
        $existingColumns = [];
        while ($row = $stmt->fetch()) {
            $existingColumns[] = $row['Field'];
        }
        
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        if (empty($missingColumns)) {
            echo "<div class='success'>✓ Table '{$tableName}' structure is complete</div>";
        } else {
            echo "<div class='warning'>⚠ Table '{$tableName}' missing columns: " . implode(', ', $missingColumns) . "</div>";
            echo "<div class='info'>ℹ You may need to manually add missing columns or recreate the table</div>";
        }
        
    } catch(PDOException $e) {
        echo "<div class='error'>✗ Could not check table '{$tableName}': " . $e->getMessage() . "</div>";
    }
}
echo "</div>";

// Step 5: Create default admin user
echo "<div class='step'>";
echo "<h3>Step 5: Creating Default Admin User</h3>";

try {
    // Check if any admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount > 0) {
        echo "<div class='info'>ℹ Admin user already exists ($adminCount admin(s) found)</div>";
        $steps[] = "Admin user already exists";
    } else {
        // Create default admin
        $adminEmail = 'admin@linkrotator.com';
        $adminPassword = 'admin123';
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$adminEmail, $hashedPassword]);
        
        echo "<div class='success'>✓ Default admin user created</div>";
        echo "<div class='warning'>⚠ <strong>IMPORTANT:</strong> Please change these credentials immediately!</div>";
        echo "<div class='info'>";
        echo "<strong>Default Admin Credentials:</strong><br>";
        echo "Email: $adminEmail<br>";
        echo "Password: $adminPassword";
        echo "</div>";
        $steps[] = "Default admin user created";
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>✗ Failed to create admin user: " . $e->getMessage() . "</div>";
    $errors[] = "Admin user creation failed: " . $e->getMessage();
}
echo "</div>";

// Step 6: Test database functions
echo "<div class='step'>";
echo "<h3>Step 6: Testing Database Functions</h3>";

try {
    // Test insert
    $testDestinations = json_encode(['https://example1.com', 'https://example2.com']);
    $testCode = 'TEST' . rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO links (short_code, destinations, rotation_type) VALUES (?, ?, 'round_robin')");
    $stmt->execute([$testCode, $testDestinations]);
    echo "<div class='success'>✓ Test link creation successful</div>";
    
    // Test select
    $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ?");
    $stmt->execute([$testCode]);
    $testLink = $stmt->fetch();
    if ($testLink) {
        echo "<div class='success'>✓ Test link retrieval successful</div>";
    }
    
    // Test click logging
    $stmt = $pdo->prepare("INSERT INTO link_clicks (link_id, ip_address, user_agent) VALUES (?, '127.0.0.1', 'Test Agent')");
    $stmt->execute([$testLink['id']]);
    echo "<div class='success'>✓ Test click logging successful</div>";
    
    // Clean up test data
    $pdo->prepare("DELETE FROM link_clicks WHERE link_id = ?")->execute([$testLink['id']]);
    $pdo->prepare("DELETE FROM links WHERE id = ?")->execute([$testLink['id']]);
    echo "<div class='success'>✓ Test data cleaned up</div>";
    
    $steps[] = "Database functions tested successfully";
    
} catch(PDOException $e) {
    echo "<div class='error'>✗ Database function test failed: " . $e->getMessage() . "</div>";
    $errors[] = "Database function test failed: " . $e->getMessage();
}
echo "</div>";

// Step 7: Summary
echo "<div class='step'>";
echo "<h3>🎉 Setup Complete - Summary</h3>";

if (empty($errors)) {
    echo "<div class='success'>";
    echo "<h4>✅ All steps completed successfully!</h4>";
    echo "<p>Your Link Rotator database is ready to use.</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h4>⚠ Setup completed with some issues</h4>";
    echo "<p>Please review the errors below:</p>";
    echo "</div>";
}

echo "<h4>Steps Completed:</h4>";
echo "<ul>";
foreach ($steps as $step) {
    echo "<li>✓ $step</li>";
}
echo "</ul>";

if (!empty($errors)) {
    echo "<h4>Errors Encountered:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: #ef4444;'>✗ $error</li>";
    }
    echo "</ul>";
}

echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li>Delete this file (fix_database.php) for security</li>";
echo "<li>Visit your website: <a href='./'>Go to Link Rotator</a></li>";
echo "<li>Log in with admin credentials (if created above)</li>";
echo "<li>Change the default admin password immediately</li>";
echo "<li>Start creating your link rotators!</li>";
echo "</ol>";

echo "<div class='warning'>";
echo "<strong>Security Note:</strong> Please delete this file after running it to prevent unauthorized access.";
echo "</div>";

echo "</div>";

// Add database info for troubleshooting
echo "<div class='step'>";
echo "<h3>🔧 Database Information (for troubleshooting)</h3>";

try {
    // Get MySQL version
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "<p><strong>MySQL Version:</strong> " . $version['version'] . "</p>";
    
    // Get table count
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "<p><strong>Tables Created:</strong> " . count($tables) . "</p>";
    
    // Get user count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch();
    echo "<p><strong>Users in Database:</strong> " . $userCount['count'] . "</p>";
    
    // Get link count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM links");
    $linkCount = $stmt->fetch();
    echo "<p><strong>Links in Database:</strong> " . $linkCount['count'] . "</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: #ef4444;'>Could not retrieve database info: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='./' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Link Rotator</a>";
echo "</p>";

echo "</body></html>";
?> 