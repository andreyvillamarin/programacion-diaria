<?php
require_once '../includes/init.php';
// ⚙️ Solo el rol Administrador (ID 1) puede acceder
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

// Obtenemos el ID del detalle de la URL
$detail_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$detail_id) {
    header("Location: index.php");
    exit;
}

// ⚙️ Procesar el formulario al guardar los cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sede = $_POST['id_sede'];
    $desayuno = isset($_POST['desayuno']) ? 1 : 0;
    $almuerzo = isset($_POST['almuerzo']) ? 1 : 0;
    $comida = isset($_POST['comida']) ? 1 : 0;
    $refrigerio_tipo1 = isset($_POST['refrigerio_tipo1']) ? 1 : 0;
    $refrigerio_capacitacion = isset($_POST['refrigerio_capacitacion']) ? 1 : 0;
    $transporte_tipo = $_POST['transporte_tipo'];

    // Si es un registro de "otras áreas", también actualizamos los campos de texto
    if (isset($_POST['nombre_manual'])) {
        $nombre_manual = $_POST['nombre_manual'];
        $area_wbe = $_POST['area_wbe'];
        $actividad = $_POST['actividad'];
        $stmt = $pdo->prepare(
            "UPDATE detalle_programacion SET id_sede = ?, desayuno = ?, almuerzo = ?, comida = ?, refrigerio_tipo1 = ?, refrigerio_capacitacion = ?, transporte_tipo = ?, nombre_manual = ?, area_wbe = ?, actividad = ? WHERE id = ?"
        );
        $stmt->execute([$id_sede, $desayuno, $almuerzo, $comida, $refrigerio_tipo1, $refrigerio_capacitacion, $transporte_tipo, $nombre_manual, $area_wbe, $actividad, $detail_id]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE detalle_programacion SET id_sede = ?, desayuno = ?, almuerzo = ?, comida = ?, refrigerio_tipo1 = ?, refrigerio_capacitacion = ?, transporte_tipo = ? WHERE id = ?"
        );
        $stmt->execute([$id_sede, $desayuno, $almuerzo, $comida, $refrigerio_tipo1, $refrigerio_capacitacion, $transporte_tipo, $detail_id]);
    }

    header("Location: index.php");
    exit;
}

// ⚙️ Obtener los datos actuales del detalle para el formulario
$stmt = $pdo->prepare("SELECT dp.*, p.nombre_completo FROM detalle_programacion dp LEFT JOIN personas p ON dp.id_persona = p.id WHERE dp.id = ?");
$stmt->execute([$detail_id]);
$detalle = $stmt->fetch();

if (!$detalle) { // Si no se encuentra el detalle, volver
    header("Location: index.php");
    exit;
}

$person_name = $detalle['id_persona'] ? $detalle['nombre_completo'] : $detalle['nombre_manual'];

// Obtener todas las sedes activas para el dropdown
$sedes_activas = $pdo->query("SELECT id, nombre_sede FROM sedes ORDER BY nombre_sede")->fetchAll();
$transport_options = ["Ruta Ordinaria Diurna", "Ruta Ordinaria Nocturna", "Ruta Operación Diurna", "Ruta Operación Nocturna", "Ruta Cambio Ing. Disponible", "Camioneta Renting", "Ingeniero Disponible", "Vehículo Propio", "No requiere", "Otro"];

$page_title = "Editar Registro de Programación";
include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Editando Registro de <?= htmlspecialchars($person_name) ?></h3>
        </div>
        <div class="card-body">
            <form action="registro-editar.php?id=<?= $detail_id ?>" method="POST">

                <?php if (!$detalle['id_persona']): ?>
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre_manual" value="<?= htmlspecialchars($detalle['nombre_manual']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Área | WBE</label>
                        <input type="text" name="area_wbe" value="<?= htmlspecialchars($detalle['area_wbe']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Actividad a Realizar</label>
                        <textarea name="actividad" required><?= htmlspecialchars($detalle['actividad']) ?></textarea>
                    </div>
                <?php endif; ?>

                <div class="service-section">
                    <h5><i class="fas fa-utensils"></i> Alimentación</h5>
                    <label><input type="checkbox" name="desayuno" value="1" <?= $detalle['desayuno'] ? 'checked' : '' ?>> Desayuno</label>
                    <label><input type="checkbox" name="almuerzo" value="1" <?= $detalle['almuerzo'] ? 'checked' : '' ?>> Almuerzo</label>
                    <label><input type="checkbox" name="comida" value="1" <?= $detalle['comida'] ? 'checked' : '' ?>> Comida</label>
                    <label><input type="checkbox" name="refrigerio_tipo1" value="1" <?= $detalle['refrigerio_tipo1'] ? 'checked' : '' ?>> Refrigerio Tipo 1</label>
                    <label><input type="checkbox" name="refrigerio_capacitacion" value="1" <?= $detalle['refrigerio_capacitacion'] ? 'checked' : '' ?>> Refrigerio Capacitación</label>
                </div>

                <div class="service-section">
                    <h5><i class="fas fa-bus"></i> Transporte</h5>
                    <label for="transporte_tipo">Tipo:</label>
                    <select id="transporte_tipo" name="transporte_tipo">
                        <?php foreach ($transport_options as $option): ?>
                            <option value="<?= $option ?>" <?= ($option == $detalle['transporte_tipo']) ? 'selected' : '' ?>><?= $option ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="service-section">
                     <h5><i class="fas fa-map-marker-alt"></i> Sede de Destino</h5>
                     <?php foreach ($sedes_activas as $sede): ?>
                        <label class="radio-label"><input type="radio" name="id_sede" value="<?= $sede['id'] ?>" <?= ($sede['id'] == $detalle['id_sede']) ? 'checked' : '' ?>> <?= $sede['nombre_sede'] ?></label>
                     <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>