<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Test the new functionality
$test_shortcode = generateShortCode();
$test_tracking_code = generateRandomString(12);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Prototype Test - IP Logger</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h4><i class='fas fa-vial'></i> Prototype Test Results</h4>
                    </div>
                    <div class='card-body'>
                        <h5>Generated Test Values:</h5>
                        <ul class='list-group mb-3'>
                            <li class='list-group-item'>
                                <strong>Shortcode:</strong> <code>$test_shortcode</code>
                            </li>
                            <li class='list-group-item'>
                                <strong>Tracking Code:</strong> <code>$test_tracking_code</code>
                            </li>
                            <li class='list-group-item'>
                                <strong>Final Link:</strong> <code>keizai-tech.com/$test_shortcode.html</code>
                            </li>
                            <li class='list-group-item'>
                                <strong>Tracking URL:</strong> <code>https://keizai-tech.com/projects/ip-logger/$test_tracking_code</code>
                            </li>
                        </ul>
                        
                        <div class='alert alert-success'>
                            <i class='fas fa-check-circle'></i> 
                            <strong>Success!</strong> The prototype functionality is working correctly.
                        </div>
                        
                        <div class='text-center'>
                            <a href='create_link.php' class='btn btn-primary'>
                                <i class='fas fa-plus'></i> Try the New Create Link Page
                            </a>
                            <a href='index.php' class='btn btn-secondary ms-2'>
                                <i class='fas fa-home'></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
?>
