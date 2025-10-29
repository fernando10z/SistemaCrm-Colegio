<?php
// Incluir la conexión
include 'system/bd/conexion.php';

$query_imagen = "SELECT valor FROM configuracion_sistema WHERE id = 6 LIMIT 1";
$result_imagen = $conn->query($query_imagen);
if ($result_imagen && $row_imagen = $result_imagen->fetch_assoc()) {
    $imagen_sistema = htmlspecialchars($row_imagen['valor']);
}

// [TODO TU CÓDIGO PHP DE CONSULTAS SE MANTIENE IGUAL]
$sql = "SELECT * FROM cursos WHERE estado='Activo'";
$result = $conn->query($sql);

$sql1="SELECT*from features where estado='Activo'";
$result1=$conn->query($sql1);

$sql_tabs = "SELECT * FROM tabs WHERE estado='Activo'";
$result_tabs = $conn->query($sql_tabs);

$sql_pres = "SELECT * FROM presentacion WHERE estado='Activo' LIMIT 1";
$result_pres = $conn->query($sql_pres);

$sql = "SELECT * FROM Titulos WHERE id=1";
$res = $conn->query($sql);
$pres = $res->fetch_assoc();
$subtitulo = $pres['subtitulo'];
$palabras = explode(' ', $subtitulo, 2);
$primera = $palabras[0] ?? '';
$resto   = $palabras[1] ?? '';

$sql = "SELECT * FROM seccion_promo WHERE id=1";
$result4 = $conn->query($sql);
$promo = $result4->fetch_assoc();

