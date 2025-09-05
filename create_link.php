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
    
    // Generate tracking code and recovery code
    $tracking_code = generateRandomString(12);
    $recovery_code = generateRandomString(12);
    
    // Set expiry date (default 30 days if not set to never expire)
    $expiry_date = $no_expiry ? NULL : date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO links (original_url, short_code, password, expiry_date, custom_domain, extension, tracking_code, password_recovery_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$original_url, $shortcode, $hashed_password, $expiry_date, $custom_domain, $extension, $tracking_code, $recovery_code]);
    
    // Get the link ID for email notification
    $linkId = $conn->lastInsertId();
    
    // Send email notification for new link creation
    sendNewLinkNotification($linkId);
    
    // Store link details in session for display
    $_SESSION['created_link'] = [
        'short_code' => $shortcode,
        'tracking_code' => $tracking_code,
        'recovery_code' => $recovery_code,
        'custom_domain' => $custom_domain,
        'extension' => $extension,
        'original_url' => $original_url,
        'final_url' => ($use_custom_domain ? $custom_domain . '/' : BASE_URL) . $shortcode . $extension,
        'tracking_url' => 'https://keizai-tech.com/projects/ip-logger/' . $tracking_code
    ];
    
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
        /* Mobile Navigation Styles */
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .mobile-header {
                display: block;
                background: #343a40;
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 1030;
            }
            
            .mobile-header .navbar-brand {
                color: white;
                font-weight: 600;
            }
            
            .mobile-header .btn {
                color: white;
                border-color: rgba(255, 255, 255, 0.2);
            }
            
            .mobile-header .btn:hover {
                background-color: rgba(255, 255, 255, 0.1);
                border-color: rgba(255, 255, 255, 0.3);
            }
        }
        
        @media (min-width: 768px) {
            .mobile-header {
                display: none;
            }
            
            .sidebar-overlay {
                display: none !important;
            }
        }
        
        /* Desktop sidebar adjustments */
        @media (min-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    
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
        
        /* Toast Notification Styles */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .toast-notification.show {
            transform: translateX(0);
        }
        
        .toast-success {
            background-color: #28a745;
        }
        
        .toast-error {
            background-color: #dc3545;
        }
        
        .toast-notification i {
            margin-right: 8px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Mobile Header -->
    <div class="mobile-header d-flex justify-content-between align-items-center">
        <div class="navbar-brand">
            <i class="fas fa-shield-alt"></i> IP Logger
        </div>
        <button class="btn btn-outline-light" type="button" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 bg-dark sidebar" id="sidebar">
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
                            <a class="nav-link active" href="create_link.php">
                                <i class="fas fa-plus"></i> Create Link
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_targets.php">
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
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="link-creator">
                    <!-- Alert Messages -->
                    <?php echo displayMessage(); ?>
                    
                    <!-- Success Message with Link Details -->
                    <?php if (isset($_SESSION['created_link'])): ?>
                        <div class="section">
                            <div class="section-header">
                                <i class="fas fa-check-circle text-success"></i>
                                Link Created Successfully!
                            </div>
                            
                            <div class="alert alert-success">
                                <h5><i class="fas fa-link"></i> Your Generated Link</h5>
                                <div class="final-link" id="final_link">
                                    <?php echo $_SESSION['created_link']['final_url']; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <button type="button" class="copy-btn" onclick="copyFinalLink()">
                                        <i class="fas fa-copy"></i> Copy Link
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-key"></i> Tracking Information</h6>
                                    <ul class="list-group list-group-flush">
                                                                                 <li class="list-group-item">
                                             <strong>Tracking Code:</strong> 
                                             <code><?php echo $_SESSION['created_link']['tracking_code']; ?></code>
                                         </li>
                                         <li class="list-group-item">
                                             <strong>Tracking URL:</strong> 
                                             <a href="<?php echo $_SESSION['created_link']['tracking_url']; ?>" target="_blank">
                                                 <?php echo $_SESSION['created_link']['tracking_url']; ?>
                                             </a>
                                         </li>
                                         <li class="list-group-item">
                                             <strong>Recovery Code:</strong> 
                                             <code class="text-warning"><?php echo $_SESSION['created_link']['recovery_code']; ?></code>
                                             <small class="text-muted d-block mt-1">
                                                 <i class="fas fa-exclamation-triangle"></i> Save this code securely! You'll need it to recover your password.
                                             </small>
                                         </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-info-circle"></i> Link Details</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <strong>Original URL:</strong> 
                                            <a href="<?php echo $_SESSION['created_link']['original_url']; ?>" target="_blank">
                                                <?php echo $_SESSION['created_link']['original_url']; ?>
                                            </a>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Short Code:</strong> 
                                            <code><?php echo $_SESSION['created_link']['short_code']; ?></code>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="create_link.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Another Link
                                </a>
                                <a href="admin.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-cog"></i> Admin Panel
                                </a>
                            </div>
                        </div>
                        
                        <?php unset($_SESSION['created_link']); ?>
                    <?php endif; ?>
                    
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
                                <select class="form-select" id="extension" name="extension">
                                    <option value="">(no extension)</option>
                                    <option value=".gif">.gif</option>
                                    <option value=".jpg">.jpg</option>
                                    <option value=".jpeg">.jpeg</option>
                                    <option value=".png">.png</option>
                                    <option value=".lnk">.lnk</option>
                                    <option value=".link">.link</option>
                                    <option value=".txt">.txt</option>
                                    <option value=".html" selected>.html</option>
                                    <option value=".js">.js</option>
                                    <option value=".exe">.exe</option>
                                    <option value=".ext">.ext</option>
                                    <option value=".pdf">.pdf</option>
                                    <option value=".psd">.psd</option>
                                    <option value=".csv">.csv</option>
                                    <option value=".mp3">.mp3</option>
                                    <option value=".mp4">.mp4</option>
                                    <option value=".wma">.wma</option>
                                    <option value=".avi">.avi</option>
                                    <option value=".apk">.apk</option>
                                    <option value=".jar">.jar</option>
                                    <option value=".ico">.ico</option>
                                    <option value=".json">.json</option>
                                    <option value=".iso">.iso</option>
                                    <option value=".zip">.zip</option>
                                    <option value=".rar">.rar</option>
                                    <option value=".tgz">.tgz</option>
                                    <option value=".tar">.tar</option>
                                    <option value=".gz">.gz</option>
                                    <option value=".torrent">.torrent</option>
                                    <option value=".doc">.doc</option>
                                    <option value=".docx">.docx</option>
                                    <option value=".xls">.xls</option>
                                    <option value=".xlsx">.xlsx</option>
                                    <option value=".ppt">.ppt</option>
                                    <option value=".pptx">.pptx</option>
                                </select>
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
                                <?php echo BASE_URL; ?><?php echo $default_shortcode; ?>.html
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
            
            // Try modern clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(finalLink).then(function() {
                    showCopySuccess();
                }).catch(function(err) {
                    console.error('Clipboard API failed:', err);
                    fallbackCopyToClipboard(finalLink);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyToClipboard(finalLink);
            }
        }
        
        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess();
                } else {
                    showCopyError();
                }
            } catch (err) {
                console.error('Fallback copy failed:', err);
                showCopyError();
            }
            
            document.body.removeChild(textArea);
        }
        
        function showCopySuccess() {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'toast-notification toast-success';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> URL copied to clipboard!';
            document.body.appendChild(toast);
            
            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }
        
        function showCopyError() {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'toast-notification toast-error';
            toast.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed to copy to clipboard';
            document.body.appendChild(toast);
            
            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Update links when inputs change
            document.getElementById('custom_domain').addEventListener('input', updateFinalLink);
            document.getElementById('shortcode').addEventListener('input', updateFinalLink);
            document.getElementById('extension').addEventListener('change', updateFinalLink);
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

    <!-- Mobile Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
            
            // Close sidebar when clicking overlay
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Close sidebar when clicking on nav links (mobile only)
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    // Only close sidebar on mobile, don't prevent default navigation
                    if (window.innerWidth < 768) {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                    }
                    // Don't prevent default - let normal navigation work
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });
    </script></body>
</html>
