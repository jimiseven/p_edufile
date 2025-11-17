<?php
// Script simple para probar guardar reporte
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once 'report_generator.php';

session_start();
$_SESSION['user_id'] = 1; // Simular usuario admin
$_SESSION['user_role'] = 1;

$db = new Database();
$conn = $db->connect();

echo "<h1>Prueba Simple de Guardado</h1>";

// Datos de prueba
$nombre = "Reporte Test " . date('H:i:s');
$tipo_base = 'info_estudiantil';
$descripcion = 'Test';
$filtros = [];
$columnas = ['nombres', 'apellido_paterno'];

echo "<h2>Datos:</h2>";
echo "<pre>";
echo "Nombre: $nombre\n";
echo "Tipo: $tipo_base\n";
echo "Columnas: " . implode(', ', $columnas) . "\n";
echo "</pre>";

// Probar guardar
$resultado = guardarReporte($nombre, $tipo_base, $descripcion, $filtros, $columnas);

echo "<h2>Resultado:</h2>";
if ($resultado['success']) {
    echo "<div style='color: green;'>✅ Guardado con ID: " . $resultado['id_reporte'] . "</div>";
} else {
    echo "<div style='color: red;'>❌ Error: " . $resultado['error'] . "</div>";
}

// Verificar en BD
$stmt = $conn->query("SELECT COUNT(*) as total FROM reportes_guardados");
$count = $stmt->fetch();
echo "<h2>Total de reportes en BD: " . $count['total'] . "</h2>";
?>
