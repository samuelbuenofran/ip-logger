<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the tracking code from URL
$tracking_code = $_GET['tracking_code'] ?? '';

if (empty($tracking_code)) {
    header('Location: index.php');
    exit;
}

// Find the link by tracking code
$stmt = $conn->prepare("SELECT * FROM links WHERE tracking_code = ?");
$stmt->execute([$tracking_code]);
$link = $stmt->fetch();

if (!$link) {
    header('Location: index.php?error=link_not_found');
    exit;
}

// Redirect to the statistics page with the link_id
header('Location: view_targets.php?link_id=' . $link['id']);
exit;
?>
