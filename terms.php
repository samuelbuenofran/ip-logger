<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sidebar_helper.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use - IP Logger</title>
    
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
    
    <style>
        .terms-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .terms-section {
            margin-bottom: 2rem;
        }
        
        .terms-section h3 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .terms-section h4 {
            color: #555;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .terms-section p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .terms-section ul {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .terms-section li {
            margin-bottom: 0.5rem;
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
        
        .last-updated {
            background: #e9ecef;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php echo generateMobileHeader(); ?>\n    <?php echo generateSidebarOverlay(); ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->\n            <?php echo generateSidebar(); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="terms-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="apple-title-1"><i class="fas fa-gavel"></i> Terms of Use</h1>
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
                    <div class="terms-section">
                        <h3>1. Introduction</h3>
                        <p>Welcome to IP Logger ("we," "our," or "us"). These Terms of Use ("Terms") govern your use of our URL shortening and IP tracking service located at <strong>keizai-tech.com/projects/ip-logger</strong> (the "Service").</p>
                        
                        <p>By accessing or using our Service, you agree to be bound by these Terms. If you disagree with any part of these terms, then you may not access the Service.</p>
                        
                        <div class="highlight-box">
                            <strong>Important:</strong> This service is designed for legitimate tracking purposes only. Users are responsible for ensuring their use complies with applicable laws and regulations.
                        </div>
                    </div>

                    <!-- Service Description -->
                    <div class="terms-section">
                        <h3>2. Service Description</h3>
                        <p>IP Logger provides a URL shortening service with IP address tracking capabilities. Our service allows users to:</p>
                        <ul>
                            <li>Create shortened URLs that redirect to original destinations</li>
                            <li>Track visitor information including IP addresses, geolocation data, and device information</li>
                            <li>View analytics and statistics about link usage</li>
                            <li>Customize links with custom domains and extensions</li>
                        </ul>
                        
                        <p>We reserve the right to modify, suspend, or discontinue the Service at any time without notice.</p>
                    </div>

                    <!-- User Accounts and Responsibilities -->
                    <div class="terms-section">
                        <h3>3. User Accounts and Responsibilities</h3>
                        
                        <h4>3.1 Account Creation</h4>
                        <p>To use certain features of our Service, you may need to create an account. You are responsible for:</p>
                        <ul>
                            <li>Providing accurate and complete information</li>
                            <li>Maintaining the security of your account credentials</li>
                            <li>All activities that occur under your account</li>
                            <li>Notifying us immediately of any unauthorized use</li>
                        </ul>
                        
                        <h4>3.2 Acceptable Use</h4>
                        <p>You agree to use the Service only for lawful purposes and in accordance with these Terms. You agree not to:</p>
                        <ul>
                            <li>Use the Service for any illegal or unauthorized purpose</li>
                            <li>Violate any applicable laws or regulations</li>
                            <li>Infringe upon the rights of others</li>
                            <li>Transmit harmful, offensive, or inappropriate content</li>
                            <li>Attempt to gain unauthorized access to our systems</li>
                            <li>Use the Service for spam, phishing, or other malicious activities</li>
                            <li>Track individuals without their knowledge or consent where required by law</li>
                        </ul>
                        
                        <div class="warning-box">
                            <strong>Warning:</strong> Unauthorized tracking of individuals may violate privacy laws. Users are responsible for obtaining necessary consent and complying with applicable regulations.
                        </div>
                    </div>

                    <!-- Privacy and Data Protection -->
                    <div class="terms-section">
                        <h3>4. Privacy and Data Protection</h3>
                        
                        <h4>4.1 Data Collection</h4>
                        <p>Our Service collects certain information from visitors to your shortened links, including:</p>
                        <ul>
                            <li>IP addresses and geolocation data</li>
                            <li>Device and browser information</li>
                            <li>Referrer information</li>
                            <li>Click timestamps</li>
                        </ul>
                        
                        <h4>4.2 Data Usage</h4>
                        <p>We use collected data to:</p>
                        <ul>
                            <li>Provide link tracking and analytics services</li>
                            <li>Improve our Service functionality</li>
                            <li>Ensure security and prevent abuse</li>
                            <li>Comply with legal obligations</li>
                        </ul>
                        
                        <h4>4.3 Data Retention</h4>
                        <p>We retain tracking data for a limited period as specified in our Privacy Policy. Users may request deletion of their data subject to our data retention policies.</p>
                        
                        <p>For detailed information about how we handle your data, please review our <a href="privacy.php">Privacy Policy</a>.</p>
                    </div>

                    <!-- Intellectual Property -->
                    <div class="terms-section">
                        <h3>5. Intellectual Property</h3>
                        
                        <h4>5.1 Our Rights</h4>
                        <p>The Service and its original content, features, and functionality are owned by IP Logger and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>
                        
                        <h4>5.2 User Content</h4>
                        <p>You retain ownership of any content you submit to our Service. By using our Service, you grant us a limited license to use, store, and display your content solely for the purpose of providing the Service.</p>
                    </div>

                    <!-- Limitation of Liability -->
                    <div class="terms-section">
                        <h3>6. Limitation of Liability</h3>
                        
                        <p>To the maximum extent permitted by law, IP Logger shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
                        <ul>
                            <li>Your use or inability to use the Service</li>
                            <li>Any unauthorized access to or use of our servers</li>
                            <li>Any interruption or cessation of transmission to or from the Service</li>
                            <li>Any bugs, viruses, or other harmful code that may be transmitted through the Service</li>
                            <li>Any errors or omissions in any content or for any loss or damage incurred as a result of the use of any content posted, transmitted, or otherwise made available via the Service</li>
                        </ul>
                        
                        <p>In no event shall our total liability to you exceed the amount paid by you, if any, for accessing the Service.</p>
                    </div>

                    <!-- Disclaimers -->
                    <div class="terms-section">
                        <h3>7. Disclaimers</h3>
                        
                        <h4>7.1 Service Availability</h4>
                        <p>The Service is provided on an "AS IS" and "AS AVAILABLE" basis. We make no warranties that the Service will be uninterrupted, secure, or error-free.</p>
                        
                        <h4>7.2 Accuracy of Information</h4>
                        <p>While we strive to provide accurate tracking information, we cannot guarantee the accuracy, completeness, or reliability of any data collected through our Service.</p>
                        
                        <h4>7.3 Third-Party Services</h4>
                        <p>Our Service may integrate with third-party services for geolocation and analytics. We are not responsible for the accuracy or reliability of information provided by these third-party services.</p>
                    </div>

                    <!-- Indemnification -->
                    <div class="terms-section">
                        <h3>8. Indemnification</h3>
                        <p>You agree to defend, indemnify, and hold harmless IP Logger and its officers, directors, employees, and agents from and against any claims, damages, obligations, losses, liabilities, costs, or debt arising from:</p>
                        <ul>
                            <li>Your use of the Service</li>
                            <li>Your violation of these Terms</li>
                            <li>Your violation of any third-party rights</li>
                            <li>Any content you submit to the Service</li>
                        </ul>
                    </div>

                    <!-- Termination -->
                    <div class="terms-section">
                        <h3>9. Termination</h3>
                        
                        <h4>9.1 Termination by You</h4>
                        <p>You may terminate your use of the Service at any time by discontinuing use and deleting your account.</p>
                        
                        <h4>9.2 Termination by Us</h4>
                        <p>We may terminate or suspend your access to the Service immediately, without prior notice, for any reason, including without limitation if you breach these Terms.</p>
                        
                        <h4>9.3 Effect of Termination</h4>
                        <p>Upon termination, your right to use the Service will cease immediately. We may delete your account and any associated data in accordance with our data retention policies.</p>
                    </div>

                    <!-- Governing Law -->
                    <div class="terms-section">
                        <h3>10. Governing Law</h3>
                        <p>These Terms shall be governed by and construed in accordance with the laws of the jurisdiction where IP Logger operates, without regard to its conflict of law provisions.</p>
                    </div>

                    <!-- Changes to Terms -->
                    <div class="terms-section">
                        <h3>11. Changes to Terms</h3>
                        <p>We reserve the right to modify these Terms at any time. We will notify users of any material changes by posting the new Terms on this page and updating the "Last updated" date.</p>
                        
                        <p>Your continued use of the Service after any changes constitutes acceptance of the new Terms.</p>
                    </div>

                    <!-- Contact Information -->
                    <div class="terms-section">
                        <h3>12. Contact Information</h3>
                        <p>If you have any questions about these Terms of Use, please contact us:</p>
                        <ul>
                            <li>Email: [Your contact email]</li>
                            <li>Website: keizai-tech.com/projects/ip-logger</li>
                        </ul>
                    </div>

                    <!-- Acceptance -->
                    <div class="terms-section">
                        <div class="highlight-box">
                            <strong>By using our Service, you acknowledge that you have read, understood, and agree to be bound by these Terms of Use.</strong>
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

    </body>
</html>
