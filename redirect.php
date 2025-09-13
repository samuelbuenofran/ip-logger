<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/functions/get_device_type.php';

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
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$device_type = getDeviceType($user_agent);
$timestamp = date('Y-m-d H:i:s');

// Get geolocation data
$geolocation_data = getGeolocationData($ip_address);

// Log the visit
$stmt = $conn->prepare("
    INSERT INTO targets (link_id, ip_address, user_agent, referer, device_type, country, region, city, latitude, longitude, timezone, isp, clicked_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $link['id'],
    $ip_address,
    $user_agent,
    $referer,
    $device_type,
    $geolocation_data['country'] ?? '',
    $geolocation_data['region'] ?? '',
    $geolocation_data['city'] ?? '',
    $geolocation_data['latitude'] ?? null,
    $geolocation_data['longitude'] ?? null,
    $geolocation_data['timezone'] ?? '',
    $geolocation_data['isp'] ?? '',
    $timestamp
]);

// Redirect to the original URL after logging
header('Location: ' . $link['original_url']);
exit;
