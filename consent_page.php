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
?>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consentimento necessário - IP Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/pearlight.css"> 
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="consent-card">
                    <div class="consent-header">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h2>Consentimento necessário</h2>
                        <p class="mb-0">Este link requer seu consentimento antes de prosseguir</p>
                    </div>
                    
                    <div class="consent-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <p id="privacy-notice" class="warning-subtitle">Aviso de privacidade:</p><p>This link uses tracking technology to collect information about your visit. 
                            Your data will be used solely for analytics purposes and will not be shared with third parties.</p>
                        </div>
                        
                        <h5>Quais informações coletamos:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Endereço IP e dados de localização</li>
                            <li><i class="fas fa-check text-success me-2"></i>Informações do dispositivo e do navegador</li>
                            <li><i class="fas fa-check text-success me-2"></i>Timestamp* de data e hora da visita e duração</li>
                            <li><i class="fas fa-check text-success me-2"></i>Site de referência (se aplicável)</li>
                        </ul>
                        
                        <div class="consent-option">
                            <input type="checkbox" id="consent_tracking" class="form-check-input" checked>
                            <label for="consent_tracking" class="form-check-label ms-2">
                                <strong>I consent to tracking</strong>
                                <br><small class="text-muted">Allow this website to collect tracking data for analytics purposes</small>
                            </label>
                        </div>
                        
                        <?php if ($link['collect_gps_data']): ?>
                        <div class="consent-option">
                            <input type="checkbox" id="consent_gps" class="form-check-input">
                            <label for="consent_gps" class="form-check-label ms-2">
                                <strong>I consent to GPS location tracking</strong>
                                <br><small class="text-muted">Allow precise GPS location tracking (optional)</small>
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="redirect.php?code=<?php echo $short_code; ?>&consent=1" 
                               class="btn btn-consent me-3" onclick="return confirmConsent()">
                                <i class="fas fa-check"></i> Accept & Continue
                            </a>
                            <a href="<?php echo htmlspecialchars($link['original_url']); ?>" 
                               class="btn btn-decline">
                                <i class="fas fa-times"></i> Decline & Continue
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                By clicking "Accept & Continue", you agree to our 
                                <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmConsent() {
            if (!document.getElementById('consent_tracking').checked) {
                alert('Please check the consent box to continue.');
                return false;
            }
            return true;
        }
        
        // Add GPS consent to URL if checked
        document.getElementById('consent_gps').addEventListener('change', function() {
            const acceptBtn = document.querySelector('.btn-consent');
            let url = 'redirect.php?code=<?php echo $short_code; ?>&consent=1';
            
            if (this.checked) {
                url += '&gps=1';
            }
            
            acceptBtn.href = url;
        });
    </script>
</body>
</html>
