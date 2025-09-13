<?php
// Local Database Configuration for Testing
define('DB_HOST', 'localhost');
define('DB_NAME', 'techeletric_ip_logger');
define('DB_USER', 'root');
define('DB_PASS', '123qwe');

// Application Configuration
define('BASE_URL', 'http://localhost/ip-logger/');
define('SITE_NAME', 'IP Logger');
define('SITE_DESCRIPTION', 'Encurtador de URL e rastreador de IP');

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
define('SMTP_HOST', 'mail.keizai-tech.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'iplogger@gmail.com');
define('SMTP_PASSWORD', 'YS^aL&fctdzcQ5');
define('SMTP_FROM_EMAIL', 'iplogger@keizai-tech.com');
define('SMTP_FROM_NAME', 'IP Logger System');
define('SMTP_SECURE', 'tls');

// Enhanced Geolocation API Keys (Optional - for maximum precision)
// IP API charges users in Euros, which makes the platform unfeasable for us in Brazil
// Get from https://ip-api.com/
// define('IP_API_KEY', '');

// Also expensive
// Get from https://www.ip2location.com/
//define('IP2LOCATION_API_KEY', '');


// Get from https://www.maxmind.com/
define('MAXMIND_ACCOUNT_ID', '1225647');

define('MAXMIND_LICENSE_KEY', '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 ');

// Get from https://ipgeolocation.io/
define('IP_GEOLOCATION_API_KEY', '');

// Session Configuration
// 1 hour
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

define('GEOLOCATION_TIMEOUT', 5);


// Debug Mode (set to false in production)
define('DEBUG_MODE', true);
// define('LOG_ERRORS', true);
// define('ERROR_LOG_FILE', 'logs/error.log');

// Error reporting
error_reporting(E_ALL);

// Will it log errors?
ini_set('log_errors', 1);

// Set the path to save the log files
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/Sao_Paulo');
