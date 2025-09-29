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
<head>
    <title><?php echo $nombre_sistema; ?> - Acceso</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Sistema de gestión académica - Colegio San José">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
  <style>
      :root {
          --primary-color: #2563eb;
          --primary-dark: #1d4ed8;
          --primary-light: #3b82f6;
          --accent-color: #f59e0b;
          --accent-light: #fbbf24;
          --text-primary: #111827;
          --text-secondary: #374151; /* más visible en fondo claro */
          --text-light: #6b7280; 
          --bg-primary: #ffffff;
          --bg-secondary: #f9fafb;
          --bg-glass: rgba(255, 255, 255, 0.7); /* más sólido para mejor contraste */
          --border-color: #e5e7eb;
          --shadow-light: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
          --shadow-medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
          --shadow-large: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
          --radius-sm: 8px;
          --radius-md: 12px;
          --radius-lg: 16px;
          --radius-xl: 24px;
      }

      * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
      }

      body {
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
          line-height: 1.5;
          color: var(--text-primary);
          background: linear-gradient(135deg, #ffffffff 0%, #ffffffff 100%);
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 2rem 1rem;
          position: relative;
          overflow-x: hidden;
      }

      /* Ajuste: el fondo animado ahora con tonos suaves para que no se pierda en blanco */
      body::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: 
              radial-gradient(circle at 20% 80%, rgba(37, 99, 235, 0.12) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.15) 0%, transparent 50%),
              radial-gradient(circle at 40% 40%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
          animation: backgroundShift 20s ease-in-out infinite;
      }

      @keyframes backgroundShift {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.85; }
      }

      .login-container {
          width: 100%;
          max-width: 420px;
          position: relative;
          z-index: 10;
      }

      .login-card {
          background: var(--bg-glass);
          backdrop-filter: blur(18px);
          border: 1px solid rgba(0, 0, 0, 0.08);
          border-radius: var(--radius-xl);
          padding: 3rem 2rem 2rem;
          box-shadow: var(--shadow-large);
          position: relative;
          overflow: hidden;
          animation: slideUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      }

      .login-card::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 1px;
          background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
      }

      @keyframes slideUp {
          from { opacity: 0; transform: translateY(30px); }
          to { opacity: 1; transform: translateY(0); }
      }

      .logo-section {
          text-align: center;
          margin-bottom: 2.5rem;
      }

      .logo {
          width: 80px;
          height: 80px;
          margin: 0 auto 1.5rem;
          background: var(--bg-secondary);
          border-radius: var(--radius-lg);
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: var(--shadow-medium);
          transition: transform 0.3s ease;
      }

      .logo:hover { transform: scale(1.05); }

      .logo img {
          width: 60px;
          height: 60px;
          object-fit: contain;
          border-radius: var(--radius-sm);
      }

      .school-name {
          font-size: 1.5rem;
          font-weight: 700;
          color: var(--text-primary);
          margin-bottom: 0.5rem;
      }

      .school-subtitle {
          font-size: 0.875rem;
          color: var(--text-secondary);
          font-weight: 500;
      }

      .form-header {
          text-align: center;
          margin-bottom: 2rem;
      }

      .form-title {
          font-size: 1.75rem;
          font-weight: 700;
          color: var(--text-primary);
          margin-bottom: 0.5rem;
      }

      .form-subtitle {
          font-size: 0.875rem;
          color: var(--text-secondary);
          font-weight: 400;
      }

      .form-group {
          margin-bottom: 1.5rem;
          position: relative;
      }

      .form-label {
          display: block;
          font-size: 0.875rem;
          font-weight: 600;
          color: var(--text-primary);
          margin-bottom: 0.75rem;
          letter-spacing: 0.025em;
      }

      .input-wrapper { position: relative; }

      .input-icon {
          position: absolute;
          left: 1rem;
          top: 50%;
          transform: translateY(-50%);
          color: var(--text-light);
          font-size: 1.125rem;
          transition: color 0.3s ease;
          z-index: 5;
      }

      .form-input {
          width: 100%;
          padding: 1rem 1rem 1rem 3rem;
          border: 2px solid #d1d5db;
          border-radius: var(--radius-md);
          background: #ffffffcc;
          font-size: 1rem;
          color: var(--text-primary);
          transition: all 0.3s ease;
          outline: none;
      }

      .form-input::placeholder { color: #9ca3af; }

      .form-input:focus {
          border-color: var(--primary-light);
          background: #fff;
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(37, 99, 235, 0.2);
      }

      .form-input:focus + .input-icon {
          color: var(--primary-light);
      }

      .form-options {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 2rem;
          font-size: 0.875rem;
      }

      .checkbox-wrapper {
          display: flex;
          align-items: center;
          gap: 0.5rem;
      }

      .checkbox {
          width: 1.125rem;
          height: 1.125rem;
          border: 2px solid #9ca3af;
          border-radius: 4px;
          background: white;
          cursor: pointer;
          position: relative;
          transition: all 0.3s ease;
      }

      .checkbox:checked {
          background: var(--primary-light);
          border-color: var(--primary-light);
      }

      .checkbox:checked::after {
          content: '✓';
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          color: white;
          font-size: 0.75rem;
          font-weight: bold;
      }

      .checkbox-label {
          color: var(--text-secondary);
          cursor: pointer;
          user-select: none;
      }

      .forgot-link {
          color: var(--primary-light);
          text-decoration: none;
          font-weight: 500;
          transition: color 0.3s ease;
      }

      .forgot-link:hover { color: var(--primary-dark); }

      .login-button {
          width: 100%;
          padding: 1rem 2rem;
          background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
          border: none;
          border-radius: var(--radius-md);
          color: white;
          font-size: 1rem;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.3s ease;
          position: relative;
          overflow: hidden;
          letter-spacing: 0.025em;
          margin-bottom: 1.5rem;
      }

      .login-button::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
          transition: left 0.6s ease;
      }

      .login-button:hover::before { left: 100%; }
      .login-button:hover {
          transform: translateY(-2px);
          box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4);
      }
      .login-button:active { transform: translateY(0); }
      .login-button:disabled { opacity: 0.6; cursor: not-allowed; }

      .divider {
          text-align: center;
          margin: 1.5rem 0;
          position: relative;
          color: var(--text-light);
          font-size: 0.875rem;
      }

      .divider::before {
          content: '';
          position: absolute;
          top: 50%;
          left: 0;
          right: 0;
          height: 1px;
          background: #d1d5db;
      }

      .divider span {
          background: var(--bg-glass);
          padding: 0 1rem;
          position: relative;
          z-index: 1;
      }

      .social-login {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 0.75rem;
          margin-bottom: 2rem;
      }

      .social-button {
          padding: 0.75rem;
          background: #f3f4f6;
          border: 1px solid #e5e7eb;
          border-radius: var(--radius-md);
          color: var(--text-primary);
          text-decoration: none;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
      }

      .social-button:hover {
          background: #e5e7eb;
          transform: translateY(-2px);
      }

      .footer {
          text-align: center;
          font-size: 0.75rem;
          color: var(--text-secondary);
      }

      .footer a {
          color: var(--primary-light);
          text-decoration: none;
      }

      .footer a:hover { text-decoration: underline; }

      .error-alert {
          background: #fee2e2;
          border: 1px solid #fca5a5;
          border-radius: var(--radius-md);
          padding: 1rem;
          margin-bottom: 1.5rem;
          color: #b91c1c;
          font-size: 0.875rem;
          display: flex;
          align-items: center;
          gap: 0.75rem;
          animation: shake 0.5s ease-in-out;
      }

      @keyframes shake {
          0%, 100% { transform: translateX(0); }
          25% { transform: translateX(-5px); }
          75% { transform: translateX(5px); }
      }

      .loading {
          display: inline-block;
          width: 20px;
          height: 20px;
          border: 2px solid #d1d5db;
          border-radius: 50%;
          border-top: 2px solid var(--primary-light);
          animation: spin 1s linear infinite;
      }

      @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

      @media (max-width: 480px) {
          .login-card { padding: 2rem 1.5rem 1.5rem; }
          .logo { width: 70px; height: 70px; }
          .logo img { width: 50px; height: 50px; }
          .school-name { font-size: 1.25rem; }
          .form-title { font-size: 1.5rem; }
      }

      @media (prefers-reduced-motion: reduce) {
          *, *::before, *::after {
              animation-duration: 0.01ms !important;
              animation-iteration-count: 1 !important;
              transition-duration: 0.01ms !important;
          }
      }

      .form-input:focus,
      .login-button:focus,
      .social-button:focus,
      .checkbox:focus {
          outline: 2px solid var(--primary-light);
          outline-offset: 2px;
      }
  </style>

