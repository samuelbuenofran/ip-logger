<?php
// IP Logger Installation Script

// Check if already installed
if (file_exists('config/installed.txt')) {
    die('IP Logger is already installed. Remove config/installed.txt to reinstall.');
}

// Include configuration
require_once 'config/config.php';

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
} catch(PDOException $e) {
    die("Failed to create database: " . $e->getMessage());
}

// Create tables
try {
    // Links table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_url TEXT NOT NULL,
            short_code VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            expiry_date DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_short_code (short_code),
            INDEX idx_created_at (created_at),
            INDEX idx_expiry_date (expiry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Targets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS targets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            link_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            device_type ENUM('desktop', 'mobile') DEFAULT 'desktop',
            country VARCHAR(100),
            country_code VARCHAR(10),
            region VARCHAR(100),
            city VARCHAR(100),
            zip_code VARCHAR(20),
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            timezone VARCHAR(50),
            isp VARCHAR(200),
            organization VARCHAR(200),
            as_number VARCHAR(100),
            referer TEXT,
            clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
            INDEX idx_link_id (link_id),
            INDEX idx_ip_address (ip_address),
            INDEX idx_clicked_at (clicked_at),
            INDEX idx_country (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert default settings
    $pdo->exec("
        INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
        ('site_name', 'IP Logger'),
        ('site_description', 'URL Shortener & IP Tracker'),
        ('default_expiry_days', '30'),
        ('max_links_per_user', '100'),
        ('data_retention_days', '90'),
        ('anonymize_ips', 'false'),
        ('google_maps_api_key', ''),
        ('privacy_policy', 'This tool respects your privacy and complies with applicable data protection laws.'),
        ('terms_of_service', 'By using this service, you agree to our terms of service.'),
        ('installation_date', NOW())
    ");
    
    // Create sample data for testing
    $pdo->exec("
        INSERT INTO links (original_url, short_code, password, expiry_date) VALUES
        ('https://www.google.com', 'sample1', '" . password_hash('test123', PASSWORD_DEFAULT) . "', DATE_ADD(NOW(), INTERVAL 30 DAY)),
        ('https://www.github.com', 'sample2', '" . password_hash('test123', PASSWORD_DEFAULT) . "', NULL)
    ");
    
    $linkId1 = $pdo->lastInsertId();
    
    // Insert sample targets
    $pdo->exec("
        INSERT INTO targets (link_id, ip_address, user_agent, device_type, country, city, latitude, longitude, isp) VALUES
        ($linkId1, '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'desktop', 'United States', 'New York', 40.7128, -74.0060, 'Comcast'),
        ($linkId1, '203.0.113.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', 'mobile', 'United Kingdom', 'London', 51.5074, -0.1278, 'BT')
    ");
    
} catch(PDOException $e) {
    die("Failed to create tables: " . $e->getMessage());
}

// Create .htaccess file for URL rewriting
$htaccess = "RewriteEngine On\n";
$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "RewriteRule ^([a-zA-Z0-9]+)$ redirect.php?code=$1 [L,QSA]\n";
$htaccess .= "\n";
$htaccess .= "# Security headers\n";
$htaccess .= "Header always set X-Content-Type-Options nosniff\n";
$htaccess .= "Header always set X-Frame-Options DENY\n";
$htaccess .= "Header always set X-XSS-Protection \"1; mode=block\"\n";
$htaccess .= "Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n";

file_put_contents('.htaccess', $htaccess);

// Create installed marker
file_put_contents('config/installed.txt', date('Y-m-d H:i:s'));

// Create assets directory if it doesn't exist
if (!is_dir('assets')) {
    mkdir('assets', 0755, true);
}
if (!is_dir('assets/css')) {
    mkdir('assets/css', 0755, true);
}
if (!is_dir('assets/js')) {
    mkdir('assets/js', 0755, true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Logger - Installation Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-check-circle"></i> Installation Complete</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check"></i> IP Logger has been successfully installed!</h5>
                            <p>Your database and tables have been created successfully.</p>
                        </div>
                        
                        <h5>Next Steps:</h5>
                        <ol>
                            <li><strong>Configure Google Maps API:</strong> Edit <code>config/config.php</code> and add your Google Maps API key</li>
                            <li><strong>Update Database Settings:</strong> Modify database credentials in <code>config/config.php</code> if needed</li>
                            <li><strong>Set Permissions:</strong> Ensure the web server has write permissions to the <code>config/</code> directory</li>
                            <li><strong>Test the Application:</strong> Visit your domain to start using IP Logger</li>
                        </ol>
                        
                        <h5>Sample Data Created:</h5>
                        <ul>
                            <li>2 sample links with short codes: <code>sample1</code> and <code>sample2</code></li>
                            <li>Password for both: <code>test123</code></li>
                            <li>Sample target data for testing</li>
                        </ul>
                        
                        <h5>Important Security Notes:</h5>
                        <div class="alert alert-warning">
                            <ul class="mb-0">
                                <li>Change default passwords immediately</li>
                                <li>Configure your Google Maps API key</li>
                                <li>Review and update privacy settings</li>
                                <li>Consider enabling HTTPS</li>
                                <li>Regularly backup your database</li>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket"></i> Launch IP Logger
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                                <p><strong>MySQL Version:</strong> <?php echo $pdo->query('SELECT VERSION() as version')->fetch()['version']; ?></p>
                                <p><strong>Installation Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
                                <p><strong>Tables Created:</strong> 3 (links, targets, settings)</p>
                                <p><strong>Sample Data:</strong> 2 links, 2 targets</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
