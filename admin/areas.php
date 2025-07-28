<?php
require_once '../includes/init.php';
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_area' && !empty($_POST['nombre_area'])) {
        $stmt = $pdo->prepare("INSERT INTO areas (nombre_area) VALUES (?)");
        $stmt->execute([$_POST['nombre_area']]);
    }
    header("Location: areas.php");
    exit;
}

$page_title = "Gestión de Áreas";
include '../templates/header.php';

$areas = $pdo->query("SELECT * FROM areas ORDER BY nombre_area")->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Añadir Nueva Área</h3>
        </div>
        <div class="card-body">
            <form action="areas.php" method="POST" class="inline-form">
                <input type="hidden" name="action" value="add_area">
                <div class="form-group">
                    <input type="text" name="nombre_area" placeholder="Nombre del área" required>
                </div>
                <button type="submit" class="btn">Añadir Área</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Áreas Existentes</h3>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Área</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $area): ?>
                    <tr>
                        <td><?= $area['id'] ?></td>
                        <td><?= htmlspecialchars($area['nombre_area']) ?></td>
                        <td><?= $area['activa'] ? 'Activa' : 'Inactiva' ?></td>
                        <td>
                            <?php if (strtolower($area['nombre_area']) !== 'otras áreas'): ?>
                                <a href="area-editar.php?id=<?= $area['id'] ?>" class="btn btn-sm">Editar</a>
                                <a href="eliminar.php?tipo=area&id=<?= $area['id'] ?>" class="btn btn-sm btn-danger delete-btn">Eliminar</a>
                            <?php else: ?>
                                <span>(Área Fija)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>