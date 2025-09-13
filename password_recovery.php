<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sidebar_helper.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$message = '';
$message_type = '';

// Handle password recovery request
if (isset($_POST['action']) && $_POST['action'] === 'recover_password') {
    $short_code = sanitizeInput($_POST['short_code']);
    $recovery_code = sanitizeInput($_POST['recovery_code']);

    // Find the link
    $stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ? AND password_recovery_code = ?");
    $stmt->execute([$short_code, $recovery_code]);
    $link = $stmt->fetch();

    if ($link) {
        // Generate new password and recovery code
        $new_password = generateRandomString(8);
        $new_recovery_code = generateRandomString(12);

        // Update the link with new password and recovery code
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE links SET password = ?, password_recovery_code = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $new_recovery_code, $link['id']]);

        $message = "Password recovered successfully! New password: <strong>$new_password</strong><br>New recovery code: <strong>$new_recovery_code</strong>";
        $message_type = 'success';
    } else {
        $message = "Invalid short code or recovery code.";
        $message_type = 'error';
    }
}

// Handle generate recovery code request
if (isset($_POST['action']) && $_POST['action'] === 'generate_recovery') {
    $short_code = sanitizeInput($_POST['short_code']);
    $password = $_POST['password'];

    // Find the link and verify password
    $stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ?");
    $stmt->execute([$short_code]);
    $link = $stmt->fetch();

    if ($link && password_verify($password, $link['password'])) {
        // Generate new recovery code
        $new_recovery_code = generateRandomString(12);

        // Update the link with new recovery code
        $stmt = $conn->prepare("UPDATE links SET password_recovery_code = ? WHERE id = ?");
        $stmt->execute([$new_recovery_code, $link['id']]);

        $message = "Recovery code generated successfully! Recovery code: <strong>$new_recovery_code</strong>";
        $message_type = 'success';
    } else {
        $message = "Invalid short code or password.";
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - IP Logger</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Pearlight Fonts -->
    <link rel="stylesheet" href="assets/css/pearlight-fonts.css">
    <!-- Pearlight Design System -->
    <link rel="stylesheet" href="assets/css/pearlight.css">
    <link rel="stylesheet" href="assets/css/pearlight-fonts.css">

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
                background: var(--pearlight-bg-primary);
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 1030;
                border-bottom: 1px solid var(--pearlight-gray-5);
            }

            .mobile-header .navbar-brand {
                color: var(--pearlight-text-primary);
                font-weight: 600;
            }

            .mobile-header .btn {
                color: var(--pearlight-text-primary);
                border-color: var(--pearlight-gray-4);
            }

            .mobile-header .btn:hover {
                background-color: var(--pearlight-gray-6);
                border-color: var(--pearlight-gray-3);
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

        .recovery-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .recovery-section h3 {
            color: #007bff;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .code-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem;
            font-family: monospace;
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>

<body class="bg-light">
    <?php echo generateMobileHeader(); ?>\n <?php echo generateSidebarOverlay(); ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->\n <?php echo generateSidebar(); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="pearlight-title-1"><i class="fas fa-key"></i> Password Recovery</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="pearlight-btn pearlight-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">

                        <!-- Alert Messages -->
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Generate Recovery Code -->
                        <div class="recovery-section">
                            <h3><i class="fas fa-plus-circle"></i> Generate Recovery Code</h3>
                            <p class="text-muted">Generate a recovery code for your link. You'll need this to recover your password later.</p>

                            <form method="POST">
                                <input type="hidden" name="action" value="generate_recovery">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="short_code_gen" class="pearlight-body-emphasized">Short Code</label>
                                            <input type="text" class="pearlight-input" id="short_code_gen" name="short_code" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password_gen" class="pearlight-body-emphasized">Current Password</label>
                                            <input type="password" class="pearlight-input" id="password_gen" name="password" required>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="pearlight-btn pearlight-btn-primary">
                                    <i class="fas fa-key"></i> Generate Recovery Code
                                </button>
                            </form>
                        </div>

                        <!-- Recover Password -->
                        <div class="recovery-section">
                            <h3><i class="fas fa-unlock"></i> Recover Password</h3>
                            <p class="text-muted">Use your recovery code to generate a new password for your link.</p>

                            <form method="POST">
                                <input type="hidden" name="action" value="recover_password">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="short_code_rec" class="pearlight-body-emphasized">Short Code</label>
                                            <input type="text" class="pearlight-input" id="short_code_rec" name="short_code" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="recovery_code" class="pearlight-body-emphasized">Recovery Code</label>
                                            <input type="text" class="pearlight-input" id="recovery_code" name="recovery_code" required>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="pearlight-btn pearlight-btn-warning">
                                    <i class="fas fa-unlock"></i> Recover Password
                                </button>
                            </form>
                        </div>

                        <!-- Security Information -->
                        <div class="recovery-section">
                            <h3><i class="fas fa-shield-alt"></i> Security Information</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-info-circle text-info"></i> How It Works</h5>
                                    <ul>
                                        <li>Recovery codes are randomly generated</li>
                                        <li>Each recovery code can only be used once</li>
                                        <li>New recovery codes are generated after each use</li>
                                        <li>Passwords are never stored in plain text</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-exclamation-triangle text-warning"></i> Security Tips</h5>
                                    <ul>
                                        <li>Store recovery codes securely</li>
                                        <li>Don't share recovery codes with others</li>
                                        <li>Generate new recovery codes regularly</li>
                                        <li>Use strong, unique passwords</li>
                                    </ul>
                                </div>
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