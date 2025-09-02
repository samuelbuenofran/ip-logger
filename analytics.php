<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

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

// Get hourly distribution
$stmt = $conn->query("
    SELECT HOUR(clicked_at) as hour, COUNT(*) as clicks 
    FROM targets 
    GROUP BY HOUR(clicked_at) 
    ORDER BY hour
");
$hourlyStats = $stmt->fetchAll();

// Prepare data for charts
$chartData = [
    'recentActivity' => $recentActivity,
    'topCountries' => $topCountries,
    'deviceStats' => $deviceStats,
    'hourlyStats' => $hourlyStats
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - IP Logger</title>
    
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
                            <a class="nav-link active" href="analytics.php">
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
                    <h1 class="h2">Analytics Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="test_maps.php" class="btn btn-info me-2" target="_blank">
                            <i class="fas fa-map"></i> Test Maps
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Links</h5>
                                        <h2><?php echo $totalLinks; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-link fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
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
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white">
                            <div class="card-body">
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
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
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
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Recent Activity Chart -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Recent Activity (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="recentActivityChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Device Statistics -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-mobile-alt"></i> Device Types</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="deviceChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Charts Row -->
                <div class="row mb-4">
                    <!-- Top Countries -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-flag"></i> Top Countries</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="countriesChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hourly Distribution -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock"></i> Hourly Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="hourlyChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Detailed Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Value</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Links Created</td>
                                        <td><?php echo $totalLinks; ?></td>
                                        <td>100%</td>
                                    </tr>
                                    <tr>
                                        <td>Total Clicks</td>
                                        <td><?php echo $totalClicks; ?></td>
                                        <td><?php echo $totalLinks > 0 ? round(($totalClicks / $totalLinks) * 100, 1) : 0; ?>% per link</td>
                                    </tr>
                                    <tr>
                                        <td>Unique Visitors</td>
                                        <td><?php echo $uniqueVisitors; ?></td>
                                        <td><?php echo $totalClicks > 0 ? round(($uniqueVisitors / $totalClicks) * 100, 1) : 0; ?>% of clicks</td>
                                    </tr>
                                    <tr>
                                        <td>Countries Reached</td>
                                        <td><?php echo $countries; ?></td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>Average Clicks per Link</td>
                                        <td><?php echo $totalLinks > 0 ? round($totalClicks / $totalLinks, 1) : 0; ?></td>
                                        <td>-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart data from PHP
        const chartData = <?php echo json_encode($chartData); ?>;
        
        // Recent Activity Chart
        const recentActivityCtx = document.getElementById('recentActivityChart').getContext('2d');
        new Chart(recentActivityCtx, {
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
        
        // Hourly Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: chartData.hourlyStats.map(item => item.hour + ':00'),
                datasets: [{
                    label: 'Clicks',
                    data: chartData.hourlyStats.map(item => item.clicks),
                    backgroundColor: 'rgba(255, 206, 86, 0.8)'
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
    </script>
</body>
</html>
