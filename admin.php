<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Simple admin authentication
$admin_password = 'admin123'; // Change this to a secure password
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'];

// Handle admin login
if (isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'];
    if ($password === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
        $is_authenticated = true;
    } else {
        $login_error = 'Invalid admin password';
    }
}

// Handle admin logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    $is_authenticated = false;
}

// Show login form if not authenticated
if (!$is_authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - IP Logger</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header text-center">
                            <h4><i class="fas fa-shield-alt"></i> Admin Login</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($login_error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Admin Password</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                </div>
                                <button type="submit" name="admin_login" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
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

// Handle admin actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'delete_link':
            $link_id = (int)$_POST['link_id'];
            $stmt = $conn->prepare("DELETE FROM links WHERE id = ?");
            $stmt->execute([$link_id]);
            redirectWithMessage('admin.php', 'Link deleted successfully!', 'success');
            break;
            
        case 'toggle_expiry':
            $link_id = (int)$_POST['link_id'];
            $current_expiry = $_POST['current_expiry'];
            $new_expiry = $current_expiry === 'NULL' ? date('Y-m-d H:i:s', strtotime('+30 days')) : NULL;
            $stmt = $conn->prepare("UPDATE links SET expiry_date = ? WHERE id = ?");
            $stmt->execute([$new_expiry, $link_id]);
            redirectWithMessage('admin.php', 'Link expiry updated successfully!', 'success');
            break;
            
        case 'regenerate_tracking':
            $link_id = (int)$_POST['link_id'];
            $new_tracking_code = generateRandomString(12);
            $stmt = $conn->prepare("UPDATE links SET tracking_code = ? WHERE id = ?");
            $stmt->execute([$new_tracking_code, $link_id]);
            redirectWithMessage('admin.php', 'Tracking code regenerated successfully!', 'success');
            break;
    }
}

