<?php
require_once '../includes/init.php';
// Solo rol Administrador (ID 1)
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

// Lógica para manejar las acciones (añadir, editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_person' && !empty($_POST['nombre_completo']) && !empty($_POST['id_area'])) {
        $stmt = $pdo->prepare("INSERT INTO personas (nombre_completo, id_area) VALUES (?, ?)");
        $stmt->execute([$_POST['nombre_completo'], $_POST['id_area']]);
    }
    header("Location: personas.php");
    exit;
}

$page_title = "Gestión de Personas";
include '../templates/header.php';

// Obtener datos para la vista
$personas = $pdo->query(
    "SELECT p.id, p.nombre_completo, p.activo, a.nombre_area 
     FROM personas p JOIN areas a ON p.id_area = a.id 
     ORDER BY p.nombre_completo"
)->fetchAll();
$areas_activas = $pdo->query("SELECT id, nombre_area FROM areas WHERE activa = 1 ORDER BY nombre_area")->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h3>Añadir Nueva Persona</h3></div>
        <div class="card-body">
            <form action="personas.php" method="POST" class="inline-form">
                <input type="hidden" name="action" value="add_person">
                <div class="form-group">
                    <input type="text" name="nombre_completo" placeholder="Nombre completo" required>
                </div>
                <div class="form-group">
                    <select name="id_area" required>
                        <option value="">-- Asignar a un Área --</option>
                        <?php foreach ($areas_activas as $area): ?>
                            <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nombre_area']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Añadir Persona</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Listado de Personal</h3>
            <div class="form-group">
                <input type="text" id="person-search" placeholder="Buscar por nombre...">
            </div>
        </div>
        <div class="card-body">
            <table class="data-table" id="personas-table">
                <thead><tr><th>Nombre Completo</th><th>Área</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td><?= htmlspecialchars($persona['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($persona['nombre_area']) ?></td>
                        <td><?= $persona['activo'] ? 'Activo' : 'Inactivo' ?></td>
<td>
    <a href="persona-editar.php?id=<?= $persona['id'] ?>" class="btn btn-sm">Editar</a>
    <a href="eliminar.php?tipo=persona&id=<?= $persona['id'] ?>" class="btn btn-sm btn-danger delete-btn">Eliminar</a>
</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>