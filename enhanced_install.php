<?php
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>Enhanced IP Logger Installation</h2>";
    echo "<p>Installing advanced features...</p>";
    
    // Enhanced links table with advanced features
    $pdo->exec("
        ALTER TABLE links 
        ADD COLUMN IF NOT EXISTS custom_domain VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS custom_path VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS link_extension VARCHAR(10) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS collect_smart_data BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS collect_gps_data BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS require_consent BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS forward_get_params BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS destination_preview BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS click_count INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS unique_visitors INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS last_click_at TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS tracking_code VARCHAR(20) DEFAULT NULL
    ");
    
    // Enhanced targets table with detailed tracking
    $pdo->exec("
        ALTER TABLE targets 
        ADD COLUMN IF NOT EXISTS browser_name VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS browser_version VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS os_name VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS os_version VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS screen_resolution VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS connection_type VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS mobile_carrier VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS device_model VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS gps_accuracy DECIMAL(10,2) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS consent_given BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS consent_timestamp TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS referrer_domain VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS referrer_path VARCHAR(500) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS search_terms TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS utm_source VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS utm_medium VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS utm_term VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS utm_content VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS accuracy_level ENUM('IP', 'GPS', 'CELL') DEFAULT 'IP',
        ADD COLUMN IF NOT EXISTS proxy_detected BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS vpn_detected BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS tor_detected BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS bot_detected BOOLEAN DEFAULT FALSE
    ");
    
    // New table for custom domains
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS custom_domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain VARCHAR(255) NOT NULL UNIQUE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // New table for link extensions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS link_extensions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            extension VARCHAR(10) NOT NULL UNIQUE,
            description VARCHAR(255) DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // New table for detailed analytics
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            link_id INT NOT NULL,
            date DATE NOT NULL,
            hour INT DEFAULT NULL,
            clicks INT DEFAULT 0,
            unique_clicks INT DEFAULT 0,
            mobile_clicks INT DEFAULT 0,
            desktop_clicks INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
            UNIQUE KEY unique_analytics (link_id, date, hour)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // New table for visitor sessions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visitor_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            link_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            first_visit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_visit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            visit_count INT DEFAULT 1,
            FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // New table for consent logs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS consent_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            target_id INT NOT NULL,
            consent_type ENUM('tracking', 'gps', 'smart_data') NOT NULL,
            consent_given BOOLEAN NOT NULL,
            consent_text TEXT,
            ip_address VARCHAR(45),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (target_id) REFERENCES targets(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert default custom domains
    $pdo->exec("
        INSERT IGNORE INTO custom_domains (domain, description) VALUES 
        ('maper.info', 'Default tracking domain'),
        ('iplogger.com', 'Alternative domain'),
        ('tracker.pro', 'Professional tracking domain')
    ");
    
    // Insert default link extensions
    $pdo->exec("
        INSERT IGNORE INTO link_extensions (extension, description) VALUES 
        ('', 'No extension'),
        ('.html', 'HTML page'),
        ('.php', 'PHP script'),
        ('.txt', 'Text file'),
        ('.pdf', 'PDF document')
    ");
    
    // Update existing links with tracking codes
    $pdo->exec("
        UPDATE links 
        SET tracking_code = CONCAT('TRK', LPAD(id, 8, '0'))
        WHERE tracking_code IS NULL
    ");
    
    echo "<div style='color: green;'>✅ Enhanced database structure created successfully!</div>";
    echo "<p><strong>New features added:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Custom domain support</li>";
    echo "<li>✅ Advanced tracking options (SMART data, GPS, consent)</li>";
    echo "<li>✅ Enhanced visitor analytics</li>";
    echo "<li>✅ Session tracking</li>";
    echo "<li>✅ Consent logging</li>";
    echo "<li>✅ UTM parameter tracking</li>";
    echo "<li>✅ Proxy/VPN detection</li>";
    echo "<li>✅ Detailed browser/OS information</li>";
    echo "</ul>";
    
    // Count tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Total tables created:</strong> " . count($tables) . "</p>";
    
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Installation Complete!</h3>";
    echo "<p>Your IP Logger now supports all advanced features including:</p>";
    echo "<ul>";
    echo "<li>Custom domains and paths</li>";
    echo "<li>SMART data collection</li>";
    echo "<li>GPS location tracking</li>";
    echo "<li>Consent management</li>";
    echo "<li>Advanced analytics</li>";
    echo "<li>Visitor session tracking</li>";
    echo "<li>UTM parameter tracking</li>";
    echo "<li>Proxy/VPN detection</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ Database Error: " . $e->getMessage() . "</div>";
}
?>
