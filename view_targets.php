<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$link_id = $_GET['link_id'] ?? 0;
$password = $_POST['password'] ?? '';

// If no link_id is provided, show list of all links
if (empty($link_id)) {
    // Get all links
    $stmt = $conn->query("SELECT * FROM links ORDER BY created_at DESC");
    $links = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Geolocation - IP Logger</title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            
            .sidebar {
                min-height: 100vh;
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            }
            
            .sidebar .nav-link {
                color: #adb5bd;
                padding: 0.75rem 1rem;
                border-radius: 0.375rem;
                margin: 0.25rem 0;
                transition: all 0.3s ease;
            }
            
            .sidebar .nav-link:hover {
                color: #fff;
                background-color: rgba(255,255,255,0.1);
            }
            
            .sidebar .nav-link.active {
                color: #fff;
                background-color: #007bff;
            }
            
            .link-card {
                background: white;
                border: 1px solid #e9ecef;
                border-radius: 12px;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                height: 100%;
            }
            
            .link-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            
            .link-card .card-body {
                padding: 1.5rem;
            }
            
            .link-card .card-title {
                font-weight: 600;
                color: #333;
                margin-bottom: 0.75rem;
            }
            
            .link-card .card-text {
                color: #6c757d;
                margin-bottom: 1rem;
            }
            
            .stats-badge {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
                border-radius: 0.375rem;
                font-weight: 500;
            }
            
            .btn-primary {
                background-color: #007bff;
                border-color: #007bff;
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .btn-primary:hover {
                background-color: #0056b3;
                border-color: #0056b3;
                transform: translateY(-1px);
            }
            
            .btn-secondary {
                background-color: #6c757d;
                border-color: #6c757d;
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .btn-secondary:hover {
                background-color: #545b62;
                border-color: #545b62;
                transform: translateY(-1px);
            }
            
            .card {
                border: 1px solid #e9ecef;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .card-header {
                background-color: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                border-radius: 12px 12px 0 0;
                padding: 1rem 1.5rem;
            }
            
            .card-header h5 {
                margin: 0;
                font-weight: 600;
                color: #333;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .h2 {
                font-weight: 600;
                color: #333;
                margin-bottom: 0;
            }
            
            .text-muted {
                color: #6c757d !important;
            }
            
            .bg-light {
                background-color: #f8f9fa !important;
            }
            
            .border-bottom {
                border-bottom: 1px solid #e9ecef !important;
            }
            
            .pt-3 {
                padding-top: 1rem !important;
            }
            
            .pb-2 {
                padding-bottom: 0.5rem !important;
            }
            
            .mb-3 {
                margin-bottom: 1rem !important;
            }
            
            .mb-2 {
                margin-bottom: 0.5rem !important;
            }
            
            .mb-md-0 {
                margin-bottom: 0 !important;
            }
            
            .d-flex {
                display: flex !important;
            }
            
            .justify-content-between {
                justify-content: space-between !important;
            }
            
            .align-items-center {
                align-items: center !important;
            }
            
            .flex-wrap {
                flex-wrap: wrap !important;
            }
            
            .flex-md-nowrap {
                flex-wrap: nowrap !important;
            }
            
            .btn-toolbar {
                display: flex;
                gap: 0.5rem;
            }
            
            .row {
                display: flex;
                flex-wrap: wrap;
                margin: 0 -0.75rem;
            }
            
            .col-12 {
                flex: 0 0 100%;
                max-width: 100%;
                padding: 0 0.75rem;
            }
            
            .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
                padding: 0 0.75rem;
            }
            
            .col-lg-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
                padding: 0 0.75rem;
            }
            
            @media (max-width: 768px) {
                .col-md-6 {
                    flex: 0 0 100%;
                    max-width: 100%;
                }
            }
            
            @media (max-width: 992px) {
                .col-lg-4 {
                    flex: 0 0 50%;
                    max-width: 50%;
                }
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
                    <div class="position-sticky pt-3">
                        <div class="text-center mb-4">
                            <h4 class="text-white"><i class="fas fa-shield-alt"></i> IP Logger</h4>
                            <p class="text-muted">URL Shortener & Tracker</p>
                        </div>
                        
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="links.php">
                                    <i class="fas fa-link"></i> My Links
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="create_link.php">
                                    <i class="fas fa-plus"></i> Create Link
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="view_targets.php">
                                    <i class="fas fa-map-marker-alt"></i> Geolocation
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin.php">
                                    <i class="fas fa-cog"></i> Admin Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="privacy.php">
                                    <i class="fas fa-user-shield"></i> Privacy Policy
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="terms.php">
                                    <i class="fas fa-file-contract"></i> Terms of Use
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="cookies.php">
                                    <i class="fas fa-cookie-bite"></i> Cookie Policy
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="password_recovery.php">
                                    <i class="fas fa-key"></i> Password Recovery
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="fas fa-map-marker-alt"></i> Geolocation</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-link"></i> Select a Link to View Geolocation Data</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($links)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-link fa-3x text-muted mb-3"></i>
                                            <h4>No Links Found</h4>
                                            <p class="text-muted">You haven't created any links yet.</p>
                                            <a href="create_link.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Create Your First Link
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($links as $link): ?>
                                                <?php
                                                // Get click count for this link
                                                $stmt = $conn->prepare("SELECT COUNT(*) as clicks FROM targets WHERE link_id = ?");
                                                $stmt->execute([$link['id']]);
                                                $click_count = $stmt->fetch(PDO::FETCH_ASSOC)['clicks'];
                                                
                                                // Get unique visitors count
                                                $stmt = $conn->prepare("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM targets WHERE link_id = ?");
                                                $stmt->execute([$link['id']]);
                                                $unique_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['unique_visitors'];
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <div class="card link-card h-100">
                                                        <div class="card-body">
                                                            <h6 class="card-title">
                                                                <code><?php echo htmlspecialchars($link['short_code']); ?></code>
                                                            </h6>
                                                            <p class="card-text text-muted small">
                                                                <?php echo htmlspecialchars(substr($link['original_url'], 0, 50)) . (strlen($link['original_url']) > 50 ? '...' : ''); ?>
                                                            </p>
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <span class="badge bg-primary stats-badge">
                                                                    <i class="fas fa-mouse-pointer"></i> <?php echo $click_count; ?> clicks
                                                                </span>
                                                                <span class="badge bg-info stats-badge">
                                                                    <i class="fas fa-users"></i> <?php echo $unique_visitors; ?> visitors
                                                                </span>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <small class="text-muted">
                                                                    Created: <?php echo date('M j, Y', strtotime($link['created_at'])); ?>
                                                                </small>
                                                                <a href="view_targets.php?link_id=<?php echo $link['id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-map-marker-alt"></i> View Map
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Get link information
$stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
$stmt->execute([$link_id]);
$link = $stmt->fetch();

if (!$link) {
    header('Location: index.php?error=link_not_found');
    exit;
}

// Check if password is provided and correct
if (empty($password)) {
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
                            <h5 class="mb-0"><i class="fas fa-lock"></i> Enter Password</h5>
                        </div>
                        <div class="card-body">
                            <p>This link is password protected. Please enter the password to view tracking data.</p>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">View Data</button>
                                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
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

// Verify password
if (!password_verify($password, $link['password'])) {
    header('Location: view_targets.php?link_id=' . $link_id . '&error=invalid_password');
    exit;
}

// Get targets for this link
$stmt = $conn->prepare("
    SELECT * FROM targets 
    WHERE link_id = ? 
    ORDER BY clicked_at DESC
");
$stmt->execute([$link_id]);
$targets = $stmt->fetchAll();

// Prepare data for Google Maps with enhanced information
$map_data = [];
$countries = [];
$cities = [];
$device_types = [];

foreach ($targets as $target) {
    if ($target['latitude'] && $target['longitude']) {
        // Count statistics
        if ($target['country']) {
            $countries[$target['country']] = ($countries[$target['country']] ?? 0) + 1;
        }
        if ($target['city']) {
            $cities[$target['city']] = ($cities[$target['city']] ?? 0) + 1;
        }
        if ($target['device_type']) {
            $device_types[$target['device_type']] = ($device_types[$target['device_type']] ?? 0) + 1;
        }
        
        // Create detailed info window content
        $info_content = '
            <div style="min-width: 250px; padding: 10px;">
                <h6 style="margin: 0 0 10px 0; color: #333;">
                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                    ' . ($target['city'] ? $target['city'] . ', ' : '') . $target['country'] . '
                </h6>
                <div style="font-size: 12px; line-height: 1.4;">
                    <p style="margin: 5px 0;"><strong>IP Address:</strong> ' . $target['ip_address'] . '</p>
                    <p style="margin: 5px 0;"><strong>Device:</strong> ' . ucfirst($target['device_type']) . '</p>
                    <p style="margin: 5px 0;"><strong>ISP:</strong> ' . ($target['isp'] ?: 'Unknown') . '</p>
                    <p style="margin: 5px 0;"><strong>Time:</strong> ' . formatDate($target['clicked_at']) . '</p>
                    <p style="margin: 5px 0;"><strong>Coordinates:</strong> ' . $target['latitude'] . ', ' . $target['longitude'] . '</p>
                </div>
            </div>
        ';
        
        $map_data[] = [
            'lat' => (float)$target['latitude'],
            'lng' => (float)$target['longitude'],
            'title' => ($target['city'] ? $target['city'] . ', ' : '') . $target['country'],
            'info' => $info_content,
            'ip' => $target['ip_address'],
            'device' => $target['device_type'],
            'country' => $target['country'],
            'city' => $target['city'],
            'time' => $target['clicked_at']
        ];
    }
}

// Calculate map statistics
$total_locations = count($map_data);
$unique_countries = count($countries);
$unique_cities = count($cities);
$most_common_country = !empty($countries) ? array_keys($countries, max($countries))[0] : 'None';
$most_common_device = !empty($device_types) ? array_keys($device_types, max($device_types))[0] : 'None';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Data - IP Logger</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .map-controls {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .map-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .map-stat {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .map-stat i {
            margin-right: 0.5rem;
            color: #007bff;
        }
        
        .target-card {
            transition: transform 0.2s;
        }
        
        .target-card:hover {
            transform: translateY(-2px);
        }
        
        .location-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            background: #e9ecef;
            color: #495057;
        }
        
        .location-badge i {
            margin-right: 0.25rem;
            color: #dc3545;
        }
        
        .map-legend {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .map-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 500px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .map-loading i {
            font-size: 2rem;
            color: #007bff;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white"><i class="fas fa-shield-alt"></i> IP Logger</h4>
                        <p class="text-muted">URL Shortener & Tracker</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="links.php">
                                <i class="fas fa-link"></i> My Links
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_link.php">
                                <i class="fas fa-plus"></i> Create Link
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="view_targets.php">
                                <i class="fas fa-map-marker-alt"></i> Geolocation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-cog"></i> Admin Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="privacy.php">
                                <i class="fas fa-user-shield"></i> Privacy Policy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="terms.php">
                                <i class="fas fa-file-contract"></i> Terms of Use
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cookies.php">
                                <i class="fas fa-cookie-bite"></i> Cookie Policy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="password_recovery.php">
                                <i class="fas fa-key"></i> Password Recovery
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Target Data</h1>
                                         <div class="btn-toolbar mb-2 mb-md-0">
                         <a href="index.php" class="btn btn-secondary me-2">
                             <i class="fas fa-arrow-left"></i> Back to Dashboard
                         </a>
                         <a href="test_maps.php" class="btn btn-info" target="_blank">
                             <i class="fas fa-map"></i> Test Maps
                         </a>
                     </div>
                </div>

                <!-- Link Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-link"></i> Link Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Short Code:</strong> <code><?php echo $link['short_code']; ?></code></p>
                                <p><strong>Original URL:</strong> <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank"><?php echo htmlspecialchars($link['original_url']); ?></a></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Created:</strong> <?php echo formatDate($link['created_at']); ?></p>
                                <p><strong>Total Clicks:</strong> <?php echo count($targets); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                                 <!-- Google Maps -->
                 <?php if (!empty($map_data)): ?>
                 <div class="card mb-4">
                     <div class="card-header">
                         <h5 class="mb-0"><i class="fas fa-map"></i> Location Map</h5>
                     </div>
                     <div class="card-body">
                         <!-- Map Statistics -->
                         <div class="map-stats">
                             <div class="map-stat">
                                 <i class="fas fa-map-marker-alt"></i>
                                 <?php echo $total_locations; ?> Locations
                             </div>
                             <div class="map-stat">
                                 <i class="fas fa-globe"></i>
                                 <?php echo $unique_countries; ?> Countries
                             </div>
                             <div class="map-stat">
                                 <i class="fas fa-city"></i>
                                 <?php echo $unique_cities; ?> Cities
                             </div>
                             <div class="map-stat">
                                 <i class="fas fa-flag"></i>
                                 Top: <?php echo $most_common_country; ?>
                             </div>
                             <div class="map-stat">
                                 <i class="fas fa-mobile-alt"></i>
                                 Most: <?php echo ucfirst($most_common_device); ?>
                             </div>
                         </div>
                         
                         <!-- Map Controls -->
                         <div class="map-controls">
                             <div class="row">
                                 <div class="col-md-6">
                                     <button class="btn btn-outline-primary btn-sm" onclick="fitMapToBounds()">
                                         <i class="fas fa-expand"></i> Fit All Markers
                                     </button>
                                     <button class="btn btn-outline-secondary btn-sm ms-2" onclick="clearMap()">
                                         <i class="fas fa-eraser"></i> Clear Map
                                     </button>
                                 </div>
                                 <div class="col-md-6 text-end">
                                     <button class="btn btn-outline-info btn-sm" onclick="exportMapData()">
                                         <i class="fas fa-download"></i> Export Data
                                     </button>
                                     <button class="btn btn-outline-success btn-sm ms-2" onclick="refreshMap()">
                                         <i class="fas fa-sync"></i> Refresh
                                     </button>
                                 </div>
                             </div>
                         </div>
                         
                         <!-- Map Container -->
                         <div id="map"></div>
                         
                         <!-- Map Legend -->
                         <div class="map-legend">
                             <h6><i class="fas fa-info-circle"></i> Map Legend</h6>
                             <div class="legend-item">
                                 <div class="legend-color" style="background: #dc3545;"></div>
                                 <span>Desktop Users</span>
                             </div>
                             <div class="legend-item">
                                 <div class="legend-color" style="background: #28a745;"></div>
                                 <span>Mobile Users</span>
                             </div>
                             <div class="legend-item">
                                 <div class="legend-color" style="background: #ffc107;"></div>
                                 <span>Tablet Users</span>
                             </div>
                             <div class="legend-item">
                                 <div class="legend-color" style="background: #17a2b8;"></div>
                                 <span>Other Devices</span>
                             </div>
                         </div>
                     </div>
                 </div>
                 <?php else: ?>
                 <div class="card mb-4">
                     <div class="card-header">
                         <h5 class="mb-0"><i class="fas fa-map"></i> Location Map</h5>
                     </div>
                     <div class="card-body">
                         <div class="alert alert-info">
                             <i class="fas fa-info-circle"></i>
                             <strong>No location data available.</strong> 
                             Location tracking requires valid IP addresses and geolocation data. 
                             Some visitors may have private IP addresses or location data may not be available.
                         </div>
                     </div>
                 </div>
                 <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count($targets); ?></h3>
                                <p class="mb-0">Total Clicks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_unique(array_column($targets, 'ip_address'))); ?></h3>
                                <p class="mb-0">Unique IPs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_unique(array_column($targets, 'country'))); ?></h3>
                                <p class="mb-0">Countries</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($targets, function($t) { return $t['device_type'] === 'mobile'; })); ?></h3>
                                <p class="mb-0">Mobile Users</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Targets Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Target Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>IP Address</th>
                                        <th>Location</th>
                                        <th>Device</th>
                                        <th>ISP</th>
                                        <th>Clicked</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($targets as $target): ?>
                                    <tr>
                                        <td><?php echo $target['id']; ?></td>
                                        <td>
                                            <code><?php echo $target['ip_address']; ?></code>
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo $target['ip_address']; ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($target['city'] && $target['country']): ?>
                                                <i class="fas fa-map-marker-alt text-danger"></i>
                                                <?php echo $target['city'] . ', ' . $target['country']; ?>
                                                <?php if ($target['latitude'] && $target['longitude']): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo $target['latitude'] . ', ' . $target['longitude']; ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $target['device_type'] === 'mobile' ? 'success' : 'primary'; ?>">
                                                <i class="fas fa-<?php echo $target['device_type'] === 'mobile' ? 'mobile-alt' : 'desktop'; ?>"></i>
                                                <?php echo ucfirst($target['device_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($target['isp']): ?>
                                                <span title="<?php echo htmlspecialchars($target['organization'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($target['isp']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span title="<?php echo formatDate($target['clicked_at'], 'Y-m-d H:i:s'); ?>">
                                                <?php echo timeAgo($target['clicked_at']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="showTargetDetails(<?php echo htmlspecialchars(json_encode($target)); ?>)">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Target Details Modal -->
    <div class="modal fade" id="targetDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Target Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="targetDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
         <?php if (!empty($map_data)): ?>
     <!-- Google Maps API -->
     <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap" async defer onerror="handleMapError()"></script>
     <?php else: ?>
     <!-- No map data available -->
     <script>
         function handleMapError() {
             console.error('Google Maps failed to load');
         }
     </script>
     <?php endif; ?>
    
    <!-- Custom JS -->
    <script>
        <?php if (!empty($map_data)): ?>
        let map;
        let markers = [];
        let bounds;
        
        function initMap() {
            try {
                const mapData = <?php echo json_encode($map_data); ?>;
                
                if (mapData.length === 0) {
                    console.log('No map data available');
                    return;
                }
                
                // Initialize bounds
                bounds = new google.maps.LatLngBounds();
                
                // Create map
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 2,
                    center: { lat: mapData[0].lat, lng: mapData[0].lng },
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true,
                    zoomControl: true,
                    styles: [
                        {
                            featureType: 'poi',
                            elementType: 'labels',
                            stylers: [{ visibility: 'off' }]
                        }
                    ]
                });
                
                // Add markers
                mapData.forEach(function(location) {
                    // Determine marker color based on device type
                    let markerColor = '#17a2b8'; // default
                    switch(location.device) {
                        case 'desktop':
                            markerColor = '#dc3545';
                            break;
                        case 'mobile':
                            markerColor = '#28a745';
                            break;
                        case 'tablet':
                            markerColor = '#ffc107';
                            break;
                    }
                    
                    // Create custom marker
                    const marker = new google.maps.Marker({
                        position: { lat: location.lat, lng: location.lng },
                        map: map,
                        title: location.title,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: markerColor,
                            fillOpacity: 0.8,
                            strokeColor: '#ffffff',
                            strokeWeight: 2
                        }
                    });
                    
                    // Create info window
                    const infowindow = new google.maps.InfoWindow({
                        content: location.info,
                        maxWidth: 300
                    });
                    
                    // Add click listener
                    marker.addListener('click', function() {
                        infowindow.open(map, marker);
                    });
                    
                    // Add marker to bounds
                    bounds.extend(marker.getPosition());
                    markers.push(marker);
                });
                
                // Fit map to bounds
                if (markers.length > 1) {
                    map.fitBounds(bounds);
                }
                
                console.log('Google Maps initialized successfully with', mapData.length, 'markers');
            } catch (error) {
                console.error('Error initializing Google Maps:', error);
                document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error loading map: ' + error.message + '</div>';
            }
        }
        
        // Map control functions
        function fitMapToBounds() {
            if (bounds && markers.length > 0) {
                map.fitBounds(bounds);
            }
        }
        
        function clearMap() {
            markers.forEach(marker => marker.setMap(null));
            markers = [];
            bounds = new google.maps.LatLngBounds();
        }
        
        function refreshMap() {
            location.reload();
        }
        
        function exportMapData() {
            const mapData = <?php echo json_encode($map_data); ?>;
            let csv = 'IP Address,Country,City,Device,Time,Latitude,Longitude\n';
            
            mapData.forEach(location => {
                csv += `"${location.ip}","${location.country}","${location.city}","${location.device}","${location.time}","${location.lat}","${location.lng}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ip_logger_map_data_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function handleMapError() {
            console.error('Google Maps API failed to load');
            document.getElementById('map').innerHTML = '<div class="alert alert-danger">Failed to load Google Maps. Please check your API key configuration.</div>';
        }
        <?php endif; ?>
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-success');
                
                setTimeout(function() {
                    button.innerHTML = originalHTML;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 2000);
            });
        }
        
        function showTargetDetails(target) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <p><strong>IP Address:</strong> ${target.ip_address}</p>
                        <p><strong>Device Type:</strong> ${target.device_type}</p>
                        <p><strong>Clicked At:</strong> ${target.clicked_at}</p>
                        <p><strong>User Agent:</strong> <small>${target.user_agent}</small></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Location Information</h6>
                        <p><strong>Country:</strong> ${target.country || 'Unknown'}</p>
                        <p><strong>Region:</strong> ${target.region || 'Unknown'}</p>
                        <p><strong>City:</strong> ${target.city || 'Unknown'}</p>
                        <p><strong>Coordinates:</strong> ${target.latitude && target.longitude ? target.latitude + ', ' + target.longitude : 'Unknown'}</p>
                        <p><strong>Timezone:</strong> ${target.timezone || 'Unknown'}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Network Information</h6>
                        <p><strong>ISP:</strong> ${target.isp || 'Unknown'}</p>
                        <p><strong>Organization:</strong> ${target.organization || 'Unknown'}</p>
                        <p><strong>AS Number:</strong> ${target.as_number || 'Unknown'}</p>
                        <p><strong>Referer:</strong> ${target.referer || 'Direct'}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('targetDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('targetDetailsModal')).show();
        }
    </script>
</body>
</html>
