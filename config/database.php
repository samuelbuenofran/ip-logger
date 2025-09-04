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
                    expiry_date DATETIME NULL,
                    custom_domain VARCHAR(255) NULL,
                    extension VARCHAR(20) NULL,
                    tracking_code VARCHAR(50) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_short_code (short_code),
                    INDEX idx_tracking_code (tracking_code),
                    INDEX idx_created_at (created_at),
                    INDEX idx_expiry_date (expiry_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Targets table
            $this->conn->exec("
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
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Insert default settings
            $this->conn->exec("
                INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
                ('site_name', 'IP Logger'),
                ('site_description', 'URL Shortener & IP Tracker'),
                ('default_expiry_days', '30'),
                ('max_links_per_user', '100'),
                ('data_retention_days', '90'),
                ('anonymize_ips', 'false'),
                ('google_maps_api_key', ''),
                ('privacy_policy', 'This tool respects your privacy and complies with applicable data protection laws.'),
                ('terms_of_service', 'By using this service, you agree to our terms of service.')
            ");
            
            return true;
        } catch(PDOException $exception) {
            echo "Error creating tables: " . $exception->getMessage();
            return false;
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
