<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the short code from URL
$short_code = $_GET['code'] ?? '';

if (empty($short_code)) {
    header('Location: index.php');
    exit;
}

// Find the link
$stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ? AND (expiry_date IS NULL OR expiry_date > NOW()) AND is_active = 1");
$stmt->execute([$short_code]);
$link = $stmt->fetch();

if (!$link) {
    header('Location: index.php?error=link_not_found');
    exit;
}

// Check if consent is required
if ($link['require_consent'] && !isset($_GET['consent'])) {
    // Show consent page
    include 'consent_page.php';
    exit;
}

// Get client information
$ip_address = getClientIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$device_type = getDeviceType();

// Enhanced tracking based on link settings
$target_data = [
    'link_id' => $link['id'],
    'ip_address' => $ip_address,
    'user_agent' => $user_agent,
    'referrer' => $referrer,
    'device_type' => $device_type,
    'clicked_at' => date('Y-m-d H:i:s')
];

// Get basic geolocation data
$geo_data = getGeolocationData($ip_address);
$target_data = array_merge($target_data, [
    'country' => $geo_data['country'],
    'country_code' => $geo_data['country_code'],
    'region' => $geo_data['region'],
    'city' => $geo_data['city'],
    'zip' => $geo_data['zip'],
    'latitude' => $geo_data['lat'],
    'longitude' => $geo_data['lon'],
    'timezone' => $geo_data['timezone'],
    'isp' => $geo_data['isp'],
    'organization' => $geo_data['org'],
    'as_number' => $geo_data['as']
]);

// Enhanced tracking if SMART data is enabled
if ($link['collect_smart_data']) {
    $browser_info = getDetailedBrowserInfo($user_agent);
    $target_data = array_merge($target_data, $browser_info);
    
    // Additional SMART data
    $target_data['screen_resolution'] = $_GET['screen'] ?? null;
    $target_data['language'] = $_GET['lang'] ?? null;
    $target_data['connection_type'] = $_GET['connection'] ?? null;
    $target_data['mobile_carrier'] = $_GET['carrier'] ?? null;
    $target_data['device_model'] = $_GET['model'] ?? null;
}

// GPS data if enabled
if ($link['collect_gps_data'] && isset($_GET['gps_lat']) && isset($_GET['gps_lng'])) {
    $target_data['latitude'] = $_GET['gps_lat'];
    $target_data['longitude'] = $_GET['gps_lng'];
    $target_data['gps_accuracy'] = $_GET['gps_acc'] ?? null;
    $target_data['accuracy_level'] = 'GPS';
}

// Proxy/VPN detection
$proxy_info = detectProxyVPN($ip_address);
$target_data = array_merge($target_data, $proxy_info);

// Referrer analysis
$referrer_info = getReferrerInfo($referrer);
$target_data['referrer_domain'] = $referrer_info['domain'];
$target_data['referrer_path'] = $referrer_info['path'];

// UTM parameters
$current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$utm_params = parseUTMParameters($current_url);
$target_data = array_merge($target_data, $utm_params);

// Consent tracking
$target_data['consent_given'] = isset($_GET['consent']) ? 1 : 0;
$target_data['consent_timestamp'] = $target_data['consent_given'] ? date('Y-m-d H:i:s') : null;

// Insert target data
$stmt = $conn->prepare("
    INSERT INTO targets (
        link_id, ip_address, user_agent, referrer, device_type,
        country, country_code, region, city, zip_code,
        latitude, longitude, timezone, isp, organization, as_number,
        browser_name, browser_version, os_name, os_version,
        screen_resolution, language, connection_type, mobile_carrier, device_model,
        gps_accuracy, consent_given, consent_timestamp,
        referrer_domain, referrer_path, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
        accuracy_level, proxy_detected, vpn_detected, tor_detected, bot_detected,
        clicked_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $target_data['link_id'], $target_data['ip_address'], $target_data['user_agent'], 
    $target_data['referrer'], $target_data['device_type'], $target_data['country'], 
    $target_data['country_code'], $target_data['region'], $target_data['city'], 
    $target_data['zip'], $target_data['latitude'], $target_data['longitude'], 
    $target_data['timezone'], $target_data['isp'], $target_data['organization'], 
    $target_data['as_number'], $target_data['browser_name'], $target_data['browser_version'],
    $target_data['os_name'], $target_data['os_version'], $target_data['screen_resolution'],
    $target_data['language'], $target_data['connection_type'], $target_data['mobile_carrier'],
    $target_data['device_model'], $target_data['gps_accuracy'], $target_data['consent_given'],
    $target_data['consent_timestamp'], $target_data['referrer_domain'], $target_data['referrer_path'],
    $target_data['utm_source'], $target_data['utm_medium'], $target_data['utm_campaign'],
    $target_data['utm_term'], $target_data['utm_content'], $target_data['accuracy_level'],
    $target_data['proxy_detected'], $target_data['vpn_detected'], $target_data['tor_detected'],
    $target_data['bot_detected'], $target_data['clicked_at']
]);

$target_id = $conn->lastInsertId();

// Log consent if given
if ($target_data['consent_given']) {
    logConsent($target_id, 'tracking', true, 'User consented to tracking');
}

// Update link statistics
updateLinkStats($link['id']);

// Send email notification
sendLinkClickNotification($link['id'], $target_data);

// Handle destination preview
if ($link['destination_preview'] && !isset($_GET['redirect'])) {
    // Show preview page
    include 'preview_page.php';
    exit;
}

// Build destination URL
$destination_url = $link['original_url'];

// Forward GET parameters if enabled
if ($link['forward_get_params']) {
    $params = $_GET;
    unset($params['code'], $params['consent'], $params['redirect']); // Remove tracking parameters
    
    if (!empty($params)) {
        $separator = strpos($destination_url, '?') !== false ? '&' : '?';
        $destination_url .= $separator . http_build_query($params);
    }
}

// Redirect to original URL
header('Location: ' . $destination_url);
exit;
?>
