<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$active_menu = "links";
include_once "../layout/header.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get all links with their statistics
$stmt = $conn->query("
    SELECT l.*, 
           COUNT(t.id) as click_count,
           COUNT(DISTINCT t.ip_address) as unique_visitors
    FROM links l 
    LEFT JOIN targets t ON l.id = t.link_id 
    GROUP BY l.id 
    ORDER BY l.created_at DESC
");
$links = $stmt->fetchAll();

// Get statistics
$total_links = count($links);
$total_clicks = array_sum(array_column($links, 'click_count'));
$active_links = count(array_filter($links, function ($link) {
    return !$link['expiry_date'] || strtotime($link['expiry_date']) > time();
}));
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
                    Meus Links
                    <small>Gerenciar todos os seus links</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Meus Links</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <?php echo displayMessage(); ?>

                <!-- Info boxes -->
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-aqua"><i class="fa fa-link"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total de Links</span>
                                <span class="info-box-number"><?php echo $total_links; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-red"><i class="fa fa-mouse-pointer"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total de Cliques</span>
                                <span class="info-box-number"><?php echo $total_clicks; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Visitantes Únicos</span>
                                <span class="info-box-number"><?php echo array_sum(array_column($links, 'unique_visitors')); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow"><i class="fa fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Links Ativos</span>
                                <span class="info-box-number"><?php echo $active_links; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Todos os Links</h3>
                                <div class="box-tools">
                                    <a href="../create_link" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus"></i> Criar Novo Link
                                    </a>
                                </div>
                            </div>
                            <!-- /.box-header -->
                            <div class="box-body">
                                <?php if (empty($links)): ?>
                                    <div class="text-center" style="padding: 50px;">
                                        <i class="fa fa-link fa-5x text-muted"></i>
                                        <h3 class="text-muted">Nenhum link criado ainda</h3>
                                        <p class="text-muted">Crie seu primeiro link para começar a rastrear visitantes</p>
                                        <a href="../create_link" class="btn btn-primary btn-lg">
                                            <i class="fa fa-plus"></i> Criar Primeiro Link
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="links-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Código</th>
                                                    <th>URL Original</th>
                                                    <th>Cliques</th>
                                                    <th>Visitantes</th>
                                                    <th>Criado</th>
                                                    <th>Expira</th>
                                                    <th>Status</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($links as $link): ?>
                                                    <?php
                                                    $isExpired = $link['expiry_date'] && strtotime($link['expiry_date']) < time();
                                                    $shortUrl = BASE_URL . $link['short_code'];
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $link['id']; ?></td>
                                                        <td>
                                                            <a href="<?php echo $shortUrl; ?>" target="_blank" class="text-primary">
                                                                <?php echo $link['short_code']; ?>
                                                            </a>
                                                            <br>
                                                            <small class="text-muted"><?php echo $shortUrl; ?></small>
                                                        </td>
                                                        <td>
                                                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($link['original_url']); ?>">
                                                                <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank">
                                                                    <?php echo htmlspecialchars($link['original_url']); ?>
                                                                </a>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-blue"><?php echo $link['click_count']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-green"><?php echo $link['unique_visitors']; ?></span>
                                                        </td>
                                                        <td><?php echo formatDate($link['created_at']); ?></td>
                                                        <td>
                                                            <?php if ($link['expiry_date']): ?>
                                                                <?php echo formatDate($link['expiry_date']); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Nunca</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($isExpired): ?>
                                                                <span class="label label-danger">Expirado</span>
                                                            <?php else: ?>
                                                                <span class="label label-success">Ativo</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="<?php echo $shortUrl; ?>" target="_blank" class="btn btn-xs btn-info" title="Testar Link">
                                                                    <i class="fa fa-external-link"></i>
                                                                </a>
                                                                <a href="../view_targets?link_id=<?php echo $link['id']; ?>" class="btn btn-xs btn-primary" title="Ver Dados">
                                                                    <i class="fa fa-eye"></i>
                                                                </a>
                                                                <button class="btn btn-xs btn-warning" onclick="copyToClipboard('<?php echo $shortUrl; ?>')" title="Copiar URL">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- /.box-body -->
                        </div>
                        <!-- /.box -->
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

    <!-- DataTables -->
    <script src="../../plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../../plugins/datatables/dataTables.bootstrap.min.js"></script>

    <script>
        $(function() {
            $('#links-table').DataTable({
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

        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('URL copiada para a área de transferência!', 'success');
                }).catch(function(err) {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showToast('URL copiada para a área de transferência!', 'success');
                } else {
                    showToast('Falha ao copiar URL', 'error');
                }
            } catch (err) {
                showToast('Falha ao copiar URL', 'error');
            }

            document.body.removeChild(textArea);
        }

        function showToast(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const toast = $('<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"><button type="button" class="close" data-dismiss="alert">&times;</button>' + message + '</div>');
            $('body').append(toast);

            setTimeout(function() {
                toast.fadeOut(function() {
                    toast.remove();
                });
            }, 3000);
        }
    </script>