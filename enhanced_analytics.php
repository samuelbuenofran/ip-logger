<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get link ID from URL
$link_id = $_GET['link_id'] ?? 0;

if ($link_id) {
    // Get specific link data
    $stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$link_id]);
    $link = $stmt->fetch();
    
    if (!$link) {
        header('Location: index.php?error=link_not_found');
        exit;
    }
    
    // Get targets for this specific link
    $stmt = $conn->prepare("
        SELECT * FROM targets 
        WHERE link_id = ? 
        ORDER BY clicked_at DESC
    ");
    $stmt->execute([$link_id]);
    $targets = $stmt->fetchAll();
    
    // Get link statistics
    $totalClicks = count($targets);
    $uniqueVisitors = count(array_unique(array_column($targets, 'ip_address')));
    $countries = count(array_unique(array_filter(array_column($targets, 'country'))));
    $mobileUsers = count(array_filter($targets, function($t) { return $t['device_type'] === 'mobile'; }));
    
    // Get recent activity (last 7 days)
    $stmt = $conn->prepare("
        SELECT DATE(clicked_at) as date, COUNT(*) as clicks 
        FROM targets 
        WHERE link_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(clicked_at)
        ORDER BY date
    ");
    $stmt->execute([$link_id]);
    $recentActivity = $stmt->fetchAll();
    
    // Get top countries
    $stmt = $conn->prepare("
        SELECT country, COUNT(*) as clicks 
        FROM targets 
        WHERE link_id = ? AND country IS NOT NULL 
        GROUP BY country 
        ORDER BY clicks DESC 
        LIMIT 10
    ");
    $stmt->execute([$link_id]);
    $topCountries = $stmt->fetchAll();
    
    // Get device statistics
    $stmt = $conn->prepare("
        SELECT device_type, COUNT(*) as count 
        FROM targets 
        WHERE link_id = ?
        GROUP BY device_type
    ");
    $stmt->execute([$link_id]);
    $deviceStats = $stmt->fetchAll();
    
    // Get browser statistics
    $stmt = $conn->prepare("
        SELECT browser_name, COUNT(*) as count 
        FROM targets 
        WHERE link_id = ? AND browser_name IS NOT NULL
        GROUP BY browser_name
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$link_id]);
    $browserStats = $stmt->fetchAll();
    
    // Get OS statistics
    $stmt = $conn->prepare("
        SELECT os_name, COUNT(*) as count 
        FROM targets 
        WHERE link_id = ? AND os_name IS NOT NULL
        GROUP BY os_name
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$link_id]);
    $osStats = $stmt->fetchAll();
    
    // Prepare map data
    $map_data = [];
    foreach ($targets as $target) {
        if ($target['latitude'] && $target['longitude']) {
            $map_data[] = [
                'lat' => (float)$target['latitude'],
                'lng' => (float)$target['longitude'],
                'title' => $target['city'] . ', ' . $target['country'],
                'info' => 'IP: ' . $target['ip_address'] . '<br>Device: ' . $target['device_type'] . '<br>Time: ' . formatDate($target['clicked_at'])
            ];
        }
    }
    
    $pageTitle = "Analytics for " . $link['short_code'];
    $showLinkSpecific = true;
} else {
    // Get overall statistics
    $stmt = $conn->query("SELECT COUNT(*) as total_links FROM links");
    $totalLinks = $stmt->fetch()['total_links'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total_clicks FROM targets");
    $totalClicks = $stmt->fetch()['total_clicks'];
    
    $stmt = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM targets");
    $uniqueVisitors = $stmt->fetch()['unique_visitors'];
    
    $stmt = $conn->query("SELECT COUNT(DISTINCT country) as countries FROM targets WHERE country IS NOT NULL");
    $countries = $stmt->fetch()['countries'];
    
    // Get recent activity (last 7 days)
    $stmt = $conn->query("
        SELECT DATE(clicked_at) as date, COUNT(*) as clicks 
        FROM targets 
        WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(clicked_at)
        ORDER BY date
    ");
    $recentActivity = $stmt->fetchAll();
    
    // Get top countries
    $stmt = $conn->query("
        SELECT country, COUNT(*) as clicks 
        FROM targets 
        WHERE country IS NOT NULL 
        GROUP BY country 
        ORDER BY clicks DESC 
        LIMIT 10
    ");
    $topCountries = $stmt->fetchAll();
    
    // Get device statistics
    $stmt = $conn->query("
        SELECT device_type, COUNT(*) as count 
        FROM targets 
        GROUP BY device_type
    ");
    $deviceStats = $stmt->fetchAll();
    
    $pageTitle = "Analytics Dashboard";
    $showLinkSpecific = false;
}

// Prepare data for charts
$chartData = [
    'recentActivity' => $recentActivity,
    'topCountries' => $topCountries,
    'deviceStats' => $deviceStats,
    'browserStats' => $browserStats ?? [],
    'osStats' => $osStats ?? []
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - IP Logger</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .world-map {
            height: 400px;
            background: #f8f9fa;
            border-radius: 10px;
            position: relative;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .country-flag {
            width: 20px;
            height: 15px;
            margin-right: 8px;
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
                            <a class="nav-link" href="view_targets.php">
                                <i class="fas fa-map-marker-alt"></i> Targets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="enhanced_analytics.php">
                                <i class="fas fa-chart-line"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="email_settings.php">
                                <i class="fas fa-envelope"></i> Email Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="privacy.php">
                                <i class="fas fa-user-shield"></i> Privacy Policy
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($showLinkSpecific): ?>
                            <a href="view_targets.php?link_id=<?php echo $link_id; ?>" class="btn btn-info me-2">
                                <i class="fas fa-eye"></i> View Targets
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <?php if ($showLinkSpecific): ?>
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
                                <p><strong>Status:</strong> 
                                    <?php if ($link['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Total Clicks</h5>
                                    <h2><?php echo $totalClicks; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-mouse-pointer fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Unique Visitors</h5>
                                    <h2><?php echo $uniqueVisitors; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Countries</h5>
                                    <h2><?php echo $countries; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-globe fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Mobile Users</h5>
                                    <h2><?php echo $mobileUsers; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-mobile-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- World Map and Charts Row -->
                <div class="row mb-4">
                    <!-- World Map -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-map"></i> Visitor Locations</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($map_data)): ?>
                                    <div id="worldMap" class="world-map"></div>
                                <?php else: ?>
                                    <div class="world-map d-flex align-items-center justify-content-center">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-map fa-3x mb-3"></i>
                                            <h5>No location data available</h5>
                                            <p>Location tracking requires valid IP addresses and geolocation data.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Device Types -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-mobile-alt"></i> Device Types</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="deviceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Recent Activity -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Recent Activity (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Countries -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-flag"></i> Top Countries</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="countriesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Browser and OS Row -->
                <div class="row mb-4">
                    <!-- Browser Statistics -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-browser"></i> Browser Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="browserChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- OS Statistics -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-desktop"></i> Operating Systems</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="osChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($map_data)): ?>
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap" async defer></script>
    <?php endif; ?>
    
    <script>
        // Chart data from PHP
        const chartData = <?php echo json_encode($chartData); ?>;
        
        // Recent Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: chartData.recentActivity.map(item => item.date),
                datasets: [{
                    label: 'Clicks',
                    data: chartData.recentActivity.map(item => item.clicks),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Device Chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.deviceStats.map(item => item.device_type),
                datasets: [{
                    data: chartData.deviceStats.map(item => item.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Countries Chart
        const countriesCtx = document.getElementById('countriesChart').getContext('2d');
        new Chart(countriesCtx, {
            type: 'bar',
            data: {
                labels: chartData.topCountries.map(item => item.country),
                datasets: [{
                    label: 'Clicks',
                    data: chartData.topCountries.map(item => item.clicks),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Browser Chart
        const browserCtx = document.getElementById('browserChart').getContext('2d');
        new Chart(browserCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.browserStats.map(item => item.browser_name),
                datasets: [{
                    data: chartData.browserStats.map(item => item.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // OS Chart
        const osCtx = document.getElementById('osChart').getContext('2d');
        new Chart(osCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.osStats.map(item => item.os_name),
                datasets: [{
                    data: chartData.osStats.map(item => item.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        <?php if (!empty($map_data)): ?>
        // Google Maps initialization
        function initMap() {
            const mapData = <?php echo json_encode($map_data); ?>;
            
            if (mapData.length === 0) return;
            
            const map = new google.maps.Map(document.getElementById('worldMap'), {
                zoom: 2,
                center: { lat: mapData[0].lat, lng: mapData[0].lng },
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });
            
            mapData.forEach(function(location) {
                const marker = new google.maps.Marker({
                    position: { lat: location.lat, lng: location.lng },
                    map: map,
                    title: location.title
                });
                
                const infowindow = new google.maps.InfoWindow({
                    content: location.info
                });
                
                marker.addListener('click', function() {
                    infowindow.open(map, marker);
                });
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>
