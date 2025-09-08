<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get all links ordered by creation date
$stmt = $conn->prepare("
    SELECT l.*, 
           COUNT(t.id) as click_count,
           COUNT(DISTINCT t.ip_address) as unique_visitors
    FROM links l 
    LEFT JOIN targets t ON l.id = t.link_id 
    GROUP BY l.id 
    ORDER BY l.created_at DESC
");
$stmt->execute();
$links = $stmt->fetchAll();

// Get statistics
$totalLinks = count($links);
$totalClicks = array_sum(array_column($links, 'click_count'));
$activeLinks = count(array_filter($links, function($link) {
    return !$link['expiry_date'] || strtotime($link['expiry_date']) > time();
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Links - IP Logger</title>
    
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
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="links.php">
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
                    <h1 class="h2">My Links</h1>
                    <a href="create_link.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Link
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $totalLinks; ?></h4>
                                        <p class="card-text">Total Links</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-link fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $totalClicks; ?></h4>
                                        <p class="card-text">Total Clicks</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-mouse-pointer fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $activeLinks; ?></h4>
                                        <p class="card-text">Active Links</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $totalLinks - $activeLinks; ?></h4>
                                        <p class="card-text">Expired Links</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Links Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> All Links</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($links)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-link fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No links created yet</h5>
                                <p class="text-muted">Create your first link to start tracking visitors</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create First Link
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover resizable-table" id="links-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 200px;" draggable="true" data-column="0">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Short URL</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 250px;" draggable="true" data-column="1">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Original URL</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 80px;" draggable="true" data-column="2">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Clicks</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 120px;" draggable="true" data-column="3">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Unique Visitors</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 120px;" draggable="true" data-column="4">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Created</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 120px;" draggable="true" data-column="5">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Expires</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 100px;" draggable="true" data-column="6">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Status</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                            <th style="width: 120px;" draggable="true" data-column="7">
                                                <div class="column-header">
                                                    <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                    <span class="column-title">Actions</span>
                                                    <i class="fas fa-grip-vertical resize-icon"></i>
                                                </div>
                                                <div class="resize-handle"></div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($links as $link): ?>
                                            <?php 
                                            $isExpired = $link['expiry_date'] && strtotime($link['expiry_date']) < time();
                                            $shortUrl = BASE_URL . $link['short_code'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <code class="me-2"><?php echo $link['short_code']; ?></code>
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                onclick="copyToClipboard('<?php echo $shortUrl; ?>')"
                                                                title="Copy URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="url-cell" data-full-url="<?php echo htmlspecialchars($link['original_url']); ?>">
                                                        <?php echo htmlspecialchars($link['original_url']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $link['click_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $link['unique_visitors']; ?></span>
                                                </td>
                                                <td><?php echo formatDate($link['created_at']); ?></td>
                                                <td>
                                                    <?php if ($link['expiry_date']): ?>
                                                        <?php echo formatDate($link['expiry_date']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($isExpired): ?>
                                                        <span class="badge bg-danger">Expired</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo $shortUrl; ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           title="Test Link">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                        <a href="view_targets.php?link_id=<?php echo $link['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info"
                                                           title="View Targets">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-secondary"
                                                                onclick="showLinkDetails('<?php echo $shortUrl; ?>', '<?php echo htmlspecialchars($link['original_url']); ?>')"
                                                                title="Link Details">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                    </div>
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

    <!-- Link Details Modal -->
    <div class="modal fade" id="linkDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Link Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Short URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalShortUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(document.getElementById('modalShortUrl').value)" title="Copy URL">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Original URL</label>
                        <input type="text" class="form-control" id="modalOriginalUrl" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
            cursor: move;
            position: relative;
        }
        
        .resizable-table th:hover {
            background-color: #e9ecef;
        }
        
        .resizable-table th.dragging {
            opacity: 0.5;
            background-color: #007bff;
            color: white;
        }
        
        .resizable-table th.drag-over {
            border-left: 3px solid #007bff;
        }
        
        .resizable-table th.drag-over::before {
            content: '';
            position: absolute;
            left: -3px;
            top: 0;
            bottom: 0;
            width: 3px;
            background-color: #007bff;
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
        
        .resizable-table th .drag-handle {
            opacity: 0;
            transition: opacity 0.2s ease;
            font-size: 12px;
            color: #6c757d;
            cursor: grab;
        }
        
        .resizable-table th:hover .drag-handle {
            opacity: 1;
        }
        
        .resizable-table th .drag-handle:active {
            cursor: grabbing;
        }
        
        .resizable-table td {
            cursor: help;
        }
        
        .resizable-table td:hover {
            background-color: #f8f9fa;
        }
        
        /* URL cell styling */
        .url-cell {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: help;
            color: #007bff;
            transition: color 0.2s ease;
        }
        
        .url-cell:hover {
            color: #0056b3;
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

        function showLinkDetails(shortUrl, originalUrl) {
            document.getElementById('modalShortUrl').value = shortUrl;
            document.getElementById('modalOriginalUrl').value = originalUrl;
            new bootstrap.Modal(document.getElementById('linkDetailsModal')).show();
        }
        
        // Resizable Table Columns Functionality
        function initResizableTables() {
            console.log('initResizableTables called');
            const tables = document.querySelectorAll('.resizable-table');
            console.log('Found tables:', tables.length);
            
            tables.forEach((table, tableIndex) => {
                console.log(`Processing table ${tableIndex}:`, table);
                const headers = table.querySelectorAll('th');
                console.log(`Found ${headers.length} headers in table ${tableIndex}`);
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
                    console.log(`Header ${index}:`, header, 'Resize handle:', resizeHandle);
                    
                    if (resizeHandle) {
                        // Mouse down on resize handle
                        resizeHandle.addEventListener('mousedown', (e) => {
                            console.log('Resize started');
                            isResizing = true;
                            currentHeader = header;
                            startX = e.clientX;
                            startWidth = header.offsetWidth;
                            
                            header.classList.add('active');
                            document.body.style.cursor = 'col-resize';
                            document.body.style.userSelect = 'none';
                            
                            e.preventDefault();
                        });
                    }
                    
                    // Mouse move during resize
                    document.addEventListener('mousemove', (e) => {
                        if (!isResizing || !currentHeader) return;
                        
                        const newWidth = startWidth + (e.clientX - startX);
                        const minWidth = 50; // Minimum column width
                        const maxWidth = 600; // Increased maximum column width
                        
                        if (newWidth >= minWidth && newWidth <= maxWidth) {
                            currentHeader.style.width = newWidth + 'px';
                            // Update all cells in this column
                            const cells = table.querySelectorAll(`td:nth-child(${index + 1})`);
                            cells.forEach(cell => {
                                cell.style.width = newWidth + 'px';
                            });
                        }
                    });
                    
                    // Mouse up to end resize
                    document.addEventListener('mouseup', () => {
                        if (isResizing && currentHeader) {
                            console.log('Resize ended');
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
                            // Check if this is a URL cell with data-full-url attribute
                            const urlCell = cell.querySelector('.url-cell');
                            let tooltipText = '';
                            let shouldShowTooltip = false;
                            
                            if (urlCell && urlCell.hasAttribute('data-full-url')) {
                                // This is a URL cell - always show the full URL
                                tooltipText = urlCell.getAttribute('data-full-url');
                                shouldShowTooltip = true;
                                console.log('URL cell detected, showing full URL:', tooltipText);
                            } else {
                                // Regular cell - check if text is truncated
                                const cellText = cell.textContent.trim();
                                const cellWidth = cell.offsetWidth;
                                const textWidth = getTextWidth(cellText, '14px Inter, sans-serif');
                                
                                console.log(`Cell text: "${cellText}", cellWidth: ${cellWidth}, textWidth: ${textWidth}`);
                                
                                if (textWidth > cellWidth || cellText.includes('http')) {
                                    tooltipText = cellText;
                                    shouldShowTooltip = true;
                                }
                            }
                            
                            if (shouldShowTooltip) {
                                tooltip.textContent = tooltipText;
                                tooltip.classList.add('show');
                                
                                // Position tooltip
                                const rect = cell.getBoundingClientRect();
                                const tooltipWidth = Math.min(tooltip.offsetWidth, 500);
                                let left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
                                
                                // Keep tooltip within viewport
                                if (left < 10) left = 10;
                                if (left + tooltipWidth > window.innerWidth - 10) {
                                    left = window.innerWidth - tooltipWidth - 10;
                                }
                                
                                tooltip.style.left = left + 'px';
                                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                                tooltip.style.maxWidth = '500px';
                                
                                console.log('Tooltip shown:', tooltipText);
                            }
                        });
                        
                        cell.addEventListener('mouseleave', () => {
                            tooltip.classList.remove('show');
                        });
                    });
                });
                
                // Drag and Drop functionality
                headers.forEach((header, index) => {
                    // Drag start
                    header.addEventListener('dragstart', (e) => {
                        console.log('Drag started on column:', index);
                        draggedColumn = header;
                        draggedIndex = index;
                        header.classList.add('dragging');
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/html', header.outerHTML);
                    });
                    
                    // Drag end
                    header.addEventListener('dragend', (e) => {
                        console.log('Drag ended');
                        header.classList.remove('dragging');
                        // Remove all drag-over classes
                        headers.forEach(h => h.classList.remove('drag-over'));
                        draggedColumn = null;
                        draggedIndex = -1;
                    });
                    
                    // Drag over
                    header.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        
                        if (draggedColumn && draggedColumn !== header) {
                            header.classList.add('drag-over');
                        }
                    });
                    
                    // Drag leave
                    header.addEventListener('dragleave', (e) => {
                        header.classList.remove('drag-over');
                    });
                    
                    // Drop
                    header.addEventListener('drop', (e) => {
                        e.preventDefault();
                        header.classList.remove('drag-over');
                        
                        if (draggedColumn && draggedColumn !== header) {
                            const targetIndex = index;
                            const sourceIndex = draggedIndex;
                            
                            console.log(`Moving column from ${sourceIndex} to ${targetIndex}`);
                            
                            // Reorder columns
                            reorderColumns(table, sourceIndex, targetIndex);
                            
                            // Save column order
                            saveColumnOrder(table);
                        }
                    });
                });
                
                // Load saved column widths and order
                loadColumnWidths(table);
                loadColumnOrder(table);
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
                        // Apply width to all cells in this column
                        const cells = table.querySelectorAll(`td:nth-child(${index + 1})`);
                        cells.forEach(cell => {
                            cell.style.width = widths[index];
                        });
                    }
                });
            }
        }
        
        // Reorder columns function
        function reorderColumns(table, sourceIndex, targetIndex) {
            const headers = table.querySelectorAll('th');
            const rows = table.querySelectorAll('tr');
            
            if (sourceIndex === targetIndex) return;
            
            // Get the source elements
            const sourceHeader = headers[sourceIndex];
            const sourceCells = [];
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                if (cells[sourceIndex]) {
                    sourceCells.push(cells[sourceIndex]);
                }
            });
            
            // Remove source elements
            sourceHeader.remove();
            sourceCells.forEach(cell => cell.remove());
            
            // Insert at target position
            if (targetIndex > sourceIndex) {
                // Moving right - insert after target
                const targetHeader = headers[targetIndex - 1];
                targetHeader.insertAdjacentElement('afterend', sourceHeader);
                
                rows.forEach((row, rowIndex) => {
                    const cells = row.querySelectorAll('th, td');
                    const targetCell = cells[targetIndex - 1];
                    if (targetCell && sourceCells[rowIndex]) {
                        targetCell.insertAdjacentElement('afterend', sourceCells[rowIndex]);
                    }
                });
            } else {
                // Moving left - insert before target
                const targetHeader = headers[targetIndex];
                targetHeader.insertAdjacentElement('beforebegin', sourceHeader);
                
                rows.forEach((row, rowIndex) => {
                    const cells = row.querySelectorAll('th, td');
                    const targetCell = cells[targetIndex];
                    if (targetCell && sourceCells[rowIndex]) {
                        targetCell.insertAdjacentElement('beforebegin', sourceCells[rowIndex]);
                    }
                });
            }
            
            // Update data-column attributes
            const newHeaders = table.querySelectorAll('th');
            newHeaders.forEach((header, index) => {
                header.setAttribute('data-column', index);
            });
        }
        
        // Save column order to localStorage
        function saveColumnOrder(table) {
            const tableId = table.id || 'default-table';
            const headers = table.querySelectorAll('th');
            const order = Array.from(headers).map(header => header.getAttribute('data-column'));
            localStorage.setItem(`table-order-${tableId}`, JSON.stringify(order));
        }
        
        // Load column order from localStorage
        function loadColumnOrder(table) {
            const tableId = table.id || 'default-table';
            const savedOrder = localStorage.getItem(`table-order-${tableId}`);
            
            if (savedOrder) {
                const order = JSON.parse(savedOrder);
                const headers = table.querySelectorAll('th');
                
                // Only reorder if the order is different
                const currentOrder = Array.from(headers).map(header => header.getAttribute('data-column'));
                if (JSON.stringify(currentOrder) !== JSON.stringify(order)) {
                    // Reorder columns based on saved order
                    const headerArray = Array.from(headers);
                    const reorderedHeaders = order.map(index => headerArray.find(h => h.getAttribute('data-column') === index.toString()));
                    
                    // Clear the table header
                    const thead = table.querySelector('thead tr');
                    thead.innerHTML = '';
                    
                    // Add reordered headers
                    reorderedHeaders.forEach(header => {
                        if (header) {
                            thead.appendChild(header);
                        }
                    });
                    
                    // Reorder data rows
                    const tbody = table.querySelector('tbody');
                    if (tbody) {
                        const rows = Array.from(tbody.querySelectorAll('tr'));
                        rows.forEach(row => {
                            const cells = Array.from(row.querySelectorAll('td'));
                            const reorderedCells = order.map(index => cells.find(c => c.parentNode.children[Array.from(c.parentNode.children).indexOf(c)] === cells[parseInt(index)]));
                            
                            // Clear and reorder cells
                            row.innerHTML = '';
                            reorderedCells.forEach(cell => {
                                if (cell) {
                                    row.appendChild(cell);
                                }
                            });
                        });
                    }
                }
            }
        }
        
        // Initialize resizable tables when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing resizable tables...');
            try {
                initResizableTables();
                console.log('Resizable tables initialized successfully');
            } catch (error) {
                console.error('Error initializing resizable tables:', error);
            }
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
    </script>
</body>
</html>
