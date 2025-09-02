<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_url = sanitizeInput($_POST['original_url']);
    $custom_domain = sanitizeInput($_POST['custom_domain'] ?? '');
    $custom_path = sanitizeInput($_POST['custom_path'] ?? '');
    $link_extension = sanitizeInput($_POST['link_extension'] ?? '');
    $password = $_POST['password'];
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Tracking options
    $collect_smart_data = isset($_POST['collect_smart_data']) ? 1 : 0;
    $collect_gps_data = isset($_POST['collect_gps_data']) ? 1 : 0;
    $require_consent = isset($_POST['require_consent']) ? 1 : 0;
    $forward_get_params = isset($_POST['forward_get_params']) ? 1 : 0;
    $destination_preview = isset($_POST['destination_preview']) ? 1 : 0;
    
    // Validation
    if (!isValidUrl($original_url)) {
        redirectWithMessage('create_link.php', 'Please enter a valid URL', 'error');
    }
    
    if (strlen($password) < 3) {
        redirectWithMessage('create_link.php', 'Password must be at least 3 characters long', 'error');
    }
    
    // Generate unique short code and tracking code
    $short_code = generateShortCode();
    $tracking_code = generateTrackingCode();
    
    // Set expiry date (default 30 days)
    $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO links (
            original_url, short_code, tracking_code, password, expiry_date, 
            custom_domain, custom_path, link_extension, notes,
            collect_smart_data, collect_gps_data, require_consent, 
            forward_get_params, destination_preview, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $original_url, $short_code, $tracking_code, $hashed_password, $expiry_date,
        $custom_domain, $custom_path, $link_extension, $notes,
        $collect_smart_data, $collect_gps_data, $require_consent,
        $forward_get_params, $destination_preview
    ]);
    
    $linkId = $conn->lastInsertId();
    
    // Send email notification
    sendNewLinkNotification($linkId);
    
    redirectWithMessage('create_link.php', 'Link created successfully!', 'success');
}

