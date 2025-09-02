<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'techeletric_ip_logger');
define('DB_USER', 'techeletric_ip_logger');
define('DB_PASS', 'guLepdtQVrbnkYV6CEuf');

// Application Configuration
define('BASE_URL', 'http://keizai-tech.com/projects/ip-logger/');
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
define('ANONYMIZE_IPS', false); // Set to true to anonymize IP addresses

// Rate Limiting
define('MAX_REQUESTS_PER_MINUTE', 60);
define('MAX_LINKS_PER_HOUR', 10);

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'it@keizai-tech.com');
define('SMTP_PASSWORD', 'your-app-password'); // Replace with your actual app password
define('SMTP_FROM_EMAIL', 'it@keizai-tech.com');
define('SMTP_FROM_NAME', 'IP Logger System');
define('SMTP_SECURE', 'tls'); // Use 'ssl' for port 465, 'tls' for port 587

// Email notification settings
define('EMAIL_NOTIFICATIONS_ENABLED', true);
define('NOTIFY_ON_LINK_CLICK', true);
define('NOTIFY_ON_NEW_LINK', true);
define('NOTIFY_ON_LINK_EXPIRY', true);

// Geolocation API Configuration
define('GEOLOCATION_API_URL', 'http://ip-api.com/json/');
define('GEOLOCATION_TIMEOUT', 5);

// Function to generate short codes - moved to includes/functions.php to avoid duplication

// Function to get client IP address - moved to includes/functions.php to avoid duplication

// Function to get geolocation data - moved to includes/functions.php to avoid duplication

// Function to detect device type - moved to includes/functions.php to avoid duplication

// Function to validate URL - moved to includes/functions.php to avoid duplication
// Function to sanitize input - moved to includes/functions.php to avoid duplication
// Function to check if link is expired - moved to includes/functions.php to avoid duplication
// Function to format date - moved to includes/functions.php to avoid duplication
// Function to get time ago - moved to includes/functions.php to avoid duplication

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');
?>
