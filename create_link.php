<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle form submission for creating new links
if (isset($_POST['action']) && $_POST['action'] === 'create_link') {
    $original_url = sanitizeInput($_POST['original_url']);
    $custom_domain = sanitizeInput($_POST['custom_domain']);
    $use_custom_domain = isset($_POST['use_custom_domain']) ? 1 : 0;
    $shortcode = sanitizeInput($_POST['shortcode']);
    $extension = sanitizeInput($_POST['extension']);
    $password = $_POST['password'];
    $no_expiry = isset($_POST['no_expiry']) ? 1 : 0;
    
    // Validate input
    if (!isValidUrl($original_url)) {
        redirectWithMessage('create_link.php', 'Please enter a valid URL', 'error');
    }
    
    if (strlen($password) < 3) {
        redirectWithMessage('create_link.php', 'Password must be at least 3 characters long', 'error');
    }
    
    // Validate custom domain if used
    if ($use_custom_domain && !empty($custom_domain)) {
        if (!filter_var('http://' . $custom_domain, FILTER_VALIDATE_URL)) {
            redirectWithMessage('create_link.php', 'Please enter a valid domain name', 'error');
        }
    }
    
    // Validate shortcode
    if (empty($shortcode) || strlen($shortcode) < 3) {
        redirectWithMessage('create_link.php', 'Shortcode must be at least 3 characters long', 'error');
    }
    
    // Check if shortcode already exists
    $stmt = $conn->prepare("SELECT id FROM links WHERE short_code = ?");
    $stmt->execute([$shortcode]);
    if ($stmt->fetch()) {
        redirectWithMessage('create_link.php', 'This shortcode is already taken. Please choose another one.', 'error');
    }
    
    // Generate tracking code
    $tracking_code = generateRandomString(12);
    
    // Set expiry date (default 30 days if not set to never expire)
    $expiry_date = $no_expiry ? NULL : date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO links (original_url, short_code, password, expiry_date, custom_domain, extension, tracking_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$original_url, $shortcode, $hashed_password, $expiry_date, $custom_domain, $extension, $tracking_code]);
    
    // Get the link ID for email notification
    $linkId = $conn->lastInsertId();
    
    // Send email notification for new link creation
    sendNewLinkNotification($linkId);
    
    redirectWithMessage('create_link.php', 'Link created successfully!', 'success');
}

