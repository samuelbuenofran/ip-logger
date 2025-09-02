<?php
// Utility Functions for IP Logger

/**
 * Generate a unique short code for URLs
 */
function generateShortCode($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $shortCode = '';
    
    do {
        $shortCode = '';
        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM links WHERE short_code = ?");
        $stmt->execute([$shortCode]);
    } while ($stmt->fetch());
    
    return $shortCode;
}

/**
 * Get client IP address
 */
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

/**
 * Get device type from user agent
 */
function getDeviceType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone', 'BlackBerry', 'Opera Mini'];
    
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return 'mobile';
        }
    }
    
    return 'desktop';
}

/**
 * Get geolocation data for IP address
 */
function getGeolocationData($ip) {
    // Skip private IPs
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return [
            'country' => 'Private Network',
            'country_code' => 'PRIVATE',
            'region' => '',
            'city' => '',
            'zip' => '',
            'lat' => null,
            'lon' => null,
            'timezone' => '',
            'isp' => '',
            'org' => '',
            'as' => ''
        ];
    }
    
    // Use ipapi.co for geolocation (free tier available)
    $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'IP Logger/1.0'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return [
            'country' => 'Unknown',
            'country_code' => 'UN',
            'region' => '',
            'city' => '',
            'zip' => '',
            'lat' => null,
            'lon' => null,
            'timezone' => '',
            'isp' => '',
            'org' => '',
            'as' => ''
        ];
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['status']) && $data['status'] === 'success') {
        return [
            'country' => $data['country'] ?? 'Unknown',
            'country_code' => $data['countryCode'] ?? 'UN',
            'region' => $data['regionName'] ?? '',
            'city' => $data['city'] ?? '',
            'zip' => $data['zip'] ?? '',
            'lat' => $data['lat'] ?? null,
            'lon' => $data['lon'] ?? null,
            'timezone' => $data['timezone'] ?? '',
            'isp' => $data['isp'] ?? '',
            'org' => $data['org'] ?? '',
            'as' => $data['as'] ?? ''
        ];
    }
    
    return [
        'country' => 'Unknown',
        'country_code' => 'UN',
        'region' => '',
        'city' => '',
        'zip' => '',
        'lat' => null,
        'lon' => null,
        'timezone' => '',
        'isp' => '',
        'org' => '',
        'as' => ''
    ];
}

/**
 * Check if link is expired
 */
function isLinkExpired($expiryDate) {
    if (!$expiryDate) {
        return false; // No expiration set
    }
    
    return strtotime($expiryDate) < time();
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

/**
 * Get time ago string
 */
function timeAgo($date) {
    $time = time() - strtotime($date);
    
    if ($time < 60) {
        return $time . ' seconds ago';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($time / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting check
 */
function checkRateLimit($ip, $action = 'general', $limit = 60, $window = 60) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM rate_limits 
        WHERE ip_address = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$ip, $action, $window]);
    $result = $stmt->fetch();
    
    if ($result['count'] >= $limit) {
        return false; // Rate limit exceeded
    }
    
    // Log this request
    $stmt = $conn->prepare("
        INSERT INTO rate_limits (ip_address, action, created_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$ip, $action]);
    
    return true; // Within rate limit
}

/**
 * Clean expired data
 */
function cleanExpiredData() {
    global $conn;
    
    // Get data retention period from settings
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'data_retention_days'");
    $stmt->execute();
    $result = $stmt->fetch();
    $retentionDays = $result ? (int)$result['setting_value'] : 90;
    
    // Delete expired links
    $stmt = $conn->prepare("
        DELETE FROM links 
        WHERE expiry_date IS NOT NULL AND expiry_date < NOW()
    ");
    $stmt->execute();
    
    // Delete old target data
    $stmt = $conn->prepare("
        DELETE FROM targets 
        WHERE clicked_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$retentionDays]);
    
    // Clean up rate limits
    $stmt = $conn->prepare("
        DELETE FROM rate_limits 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute();
}

/**
 * Get statistics for a link
 */
function getLinkStats($linkId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_clicks,
            COUNT(DISTINCT ip_address) as unique_ips,
            COUNT(DISTINCT country) as countries,
            COUNT(CASE WHEN device_type = 'mobile' THEN 1 END) as mobile_clicks,
            COUNT(CASE WHEN device_type = 'desktop' THEN 1 END) as desktop_clicks
        FROM targets 
        WHERE link_id = ?
    ");
    $stmt->execute([$linkId]);
    
    return $stmt->fetch();
}

/**
 * Export data to CSV
 */
function exportToCSV($linkId, $password) {
    global $conn;
    
    // Verify password
    $stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();
    
    if (!$link || !password_verify($password, $link['password'])) {
        return false;
    }
    
    // Get targets
    $stmt = $conn->prepare("
        SELECT * FROM targets 
        WHERE link_id = ? 
        ORDER BY clicked_at DESC
    ");
    $stmt->execute([$linkId]);
    $targets = $stmt->fetchAll();
    
    // Generate CSV
    $csv = "IP Address,Device Type,Country,City,ISP,Clicked At\n";
    
    foreach ($targets as $target) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s"' . "\n",
            $target['ip_address'],
            $target['device_type'],
            $target['country'] ?? 'Unknown',
            $target['city'] ?? 'Unknown',
            $target['isp'] ?? 'Unknown',
            $target['clicked_at']
        );
    }
    
    return $csv;
}

/**
 * Log activity
 */
function logActivity($action, $details = '') {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO audit_log (action, details, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $action,
        $details,
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Get setting value
 */
function getSetting($key, $default = '') {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting value
 */
function updateSetting($key, $value) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Display message from session
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        return sprintf(
            '<div class="alert alert-%s alert-dismissible fade show" role="alert">%s<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>',
            $type,
            htmlspecialchars($message)
        );
    }
    
    return '';
}
?>
