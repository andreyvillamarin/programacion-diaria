<?php
require_once '../includes/init.php';
// ⚙️ Solo el rol Administrador (ID 1) puede acceder
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

// Obtenemos el ID de la persona de la URL
$person_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$person_id) {
    header("Location: personas.php");
    exit;
}

// ⚙️ Procesar el formulario al guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = $_POST['nombre_completo'];
    $id_area = $_POST['id_area'];
    $zona = $_POST['zona'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = $pdo->prepare(
        "UPDATE personas SET nombre_completo = ?, id_area = ?, zona = ?, activo = ? WHERE id = ?"
    );
    $stmt->execute([$nombre_completo, $id_area, $zona, $activo, $person_id]);

    header("Location: personas.php");
    exit;
}

// ⚙️ Obtener los datos actuales de la persona para el formulario
$stmt = $pdo->prepare("SELECT * FROM personas WHERE id = ?");
$stmt->execute([$person_id]);
$persona = $stmt->fetch();

if (!$persona) { // Si no se encuentra la persona, volver
    header("Location: personas.php");
    exit;
}

// Obtener todas las áreas activas para el dropdown
$areas_activas = $pdo->query("SELECT id, nombre_area FROM areas WHERE activa = 1 ORDER BY nombre_area")->fetchAll();

$page_title = "Editar Persona: " . htmlspecialchars($persona['nombre_completo']);
include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Editando a "<?= htmlspecialchars($persona['nombre_completo']) ?>"</h3>
        </div>
        <div class="card-body">
            <form action="persona-editar.php?id=<?= $person_id ?>" method="POST">

                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($persona['nombre_completo']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="id_area">Área</label>
                    <select name="id_area" id="id_area" required>
                        <option value="">-- Seleccionar un Área --</option>
                        <?php foreach ($areas_activas as $area): ?>
                            <option value="<?= $area['id'] ?>" <?= ($area['id'] == $persona['id_area']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($area['nombre_area']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="zona">Zona</label>
                    <input type="text" id="zona" name="zona" value="<?= htmlspecialchars($persona['zona']) ?>" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" value="1" <?= ($persona['activo']) ? 'checked' : '' ?>>
                        Persona Activa
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    <a href="personas.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>