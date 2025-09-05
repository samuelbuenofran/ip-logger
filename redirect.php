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

// Basic geolocation data (fallback)
$geo_data = getGeolocationData($ip_address);

// Prepare target data for database insertion
$target_data = [
    'link_id' => $link['id'],
    'ip_address' => $ip_address,
    'user_agent' => $user_agent,
    'referrer' => $referrer,
    'device_type' => $device_type,
    'country' => $geo_data['country'] ?? null,
    'country_code' => $geo_data['country_code'] ?? null,
    'region' => $geo_data['region'] ?? null,
    'city' => $geo_data['city'] ?? null,
    'zip_code' => $geo_data['zip_code'] ?? null,
    'latitude' => $geo_data['latitude'] ?? null,
    'longitude' => $geo_data['longitude'] ?? null,
    'timezone' => $geo_data['timezone'] ?? null,
    'isp' => $geo_data['isp'] ?? null,
    'organization' => $geo_data['organization'] ?? null,
    'as_number' => $geo_data['as_number'] ?? null,
    'clicked_at' => date('Y-m-d H:i:s')
];

// Insert tracking data into database
try {
    $stmt = $conn->prepare("
        INSERT INTO targets (
            link_id, ip_address, user_agent, referer, device_type,
            country, country_code, region, city, zip_code, latitude, longitude, 
            timezone, isp, organization, as_number, clicked_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $target_data['link_id'],
        $target_data['ip_address'],
        $target_data['user_agent'],
        $target_data['referrer'],
        $target_data['device_type'],
        $target_data['country'],
        $target_data['country_code'],
        $target_data['region'],
        $target_data['city'],
        $target_data['zip_code'],
        $target_data['latitude'],
        $target_data['longitude'],
        $target_data['timezone'],
        $target_data['isp'],
        $target_data['organization'],
        $target_data['as_number'],
        $target_data['clicked_at']
    ]);
    
} catch (Exception $e) {
    // Log error but don't stop the redirect
    error_log("Tracking error: " . $e->getMessage());
}

// Check if password is required
if (!empty($link['password'])) {
    $password = $_POST['password'] ?? '';
    
    if (empty($password) || !password_verify($password, $link['password'])) {
        // Show password form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Enter Password - IP Logger</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-lock"></i> Password Required</h4>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">This link is password protected. Please enter the password to continue.</p>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-unlock"></i> Continue
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Redirect to original URL
$original_url = $link['original_url'];

// Forward any additional GET parameters
$query_params = $_GET;
unset($query_params['short_code']); // Remove our parameter

if (!empty($query_params)) {
    $separator = (strpos($original_url, '?') !== false) ? '&' : '?';
    $original_url .= $separator . http_build_query($query_params);
}

// Perform redirect
header('Location: ' . $original_url);
exit;
?>
