<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/qr_generator.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get all links
$stmt = $conn->query("
    SELECT l.*, 
           COUNT(t.id) as click_count,
           COUNT(DISTINCT t.ip_address) as unique_visitors
    FROM links l 
    LEFT JOIN targets t ON l.id = t.link_id 
    GROUP BY l.id 
    ORDER BY l.created_at DESC
");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle QR Code download
if (isset($_GET['download_qr']) && isset($_GET['link_id'])) {
    $link_id = (int)$_GET['link_id'];
    $stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$link_id]);
    $link = $stmt->fetch();
    
    if ($link) {
        $short_url = BASE_URL . $link['short_code'];
        QRCodeGenerator::downloadQRCode($short_url, 'qr_code_' . $link['short_code'] . '.png');
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de QR Code - IP Logger</title>
    
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
        
        .qr-content {
            padding: 2rem;
        }
        
        .qr-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .qr-card h3 {
            color: #495057;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .qr-code-display {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .qr-code-image {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            max-width: 100%;
            height: auto;
        }
        
        .qr-code-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .qr-code-actions .btn {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        
        .link-info {
            background: #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .link-info h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .link-info p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .link-url {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            word-break: break-all;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Mobile Header -->
    <div class="mobile-header d-flex justify-content-between align-items-center">
        <div class="navbar-brand">
            <i class="fas fa-qrcode"></i> IP Logger
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
                            <a class="nav-link" href="create_link.php">
                                <i class="fas fa-plus"></i> Criar Link
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="qr_generator.php">
                                <i class="fas fa-qrcode"></i> QR Codes
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
                <div class="qr-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="fas fa-qrcode"></i> Gerador de QR Code</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="create_link.php" class="btn btn-primary me-2">
                                <i class="fas fa-plus"></i> Criar Novo Link
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                            </a>
                        </div>
                    </div>

                    <?php if (empty($links)): ?>
                        <div class="qr-card text-center">
                            <h3><i class="fas fa-qrcode"></i> Nenhum Link Encontrado</h3>
                            <p class="text-muted">Você ainda não criou nenhum link. Crie seu primeiro link para gerar QR Codes.</p>
                            <a href="create_link.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Criar Primeiro Link
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($links as $link): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="qr-card">
                                        <h5><i class="fas fa-link"></i> <?php echo $link['short_code']; ?></h5>
                                        
                                        <div class="link-info">
                                            <h6>URL Original:</h6>
                                            <div class="link-url"><?php echo htmlspecialchars($link['original_url']); ?></div>
                                        </div>
                                        
                                        <div class="link-info">
                                            <h6>Link Encurtado:</h6>
                                            <div class="link-url"><?php echo BASE_URL . $link['short_code']; ?></div>
                                        </div>
                                        
                                        <div class="link-info">
                                            <h6>Estatísticas:</h6>
                                            <p><i class="fas fa-mouse-pointer"></i> <?php echo $link['click_count']; ?> cliques</p>
                                            <p><i class="fas fa-users"></i> <?php echo $link['unique_visitors']; ?> visitantes únicos</p>
                                        </div>
                                        
                                        <div class="qr-code-display">
                                            <img src="<?php echo QRCodeGenerator::generateQRCode(BASE_URL . $link['short_code'], 200); ?>" 
                                                 alt="QR Code" 
                                                 class="qr-code-image">
                                            
                                            <div class="qr-code-actions">
                                                <a href="?download_qr=1&link_id=<?php echo $link['id']; ?>" 
                                                   class="btn btn-success">
                                                    <i class="fas fa-download"></i> Baixar QR Code
                                                </a>
                                                <button class="btn btn-info" 
                                                        onclick="copyToClipboard('<?php echo BASE_URL . $link['short_code']; ?>')">
                                                    <i class="fas fa-copy"></i> Copiar Link
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                btn.classList.remove('btn-info');
                btn.classList.add('btn-success');
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-info');
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
