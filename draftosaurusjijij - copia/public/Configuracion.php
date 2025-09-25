<!-- Configuraciones.php - FORMULARIO COMPLETO -->
<form id="config-form" class="config-form">
    <h3>Configuración del Juego</h3>
    
    <div class="form-group">
        <label for="tablero-tipo">Tipo de Tablero:</label>
        <select id="tablero-tipo" name="tablero_tipo" required>
            <option value="verano">Verano</option>
            <option value="invierno">Invierno</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="dificultad">Dificultad:</label>
        <select id="dificultad" name="dificultad">
            <option value="facil">Fácil</option>
            <option value="normal">Normal</option>
            <option value="dificil">Difícil</option>
        </select>
    </div>
    
    <button type="submit">Guardar Configuración</button>
</form>