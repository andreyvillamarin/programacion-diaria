<?php
require_once '../includes/init.php';

// 1. Verificación de Seguridad
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 1) {
    die('Acceso Denegado');
}

// 2. Obtener y validar los parámetros de la URL
$tipo = $_GET['tipo'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($tipo)) {
    header('Location: index.php');
    exit;
}

// 3. Determinar la tabla y la página de redirección
$tabla = '';
$redirect_page = 'index.php';

switch ($tipo) {
    case 'area':
        // Comprobación de seguridad para no borrar el área fija
        $stmt = $pdo->prepare("SELECT nombre_area FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        if (strtolower($stmt->fetchColumn()) === 'otras áreas') {
            die('El área "Otras Áreas" es fija y no se puede eliminar.');
        }
        $tabla = 'areas';
        $redirect_page = 'areas.php';
        break;
    case 'persona':
        $tabla = 'personas';
        $redirect_page = 'personas.php';
        break;
    case 'usuario':
        if ($id == $_SESSION['user_id']) { die('No puedes eliminar tu propio usuario.'); }
        $tabla = 'usuarios';
        $redirect_page = 'usuarios.php';
        break;
    case 'detalle_programacion':
        $tabla = 'detalle_programacion';
        $redirect_page = 'index.php';
        break;
    default:
        header('Location: index.php');
        exit;
}

// 4. Ejecutar la consulta de eliminación
try {
    // Para las personas, se realiza un borrado lógico (soft delete)
    if ($tipo === 'persona') {
        $sql = "UPDATE personas SET activo = 0 WHERE id = ?";
    } else {
        // Para los demás tipos, se mantiene el borrado físico
        $sql = "DELETE FROM `$tabla` WHERE id = ?";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

} catch (PDOException $e) {
    // Si, a pesar de todo, ocurre un error, se muestra un mensaje genérico
    die("Error al procesar la solicitud: " . $e->getMessage() . " <a href='$redirect_page'>Volver</a>");
}

// 5. Redirigir de vuelta
header("Location: " . $redirect_page);
exit;
?>