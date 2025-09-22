<?php
// Incluir la conexión
include 'system/bd/conexion.php';  // ajusta la ruta según tu carpeta

// Traer solo los cursos activos
$sql = "SELECT * FROM cursos WHERE estado='Activo'";
$result = $conn->query($sql);

$sql1="SELECT*from features where estado='Activo'";
$result1=$conn->query($sql1);

// Traer solo las tabs activas
$sql_tabs = "SELECT * FROM tabs WHERE estado='Activo'";
$result_tabs = $conn->query($sql_tabs);

$sql_pres = "SELECT * FROM presentacion WHERE estado='Activo' LIMIT 1";
$result_pres = $conn->query($sql_pres);

$sql = "SELECT * FROM Titulos WHERE id=1";
$res = $conn->query($sql);
$pres = $res->fetch_assoc();
// separar primera palabra del resto
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
<html lang="en">

  <head>
<style>
.highlight {
  color: #f1c40f;        /* amarillo */
  font-weight: 900;      /* más grueso que bold */
  text-shadow: 1px 1px 2px rgba(0,0,0,0.3); /* sombra opcional para destacar */
}

</style>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:100,200,300,400,500,600,700,800,900" rel="stylesheet">

    <title><?php echo $nombre_sistema; ?></title>
    
    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-grad-school.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/lightbox.css">
<!--
    
TemplateMo 557 Grad School

https://templatemo.com/tm-557-grad-school

-->
  </head>

<body>

   
  <!--header-->
  <header class="main-header clearfix" role="header">
    <div class="logo">
      <a href="#"><?php echo $nombre_sistema; ?></a>
    </div>
    <a href="#menu" class="menu-link"><i class="fa fa-bars"></i></a>
    <nav id="menu" class="main-nav" role="navigation">
      <ul class="main-menu">
        <li><a href="#section1">Inicio</a></li>
        <li class="has-submenu"><a href="#section2">Sobre nosotros</a>
          <ul class="sub-menu">
            <li><a href="#section2">¿Quiénes somos?</a></li>
            <li><a href="#section3">¿Qué hacemos?</a></li>
            <li><a href="#section3">¿Cómo funciona?</a></li>
          </ul>
        </li>
        <li><a href="#section4">Cursos</a></li>
        <!-- <li><a href="#section5">Video</a></li> -->
        <li><a href="#section6">Contactanos</a></li>
        <li><a href="System/login.php" class="external">Acceso</a></li>
      </ul>
    </nav>
  </header>

  <!-- ***** Main Banner Area Start ***** -->
<section class="section main-banner" id="top" data-section="section1">
  <video autoplay muted loop id="bg-video">
      <source src="<?= $pres['video_url']; ?>" type="video/mp4" />
  </video>

  <div class="video-overlay header-text">
      <div class="caption">
          <h6><?= $pres['titulo']; ?></h6>
          <h2>
            <span class="highlight"><?= $primera ?></span> <?= $resto ?>
          </h2>
          <div class="main-button">
              <div class="scroll-to-section">
                <a href="<?= $pres['boton_url']; ?>"><?= $pres['boton_texto']; ?></a>
              </div>
          </div>
      </div>
  </div>
</section>
  <!-- ***** Main Banner Area End ***** -->

<section class="features">
  <div class="container">
    <div class="row">
      <?php if ($result1 && $result1->num_rows > 0): ?>
        <?php while($row = $result1->fetch_assoc()): ?>
          <div class="col-lg-4 col-12">
            <div class="features-post <?= htmlspecialchars($row['clase_extra']) ?>">
              <div class="features-content">
                <div class="content-show">
                  <h4>
                    <i class="<?= htmlspecialchars($row['icono']) ?>"></i>
                    <?= htmlspecialchars($row['titulo']) ?>
                  </h4>
                </div>
                <div class="content-hide">
                  <p><?= htmlspecialchars($row['descripcion']) ?></p>
                  <p class="hidden-sm"><?= htmlspecialchars($row['descripcion_corta']) ?></p>
                  <div class="scroll-to-section">
                    <a href="<?= htmlspecialchars($row['enlace']) ?>">
                      <?= htmlspecialchars($row['texto_enlace']) ?>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No hay features activas disponibles.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section why-us" data-section="section2">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="section-heading">
          <h2>
¿Por qué elegir Grad School?</h2>
        </div>
      </div>

      <div class="col-md-12">
        <div id="tabs">
          <!-- LISTA DE PESTAÑAS -->
          <ul>
            <?php while ($tab = $result_tabs->fetch_assoc()) { ?>
              <li>
                <a href="#tabs-<?php echo $tab['id']; ?>">
                  <?php echo htmlspecialchars($tab['titulo_tab']); ?>
                </a>
              </li>
            <?php } ?>
          </ul>

          <!-- CONTENIDO DE PESTAÑAS -->
          <section class="tabs-content">
            <?php 
            $result_tabs->data_seek(0); // reiniciar puntero
            while ($tab = $result_tabs->fetch_assoc()) { ?>
              <article id="tabs-<?php echo $tab['id']; ?>">
                <div class="row">
                  <div class="col-md-6">
                    <img src="<?php echo htmlspecialchars($tab['imagen']); ?>" alt="">
                  </div>
                  <div class="col-md-6">
                    <h4><?php echo htmlspecialchars($tab['titulo_h4']); ?></h4>
                    <p><?php echo nl2br(htmlspecialchars($tab['descripcion'])); ?></p>
                    <?php if (!empty($tab['descripcion_extra'])) { ?>
                      <p><?php echo nl2br(htmlspecialchars($tab['descripcion_extra'])); ?></p>
                    <?php } ?>
                  </div>
                </div>
              </article>
            <?php } ?>
          </section>
        </div>
      </div>
    </div>
  </div>
