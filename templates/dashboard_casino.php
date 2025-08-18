<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Programación de Casino</h3>
            <div class="header-controls">
                <div class="form-group">
                    <label for="casino-date-selector">Seleccione una fecha:</label>
                    <input type="date" id="casino-date-selector" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <button id="download-casino-pdf-btn" class="btn btn-info"><i class="fas fa-file-pdf"></i> Descargar Reporte PDF</button>
            </div>
        </div>
        <div class="card-body" id="casino-dashboard-content">
            <p class="loading-placeholder">Cargando datos de programación...</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>