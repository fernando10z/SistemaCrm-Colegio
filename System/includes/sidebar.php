<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
    <a href="index.php" class="b-brand text-primary d-flex align-items-center" style="text-decoration: none;">
      <i class="ti ti-school" style="font-size: 2rem; margin-right: 10px;"></i>
      <h1 style="font-size: 1rem; font-weight: bold; margin: 0;"><?php echo $nombre_sistema; ?></h1>
    </a>
    </div>
    <div class="navbar-content">
        <ul class="pc-navbar">
            <li class="pc-item">
                <a href="index.php" class="pc-link">
                <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                <span class="pc-mtext">Inicio</span>
                </a>
            </li>
            
            <!-- 1. MÓDULO DE CAPTACIÓN Y SEGUIMIENTO -->
            <li class="pc-item pc-caption">
                <label>Captación</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-eye"></i></span><span class="pc-mtext">Seguimiento</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="leads.php">Registro de Leads</a></li>
                <li class="pc-item"><a class="pc-link" href="clasificacion_leads.php">Clasificación de Leads</a></li>
                <li class="pc-item"><a class="pc-link" href="asignacion_leads.php">Asignación</a></li>
                <li class="pc-item"><a class="pc-link" href="agenda_leads.php">Agenda</a></li>
                <li class="pc-item"><a class="pc-link" href="reportes_conversion.php">Reportes de Conversión</a></li>
                </ul>
            </li>

            <!-- 2. MÓDULO DE COMUNICACIÓN INSTITUCIONAL -->
            <li class="pc-item pc-caption">
                <label>Comunicación Institucional</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-message"></i>
                    </span><span class="pc-mtext">Comunicación</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="gestion_envios.php">Gestión de Envíos</a></li>
                <li class="pc-item"><a class="pc-link" href="mensajeria.php">Mensajería Programada</a></li>
                <li class="pc-item"><a class="pc-link" href="comunicaciones.php">Historial de Comunicaciones</a></li>
                </ul>
            </li>

            <!-- 3. MÓDULO DE GESTIÓN DE PADRES Y APODERADOS -->
            <li class="pc-item pc-caption">
                <label>Padres y Apoderados</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="fas fa-users"></i>
                    </span><span class="pc-mtext">Gestión de Apoderados</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="apoderados.php">Registro de Apoderados</a></li>
                <li class="pc-item"><a class="pc-link" href="historial_interacciones.php">Historial de Interacciones</a></li>
                <li class="pc-item"><a class="pc-link" href="clasi_seg.php">Clasificación y Segmentación</a></li>
                </ul>
            </li>

            <!-- 4. MÓDULO DE FIDELIZACIÓN DE FAMILIAS -->
            <li class="pc-item pc-caption">
                <label>Fidelización</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-heart"></i>
                    </span><span class="pc-mtext">Familias</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="encuestas.php">Encuestas de Satisfacción</a></li>
                <li class="pc-item"><a class="pc-link" href="boletines.php">Boletines Informativos</a></li>
                <li class="pc-item"><a class="pc-link" href="participacion.php">Estadísticas de Participación</a></li>
                <li class="pc-item"><a class="pc-link" href="campanas_fidelizacion.php">Campañas de Fidelización</a></li>
                </ul>
            </li>

            <!-- 5. MÓDULO DE GESTIÓN FINANCIERA CRM -->
            <!-- <li class="pc-item pc-caption">
                <label>Gestión Financiera</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-credit-card"></i>
                    </span><span class="pc-mtext">Finanzas CRM</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="historial_pagos.php">Historial de Pagos</a></li>
                <li class="pc-item"><a class="pc-link" href="alertas_vencimientos.php">Alertas de Vencimientos</a></li>
                <li class="pc-item"><a class="pc-link" href="descuentos_planes.php">Descuentos y Planes</a></li>
                <li class="pc-item"><a class="pc-link" href="deuda_consolidada.php">Deuda Consolidada</a></li>
                </ul>
            </li> -->

            <!-- 6. MÓDULO DE EXALUMNOS Y REENGANCHE -->
            <li class="pc-item pc-caption">
                <label>Exalumnos</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-school"></i>
                    </span><span class="pc-mtext">Reenganche</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="registro_egresados.php">Registro de Egresados</a></li>
                <li class="pc-item"><a class="pc-link" href="campanas_exalumnos.php">Campañas para Exalumnos</a></li>
                <!-- <li class="pc-item"><a class="pc-link" href="reenganche_desertores.php">Reenganche de Desertores</a></li> -->
                <li class="pc-item"><a class="pc-link" href="referidos_exalumnos.php">Referidos de Exalumnos</a></li>
                </ul>
            </li>

            <!-- 7. MÓDULO DE REFERIDOS Y PROMOCIÓN -->
            <li class="pc-item pc-caption">
                <label>Referidos</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-share"></i>
                    </span><span class="pc-mtext">Promoción</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="codigos_referidos.php">Códigos de Recomendación</a></li>
                <li class="pc-item"><a class="pc-link" href="leads_referidos.php">Leads Referidos</a></li>
                <li class="pc-item"><a class="pc-link" href="incentivos_referidos.php">Incentivos por Referidos</a></li>
                <li class="pc-item"><a class="pc-link" href="rankings_recomendacion.php">Rankings de Recomendación</a></li>
                </ul>
            </li>

            <!-- 8. MÓDULO DE REPORTES Y ANÁLISIS -->
            <li class="pc-item pc-caption">
                <label>Reportes</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-chart-bar"></i>
                    </span><span class="pc-mtext">Análisis</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="dashboard_area.php">Dashboards por Área</a></li>
                <!-- <li class="pc-item"><a class="pc-link" href="exportar_reportes.php">Exportación de Reportes</a></li> -->
                <li class="pc-item"><a class="pc-link" href="analisis_comparativo.php">Análisis Comparativo</a></li>
                </ul>
            </li>

            <!-- 10. MÓDULO DE INTEGRACIÓN EXTERNA -->
            <!-- <li class="pc-item pc-caption">
                <label>Integraciones</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-plug"></i>
                    </span><span class="pc-mtext">Servicios Externos</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="integracion_comunicaciones.php">Comunicaciones</a></li>
                <li class="pc-item"><a class="pc-link" href="apis_externas.php">APIs Externas</a></li>
                <li class="pc-item"><a class="pc-link" href="logs_integracion.php">Logs y Monitoreo</a></li>
                </ul>
            </li> -->

            <li class="pc-item pc-caption">
                <label>Página Web</label>
                <i class="ti ti-world"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i class="ti ti-layout"></i></span>
                    <span class="pc-mtext">Gestión Web</span>
                    <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="pc-submenu">
                    <li class="pc-item"><a class="pc-link" href="titulos.php">Títulos</a></li>
                    <li class="pc-item"><a class="pc-link" href="features.php">Features</a></li>
                    <li class="pc-item"><a class="pc-link" href="cursos.php">Cursos</a></li>
                    <li class="pc-item"><a class="pc-link" href="tabs.php">Tablas</a></li>
                    <li class="pc-item"><a class="pc-link" href="presentacion.php">Presentación</a></li>
                    <li class="pc-item"><a class="pc-link" href="promociones.php">Promociones</a></li>
                </ul>
            </li>

        <!-- Mensajería -->
        <li class="pc-item pc-caption">
            <label>Mensajería</label>
            <i class="ti ti-mail"></i>
        </li>
        <li class="pc-item pc-hasmenu">
            <a href="#!" class="pc-link">
            <span class="pc-micon"><i class="ti ti-message-circle"></i></span>
            <span class="pc-mtext">Gestión Mensajería</span>
            <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
            </a>
            <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="mensajespromo.php">Mensajes Promociones</a></li>
            <li class="pc-item"><a class="pc-link" href="consultas.php">Consultas</a></li>
            </ul>
        </li>

            <!-- CONFIGURACIÓN -->
            <li class="pc-item pc-caption">
                <label>Configuración</label>
                <i class="ti ti-brand-chrome"></i>
            </li>
            <li class="pc-item pc-hasmenu">
                <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-settings"></i></span><span class="pc-mtext">Configuración</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="pc-submenu">
                <li class="pc-item"><a class="pc-link" href="informacion.php">Información</a></li>
                <li class="pc-item"><a class="pc-link" href="usuarios.php">Usuarios</a></li>
                <li class="pc-item"><a class="pc-link" href="logs.php">Registro de Actividades</a></li>
                </ul>
            </li>
        </ul>
        <div class="card text-center" style="margin-top: 20px; padding: 10px;">
        <small class="text-muted">&copy; <?php echo date("Y"); ?> <?php echo $nombre_sistema; ?></small>
      </div>
    </div>
  </div>
</nav>