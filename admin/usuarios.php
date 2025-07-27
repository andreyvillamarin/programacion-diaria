<?php
require_once '../includes/init.php';
// Solo rol Administrador (ID 1)
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

// Lógica para manejar las acciones (añadir, editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $id_rol = $_POST['id_rol'];
        // La sede solo se asigna si el rol es Chef (ID 2)
        $id_sede = ($id_rol == 2) ? $_POST['id_sede'] : null;

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, id_rol, id_sede) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password, $id_rol, $id_sede]);
    }
    header("Location: usuarios.php");
    exit;
}

$page_title = "Gestión de Usuarios";
include '../templates/header.php';

// Obtener datos para la vista
$usuarios = $pdo->query(
    "SELECT u.id, u.nombre, u.email, u.activo, r.nombre_rol, s.nombre_sede 
     FROM usuarios u 
     JOIN roles r ON u.id_rol = r.id
     LEFT JOIN sedes s ON u.id_sede = s.id
     ORDER BY u.nombre"
)->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h3>Añadir Nuevo Usuario</h3></div>
        <div class="card-body">
            <form action="usuarios.php" method="POST" class="inline-form" id="add-user-form">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group"><input type="text" name="nombre" placeholder="Nombre completo" required></div>
                <div class="form-group"><input type="email" name="email" placeholder="Correo electrónico" required></div>
                <div class="form-group"><input type="password" name="password" placeholder="Contraseña" required></div>
                <div class="form-group">
                    <select name="id_rol" id="role-select" required>
                        <option value="">-- Seleccionar Rol --</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group hidden" id="sede-select-group">
                    <select name="id_sede">
                        <option value="">-- Asignar Sede al Chef --</option>
                         <?php foreach ($sedes as $sede): ?>
                            <option value="<?= $sede['id'] ?>"><?= htmlspecialchars($sede['nombre_sede']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Añadir Usuario</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Usuarios del Sistema</h3></div>
        <div class="card-body">
            <table class="data-table">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Sede Asignada</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td><?= htmlspecialchars($usuario['nombre_rol']) ?></td>
                        <td><?= htmlspecialchars($usuario['nombre_sede'] ?? 'N/A') ?></td>
                        <td><?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?></td>
                        <td><a href="#" class="btn btn-sm">Editar</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Script para mostrar el selector de sede solo si el rol es "Chef" (ID 2)
document.getElementById('role-select').addEventListener('change', function() {
    const sedeGroup = document.getElementById('sede-select-group');
    if (this.value == '2') { // ID del rol "Chef"
        sedeGroup.classList.remove('hidden');
        sedeGroup.querySelector('select').required = true;
    } else {
        sedeGroup.classList.add('hidden');
        sedeGroup.querySelector('select').required = false;
    }
});
</script>

<?php include '../templates/footer.php'; ?>