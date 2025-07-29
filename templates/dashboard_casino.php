<?php include 'header.php'; ?>

<?php
$sede_id = $_SESSION['user_sede'] ?? 0;
if (!$sede_id) {
    echo "<div class='error-message'>Su usuario no está asignado a ninguna sede. Contacte al administrador.</div>";
} else {
    // Obtener datos para la sede de este chef para el día siguiente
    $fecha_reporte = date('Y-m-d', strtotime('+1 day'));
    $stmt = $pdo->prepare(
        "SELECT 
            SUM(dp.desayuno) as total_desayunos, SUM(dp.almuerzo) as total_almuerzos,
            SUM(dp.comida) as total_comidas, SUM(dp.refrigerio_tipo1) as total_ref1,
            SUM(dp.refrigerio_capacitacion) as total_ref_cap,
            GROUP_CONCAT(DISTINCT p.nombre_completo ORDER BY p.nombre_completo SEPARATOR '<br>') as personas
         FROM detalle_programacion dp
         JOIN programaciones pr ON dp.id_programacion = pr.id
         JOIN personas p ON dp.id_persona = p.id
         WHERE pr.fecha_programacion = ? AND dp.id_sede = ?
         GROUP BY dp.id_sede"
    );
    $stmt->execute([$fecha_reporte, $sede_id]);
    $reporte = $stmt->fetch();
}
?>

<div class="container-fluid">
    <h3>Programación para mañana (<?= $fecha_reporte ?>)</h3>
    <?php if (isset($reporte) && $reporte): ?>
        <div class="dashboard-cards">
            <div class="stat-card"><h4>Desayunos</h4><span><?= $reporte['total_desayunos'] ?></span></div>
            <div class="stat-card"><h4>Almuerzos</h4><span><?= $reporte['total_almuerzos'] ?></span></div>
            <div class="stat-card"><h4>Comidas</h4><span><?= $reporte['total_comidas'] ?></span></div>
            <div class="stat-card"><h4>Refrig. Tipo 1</h4><span><?= $reporte['total_ref1'] ?></span></div>
            <div class="stat-card"><h4>Refrig. Capacit.</h4><span><?= $reporte['total_ref_cap'] ?></span></div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Listado de Personal</h4></div>
            <div class="card-body"><p><?= $reporte['personas'] ?></p></div>
        </div>
    <?php else: ?>
        <p>No hay programación de alimentación para su sede en la fecha indicada.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>