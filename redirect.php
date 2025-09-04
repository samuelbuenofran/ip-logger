<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the short code from URL
$short_code = $_GET['short_code'] ?? '';

if (empty($short_code)) {
    header('Location: index.php');
    exit;
}

// Find the link
$stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ? AND (expiry_date IS NULL OR expiry_date > NOW())");
$stmt->execute([$short_code]);
$link = $stmt->fetch();

if (!$link) {
    header('Location: index.php?error=link_not_found');
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

// Basic browser detection
$browser_info = getDetailedBrowserInfo($user_agent);
$target_data = array_merge($target_data, $browser_info);

// Insert target data
$stmt = $conn->prepare("
    INSERT INTO targets (
        link_id, ip_address, user_agent, referer, device_type,
        country, region, city, latitude, longitude, timezone, isp, clicked_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $target_data['link_id'], $target_data['ip_address'], $target_data['user_agent'], 
    $target_data['referrer'], $target_data['device_type'], $target_data['country'], 
    $target_data['region'], $target_data['city'], $target_data['latitude'], 
    $target_data['longitude'], $target_data['timezone'], $target_data['isp'], 
    $target_data['clicked_at']
]);

$target_id = $conn->lastInsertId();

// Send email notification if function exists
if (function_exists('sendLinkClickNotification')) {
    sendLinkClickNotification($link['id'], $target_data);
}

// Build destination URL
$destination_url = $link['original_url'];

// Forward GET parameters (excluding tracking parameters)
$params = $_GET;
unset($params['short_code'], $params['consent'], $params['redirect']); // Remove tracking parameters

if (!empty($params)) {
    $separator = strpos($destination_url, '?') !== false ? '&' : '?';
    $destination_url .= $separator . http_build_query($params);
}

// Redirect to original URL
header('Location: ' . $destination_url);
exit;
?>
