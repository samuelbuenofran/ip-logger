<?php
// Utility Functions for AdminLTE IP Logger

/**
 * Generate a random base62 short code (cryptographically secure).
 */
function generateShortCode(int $length = 8): string
{
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $n = strlen($alphabet);
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $n - 1)];
    }
    return $code;
}

/**
 * Get client IP address
 */
function getClientIP(array $trustedProxies = []): string
{
    $server = $_SERVER;
    $remote = $server['REMOTE_ADDR'] ?? null;
    $xff    = $server['HTTP_X_FORWARDED_FOR'] ?? '';

    $chain = [];
    if ($xff !== '') {
        foreach (explode(',', $xff) as $h) {
            $chain[] = trim($h);
        }
    }
    if ($remote) {
        $chain[] = $remote;
    }

    foreach ($chain as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) continue;

        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        $isTrustedProxy = in_array($ip, $trustedProxies, true);

        if ($isPublic && !$isTrustedProxy) {
            return $ip;
        }
    }

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
        return false;
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
    $url = trim($url);
    if (preg_match('/^https?:\/\//', $url)) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    $urlWithProtocol = 'https://' . $url;
    return filter_var($urlWithProtocol, FILTER_VALIDATE_URL) !== false;
}

/**
 * Normalize URL by adding protocol if missing
 */
function normalizeUrl($url)
{
    $url = trim($url);
    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }
    return 'https://' . $url;
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

        $alertClass = '';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            default:
                $alertClass = 'alert-info';
        }

        return sprintf(
            '<div class="alert %s alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>%s</div>',
            $alertClass,
            htmlspecialchars($message)
        );
    }
    return '';
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
