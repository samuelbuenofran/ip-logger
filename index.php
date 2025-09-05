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
    $no_expiry = isset($_POST['no_expiry']) ? 1 : 0;
    $password = $_POST['password'];
    
    // Validate input
    if (!isValidUrl($original_url)) {
        redirectWithMessage('index.php', 'Please enter a valid URL', 'error');
    }
    
    if (strlen($password) < 3) {
        redirectWithMessage('index.php', 'Password must be at least 3 characters long', 'error');
    }
    
    // Generate unique short code
    $short_code = generateShortCode();
    
    // Set expiry date (default 30 days if not set to never expire)
    $expiry_date = $no_expiry ? NULL : date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO links (original_url, short_code, password, expiry_date, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$original_url, $short_code, $hashed_password, $expiry_date]);
    
    // Get the link ID for email notification
    $linkId = $conn->lastInsertId();
    
    // Send email notification for new link creation
    sendNewLinkNotification($linkId);
    
    redirectWithMessage('index.php', 'Link created successfully! Short URL: ' . BASE_URL . $short_code, 'success');
}

// Get all links for display
$stmt = $conn->query("SELECT * FROM links ORDER BY created_at DESC");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Logger - URL Shortener & Tracker</title>
    
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
</head>
<body>
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
                            <a class="nav-link active" href="index.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                                         <div class="btn-toolbar mb-2 mb-md-0">
                         <a href="create_link.php" class="btn btn-primary me-2">
                             <i class="fas fa-plus"></i> Create New Link
                         </a>
                     </div>
                </div>

                <!-- Alert Messages -->
                <?php echo displayMessage(); ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Links</h5>
                                        <h2><?php echo count($links); ?></h2>
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
                                        <h5 class="card-title">Active Links</h5>
                                        <h2><?php echo count(array_filter($links, function($link) { return $link['expiry_date'] === NULL || strtotime($link['expiry_date']) > time(); })); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
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
                                        <h5 class="card-title">Total Clicks</h5>
                                        <h2><?php 
                                            $stmt = $conn->query("SELECT COUNT(*) as total FROM targets");
                                            echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                        ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-mouse-pointer fa-2x"></i>
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
                                        <h5 class="card-title">Unique Visitors</h5>
                                        <h2><?php 
                                            $stmt = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM targets");
                                            echo $stmt->fetch(PDO::FETCH_ASSOC)['unique_visitors'];
                                        ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Links Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-link"></i> Recent Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Short Code</th>
                                        <th>Original URL</th>
                                        <th>Created</th>
                                        <th>Expires</th>
                                        <th>Clicks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($links, 0, 10) as $link): ?>
                                    <tr>
                                        <td><?php echo $link['id']; ?></td>
                                        <td>
                                            <code><?php echo $link['short_code']; ?></code>
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo BASE_URL . $link['short_code']; ?>')" title="Copy URL">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($link['original_url']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($link['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($link['expiry_date'] === NULL) {
                                                echo '<span class="badge bg-success">Never</span>';
                                            } else {
                                                $expiry = strtotime($link['expiry_date']);
                                                if ($expiry > time()) {
                                                    echo '<span class="badge bg-warning">' . date('M j, Y', $expiry) . '</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">Expired</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $stmt = $conn->prepare("SELECT COUNT(*) as clicks FROM targets WHERE link_id = ?");
                                            $stmt->execute([$link['id']]);
                                            echo $stmt->fetch(PDO::FETCH_ASSOC)['clicks'];
                                            ?>
                                        </td>
                                        <td>
                                            <a href="view_targets.php?link_id=<?php echo $link['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
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

    <!-- Create Link Modal -->
    <div class="modal fade" id="createLinkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_link">
                        
                        <div class="mb-3">
                            <label for="original_url" class="form-label">Original URL</label>
                            <input type="url" class="form-control" id="original_url" name="original_url" required>
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <!-- Copy to Clipboard Function -->
    <script>
        function copyToClipboard(text) {
            // Try modern clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess();
                }).catch(function(err) {
                    console.error('Clipboard API failed:', err);
                    fallbackCopyToClipboard(text);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyToClipboard(text);
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
    </script>
    
    <!-- Toast Notification Styles -->
    <style>
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
    </script>
</body>
</html>
