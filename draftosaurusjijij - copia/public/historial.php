<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Partidas - Draftosaurus</title>
  
  <!-- CSS Framework y Fuentes -->
  <link rel="stylesheet" href="../public/css/inicio.css">
  <link rel="stylesheet" href="../public/css/historial.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One:wght@400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/historial.css">
</head>
<body>
  <div class="contenedor-principal">
    <div class="tarjeta-juego aparecer">
      
      <!-- ENCABEZADO -->
      <div class="encabezado-juego">
        <h1 class="titulo-juego">
          <i class="fas fa-history icono-pequeno"></i>
          Historial de Partidas
        </h1>
        <p>Consulta los resultados de tus aventuras jurásicas</p>
      </div>

      <!-- CONTENIDO PRINCIPAL -->
      <div class="contenido-juego">
        
        <!-- BOTONES DE NAVEGACIÓN -->
        <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
          <button class="boton-volver" onclick="window.location.href='menu.php'">
            <i class="fas fa-arrow-left"></i> Volver al Menu
          </button>
          
          <button class="boton-dino" id="btn-recargar" onclick="recargarHistorial()">
            <i class="fas fa-redo"></i> Actualizar
          </button>
        </div>

        <!-- CONTROLES ADICIONALES (OPCIONAL) -->
        <div style="margin-bottom: 20px;">
          <label for="limite-partidas" style="margin-right: 10px; font-weight: 600;">
            Mostrar últimas:
          </label>
          <select id="limite-partidas" class="campo-formulario" style="width: auto; display: inline-block;">
            <option value="5">5 partidas</option>
            <option value="10" selected>10 partidas</option>
            <option value="20">20 partidas</option>
            <option value="50">50 partidas</option>
          </select>
        </div>

        <!-- ESTADÍSTICAS GENERALES (OPCIONAL) -->
        <div id="estadisticas-generales"></div>

        <!-- TÍTULO SECCIÓN -->
        <h2 style="margin-bottom: 15px; color: var(--color-texto);">
          <i class="fas fa-trophy"></i> Partidas Finalizadas
        </h2>
        
        <!-- CONTENEDOR PRINCIPAL DE PARTIDAS -->
        <div id="lista-partidas">
          <!-- Aquí se cargarán dinámicamente las partidas -->
          <div class="cargando">
            <div class="spinner"></div>
            <p>Cargando historial...</p>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script src="../public/js/historial.js"></script>
</body>
</html>