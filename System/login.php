<?php
session_start();


      include 'bd/conexion.php';
    $query_nombre = "SELECT valor FROM configuracion_sistema WHERE id = 1 LIMIT 1";
    $result_nombre = $conn->query($query_nombre);
    if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
      $nombre_sistema = htmlspecialchars($row_nombre['valor']);
    }

// Procesar el formulario de login cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Incluir la conexión a la base de datos
    include 'bd/conexion.php';

    
    // Obtener y limpiar los datos del formulario
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Consulta para verificar el usuario
    $sql = "SELECT id, usuario, email, password_hash, nombre, apellidos, rol_id, activo FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verificar si la cuenta está activa
        if ($row['activo'] != 1) {
            $login_error = "Cuenta inactiva. Contacta al administrador.";
        } else {
            // Verificar la contraseña (asumiendo que está hasheada con password_hash())
            if (password_verify($password, $row['password_hash'])) {
                // Iniciar sesión y guardar datos
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['nombre'] . ' ' . $row['apellidos'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_username'] = $row['usuario'];
                $_SESSION['user_role'] = $row['rol_id'];
                
                // Redirigir al index.php
                header("Location: index.php");
                exit();
            } else {
                $login_error = "La contraseña ingresada es incorrecta.";
            }
        }
    } else {
        $login_error = "No existe una cuenta con ese correo electrónico.";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- [Head] start -->

<head>
  <title><?php echo $nombre_sistema; ?></title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Sistema de gestión académica - Colegio San José">
  <meta name="keywords" content="Colegio San José, Sistema Académico, Login, Educación">
  <meta name="author" content="Colegio San José">

  <!-- [Favicon] icon -->
  <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon">
  <!-- [Google Font] Family -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" id="main-font-link">
  <!-- [Tabler Icons] https://tablericons.com -->
  <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css">
  <!-- [Feather Icons] https://feathericons.com -->
  <link rel="stylesheet" href="assets/fonts/feather.css">
  <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
  <link rel="stylesheet" href="assets/fonts/fontawesome.css">
  <!-- [Material Icons] https://fonts.google.com/icons -->
  <link rel="stylesheet" href="assets/fonts/material.css">
  <!-- [Template CSS Files] -->
  <link rel="stylesheet" href="assets/css/style.css" id="main-style-link">
  <link rel="stylesheet" href="assets/css/style-preset.css">

  <!-- SweetAlert2 para mensajes bonitos -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <style>
      * {
          font-family: 'Poppins', sans-serif;
      }
      
      body {
          margin: 0;
          padding: 0;
          overflow-x: hidden;
      }
      
      .auth-main {
          min-height: 100vh;
          background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 25%, #3b82f6 50%, #f59e0b 75%, #d97706 100%);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 20px;
          position: relative;
      }
      
      .auth-main::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
          opacity: 0.3;
      }
      
      .auth-wrapper {
          width: 100%;
          max-width: 450px;
          position: relative;
          z-index: 2;
      }
      
      .auth-header {
          text-align: center;
          margin-bottom: 3rem;
          position: relative;
      }
      
      .logo-container {
          background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
          border-radius: 25px;
          padding: 30px;
          box-shadow: 
              0 20px 40px rgba(0,0,0,0.15),
              0 10px 20px rgba(0,0,0,0.1),
              inset 0 1px 0 rgba(255,255,255,0.8);
          display: inline-block;
          margin-bottom: 1.5rem;
          transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
          position: relative;
          border: 3px solid rgba(251, 191, 36, 0.3);
      }
      
      .logo-container::before {
          content: '';
          position: absolute;
          top: -3px;
          left: -3px;
          right: -3px;
          bottom: -3px;
          background: linear-gradient(45deg, #fbbf24, #f59e0b, #d97706, #92400e);
          border-radius: 28px;
          z-index: -1;
          opacity: 0;
          transition: opacity 0.3s ease;
      }
      
      .logo-container:hover::before {
          opacity: 1;
      }
      
      .logo-container:hover {
          transform: translateY(-8px) scale(1.05);
          box-shadow: 
              0 30px 60px rgba(0,0,0,0.2),
              0 15px 30px rgba(0,0,0,0.15);
      }
      
      .logo {
          width: 120px;
          height: 120px;
          background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #1e3a8a 100%);
          border-radius: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #fbbf24;
          font-size: 32px;
          font-weight: 800;
          text-decoration: none;
          position: relative;
          overflow: hidden;
          border: 2px solid #fbbf24;
          box-shadow: inset 0 2px 4px rgba(255,255,255,0.1);
      }
      
      .logo::before {
          content: '⚜️';
          position: absolute;
          font-size: 28px;
          animation: pulse 2s infinite;
      }
      
      @keyframes pulse {
          0%, 100% { transform: scale(1); opacity: 1; }
          50% { transform: scale(1.1); opacity: 0.8; }
      }
      
      .school-info {
          color: white;
          text-align: center;
          text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
      }
      
      .school-name {
          font-size: 28px;
          font-weight: 800;
          margin-bottom: 5px;
          background: linear-gradient(45deg, #fbbf24, #f59e0b);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          text-shadow: none;
      }
      
      .school-subtitle {
          font-size: 16px;
          font-weight: 500;
          opacity: 0.9;
          margin-bottom: 0;
      }
      
      .card {
          border: none;
          border-radius: 25px;
          box-shadow: 
              0 25px 50px rgba(0,0,0,0.15),
              0 12px 24px rgba(0,0,0,0.1);
          backdrop-filter: blur(20px);
          background: rgba(255,255,255,0.95);
          border: 1px solid rgba(255,255,255,0.2);
          overflow: hidden;
          position: relative;
      }
      
      .card::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 4px;
          background: linear-gradient(90deg, #fbbf24, #f59e0b, #d97706, #fbbf24);
          background-size: 200% 100%;
          animation: gradientShift 3s ease infinite;
      }
      
      @keyframes gradientShift {
          0%, 100% { background-position: 0% 50%; }
          50% { background-position: 100% 50%; }
      }
      
      .card-body {
          padding: 3rem 2.5rem 2.5rem;
      }
      
      .login-title {
          color: #1e40af;
          font-weight: 700;
          font-size: 24px;
          margin-bottom: 2rem;
          text-align: center;
      }
      
      .form-label {
          color: #1e40af;
          font-weight: 600;
          margin-bottom: 8px;
      }
      
      .form-control {
          border-radius: 15px;
          border: 2px solid #e2e8f0;
          padding: 15px 20px;
          transition: all 0.3s ease;
          font-size: 16px;
          background: rgba(248, 250, 252, 0.8);
      }
      
      .form-control:focus {
          border-color: #fbbf24;
          box-shadow: 0 0 0 0.25rem rgba(251, 191, 36, 0.25);
          background: white;
          transform: translateY(-2px);
      }
      
      .btn-primary {
          background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
          border: none;
          border-radius: 15px;
          padding: 15px;
          font-weight: 700;
          font-size: 16px;
          transition: all 0.3s ease;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          position: relative;
          overflow: hidden;
      }
      
      .btn-primary::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
          transition: left 0.5s;
      }
      
      .btn-primary:hover::before {
          left: 100%;
      }
      
      .btn-primary:hover {
          transform: translateY(-3px);
          box-shadow: 0 10px 25px rgba(30, 64, 175, 0.4);
          background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
      }
      
      .btn-social {
          border-radius: 12px;
          border: 2px solid #e2e8f0;
          transition: all 0.3s ease;
          padding: 10px;
          background: white;
      }
      
      .btn-social:hover {
          border-color: #fbbf24;
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }
      
      .social-section {
          margin-top: 2rem;
          padding-top: 2rem;
          border-top: 1px solid #e2e8f0;
      }
      
      .social-title {
          text-align: center;
          color: #64748b;
          font-size: 14px;
          margin-bottom: 1rem;
          position: relative;
      }
      
      .social-title::before,
      .social-title::after {
          content: '';
          position: absolute;
          top: 50%;
          width: 40px;
          height: 1px;
          background: #e2e8f0;
      }
      
      .social-title::before {
          left: 0;
      }
      
      .social-title::after {
          right: 0;
      }
      
      .auth-footer {
          text-align: center;
          margin-top: 2rem;
          color: rgba(255,255,255,0.9);
          font-size: 14px;
      }
      
      .auth-footer a {
          color: #fbbf24;
          text-decoration: none;
          font-weight: 500;
          transition: color 0.3s ease;
      }
      
      .auth-footer a:hover {
          color: #f59e0b;
          text-decoration: underline;
      }
      
      .form-check-input:checked {
          background-color: #1e40af;
          border-color: #1e40af;
      }
      
      .form-check-label {
          color: #64748b;
          font-size: 14px;
      }
      
      .alert-danger {
          border-radius: 12px;
          border: none;
          background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
          color: #dc2626;
          border-left: 4px solid #dc2626;
      }
      
      @media (max-width: 576px) {
          .auth-main {
              padding: 15px;
          }
          
          .card-body {
              padding: 2rem 1.5rem;
          }
          
          .logo {
              width: 100px;
              height: 100px;
              font-size: 28px;
          }
          
          .school-name {
              font-size: 24px;
          }
          
          .school-subtitle {
              font-size: 14px;
          }
          
          .logo-container {
              padding: 25px;
          }
      }
      
      /* Animaciones adicionales */
      .card {
          animation: fadeInUp 0.8s ease-out;
      }
      
      .auth-header {
          animation: fadeInDown 0.8s ease-out;
      }
      
      @keyframes fadeInUp {
          from {
              opacity: 0;
              transform: translateY(30px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }
      
      @keyframes fadeInDown {
          from {
              opacity: 0;
              transform: translateY(-30px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }
  </style>
</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body>
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg">
    <div class="loader-track">
      <div class="loader-fill"></div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->

  <div class="auth-main">
    <div class="auth-wrapper">
      <div class="auth-header">
        <div class="logo-container">
          <div class="logo">
            <img src="assets/images/logocoelgio.jpeg" alt="Logo Colegio San José" style="width: 90px; height: 90px; object-fit: contain;">
          </div>
        </div>
        <div class="school-info">
          <h1 class="school-name"><?php echo $nombre_sistema; ?></h1>
          <p class="school-subtitle">Sistema de Gestión Académica</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <?php if (!empty($login_error)): ?>
          <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $login_error; ?>
          </div>
          <?php endif; ?>
          
          <h3 class="login-title">Iniciar Sesión</h3>
          
          <form id="loginForm" method="POST" action="">
            <div class="form-group mb-3">
              <label class="form-label">
                <i class="fas fa-envelope me-2"></i>Correo Electrónico
              </label>
              <input type="email" class="form-control" name="email" placeholder="Ingresa tu correo electrónico" required>
            </div>
            
            <div class="form-group mb-4">
              <label class="form-label">
                <i class="fas fa-lock me-2"></i>Contraseña
              </label>
              <input type="password" class="form-control" name="password" placeholder="Ingresa tu contraseña" required>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="customCheckc1" name="remember">
                <label class="form-check-label" for="customCheckc1">
                  Recordarme
                </label>
              </div>
              <a href="#" class="text-decoration-none" style="color: #fbbf24; font-weight: 500;">
                ¿Olvidaste tu contraseña?
              </a>
            </div>
            
            <div class="d-grid">
              <button type="submit" class="btn btn-primary" name="login">
                <i class="fas fa-sign-in-alt me-2"></i>Acceder
              </button>
            </div>
          </form>
          
          <div class="social-section">
            <div class="social-title">O continúa con</div>
            <div class="row g-2">
              <div class="col-4">
                <button type="button" class="btn btn-social w-100">
                  <img src="assets/images/authentication/google.svg" alt="Google" width="20">
                </button>
              </div>
              <div class="col-4">
                <button type="button" class="btn btn-social w-100">
                  <img src="assets/images/authentication/facebook.svg" alt="Facebook" width="20">
                </button>
              </div>
              <div class="col-4">
                <button type="button" class="btn btn-social w-100">
                  <img src="assets/images/authentication/twitter.svg" alt="Twitter" width="20">
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="auth-footer">
        <div class="row align-items-center">
          <div class="col">
            <p class="m-0">© 2024 <a href="#">Colegio San José</a> - Todos los derechos reservados</p>
          </div>
          <div class="col-auto">
            <div class="d-flex gap-3">
              <a href="#">Política de Privacidad</a>
              <a href="#">Términos de Uso</a>
              <a href="#">Soporte</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Required Js -->
  <script src="assets/js/plugins/popper.min.js"></script>
  <script src="assets/js/plugins/simplebar.min.js"></script>
  <script src="assets/js/plugins/bootstrap.min.js"></script>
  <script src="assets/js/fonts/custom-font.js"></script>
  <script src="assets/js/pcoded.js"></script>
  <script src="assets/js/plugins/feather.min.js"></script>
  
  <!-- SweetAlert2 para mensajes bonitos -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  
  <script>
    layout_change('light');
    change_box_container('false');
    layout_rtl_change('false');
    preset_change("preset-1");
    font_change("Poppins");
    
    // Animación suave para los campos de entrada
    document.querySelectorAll('.form-control').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
      });
    });
    
    // Efecto de ripple en el botón
    document.querySelector('.btn-primary').addEventListener('click', function(e) {
      let ripple = document.createElement('span');
      let rect = this.getBoundingClientRect();
      let size = Math.max(rect.width, rect.height);
      let x = e.clientX - rect.left - size / 2;
      let y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      ripple.style.position = 'absolute';
      ripple.style.borderRadius = '50%';
      ripple.style.background = 'rgba(255,255,255,0.6)';
      ripple.style.transform = 'scale(0)';
      ripple.style.animation = 'ripple 0.6s linear';
      ripple.style.pointerEvents = 'none';
      
      this.appendChild(ripple);
      
      setTimeout(() => {
        ripple.remove();
      }, 600);
    });
    
    // Agregar keyframes para el efecto ripple
    const style = document.createElement('style');
    style.textContent = `
      @keyframes ripple {
        to {
          transform: scale(4);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
<!-- [Body] end -->
</html>