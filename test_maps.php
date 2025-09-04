<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Sample map data for testing
$test_map_data = [
    [
        'lat' => 40.7128,
        'lng' => -74.0060,
        'title' => 'New York, USA',
        'info' => '
            <div style="min-width: 250px; padding: 10px;">
                <h6 style="margin: 0 0 10px 0; color: #333;">
                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                    New York, USA
                </h6>
                <div style="font-size: 12px; line-height: 1.4;">
                    <p style="margin: 5px 0;"><strong>IP Address:</strong> 192.168.1.100</p>
                    <p style="margin: 5px 0;"><strong>Device:</strong> Desktop</p>
                    <p style="margin: 5px 0;"><strong>ISP:</strong> Verizon</p>
                    <p style="margin: 5px 0;"><strong>Time:</strong> 2024-01-15 10:30:00</p>
                    <p style="margin: 5px 0;"><strong>Coordinates:</strong> 40.7128, -74.0060</p>
                </div>
            </div>
        ',
        'ip' => '192.168.1.100',
        'device' => 'desktop',
        'country' => 'USA',
        'city' => 'New York',
        'time' => '2024-01-15 10:30:00'
    ],
    [
        'lat' => 51.5074,
        'lng' => -0.1278,
        'title' => 'London, UK',
        'info' => '
            <div style="min-width: 250px; padding: 10px;">
                <h6 style="margin: 0 0 10px 0; color: #333;">
                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                    London, UK
                </h6>
                <div style="font-size: 12px; line-height: 1.4;">
                    <p style="margin: 5px 0;"><strong>IP Address:</strong> 192.168.1.101</p>
                    <p style="margin: 5px 0;"><strong>Device:</strong> Mobile</p>
                    <p style="margin: 5px 0;"><strong>ISP:</strong> BT</p>
                    <p style="margin: 5px 0;"><strong>Time:</strong> 2024-01-15 11:45:00</p>
                    <p style="margin: 5px 0;"><strong>Coordinates:</strong> 51.5074, -0.1278</p>
                </div>
            </div>
        ',
        'ip' => '192.168.1.101',
        'device' => 'mobile',
        'country' => 'UK',
        'city' => 'London',
        'time' => '2024-01-15 11:45:00'
    ],
    [
        'lat' => 35.6762,
        'lng' => 139.6503,
        'title' => 'Tokyo, Japan',
        'info' => '
            <div style="min-width: 250px; padding: 10px;">
                <h6 style="margin: 0 0 10px 0; color: #333;">
                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                    Tokyo, Japan
                </h6>
                <div style="font-size: 12px; line-height: 1.4;">
                    <p style="margin: 5px 0;"><strong>IP Address:</strong> 192.168.1.102</p>
                    <p style="margin: 5px 0;"><strong>Device:</strong> Tablet</p>
                    <p style="margin: 5px 0;"><strong>ISP:</strong> NTT</p>
                    <p style="margin: 5px 0;"><strong>Time:</strong> 2024-01-15 12:15:00</p>
                    <p style="margin: 5px 0;"><strong>Coordinates:</strong> 35.6762, 139.6503</p>
                </div>
            </div>
        ',
        'ip' => '192.168.1.102',
        'device' => 'tablet',
        'country' => 'Japan',
        'city' => 'Tokyo',
        'time' => '2024-01-15 12:15:00'
    ],
    [
        'lat' => -33.8688,
        'lng' => 151.2093,
        'title' => 'Sydney, Australia',
        'info' => '
            <div style="min-width: 250px; padding: 10px;">
                <h6 style="margin: 0 0 10px 0; color: #333;">
                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                    Sydney, Australia
                </h6>
                <div style="font-size: 12px; line-height: 1.4;">
                    <p style="margin: 5px 0;"><strong>IP Address:</strong> 192.168.1.103</p>
                    <p style="margin: 5px 0;"><strong>Device:</strong> Desktop</p>
                    <p style="margin: 5px 0;"><strong>ISP:</strong> Telstra</p>
                    <p style="margin: 5px 0;"><strong>Time:</strong> 2024-01-15 13:20:00</p>
                    <p style="margin: 5px 0;"><strong>Coordinates:</strong> -33.8688, 151.2093</p>
                </div>
            </div>
        ',
        'ip' => '192.168.1.103',
        'device' => 'desktop',
        'country' => 'Australia',
        'city' => 'Sydney',
        'time' => '2024-01-15 13:20:00'
    ]
];

$total_locations = count($test_map_data);
$unique_countries = count(array_unique(array_column($test_map_data, 'country')));
$unique_cities = count(array_unique(array_column($test_map_data, 'city')));
$device_types = array_count_values(array_column($test_map_data, 'device'));
$most_common_device = array_keys($device_types, max($device_types))[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Google Maps - IP Logger</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        
        .test-info {
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
                    <h1><i class="fas fa-map"></i> Google Maps Test</h1>
                    <a href="view_targets.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Targets
                    </a>
                </div>
                
                <div class="test-info">
                    <h5><i class="fas fa-info-circle"></i> Test Information</h5>
                    <p>This page tests the Google Maps functionality with sample data. The map should display 4 markers representing different locations around the world.</p>
                    <ul>
                        <li><strong>New York, USA</strong> - Desktop user (Red marker)</li>
                        <li><strong>London, UK</strong> - Mobile user (Green marker)</li>
                        <li><strong>Tokyo, Japan</strong> - Tablet user (Yellow marker)</li>
                        <li><strong>Sydney, Australia</strong> - Desktop user (Red marker)</li>
                    </ul>
                </div>
                
                <!-- Map Statistics -->
                <div class="map-stats">
                    <div class="map-stat">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo $total_locations; ?> Test Locations
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
                                <i class="fas fa-download"></i> Export Test Data
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
                
                <!-- Test Results -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Expected Features:</h6>
                                <ul>
                                    <li>✅ Interactive Google Map</li>
                                    <li>✅ Color-coded markers by device type</li>
                                    <li>✅ Clickable markers with detailed info</li>
                                    <li>✅ Map controls (zoom, street view, fullscreen)</li>
                                    <li>✅ Automatic bounds fitting</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Test Data:</h6>
                                <ul>
                                    <li>📍 4 sample locations worldwide</li>
                                    <li>🌍 4 different countries</li>
                                    <li>📱 3 different device types</li>
                                    <li>🕒 Various timestamps</li>
                                    <li>🌐 Different ISPs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap" async defer onerror="handleMapError()"></script>
    
    <script>
        let map;
        let markers = [];
        let bounds;
        
        function initMap() {
            try {
                const mapData = <?php echo json_encode($test_map_data); ?>;
                
                if (mapData.length === 0) {
                    console.log('No test map data available');
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
                
                console.log('Google Maps test initialized successfully with', mapData.length, 'markers');
            } catch (error) {
                console.error('Error initializing Google Maps test:', error);
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
            const mapData = <?php echo json_encode($test_map_data); ?>;
            let csv = 'IP Address,Country,City,Device,Time,Latitude,Longitude\n';
            
            mapData.forEach(location => {
                csv += `"${location.ip}","${location.country}","${location.city}","${location.device}","${location.time}","${location.lat}","${location.lng}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ip_logger_test_map_data_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function handleMapError() {
            console.error('Google Maps API failed to load');
            document.getElementById('map').innerHTML = '<div class="alert alert-danger">Failed to load Google Maps. Please check your API key configuration.</div>';
        }
    </script>
</body>
</html> 