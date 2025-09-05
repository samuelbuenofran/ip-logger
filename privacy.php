<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - IP Logger</title>
    
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
                            <a class="nav-link active" href="privacy.php">
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
                    <h1 class="h2">Privacy Policy</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3>Privacy Policy for IP Logger</h3>
                        <p class="text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
                        
                        <div class="privacy-notice">
                            <i class="fas fa-info-circle"></i>
                            <strong>Important:</strong> This tool is designed to respect user privacy and comply with applicable data protection laws.
                        </div>
                        
                        <h4>1. Information We Collect</h4>
                        <p>When you click on a shortened link created through IP Logger, we may collect the following information:</p>
                        <ul>
                            <li><strong>IP Address:</strong> Your internet protocol address</li>
                            <li><strong>Geolocation Data:</strong> Country, city, and approximate location</li>
                            <li><strong>Device Information:</strong> Browser type, device type (desktop/mobile)</li>
                            <li><strong>Referrer:</strong> The website that linked to our service</li>
                            <li><strong>Timestamp:</strong> When the link was clicked</li>
                        </ul>
                        
                        <h4>2. How We Use Information</h4>
                        <p>The information we collect is used solely for:</p>
                        <ul>
                            <li>Providing link tracking analytics to link creators</li>
                            <li>Improving our service functionality</li>
                            <li>Preventing abuse and ensuring security</li>
                        </ul>
                        
                        <h4>3. Data Protection</h4>
                        <p>We implement appropriate security measures to protect your information:</p>
                        <ul>
                            <li>All data is encrypted in transit and at rest</li>
                            <li>Access to tracking data is password-protected</li>
                            <li>We do not sell, rent, or share your personal information</li>
                            <li>Data is automatically deleted after the retention period</li>
                        </ul>
                        
                        <h4>4. Data Retention</h4>
                        <p>We retain your information for a limited period:</p>
                        <ul>
                            <li>Link tracking data: <?php echo getSetting('data_retention_days', '90'); ?> days</li>
                            <li>Expired links are automatically removed</li>
                            <li>You can request data deletion at any time</li>
                        </ul>
                        
                        <h4>5. Your Rights</h4>
                        <p>Under applicable privacy laws, you have the right to:</p>
                        <ul>
                            <li><strong>Access:</strong> Request a copy of your personal data</li>
                            <li><strong>Rectification:</strong> Correct inaccurate information</li>
                            <li><strong>Erasure:</strong> Request deletion of your data</li>
                            <li><strong>Portability:</strong> Receive your data in a structured format</li>
                            <li><strong>Objection:</strong> Object to processing of your data</li>
                        </ul>
                        
                        <h4>6. Third-Party Services</h4>
                        <p>We may use third-party services for:</p>
                        <ul>
                            <li><strong>Geolocation:</strong> IP-API for location data</li>
                            <li><strong>Maps:</strong> Google Maps for location visualization</li>
                        </ul>
                        <p>These services have their own privacy policies.</p>
                        
                        <h4>7. Cookies</h4>
                        <p>We use minimal cookies for:</p>
                        <ul>
                            <li>Session management</li>
                            <li>Security purposes</li>
                            <li>Service functionality</li>
                        </ul>
                        
                        <h4>8. Children's Privacy</h4>
                        <p>Our service is not intended for children under 13. We do not knowingly collect personal information from children under 13.</p>
                        
                        <h4>9. International Transfers</h4>
                        <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place.</p>
                        
                        <h4>10. Changes to This Policy</h4>
                        <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page.</p>
                        
                        <h4>11. Contact Information</h4>
                        <p>If you have any questions about this privacy policy or our data practices, please contact us:</p>
                        <ul>
                            <li>Email: privacy@yourdomain.com</li>
                            <li>Address: [Your Business Address]</li>
                        </ul>
                        
                        <h4>12. Legal Basis</h4>
                        <p>We process your personal data based on:</p>
                        <ul>
                            <li><strong>Legitimate Interest:</strong> Providing link tracking services</li>
                            <li><strong>Consent:</strong> When you click on a shortened link</li>
                            <li><strong>Legal Obligation:</strong> Complying with applicable laws</li>
                        </ul>
                        
                        <div class="alert alert-info mt-4">
                            <h5><i class="fas fa-shield-alt"></i> GDPR Compliance</h5>
                            <p>This service is designed to comply with the General Data Protection Regulation (GDPR) and other applicable privacy laws. We are committed to protecting your privacy and ensuring transparency in our data practices.</p>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h5><i class="fas fa-exclamation-triangle"></i> Disclaimer</h5>
                            <p>This tool is for legitimate tracking purposes only. Users are responsible for complying with applicable privacy laws and regulations in their jurisdiction. The developers are not responsible for misuse of this software.</p>
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
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>

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