</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo">
                    <img src="assets/images/logocoelgio.jpeg" alt="Logo <?php echo $nombre_sistema; ?>">
                </div>
                <h1 class="school-name"><?php echo $nombre_sistema; ?></h1>
                <p class="school-subtitle">Sistema de Gestión Académica</p>
            </div>

            <div class="form-header">
                <h2 class="form-title">Bienvenido</h2>
                <p class="form-subtitle">Inicia sesión para acceder a tu cuenta</p>
            </div>

            <?php if (!empty($login_error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $login_error; ?></span>
            </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            class="form-input" 
                            placeholder="tu@correo.com"
                            required
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-input" 
                            placeholder="Tu contraseña"
                            required
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="remember" name="remember" class="checkbox">
                        <label for="remember" class="checkbox-label">Recordarme</label>
                    </div>
                    <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" name="login" class="login-button">
                    <span class="btn-text">Iniciar Sesión</span>
                </button>
            </form>

            <div class="footer">
                <p>&copy; 2025 <?php echo $nombre_sistema; ?>. Todos los derechos reservados.</p>
                <p>
                    <a href="#">Política de Privacidad</a> • 
                    <a href="#">Términos de Servicio</a> • 
                    <a href="#">Soporte</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const inputs = document.querySelectorAll('.form-input');
            const loginBtn = document.getElementById('loginBtn');
            
            // Enhanced input interactions
            inputs.forEach(input => {
                const wrapper = input.closest('.input-wrapper');
                const icon = wrapper.querySelector('.input-icon');
                
                input.addEventListener('focus', function() {
                    wrapper.style.transform = 'translateY(-2px)';
                    icon.style.color = 'var(--accent-light)';
                });
                
                input.addEventListener('blur', function() {
                    wrapper.style.transform = 'translateY(0)';
                    if (!input.value) {
                        icon.style.color = 'var(--text-light)';
                    }
                });
                
                // Real-time validation
                input.addEventListener('input', function() {
                    validateField(input);
                });
            });
            
            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email');
                const password = document.getElementById('password');
                
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }
                
                // Show loading state
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<div class="loading"></div> Iniciando sesión...';
            });
            
            // Field validation
            function validateField(field) {
                const wrapper = field.closest('.form-group');
                let existingError = wrapper.querySelector('.field-error');
                
                if (existingError) {
                    existingError.remove();
                }
                
                let isValid = true;
                let errorMessage = '';
                
                if (field.type === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (field.value && !emailRegex.test(field.value)) {
                        isValid = false;
                        errorMessage = 'Ingresa un correo electrónico válido';
                    }
                } else if (field.type === 'password') {
                    if (field.value && field.value.length < 5) {
                        isValid = false;
                        errorMessage = 'La contraseña debe tener al menos 5 caracteres';
                    }
                }
                
                if (!isValid) {
                    const error = document.createElement('div');
                    error.className = 'field-error';
                    error.style.cssText = `
                        color: #fca5a5;
                        font-size: 0.75rem;
                        margin-top: 0.5rem;
                        animation: fadeIn 0.3s ease;
                    `;
                    error.textContent = errorMessage;
                    wrapper.appendChild(error);
                    
                    field.style.borderColor = 'rgba(220, 38, 38, 0.5)';
                } else {
                    field.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                }
                
                return isValid;
            }
            
            function validateForm() {
                const email = document.getElementById('email');
                const password = document.getElementById('password');
                
                const emailValid = validateField(email);
                const passwordValid = validateField(password);
                
                if (!email.value) {
                    showFieldError(email, 'El correo electrónico es requerido');
                    return false;
                }
                
                if (!password.value) {
                    showFieldError(password, 'La contraseña es requerida');
                    return false;
                }
                
                return emailValid && passwordValid;
            }
            
            function showFieldError(field, message) {
                const wrapper = field.closest('.form-group');
                let existingError = wrapper.querySelector('.field-error');
                
                if (existingError) {
                    existingError.remove();
                }
                
                const error = document.createElement('div');
                error.className = 'field-error';
                error.style.cssText = `
                    color: #fca5a5;
                    font-size: 0.75rem;
                    margin-top: 0.5rem;
                    animation: fadeIn 0.3s ease;
                `;
                error.textContent = message;
                wrapper.appendChild(error);
                
                field.style.borderColor = 'rgba(220, 38, 38, 0.5)';
                field.focus();
            }
            
            // Keyboard navigation for social buttons
            document.querySelectorAll('.social-button').forEach(btn => {
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // Enhanced checkbox interaction
            const checkbox = document.getElementById('remember');
            checkbox.addEventListener('change', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });
        
        // Add fadeIn animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
        
        // Show success message if needed
        <?php if (isset($_SESSION['login_success'])): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Bienvenido!',
            text: 'Has iniciado sesión correctamente',
            timer: 2000,
            showConfirmButton: false
        });
        <?php unset($_SESSION['login_success']); ?>
        <?php endif; ?>
    </script>
</body>
</html>