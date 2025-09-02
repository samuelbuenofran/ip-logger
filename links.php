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
                            <a class="nav-link active" href="links.php">
                                <i class="fas fa-link"></i> My Links
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_targets.php">
                                <i class="fas fa-map-marker-alt"></i> Targets
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
                    <h1 class="h2">My Links</h1>
                    <a href="index.php" class="btn btn-primary">
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
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Short URL</th>
                                            <th>Original URL</th>
                                            <th>Clicks</th>
                                            <th>Unique Visitors</th>
                                            <th>Created</th>
                                            <th>Expires</th>
                                            <th>Status</th>
                                            <th>Actions</th>
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
                                                    <div class="text-truncate" style="max-width: 200px;" 
                                                         title="<?php echo htmlspecialchars($link['original_url']); ?>">
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
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(document.getElementById('modalShortUrl').value)">
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
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 1000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        function showLinkDetails(shortUrl, originalUrl) {
            document.getElementById('modalShortUrl').value = shortUrl;
            document.getElementById('modalOriginalUrl').value = originalUrl;
            new bootstrap.Modal(document.getElementById('linkDetailsModal')).show();
        }
    </script>
</body>
</html>
