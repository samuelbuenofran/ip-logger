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

// Prepare data for Google Maps
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
            height: 400px;
            width: 100%;
            border-radius: 8px;
        }
        .target-card {
            transition: transform 0.2s;
        }
        .target-card:hover {
            transform: translateY(-2px);
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
                            <a class="nav-link active" href="view_targets.php">
                                <i class="fas fa-map-marker-alt"></i> Targets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="fas fa-chart-line"></i> Analytics
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
                         <div id="map"></div>
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
         function initMap() {
             try {
                 const mapData = <?php echo json_encode($map_data); ?>;
                 
                 if (mapData.length === 0) {
                     console.log('No map data available');
                     return;
                 }
                 
                 const map = new google.maps.Map(document.getElementById('map'), {
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
                 
                 console.log('Google Maps initialized successfully with', mapData.length, 'markers');
             } catch (error) {
                 console.error('Error initializing Google Maps:', error);
                 document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error loading map: ' + error.message + '</div>';
             }
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
