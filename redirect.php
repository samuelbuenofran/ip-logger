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

// Find the link in database
$stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ?");
$stmt->execute([$short_code]);
$link = $stmt->fetch();

if (!$link) {
    // Link not found
    header('Location: index.php?error=link_not_found');
    exit;
}

// Check if link is expired
if (isLinkExpired($link['expiry_date'])) {
    header('Location: index.php?error=link_expired');
    exit;
}

// Get client information
$ip_address = getClientIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device_type = getDeviceType();
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Get geolocation data
$geo_data = getGeolocationData($ip_address);

// Insert target data into database
$stmt = $conn->prepare("
    INSERT INTO targets (
        link_id, ip_address, user_agent, device_type, country, country_code, 
        region, city, zip_code, latitude, longitude, timezone, isp, organization, 
        as_number, referer
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $link['id'],
    $ip_address,
    $user_agent,
    $device_type,
    $geo_data['country'] ?? null,
    $geo_data['country_code'] ?? null,
    $geo_data['region'] ?? null,
    $geo_data['city'] ?? null,
    $geo_data['zip'] ?? null,
    $geo_data['lat'] ?? null,
    $geo_data['lon'] ?? null,
    $geo_data['timezone'] ?? null,
    $geo_data['isp'] ?? null,
    $geo_data['org'] ?? null,
    $geo_data['as'] ?? null,
    $referer
]);

// Get the target ID for email notification
$targetId = $conn->lastInsertId();

// Prepare target data for email notification
$targetData = [
    'ip_address' => $ip_address,
    'device_type' => $device_type,
    'country' => $geo_data['country'] ?? 'Unknown',
    'city' => $geo_data['city'] ?? 'Unknown',
    'isp' => $geo_data['isp'] ?? 'Unknown',
    'clicked_at' => date('Y-m-d H:i:s')
];

// Send email notification for link click
sendLinkClickNotification($link['id'], $targetData);

// Redirect to original URL
header('Location: ' . $link['original_url']);
exit;
?>
