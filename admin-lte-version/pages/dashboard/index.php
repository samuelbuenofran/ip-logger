<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$active_menu = "dashboard";
include_once "../layout/header.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total_links FROM links");
$total_links = $stmt->fetch()['total_links'];

$stmt = $conn->query("SELECT COUNT(*) as total_clicks FROM targets");
$total_clicks = $stmt->fetch()['total_clicks'];

$stmt = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM targets");
$unique_visitors = $stmt->fetch()['unique_visitors'];

$stmt = $conn->query("SELECT COUNT(*) as active_links FROM links WHERE expiry_date IS NULL OR expiry_date > NOW()");
$active_links = $stmt->fetch()['active_links'];

// Get recent links
$stmt = $conn->query("
    SELECT l.*, 
           COUNT(t.id) as click_count,
           COUNT(DISTINCT t.ip_address) as unique_visitors
    FROM links l 
    LEFT JOIN targets t ON l.id = t.link_id 
    GROUP BY l.id 
    ORDER BY l.created_at DESC 
    LIMIT 5
");
$recent_links = $stmt->fetchAll();

// Get recent clicks
$stmt = $conn->query("
    SELECT t.*, l.short_code, l.original_url
    FROM targets t
    JOIN links l ON t.link_id = l.id
    ORDER BY t.clicked_at DESC
    LIMIT 10
");
$recent_clicks = $stmt->fetchAll();
?>

<body class="hold-transition skin-blue sidebar-mini">
  <!-- Put Page-level css and javascript libraries here -->

  <!-- iCheck -->
  <link rel="stylesheet" href="../../plugins/iCheck/flat/blue.css">
  <!-- Morris chart -->
  <link rel="stylesheet" href="../../plugins/morris/morris.css">
  <!-- jvectormap -->
  <link rel="stylesheet" href="../../plugins/jvectormap/jquery-jvectormap-1.2.2.css">
  <!-- Date Picker -->
  <link rel="stylesheet" href="../../plugins/datepicker/datepicker3.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="../../plugins/daterangepicker/daterangepicker.css">

  <!-- jQuery UI 1.11.4 -->
  <script src="../../plugins/jQueryUI/jquery-ui.min.js"></script>

  <!-- Morris chart -->
  <link rel="stylesheet" href="../../plugins/morris/morris.css">

  <!-- Morris.js charts -->
  <script src="../../plugins/raphael/raphael-min.js"></script>
  <script src="../../plugins/morris/morris.min.js"></script>

  <!-- Sparkline -->
  <script src="../../plugins/sparkline/jquery.sparkline.min.js"></script>

  <!-- jvectormap -->
  <script src="../../plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
  <script src="../../plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>

  <!-- jQuery Knob -->
  <script src="../../plugins/knob/jquery.knob.js"></script>

  <!-- daterangepicker -->
  <script src="../../plugins/moment/moment.min.js"></script>
  <script src="../../plugins/daterangepicker/daterangepicker.js"></script>
  <!-- datepicker -->
  <script src="../../plugins/datepicker/bootstrap-datepicker.js"></script>

  <!-- ================================================ -->

  <div class="wrapper">

    <?php include_once "../layout/topmenu.php"; ?>
    <?php include_once "../layout/left-sidebar.php"; ?>


    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">

      <!-- Content Header (Page header) -->
      <section class="content-header">
        <h1>
          Dashboard
          <small>IP Logger - Controle de Links</small>
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li class="active">Dashboard</li>
        </ol>
      </section>

      <!-- Main content -->
      <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-aqua">
              <div class="inner">
                <h3><?php echo $total_links; ?></h3>
                <p>Total de Links</p>
              </div>
              <div class="icon">
                <i class="fa fa-link"></i>
              </div>
              <a href="../links" class="small-box-footer">Mais informações <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-green">
              <div class="inner">
                <h3><?php echo $total_clicks; ?></h3>
                <p>Total de Cliques</p>
              </div>
              <div class="icon">
                <i class="fa fa-mouse-pointer"></i>
              </div>
              <a href="../geolocation" class="small-box-footer">Mais informações <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-yellow">
              <div class="inner">
                <h3><?php echo $unique_visitors; ?></h3>
                <p>Visitantes Únicos</p>
              </div>
              <div class="icon">
                <i class="fa fa-users"></i>
              </div>
              <a href="../geolocation" class="small-box-footer">Mais informações <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-red">
              <div class="inner">
                <h3><?php echo $active_links; ?></h3>
                <p>Links Ativos</p>
              </div>
              <div class="icon">
                <i class="fa fa-check-circle"></i>
              </div>
              <a href="../links" class="small-box-footer">Mais informações <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        </div>
        <!-- /.row -->

        <!-- Main row -->
        <div class="row">
          <!-- Left col -->
          <section class="col-lg-8 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
              <!-- Tabs within a box -->
              <ul class="nav nav-tabs pull-right">
                <li class="active"><a href="#revenue-chart" data-toggle="tab">Links Recentes</a></li>
                <li><a href="#sales-chart" data-toggle="tab">Cliques Recentes</a></li>
                <li class="pull-left header"><i class="fa fa-inbox"></i> Atividade Recente</li>
              </ul>
              <div class="tab-content no-padding">
                <!-- Morris chart - Sales -->
                <div class="chart tab-pane active" id="revenue-chart" style="position: relative; height: 300px;">
                  <div class="box-body">
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead>
                          <tr>
                            <th>Link</th>
                            <th>Cliques</th>
                            <th>Visitantes</th>
                            <th>Criado</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($recent_links as $link): ?>
                            <tr>
                              <td>
                                <a href="<?php echo BASE_URL . $link['short_code']; ?>" target="_blank">
                                  <?php echo $link['short_code']; ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo substr($link['original_url'], 0, 50) . '...'; ?></small>
                              </td>
                              <td><span class="badge bg-blue"><?php echo $link['click_count']; ?></span></td>
                              <td><span class="badge bg-green"><?php echo $link['unique_visitors']; ?></span></td>
                              <td><?php echo timeAgo($link['created_at']); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="chart tab-pane" id="sales-chart" style="position: relative; height: 300px;">
                  <div class="box-body">
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead>
                          <tr>
                            <th>IP</th>
                            <th>País</th>
                            <th>Dispositivo</th>
                            <th>Link</th>
                            <th>Data</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($recent_clicks as $click): ?>
                            <tr>
                              <td><?php echo $click['ip_address']; ?></td>
                              <td><?php echo $click['country'] ?: 'Unknown'; ?></td>
                              <td>
                                <span class="label label-<?php echo $click['device_type'] == 'mobile' ? 'success' : 'primary'; ?>">
                                  <?php echo ucfirst($click['device_type']); ?>
                                </span>
                              </td>
                              <td><?php echo $click['short_code']; ?></td>
                              <td><?php echo timeAgo($click['clicked_at']); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- /.nav-tabs-custom -->

          </section>
          <!-- /.Left col -->
          <!-- right col (We are only adding the ID to make the widgets sortable)-->
          <section class="col-lg-4 connectedSortable">

            <!-- Map box -->
            <div class="box box-solid bg-light-blue-gradient">
              <div class="box-header">
                <!-- tools box -->
                <div class="pull-right box-tools">
                  <button class="btn btn-primary btn-sm daterange pull-right" data-toggle="tooltip" title="Date range"><i class="fa fa-calendar"></i></button>
                  <button class="btn btn-primary btn-sm pull-right" data-widget="collapse" data-toggle="tooltip" title="Collapse" style="margin-right: 5px;"><i class="fa fa-minus"></i></button>
                </div>
                <!-- /. tools -->

                <i class="fa fa-map-marker"></i>

                <h3 class="box-title">
                  Visitantes
                </h3>
              </div>
              <div class="box-body">
                <div id="world-map" style="height: 250px; width: 100%;"></div>
              </div>
              <!-- /.box-body-->
              <div class="box-footer no-border">
                <div class="row">
                  <div class="col-xs-4 text-center" style="border-right: 1px solid #f4f4f4">
                    <div id="sparkline-1"></div>
                    <div class="knob-label">Visitors</div>
                  </div>
                  <!-- ./col -->
                  <div class="col-xs-4 text-center" style="border-right: 1px solid #f4f4f4">
                    <div id="sparkline-2"></div>
                    <div class="knob-label">Online</div>
                  </div>
                  <!-- ./col -->
                  <div class="col-xs-4 text-center">
                    <div id="sparkline-3"></div>
                    <div class="knob-label">Exists</div>
                  </div>
                  <!-- ./col -->
                </div>
                <!-- /.row -->
              </div>
            </div>
            <!-- /.box -->

            <!-- solid sales graph -->
            <div class="box box-solid bg-teal-gradient">
              <div class="box-header">
                <i class="fa fa-th"></i>

                <h3 class="box-title">Ações Rápidas</h3>

                <div class="box-tools pull-right">
                  <button class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
                  </button>
                  <button class="btn bg-teal btn-sm" data-widget="remove"><i class="fa fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="box-body border-radius-none">
                <div class="row">
                  <div class="col-md-6">
                    <a href="../create_link" class="btn btn-block btn-primary btn-lg">
                      <i class="fa fa-plus"></i> Criar Novo Link
                    </a>
                  </div>
                  <div class="col-md-6">
                    <a href="../links" class="btn btn-block btn-success btn-lg">
                      <i class="fa fa-list"></i> Ver Todos os Links
                    </a>
                  </div>
                </div>
                <br>
                <div class="row">
                  <div class="col-md-6">
                    <a href="../geolocation" class="btn btn-block btn-info btn-lg">
                      <i class="fa fa-map"></i> Ver Mapa
                    </a>
                  </div>
                  <div class="col-md-6">
                    <a href="../admin" class="btn btn-block btn-warning btn-lg">
                      <i class="fa fa-cog"></i> Administração
                    </a>
                  </div>
                </div>
              </div>
              <!-- /.box-body -->
            </div>
            <!-- /.box -->

          </section>
          <!-- right col -->
        </div>
        <!-- /.row (main row) -->

      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

  </div><!-- /.content-wrapper -->

  <?php include_once "../layout/copyright.php"; ?>
  <?php include_once "../layout/right-sidebar.php"; ?>

  <!-- /.control-sidebar -->
  <!-- Add the sidebar's background. This div must be placed
         immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
  </div><!-- ./wrapper -->

  <?php include_once "../layout/footer.php" ?>
  <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
  <script src="../../dist/js/pages/dashboard.js"></script>
  <!-- AdminLTE for demo purposes -->
  <script src="../../dist/js/demo.js"></script>

  <script>
    $(function() {
      //Initialize the jQuery UI slider
      $('.slider').slider();

      //Initialize the jQuery UI datepicker
      $('#datepicker').datepicker();

      //Initialize the jQuery UI date range picker
      $('.daterange').daterangepicker();

      //Initialize the jQuery UI knob
      $('.knob').knob();

      //Initialize the jQuery UI world map
      $('#world-map').vectorMap({
        map: 'world_mill_en',
        backgroundColor: 'transparent',
        regionStyle: {
          initial: {
            fill: '#e4e4e4',
            'fill-opacity': 1,
            stroke: 'none',
            'stroke-width': 0,
            'stroke-opacity': 1
          },
          hover: {
            'fill-opacity': 0.8,
            cursor: 'pointer'
          },
          selected: {
            fill: 'yellow'
          },
          selectedHover: {}
        },
        series: {
          regions: [{
            values: {
              'US': 298,
              'SA': 200,
              'AU': 760,
              'IN': 200,
              'BR': 200,
              'CN': 200,
              'RU': 200
            },
            scale: ['#C8EEFF', '#0071A4'],
            normalizeFunction: 'polynomial'
          }]
        },
        onRegionLabelShow: function(e, el, code) {
          if (typeof countries[code] != 'undefined')
            el.html(el.html() + ': ' + countries[code] + ' visitors');
        }
      });

      //Initialize the jQuery UI sparkline
      $('#sparkline-1').sparkline([<?php echo implode(',', array_fill(0, 10, rand(1, 100))); ?>], {
        type: 'line',
        lineColor: '#92c1dc',
        fillColor: '#ebf4f9',
        height: '50',
        width: '80'
      });

      $('#sparkline-2').sparkline([<?php echo implode(',', array_fill(0, 10, rand(1, 50))); ?>], {
        type: 'line',
        lineColor: '#92c1dc',
        fillColor: '#ebf4f9',
        height: '50',
        width: '80'
      });

      $('#sparkline-3').sparkline([<?php echo implode(',', array_fill(0, 10, rand(1, 30))); ?>], {
        type: 'line',
        lineColor: '#92c1dc',
        fillColor: '#ebf4f9',
        height: '50',
        width: '80'
      });
    });
  </script>