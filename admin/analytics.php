<?php
require_once '../includes/init.php';
// Solo rol Administrador (ID 1)
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

$page_title = "Analíticas y Reportes";
include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h4>Filtros de Reporte</h4></div>
        <div class="card-body inline-form">
            <div class="form-group"><label for="start-date">Fecha Inicio:</label><input type="date" id="start-date" value="<?= date('Y-m-01') ?>"></div>
            <div class="form-group"><label for="end-date">Fecha Fin:</label><input type="date" id="end-date" value="<?= date('Y-m-t') ?>"></div>
            <button id="generate-report-btn" class="btn">Generar Reporte</button>
            <button id="download-analytics-pdf-btn" class="btn btn-info"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
        </div>
    </div>
    
    <div class="analytics-grid">
        <div class="card">
            <div class="card-header"><h4>Consumo de Alimentos</h4></div>
            <div class="card-body chart-container"><canvas id="food-chart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Uso de Transporte</h4></div>
            <div class="card-body chart-container"><canvas id="transport-chart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Total de Sedes</h4></div>
            <div class="card-body chart-container"><canvas id="sedes-chart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Total de Áreas</h4></div>
            <div class="card-body chart-container"><canvas id="areas-chart"></canvas></div>
        </div>
        <div class="card full-width">
            <div class="card-header"><h4>Total de Personas</h4></div>
            <div class="card-body chart-container"><canvas id="personas-chart"></canvas></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include '../templates/footer.php'; ?>