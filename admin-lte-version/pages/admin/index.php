<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$active_menu = "admin";
include_once "../layout/header.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Simple admin authentication
$admin_password = 'admin123'; // Change this to a secure password
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'];

// Handle admin login
if (isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'];
    if ($password === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
        $is_authenticated = true;
    } else {
        $login_error = 'Senha de administrador incorreta';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    $is_authenticated = false;
}

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total_links FROM links");
$total_links = $stmt->fetch()['total_links'];

$stmt = $conn->query("SELECT COUNT(*) as total_clicks FROM targets");
$total_clicks = $stmt->fetch()['total_clicks'];

$stmt = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM targets");
$unique_visitors = $stmt->fetch()['unique_visitors'];

$stmt = $conn->query("SELECT COUNT(*) as active_links FROM links WHERE expiry_date IS NULL OR expiry_date > NOW()");
$active_links = $stmt->fetch()['active_links'];

// Get recent activity
$stmt = $conn->query("
    SELECT action, details, ip_address, created_at
    FROM audit_log 
    ORDER BY created_at DESC 
    LIMIT 20
");
$recent_activity = $stmt->fetchAll();

// Get top countries
$stmt = $conn->query("
    SELECT country, COUNT(*) as count
    FROM targets 
    WHERE country IS NOT NULL AND country != 'Unknown'
    GROUP BY country
    ORDER BY count DESC
    LIMIT 10
");
$top_countries = $stmt->fetchAll();
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
                    Painel de Administração
                    <small>Controle total do sistema</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Administração</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <?php if (!$is_authenticated): ?>
                    <!-- Login Form -->
                    <div class="row">
                        <div class="col-md-4 col-md-offset-4">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Acesso Administrativo</h3>
                                </div>
                                <div class="box-body">
                                    <?php if (isset($login_error)): ?>
                                        <div class="alert alert-danger">
                                            <i class="fa fa-warning"></i> <?php echo $login_error; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <label for="admin_password">Senha de Administrador</label>
                                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name="admin_login" class="btn btn-primary btn-block">
                                                <i class="fa fa-sign-in"></i> Entrar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Admin Dashboard -->
                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-aqua">
                                <div class="inner">
                                    <h3><?php echo $total_links; ?></h3>
                                    <p>Total de Links</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-link"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-green">
                                <div class="inner">
                                    <h3><?php echo $total_clicks; ?></h3>
                                    <p>Total de Cliques</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-mouse-pointer"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                    <h3><?php echo $unique_visitors; ?></h3>
                                    <p>Visitantes Únicos</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-red">
                                <div class="inner">
                                    <h3><?php echo $active_links; ?></h3>
                                    <p>Links Ativos</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Atividade Recente</h3>
                                </div>
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Ação</th>
                                                    <th>IP</th>
                                                    <th>Data</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_activity as $activity): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                        <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                                        <td><?php echo timeAgo($activity['created_at']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="box box-success">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Top Países</h3>
                                </div>
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>País</th>
                                                    <th>Visitas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_countries as $country): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($country['country']); ?></td>
                                                        <td>
                                                            <span class="badge bg-blue"><?php echo $country['count']; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Ações Administrativas</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <a href="../links" class="btn btn-primary btn-block">
                                                <i class="fa fa-list"></i> Gerenciar Links
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="../geolocation" class="btn btn-info btn-block">
                                                <i class="fa fa-map"></i> Ver Mapa
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="?logout=1" class="btn btn-danger btn-block">
                                                <i class="fa fa-sign-out"></i> Sair
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-warning btn-block" onclick="exportData()">
                                                <i class="fa fa-download"></i> Exportar Dados
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
        function exportData() {
            if (confirm('Deseja exportar todos os dados do sistema?')) {
                window.location.href = 'export.php';
            }
        }
    </script>