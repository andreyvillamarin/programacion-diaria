<?php
require_once '../includes/init.php';
// ⚙️ Solo el rol Administrador (ID 1) puede acceder a esta página
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

// Obtenemos el ID del usuario de la URL y lo validamos
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: usuarios.php");
    exit;
}

// ⚙️ Lógica para procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $id_rol = $_POST['id_rol'];
    $id_sede = ($id_rol == 2) ? $_POST['id_sede'] : null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    $sql = "UPDATE usuarios SET nombre = ?, email = ?, id_rol = ?, id_sede = ?, activo = ?";
    $params = [$nombre, $email, $id_rol, $id_sede, $activo];

    // Solo actualiza la contraseña si se ingresó una nueva
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $password;
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Redirigimos de vuelta a la lista de usuarios
    header("Location: usuarios.php");
    exit;
}


// ⚙️ Obtener los datos actuales del usuario para rellenar el formulario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) { // Si el usuario no existe, volver a la lista
    header("Location: usuarios.php");
    exit;
}

// Obtener listas de roles y sedes para los dropdowns
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();

$page_title = "Editar Usuario: " . htmlspecialchars($user['nombre']);
include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Editando a "<?= htmlspecialchars($user['nombre']) ?>"</h3>
        </div>
        <div class="card-body">
            <form action="usuario-editar.php?id=<?= $user_id ?>" method="POST" id="edit-user-form">

                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                    <input type="password" id="password" name="password" placeholder="••••••••••">
                </div>

                <div class="form-group">
                    <label for="role-select">Rol</label>
                    <select name="id_rol" id="role-select" required>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>" <?= ($rol['id'] == $user['id_rol']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group <?= ($user['id_rol'] != 2) ? 'hidden' : '' ?>" id="sede-select-group">
                    <label for="sede-select">Sede Asignada (solo para Chefs)</label>
                    <select name="id_sede" id="sede-select">
                        <option value="">-- Ninguna --</option>
                         <?php foreach ($sedes as $sede): ?>
                            <option value="<?= $sede['id'] ?>" <?= ($sede['id'] == $user['id_sede']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sede['nombre_sede']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" value="1" <?= ($user['activo']) ? 'checked' : '' ?>>
                        Usuario Activo
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// Muestra/oculta el selector de sede si el rol cambia a "Chef"
document.getElementById('role-select').addEventListener('change', function() {
    const sedeGroup = document.getElementById('sede-select-group');
    if (this.value == '2') { // ID del rol "Chef"
        sedeGroup.classList.remove('hidden');
    } else {
        sedeGroup.classList.add('hidden');
    }
});
</script>

<?php include '../templates/footer.php'; ?>