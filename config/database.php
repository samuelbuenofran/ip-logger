<?php
class Database {

    /*
    const DB_HOST = 'localhost';
    const DB_NAME = 'techeletric_ip_logger';
    const DB_USER = 'techeletric_ip_logger';
    const DB_PASS = 'guLepdtQVrbnkYV6CEuf';
    */
    
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    public function createTables() {
        try {
            // Links table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS links (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    original_url TEXT NOT NULL,
                    short_code VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    password_recovery_code VARCHAR(255) NULL,
                    expiry_date DATETIME NULL,
                    custom_domain VARCHAR(255) NULL,
                    extension VARCHAR(20) NULL,
                    tracking_code VARCHAR(50) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_short_code (short_code),
                    INDEX idx_tracking_code (tracking_code),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Targets table with enhanced geolocation columns
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS targets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    link_id INT NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
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
                    
                    -- Enhanced Geolocation Data
                    confidence_score DECIMAL(5,2) DEFAULT NULL,
                    accuracy_meters INT DEFAULT NULL,
                    location_method VARCHAR(50) DEFAULT NULL,
                    precision_level VARCHAR(50) DEFAULT NULL,
                    data_sources JSON DEFAULT NULL,
                    
                    -- Network Analysis
                    network_type VARCHAR(50) DEFAULT NULL,
                    carrier_info VARCHAR(100) DEFAULT NULL,
                    vpn_detected BOOLEAN DEFAULT FALSE,
                    proxy_detected BOOLEAN DEFAULT FALSE,
                    tor_exit_node BOOLEAN DEFAULT FALSE,
                    data_center BOOLEAN DEFAULT FALSE,
                    mobile_network BOOLEAN DEFAULT FALSE,
                    isp_network BOOLEAN DEFAULT FALSE,
                    
                    -- Device Fingerprinting
                    browser VARCHAR(50) DEFAULT NULL,
                    browser_version VARCHAR(20) DEFAULT NULL,
                    os VARCHAR(50) DEFAULT NULL,
                    os_version VARCHAR(20) DEFAULT NULL,
                    platform VARCHAR(20) DEFAULT NULL,
                    screen_resolution VARCHAR(20) DEFAULT NULL,
                    timezone_offset INT DEFAULT NULL,
                    language VARCHAR(10) DEFAULT NULL,
                    touch_support BOOLEAN DEFAULT FALSE,
                    
                    -- Historical Analysis
                    historical_consistency_score DECIMAL(5,2) DEFAULT NULL,
                    location_variance_km DECIMAL(10,2) DEFAULT NULL,
                    most_frequent_location JSON DEFAULT NULL,
                    
                    -- Refined Coordinates
                    refined_latitude DECIMAL(10, 8) DEFAULT NULL,
                    refined_longitude DECIMAL(11, 8) DEFAULT NULL,
                    accuracy_improvement_meters INT DEFAULT NULL,
                    confidence_boost INT DEFAULT NULL,
                    
                    FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
                    INDEX idx_link_id (link_id),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_clicked_at (clicked_at),
                    INDEX idx_country (country),
                    INDEX idx_confidence_score (confidence_score),
                    INDEX idx_network_type (network_type),
                    INDEX idx_device_type (device_type),
                    INDEX idx_vpn_detected (vpn_detected),
                    INDEX idx_data_center (data_center)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            echo "Tables created successfully!\n";
            
        } catch (PDOException $e) {
            echo "Error creating tables: " . $e->getMessage() . "\n";
        }
    }
    
    public function getSetting($key) {
        $stmt = $this->conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }
    
    public function updateSetting($key, $value) {
        $stmt = $this->conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    }
}
?>