// Get all links with their statistics
$stmt = $conn->query("
    SELECT l.*, 
           COUNT(t.id) as click_count,
           COUNT(DISTINCT t.ip_address) as unique_visitors
    FROM links l 
    LEFT JOIN targets t ON l.id = t.link_id 
    GROUP BY l.id 
    ORDER BY l.created_at DESC
");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overall statistics
$total_links = count($links);
$active_links = count(array_filter($links, function($link) { 
    return $link['expiry_date'] === NULL || strtotime($link['expiry_date']) > time(); 
}));
$total_clicks = array_sum(array_column($links, 'click_count'));
$total_visitors = array_sum(array_column($links, 'unique_visitors'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - IP Logger</title>
    
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
        .admin-content {
            padding: 2rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        .link-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .link-table th {
            background: #f8f9fa;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
        }
        
        .link-table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }
        
        .link-url {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-expiring {
            background: #fff3cd;
            color: #856404;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        
        .search-box {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .generated-urls {
            min-width: 300px;
        }
        
        .url-item {
            margin-bottom: 0.5rem;
        }
        
        .url-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .url-display {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 4px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
        }
        
        .url-text {
            flex: 1;
            font-size: 0.8rem;
            color: #495057;
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            border: 1px solid #dee2e6;
            word-break: break-all;
        }
        
        .url-display .btn {
            flex-shrink: 0;
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
        }
        
        /* Resizable Table Columns */
        .resizable-table {
            table-layout: fixed;
            width: 100%;
        }
        
        .resizable-table th,
        .resizable-table td {
            position: relative;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 8px 12px;
        }
        
        .resizable-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            user-select: none;
        }
        
        .resizable-table th:hover {
            background-color: #e9ecef;
        }
        
        .resizable-table th .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background-color: transparent;
            cursor: col-resize;
            transition: background-color 0.2s ease;
        }
        
        .resizable-table th .resize-handle:hover {
            background-color: #007bff;
        }
        
        .resizable-table th .resize-handle.active {
            background-color: #0056b3;
        }
        
        .resizable-table th .column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .resizable-table th .column-title {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .resizable-table th .resize-icon {
            opacity: 0;
            transition: opacity 0.2s ease;
            font-size: 12px;
            color: #6c757d;
        }
        
        .resizable-table th:hover .resize-icon {
            opacity: 1;
        }
        
        .resizable-table td {
            cursor: help;
        }
        
        .resizable-table td:hover {
            background-color: #f8f9fa;
        }
        
        /* Tooltip styles */
        .table-tooltip {
            position: absolute;
            background-color: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            max-width: 300px;
            word-wrap: break-word;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .table-tooltip.show {
            opacity: 1;
        }
        
        .table-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
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
                            <a class="nav-link active" href="admin.php">
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
                <div class="admin-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="fas fa-cog"></i> Admin Panel</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="create_link.php" class="btn btn-primary me-2">
                                <i class="fas fa-plus"></i> Create New Link
                            </a>
                            <a href="?logout=1" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to logout?')">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php echo displayMessage(); ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $total_links; ?></h3>
                                        <p>Total Links</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-link fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $active_links; ?></h3>
                                        <p>Active Links</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $total_clicks; ?></h3>
                                        <p>Total Clicks</p>
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
                                        <h3><?php echo $total_visitors; ?></h3>
                                        <p>Unique Visitors</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filter -->
                    <div class="search-box">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by shortcode, URL, or tracking code...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="expiring">Expiring Soon</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-secondary w-100" onclick="exportToCSV()">
                                    <i class="fas fa-download"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Links Table -->
                    <div class="link-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 resizable-table" id="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">
                                            <div class="column-header">
                                                <span class="column-title">ID</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 100px;">
                                            <div class="column-header">
                                                <span class="column-title">Short Code</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 200px;">
                                            <div class="column-header">
                                                <span class="column-title">Generated URLs</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 250px;">
                                            <div class="column-header">
                                                <span class="column-title">Original URL</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 100px;">
                                            <div class="column-header">
                                                <span class="column-title">Password</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 120px;">
                                            <div class="column-header">
                                                <span class="column-title">Tracking Code</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 80px;">
                                            <div class="column-header">
                                                <span class="column-title">Status</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 80px;">
                                            <div class="column-header">
                                                <span class="column-title">Clicks</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 120px;">
                                            <div class="column-header">
                                                <span class="column-title">Created</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 120px;">
                                            <div class="column-header">
                                                <span class="column-title">Actions</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($links as $link): ?>
                                    <tr>
                                        <td><?php echo $link['id']; ?></td>
                                        <td>
                                            <code><?php echo $link['short_code']; ?></code>
                                        </td>
                                        <td>
                                            <div class="generated-urls">
                                                <div class="url-item mb-2">
                                                    <label class="url-label">Short URL:</label>
                                                    <div class="url-display">
                                                        <code class="url-text"><?php echo BASE_URL . $link['short_code'] . ($link['extension'] ?: ''); ?></code>
                                                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?php echo BASE_URL . $link['short_code'] . ($link['extension'] ?: ''); ?>')" title="Copy Short URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="url-item">
                                                    <label class="url-label">Tracking URL:</label>
                                                    <div class="url-display">
                                                        <code class="url-text"><?php echo BASE_URL . $link['tracking_code']; ?></code>
                                                        <button class="btn btn-sm btn-outline-info ms-2" onclick="copyToClipboard('<?php echo BASE_URL . $link['tracking_code']; ?>')" title="Copy Tracking URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="link-url" title="<?php echo htmlspecialchars($link['original_url']); ?>">
                                                <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($link['original_url']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="password-field">
                                                <input type="password" class="form-control form-control-sm" value="<?php echo $link['password']; ?>" readonly>
                                                <button class="password-toggle" onclick="togglePassword(this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?php echo $link['tracking_code']; ?></code>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($link['expiry_date'] === NULL) {
                                                echo '<span class="status-badge status-active">Active</span>';
                                            } else {
                                                $expiry = strtotime($link['expiry_date']);
                                                if ($expiry > time()) {
                                                    if ($expiry < strtotime('+7 days')) {
                                                        echo '<span class="status-badge status-expiring">Expiring Soon</span>';
                                                    } else {
                                                        echo '<span class="status-badge status-active">Active</span>';
                                                    }
                                                } else {
                                                    echo '<span class="status-badge status-expired">Expired</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $link['click_count']; ?></span>
                                            <?php if ($link['unique_visitors'] > 0): ?>
                                                <small class="text-muted d-block"><?php echo $link['unique_visitors']; ?> unique</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($link['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_targets.php?link_id=<?php echo $link['id']; ?>" class="btn btn-sm btn-primary" title="View Targets">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-warning" onclick="toggleExpiry(<?php echo $link['id']; ?>, '<?php echo $link['expiry_date']; ?>')" title="Toggle Expiry">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="regenerateTracking(<?php echo $link['id']; ?>)" title="Regenerate Tracking Code">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteLink(<?php echo $link['id']; ?>)" title="Delete Link">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filterValue = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusCell = row.querySelector('td:nth-child(7)');
                const status = statusCell.textContent.trim();
                
                if (filterValue === '' || status.toLowerCase().includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Password toggle
        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            });
        }
        
        // Admin actions
        function deleteLink(linkId) {
            if (confirm('Are you sure you want to delete this link? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_link">
                    <input type="hidden" name="link_id" value="${linkId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleExpiry(linkId, currentExpiry) {
            const action = currentExpiry ? 'remove expiry' : 'set expiry';
            if (confirm(`Are you sure you want to ${action} for this link?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_expiry">
                    <input type="hidden" name="link_id" value="${linkId}">
                    <input type="hidden" name="current_expiry" value="${currentExpiry}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function regenerateTracking(linkId) {
            if (confirm('Are you sure you want to regenerate the tracking code? This will invalidate the old tracking URL.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="regenerate_tracking">
                    <input type="hidden" name="link_id" value="${linkId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Export to CSV
        function exportToCSV() {
            const rows = document.querySelectorAll('tbody tr');
            let csv = 'ID,Short Code,Generated URLs,Original URL,Password,Tracking Code,Status,Clicks,Created\n';
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    const rowData = [];
                    
                    cells.forEach((cell, index) => {
                        if (index < 9) { // Exclude actions column
                            let text = cell.textContent.trim();
                            if (text.includes(',')) {
                                text = '"' + text + '"';
                            }
                            rowData.push(text);
                        }
                    });
                    
                    csv += rowData.join(',') + '\n';
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ip_logger_links_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Resizable Table Columns Functionality
        function initResizableTables() {
            const tables = document.querySelectorAll('.resizable-table');
            
            tables.forEach(table => {
                const headers = table.querySelectorAll('th');
                let isResizing = false;
                let currentHeader = null;
                let startX = 0;
                let startWidth = 0;
                
                // Create tooltip element
                const tooltip = document.createElement('div');
                tooltip.className = 'table-tooltip';
                document.body.appendChild(tooltip);
                
                headers.forEach((header, index) => {
                    const resizeHandle = header.querySelector('.resize-handle');
                    
                    // Mouse down on resize handle
                    resizeHandle.addEventListener('mousedown', (e) => {
                        isResizing = true;
                        currentHeader = header;
                        startX = e.clientX;
                        startWidth = header.offsetWidth;
                        
                        header.classList.add('active');
                        document.body.style.cursor = 'col-resize';
                        document.body.style.userSelect = 'none';
                        
                        e.preventDefault();
                    });
                    
                    // Mouse move during resize
                    document.addEventListener('mousemove', (e) => {
                        if (!isResizing || !currentHeader) return;
                        
                        const newWidth = startWidth + (e.clientX - startX);
                        const minWidth = 50; // Minimum column width
                        const maxWidth = 400; // Maximum column width
                        
                        if (newWidth >= minWidth && newWidth <= maxWidth) {
                            currentHeader.style.width = newWidth + 'px';
                        }
                    });
                    
                    // Mouse up to end resize
                    document.addEventListener('mouseup', () => {
                        if (isResizing && currentHeader) {
                            isResizing = false;
                            currentHeader.classList.remove('active');
                            currentHeader = null;
                            
                            document.body.style.cursor = '';
                            document.body.style.userSelect = '';
                            
                            // Save column widths to localStorage
                            saveColumnWidths(table);
                        }
                    });
                    
                    // Add tooltip functionality to table cells
                    const cells = table.querySelectorAll(`td:nth-child(${index + 1})`);
                    cells.forEach(cell => {
                        cell.addEventListener('mouseenter', (e) => {
                            const cellText = cell.textContent.trim();
                            const cellWidth = cell.offsetWidth;
                            const textWidth = getTextWidth(cellText, '12px Inter, sans-serif');
                            
                            // Only show tooltip if text is truncated
                            if (textWidth > cellWidth) {
                                tooltip.textContent = cellText;
                                tooltip.classList.add('show');
                                
                                // Position tooltip
                                const rect = cell.getBoundingClientRect();
                                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                            }
                        });
                        
                        cell.addEventListener('mouseleave', () => {
                            tooltip.classList.remove('show');
                        });
                    });
                });
                
                // Load saved column widths
                loadColumnWidths(table);
            });
        }
        
        // Helper function to get text width
        function getTextWidth(text, font) {
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            context.font = font;
            return context.measureText(text).width;
        }
        
        // Save column widths to localStorage
        function saveColumnWidths(table) {
            const tableId = table.id || 'default-table';
            const widths = [];
            const headers = table.querySelectorAll('th');
            
            headers.forEach(header => {
                widths.push(header.style.width || header.offsetWidth + 'px');
            });
            
            localStorage.setItem(`table-widths-${tableId}`, JSON.stringify(widths));
        }
        
        // Load column widths from localStorage
        function loadColumnWidths(table) {
            const tableId = table.id || 'default-table';
            const savedWidths = localStorage.getItem(`table-widths-${tableId}`);
            
            if (savedWidths) {
                const widths = JSON.parse(savedWidths);
                const headers = table.querySelectorAll('th');
                
                headers.forEach((header, index) => {
                    if (widths[index]) {
                        header.style.width = widths[index];
                    }
                });
            }
        }
        
        // Initialize resizable tables when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initResizableTables();
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
