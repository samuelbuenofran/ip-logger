<?php

/**
 * Device Type Detection Functions
 * Enhanced device detection for IP Logger
 */

/**
 * Get device type from user agent with enhanced detection
 */
function getDeviceType($userAgent = null)
{
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    $userAgent = strtolower($userAgent);

    // Mobile device detection
    $mobileKeywords = [
        'mobile',
        'android',
        'iphone',
        'ipad',
        'windows phone',
        'blackberry',
        'opera mini',
        'webos',
        'palm',
        'symbian',
        'kindle',
        'silk',
        'mobile safari',
        'fennec'
    ];

    foreach ($mobileKeywords as $keyword) {
        if (strpos($userAgent, $keyword) !== false) {
            // Additional check for tablets
            if (
                strpos($userAgent, 'ipad') !== false ||
                strpos($userAgent, 'tablet') !== false ||
                strpos($userAgent, 'kindle') !== false
            ) {
                return 'tablet';
            }
            return 'mobile';
        }
    }

    // Desktop detection
    $desktopKeywords = [
        'windows nt',
        'macintosh',
        'mac os x',
        'linux',
        'x11',
        'windows',
        'win32',
        'win64',
        'ubuntu',
        'debian'
    ];

    foreach ($desktopKeywords as $keyword) {
        if (strpos($userAgent, $keyword) !== false) {
            return 'desktop';
        }
    }

    // Default fallback
    return 'unknown';
}

/**
 * Get detailed device information
 */
function getDetailedDeviceInfo($userAgent = null)
{
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    $deviceInfo = [
        'type' => getDeviceType($userAgent),
        'browser' => getBrowser($userAgent),
        'os' => getOperatingSystem($userAgent),
        'is_mobile' => isMobile($userAgent),
        'is_tablet' => isTablet($userAgent),
        'is_desktop' => isDesktop($userAgent)
    ];

    return $deviceInfo;
}

/**
 * Detect browser from user agent
 */
function getBrowser($userAgent)
{
    $userAgent = strtolower($userAgent);

    if (strpos($userAgent, 'chrome') !== false) {
        return 'Chrome';
    } elseif (strpos($userAgent, 'firefox') !== false) {
        return 'Firefox';
    } elseif (strpos($userAgent, 'safari') !== false) {
        return 'Safari';
    } elseif (strpos($userAgent, 'edge') !== false) {
        return 'Edge';
    } elseif (strpos($userAgent, 'opera') !== false) {
        return 'Opera';
    } elseif (strpos($userAgent, 'msie') !== false) {
        return 'Internet Explorer';
    } else {
        return 'Unknown';
    }
}

/**
 * Detect operating system from user agent
 */
function getOperatingSystem($userAgent)
{
    $userAgent = strtolower($userAgent);

    if (strpos($userAgent, 'windows nt 10.0') !== false) {
        return 'Windows 10';
    } elseif (strpos($userAgent, 'windows nt 6.3') !== false) {
        return 'Windows 8.1';
    } elseif (strpos($userAgent, 'windows nt 6.2') !== false) {
        return 'Windows 8';
    } elseif (strpos($userAgent, 'windows nt 6.1') !== false) {
        return 'Windows 7';
    } elseif (strpos($userAgent, 'windows nt 6.0') !== false) {
        return 'Windows Vista';
    } elseif (strpos($userAgent, 'windows nt 5.1') !== false) {
        return 'Windows XP';
    } elseif (strpos($userAgent, 'windows nt') !== false) {
        return 'Windows';
    } elseif (strpos($userAgent, 'mac os x') !== false) {
        return 'macOS';
    } elseif (strpos($userAgent, 'linux') !== false) {
        return 'Linux';
    } elseif (strpos($userAgent, 'android') !== false) {
        return 'Android';
    } elseif (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
        return 'iOS';
    } else {
        return 'Unknown';
    }
}

/**
 * Check if device is mobile
 */
function isMobile($userAgent = null)
{
    return getDeviceType($userAgent) === 'mobile';
}

/**
 * Check if device is tablet
 */
function isTablet($userAgent = null)
{
    return getDeviceType($userAgent) === 'tablet';
}

/**
 * Check if device is desktop
 */
function isDesktop($userAgent = null)
{
    return getDeviceType($userAgent) === 'desktop';
}

/**
 * Get device category (mobile, tablet, desktop)
 */
function getDeviceCategory($userAgent = null)
{
    $deviceType = getDeviceType($userAgent);

    if ($deviceType === 'mobile') {
        return 'mobile';
    } elseif ($deviceType === 'tablet') {
        return 'tablet';
    } elseif ($deviceType === 'desktop') {
        return 'desktop';
    } else {
        return 'unknown';
    }
}
