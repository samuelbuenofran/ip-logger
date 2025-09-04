<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Test current IP geolocation
$current_ip = getClientIP();
$geo_data = getGeolocationData($current_ip);

// Get recent targets from database
$stmt = $conn->query("
    SELECT t.*, l.short_code, l.original_url 
    FROM targets t 
    JOIN links l ON t.link_id = l.id 
    ORDER BY t.clicked_at DESC 
    LIMIT 10
");
$recent_targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_targets = $conn->query("SELECT COUNT(*) FROM targets")->fetchColumn();
$targets_with_location = $conn->query("SELECT COUNT(*) FROM targets WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn();
$unique_countries = $conn->query("SELECT COUNT(DISTINCT country) FROM targets WHERE country IS NOT NULL AND country != 'Unknown'")->fetchColumn();
$unique_cities = $conn->query("SELECT COUNT(DISTINCT city) FROM targets WHERE city IS NOT NULL AND city != ''")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geolocation Test - IP Logger</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .test-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .geo-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .geo-info h5 {
            color: #007bff;
            margin-bottom: 1rem;
        }
        
        .geo-info p {
            margin-bottom: 0.5rem;
        }
        
        .geo-info strong {
            color: #495057;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        .target-row {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        
        .target-row:hover {
            background: #e9ecef;
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
        
        .test-actions {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-globe"></i> Geolocation Test</h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                
                <div class="test-actions">
                    <h5><i class="fas fa-info-circle"></i> How to Test Real Geolocation</h5>
                    <p>To see real location data, you need to:</p>
                    <ol>
                        <li><strong>Create a link</strong> using the "Create Link" page</li>
                        <li><strong>Share the link</strong> with someone or access it from a different device/network</li>
                        <li><strong>Click the link</strong> - this will automatically capture the visitor's location</li>
                        <li><strong>View the results</strong> in the "Targets" section or here</li>
                    </ol>
                    <p><strong>Note:</strong> The system captures location data automatically when anyone clicks your links!</p>
                </div>
                
                <!-- Current IP Test -->
                <div class="test-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Your Current Location</h3>
                    <div class="geo-info">
                        <h5><i class="fas fa-info-circle"></i> IP Address: <?php echo $current_ip; ?></h5>
                        <p><strong>Country:</strong> <?php echo $geo_data['country']; ?></p>
                        <p><strong>Region:</strong> <?php echo $geo_data['region']; ?></p>
                        <p><strong>City:</strong> <?php echo $geo_data['city']; ?></p>
                        <p><strong>Coordinates:</strong> <?php echo $geo_data['lat'] && $geo_data['lon'] ? $geo_data['lat'] . ', ' . $geo_data['lon'] : 'Not available'; ?></p>
                        <p><strong>ISP:</strong> <?php echo $geo_data['isp']; ?></p>
                        <p><strong>Timezone:</strong> <?php echo $geo_data['timezone']; ?></p>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="test-section">
                    <h3><i class="fas fa-chart-bar"></i> Tracking Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php echo $total_targets; ?></h3>
                            <p>Total Clicks</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $targets_with_location; ?></h3>
                            <p>With Location</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $unique_countries; ?></h3>
                            <p>Countries</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $unique_cities; ?></h3>
                            <p>Cities</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Targets -->
                <div class="test-section">
                    <h3><i class="fas fa-users"></i> Recent Visitors</h3>
                    <?php if (empty($recent_targets)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>No visitors yet.</strong> 
                            Create a link and share it to start seeing real location data!
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_targets as $target): ?>
                            <div class="target-row">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>IP Address:</strong><br>
                                        <code><?php echo $target['ip_address']; ?></code>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Location:</strong><br>
                                        <?php if ($target['city'] && $target['country']): ?>
                                            <span class="location-badge">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo $target['city'] . ', ' . $target['country']; ?>
                                            </span>
                                            <?php if ($target['latitude'] && $target['longitude']): ?>
                                                <br><small class="text-muted">
                                                    <?php echo $target['latitude'] . ', ' . $target['longitude']; ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unknown</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Device:</strong><br>
                                        <span class="badge bg-<?php echo $target['device_type'] === 'mobile' ? 'success' : 'primary'; ?>">
                                            <i class="fas fa-<?php echo $target['device_type'] === 'mobile' ? 'mobile-alt' : 'desktop'; ?>"></i>
                                            <?php echo ucfirst($target['device_type']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>ISP:</strong><br>
                                        <small><?php echo $target['isp'] ?: 'Unknown'; ?></small>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Time:</strong><br>
                                        <small><?php echo date('M j, H:i', strtotime($target['clicked_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Test Instructions -->
                <div class="test-section">
                    <h3><i class="fas fa-lightbulb"></i> Testing Instructions</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Step 1: Create a Test Link</h5>
                            <ol>
                                <li>Go to "Create Link" page</li>
                                <li>Enter any URL (e.g., https://google.com)</li>
                                <li>Set a password</li>
                                <li>Click "Create Link"</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h5>Step 2: Test the Link</h5>
                            <ol>
                                <li>Copy the generated short URL</li>
                                <li>Open it in a different browser/device</li>
                                <li>Or share it with someone</li>
                                <li>Check back here to see the location data</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mt-3">
                        <h6><i class="fas fa-check-circle"></i> What Gets Captured Automatically:</h6>
                        <ul class="mb-0">
                            <li>✅ IP Address</li>
                            <li>✅ Country, Region, City</li>
                            <li>✅ Latitude & Longitude</li>
                            <li>✅ ISP & Organization</li>
                            <li>✅ Device Type (Desktop/Mobile/Tablet)</li>
                            <li>✅ Browser & Operating System</li>
                            <li>✅ Click Time</li>
                            <li>✅ Referrer URL</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