</section>
  <section class="section coming-soon d-flex align-items-center justify-content-center" data-section="section3" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-12">
        <div class="right-content text-center">
          <div class="top-content mb-3">
            <h6><?= $promo['subtitulo']; ?></h6>
          </div>
          <form id="contact" action="guardar_registro.php" method="post">
            <input name="name" type="text" placeholder="Your Name" required>
            <input name="email" type="email" placeholder="Your Email" required>
            <input name="phone" type="text" placeholder="Your Phone Number" required>
            <button type="submit">
Consíguelo ahora</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>


<section class="section courses" data-section="section4">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="section-heading">
          <h2>Elige tu curso</h2>
        </div>
      </div>
      <div class="owl-carousel owl-theme">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                ?>
                <div class="item">
                  <img src="<?php echo $row['imagen_curso']; ?>" alt="<?php echo $row['titulo']; ?>">
                  <div class="down-content">
                    <h4><?php echo $row['titulo']; ?></h4>
                    <p><?php echo $row['descripcion']; ?></p>
                 
                    <div class="<?php echo ($row['tipo'] == 'Pay') ? 'text-button-pay' : 'text-button-free'; ?>">
                      <a href="<?php echo $row['link']; ?>">
                        <?php echo $row['tipo']; ?> <i class="fa fa-angle-double-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <?php
            }
        } else {
            echo "<p>No hay cursos disponibles.</p>";
        }
        ?>
      </div>
    </div>
  </div>
</section>
<section class="section video" data-section="section5">
  <div class="container">
    <div class="row">

      <?php if ($result_pres->num_rows > 0): 
          $pres = $result_pres->fetch_assoc(); 
      ?>
        <div class="col-md-6 align-self-center">
          <div class="left-content">
            <span><?= $pres['subtitulo']; ?></span>
            <h4><?= $pres['titulo']; ?></h4>
            <p><?= $pres['descripcion']; ?></p>
            <div class="main-button">
              <a rel="nofollow" href="https://fb.com/templatemo" target="_parent">URL externa</a>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <article class="video-item">
            <div class="video-caption">
              <h4><?= strip_tags($pres['titulo']); ?></h4>
            </div>
            <figure>
              <a href="<?= $pres['url_video']; ?>" class="play">
                <img src="<?= $pres['imagen']; ?>">
              </a>
            </figure>
          </article>
        </div>
      <?php else: ?>
         <div class="col-md-12 text-center">
          <p style="color: white; font-weight: bold;">
            No hay presentación activa en la base de datos.
          </p>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

  <section class="section contact" data-section="section6">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="section-heading">
            <h2>Mantengámonos en contacto</h2>
          </div>
        </div>
        <div class="col-md-6">
        
        <!-- Do you need a working HTML contact-form script?
                	
                    Please visit https://templatemo.com/contact page -->
                    
          <form id="contact" action="guardar_consulta.php" method="post">
  <div class="row">
    <div class="col-md-6">
      <fieldset>
        <input name="name" type="text" class="form-control" id="name" placeholder="Your Name" required>
      </fieldset>
    </div>
    <div class="col-md-6">
      <fieldset>
        <input name="email" type="email" class="form-control" id="email" placeholder="Your Email" required>
      </fieldset>
    </div>
    <div class="col-md-12">
      <fieldset>
        <textarea name="message" rows="6" class="form-control" id="message" placeholder="Your message..." required></textarea>
      </fieldset>
    </div>
    <div class="col-md-12">
      <fieldset>
        <button type="submit" id="form-submit" class="button">
Enviar Consulta </button>
      </fieldset>
    </div>
  </div>
</form>

        </div>
        <div class="col-md-6">
          <div id="map">
            <iframe src="https://maps.google.com/maps?q=Av.+L%C3%BAcio+Costa,+Rio+de+Janeiro+-+RJ,+Brazil&t=&z=13&ie=UTF8&iwloc=&output=embed" width="100%" height="422px" frameborder="0" style="border:0" allowfullscreen></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <p><i class="fa fa-copyright"></i> 
Derechos de autor 2020 de Grad School 
          
           | Diseño: <a href="https://templatemo.com" rel="sponsored" target="_parent">TemplateMo</a></p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/lightbox.js"></script>
    <script src="assets/js/tabs.js"></script>
    <script src="assets/js/video.js"></script>
    <script src="assets/js/slick-slider.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        //according to loftblog tut
        $('.nav li:first').addClass('active');

        var showSection = function showSection(section, isAnimate) {
          var
          direction = section.replace(/#/, ''),
          reqSection = $('.section').filter('[data-section="' + direction + '"]'),
          reqSectionPos = reqSection.offset().top - 0;

          if (isAnimate) {
            $('body, html').animate({
              scrollTop: reqSectionPos },
            800);
          } else {
            $('body, html').scrollTop(reqSectionPos);
          }

        };

        var checkSection = function checkSection() {
          $('.section').each(function () {
            var
            $this = $(this),
            topEdge = $this.offset().top - 80,
            bottomEdge = topEdge + $this.height(),
            wScroll = $(window).scrollTop();
            if (topEdge < wScroll && bottomEdge > wScroll) {
              var
              currentId = $this.data('section'),
              reqLink = $('a').filter('[href*=\\#' + currentId + ']');
              reqLink.closest('li').addClass('active').
              siblings().removeClass('active');
            }
          });
        };

        $('.main-menu, .scroll-to-section').on('click', 'a', function (e) {
          if($(e.target).hasClass('external')) {
            return;
          }
          e.preventDefault();
          $('#menu').removeClass('active');
          showSection($(this).attr('href'), true);
        });

        $(window).scroll(function () {
          checkSection();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>