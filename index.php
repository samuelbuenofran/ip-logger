<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sidebar_helper.php';

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
    <!-- Apple Fonts -->
    <link rel="stylesheet" href="assets/css/apple-fonts.css">
    <!-- Apple Design System -->
    <link rel="stylesheet" href="assets/css/apple-design-system.css">
    
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
                background: var(--apple-bg-primary);
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 1030;
                border-bottom: 1px solid var(--apple-gray-5);
            }
            
            .mobile-header .navbar-brand {
                color: var(--apple-text-primary);
                font-weight: 600;
            }
            
            .mobile-header .btn {
                color: var(--apple-text-primary);
                border-color: var(--apple-gray-4);
            }
            
            .mobile-header .btn:hover {
                background-color: var(--apple-gray-6);
                border-color: var(--apple-gray-3);
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
    <?php echo generateMobileHeader(); ?>
    <?php echo generateSidebarOverlay(); ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo generateSidebar(); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="apple-title-1">Dashboard</h1>
                                         <div class="btn-toolbar mb-2 mb-md-0">
                         <a href="create_link.php" class="apple-btn apple-btn-primary me-2">
                             <i class="fas fa-plus"></i> Criar Novo Link
                         </a>
                     </div>
                </div>

                <!-- Alert Messages -->
                <?php echo displayMessage(); ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="apple-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="apple-headline">Total de Links</h5>
                                    <h2 class="apple-title-1"><?php echo count($links); ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-link fa-2x" style="color: var(--apple-blue);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="apple-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="apple-headline">Links Ativos</h5>
                                    <h2 class="apple-title-1"><?php echo count(array_filter($links, function($link) { return $link['expiry_date'] === NULL || strtotime($link['expiry_date']) > time(); })); ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x" style="color: var(--apple-green);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="apple-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="apple-headline">Total de Cliques</h5>
                                    <h2 class="apple-title-1"><?php 
                                        $stmt = $conn->query("SELECT COUNT(*) as total FROM targets");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-mouse-pointer fa-2x" style="color: var(--apple-teal);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="apple-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="apple-headline">Visitantes Únicos</h5>
                                    <h2 class="apple-title-1"><?php 
                                        $stmt = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM targets");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['unique_visitors'];
                                    ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x" style="color: var(--apple-orange);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Links Table -->
                <div class="apple-card">
                    <div class="apple-card-header">
                        <h5 class="apple-card-title"><i class="fas fa-link"></i> Links Recentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="apple-table resizable-table" id="dashboard-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">
                                            <div class="column-header">
                                                <span class="column-title">ID</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 120px;" draggable="true" data-column="0">
                                            <div class="column-header">
                                                <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                <span class="column-title">Código</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 250px;" draggable="true" data-column="1">
                                            <div class="column-header">
                                                <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                <span class="column-title">URL Original</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 120px;" draggable="true" data-column="2">
                                            <div class="column-header">
                                                <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                <span class="column-title">Criado</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 120px;" draggable="true" data-column="3">
                                            <div class="column-header">
                                                <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                <span class="column-title">Expira</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 80px;" draggable="true" data-column="4">
                                            <div class="column-header">
                                                <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                <span class="column-title">Cliques</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                        <th style="width: 80px;" draggable="true" data-column="5">
                                            <div class="column-header">
                                                <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                <span class="column-title">Ações</span>
                                                <i class="fas fa-grip-vertical resize-icon"></i>
                                            </div>
                                            <div class="resize-handle"></div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($links, 0, 10) as $link): ?>
                                    <tr>
                                        <td><?php echo $link['id']; ?></td>
                                        <td>
                                            <code><?php echo $link['short_code']; ?></code>
                                            <button class="apple-btn apple-btn-secondary ms-2" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="copyToClipboard('<?php echo BASE_URL . $link['short_code']; ?>')" title="Copiar URL">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <div class="url-cell" data-full-url="<?php echo htmlspecialchars($link['original_url']); ?>">
                                                <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($link['original_url']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($link['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($link['expiry_date'] === NULL) {
                                                echo '<span class="badge bg-success">Nunca</span>';
                                            } else {
                                                $expiry = strtotime($link['expiry_date']);
                                                if ($expiry > time()) {
                                                    echo '<span class="badge bg-warning">' . date('M j, Y', $expiry) . '</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">Expirado</span>';
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
                                        <td class="text-center">
                                            <button class="expand-btn" 
                                                    onclick="toggleRowActions(this)" 
                                                    title="Expandir ações">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="6,9 12,15 18,9"></polyline>
                                                </svg>
                                            </button>
                                            <div class="row-actions" style="display: none;">
                                                <a href="view_targets.php?link_id=<?php echo $link['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
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
                        <button type="button" class="apple-btn apple-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="apple-btn apple-btn-primary">Create Link</button>
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
    
    <!-- Mobile Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing mobile navigation...');
            
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (!sidebarToggle || !sidebar || !sidebarOverlay) {
                console.error('Mobile navigation elements not found');
                return;
            }
            
            console.log('Mobile navigation elements found');
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Sidebar toggle clicked');
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
            
            // Close sidebar when clicking overlay
            sidebarOverlay.addEventListener('click', function() {
                console.log('Overlay clicked, closing sidebar');
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Close sidebar when clicking on nav links (mobile only)
            const navLinks = document.querySelectorAll('.sidebar .apple-nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (window.innerWidth < 768) {
                        console.log('Nav link clicked on mobile, closing sidebar');
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                    }
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            });
            
            console.log('Mobile navigation initialized successfully');
        });
    </script>
    
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
                
                // Drag and drop variables
                let draggedColumn = null;
                let draggedIndex = -1;
                
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
                            const columnIndex = Array.from(headers).indexOf(currentHeader);
                            const cells = table.querySelectorAll(`td:nth-child(${columnIndex + 1})`);
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
                        
                        // Also apply to any nested elements that might affect width
                        const expandBtns = table.querySelectorAll(`td:nth-child(${index + 1}) .expand-btn`);
                        expandBtns.forEach(btn => {
                            btn.style.width = '32px';
                            btn.style.height = '32px';
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
        
        // Row expansion functionality
        function toggleRowActions(button) {
            const rowActions = button.nextElementSibling;
            const isExpanded = button.classList.contains('expanded');
            
            // Close all other expanded rows
            document.querySelectorAll('.expand-btn.expanded').forEach(btn => {
                if (btn !== button) {
                    btn.classList.remove('expanded');
                    btn.nextElementSibling.style.display = 'none';
                }
            });
            
            // Toggle current row
            if (isExpanded) {
                button.classList.remove('expanded');
                rowActions.style.display = 'none';
            } else {
                button.classList.add('expanded');
                rowActions.style.display = 'block';
            }
        }
        
        // Force column auto-adjustment
        function forceColumnAdjustment(table) {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                const currentWidth = header.style.width || header.offsetWidth + 'px';
                
                // Apply to all cells in this column
                const cells = table.querySelectorAll(`td:nth-child(${index + 1})`);
                cells.forEach(cell => {
                    cell.style.width = currentWidth;
                });
            });
        }
        
        // Initialize resizable tables when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing resizable tables...');
            try {
                initResizableTables();
                
                // Force column adjustment after initialization
                setTimeout(() => {
                    const tables = document.querySelectorAll('.resizable-table');
                    tables.forEach(table => {
                        forceColumnAdjustment(table);
                    });
                }, 100);
                
                console.log('Resizable tables initialized successfully');
            } catch (error) {
                console.error('Error initializing resizable tables:', error);
            }
        });
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
        
        /* Resizable Table Columns */
        .resizable-table {
            table-layout: fixed;
            width: 100%;
            min-width: 100%;
        }
        
        .resizable-table th,
        .resizable-table td {
            position: relative;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 8px 12px;
            vertical-align: middle;
        }
        
        .resizable-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            user-select: none;
            font-weight: 600;
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
            width: 6px;
            height: 100%;
            background-color: transparent;
            cursor: col-resize;
            transition: background-color 0.2s ease;
            z-index: 10;
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
            width: calc(100% - 6px);
            padding-right: 6px;
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
            margin-left: 4px;
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
            transition: background-color 0.2s ease;
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
        }
        
        .url-cell a {
            color: #007bff;
            text-decoration: none;
        }
        
        .url-cell a:hover {
            text-decoration: underline;
        }
        
        /* Special handling for URL columns */
        .resizable-table td a {
            color: #007bff;
            text-decoration: none;
        }
        
        .resizable-table td a:hover {
            text-decoration: underline;
        }
        
        /* Tooltip styles */
        .table-tooltip {
            position: absolute;
            background-color: #333;
            color: white;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            max-width: 400px;
            word-wrap: break-word;
            word-break: break-all;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .table-tooltip.show {
            opacity: 1;
        }
        
        .table-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -6px;
            border-width: 6px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        /* Row Expansion Styles */
        .expand-btn {
            transition: all 0.3s ease;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .expand-btn:hover {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .expand-btn.expanded {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .expand-btn.expanded i {
            transform: rotate(180deg);
        }
        
        .expand-btn i {
            transition: transform 0.3s ease;
            font-size: 12px;
        }
        
        .row-actions {
            margin-top: 0.5rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .row-actions.hidden {
            display: none !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .resizable-table th .resize-handle {
                width: 8px;
            }
            
            .resizable-table th .column-header {
                width: calc(100% - 8px);
                padding-right: 8px;
            }
            
            .table-tooltip {
                max-width: 300px;
                font-size: 12px;
            }
        }
    </style>
    
</body>
</html>
