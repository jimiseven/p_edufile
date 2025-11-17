<?php
session_start();
require_once '../config/database.php';
require_once 'report_generator.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$id_reporte = $_GET['id'] ?? 0;

if (!$id_reporte) {
    header('Location: reportes.php');
    exit();
}

// Eliminar reporte
$resultado = eliminarReporte($id_reporte);

if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Reporte eliminado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al eliminar el reporte: ' . $resultado['error'];
    $_SESSION['tipo_mensaje'] = 'error';
}

header('Location: reportes.php');
exit();
?>
