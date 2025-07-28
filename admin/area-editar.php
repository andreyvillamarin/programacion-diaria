<?php
require_once '../includes/init.php';
// ⚙️ Solo el rol Administrador (ID 1) puede acceder
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

// Obtenemos el ID del área de la URL
$area_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$area_id) {
    header("Location: areas.php");
    exit;
}

// ⚙️ Procesar el formulario al guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_area = $_POST['nombre_area'];
    $activa = isset($_POST['activa']) ? 1 : 0;

    $stmt = $pdo->prepare(
        "UPDATE areas SET nombre_area = ?, activa = ? WHERE id = ?"
    );
    $stmt->execute([$nombre_area, $activa, $area_id]);

    header("Location: areas.php");
    exit;
}

// ⚙️ Obtener los datos actuales del área para el formulario
$stmt = $pdo->prepare("SELECT * FROM areas WHERE id = ?");
$stmt->execute([$area_id]);
$area = $stmt->fetch();

if (!$area) { // Si no se encuentra el área, volver
    header("Location: areas.php");
    exit;
}

$page_title = "Editar Área: " . htmlspecialchars($area['nombre_area']);
include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Editando el Área "<?= htmlspecialchars($area['nombre_area']) ?>"</h3>
        </div>
        <div class="card-body">
            <form action="area-editar.php?id=<?= $area_id ?>" method="POST">

                <div class="form-group">
                    <label for="nombre_area">Nombre del Área</label>
                    <input type="text" id="nombre_area" name="nombre_area" value="<?= htmlspecialchars($area['nombre_area']) ?>" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activa" value="1" <?= ($area['activa']) ? 'checked' : '' ?>>
                        Área Activa (los usuarios podrán verla en el formulario público)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    <a href="areas.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>