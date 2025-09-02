<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                updateSetting('admin_email', sanitizeInput($_POST['admin_email']));
                updateSetting('email_notifications_enabled', isset($_POST['email_enabled']) ? 'true' : 'false');
                updateSetting('notify_on_link_click', isset($_POST['notify_click']) ? 'true' : 'false');
                updateSetting('notify_on_new_link', isset($_POST['notify_new']) ? 'true' : 'false');
                redirectWithMessage('email_settings.php', 'Email settings updated successfully!', 'success');
                break;
                
            case 'test_email':
                $testResult = testEmailFunctionality();
                if ($testResult['success']) {
                    redirectWithMessage('email_settings.php', 'Test email sent successfully!', 'success');
                } else {
                    redirectWithMessage('email_settings.php', 'Failed to send test email. Check your SMTP configuration.', 'error');
                }
                break;
        }
    }
}

// Get current settings
$adminEmail = getSetting('admin_email', SMTP_FROM_EMAIL);
$emailEnabled = getSetting('email_notifications_enabled', 'true') === 'true';
$notifyClick = getSetting('notify_on_link_click', 'true') === 'true';
$notifyNew = getSetting('notify_on_new_link', 'true') === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings - IP Logger</title>
    
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
                            <a class="nav-link" href="links.php">
                                <i class="fas fa-link"></i> My Links
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="targets.php">
                                <i class="fas fa-map-marker-alt"></i> Targets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="email_settings.php">
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
                    <h1 class="h2">Email Settings</h1>
                </div>

                <!-- Alert Messages -->
                <?php echo displayMessage(); ?>

                <div class="row">
                    <div class="col-md-8">
                        <!-- Email Configuration -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cog"></i> Email Configuration</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="mb-3">
                                        <label for="admin_email" class="form-label">Admin Email Address</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                               value="<?php echo htmlspecialchars($adminEmail); ?>" required>
                                        <div class="form-text">This email will receive all notifications</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" 
                                                   <?php echo $emailEnabled ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_enabled">
                                                Enable Email Notifications
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notification Types</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notify_click" name="notify_click" 
                                                   <?php echo $notifyClick ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notify_click">
                                                Notify when links are clicked
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notify_new" name="notify_new" 
                                                   <?php echo $notifyNew ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notify_new">
                                                Notify when new links are created
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Test Email -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Test Email Configuration</h5>
                            </div>
                            <div class="card-body">
                                <p>Send a test email to verify your SMTP configuration is working correctly.</p>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="test_email">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-envelope"></i> Send Test Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- SMTP Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> SMTP Configuration</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>SMTP Host:</strong> <?php echo SMTP_HOST; ?></p>
                                <p><strong>SMTP Port:</strong> <?php echo SMTP_PORT; ?></p>
                                <p><strong>Security:</strong> <?php echo SMTP_SECURE; ?></p>
                                <p><strong>Username:</strong> <?php echo SMTP_USERNAME; ?></p>
                                <p><strong>From Email:</strong> <?php echo SMTP_FROM_EMAIL; ?></p>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <strong>Note:</strong> To change SMTP settings, edit the <code>config/config.php</code> file.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Email Templates -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Email Templates</h5>
                            </div>
                            <div class="card-body">
                                <p>The system uses HTML email templates for better presentation.</p>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Link click notifications</li>
                                    <li><i class="fas fa-check text-success"></i> New link creation</li>
                                    <li><i class="fas fa-check text-success"></i> Test emails</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
