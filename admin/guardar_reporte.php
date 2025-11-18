<?php
require_once '../config/database.php';
require_once 'includes/report_functions.php';
require_once 'report_generator.php';

session_start();

// Verificar si hay un ID de reporte
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'No se proporcionó ID de reporte';
    header('Location: reportes.php');
    exit;
}

$id_reporte = $_GET['id'];

try {
    // Cargar datos del reporte existente
    $datos_reporte = cargarReporteGuardado($id_reporte);
    
    if (!$datos_reporte) {
        $_SESSION['error'] = 'El reporte no existe';
        header('Location: reportes.php');
        exit;
    }
    
    // Extraer datos del reporte
    $reporte = $datos_reporte['reporte'];
    $filtros = $datos_reporte['filtros'];
    $columnas = $datos_reporte['columnas'];
    $tipo_base = $reporte['tipo_base'];
    
    // Generar nombre único para la copia
    $nombre_original = $reporte['nombre'];
    $nombre_copia = $nombre_original . ' - Copia ' . date('d/m/Y H:i');
    $descripcion = 'Copia del reporte "' . $nombre_original . '" creada el ' . date('d/m/Y H:i');
    
    // Guardar la copia usando la función existente
    $resultado = guardarReporte($nombre_copia, $tipo_base, $descripcion, $filtros, $columnas);
    
    if ($resultado['success']) {
        $_SESSION['success'] = 'Reporte guardado como copia exitosamente. ID: ' . $resultado['id_reporte'];
        header('Location: reportes.php');
        exit;
    } else {
        $_SESSION['error'] = 'Error al guardar la copia: ' . $resultado['error'];
        header('Location: ver_reporte.php?id=' . $id_reporte);
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error general: ' . $e->getMessage();
    header('Location: ver_reporte.php?id=' . $id_reporte);
    exit;
}
?>
