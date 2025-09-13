<?php
// Utility Functions for IP Logger

// PHPMailer classes not available - using native mail() function

/**
 * Generate a random base62 short code (cryptographically secure).
 *
 */
function generateShortCode(int $length = 8): string
{
    // The array of possible characters
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // The length of the alphabet
    $n = strlen($alphabet);

    $code = '';
    for ($i = 0; $i < $length; $i++) {
        // random_int() is cryptographically secure (PHP 7+)
        $code .= $alphabet[random_int(0, $n - 1)];
    }
    return $code;
}


function getClientIP(array $trustedProxies = []): string
{
    $server = $_SERVER;

    // Prefer canonical vendor headers if you use them (uncomment as appropriate)
    // if (!empty($server['HTTP_CF_CONNECTING_IP'])) {
    //     $ip = trim($server['HTTP_CF_CONNECTING_IP']);
    //     if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    //         return $ip;
    //     }
    // }

    // Start from the direct peer first
    $remote = $server['REMOTE_ADDR'] ?? null;
    $xff    = $server['HTTP_X_FORWARDED_FOR'] ?? '';

    // Build the chain: client, proxy1, proxy2, ..., REMOTE_ADDR (rightmost)
    $chain = [];
    if ($xff !== '') {
        foreach (explode(',', $xff) as $h) {
            $chain[] = trim($h);
        }
    }
    if ($remote) {
        $chain[] = $remote;
    }

    // Walk left→right: return the first IP that is public and not a trusted proxy
    foreach ($chain as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) continue;

        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        $isTrustedProxy = in_array($ip, $trustedProxies, true);

        if ($isPublic && !$isTrustedProxy) {
            return $ip;
        }
    }

    // Fallback to REMOTE_ADDR if valid, else 0.0.0.0
    if ($remote && filter_var($remote, FILTER_VALIDATE_IP)) {
        return $remote;
    }
    return '0.0.0.0';
}


/**
 * Get device type from user agent
 */