// Get available domains and extensions
$customDomains = getCustomDomains();
$linkExtensions = getLinkExtensions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Link - IP Logger</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .tracking-option {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .tracking-option:hover {
            background: #e9ecef;
        }
        .form-switch {
            padding-left: 2.5em;
        }
        .help-icon {
            color: #6c757d;
            cursor: help;
        }
        .preview-url {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            margin-top: 10px;
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
                            <a class="nav-link" href="analytics.php">
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
                    <h1 class="h2">Create New Link</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php echo displayMessage(); ?>

                <form method="POST" id="createLinkForm">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Link Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-link"></i> Link Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="original_url" class="form-label">Redirect URL <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control" id="original_url" name="original_url" required 
                                               placeholder="https://www.youtube.com/">
                                        <div class="form-text">The URL where visitors will be redirected after clicking your link.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="custom_domain" class="form-label">Custom Domain</label>
                                        <select class="form-select" id="custom_domain" name="custom_domain">
                                            <option value="">Default domain</option>
                                            <?php foreach ($customDomains as $domain): ?>
                                                <option value="<?php echo htmlspecialchars($domain['domain']); ?>">
                                                    <?php echo htmlspecialchars($domain['domain']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="custom_path" class="form-label">Custom Path</label>
                                                <input type="text" class="form-control" id="custom_path" name="custom_path" 
                                                       placeholder="mycustompath">
                                                <div class="form-text">Optional custom path for your link.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="link_extension" class="form-label">Extension</label>
                                                <select class="form-select" id="link_extension" name="link_extension">
                                                    <?php foreach ($linkExtensions as $ext): ?>
                                                        <option value="<?php echo htmlspecialchars($ext['extension']); ?>">
                                                            <?php echo htmlspecialchars($ext['extension'] ?: 'No extension'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preview URL -->
                                    <div class="mb-3">
                                        <label class="form-label">Preview URL</label>
                                        <div class="preview-url" id="previewUrl">
                                            <span class="text-muted">Enter a redirect URL to see preview</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">This password will be required to view tracking data.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Add any notes about this link..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Tracking Options -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-cog"></i> Tracking Options</h5>
                                </div>
                                <div class="card-body">
                                    <div class="tracking-option">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="collect_smart_data" 
                                                   name="collect_smart_data" checked>
                                            <label class="form-check-label" for="collect_smart_data">
                                                <strong>Collect SMART data</strong>
                                                <i class="fas fa-question-circle help-icon ms-2" 
                                                   title="Collect detailed device and browser information"></i>
                                            </label>
                                        </div>
                                        <small class="text-muted">Gather detailed information about device, browser, and system.</small>
                                    </div>

                                    <div class="tracking-option">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="require_consent" 
                                                   name="require_consent">
                                            <label class="form-check-label" for="require_consent">
                                                <strong>Consent collection</strong>
                                                <i class="fas fa-question-circle help-icon ms-2" 
                                                   title="Require user consent before tracking"></i>
                                            </label>
                                        </div>
                                        <small class="text-muted">Require user consent before collecting tracking data (GDPR compliant).</small>
                                    </div>

                                    <div class="tracking-option">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="collect_gps_data" 
                                                   name="collect_gps_data" checked>
                                            <label class="form-check-label" for="collect_gps_data">
                                                <strong>Collect GPS data</strong>
                                                <i class="fas fa-question-circle help-icon ms-2" 
                                                   title="Collect precise GPS location when available"></i>
                                            </label>
                                        </div>
                                        <small class="text-muted">Collect precise GPS location when available (requires user permission).</small>
                                    </div>

                                    <div class="tracking-option">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="forward_get_params" 
                                                   name="forward_get_params">
                                            <label class="form-check-label" for="forward_get_params">
                                                <strong>Forward GET parameters</strong>
                                                <i class="fas fa-question-circle help-icon ms-2" 
                                                   title="Forward URL parameters to destination"></i>
                                            </label>
                                        </div>
                                        <small class="text-muted">Forward URL parameters from tracking link to destination URL.</small>
                                    </div>

                                    <div class="tracking-option">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="destination_preview" 
                                                   name="destination_preview">
                                            <label class="form-check-label" for="destination_preview">
                                                <strong>Destination preview</strong>
                                                <i class="fas fa-question-circle help-icon ms-2" 
                                                   title="Show destination URL before redirecting"></i>
                                                <span class="badge bg-success ms-2">New</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">Show destination URL to users before redirecting them.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Statistics Preview -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistics Preview</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="text-primary">0</h4>
                                            <small class="text-muted">Clicks</small>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-success">0</h4>
                                            <small class="text-muted">Unique</small>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <small class="text-muted">Your link will appear here once created</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100 mb-2">
                                        <i class="fas fa-plus"></i> Create Link
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary w-100 mb-2" onclick="resetForm()">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                    <a href="links.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-list"></i> View All Links
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update preview URL in real-time
        function updatePreview() {
            const originalUrl = document.getElementById('original_url').value;
            const customDomain = document.getElementById('custom_domain').value;
            const customPath = document.getElementById('custom_path').value;
            const extension = document.getElementById('link_extension').value;
            
            let previewUrl = '';
            
            if (originalUrl) {
                if (customDomain) {
                    previewUrl = `https://${customDomain}/`;
                    if (customPath) {
                        previewUrl += customPath;
                    } else {
                        previewUrl += '<?php echo generateShortCode(); ?>';
                    }
                } else {
                    previewUrl = '<?php echo BASE_URL; ?>';
                    if (customPath) {
                        previewUrl += customPath;
                    } else {
                        previewUrl += '<?php echo generateShortCode(); ?>';
                    }
                }
                
                if (extension) {
                    previewUrl += extension;
                }
                
                document.getElementById('previewUrl').innerHTML = `<a href="${previewUrl}" target="_blank">${previewUrl}</a>`;
            } else {
                document.getElementById('previewUrl').innerHTML = '<span class="text-muted">Enter a redirect URL to see preview</span>';
            }
        }
        
        // Add event listeners
        document.getElementById('original_url').addEventListener('input', updatePreview);
        document.getElementById('custom_domain').addEventListener('change', updatePreview);
        document.getElementById('custom_path').addEventListener('input', updatePreview);
        document.getElementById('link_extension').addEventListener('change', updatePreview);
        
        function resetForm() {
            document.getElementById('createLinkForm').reset();
            updatePreview();
        }
        
        // Initialize preview
        updatePreview();
    </script>
</body>
</html>
