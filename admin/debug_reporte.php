<?php
session_start();
require_once '../config/database.php';
require_once 'report_generator.php';

// Simular la carga de un reporte
$id_reporte = 1; // Cambiar este ID por uno existente

echo "<h2>Depuración de Reporte ID: $id_reporte</h2>";

// Cargar reporte guardado
$datos_reporte = cargarReporteGuardado($id_reporte);

if (!$datos_reporte) {
    echo "<p>Reporte no encontrado</p>";
    exit();
}

$reporte = $datos_reporte['reporte'];
$filtros = $datos_reporte['filtros'];
$columnas = $datos_reporte['columnas'];
$tipo_base = $reporte['tipo_base'];

echo "<h3>Información del Reporte</h3>";
echo "<pre>" . print_r($reporte, true) . "</pre>";

echo "<h3>Filtros</h3>";
echo "<pre>" . print_r($filtros, true) . "</pre>";

echo "<h3>Columnas (en orden)</h3>";
echo "<pre>" . print_r($columnas, true) . "</pre>";

echo "<h3>Consulta SQL</h3>";
$consulta = construirConsultaSQL($filtros, $columnas, $tipo_base);
echo "<p><strong>SQL:</strong> " . htmlspecialchars($consulta['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong> " . print_r($consulta['params'], true) . "</p>";

// Ejecutar consulta
try {
    $conn = (new Database())->connect();
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Resultados (" . count($resultados) . " filas)</h3>";
    if (!empty($resultados)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach ($columnas as $col) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        
        foreach ($resultados as $fila) {
            echo "<tr>";
            foreach ($columnas as $col) {
                echo "<td>" . htmlspecialchars($fila[$col] ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
