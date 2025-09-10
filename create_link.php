<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/qr_generator.php';
require_once 'includes/sidebar_helper.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle form submission for creating new links
if (isset($_POST['action']) && $_POST['action'] === 'create_link') {
    $original_url = sanitizeInput($_POST['original_url']);
    $password = $_POST['password'];
    
    // Validate input
    if (!isValidUrl($original_url)) {
        redirectWithMessage('create_link.php', 'Por favor, insira uma URL válida', 'error');
    }
    
    // Normalize URL (add https:// if missing)
    $original_url = normalizeUrl($original_url);
    
    if (strlen($password) < 3) {
        redirectWithMessage('create_link.php', 'A senha deve ter pelo menos 3 caracteres', 'error');
    }
    
    // Generate shortcode and tracking code
    $shortcode = generateRandomString(8);
    $tracking_code = generateRandomString(12);
    $recovery_code = generateRandomString(16);
    
    // Check if shortcode already exists (very unlikely with 8 chars)
    $stmt = $conn->prepare("SELECT id FROM links WHERE short_code = ?");
    $stmt->execute([$shortcode]);
    if ($stmt->fetch()) {
        // Regenerate if exists
        $shortcode = generateRandomString(8);
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new link
    $stmt = $conn->prepare("
        INSERT INTO links (short_code, original_url, password, tracking_code, password_recovery_code, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$shortcode, $original_url, $hashed_password, $tracking_code, $recovery_code])) {
        $link_id = $conn->lastInsertId();
        
        // Store in session for display
        $_SESSION['created_link'] = [
            'short_url' => BASE_URL . $shortcode,
            'tracking_url' => BASE_URL . $tracking_code,
            'recovery_code' => $recovery_code,
            'original_url' => $original_url
        ];
        
        redirectWithMessage('create_link.php', 'Link criado com sucesso!', 'success');
    } else {
        redirectWithMessage('create_link.php', 'Erro ao criar o link. Tente novamente.', 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Link - IP Logger</title>
    
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
        
        .create-content {
            padding: 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-card h3 {
            color: #495057;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-1px);
        }
        
        .success-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .success-card h4 {
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .url-display {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .url-text {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .copy-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .warning-box .text-warning {
            color: #856404 !important;
            font-weight: 500;
        }
        
        .example-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .example-box h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .example-box p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        /* QR Code Styles */
        .qr-code-container {
            text-align: center;
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        
        .qr-code-image {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 0 auto 1rem auto;
            width: 150px;
            height: 150px;
            display: block;
            object-fit: contain;
        }
        
        .qr-code-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .qr-code-actions .copy-btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .qr-code-actions .copy-btn i {
            margin-right: 0.3rem;
        }
        
        .qr-code-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 150px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            color: #6c757d;
            margin: 0 auto 1rem auto;
            width: 150px;
        }
        
        .qr-code-loading .spinner-border {
            width: 2rem;
            height: 2rem;
            margin-right: 0.5rem;
        }
        
        @media (max-width: 576px) {
            .qr-code-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .qr-code-actions .copy-btn {
                width: 100%;
                max-width: 200px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php echo generateMobileHeader(); ?>
    <?php echo generateSidebarOverlay(); ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo generateSidebar(); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="create-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="fas fa-plus"></i> Criar Novo Link</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="index.php" class="apple-btn apple-btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php echo displayMessage(); ?>

                    <!-- Success Message -->
                    <?php if (isset($_SESSION['created_link'])): ?>
                        <?php $link = $_SESSION['created_link']; ?>
                        <div class="success-card">
                            <h4><i class="fas fa-check-circle"></i> Link Criado com Sucesso!</h4>
                            
                            <div class="url-display">
                                <strong>Link Encurtado:</strong>
                                <div class="url-text"><?php echo $link['short_url']; ?></div>
                                <button class="copy-btn" onclick="copyToClipboard('<?php echo $link['short_url']; ?>')">
                                    <i class="fas fa-copy"></i> Copiar Link
                                </button>
                            </div>
                            
                            <div class="url-display">
                                <strong>Link de Rastreamento:</strong>
                                <div class="url-text"><?php echo $link['tracking_url']; ?></div>
                                <button class="copy-btn" onclick="copyToClipboard('<?php echo $link['tracking_url']; ?>')">
                                    <i class="fas fa-copy"></i> Copiar Link de Rastreamento
                                </button>
                            </div>
                            
                            <div class="url-display">
                                <strong>QR Code:</strong>
                                <div class="qr-code-container">
                                    <div class="qr-code-loading" id="qrCodeLoading">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <span>Gerando QR Code...</span>
                                    </div>
                                    <img src="<?php echo QRCodeGenerator::generateQRCode($link['short_url'], 150); ?>" 
                                         alt="QR Code" 
                                         class="qr-code-image"
                                         id="qrCodeImage"
                                         data-url="<?php echo htmlspecialchars($link['short_url']); ?>"
                                         style="display: none;">
                                    <div class="qr-code-actions">
                                        <button class="copy-btn" onclick="downloadQRCode('<?php echo $link['short_url']; ?>')">
                                            <i class="fas fa-download"></i> Baixar QR Code
                                        </button>
                                        <button class="copy-btn" onclick="copyQRCodeUrl('<?php echo QRCodeGenerator::generateQRCode($link['short_url'], 150); ?>')">
                                            <i class="fas fa-copy"></i> Copiar URL do QR Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="warning-box">
                                <p class="text-warning mb-0">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Importante:</strong> Guarde este código de recuperação: <code><?php echo $link['recovery_code']; ?></code>
                                </p>
                            </div>
                        </div>
                        <?php unset($_SESSION['created_link']); ?>
                    <?php endif; ?>

                    <!-- Create Link Form -->
                    <div class="form-card">
                        <h3 class="apple-title-2"><i class="fas fa-link"></i> Criar Novo Link</h3>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="create_link">
                            
                            <div class="mb-3">
                                <label for="original_url" class="apple-body-emphasized">
                                    <i class="fas fa-globe"></i> URL de Destino
                                </label>
                                <input type="text" class="apple-input" id="original_url" name="original_url" 
                                       placeholder="www.joblinerh.com.br ou https://example.com/documento.pdf..." 
                                       required>
                                <div class="apple-footnote" style="margin-top: var(--apple-space-xs);">Cole aqui a URL que você quer enviar (imagem, documento, website, etc.) - não precisa incluir https://</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="apple-body-emphasized">
                                    <i class="fas fa-lock"></i> Senha para Ver Logs
                                </label>
                                <input type="password" class="apple-input" id="password" name="password" 
                                       placeholder="Digite uma senha..." 
                                       required>
                                <div class="apple-footnote" style="margin-top: var(--apple-space-xs);">Esta senha será usada para acessar os logs de localização</div>
                            </div>
                            
                            <button type="submit" class="apple-btn apple-btn-primary">
                                <i class="fas fa-plus"></i> Criar Link
                            </button>
                        </form>
                        
                        <div class="example-box">
                            <h6><i class="fas fa-lightbulb"></i> Como Funciona:</h6>
                            <p>1. Cole qualquer URL (ex: www.joblinerh.com.br ou https://example.com)<br>
                               2. O sistema criará um link encurtado<br>
                               3. Quando alguém acessar o link, será redirecionado para o conteúdo<br>
                               4. Você receberá a localização da pessoa nos logs</p>
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
    
    <script>
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                btn.style.background = 'rgba(40, 167, 69, 0.3)';
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.style.background = 'rgba(255, 255, 255, 0.2)';
                }, 2000);
            }).catch(function(err) {
                console.error('Erro ao copiar: ', err);
                alert('Erro ao copiar para a área de transferência');
            });
        }
        
        // Download QR Code
        function downloadQRCode(url) {
            const qrUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' + encodeURIComponent(url);
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'qr_code_' + new Date().toISOString().split('T')[0] + '.png';
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Copy QR Code URL
        function copyQRCodeUrl(qrUrl) {
            copyToClipboard(qrUrl);
        }
        
        // QR Code fallback system
        function initQRCodeFallback() {
            const qrImage = document.getElementById('qrCodeImage');
            const qrLoading = document.getElementById('qrCodeLoading');
            if (!qrImage || !qrLoading) return;
            
            const targetUrl = qrImage.getAttribute('data-url');
            if (!targetUrl) return;
            
            const fallbackAPIs = [
                'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=',
                'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=',
                'https://qr-code-generator.com/api/qr?size=150&data=',
                'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=&format=png'
            ];
            
            let currentAPI = 0;
            let isLoaded = false;
            
            function hideLoading() {
                qrLoading.style.display = 'none';
                qrImage.style.display = 'block';
            }
            
            function showError() {
                qrLoading.style.display = 'none';
                qrImage.style.display = 'none';
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-warning text-center mt-2';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> QR Code temporariamente indisponível. Tente novamente mais tarde.';
                qrImage.parentNode.appendChild(errorDiv);
            }
            
            function tryNextAPI() {
                if (isLoaded) return;
                
                if (currentAPI < fallbackAPIs.length) {
                    const newSrc = fallbackAPIs[currentAPI] + encodeURIComponent(targetUrl);
                    console.log(`Trying QR Code API ${currentAPI + 1}:`, newSrc);
                    qrImage.src = newSrc;
                    currentAPI++;
                } else {
                    // All APIs failed, show error message
                    console.log('All QR Code APIs failed');
                    showError();
                }
            }
            
            qrImage.addEventListener('error', function() {
                console.log('QR Code failed to load, trying next API...');
                setTimeout(tryNextAPI, 1000); // Wait 1 second before trying next API
            });
            
            qrImage.addEventListener('load', function() {
                console.log('QR Code loaded successfully');
                isLoaded = true;
                hideLoading();
            });
            
            // Test if current image loads, if not, start fallback
            setTimeout(function() {
                if (!isLoaded && qrImage.complete && qrImage.naturalHeight === 0) {
                    console.log('Initial QR Code failed, starting fallback...');
                    tryNextAPI();
                }
            }, 2000);
        }
        
        // Initialize QR Code fallback when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initQRCodeFallback();
        });
    </script>

</body>
</html>