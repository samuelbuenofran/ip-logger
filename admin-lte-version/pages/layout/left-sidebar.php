<?php
function isActive($menu, $mode = "full")
{
  global $active_menu;
  if ($mode == "partial")
    echo ($active_menu == $menu ? "active" : "");
  else
    echo ($active_menu == $menu ? "class='active'" : "");
}
?>
<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">
    <!-- Sidebar user panel -->
    <div class="user-panel">
      <div class="pull-left image">
        <img src="../../dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
      </div>
      <div class="pull-left info">
        <p>Alexander Pierce</p>
        <a href="pages/dashboard"><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>
    <!-- search form -->
    <form action="#" method="get" class="sidebar-form">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search...">
        <span class="input-group-btn">
          <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
          </button>
        </span>
      </div>
    </form>
    <!-- /.search form -->
    <!-- sidebar menu: : style can be found in sidebar.less -->
    <ul class="sidebar-menu">
      <li class="header">IP LOGGER</li>

      <li <?php isActive("dashboard") ?>>
        <a href="../../pages/dashboard">
          <i class="fa fa-dashboard"></i> <span>Dashboard</span>
        </a>
      </li>

      <li <?php isActive("links") ?>>
        <a href="../../pages/links">
          <i class="fa fa-link"></i> <span>Meus Links</span>
          <span class="pull-right-container">
            <small class="label pull-right bg-blue" id="links-count">0</small>
          </span>
        </a>
      </li>

      <li <?php isActive("create_link") ?>>
        <a href="../../pages/create_link">
          <i class="fa fa-plus"></i> <span>Criar Link</span>
        </a>
      </li>

      <li <?php isActive("geolocation") ?>>
        <a href="../../pages/geolocation">
          <i class="fa fa-map-marker"></i> <span>Geolocalização</span>
        </a>
      </li>

      <li class="treeview">
        <a href="#">
          <i class="fa fa-cog"></i> <span>Administração</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li <?php isActive("admin") ?>>
            <a href="../../pages/admin"><i class="fa fa-circle-o"></i> Painel Admin</a>
          </li>
          <li <?php isActive("settings") ?>>
            <a href="../../pages/settings"><i class="fa fa-circle-o"></i> Configurações</a>
          </li>
        </ul>
      </li>

      <li class="header">INFORMAÇÕES</li>

      <li>
        <a href="../../pages/privacy">
          <i class="fa fa-shield"></i>
          <span>Política de Privacidade</span>
        </a>
      </li>
      <li>
        <a href="../../pages/terms">
          <i class="fa fa-file-text"></i>
          <span>Termos de Uso</span>
        </a>
      </li>
      <li>
        <a href="../../pages/cookies">
          <i class="fa fa-cookie-bite"></i>
          <span>Cookies</span>
        </a>
      </li>

    </ul>
  </section>
  <!-- /.sidebar -->
</aside>
<script>
  var parent = $("ul.sidebar-menu li.active").closest("ul").closest("li");
  if (parent[0] != undefined)
    $(parent[0]).addClass("active");
</script>