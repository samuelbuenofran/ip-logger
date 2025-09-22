<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$active_menu = "view_targets";
include_once "../layout/header.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get link ID from URL
$link_id = $_GET['link_id'] ?? 0;

if (!$link_id) {
    redirectWithMessage('../links', 'Link não especificado', 'error');
}

// Get link information
$stmt = $conn->prepare("SELECT * FROM links WHERE id = ?");
$stmt->execute([$link_id]);
$link = $stmt->fetch();

if (!$link) {
    redirectWithMessage('../links', 'Link não encontrado', 'error');
}

// Handle password verification
$password_verified = isset($_SESSION['link_' . $link_id . '_verified']);

if (isset($_POST['verify_password'])) {
    $password = $_POST['password'];
    if (password_verify($password, $link['password'])) {
        $_SESSION['link_' . $link_id . '_verified'] = true;
        $password_verified = true;
    } else {
        $password_error = 'Senha incorreta';
    }
}

// Get targets for this link
$stmt = $conn->prepare("
    SELECT * FROM targets 
    WHERE link_id = ? 
    ORDER BY clicked_at DESC
");
$stmt->execute([$link_id]);
$targets = $stmt->fetchAll();

// Get statistics
$total_clicks = count($targets);
$unique_visitors = count(array_unique(array_column($targets, 'ip_address')));
$countries = array_count_values(array_column($targets, 'country'));
$devices = array_count_values(array_column($targets, 'device_type'));
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
                    Dados de Rastreamento
                    <small><?php echo $link['short_code']; ?></small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li><a href="../links">Meus Links</a></li>
                    <li class="active">Dados de Rastreamento</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <?php if (!$password_verified): ?>
                    <!-- Password Verification -->
                    <div class="row">
                        <div class="col-md-4 col-md-offset-4">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Verificação de Senha</h3>
                                </div>
                                <div class="box-body">
                                    <p>Este link está protegido por senha. Digite a senha para visualizar os dados de rastreamento.</p>

                                    <?php if (isset($password_error)): ?>
                                        <div class="alert alert-danger">
                                            <i class="fa fa-warning"></i> <?php echo $password_error; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <label for="password">Senha</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name="verify_password" class="btn btn-primary btn-block">
                                                <i class="fa fa-unlock"></i> Verificar
                                            </button>
                                        </div>
                                    </form>

                                    <div class="text-center">
                                        <a href="../links" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Data Display -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Informações do Link</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Código:</strong> <?php echo $link['short_code']; ?></p>
                                            <p><strong>URL Original:</strong>
                                                <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($link['original_url']); ?>
                                                </a>
                                            </p>
                                            <p><strong>Criado em:</strong> <?php echo formatDate($link['created_at']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Total de Cliques:</strong> <span class="badge bg-blue"><?php echo $total_clicks; ?></span></p>
                                            <p><strong>Visitantes Únicos:</strong> <span class="badge bg-green"><?php echo $unique_visitors; ?></span></p>
                                            <p><strong>Status:</strong>
                                                <?php if ($link['expiry_date'] && strtotime($link['expiry_date']) < time()): ?>
                                                    <span class="label label-danger">Expirado</span>
                                                <?php else: ?>
                                                    <span class="label label-success">Ativo</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="box box-info">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Países</h3>
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
                                                <?php foreach (array_slice($countries, 0, 10, true) as $country => $count): ?>
                                                    <tr>
                                                        <td><?php echo $country ?: 'Unknown'; ?></td>
                                                        <td><span class="badge bg-blue"><?php echo $count; ?></span></td>
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
                                    <h3 class="box-title">Dispositivos</h3>
                                </div>
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Dispositivo</th>
                                                    <th>Visitas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($devices as $device => $count): ?>
                                                    <tr>
                                                        <td><?php echo ucfirst($device); ?></td>
                                                        <td><span class="badge bg-green"><?php echo $count; ?></span></td>
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
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Dados Detalhados de Visitantes</h3>
                                </div>
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="targets-table">
                                            <thead>
                                                <tr>
                                                    <th>IP</th>
                                                    <th>País</th>
                                                    <th>Cidade</th>
                                                    <th>ISP</th>
                                                    <th>Dispositivo</th>
                                                    <th>Navegador</th>
                                                    <th>OS</th>
                                                    <th>Data</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($targets as $target): ?>
                                                    <tr>
                                                        <td><?php echo $target['ip_address']; ?></td>
                                                        <td>
                                                            <?php if ($target['country']): ?>
                                                                <i class="fa fa-globe"></i>
                                                                <?php echo $target['country']; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Unknown</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $target['city'] ?: 'Unknown'; ?></td>
                                                        <td><?php echo $target['isp'] ?: 'Unknown'; ?></td>
                                                        <td>
                                                            <span class="label label-<?php echo $target['device_type'] == 'mobile' ? 'success' : 'primary'; ?>">
                                                                <?php echo ucfirst($target['device_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $target['browser_name'] ?: 'Unknown'; ?></td>
                                                        <td><?php echo $target['os_name'] ?: 'Unknown'; ?></td>
                                                        <td><?php echo timeAgo($target['clicked_at']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
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

    <!-- DataTables -->
    <script src="../../plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../../plugins/datatables/dataTables.bootstrap.min.js"></script>

    <script>
        $(function() {
            $('#targets-table').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
                }
            });
        });
    </script>