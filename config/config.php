<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'techeletric_ip_logger');
define('DB_USER', 'techeletric_ip_logger');
define('DB_PASS', 'guLepdtQVrbnkYV6CEuf');

// Application Configuration
define('BASE_URL', 'https://keizai-tech.com/projects/ip-logger/');
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
define('SMTP_HOST', 'mail.keizai-tech.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'iplogger@gmail.com');
define('SMTP_PASSWORD', 'YS^aL&fctdzcQ5'); // Replace with your actual app password
define('SMTP_FROM_EMAIL', 'iplogger@keizai-tech.com');
define('SMTP_FROM_NAME', 'IP Logger System');
define('SMTP_SECURE', 'tls'); // Use 'ssl' for port 465, 'tls' for port 587

// Email notification settings
define('EMAIL_NOTIFICATIONS_ENABLED', true);
define('NOTIFY_ON_LINK_CLICK', true);
define('NOTIFY_ON_NEW_LINK', true);
define('NOTIFY_ON_LINK_EXPIRY', true);


// I am using several options just in case

// Geolocation API Configuration for the JSON format
define('GEOLOCATION_API_URL_JSON', 'http://ip-api.com/json/');

// Geolocation API Configuration for the XML format
define('GEOLOCATION_API_URL_XML', 'https://ip-api.com/xml/');

// Geolocation API Configuration for the CSV format
define('GEOLOCATION_API_URL_CSV', 'https://ip-api.com/csv/');

// Geolocation API Configuration for the Newline format
define('GEOLOCATION_API_URL_LINE', 'http://ip-api.com/line/');

// Geolocation API Configuration for the PHP format
define('GEOLOCATION_API_URL_PHP', 'https://ip-api.com/php/');


// Uncomment this line and place your API key if you purchased a license
// define('IP_GEOLOCATION_API_KEY', '');

define('GEOLOCATION_TIMEOUT', 5);

// Error reporting
error_reporting(E_ALL);

// Will it log errors?
ini_set('log_errors', 1);

// Set the path to save the log files
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/Sao_Paulo');
