<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

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
    
    // Insert new link
    $stmt = $conn->prepare("
        INSERT INTO links (short_code, original_url, password, tracking_code, password_recovery_code, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$shortcode, $original_url, $password, $tracking_code, $recovery_code])) {
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
    </style>
</head>
<body class="bg-light">
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
                        <p class="text-muted">Encurtador de URL & Rastreador</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="links.php">
                                <i class="fas fa-link"></i> Meus Links
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="create_link.php">
                                <i class="fas fa-plus"></i> Criar Link
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_targets.php">
                                <i class="fas fa-map-marker-alt"></i> Geolocalização
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-cog"></i> Painel Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="privacy.php">
                                <i class="fas fa-user-shield"></i> Política de Privacidade
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="terms.php">
                                <i class="fas fa-file-contract"></i> Termos de Uso
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cookies.php">
                                <i class="fas fa-cookie-bite"></i> Política de Cookies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="password_recovery.php">
                                <i class="fas fa-key"></i> Recuperar Senha
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="create-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="fas fa-plus"></i> Criar Novo Link</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="index.php" class="btn btn-outline-secondary">
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
                        <h3><i class="fas fa-link"></i> Criar Novo Link</h3>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="create_link">
                            
                            <div class="mb-3">
                                <label for="original_url" class="form-label">
                                    <i class="fas fa-globe"></i> URL de Destino
                                </label>
                                <input type="text" class="form-control" id="original_url" name="original_url" 
                                       placeholder="www.joblinerh.com.br ou https://example.com/documento.pdf..." 
                                       required>
                                <div class="form-text">Cole aqui a URL que você quer enviar (imagem, documento, website, etc.) - não precisa incluir https://</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Senha para Ver Logs
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Digite uma senha..." 
                                       required>
                                <div class="form-text">Esta senha será usada para acessar os logs de localização</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
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
    </script>

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
    </script>
</body>
</html>