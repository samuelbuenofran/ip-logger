<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$active_menu = "create_link";
include_once "../layout/header.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'create_link') {
    $original_url = sanitizeInput($_POST['original_url']);
    $password = $_POST['password'];
    $expiry_days = isset($_POST['expiry_days']) ? (int)$_POST['expiry_days'] : 0;

    // Validate input
    if (!isValidUrl($original_url)) {
        redirectWithMessage('index.php', 'Por favor, insira uma URL válida', 'error');
    }

    // Normalize URL
    $original_url = normalizeUrl($original_url);

    if (strlen($password) < 3) {
        redirectWithMessage('index.php', 'A senha deve ter pelo menos 3 caracteres', 'error');
    }

    // Generate shortcode and tracking code
    $shortcode = generateShortCode(8);
    $tracking_code = generateRandomString(12);
    $recovery_code = generateRandomString(16);

    // Check if shortcode already exists
    $stmt = $conn->prepare("SELECT id FROM links WHERE short_code = ?");
    $stmt->execute([$shortcode]);
    if ($stmt->fetch()) {
        $shortcode = generateShortCode(8);
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Calculate expiry date
    $expiry_date = null;
    if ($expiry_days > 0) {
        $expiry_date = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
    }

    try {
        // Insert new link
        $stmt = $conn->prepare("
            INSERT INTO links (short_code, original_url, password, tracking_code, password_recovery_code, expiry_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$shortcode, $original_url, $hashed_password, $tracking_code, $recovery_code, $expiry_date]);

        $link_id = $conn->lastInsertId();

        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO audit_log (action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'link_created',
            "Link created: {$shortcode} -> {$original_url}",
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        redirectWithMessage('index.php', 'Link criado com sucesso!', 'success');
    } catch (PDOException $e) {
        redirectWithMessage('index.php', 'Erro ao criar link: ' . $e->getMessage(), 'error');
    }
}
?>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

        <?php include_once "../layout/topmenu.php"; ?>
        <?php include_once "../layout/left-sidebar.php"; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                    Criar Link
                    <small>Criar um novo link de rastreamento</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Criar Link</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <?php echo displayMessage(); ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Informações do Link</h3>
                            </div>
                            <!-- /.box-header -->
                            <div class="box-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="create_link">

                                    <div class="form-group">
                                        <label for="original_url">URL de Destino *</label>
                                        <input type="url" class="form-control" id="original_url" name="original_url"
                                            placeholder="https://exemplo.com" required>
                                        <small class="help-block">A URL que será redirecionada quando alguém clicar no link encurtado</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="password">Senha de Proteção *</label>
                                        <input type="password" class="form-control" id="password" name="password"
                                            placeholder="Digite uma senha forte" required minlength="3">
                                        <small class="help-block">Esta senha será necessária para visualizar os dados de rastreamento</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="expiry_days">Expiração (opcional)</label>
                                        <select class="form-control" id="expiry_days" name="expiry_days">
                                            <option value="0">Nunca expira</option>
                                            <option value="1">1 dia</option>
                                            <option value="7">7 dias</option>
                                            <option value="30" selected>30 dias</option>
                                            <option value="90">90 dias</option>
                                            <option value="365">1 ano</option>
                                        </select>
                                        <small class="help-block">O link será automaticamente desativado após o período selecionado</small>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fa fa-plus"></i> Criar Link
                                        </button>
                                        <a href="../links" class="btn btn-default btn-lg">
                                            <i class="fa fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </form>
                            </div>
                            <!-- /.box-body -->
                        </div>
                        <!-- /.box -->
                    </div>

                    <div class="col-md-4">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title">Informações Importantes</h3>
                            </div>
                            <div class="box-body">
                                <div class="alert alert-warning">
                                    <h4><i class="icon fa fa-warning"></i> Aviso!</h4>
                                    Este serviço é público. Qualquer link que você criar será visível para todos os visitantes.
                                </div>

                                <h5>Como funciona:</h5>
                                <ol>
                                    <li>Digite a URL que deseja encurtar</li>
                                    <li>Defina uma senha para proteger os dados</li>
                                    <li>Escolha quando o link deve expirar</li>
                                    <li>Clique em "Criar Link"</li>
                                </ol>

                                <h5>Recursos:</h5>
                                <ul>
                                    <li><i class="fa fa-check text-green"></i> Rastreamento de IP</li>
                                    <li><i class="fa fa-check text-green"></i> Geolocalização</li>
                                    <li><i class="fa fa-check text-green"></i> Detecção de dispositivo</li>
                                    <li><i class="fa fa-check text-green"></i> Estatísticas detalhadas</li>
                                    <li><i class="fa fa-check text-green"></i> Mapa de visitantes</li>
                                </ul>
                            </div>
                        </div>

                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title">Dicas de Segurança</h3>
                            </div>
                            <div class="box-body">
                                <ul>
                                    <li>Use senhas fortes e únicas</li>
                                    <li>Não compartilhe suas senhas</li>
                                    <li>Configure expiração para links temporários</li>
                                    <li>Monitore regularmente os dados de acesso</li>
                                    <li>Use HTTPS sempre que possível</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php include_once "../layout/copyright.php"; ?>
        <?php include_once "../layout/right-sidebar.php"; ?>

        <!-- /.control-sidebar -->
        <div class="control-sidebar-bg"></div>
    </div>
    <!-- ./wrapper -->

    <?php include_once "../layout/footer.php" ?>

    <script>
        $(function() {
            // Auto-prepend https:// if no protocol is provided
            $('#original_url').on('blur', function() {
                var url = $(this).val();
                if (url && !url.match(/^https?:\/\//)) {
                    $(this).val('https://' + url);
                }
            });

            // Form validation
            $('form').on('submit', function(e) {
                var url = $('#original_url').val();
                var password = $('#password').val();

                if (!url) {
                    e.preventDefault();
                    alert('Por favor, insira uma URL válida');
                    $('#original_url').focus();
                    return false;
                }

                if (!password || password.length < 3) {
                    e.preventDefault();
                    alert('A senha deve ter pelo menos 3 caracteres');
                    $('#password').focus();
                    return false;
                }

                // Validate URL format
                var urlPattern = /^https?:\/\/.+/;
                if (!urlPattern.test(url)) {
                    e.preventDefault();
                    alert('Por favor, insira uma URL válida (deve começar com http:// ou https://)');
                    $('#original_url').focus();
                    return false;
                }
            });
        });
    </script>