<?php


// Obtener datos del usuario desde la sesión
$usuario_nombre = $_SESSION['user_name'] ?? 'Invitado';
$usuario_email = $_SESSION['user_email'] ?? '';
$usuario_id = $_SESSION['user_id'] ?? null;
$usuario_username = $_SESSION['user_username'] ?? '';
?>

<style>
  .header-notification-scroll {
    max-height: calc(100vh - 215px);
    overflow-y: auto;
}

</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont/tabler-icons.min.css">

<header class="pc-header">
  <div class="header-wrapper"> <!-- [Mobile Media Block] start -->
<div class="me-auto pc-mob-drp">
  <ul class="list-unstyled">
    <!-- ======= Menu collapse Icon ===== -->
    <li class="pc-h-item pc-sidebar-collapse">
      <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
        <i class="ti ti-menu-2"></i>
      </a>
    </li>
    <li class="pc-h-item pc-sidebar-popup">
      <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
        <i class="ti ti-menu-2"></i>
      </a>
    </li>
  </ul>
</div>
<!-- [Mobile Media Block end] -->
<div class="ms-auto">
  <ul class="list-unstyled">
    <li class="dropdown pc-h-item pc-mega-menu">
      <a
        class="pc-head-link dropdown-toggle arrow-none me-0"
        data-bs-toggle="dropdown"
        href="#"
        role="button"
        aria-haspopup="false"
        aria-expanded="false"
      >
        <i class="ti ti-layout-grid"></i>
      </a>
      <div class="dropdown-menu pc-h-dropdown pc-mega-dmenu">
        <div class="row g-0">
          <div class="col image-block">
            <h2 class="text-white">Explore Components</h2>
            <p class="text-white my-4">Try our pre made component pages to check how it feels and suits as per your need.</p>
            <div class="row align-items-end">
              <div class="col-auto">
                <div class="btn btn btn-light">View All <i class="ti ti-arrow-narrow-right"></i></div>
              </div>
              <div class="col">
                <img src="assets/images/mega-menu/chart.svg" alt="image" class="img-fluid img-charts">
              </div>
            </div>
          </div>
          <div class="col">
            <h6 class="mega-title">UI Components</h6>
            <ul class="pc-mega-list">
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Alerts</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Accordions</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Avatars</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Badges</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Breadcrumbs</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Button</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Buttons Groups</a></li
              >
            </ul>
          </div>
          <div class="col">
            <h6 class="mega-title">UI Components</h6>
            <ul class="pc-mega-list">
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Menus</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Media Sliders / Carousel</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Modals</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Pagination</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Progress Bars &amp; Graphs</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Search Bar</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Tabs</a></li
              >
            </ul>
          </div>
          <div class="col">
            <h6 class="mega-title">Advance Components</h6>
            <ul class="pc-mega-list">
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Advanced Stats</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Advanced Cards</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Lightbox</a></li
              >
              <li
                ><a href="#!" class="dropdown-item"><i class="ti ti-circle"></i> Notification</a></li
              >
            </ul>
          </div>
        </div>
      </div>
    </li>
    <li class="dropdown pc-h-item">
      <a
        class="pc-head-link dropdown-toggle arrow-none me-0"
        data-bs-toggle="dropdown"
        href="#"
        role="button"
        aria-haspopup="false"
        aria-expanded="false"
      >
        <i class="ti ti-language"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
        <a href="#!" class="dropdown-item">
          <i class="ti ti-user"></i>
          <span>My Account</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-settings"></i>
          <span>Settings</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-headset"></i>
          <span>Support</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-lock"></i>
          <span>Lock Screen</span>
        </a>
        <a href="includes/logout.php" class="dropdown-item">
          <i class="ti ti-power"></i>
          <span>Logout</span>
        </a>
      </div>
    </li>
  <li class="dropdown pc-h-item">
  <a
      class="pc-head-link dropdown-toggle arrow-none me-0 position-relative"
      data-bs-toggle="dropdown"
      href="#"
      role="button"
      aria-haspopup="false"
      aria-expanded="false"
  >
      <i class="ti ti-bell"></i>
      <?php
      // Calcular total de notificaciones pendientes
      $sql_count_registros = "SELECT COUNT(*) AS total FROM registros WHERE estado = 'Pendiente'";
      $sql_count_consultas = "SELECT COUNT(*) AS total FROM consultas WHERE estado = 'Pendiente'";
      $count_registros = $conn->query($sql_count_registros)->fetch_assoc()['total'];
      $count_consultas = $conn->query($sql_count_consultas)->fetch_assoc()['total'];
      $total_notificaciones = $count_registros + $count_consultas;

      if ($total_notificaciones > 0): ?>
        <span class="badge bg-danger pc-h-badge" id="contador-notificaciones">
          <?= $total_notificaciones ?>
        </span>
      <?php endif; ?>
  </a>

  <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown">
      <div class="dropdown-header d-flex align-items-center justify-content-between">
          <h5 class="m-0">Notificaciones para atender</h5>
          <a href="#!" class="pc-head-link bg-transparent" onclick="marcarNotificacionesLeidas()">
              <i class="ti ti-circle-check text-success"></i>
          </a>
      </div>
      <div class="dropdown-divider"></div>

      <!-- CONTENEDOR CON SCROLL -->
      <div class="px-0 text-wrap position-relative" 
           style="max-height: 300px; overflow-y: auto; overflow-x: hidden;">
          <div class="list-group list-group-flush w-100" id="lista-notificaciones">
              <?php
              // Establecer zona horaria global a Lima
              date_default_timezone_set('America/Lima');

              // Función para calcular tiempo transcurrido
              function tiempo_transcurrido($fecha) {
                  $fecha_obj = new DateTime($fecha);
                  $ahora = new DateTime('now');

                  $diferencia = $ahora->getTimestamp() - $fecha_obj->getTimestamp();

                  if ($diferencia < 60) {
                      return "Hace " . $diferencia . " segundo" . ($diferencia != 1 ? "s" : "");
                  } elseif ($diferencia < 3600) {
                      $minutos = floor($diferencia / 60);
                      return "Hace " . $minutos . " minuto" . ($minutos != 1 ? "s" : "");
                  } elseif ($diferencia < 86400) {
                      $horas = floor($diferencia / 3600);
                      return "Hace " . $horas . " hora" . ($horas != 1 ? "s" : "");
                  } else {
                      $dias = floor($diferencia / 86400);
                      return "Hace " . $dias . " día" . ($dias != 1 ? "s" : "");
                  }
              }

              // Obtener últimos registros pendientes
              $sql_ultimos = "SELECT id, nombre, fecha_registro, 'registro' as tipo 
                              FROM registros 
                              WHERE estado = 'Pendiente' 
                              ORDER BY fecha_registro DESC 
                              LIMIT 10";

              $sql_ultimos_consultas = "SELECT id, nombre, fecha, 'consulta' as tipo 
                                        FROM consultas 
                                        WHERE estado = 'Pendiente' 
                                        ORDER BY fecha DESC 
                                        LIMIT 10";

              $result_ultimos = $conn->query($sql_ultimos);
              $notificaciones = [];

              while($row = $result_ultimos->fetch_assoc()) {
                  $notificaciones[] = $row;
              }

              $result_ultimos_consultas = $conn->query($sql_ultimos_consultas);
              while($row = $result_ultimos_consultas->fetch_assoc()) {
                  $notificaciones[] = $row;
              }

              // Ordenar por fecha (más recientes primero)
              usort($notificaciones, function($a, $b) {
                  $fechaA = ($a['tipo'] == 'registro') ? $a['fecha_registro'] : $a['fecha'];
                  $fechaB = ($b['tipo'] == 'registro') ? $b['fecha_registro'] : $b['fecha'];
                  return strtotime($fechaB) - strtotime($fechaA);
              });

              if (count($notificaciones) > 0) {
                  foreach($notificaciones as $notif) {
                      $tipo = $notif['tipo'];
                      $nombre = htmlspecialchars($notif['nombre']);
                      $fecha = ($tipo == 'registro') ? $notif['fecha_registro'] : $notif['fecha'];

                      $tiempo = tiempo_transcurrido($fecha);

                      $icono = ($tipo == 'registro') ? 'ti ti-user' : 'ti ti-message-circle';
                      $color = ($tipo == 'registro') ? 'bg-light-primary' : 'bg-light-info';
                      $mensaje = ($tipo == 'registro') ? "Nuevo registro de <b>$nombre</b>" : "Nueva consulta de <b>$nombre</b>";

                      echo '<a class="list-group-item list-group-item-action">';
                      echo '  <div class="d-flex">';
                      echo '    <div class="flex-shrink-0">';
                      echo '      <div class="user-avtar ' . $color . '"><i class="' . $icono . '"></i></div>';
                      echo '    </div>';
                      echo '    <div class="flex-grow-1 ms-1">';
                      echo '      <span class="float-end text-muted">' . date('H:i', strtotime($fecha)) . '</span>';
                      echo '      <p class="text-body mb-1">' . $mensaje . '</p>';
                      echo '      <span class="text-muted">' . $tiempo . '</span>';
                      echo '    </div>';
                      echo '  </div>';
                      echo '</a>';
                  }
              } else {
                  echo '<div class="text-center p-3">';
                  echo '  <i class="ti ti-bell-off" style="font-size: 2rem; color: #dee2e6;"></i>';
                  echo '  <p class="text-muted mt-2">No hay notificaciones nuevas</p>';
                  echo '</div>';
              }
              ?>
          </div>
      </div>
  </div>
