<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$active_menu = "geolocation";
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

// Get all targets with geolocation data
$stmt = $conn->query("
    SELECT t.*, l.short_code, l.original_url
    FROM targets t
    JOIN links l ON t.link_id = l.id
    WHERE t.latitude IS NOT NULL AND t.longitude IS NOT NULL
    ORDER BY t.clicked_at DESC
");
$targets = $stmt->fetchAll();

// Get country statistics
$stmt = $conn->query("
    SELECT country, country_code, COUNT(*) as count
    FROM targets 
    WHERE country IS NOT NULL AND country != 'Unknown'
    GROUP BY country, country_code
    ORDER BY count DESC
");
$countries = $stmt->fetchAll();
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
                    Geolocalização
                    <small>Visualizar visitantes no mapa</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Geolocalização</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="row">
                    <div class="col-md-8">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Mapa de Visitantes</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="box-body">
                                <div id="map" style="height: 500px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title">Estatísticas por País</h3>
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
                                            <?php foreach (array_slice($countries, 0, 10) as $country): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fa fa-globe"></i>
                                                        <?php echo $country['country']; ?>
                                                    </td>
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

                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title">Links com Mais Visitas</h3>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Link</th>
                                                <th>Visitas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($links, 0, 5) as $link): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo BASE_URL . $link['short_code']; ?>" target="_blank">
                                                            <?php echo $link['short_code']; ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-green"><?php echo $link['click_count']; ?></span>
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
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Dados de Visitantes</h3>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" id="visitors-table">
                                        <thead>
                                            <tr>
                                                <th>IP</th>
                                                <th>País</th>
                                                <th>Cidade</th>
                                                <th>ISP</th>
                                                <th>Dispositivo</th>
                                                <th>Link</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($targets, 0, 50) as $target): ?>
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
                                                    <td>
                                                        <a href="<?php echo BASE_URL . $target['short_code']; ?>" target="_blank">
                                                            <?php echo $target['short_code']; ?>
                                                        </a>
                                                    </td>
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

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap" async defer></script>

    <!-- DataTables -->
    <script src="../../plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../../plugins/datatables/dataTables.bootstrap.min.js"></script>

    <script>
        var map;
        var markers = [];

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 2,
                center: {
                    lat: 20,
                    lng: 0
                },
                mapTypeId: 'terrain'
            });

            // Add markers for each target
            <?php foreach ($targets as $target): ?>
                <?php if ($target['latitude'] && $target['longitude']): ?>
                    var marker = new google.maps.Marker({
                        position: {
                            lat: <?php echo $target['latitude']; ?>,
                            lng: <?php echo $target['longitude']; ?>
                        },
                        map: map,
                        title: '<?php echo addslashes($target['ip_address'] . ' - ' . $target['country']); ?>'
                    });

                    var infoWindow = new google.maps.InfoWindow({
                        content: '<div>' +
                            '<strong>IP:</strong> <?php echo addslashes($target['ip_address']); ?><br>' +
                            '<strong>País:</strong> <?php echo addslashes($target['country'] ?: 'Unknown'); ?><br>' +
                            '<strong>Cidade:</strong> <?php echo addslashes($target['city'] ?: 'Unknown'); ?><br>' +
                            '<strong>ISP:</strong> <?php echo addslashes($target['isp'] ?: 'Unknown'); ?><br>' +
                            '<strong>Dispositivo:</strong> <?php echo addslashes(ucfirst($target['device_type'])); ?><br>' +
                            '<strong>Link:</strong> <?php echo addslashes($target['short_code']); ?><br>' +
                            '<strong>Data:</strong> <?php echo addslashes(formatDate($target['clicked_at'])); ?>' +
                            '</div>'
                    });

                    marker.addListener('click', function() {
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                <?php endif; ?>
            <?php endforeach; ?>

            // Fit map to show all markers
            if (markers.length > 0) {
                var bounds = new google.maps.LatLngBounds();
                markers.forEach(function(marker) {
                    bounds.extend(marker.getPosition());
                });
                map.fitBounds(bounds);
            }
        }

        $(function() {
            $('#visitors-table').DataTable({
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