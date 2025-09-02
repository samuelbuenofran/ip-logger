<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$short_code = $_GET['code'] ?? '';
$stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ?");
$stmt->execute([$short_code]);
$link = $stmt->fetch();

if (!$link) {
    header('Location: index.php?error=link_not_found');
    exit;
}

$destination_url = $link['original_url'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destination Preview - IP Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .preview-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .preview-header {
            background: #17a2b8;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .preview-body {
            padding: 30px;
        }
        .url-display {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }
        .btn-continue {
            background: #28a745;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-continue:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-back {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .security-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="preview-card">
                    <div class="preview-header">
                        <i class="fas fa-eye fa-3x mb-3"></i>
                        <h2>Destination Preview</h2>
                        <p class="mb-0">You're about to visit this website</p>
                    </div>
                    
                    <div class="preview-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Security Notice:</strong> Please review the destination URL before proceeding. 
                            Make sure you trust this website.
                        </div>
                        
                        <h5>Destination URL:</h5>
                        <div class="url-display">
                            <i class="fas fa-link text-primary me-2"></i>
                            <?php echo htmlspecialchars($destination_url); ?>
                        </div>
                        
                        <div class="security-info">
                            <h6><i class="fas fa-shield-alt text-info"></i> Security Information:</h6>
                            <ul class="mb-0">
                                <li>This link was created on: <strong><?php echo formatDate($link['created_at']); ?></strong></li>
                                <li>Link expires: <strong><?php echo $link['expiry_date'] ? formatDate($link['expiry_date']) : 'Never'; ?></strong></li>
                                <li>Tracking enabled: <strong><?php echo $link['collect_smart_data'] ? 'Yes' : 'No'; ?></strong></li>
                                <li>GPS tracking: <strong><?php echo $link['collect_gps_data'] ? 'Yes' : 'No'; ?></strong></li>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="redirect.php?code=<?php echo $short_code; ?>&redirect=1" 
                               class="btn btn-continue me-3">
                                <i class="fas fa-external-link-alt"></i> Continue to Destination
                            </a>
                            <button onclick="history.back()" class="btn btn-back">
                                <i class="fas fa-arrow-left"></i> Go Back
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                This preview helps you verify the destination before visiting. 
                                If you don't recognize this URL, please go back.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-redirect after 10 seconds
        let countdown = 10;
        const countdownElement = document.createElement('div');
        countdownElement.className = 'text-center mt-3';
        countdownElement.innerHTML = `<small class="text-muted">Auto-redirecting in <span id="countdown">${countdown}</span> seconds...</small>`;
        document.querySelector('.preview-body').appendChild(countdownElement);
        
        const countdownInterval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'redirect.php?code=<?php echo $short_code; ?>&redirect=1';
            }
        }, 1000);
        
        // Stop auto-redirect if user interacts
        document.addEventListener('click', function() {
            clearInterval(countdownInterval);
            countdownElement.remove();
        });
    </script>
</body>
</html>
