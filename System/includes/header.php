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
  <div class="header-wrapper">
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

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Agregar evento click a todas las notificaciones
      document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          window.location.href = 'mensajespromo.php';
        });
      });
    });
  </script>

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
    <span style="right: 8px;"><?=htmlspecialchars($usuario_username);?></span>
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
  </div>
</li>

  </ul>
</div>
 </div>
</header>