$query_nombre = "SELECT valor FROM configuracion_sistema WHERE id = 1 LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plataforma educativa moderna">
    <title><?php echo $nombre_sistema; ?></title>
    <link rel="icon" type="image/png" href="System/<?php echo $imagen_sistema; ?>"/>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* ============================================
       SISTEMA DE DISEÑO ELARA 2.0 - MINIMALISTA
       ============================================ */
    
    :root {
        --primary: #5865F2;        /* Azul Discord */
        --primary-dark: #4752C4;   
        --accent: #1DB954;         /* Verde Spotify */
        --accent-glow: #1ED760;
        --danger: #ED4245;
        --warning: #FEE75C;
        
        /* Neutros */
        --black: #0A0A0B;
        --gray-900: #1A1A1D;
        --gray-800: #2E2E32;
        --gray-700: #36363B;
        --gray-600: #4F4F56;
        --gray-500: #72727D;
        --gray-400: #9B9BA5;
        --gray-300: #B8B8C1;
        --gray-200: #DCDDDE;
        --gray-100: #EBEBEF;
        --white: #FFFFFF;
        
        /* Gradientes */
        --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-accent: linear-gradient(135deg, #1DB954 0%, #1ED760 100%);
        --gradient-sunset: linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 52%, #2BFF88 100%);
        --gradient-dark: linear-gradient(180deg, rgba(10,10,11,0) 0%, rgba(10,10,11,0.8) 100%);
        
        /* Espaciado */
        --space-xs: 0.25rem;
        --space-sm: 0.5rem;
        --space-md: 1rem;
        --space-lg: 1.5rem;
        --space-xl: 2rem;
        --space-2xl: 3rem;
        --space-3xl: 4rem;
        --space-4xl: 6rem;
        
        /* Tipografía */
        --font-main: 'Inter', -apple-system, system-ui, sans-serif;
        --font-display: 'Space Grotesk', var(--font-main);
        --font-mono: 'JetBrains Mono', monospace;
        
        /* Animaciones */
        --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        --ease-in-out: cubic-bezier(0.45, 0, 0.55, 1);
        
        /* Componentes */
        --radius-sm: 0.375rem;
        --radius-md: 0.75rem;
        --radius-lg: 1rem;
        --radius-xl: 1.5rem;
        --blur: blur(32px);
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
        --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
        --shadow-lg: 0 8px 32px rgba(0,0,0,0.16);
        --shadow-xl: 0 16px 64px rgba(0,0,0,0.24);
    }
    
    /* Reset Moderno */
    *, *::before, *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    html {
        scroll-behavior: smooth;
        font-size: 16px;
    }
    
    body {
        font-family: var(--font-main);
        background: var(--black);
        color: var(--gray-100);
        overflow-x: hidden;
        line-height: 1.6;
    }
    
    /* Tipografía Moderna */
    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-display);
        font-weight: 700;
        line-height: 1.1;
    }
    
    /* Contenedor Principal */
    .container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 var(--space-lg);
    }
    
    /* ============================================
       HEADER FLOTANTE GLASSMORPHISM
       ============================================ */
    
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: var(--space-md) 0;
        background: rgba(10, 10, 11, 0.6);
        backdrop-filter: var(--blur);
        -webkit-backdrop-filter: var(--blur);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s var(--ease-out);
    }
    
    .navbar.scrolled {
        background: rgba(10, 10, 11, 0.95);
        padding: var(--space-sm) 0;
    }
    
    .navbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .logo {
        font-size: 1.5rem;
        font-weight: 800;
        background: var(--gradient-sunset);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-decoration: none;
        letter-spacing: -0.02em;
    }
    
    .nav-menu {
        display: flex;
        align-items: center;
        gap: var(--space-xl);
        list-style: none;
    }
    
    .nav-link {
        color: var(--gray-300);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: color 0.2s var(--ease-out);
        position: relative;
    }
    
    .nav-link:hover {
        color: var(--white);
    }
    
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--gradient-accent);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s var(--ease-out);
    }
    
    .nav-link:hover::after {
        transform: scaleX(1);
    }
    
    .btn-access {
        background: var(--gradient-primary);
        color: var(--white);
        padding: var(--space-sm) var(--space-lg);
        border-radius: var(--radius-lg);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s var(--ease-out);
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .btn-access:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(88, 101, 242, 0.4);
    }
    
    .mobile-menu-toggle {
        display: none;
        background: transparent;
        border: none;
        color: var(--white);
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    /* ============================================
       HERO SECTION - DISEÑO INMERSIVO
       ============================================ */
    
    .hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -2;
    }
    
    .hero-bg video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(ellipse at center, rgba(88, 101, 242, 0.2) 0%, rgba(10, 10, 11, 0.9) 100%);
        z-index: -1;
    }
    
    .hero-content {
        text-align: center;
        max-width: 900px;
        padding: var(--space-xl);
    }
    
    .hero-badge {
        display: inline-block;
        padding: var(--space-xs) var(--space-md);
        background: rgba(29, 185, 84, 0.1);
        border: 1px solid rgba(29, 185, 84, 0.3);
        border-radius: var(--radius-lg);
        color: var(--accent-glow);
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: var(--space-lg);
    }
    
    .hero-title {
        font-size: clamp(2.5rem, 8vw, 5.5rem);
        font-weight: 900;
        letter-spacing: -0.03em;
        margin-bottom: var(--space-lg);
    }
    
    .hero-title .gradient-text {
        background: var(--gradient-sunset);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .hero-description {
        font-size: 1.25rem;
        color: var(--gray-300);
        margin-bottom: var(--space-2xl);
        line-height: 1.6;
    }
    
    .hero-cta {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: var(--space-md) var(--space-2xl);
        border-radius: var(--radius-lg);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        transition: all 0.3s var(--ease-out);
        cursor: pointer;
        border: none;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: var(--gradient-primary);
        color: var(--white);
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(88, 101, 242, 0.3);
    }
    
    .btn-secondary {
        background: rgba(255, 255, 255, 0.05);
        color: var(--white);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
    }
    
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-3px);
    }
    
    /* ============================================
       FEATURES - CARDS FLOTANTES
       ============================================ */
    
    .features {
        padding: var(--space-4xl) 0;
        background: var(--gray-900);
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: var(--space-xl);
        margin-top: var(--space-3xl);
    }
    
    .feature-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: var(--radius-xl);
        padding: var(--space-2xl);
        transition: all 0.4s var(--ease-out);
        position: relative;
        overflow: hidden;
    }
    
    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--gradient-accent);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.4s var(--ease-out);
    }
    
    .feature-card:hover {
        transform: translateY(-8px);
        background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.04) 100%);
        box-shadow: var(--shadow-xl);
    }
    
    .feature-card:hover::before {
        transform: scaleX(1);
    }
    
    .feature-icon {
        width: 60px;
        height: 60px;
        background: var(--gradient-primary);
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--white);
        margin-bottom: var(--space-lg);
    }
    
    .feature-title {
        font-size: 1.5rem;
        margin-bottom: var(--space-md);
        color: var(--white);
    }
    
    .feature-description {
        color: var(--gray-400);
        line-height: 1.7;
    }
    
    /* ============================================
       SECCIÓN TABS - MINIMALISTA
       ============================================ */
    
    .tabs-section {
        padding: var(--space-4xl) 0;
        background: var(--black);
    }
    
    .section-header {
        text-align: center;
        max-width: 700px;
        margin: 0 auto var(--space-3xl);
    }
    
    .section-title {
        font-size: clamp(2rem, 5vw, 3.5rem);
        margin-bottom: var(--space-lg);
        background: var(--gradient-sunset);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .section-subtitle {
        font-size: 1.125rem;
        color: var(--gray-400);
    }
    
    .tabs-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .tabs-nav {
        display: flex;
        gap: var(--space-md);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: var(--space-3xl);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-btn {
        background: transparent;
        border: none;
        color: var(--gray-400);
        padding: var(--space-md) var(--space-lg);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s var(--ease-out);
        position: relative;
        white-space: nowrap;
    }
    
    .tab-btn.active {
        color: var(--white);
    }
    
    .tab-btn::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--gradient-accent);
        transform: scaleX(0);
        transition: transform 0.3s var(--ease-out);
    }
    
    .tab-btn.active::after {
        transform: scaleX(1);
    }
    
    .tabs-content {
        position: relative;
    }
    
    .tab-panel {
        display: none;
        animation: fadeIn 0.5s var(--ease-out);
    }
    
    .tab-panel.active {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-3xl);
        align-items: center;
    }
    
    .tab-image {
        width: 100%;
        border-radius: var(--radius-xl);
        overflow: hidden;
    }
    
    .tab-image img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .tab-content {
        padding: var(--space-xl);
    }
    
    .tab-content h3 {
        font-size: 2rem;
        margin-bottom: var(--space-lg);
        color: var(--white);
    }
    
    .tab-content p {
        color: var(--gray-300);
        line-height: 1.8;
        margin-bottom: var(--space-lg);
    }
    
    /* ============================================
       CURSOS - GRID MODERNO
       ============================================ */
    
    .courses {
        padding: var(--space-4xl) 0;
        background: var(--gray-900);
    }
    
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: var(--space-xl);
        margin-top: var(--space-3xl);
    }
    
    .course-card {
        background: var(--gray-800);
        border-radius: var(--radius-xl);
        overflow: hidden;
        transition: all 0.3s var(--ease-out);
        position: relative;
    }
    
    .course-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
    }
    
    .course-image {
        position: relative;
        padding-top: 60%;
        overflow: hidden;
    }
    
    .course-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s var(--ease-out);
    }
    
    .course-card:hover .course-image img {
        transform: scale(1.05);
    }
    
    .course-badge {
        position: absolute;
        top: var(--space-md);
        right: var(--space-md);
        padding: var(--space-xs) var(--space-md);
        background: var(--gradient-accent);
        color: var(--white);
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .course-badge.paid {
        background: var(--gradient-primary);
    }
    
    .course-content {
        padding: var(--space-xl);
    }
    
    .course-title {
        font-size: 1.25rem;
        margin-bottom: var(--space-sm);
        color: var(--white);
    }
    
    .course-description {
        color: var(--gray-400);
        line-height: 1.6;
        margin-bottom: var(--space-lg);
    }
    
    .course-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .course-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--accent);
    }
    
    .course-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        transition: gap 0.3s var(--ease-out);
    }
    
    .course-link:hover {
        gap: var(--space-md);
    }
    
    /* ============================================
       PROMOCIÓN - CALL TO ACTION
       ============================================ */
    
    .promo {
        padding: var(--space-4xl) 0;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        position: relative;
        overflow: hidden;
    }
    
    .promo::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    .promo-content {
        position: relative;
        text-align: center;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .promo-title {
        font-size: 2.5rem;
        margin-bottom: var(--space-xl);
        color: var(--white);
    }
    
    .promo-form {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
    }
    
    .form-input {
        padding: var(--space-md) var(--space-lg);
        border: 2px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-lg);
        color: var(--white);
        font-size: 1rem;
        transition: all 0.3s var(--ease-out);
    }
    
    .form-input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .form-input:focus {
        outline: none;
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.15);
    }
    
    .btn-submit {
        background: var(--gradient-accent);
        color: var(--white);
        padding: var(--space-md) var(--space-2xl);
        border: none;
        border-radius: var(--radius-lg);
        font-size: 1.125rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s var(--ease-out);
    }
    
    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(29, 185, 84, 0.3);
    }
    
    /* ============================================
       CONTACTO - DISEÑO SPLIT
       ============================================ */
    
    .contact {
        padding: var(--space-4xl) 0;
        background: var(--black);
    }
    
    .contact-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-3xl);
        align-items: start;
    }
    
    .contact-form {
        background: var(--gray-900);
        padding: var(--space-2xl);
        border-radius: var(--radius-xl);
    }
    
    .contact-map {
        border-radius: var(--radius-xl);
        overflow: hidden;
        height: 100%;
        min-height: 500px;
    }
    
    .contact-map iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    /* ============================================
       FOOTER MINIMALISTA
       ============================================ */
    
    .footer {
        padding: var(--space-2xl) 0;
        background: var(--gray-900);
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .footer-text {
        color: var(--gray-500);
        font-size: 0.875rem;
    }
    
    .footer-links {
        display: flex;
        gap: var(--space-lg);
    }
    
    .footer-link {
        color: var(--gray-400);
        text-decoration: none;
        transition: color 0.3s var(--ease-out);
    }
    
    .footer-link:hover {
        color: var(--white);
    }
    
    /* ============================================
       ANIMACIONES
       ============================================ */
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
    
    .fade-in {
        animation: fadeIn 0.6s var(--ease-out) forwards;
    }
    
    /* ============================================
       RESPONSIVE
       ============================================ */
    
    @media (max-width: 1024px) {
        .tab-panel.active {
            grid-template-columns: 1fr;
        }
        
        .contact-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block;
        }
        
        .nav-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 400px;
            height: 100vh;
            background: var(--gray-900);
            flex-direction: column;
            justify-content: start;
            padding: var(--space-3xl) var(--space-xl);
            transition: right 0.3s var(--ease-out);
        }
        
        .nav-menu.active {
            right: 0;
        }
        
        .hero-title {
            font-size: clamp(2rem, 8vw, 3.5rem);
        }
        
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .courses-grid {
            grid-template-columns: 1fr;
        }
        
        .hero-cta {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    /* ============================================
       UTILIDADES
       ============================================ */
    
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    
    .mt-1 { margin-top: var(--space-sm); }
    .mt-2 { margin-top: var(--space-md); }
    .mt-3 { margin-top: var(--space-lg); }
    .mt-4 { margin-top: var(--space-xl); }
    
    .mb-1 { margin-bottom: var(--space-sm); }
    .mb-2 { margin-bottom: var(--space-md); }
    .mb-3 { margin-bottom: var(--space-lg); }
    .mb-4 { margin-bottom: var(--space-xl); }
    
    .hidden { display: none; }
    .block { display: block; }
    .flex { display: flex; }
    .grid { display: grid; }
    
    /* Scrollbar Personalizada */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    ::-webkit-scrollbar-track {
        background: var(--gray-900);
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--gray-700);
        border-radius: var(--radius-md);
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--gray-600);
    }
    </style>
    
    <!-- Fuentes Optimizadas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>

