<?php include 'header.php'; ?>

<?php
$fecha_reporte = date('Y-m-d', strtotime('+1 day'));
$stmt = $pdo->prepare(
    "SELECT p.nombre_completo, s.nombre_sede, dp.transporte_tipo
     FROM detalle_programacion dp
     JOIN programaciones pr ON dp.id_programacion = pr.id
     JOIN personas p ON dp.id_persona = p.id
     JOIN sedes s ON dp.id_sede = s.id
     WHERE pr.fecha_programacion = ? AND dp.transporte_tipo != 'No requiere'
     ORDER BY s.nombre_sede, p.nombre_completo"
);
$stmt->execute([$fecha_reporte]);
$reporte = $stmt->fetchAll();
?>
<div class="container-fluid">
    <h3>Rutas para mañana (<?= $fecha_reporte ?>)</h3>
    <div class="card">
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr><th>Persona</th><th>Tipo de Ruta</th><th>Sede Destino</th></tr>
                </thead>
                <tbody>
                    <?php if ($reporte): ?>
                        <?php foreach ($reporte as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($row['transporte_tipo']) ?></td>
                            <td><?= htmlspecialchars($row['nombre_sede']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No hay rutas programadas para la fecha indicada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>