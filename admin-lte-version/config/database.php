<?php

/**
 * Database Connection Class for AdminLTE IP Logger
 */
class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function createTables()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS links (
            id int(11) NOT NULL AUTO_INCREMENT,
            short_code varchar(50) NOT NULL,
            original_url text NOT NULL,
            password varchar(255) NOT NULL,
            tracking_code varchar(50) NOT NULL,
            password_recovery_code varchar(50) DEFAULT NULL,
            click_count int(11) DEFAULT 0,
            unique_visitors int(11) DEFAULT 0,
            last_click_at timestamp NULL DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expiry_date timestamp NULL DEFAULT NULL,
            extension varchar(10) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY short_code (short_code),
            UNIQUE KEY tracking_code (tracking_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS targets (
            id int(11) NOT NULL AUTO_INCREMENT,
            link_id int(11) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            referrer text,
            country varchar(100) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            region varchar(100) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            zip_code varchar(20) DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            timezone varchar(50) DEFAULT NULL,
            isp varchar(255) DEFAULT NULL,
            organization varchar(255) DEFAULT NULL,
            as_number varchar(50) DEFAULT NULL,
            device_type enum('desktop','mobile','tablet') DEFAULT 'desktop',
            browser_name varchar(100) DEFAULT NULL,
            browser_version varchar(50) DEFAULT NULL,
            os_name varchar(100) DEFAULT NULL,
            os_version varchar(50) DEFAULT NULL,
            clicked_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id),
            KEY ip_address (ip_address),
            KEY clicked_at (clicked_at),
            FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS rate_limits (
            id int(11) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            action varchar(50) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_action (ip_address, action),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS audit_log (
            id int(11) NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            details text,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS consent_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            target_id int(11) NOT NULL,
            consent_type varchar(50) NOT NULL,
            consent_given tinyint(1) NOT NULL DEFAULT 0,
            consent_text text,
            ip_address varchar(45) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY target_id (target_id),
            FOREIGN KEY (target_id) REFERENCES targets(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        try {
            $this->conn->exec($sql);
            return true;
        } catch (PDOException $exception) {
            echo "Error creating tables: " . $exception->getMessage();
            return false;
        }
    }

    public function getSetting($key)
    {
        $stmt = $this->conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }

    public function updateSetting($key, $value)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    }
}