<body>
    <!-- NAVBAR FLOTANTE -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="#" class="logo"><?php echo $nombre_sistema; ?></a>
                
                <ul class="nav-menu" id="navMenu">
                    <li><a href="#inicio" class="nav-link">Inicio</a></li>
                    <li><a href="#nosotros" class="nav-link">Nosotros</a></li>
                    <li><a href="#cursos" class="nav-link">Cursos</a></li>
                    <li><a href="#contacto" class="nav-link">Contacto</a></li>
                    <li><a href="System/login.php" class="btn-access">
                        <i class="fas fa-user"></i> Acceder
                    </a></li>
                </ul>
                
                <button class="mobile-menu-toggle" id="mobileMenu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- HERO SECTION -->
    <section class="hero" id="inicio">
        <div class="hero-bg">
            <video autoplay muted loop>
                <source src="<?= $pres['video_url']; ?>" type="video/mp4">
            </video>
        </div>
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <span class="hero-badge"><?= $pres['titulo']; ?></span>
            <h1 class="hero-title">
                <span class="gradient-text"><?= $primera ?></span><br>
                <?= $resto ?>
            </h1>
            <p class="hero-description">
                Transforma tu futuro con educación de calidad. 
                Únete a miles de estudiantes que ya están construyendo su camino al éxito.
            </p>
            <div class="hero-cta">
                <a href="<?= $pres['boton_url']; ?>" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> <?= $pres['boton_texto']; ?>
                </a>
                <a href="#cursos" class="btn btn-secondary">
                    <i class="fas fa-play-circle"></i> Ver Cursos
                </a>
            </div>
        </div>
    </section>
    
    <!-- FEATURES -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">¿Por qué elegirnos?</h2>
                <p class="section-subtitle">Descubre lo que nos hace diferentes</p>
            </div>
            
            <div class="features-grid">
                <?php if ($result1 && $result1->num_rows > 0): ?>
                    <?php while($row = $result1->fetch_assoc()): ?>
                        <div class="feature-card fade-in">
                            <div class="feature-icon">
                                <i class="<?= htmlspecialchars($row['icono']) ?>"></i>
                            </div>
                            <h3 class="feature-title"><?= htmlspecialchars($row['titulo']) ?></h3>
                            <p class="feature-description"><?= htmlspecialchars($row['descripcion']) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- TABS SECTION -->
    <section class="tabs-section" id="nosotros">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Conoce más sobre nosotros</h2>
                <p class="section-subtitle">Explora nuestras diferentes áreas</p>
            </div>
            
            <div class="tabs-container">
                <div class="tabs-nav">
                    <?php 
                    $first = true;
                    while ($tab = $result_tabs->fetch_assoc()): 
                    ?>
                        <button class="tab-btn <?php echo $first ? 'active' : ''; ?>" 
                                data-tab="tab-<?php echo $tab['id']; ?>">
                            <?php echo htmlspecialchars($tab['titulo_tab']); ?>
                        </button>
                    <?php 
                        $first = false;
                    endwhile; 
                    ?>
                </div>
                
                <div class="tabs-content">
                    <?php 
                    $result_tabs->data_seek(0);
                    $first = true;
                    while ($tab = $result_tabs->fetch_assoc()): 
                    ?>
                        <div class="tab-panel <?php echo $first ? 'active' : ''; ?>" 
                             id="tab-<?php echo $tab['id']; ?>">
                            <div class="tab-image">
                                <img src="<?php echo htmlspecialchars($tab['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($tab['titulo_tab']); ?>">
                            </div>
                            <div class="tab-content">
                                <h3><?php echo htmlspecialchars($tab['titulo_h4']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($tab['descripcion'])); ?></p>
                                <?php if (!empty($tab['descripcion_extra'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($tab['descripcion_extra'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        $first = false;
                    endwhile; 
                    ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CURSOS -->
    <section class="courses" id="cursos">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Nuestros Cursos</h2>
                <p class="section-subtitle">Elige tu camino de aprendizaje</p>
            </div>
            
            <div class="courses-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="course-card fade-in">
                            <div class="course-image">
                                <img src="<?php echo $row['imagen_curso']; ?>" 
                                     alt="<?php echo $row['titulo']; ?>">
                                <span class="course-badge <?php echo $row['tipo'] == 'Pay' ? 'paid' : ''; ?>">
                                    <?php echo $row['tipo'] == 'Pay' ? 'Premium' : 'Gratis'; ?>
                                </span>
                            </div>
                            <div class="course-content">
                                <h3 class="course-title"><?php echo $row['titulo']; ?></h3>
                                <p class="course-description"><?php echo $row['descripcion']; ?></p>
                                <div class="course-footer">
                                    <span class="course-price">
                                        <?php echo $row['tipo'] == 'Pay' ? 'S/.' . rand(29, 99) : 'Free'; ?>
                                    </span>
                                    <a href="<?php echo $row['link']; ?>" class="course-link">
                                        Ver más <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- PROMOCIÓN -->
    <section class="promo">
        <div class="container">
            <div class="promo-content">
                <h2 class="promo-title"><?= $promo['subtitulo']; ?></h2>
                <form class="promo-form" action="guardar_registro.php" method="post">
                    <input type="text" name="name" class="form-input" placeholder="Tu nombre completo" required>
                    <input type="email" name="email" class="form-input" placeholder="Tu correo electrónico" required>
                    <input type="tel" name="phone" class="form-input" placeholder="Tu número de teléfono" required>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Comenzar ahora
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- CONTACTO -->
    <section class="contact" id="contacto">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Contáctanos</h2>
                <p class="section-subtitle">Estamos aquí para ayudarte</p>
            </div>
            
            <div class="contact-container">
                <form class="contact-form" action="guardar_consulta.php" method="post">
                  <input type="text" name="name" class="form-input" placeholder="Nombre" required>
                  <input type="email" name="email" class="form-input" placeholder="Correo" required>
                  <textarea name="message" class="form-input" rows="5" placeholder="Tu mensaje..." style="margin: 2rem 0; width: 91%" required></textarea>
                    <button type="submit" class="btn btn-primary" style="width: 91%; margin-top: 1rem; text-align: center; justify-content: center;">
                    <i class="fas fa-send"></i> Enviar mensaje
                    </button>
                </form>
                
                <div class="contact-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3864.8941560532825!2d-75.72896552374043!3d-14.068723786101387!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9110e29784b404e1%3A0x82a5d451fcd059ed!2sInstituci%C3%B3n%20Educativa%20Santa%20Ana%20de%20Benavides!5e0!3m2!1ses!2spe!4v1709679613593!5m2!1ses!2spe" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p class="footer-text">© 2024 <?php echo $nombre_sistema; ?>. Todos los derechos reservados.</p>
                <div class="footer-links">
                    <a href="#" class="footer-link">Privacidad</a>
                    <a href="#" class="footer-link">Términos</a>
                    <a href="#" class="footer-link">Ayuda</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- SCRIPTS -->
    <script>
        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Mobile Menu
        const mobileMenu = document.getElementById('mobileMenu');
        const navMenu = document.getElementById('navMenu');
        
        mobileMenu.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileMenu.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        
        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    navMenu.classList.remove('active');
                }
            });
        });
        
        // Tabs Functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanels.forEach(p => p.classList.remove('active'));
                
                btn.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Intersection Observer for Animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
            observer.observe(el);
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>