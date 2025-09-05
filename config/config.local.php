<?php
// Local Database Configuration for Testing
define('DB_HOST', 'localhost');
define('DB_NAME', 'ip_logger_test');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('BASE_URL', 'http://localhost/ip-logger/');
define('SITE_NAME', 'IP Logger');
define('SITE_DESCRIPTION', 'URL Shortener & IP Tracker');

// Google Maps API Configuration
define('GOOGLE_MAPS_API_KEY', 'AIzaSyC5gMYj7gqRiwNlE6BxyLAdG9IMCCJZsrs');

// Security Configuration
define('SHORT_CODE_LENGTH', 8);
define('DEFAULT_LINK_EXPIRY_DAYS', 30);
define('MAX_LINKS_PER_USER', 100);

// Privacy Configuration
define('DATA_RETENTION_DAYS', 90);
define('ANONYMIZE_IPS', false);

// Rate Limiting
define('MAX_REQUESTS_PER_MINUTE', 60);
define('MAX_LINKS_PER_HOUR', 10);

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'IP Logger');

// Enhanced Geolocation API Keys (Optional - for maximum precision)
define('IP_API_KEY', ''); // Get from https://ip-api.com/
define('IP2LOCATION_API_KEY', ''); // Get from https://www.ip2location.com/
define('MAXMIND_LICENSE_KEY', ''); // Get from https://www.maxmind.com/
define('IP_GEOLOCATION_API_KEY', ''); // Get from https://ipgeolocation.io/

// Debug Mode (set to false in production)
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', 'logs/error.log');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_SECURE', false); // Set to true in production with HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('CACHE_DIR', 'cache/');

// Backup Configuration
define('BACKUP_ENABLED', true);
define('BACKUP_DIR', 'backups/');
define('BACKUP_RETENTION_DAYS', 30);
?>