// Generate default values
$default_shortcode = generateShortCode();
$default_tracking_code = generateRandomString(12);
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
        .link-creator {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .section-header i {
            margin-right: 0.5rem;
            color: #007bff;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .domain-options {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .final-link {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-top: 1rem;
        }
        
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #0056b3;
        }
        
        .tracking-url {
            background: #e9ecef;
            border-radius: 6px;
            padding: 0.5rem;
            font-family: monospace;
            font-size: 0.9rem;
            color: #666;
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
                            <a class="nav-link active" href="create_link.php">
                                <i class="fas fa-plus"></i> Create Link
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
                            <a class="nav-link" href="privacy.php">
                                <i class="fas fa-user-shield"></i> Privacy Policy
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="link-creator">
                    <!-- Alert Messages -->
                    <?php echo displayMessage(); ?>
                    
                    <form method="POST" id="linkForm">
                        <input type="hidden" name="action" value="create_link">
                        
                        <!-- Original Link Section -->
                        <div class="section">
                            <div class="section-header">
                                <i class="fas fa-link"></i>
                                Original Link
                            </div>
                            <div class="mb-3">
                                <label for="original_url" class="form-label">Enter the URL you want to shorten</label>
                                <input type="url" class="form-control" id="original_url" name="original_url" 
                                       placeholder="https://www.example.com" required>
                            </div>
                        </div>
                        
                        <!-- Customize Link Section -->
                        <div class="section">
                            <div class="section-header">
                                <i class="fas fa-check-circle"></i>
                                Customize Link
                            </div>
                            
                            <!-- Domain Options -->
                            <div class="domain-options">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_custom_domain" 
                                               name="use_custom_domain" checked>
                                        <label class="form-check-label" for="use_custom_domain">
                                            Use your own domain
                                        </label>
                                    </div>
                                    <input type="text" class="form-control mt-2" id="custom_domain" name="custom_domain" 
                                           placeholder="yourdomain.com" value="keizai-tech.com">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_default_domain">
                                        <label class="form-check-label" for="use_default_domain">
                                            Use our default domain
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Shortcode -->
                            <div class="mb-3">
                                <label for="shortcode" class="form-label">Shortcode</label>
                                <input type="text" class="form-control" id="shortcode" name="shortcode" 
                                       value="<?php echo $default_shortcode; ?>" required>
                                <div class="form-text">This will be the unique identifier for your shortened link.</div>
                            </div>
                            
                            <!-- Extension -->
                            <div class="mb-3">
                                <label for="extension" class="form-label">Extension</label>
                                <input type="text" class="form-control" id="extension" name="extension" 
                                       value=".html" placeholder=".html">
                                <div class="form-text">Optional file extension to add to your link.</div>
                            </div>
                        </div>
                        
                        <!-- Tracking Information Section -->
                        <div class="section">
                            <div class="section-header">
                                <i class="fas fa-chart-line"></i>
                                Tracking Information
                            </div>
                            
                            <!-- Tracking Code -->
                            <div class="mb-3">
                                <label for="tracking_code" class="form-label">Tracking Code</label>
                                <input type="text" class="form-control" id="tracking_code" name="tracking_code" 
                                       value="<?php echo $default_tracking_code; ?>" readonly>
                                <div class="form-text">This unique code is used to identify and track your link.</div>
                            </div>
                            
                            <!-- Tracking URL -->
                            <div class="mb-3">
                                <label class="form-label">Tracking URL</label>
                                <div class="tracking-url" id="tracking_url">
                                    https://keizai-tech.com/projects/ip-logger/<?php echo $default_tracking_code; ?>
                                </div>
                                <div class="form-text">This URL will be used to access your tracking data.</div>
                            </div>
                        </div>
                        
                        <!-- Security Section -->
                        <div class="section">
                            <div class="section-header">
                                <i class="fas fa-lock"></i>
                                Security Settings
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">This password will be required to view tracking data.</div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="no_expiry" name="no_expiry">
                                <label class="form-check-label" for="no_expiry">
                                    My link does not expire
                                </label>
                            </div>
                        </div>
                        
                        <!-- Your Link Section -->
                        <div class="section">
                            <div class="section-header">
                                <i class="fas fa-link"></i>
                                Your Link
                            </div>
                            
                            <div class="final-link" id="final_link">
                                keizai-tech.com/<?php echo $default_shortcode; ?>.html
                            </div>
                            
                            <div class="text-center mt-3">
                                <button type="button" class="copy-btn" onclick="copyFinalLink()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Create Link
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Update final link when inputs change
        function updateFinalLink() {
            const customDomain = document.getElementById('custom_domain').value || 'keizai-tech.com';
            const shortcode = document.getElementById('shortcode').value || '<?php echo $default_shortcode; ?>';
            const extension = document.getElementById('extension').value || '';
            const useCustomDomain = document.getElementById('use_custom_domain').checked;
            
            let finalLink = '';
            if (useCustomDomain) {
                finalLink = customDomain + '/' + shortcode + extension;
            } else {
                finalLink = '<?php echo BASE_URL; ?>' + shortcode + extension;
            }
            
            document.getElementById('final_link').textContent = finalLink;
        }
        
        // Update tracking URL when tracking code changes
        function updateTrackingUrl() {
            const trackingCode = document.getElementById('tracking_code').value;
            const trackingUrl = 'https://keizai-tech.com/projects/ip-logger/' + trackingCode;
            document.getElementById('tracking_url').textContent = trackingUrl;
        }
        
        // Copy final link to clipboard
        function copyFinalLink() {
            const finalLink = document.getElementById('final_link').textContent;
            navigator.clipboard.writeText(finalLink).then(function() {
                // Show success message
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = '#007bff';
                }, 2000);
            });
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Update links when inputs change
            document.getElementById('custom_domain').addEventListener('input', updateFinalLink);
            document.getElementById('shortcode').addEventListener('input', updateFinalLink);
            document.getElementById('extension').addEventListener('input', updateFinalLink);
            document.getElementById('use_custom_domain').addEventListener('change', updateFinalLink);
            document.getElementById('tracking_code').addEventListener('input', updateTrackingUrl);
            
            // Handle default domain checkbox
            document.getElementById('use_default_domain').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('use_custom_domain').checked = false;
                    document.getElementById('custom_domain').disabled = true;
                } else {
                    document.getElementById('custom_domain').disabled = false;
                }
                updateFinalLink();
            });
            
            // Handle custom domain checkbox
            document.getElementById('use_custom_domain').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('use_default_domain').checked = false;
                    document.getElementById('custom_domain').disabled = false;
                } else {
                    document.getElementById('custom_domain').disabled = true;
                }
                updateFinalLink();
            });
        });
    </script>
</body>
</html>