</li>

  
</div>
    </li>
    <li class="dropdown pc-h-item">
      <a class="pc-head-link me-0" href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_pc_layout">
        <i class="ti ti-settings"></i>
      </a>
    </li>
 
<li class="dropdown pc-h-item header-user-profile">
  <a
    class="pc-head-link dropdown-toggle arrow-none me-0"
    data-bs-toggle="dropdown"
    href="#"
    role="button"
    aria-haspopup="false"
    data-bs-auto-close="outside"
    aria-expanded="false"
  >
    <!-- Ícono en vez de imagen -->
    <i class="ti ti-user-circle user-avtar"></i>
    <span><?= htmlspecialchars($usuario_username); ?></span>
  </a>
  <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
    <div class="dropdown-header">
      <div class="d-flex mb-1">
        <div class="flex-shrink-0">
          <!-- Ícono en lugar de imagen -->
<i class="ti ti-user-circle" style="font-size: 48px;"></i>
        </div>
        <div class="flex-grow-1 ms-3">
          <h6 class="mb-1"><?= htmlspecialchars($usuario_nombre); ?></h6>
          <span><?= htmlspecialchars($usuario_email); ?></span>
        </div>
        <a href="includes/logout.php" class="pc-head-link bg-transparent">
          <i class="ti ti-power text-danger"></i>
        </a>
      </div>
    </div>
    <ul class="nav drp-tabs nav-fill nav-tabs" id="mydrpTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button
          class="nav-link active"
          id="drp-t1"
          data-bs-toggle="tab"
          data-bs-target="#drp-tab-1"
          type="button"
          role="tab"
          aria-controls="drp-tab-1"
          aria-selected="true"
        ><i class="ti ti-user"></i> Profile</button>
      </li>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link"
          id="drp-t2"
          data-bs-toggle="tab"
          data-bs-target="#drp-tab-2"
          type="button"
          role="tab"
          aria-controls="drp-tab-2"
          aria-selected="false"
        ><i class="ti ti-settings"></i> Setting</button>
      </li>
    </ul>
    <div class="tab-content" id="mysrpTabContent">
      <div class="tab-pane fade show active" id="drp-tab-1" role="tabpanel" aria-labelledby="drp-t1" tabindex="0">
        <a href="#!" class="dropdown-item">
          <i class="ti ti-edit-circle"></i>
          <span>Edit Profile</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-user"></i>
          <span>View Profile</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-clipboard-list"></i>
          <span>Social Profile</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-wallet"></i>
          <span>Billing</span>
        </a>
        <a href="includes/logout.php" class="dropdown-item">
          <i class="ti ti-power"></i>
          <span>Logout</span>
        </a>
      </div>
      <div class="tab-pane fade" id="drp-tab-2" role="tabpanel" aria-labelledby="drp-t2" tabindex="0">
        <a href="#!" class="dropdown-item">
          <i class="ti ti-help"></i>
          <span>Support</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-user"></i>
          <span>Account Settings</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-lock"></i>
          <span>Privacy Center</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-messages"></i>
          <span>Feedback</span>
        </a>
        <a href="#!" class="dropdown-item">
          <i class="ti ti-list"></i>
          <span>History</span>
        </a>
      </div>
    </div>
  </div>
</li>

  </ul>
</div>
 </div>
</header>