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
                $email = sanitizeInput($_POST['notification_email']);
                
                // Validate email if provided
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    redirectWithMessage('email_settings.php', 'Please enter a valid email address.', 'error');
                }
                
                updateSetting('notification_email', $email);
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
                
            case 'delete_email':
                updateSetting('notification_email', 'NO_NOTIFICATION_EMAIL');
                redirectWithMessage('email_settings.php', 'Your email has been removed from our database. You will no longer receive notifications.', 'success');
                break;
        }
    }
}

// Get current settings
$notificationEmail = getSetting('notification_email', '');
$emailEnabled = getSetting('email_notifications_enabled', 'true') === 'true';
$notifyClick = getSetting('notify_on_link_click', 'true') === 'true';
$notifyNew = getSetting('notify_on_new_link', 'true') === 'true';

// Check if email is set to no notification
$hasNotificationEmail = $notificationEmail && $notificationEmail !== 'NO_NOTIFICATION_EMAIL';
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
                                <h5 class="mb-0"><i class="fas fa-cog"></i> Notification Email Settings</h5>
                            </div>
                            <div class="card-body">
                                <!-- Privacy Notice -->
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-shield-alt"></i> Privacy Notice</h6>
                                    <p class="mb-2">Your email address will <strong>only</strong> be used to send you notifications about your IP Logger links. We will never:</p>
                                    <ul class="mb-2">
                                        <li>Share your email with third parties</li>
                                        <li>Send you marketing emails</li>
                                        <li>Use your email for any other purpose</li>
                                    </ul>
                                    <p class="mb-0">You can remove your email at any time using the "Remove Email" button below. <a href="privacy.php" class="alert-link">Read our full privacy policy</a>.</p>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="mb-3">
                                        <label for="notification_email" class="form-label">Notification Email Address</label>
                                        <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                               value="<?php echo $hasNotificationEmail ? htmlspecialchars($notificationEmail) : ''; ?>" 
                                               placeholder="Enter your email to receive notifications">
                                        <div class="form-text">
                                            Leave empty if you don't want to receive email notifications
                                        </div>
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
                                
                                <!-- Remove Email Button -->
                                <?php if ($hasNotificationEmail): ?>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Current Email: <code><?php echo htmlspecialchars($notificationEmail); ?></code></h6>
                                        <small class="text-muted">Click the button to remove your email from our database</small>
                                    </div>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove your email? You will no longer receive notifications.');">
                                        <input type="hidden" name="action" value="delete_email">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i> Remove Email
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Test Email -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Test Email Configuration</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($hasNotificationEmail): ?>
                                    <p>Send a test email to verify your notification email is working correctly.</p>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="test_email">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-envelope"></i> Send Test Email
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>No email configured.</strong> Please add an email address above to test email notifications.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- System Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Email System:</strong> <?php echo $hasNotificationEmail ? 'Configured' : 'Not Configured'; ?></p>
                                <p><strong>Notifications:</strong> <?php echo $emailEnabled ? 'Enabled' : 'Disabled'; ?></p>
                                <p><strong>Link Clicks:</strong> <?php echo $notifyClick ? 'Will Notify' : 'No Notifications'; ?></p>
                                <p><strong>New Links:</strong> <?php echo $notifyNew ? 'Will Notify' : 'No Notifications'; ?></p>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <strong>Note:</strong> Email notifications are sent from <code><?php echo SMTP_FROM_EMAIL; ?></code>
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