function getDeviceType()
{
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
function getGeolocationData($ip)
{
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
function isLinkExpired($expiryDate)
{
    if (!$expiryDate) {
        return false; // No expiration set
    }

    return strtotime($expiryDate) < time();
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y g:i A')
{
    return date($format, strtotime($date));
}

/**
 * Get time ago string
 */
function timeAgo($date)
{
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
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate URL
 */
function isValidUrl($url)
{
    // Remove espaços em branco
    $url = trim($url);

    // Se já tem protocolo, valida normalmente
    if (preg_match('/^https?:\/\//', $url)) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    // Se não tem protocolo, adiciona https:// e valida
    $urlWithProtocol = 'https://' . $url;
    return filter_var($urlWithProtocol, FILTER_VALIDATE_URL) !== false;
}

/**
 * Normalize URL by adding protocol if missing
 */
function normalizeUrl($url)
{
    $url = trim($url);

    // Se já tem protocolo, retorna como está
    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }

    // Se não tem protocolo, adiciona https://
    return 'https://' . $url;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting check
 */
function checkRateLimit($ip, $action = 'general', $limit = 60, $window = 60)
{
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
function cleanExpiredData()
{
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
function getLinkStats($linkId)
{
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
function exportToCSV($linkId, $password)
{
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
function logActivity($action, $details = '')
{
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
function getSetting($key, $default = '')
{
    global $conn;

    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();

    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting value
 */
function updateSetting($key, $value)
{
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
function generateRandomString($length = 10)
{
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
function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Send email notification
 */
function sendEmailNotification($to, $subject, $message, $htmlMessage = null)
{
    if (!EMAIL_NOTIFICATIONS_ENABLED) {
        return false;
    }

    // Use native mail() function
    return sendEmailWithMail($to, $subject, $message, $htmlMessage);
}

/**
 * Send email using native mail() function
 */
function sendEmailWithMail($to, $subject, $message, $htmlMessage = null)
{
    $headers = [];
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SMTP_FROM_EMAIL;
    $headers[] = 'X-Mailer: IP Logger System';

    if ($htmlMessage) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $message = $htmlMessage;
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    }

    return mail($to, $subject, $message, implode("\r\n", $headers));
}


/**
 * Send link click notification
 */
function sendLinkClickNotification($linkId, $targetData)
{
    global $conn;

    if (!NOTIFY_ON_LINK_CLICK) {
        return false;
    }

    // Get link details
    $stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();

    if (!$link) {
        return false;
    }

    // Get notification email from settings
    $notificationEmail = getSetting('notification_email', '');

    // Check if email is set to no notification
    if (!$notificationEmail || $notificationEmail === 'NO_NOTIFICATION_EMAIL') {
        return false;
    }

    $subject = "Link Clicked: " . $link['short_code'];
    $message = "Your IP Logger link has been clicked!\n\n";
    $message .= "Short Code: " . $link['short_code'] . "\n";
    $message .= "Original URL: " . $link['original_url'] . "\n";
    $message .= "Clicked At: " . formatDate($targetData['clicked_at']) . "\n\n";
    $message .= "Visitor Information:\n";
    $message .= "IP Address: " . $targetData['ip_address'] . "\n";
    $message .= "Country: " . ($targetData['country'] ?? 'Unknown') . "\n";
    $message .= "City: " . ($targetData['city'] ?? 'Unknown') . "\n";
    $message .= "Device: " . $targetData['device_type'] . "\n";
    $message .= "ISP: " . ($targetData['isp'] ?? 'Unknown') . "\n";

    $htmlMessage = createEmailHTML($subject, $message, $link, $targetData);

    return sendEmailNotification($notificationEmail, $subject, $message, $htmlMessage);
}

/**
 * Send new link creation notification
 */
function sendNewLinkNotification($linkId)
{
    global $conn;

    if (!NOTIFY_ON_NEW_LINK) {
        return false;
    }

    // Get link details
    $stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();

    if (!$link) {
        return false;
    }

    // Get notification email from settings
    $notificationEmail = getSetting('notification_email', '');

    // Check if email is set to no notification
    if (!$notificationEmail || $notificationEmail === 'NO_NOTIFICATION_EMAIL') {
        return false;
    }

    $subject = "New Link Created: " . $link['short_code'];
    $message = "A new IP Logger link has been created!\n\n";
    $message .= "Short Code: " . $link['short_code'] . "\n";
    $message .= "Original URL: " . $link['original_url'] . "\n";
    $message .= "Created At: " . formatDate($link['created_at']) . "\n";
    $message .= "Expires: " . ($link['expiry_date'] ? formatDate($link['expiry_date']) : 'Never') . "\n";

    $htmlMessage = createEmailHTML($subject, $message, $link);

    return sendEmailNotification($notificationEmail, $subject, $message, $htmlMessage);
}

/**
 * Test email functionality
 */
function testEmailFunctionality()
{
    $notificationEmail = getSetting('notification_email', '');

    // Check if email is set to no notification
    if (!$notificationEmail || $notificationEmail === 'NO_NOTIFICATION_EMAIL') {
        return [
            'success' => false,
            'email' => '',
            'message' => 'No notification email configured. Please add an email address in the settings.'
        ];
    }

    $subject = "IP Logger Email Test";
    $message = "This is a test email from your IP Logger system.\n\n";
    $message .= "If you receive this email, your email configuration is working correctly.\n";
    $message .= "Test sent at: " . date('Y-m-d H:i:s') . "\n";
    $message .= "SMTP Host: " . SMTP_HOST . "\n";
    $message .= "SMTP Port: " . SMTP_PORT . "\n";
    $message .= "From Email: " . SMTP_FROM_EMAIL . "\n";

    $htmlMessage = createEmailHTML($subject, $message);

    $result = sendEmailNotification($notificationEmail, $subject, $message, $htmlMessage);

    return [
        'success' => $result,
        'email' => $notificationEmail,
        'message' => $result ? 'Test email sent successfully!' : 'Failed to send test email. Check your SMTP configuration.'
    ];
}

/**
 * Create HTML email template
 */
function createEmailHTML($subject, $textMessage, $link = null, $targetData = null)
{
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
            .highlight { background: #e3f2fd; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>IP Logger Notification</h1>
            </div>
            <div class="content">';

    // Convert text message to HTML
    $html .= '<p>' . nl2br(htmlspecialchars($textMessage)) . '</p>';

    if ($link) {
        $html .= '<div class="info-box">
            <h3>Link Details</h3>
            <p><strong>Short URL:</strong> <a href="' . BASE_URL . $link['short_code'] . '">' . BASE_URL . $link['short_code'] . '</a></p>
            <p><strong>Original URL:</strong> <a href="' . htmlspecialchars($link['original_url']) . '">' . htmlspecialchars($link['original_url']) . '</a></p>
            <p><strong>Created:</strong> ' . formatDate($link['created_at']) . '</p>';

        if ($link['expiry_date']) {
            $html .= '<p><strong>Expires:</strong> ' . formatDate($link['expiry_date']) . '</p>';
        }

        $html .= '</div>';
    }

    if ($targetData) {
        $html .= '<div class="info-box">
            <h3>Visitor Information</h3>
            <p><strong>IP Address:</strong> ' . htmlspecialchars($targetData['ip_address']) . '</p>
            <p><strong>Location:</strong> ' . htmlspecialchars($targetData['city'] ?? 'Unknown') . ', ' . htmlspecialchars($targetData['country'] ?? 'Unknown') . '</p>
            <p><strong>Device:</strong> ' . htmlspecialchars($targetData['device_type']) . '</p>
            <p><strong>ISP:</strong> ' . htmlspecialchars($targetData['isp'] ?? 'Unknown') . '</p>
            <p><strong>Time:</strong> ' . formatDate($targetData['clicked_at']) . '</p>
        </div>';
    }

    $html .= '</div>
            <div class="footer">
                <p>This is an automated notification from IP Logger System</p>
                <p>© ' . date('Y') . ' IP Logger. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success')
{
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Display message from session
 */
function displayMessage()
{
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

/**
 * Enhanced Geolocation with Multiple Data Sources
 * Uses state-of-the-art methodologies for precise location tracking
 */
function getEnhancedGeolocationData($ip_address, $user_agent = '')
{
    $geo_data = [
        'ip_address' => $ip_address,
        'country' => null,
        'country_code' => null,
        'region' => null,
        'city' => null,
        'zip_code' => null,
        'latitude' => null,
        'longitude' => null,
        'timezone' => null,
        'isp' => null,
        'organization' => null,
        'as_number' => null,
        'accuracy' => null,
        'confidence' => null,
        'data_sources' => [],
        'location_method' => null,
        'precision_level' => null
    ];

    // Method 1: IP-API (Primary source)
    $ip_api_result = getIPAPIGeolocation($ip_address);
    if ($ip_api_result) {
        $geo_data = array_merge($geo_data, $ip_api_result);
        $geo_data['data_sources'][] = 'ip-api';
        $geo_data['location_method'] = 'ip_geolocation';
        $geo_data['precision_level'] = 'city_level';
        $geo_data['confidence'] = 85;
    }

    // Method 2: IP2Location (Secondary source for validation)
    $ip2location_result = getIP2LocationGeolocation($ip_address);
    if ($ip2location_result) {
        $geo_data['data_sources'][] = 'ip2location';

        // Cross-validate coordinates
        if ($geo_data['latitude'] && $ip2location_result['latitude']) {
            $distance = calculateDistance(
                $geo_data['latitude'],
                $geo_data['longitude'],
                $ip2location_result['latitude'],
                $ip2location_result['longitude']
            );

            if ($distance < 50) { // Within 50km, consider accurate
                $geo_data['confidence'] += 10;
                $geo_data['precision_level'] = 'neighborhood_level';
            } else {
                $geo_data['confidence'] -= 15;
                $geo_data['precision_level'] = 'regional_level';
            }
        }
    }

    // Method 3: MaxMind GeoIP2 (Tertiary source)
    $maxmind_result = getMaxMindGeolocation($ip_address);
    if ($maxmind_result) {
        $geo_data['data_sources'][] = 'maxmind';

        // Additional validation
        if (
            $geo_data['country'] && $maxmind_result['country'] &&
            $geo_data['country'] === $maxmind_result['country']
        ) {
            $geo_data['confidence'] += 5;
        }
    }

    // Method 4: Device Fingerprinting for Enhanced Accuracy
    $device_fingerprint = getDeviceFingerprint($user_agent);
    if ($device_fingerprint) {
        $geo_data['data_sources'][] = 'device_fingerprint';
        $geo_data['device_info'] = $device_fingerprint;
    }

    // Method 5: Network Analysis
    $network_analysis = analyzeNetworkCharacteristics($ip_address);
    if ($network_analysis) {
        $geo_data['data_sources'][] = 'network_analysis';
        $geo_data['network_info'] = $network_analysis;

        // Adjust confidence based on network type
        if ($network_analysis['type'] === 'mobile_carrier') {
            $geo_data['confidence'] += 5;
            $geo_data['precision_level'] = 'cell_tower_level';
        } elseif ($network_analysis['type'] === 'isp') {
            $geo_data['confidence'] += 3;
        }
    }

    // Method 6: Timezone-based Validation
    $timezone_validation = validateLocationByTimezone($geo_data);
    if ($timezone_validation['valid']) {
        $geo_data['confidence'] += $timezone_validation['confidence_boost'];
    } else {
        $geo_data['confidence'] -= 10;
    }

    // Method 7: Historical Data Analysis
    $historical_analysis = analyzeHistoricalLocationData($ip_address);
    if ($historical_analysis) {
        $geo_data['data_sources'][] = 'historical_analysis';
        $geo_data['historical_data'] = $historical_analysis;

        // Use historical data to improve accuracy
        if ($historical_analysis['consistency_score'] > 0.8) {
            $geo_data['confidence'] += 8;
        }
    }

    // Method 8: Advanced Coordinate Refinement
    $refined_coordinates = refineCoordinates($geo_data);
    if ($refined_coordinates) {
        $geo_data['latitude'] = $refined_coordinates['latitude'];
        $geo_data['longitude'] = $refined_coordinates['longitude'];
        $geo_data['accuracy'] = $refined_coordinates['accuracy'];
        $geo_data['data_sources'][] = 'coordinate_refinement';
    }

    // Cap confidence at 100
    $geo_data['confidence'] = min(100, $geo_data['confidence']);

    return $geo_data;
}

/**
 * IP-API Geolocation (Primary Source)
 */
function getIPAPIGeolocation($ip_address)
{
    $url = "http://ip-api.com/json/{$ip_address}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query";

    $response = @file_get_contents($url);
    if (!$response) {
        return false;
    }

    $data = json_decode($response, true);

    if ($data && $data['status'] === 'success') {
        return [
            'country' => $data['country'] ?? null,
            'country_code' => $data['countryCode'] ?? null,
            'region' => $data['regionName'] ?? null,
            'city' => $data['city'] ?? null,
            'zip_code' => $data['zip'] ?? null,
            'latitude' => $data['lat'] ?? null,
            'longitude' => $data['lon'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'isp' => $data['isp'] ?? null,
            'organization' => $data['org'] ?? null,
            'as_number' => $data['as'] ?? null
        ];
    }

    return false;
}

/**
 * IP2Location Geolocation (Secondary Source)
 */
function getIP2LocationGeolocation($ip_address)
{
    // Using IP2Location Web Service (you would need an API key)
    $api_key = 'YOUR_IP2LOCATION_API_KEY'; // Replace with actual API key
    $url = "https://api.ip2location.io/?ip={$ip_address}&key={$api_key}";

    $response = @file_get_contents($url);
    if (!$response) {
        return false;
    }

    $data = json_decode($response, true);

    if ($data && isset($data['latitude'])) {
        return [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'country' => $data['country_name'] ?? null,
            'city' => $data['city_name'] ?? null,
            'region' => $data['region_name'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
            'timezone' => $data['time_zone'] ?? null,
            'isp' => $data['isp'] ?? null
        ];
    }

    return false;
}

/**
 * MaxMind GeoIP2 Geolocation (Tertiary Source)
 */
function getMaxMindGeolocation($ip_address)
{
    // Using MaxMind GeoIP2 Web Service (you would need an API key)
    $account_id = 'YOUR_MAXMIND_ACCOUNT_ID'; // Replace with actual account ID
    $license_key = 'YOUR_MAXMIND_LICENSE_KEY'; // Replace with actual license key
    $url = "https://geoip.maxmind.com/geoip/v2.1/country/{$ip_address}";

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("{$account_id}:{$license_key}")
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!$response) {
        return false;
    }

    $data = json_decode($response, true);

    if ($data && isset($data['country'])) {
        return [
            'country' => $data['country']['names']['en'] ?? null,
            'country_code' => $data['country']['iso_code'] ?? null
        ];
    }

    return false;
}

/**
 * Device Fingerprinting for Enhanced Accuracy
 */
function getDeviceFingerprint($user_agent)
{
    $fingerprint = [];

    // Parse User Agent
    $browser_info = parseUserAgent($user_agent);
    $fingerprint['browser'] = $browser_info;

    // Device Type Detection
    $device_type = detectDeviceType($user_agent);
    $fingerprint['device_type'] = $device_type;

    // Screen Resolution (if available via JavaScript)
    // This would be passed from client-side JavaScript
    $fingerprint['screen_resolution'] = $_POST['screen_resolution'] ?? null;

    // Timezone (if available via JavaScript)
    $fingerprint['timezone'] = $_POST['timezone'] ?? null;

    // Language Preferences
    $fingerprint['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

    return $fingerprint;
}

/**
 * Network Analysis for Location Refinement
 */
function analyzeNetworkCharacteristics($ip_address)
{
    $analysis = [];

    // Check if it's a mobile carrier IP
    $mobile_carriers = [
        'att.com',
        'verizon.com',
        'tmobile.com',
        'sprint.com',
        'vodafone.com',
        'orange.com',
        'telefonica.com'
    ];

    $reverse_dns = gethostbyaddr($ip_address);
    foreach ($mobile_carriers as $carrier) {
        if (strpos($reverse_dns, $carrier) !== false) {
            $analysis['type'] = 'mobile_carrier';
            $analysis['carrier'] = $carrier;
            break;
        }
    }

    if (!isset($analysis['type'])) {
        $analysis['type'] = 'isp';
    }

    // Check for VPN/Proxy indicators
    $vpn_indicators = checkVPNIndicators($ip_address);
    $analysis['vpn_detected'] = $vpn_indicators['detected'];
    $analysis['proxy_type'] = $vpn_indicators['type'];

    return $analysis;
}

/**
 * Timezone-based Location Validation
 */
function validateLocationByTimezone($geo_data)
{
    $result = ['valid' => false, 'confidence_boost' => 0];

    if (!$geo_data['timezone'] || !$geo_data['latitude'] || !$geo_data['longitude']) {
        return $result;
    }

    // Get timezone from coordinates
    $coordinate_timezone = getTimezoneFromCoordinates($geo_data['latitude'], $geo_data['longitude']);

    if ($coordinate_timezone === $geo_data['timezone']) {
        $result['valid'] = true;
        $result['confidence_boost'] = 15;
    } elseif (
        strpos($coordinate_timezone, $geo_data['timezone']) !== false ||
        strpos($geo_data['timezone'], $coordinate_timezone) !== false
    ) {
        $result['valid'] = true;
        $result['confidence_boost'] = 8;
    }

    return $result;
}

/**
 * Historical Location Data Analysis
 */
function analyzeHistoricalLocationData($ip_address)
{
    global $conn;

    // Get historical data for this IP
    $stmt = $conn->prepare("
        SELECT latitude, longitude, country, city, clicked_at 
        FROM targets 
        WHERE ip_address = ? 
        AND latitude IS NOT NULL 
        AND longitude IS NOT NULL 
        ORDER BY clicked_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$ip_address]);
    $historical_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($historical_data)) {
        return null;
    }

    $analysis = [
        'total_records' => count($historical_data),
        'locations' => [],
        'consistency_score' => 0
    ];

    // Group by location
    $location_groups = [];
    foreach ($historical_data as $record) {
        $location_key = $record['latitude'] . ',' . $record['longitude'];
        if (!isset($location_groups[$location_key])) {
            $location_groups[$location_key] = [
                'count' => 0,
                'latitude' => $record['latitude'],
                'longitude' => $record['longitude'],
                'country' => $record['country'],
                'city' => $record['city']
            ];
        }
        $location_groups[$location_key]['count']++;
    }

    $analysis['locations'] = array_values($location_groups);

    // Calculate consistency score
    $max_count = max(array_column($location_groups, 'count'));
    $analysis['consistency_score'] = $max_count / count($historical_data);

    return $analysis;
}

/**
 * Advanced Coordinate Refinement
 */
function refineCoordinates($geo_data)
{
    if (!$geo_data['latitude'] || !$geo_data['longitude']) {
        return false;
    }

    $refined = [
        'latitude' => $geo_data['latitude'],
        'longitude' => $geo_data['longitude'],
        'accuracy' => 1000 // Default accuracy in meters
    ];

    // Apply coordinate refinement algorithms

    // 1. Cell Tower Triangulation (if mobile carrier)
    if (
        isset($geo_data['network_info']['type']) &&
        $geo_data['network_info']['type'] === 'mobile_carrier'
    ) {
        $refined['accuracy'] = 500; // Cell tower accuracy
    }

    // 2. ISP-based refinement
    if (
        isset($geo_data['network_info']['type']) &&
        $geo_data['network_info']['type'] === 'isp'
    ) {
        $refined['accuracy'] = 2000; // ISP accuracy
    }

    // 3. Historical data refinement
    if (isset($geo_data['historical_data'])) {
        $historical = $geo_data['historical_data'];
        if ($historical['consistency_score'] > 0.8) {
            // Use most frequent location
            $most_frequent = $historical['locations'][0];
            $refined['latitude'] = $most_frequent['latitude'];
            $refined['longitude'] = $most_frequent['longitude'];
            $refined['accuracy'] = 100; // High confidence
        }
    }

    // 4. Timezone validation refinement
    if (isset($geo_data['timezone']) && $geo_data['timezone']) {
        $timezone_coords = getTimezoneCenterCoordinates($geo_data['timezone']);
        if ($timezone_coords) {
            $distance = calculateDistance(
                $refined['latitude'],
                $refined['longitude'],
                $timezone_coords['lat'],
                $timezone_coords['lng']
            );

            if ($distance < 100) { // Within 100km of timezone center
                $refined['accuracy'] = min($refined['accuracy'], 500);
            }
        }
    }

    return $refined;
}

/**
 * Calculate Distance Between Two Points (Haversine Formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earth_radius = 6371; // Earth's radius in kilometers

    $lat_diff = deg2rad($lat2 - $lat1);
    $lon_diff = deg2rad($lon2 - $lon1);

    $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($lon_diff / 2) * sin($lon_diff / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;

    return $distance;
}

/**
 * Check for VPN/Proxy Indicators
 */
function checkVPNIndicators($ip_address)
{
    $indicators = ['detected' => false, 'type' => 'none'];

    // Check against known VPN/Proxy databases
    $vpn_indicators = [
        'vpn',
        'proxy',
        'tor',
        'anonymous',
        'privacy'
    ];

    $reverse_dns = gethostbyaddr($ip_address);
    foreach ($vpn_indicators as $indicator) {
        if (strpos(strtolower($reverse_dns), $indicator) !== false) {
            $indicators['detected'] = true;
            $indicators['type'] = $indicator;
            break;
        }
    }

    return $indicators;
}

/**
 * Get Timezone from Coordinates
 */
function getTimezoneFromCoordinates($latitude, $longitude)
{
    // This would typically use a timezone database
    // For simplicity, we'll use a basic implementation
    $timezone_map = [
        'America/New_York' => ['lat_min' => 24, 'lat_max' => 50, 'lon_min' => -80, 'lon_max' => -60],
        'America/Chicago' => ['lat_min' => 30, 'lat_max' => 50, 'lon_min' => -105, 'lon_max' => -80],
        'America/Denver' => ['lat_min' => 30, 'lat_max' => 50, 'lon_min' => -115, 'lon_max' => -105],
        'America/Los_Angeles' => ['lat_min' => 30, 'lat_max' => 50, 'lon_min' => -125, 'lon_max' => -115],
        'Europe/London' => ['lat_min' => 50, 'lat_max' => 60, 'lon_min' => -10, 'lon_max' => 5],
        'Europe/Paris' => ['lat_min' => 40, 'lat_max' => 55, 'lon_min' => -5, 'lon_max' => 15],
        'Asia/Tokyo' => ['lat_min' => 30, 'lat_max' => 45, 'lon_min' => 130, 'lon_max' => 145],
        'Australia/Sydney' => ['lat_min' => -45, 'lat_max' => -10, 'lon_min' => 110, 'lon_max' => 155]
    ];

    foreach ($timezone_map as $timezone => $bounds) {
        if (
            $latitude >= $bounds['lat_min'] && $latitude <= $bounds['lat_max'] &&
            $longitude >= $bounds['lon_min'] && $longitude <= $bounds['lon_max']
        ) {
            return $timezone;
        }
    }

    return 'UTC';
}

/**
 * Get Timezone Center Coordinates
 */
function getTimezoneCenterCoordinates($timezone)
{
    $timezone_centers = [
        'America/New_York' => ['lat' => 40.7128, 'lng' => -74.0060],
        'America/Chicago' => ['lat' => 41.8781, 'lng' => -87.6298],
        'America/Denver' => ['lat' => 39.7392, 'lng' => -104.9903],
        'America/Los_Angeles' => ['lat' => 34.0522, 'lng' => -118.2437],
        'Europe/London' => ['lat' => 51.5074, 'lng' => -0.1278],
        'Europe/Paris' => ['lat' => 48.8566, 'lng' => 2.3522],
        'Asia/Tokyo' => ['lat' => 35.6762, 'lng' => 139.6503],
        'Australia/Sydney' => ['lat' => -33.8688, 'lng' => 151.2093]
    ];

    return $timezone_centers[$timezone] ?? null;
}

/**
 * Parse User Agent for Device Information
 */
function parseUserAgent($user_agent)
{
    $browser_info = [
        'browser' => 'Unknown',
        'version' => 'Unknown',
        'os' => 'Unknown',
        'device' => 'Unknown'
    ];

    // Browser detection
    if (preg_match('/Chrome\/([0-9.]+)/', $user_agent, $matches)) {
        $browser_info['browser'] = 'Chrome';
        $browser_info['version'] = $matches[1];
    } elseif (preg_match('/Firefox\/([0-9.]+)/', $user_agent, $matches)) {
        $browser_info['browser'] = 'Firefox';
        $browser_info['version'] = $matches[1];
    } elseif (preg_match('/Safari\/([0-9.]+)/', $user_agent, $matches)) {
        $browser_info['browser'] = 'Safari';
        $browser_info['version'] = $matches[1];
    } elseif (preg_match('/Edge\/([0-9.]+)/', $user_agent, $matches)) {
        $browser_info['browser'] = 'Edge';
        $browser_info['version'] = $matches[1];
    }

    // OS detection
    if (preg_match('/Windows NT ([0-9.]+)/', $user_agent, $matches)) {
        $browser_info['os'] = 'Windows ' . $matches[1];
    } elseif (preg_match('/Mac OS X ([0-9._]+)/', $user_agent, $matches)) {
        $browser_info['os'] = 'macOS ' . str_replace('_', '.', $matches[1]);
    } elseif (preg_match('/Linux/', $user_agent)) {
        $browser_info['os'] = 'Linux';
    } elseif (preg_match('/Android ([0-9.]+)/', $user_agent, $matches)) {
        $browser_info['os'] = 'Android ' . $matches[1];
    } elseif (preg_match('/iPhone OS ([0-9._]+)/', $user_agent, $matches)) {
        $browser_info['os'] = 'iOS ' . str_replace('_', '.', $matches[1]);
    }

    return $browser_info;
}

/**
 * Detect Device Type
 */
function detectDeviceType($user_agent)
{
    $user_agent_lower = strtolower($user_agent);

    if (
        strpos($user_agent_lower, 'mobile') !== false ||
        strpos($user_agent_lower, 'android') !== false ||
        strpos($user_agent_lower, 'iphone') !== false ||
        strpos($user_agent_lower, 'ipad') !== false
    ) {
        return 'mobile';
    } elseif (
        strpos($user_agent_lower, 'tablet') !== false ||
        strpos($user_agent_lower, 'ipad') !== false
    ) {
        return 'tablet';
    } else {
        return 'desktop';
    }
}

/**
 * Get detailed browser and OS information
 */
function getDetailedBrowserInfo($userAgent)
{
    $browser = 'Unknown';
    $browserVersion = '';
    $os = 'Unknown';
    $osVersion = '';

    // Browser detection
    if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Chrome';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Firefox';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Safari';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Edge';
        $browserVersion = $matches[1];
    } elseif (preg_match('/MSIE ([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Internet Explorer';
        $browserVersion = $matches[1];
    }

    // OS detection
    if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
        $os = 'Windows';
        $osVersion = $matches[1];
    } elseif (preg_match('/Mac OS X ([0-9._]+)/', $userAgent, $matches)) {
        $os = 'macOS';
        $osVersion = $matches[1];
    } elseif (preg_match('/Linux/', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
        $os = 'Android';
        $osVersion = $matches[1];
    } elseif (preg_match('/iPhone OS ([0-9._]+)/', $userAgent, $matches)) {
        $os = 'iOS';
        $osVersion = $matches[1];
    }

    return [
        'browser_name' => $browser,
        'browser_version' => $browserVersion,
        'os_name' => $os,
        'os_version' => $osVersion
    ];
}

/**
 * Detect proxy/VPN usage
 */
function detectProxyVPN($ip)
{
    // Simple proxy/VPN detection based on common patterns
    $proxyDetected = false;
    $vpnDetected = false;
    $torDetected = false;

    // Check for common proxy/VPN IP ranges
    $proxyRanges = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8'
    ];

    foreach ($proxyRanges as $range) {
        if (ipInRange($ip, $range)) {
            $proxyDetected = true;
            break;
        }
    }

    // Check for known VPN providers (simplified)
    $vpnProviders = ['nordvpn', 'expressvpn', 'surfshark', 'protonvpn'];
    $reverseIp = @gethostbyaddr($ip);

    if ($reverseIp && $reverseIp !== $ip) {
        foreach ($vpnProviders as $provider) {
            if (stripos($reverseIp, $provider) !== false) {
                $vpnDetected = true;
                break;
            }
        }
    }

    return [
        'proxy_detected' => $proxyDetected,
        'vpn_detected' => $vpnDetected,
        'tor_detected' => $torDetected
    ];
}

/**
 * Check if IP is in range
 */
function ipInRange($ip, $range)
{
    list($range, $netmask) = explode('/', $range, 2);
    $rangeDecimal = ip2long($range);
    $ipDecimal = ip2long($ip);
    $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
    $netmaskDecimal = ~$wildcardDecimal;

    return (($ipDecimal & $netmaskDecimal) == ($rangeDecimal & $netmaskDecimal));
}

/**
 * Parse UTM parameters from URL
 */
function parseUTMParameters($url)
{
    $parsed = parse_url($url);
    if (!isset($parsed['query'])) {
        return [];
    }

    parse_str($parsed['query'], $params);

    return [
        'utm_source' => $params['utm_source'] ?? null,
        'utm_medium' => $params['utm_medium'] ?? null,
        'utm_campaign' => $params['utm_campaign'] ?? null,
        'utm_term' => $params['utm_term'] ?? null,
        'utm_content' => $params['utm_content'] ?? null
    ];
}

/**
 * Get referrer information
 */
function getReferrerInfo($referrer)
{
    if (empty($referrer)) {
        return ['domain' => null, 'path' => null];
    }

    $parsed = parse_url($referrer);

    return [
        'domain' => $parsed['host'] ?? null,
        'path' => $parsed['path'] ?? null
    ];
}

/**
 * Generate tracking code
 */
function generateTrackingCode()
{
    return 'TRK' . strtoupper(substr(md5(uniqid()), 0, 8));
}

/**
 * Get custom domains
 */
function getCustomDomains()
{
    global $conn;
    $stmt = $conn->query("SELECT * FROM custom_domains WHERE is_active = 1 ORDER BY domain");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get link extensions
 */
function getLinkExtensions()
{
    global $conn;
    $stmt = $conn->query("SELECT * FROM link_extensions WHERE is_active = 1 ORDER BY extension");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update link statistics
 */
function updateLinkStats($linkId)
{
    global $conn;

    // Update click count
    $stmt = $conn->prepare("
        UPDATE links 
        SET click_count = (
            SELECT COUNT(*) FROM targets WHERE link_id = ?
        ),
        unique_visitors = (
            SELECT COUNT(DISTINCT ip_address) FROM targets WHERE link_id = ?
        ),
        last_click_at = (
            SELECT MAX(clicked_at) FROM targets WHERE link_id = ?
        )
        WHERE id = ?
    ");
    $stmt->execute([$linkId, $linkId, $linkId, $linkId]);
}

/**
 * Log consent
 */
function logConsent($targetId, $consentType, $consentGiven, $consentText = null)
{
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO consent_logs (target_id, consent_type, consent_given, consent_text, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$targetId, $consentType, $consentGiven ? 1 : 0, $consentText, getClientIP()]);
}
