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
define('SMTP_PASSWORD', 'your-app-password');

// Geolocation API Configuration
define('GEOLOCATION_API_URL', 'http://ip-api.com/json/');
define('GEOLOCATION_TIMEOUT', 5);

// Function to generate short codes
function generateShortCode($length = SHORT_CODE_LENGTH) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $shortCode = '';
    
    for ($i = 0; $i < $length; $i++) {
        $shortCode .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $shortCode;
}

// Function to get client IP address
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Function to get geolocation data
function getGeolocationData($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return null;
    }
    
    $url = GEOLOCATION_API_URL . $ip;
    $context = stream_context_create([
        'http' => [
            'timeout' => GEOLOCATION_TIMEOUT,
            'user_agent' => 'IP Logger Tool'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data && $data['status'] === 'success') {
        return [
            'ip' => $data['query'],
            'country' => $data['country'],
            'country_code' => $data['countryCode'],
            'region' => $data['regionName'],
            'city' => $data['city'],
            'zip' => $data['zip'],
            'lat' => $data['lat'],
            'lon' => $data['lon'],
            'timezone' => $data['timezone'],
            'isp' => $data['isp'],
            'org' => $data['org'],
            'as' => $data['as']
        ];
    }
    
    return null;
}

// Function to detect device type
function getDeviceType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (preg_match('/(android|iphone|ipad|mobile)/i', $userAgent)) {
        return 'mobile';
    }
    
    return 'desktop';
}

// Function to validate URL
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to check if link is expired
function isLinkExpired($expiryDate) {
    if ($expiryDate === null) {
        return false;
    }
    
    return strtotime($expiryDate) < time();
}

// Function to format date
function formatDate($date, $format = 'M j, Y H:i') {
    return date($format, strtotime($date));
}

// Function to get time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        $months = floor($time / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');
?>
