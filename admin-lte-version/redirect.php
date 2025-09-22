<?php

/**
 * Redirect Handler for AdminLTE IP Logger
 * This file handles the redirection of short links and tracks visitor data
 */

session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get the short code from the URL
$short_code = $_GET['code'] ?? '';

if (empty($short_code)) {
    http_response_code(404);
    die('Link não encontrado');
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Find the link
    $stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ?");
    $stmt->execute([$short_code]);
    $link = $stmt->fetch();

    if (!$link) {
        http_response_code(404);
        die('Link não encontrado');
    }

    // Check if link is expired
    if (isLinkExpired($link['expiry_date'])) {
        http_response_code(410);
        die('Este link expirou');
    }

    // Get client information
    $client_ip = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $device_type = getDeviceType();

    // Get geolocation data
    $geo_data = getGeolocationData($client_ip);

    // Get detailed browser info
    $browser_info = getDetailedBrowserInfo($user_agent);

    // Insert tracking data
    $stmt = $conn->prepare("
        INSERT INTO targets (
            link_id, ip_address, user_agent, referrer, country, country_code, 
            region, city, zip_code, latitude, longitude, timezone, isp, 
            organization, as_number, device_type, browser_name, browser_version, 
            os_name, os_version, clicked_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $link['id'],
        $client_ip,
        $user_agent,
        $referrer,
        $geo_data['country'],
        $geo_data['country_code'],
        $geo_data['region'],
        $geo_data['city'],
        $geo_data['zip'],
        $geo_data['lat'],
        $geo_data['lon'],
        $geo_data['timezone'],
        $geo_data['isp'],
        $geo_data['org'],
        $geo_data['as'],
        $device_type,
        $browser_info['browser_name'],
        $browser_info['browser_version'],
        $browser_info['os_name'],
        $browser_info['os_version']
    ]);

    // Update link statistics
    $stmt = $conn->prepare("
        UPDATE links 
        SET click_count = click_count + 1, 
            last_click_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$link['id']]);

    // Log activity
    $stmt = $conn->prepare("
        INSERT INTO audit_log (action, details, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        'link_clicked',
        "Link clicked: {$short_code} -> {$link['original_url']}",
        $client_ip,
        $user_agent
    ]);

    // Redirect to original URL
    header('Location: ' . $link['original_url']);
    exit;
} catch (PDOException $e) {
    error_log("Redirect error: " . $e->getMessage());
    http_response_code(500);
    die('Erro interno do servidor');
}
