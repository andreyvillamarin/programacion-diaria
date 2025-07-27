<?php include 'header.php'; ?>

<div class="dashboard-controls">
    <div class="form-group">
        <label for="date-selector">Seleccionar fecha de programación:</label>
        <input type="date" id="date-selector" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
    </div>
    <div class="control-buttons">
        <button id="add-manual-btn" class="btn btn-secondary"><i class="fas fa-plus"></i> Añadir Registro Manual</button>
        <button id="finalize-day-btn" class="btn btn-success"><i class="fas fa-check-circle"></i> Finalizar y Enviar Reportes</button>
    </div>
</div>

<div id="dashboard-content">
    <p class="loading-placeholder">Cargando datos de programación...</p>
</div>

<?php include 'footer.php'; ?>