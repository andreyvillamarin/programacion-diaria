<?php
require_once '../includes/init.php';
// Solo los administradores pueden acceder a esta página
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 1) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}
$page_title = 'Reportes';
require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-truck"></i> Reporte de Transporte</h3>
        </div>
        <div class="card-body">
            <p>Descargue el reporte consolidado de servicios de transporte para una fecha específica.</p>
            <div class="form-group">
                <label for="transport-date">Seleccione una fecha:</label>
                <input type="date" id="transport-date" class="form-control" style="max-width: 250px;" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <button id="download-transport-pdf" class="btn btn-primary mt-2">Descargar PDF</button>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-utensils"></i> Reporte de Casino - Sede Betania</h3>
        </div>
        <div class="card-body">
            <p>Descargue el reporte de servicios de alimentación para la sede de Betania en una fecha específica.</p>
            <div class="form-group">
                <label for="casino-betania-date">Seleccione una fecha:</label>
                <input type="date" id="casino-betania-date" class="form-control" style="max-width: 250px;" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <button id="download-casino-betania-pdf" class="btn btn-primary mt-2">Descargar PDF</button>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-utensils"></i> Reporte de Casino - Sede Quimbo</h3>
        </div>
        <div class="card-body">
            <p>Descargue el reporte de servicios de alimentación para la sede de Quimbo en una fecha específica.</p>
            <div class="form-group">
                <label for="casino-quimbo-date">Seleccione una fecha:</label>
                <input type="date" id="casino-quimbo-date" class="form-control" style="max-width: 250px;" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <button id="download-casino-quimbo-pdf" class="btn btn-primary mt-2">Descargar PDF</button>
        </div>
    </div>
</div>

<?php
require_once '../templates/footer.php';
?>
