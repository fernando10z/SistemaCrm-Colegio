<!-- Modal Consultar Agenda Semanal -->
<div class="modal fade" id="modalConsultarAgenda" tabindex="-1" aria-labelledby="modalConsultarAgendaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="modal-title" id="modalConsultarAgendaLabel">
          <i class="ti ti-calendar-stats"></i> Agenda Semanal de Interacciones
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="agenda-semanal-contenido">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando agenda...</span>
            </div>
            <p class="mt-3 text-muted">Cargando agenda semanal...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x"></i> Cerrar
        </button>
        <button type="button" class="btn btn-primary" onclick="imprimirAgenda()">
          <i class="ti ti-printer"></i> Imprimir Agenda
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function imprimirAgenda() {
    var contenido = document.getElementById('agenda-semanal-contenido').innerHTML;
    var ventana = window.open('', 'PRINT', 'height=600,width=800');
    
    ventana.document.write('<html><head><title>Agenda Semanal</title>');
    ventana.document.write('<link rel="stylesheet" href="assets/css/style.css">');
    ventana.document.write('</head><body>');
    ventana.document.write('<h2>Agenda Semanal de Interacciones</h2>');
    ventana.document.write(contenido);
    ventana.document.write('</body></html>');
    
    ventana.document.close();
    ventana.focus();
    
    setTimeout(function() {
        ventana.print();
        ventana.close();
    }, 250);
}
</script>