<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/enhanced_geolocation.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Initialize enhanced geolocation
$enhanced_geo = new EnhancedGeolocation();
$enhanced_geo->__init($conn);

// Test current IP
$current_ip = getClientIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Get enhanced geolocation data
$enhanced_data = $enhanced_geo->getPreciseLocation($current_ip, $user_agent, []);

// Get recent targets (check if enhanced columns exist first)
$stmt = $conn->query("SHOW COLUMNS FROM targets LIKE 'confidence_score'");
$has_enhanced_columns = $stmt->fetch();

if ($has_enhanced_columns) {
    // Use enhanced query if columns exist
    $stmt = $conn->query("
        SELECT t.*, l.short_code, l.original_url 
        FROM targets t 
        JOIN links l ON t.link_id = l.id 
        WHERE t.confidence_score IS NOT NULL
        ORDER BY t.clicked_at DESC 
        LIMIT 10
    ");
} else {
    // Use basic query if enhanced columns don't exist yet
    $stmt = $conn->query("
        SELECT t.*, l.short_code, l.original_url 
        FROM targets t 
        JOIN links l ON t.link_id = l.id 
        ORDER BY t.clicked_at DESC 
        LIMIT 10
    ");
}
$recent_targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_targets = $conn->query("SELECT COUNT(*) FROM targets")->fetchColumn();

if ($has_enhanced_columns) {
    $targets_with_confidence = $conn->query("SELECT COUNT(*) FROM targets WHERE confidence_score IS NOT NULL")->fetchColumn();
    $avg_confidence = $conn->query("SELECT AVG(confidence_score) FROM targets WHERE confidence_score IS NOT NULL")->fetchColumn();
    $vpn_detected_count = $conn->query("SELECT COUNT(*) FROM targets WHERE vpn_detected = 1")->fetchColumn();
    $mobile_network_count = $conn->query("SELECT COUNT(*) FROM targets WHERE mobile_network = 1")->fetchColumn();
} else {
    $targets_with_confidence = 0;
    $avg_confidence = 0;
    $vpn_detected_count = 0;
    $mobile_network_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Geolocation Test - IP Logger</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1.5rem;
        }
        
        .confidence-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .confidence-high {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .confidence-medium {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .confidence-low {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }
        
        .network-badge {
            font-size: 0.7rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            margin: 0.2rem;
            display: inline-block;
        }
        
        .network-mobile {
            background: #28a745;
            color: white;
        }
        
        .network-isp {
            background: #17a2b8;
            color: white;
        }
        
        .network-vpn {
            background: #dc3545;
            color: white;
        }
        
        .network-datacenter {
            background: #6c757d;
            color: white;
        }
        
        .accuracy-meter {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .accuracy-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc3545, #ffc107, #28a745);
            transition: width 0.3s ease;
        }
        
        .data-source-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.5rem;
            margin: 0.2rem;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .enhanced-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #667eea;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
                        <p class="text-muted">Enhanced Geolocation</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_link.php">
                                <i class="fas fa-plus"></i> Create Link
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_targets.php">
                                <i class="fas fa-map-marker-alt"></i> Geolocation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="test_enhanced_geolocation.php">
                                <i class="fas fa-rocket"></i> Enhanced Test
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-rocket"></i> Enhanced Geolocation Test</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="enhance_geolocation_database.php" class="btn btn-primary">
                            <i class="fas fa-database"></i> Update Database
                        </a>
                    </div>
                </div>

                <!-- Database Status Alert -->
                <?php if (!$has_enhanced_columns): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Database Update Required:</strong> 
                    Enhanced geolocation columns are not yet installed. 
                    <a href="enhance_geolocation_database.php" class="btn btn-sm btn-primary ms-2">
                        <i class="fas fa-database"></i> Update Database Now
                    </a>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <strong>Enhanced Geolocation Active:</strong> 
                    All advanced features are available!
                </div>
                <?php endif; ?>

                <!-- Current IP Enhanced Data -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Your Current Location (Enhanced)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Basic Information</h6>
                                <p><strong>IP Address:</strong> <code><?php echo $current_ip; ?></code></p>
                                <p><strong>Country:</strong> <?php echo $enhanced_data['country'] ?? 'Unknown'; ?></p>
                                <p><strong>City:</strong> <?php echo $enhanced_data['city'] ?? 'Unknown'; ?></p>
                                <p><strong>ISP:</strong> <?php echo $enhanced_data['isp'] ?? 'Unknown'; ?></p>
                                <p><strong>Coordinates:</strong> 
                                    <?php echo $enhanced_data['latitude'] ? $enhanced_data['latitude'] . ', ' . $enhanced_data['longitude'] : 'Unknown'; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Enhanced Analysis</h6>
                                <p>
                                    <strong>Confidence Score:</strong> 
                                    <span class="confidence-badge <?php 
                                        $confidence = $enhanced_data['confidence_score'] ?? 0;
                                        if ($confidence >= 80) echo 'confidence-high';
                                        elseif ($confidence >= 60) echo 'confidence-medium';
                                        else echo 'confidence-low';
                                    ?>">
                                        <?php echo number_format($confidence, 1); ?>%
                                    </span>
                                </p>
                                <p><strong>Accuracy:</strong> <?php echo $enhanced_data['accuracy'] ?? 'Unknown'; ?> meters</p>
                                <p><strong>Precision Level:</strong> <?php echo ucfirst($enhanced_data['precision_level'] ?? 'unknown'); ?></p>
                                <p><strong>Location Method:</strong> <?php echo ucfirst($enhanced_data['location_method'] ?? 'unknown'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Network Analysis -->
                        <div class="mt-4">
                            <h6>Network Analysis</h6>
                            <div class="network-badges">
                                <?php if ($enhanced_data['network_analysis']['mobile_network']): ?>
                                    <span class="network-badge network-mobile">
                                        <i class="fas fa-mobile-alt"></i> Mobile Network
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($enhanced_data['network_analysis']['isp_network']): ?>
                                    <span class="network-badge network-isp">
                                        <i class="fas fa-wifi"></i> ISP Network
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($enhanced_data['network_analysis']['vpn_detected']): ?>
                                    <span class="network-badge network-vpn">
                                        <i class="fas fa-shield-alt"></i> VPN Detected
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($enhanced_data['network_analysis']['data_center']): ?>
                                    <span class="network-badge network-datacenter">
                                        <i class="fas fa-server"></i> Data Center
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Data Sources -->
                        <div class="mt-4">
                            <h6>Data Sources Used</h6>
                            <div class="data-sources">
                                <?php foreach ($enhanced_data['data_sources'] as $source): ?>
                                    <span class="data-source-item">
                                        <i class="fas fa-database"></i> <?php echo ucfirst($source); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Features Overview -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Enhanced Features</h5>
                    </div>
                    <div class="card-body">
                        <div class="enhanced-features">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-satellite"></i>
                                </div>
                                <h6>Multi-Source Fusion</h6>
                                <p class="text-muted">Combines data from multiple geolocation APIs for higher accuracy</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <h6>ML Refinement</h6>
                                <p class="text-muted">Machine learning algorithms refine coordinates based on historical data</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h6>VPN Detection</h6>
                                <p class="text-muted">Advanced detection of VPNs, proxies, and data centers</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-fingerprint"></i>
                                </div>
                                <h6>Device Fingerprinting</h6>
                                <p class="text-muted">Canvas, WebGL, audio, and font fingerprinting for device identification</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h6>Historical Analysis</h6>
                                <p class="text-muted">Analyzes previous location data for consistency and accuracy</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h6>Confidence Scoring</h6>
                                <p class="text-muted">Dynamic confidence scores based on data quality and consistency</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Enhanced Tracking Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($total_targets); ?></div>
                                <div class="stat-label">Total Targets</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($targets_with_confidence); ?></div>
                                <div class="stat-label">Enhanced Targets</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($avg_confidence, 1); ?>%</div>
                                <div class="stat-label">Average Confidence</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($vpn_detected_count); ?></div>
                                <div class="stat-label">VPN Detected</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($mobile_network_count); ?></div>
                                <div class="stat-label">Mobile Networks</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Enhanced Targets -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Recent Enhanced Targets</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_targets)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                <h5>No Enhanced Data Yet</h5>
                                <p class="text-muted">Enhanced tracking data will appear here after you update your database and create some links.</p>
                                <a href="enhance_geolocation_database.php" class="btn btn-primary">
                                    <i class="fas fa-database"></i> Update Database First
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Location</th>
                                            <th>Confidence</th>
                                            <th>Network</th>
                                            <th>Device</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_targets as $target): ?>
                                        <tr>
                                            <td><code><?php echo $target['ip_address']; ?></code></td>
                                            <td>
                                                <?php if ($target['city'] && $target['country']): ?>
                                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                                    <?php echo $target['city'] . ', ' . $target['country']; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="confidence-badge <?php 
                                                    $confidence = $target['confidence_score'] ?? 0;
                                                    if ($confidence >= 80) echo 'confidence-high';
                                                    elseif ($confidence >= 60) echo 'confidence-medium';
                                                    else echo 'confidence-low';
                                                ?>">
                                                    <?php echo number_format($confidence, 1); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($target['vpn_detected']): ?>
                                                    <span class="network-badge network-vpn">VPN</span>
                                                <?php elseif ($target['mobile_network']): ?>
                                                    <span class="network-badge network-mobile">Mobile</span>
                                                <?php else: ?>
                                                    <span class="network-badge network-isp">ISP</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $target['device_type'] === 'mobile' ? 'success' : 
                                                        ($target['device_type'] === 'tablet' ? 'warning' : 'primary'); 
                                                ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $target['device_type'] === 'mobile' ? 'mobile-alt' : 
                                                            ($target['device_type'] === 'tablet' ? 'tablet-alt' : 'desktop'); 
                                                    ?>"></i>
                                                    <?php echo ucfirst($target['device_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($target['clicked_at'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Enhanced Tracking JS -->
    <script src="assets/js/enhanced_tracking.js"></script>
</body>
</html>
