<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Cookies - IP Logger</title>
    
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
        .cookies-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .cookies-section {
            margin-bottom: 2rem;
        }
        
        .cookies-section h3 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .cookies-section h4 {
            color: #555;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .cookies-section p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .cookies-section ul {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .cookies-section li {
            margin-bottom: 0.5rem;
        }
        
        .cookie-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 1rem 0;
        }
        
        .cookie-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
            font-weight: 600;
        }
        
        .cookie-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .cookie-table tr:last-child td {
            border-bottom: none;
        }
        
        .cookie-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .cookie-type.essential {
            background: #d4edda;
            color: #155724;
        }
        
        .cookie-type.functional {
            background: #cce5ff;
            color: #004085;
        }
        
        .cookie-type.analytics {
            background: #fff3cd;
            color: #856404;
        }
        
        .cookie-type.marketing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 4px 4px 0;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 4px 4px 0;
        }
        
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 4px 4px 0;
        }
        
        .last-updated {
            background: #e9ecef;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 2rem;
        }
        
        .browser-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .browser-info h5 {
            color: #495057;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Mobile Header -->
    <div class="mobile-header d-flex justify-content-between align-items-center">
        <a href="index.php" class="apple-nav-brand">
            <i class="fas fa-shield-alt"></i> IP Logger
        </a>
        <button class="apple-btn apple-btn-secondary" type="button" id="sidebarToggle">
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
                            <a class="nav-link active" href="cookies.php">
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
                <div class="cookies-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="apple-title-1"><i class="fas fa-cookie-bite"></i> About Cookies</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="index.php" class="apple-btn apple-btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <div class="last-updated">
                        <i class="fas fa-calendar-alt"></i> Last updated: <?php echo date('F j, Y'); ?>
                    </div>

                    <!-- Introduction -->
                    <div class="cookies-section">
                        <h3>1. What Are Cookies?</h3>
                        <p>Cookies are small text files that are stored on your device (computer, tablet, or mobile phone) when you visit a website. They help websites remember information about your visit, such as your preferred language and other settings that can make your next visit easier and the site more useful to you.</p>
                        
                        <div class="highlight-box">
                            <strong>Important:</strong> IP Logger uses cookies to enhance your experience and provide essential functionality. We are committed to transparency about our cookie usage and respect your privacy choices.
                        </div>
                    </div>

                    <!-- How We Use Cookies -->
                    <div class="cookies-section">
                        <h3>2. How We Use Cookies</h3>
                        <p>IP Logger uses cookies for the following purposes:</p>
                        <ul>
                            <li><strong>Essential Functionality:</strong> To provide core features like session management and security</li>
                            <li><strong>User Experience:</strong> To remember your preferences and settings</li>
                            <li><strong>Analytics:</strong> To understand how our service is used and improve performance</li>
                            <li><strong>Security:</strong> To protect against fraud and ensure secure access</li>
                        </ul>
                    </div>

                    <!-- Types of Cookies -->
                    <div class="cookies-section">
                        <h3>3. Types of Cookies We Use</h3>
                        
                        <div class="cookie-table">
                            <table class="table table-borderless mb-0">
                                <thead>
                                    <tr>
                                        <th>Cookie Type</th>
                                        <th>Purpose</th>
                                        <th>Duration</th>
                                        <th>Essential</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <span class="cookie-type essential">PHPSESSID</span>
                                        </td>
                                        <td>Session management and security</td>
                                        <td>Session (until browser closes)</td>
                                        <td><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="cookie-type functional">user_preferences</span>
                                        </td>
                                        <td>Remember user interface preferences</td>
                                        <td>1 year</td>
                                        <td><i class="fas fa-times text-muted"></i></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="cookie-type analytics">analytics_consent</span>
                                        </td>
                                        <td>Remember analytics consent choice</td>
                                        <td>1 year</td>
                                        <td><i class="fas fa-times text-muted"></i></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="cookie-type functional">csrf_token</span>
                                        </td>
                                        <td>Security protection against CSRF attacks</td>
                                        <td>Session (until browser closes)</td>
                                        <td><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Cookie Categories -->
                    <div class="cookies-section">
                        <h3>4. Cookie Categories</h3>
                        
                        <h4>4.1 Essential Cookies</h4>
                        <p>These cookies are necessary for the website to function properly. They cannot be disabled and are typically set in response to actions you take, such as logging in or filling out forms.</p>
                        <ul>
                            <li><strong>PHPSESSID:</strong> Maintains your session while you browse our site</li>
                            <li><strong>CSRF Token:</strong> Protects against cross-site request forgery attacks</li>
                        </ul>
                        
                        <h4>4.2 Functional Cookies</h4>
                        <p>These cookies enable enhanced functionality and personalization. They may be set by us or third-party providers whose services we use.</p>
                        <ul>
                            <li><strong>User Preferences:</strong> Remember your interface settings and choices</li>
                            <li><strong>Language Settings:</strong> Store your preferred language</li>
                        </ul>
                        
                        <h4>4.3 Analytics Cookies</h4>
                        <p>These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.</p>
                        <ul>
                            <li><strong>Usage Analytics:</strong> Track page views and user behavior</li>
                            <li><strong>Performance Monitoring:</strong> Monitor site performance and errors</li>
                        </ul>
                        
                        <div class="warning-box">
                            <strong>Note:</strong> We do not use marketing or advertising cookies that track you across websites for advertising purposes.
                        </div>
                    </div>

                    <!-- Third-Party Cookies -->
                    <div class="cookies-section">
                        <h3>5. Third-Party Cookies</h3>
                        <p>Our service may use third-party services that set their own cookies:</p>
                        
                        <h4>5.1 Google Fonts</h4>
                        <p>We use Google Fonts to display custom fonts. Google may set cookies to track font usage.</p>
                        
                        <h4>5.2 FontAwesome</h4>
                        <p>We use FontAwesome for icons. FontAwesome may set cookies for CDN performance.</p>
                        
                        <h4>5.3 Bootstrap CDN</h4>
                        <p>We use Bootstrap CDN for styling. Bootstrap may set cookies for CDN optimization.</p>
                        
                        <div class="info-box">
                            <strong>Important:</strong> We have minimized our use of third-party services to reduce tracking and improve privacy.
                        </div>
                    </div>

                    <!-- Browser Anti-Tracking -->
                    <div class="cookies-section">
                        <h3>6. Browser Anti-Tracking Features</h3>
                        <p>Modern browsers have implemented anti-tracking features that may affect cookie functionality:</p>
                        
                        <div class="browser-info">
                            <h5><i class="fab fa-firefox"></i> Firefox</h5>
                            <p>Firefox blocks third-party cookies by default and includes Enhanced Tracking Protection that may block tracking cookies.</p>
                        </div>
                        
                        <div class="browser-info">
                            <h5><i class="fab fa-chrome"></i> Chrome</h5>
                            <p>Chrome has phased out third-party cookies and implements Privacy Sandbox features to replace traditional tracking.</p>
                        </div>
                        
                        <div class="browser-info">
                            <h5><i class="fab fa-safari"></i> Safari</h5>
                            <p>Safari includes Intelligent Tracking Prevention that blocks cross-site tracking and limits cookie access.</p>
                        </div>
                        
                        <div class="browser-info">
                            <h5><i class="fab fa-edge"></i> Edge</h5>
                            <p>Edge includes tracking prevention features and blocks known tracking domains.</p>
                        </div>
                    </div>

                    <!-- Managing Cookies -->
                    <div class="cookies-section">
                        <h3>7. Managing Your Cookie Preferences</h3>
                        
                        <h4>7.1 Browser Settings</h4>
                        <p>You can control cookies through your browser settings:</p>
                        <ul>
                            <li><strong>Chrome:</strong> Settings → Privacy and security → Cookies and other site data</li>
                            <li><strong>Firefox:</strong> Options → Privacy & Security → Cookies and Site Data</li>
                            <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
                            <li><strong>Edge:</strong> Settings → Cookies and site permissions → Cookies and site data</li>
                        </ul>
                        
                        <h4>7.2 Our Cookie Consent</h4>
                        <p>When you first visit our site, you'll see a cookie consent banner that allows you to:</p>
                        <ul>
                            <li>Accept all cookies</li>
                            <li>Accept only essential cookies</li>
                            <li>Customize your cookie preferences</li>
                        </ul>
                        
                        <h4>7.3 Updating Preferences</h4>
                        <p>You can change your cookie preferences at any time by:</p>
                        <ul>
                            <li>Clicking the "Cookie Settings" link in our footer</li>
                            <li>Clearing your browser cookies and revisiting our site</li>
                            <li>Using your browser's developer tools to manage cookies</li>
                        </ul>
                    </div>

                    <!-- Impact of Disabling Cookies -->
                    <div class="cookies-section">
                        <h3>8. Impact of Disabling Cookies</h3>
                        <p>If you choose to disable cookies, please be aware that:</p>
                        <ul>
                            <li><strong>Essential Features:</strong> Some core functionality may not work properly</li>
                            <li><strong>Session Management:</strong> You may need to log in more frequently</li>
                            <li><strong>Preferences:</strong> Your settings and preferences will not be remembered</li>
                            <li><strong>Security:</strong> Some security features may be limited</li>
                        </ul>
                        
                        <div class="warning-box">
                            <strong>Recommendation:</strong> We recommend allowing essential cookies for the best user experience while maintaining your privacy preferences for optional cookies.
                        </div>
                    </div>

                    <!-- Data Protection -->
                    <div class="cookies-section">
                        <h3>9. Data Protection and Privacy</h3>
                        <p>Our cookie usage complies with data protection regulations:</p>
                        <ul>
                            <li><strong>GDPR Compliance:</strong> We obtain consent before setting non-essential cookies</li>
                            <li><strong>Data Minimization:</strong> We only collect data necessary for service functionality</li>
                            <li><strong>Transparency:</strong> We clearly explain what data we collect and why</li>
                            <li><strong>User Control:</strong> You can manage and withdraw consent at any time</li>
                        </ul>
                        
                        <p>For more information about how we protect your data, please see our <a href="privacy.php">Privacy Policy</a>.</p>
                    </div>

                    <!-- Updates to Cookie Policy -->
                    <div class="cookies-section">
                        <h3>10. Updates to This Cookie Policy</h3>
                        <p>We may update this cookie policy from time to time to reflect changes in our practices or for other operational, legal, or regulatory reasons. We will notify you of any material changes by:</p>
                        <ul>
                            <li>Posting the updated policy on this page</li>
                            <li>Updating the "Last updated" date</li>
                            <li>Providing notice through our service when appropriate</li>
                        </ul>
                    </div>

                    <!-- Contact Information -->
                    <div class="cookies-section">
                        <h3>11. Contact Us</h3>
                        <p>If you have any questions about our use of cookies, please contact us:</p>
                        <ul>
                            <li>Email: [Your contact email]</li>
                            <li>Website: keizai-tech.com/projects/ip-logger</li>
                        </ul>
                    </div>

                    <!-- Cookie Consent Banner -->
                    <div class="cookies-section">
                        <div class="highlight-box">
                            <strong>Cookie Consent:</strong> By using our service, you consent to our use of cookies as described in this policy. You can manage your preferences at any time through your browser settings or our cookie consent tools.
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
