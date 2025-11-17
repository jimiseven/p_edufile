<?php
/**
 * Script de prueba para verificar el funcionamiento del generador de reportes
 */

session_start();
require_once '../config/database.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    echo "Acceso denegado. Debes ser administrador.";
    exit();
}

$db = new Database();
$conn = $db->connect();

echo "<h2>Prueba del Generador de Reportes</h2>";

// 1. Verificar conexión a la base de datos
if ($conn) {
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
} else {
    echo "<p style='color: red;'>✗ Error de conexión a la base de datos</p>";
    exit();
}

// 2. Verificar tablas necesarias
$tablas_necesarias = ['reportes_guardados', 'reportes_guardados_columnas', 'reportes_guardados_filtros', 'estudiantes', 'cursos', 'personal'];

foreach ($tablas_necesarias as $tabla) {
    $stmt = $conn->query("SHOW TABLES LIKE '$tabla'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabla '$tabla' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla '$tabla' no existe</p>";
    }
}

// 3. Verificar si hay datos en las tablas principales
$stmt = $conn->query("SELECT COUNT(*) as total FROM estudiantes");
$estudiantes = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Estudiantes en la base de datos: <strong>{$estudiantes['total']}</strong></p>";

$stmt = $conn->query("SELECT COUNT(*) as total FROM cursos");
$cursos = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Cursos en la base de datos: <strong>{$cursos['total']}</strong></p>";

$stmt = $conn->query("SELECT COUNT(*) as total FROM reportes_guardados");
$reportes = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Reportes guardados: <strong>{$reportes['total']}</strong></p>";

// 4. Probar generación de un reporte simple
echo "<h3>Prueba de Generación de Reporte</h3>";

require_once 'report_generator.php';

// Datos de prueba
$filtros_prueba = [
    'genero' => 'Masculino',
    'nivel' => ['Primaria', 'Secundaria']
];

$columnas_prueba = ['nombres', 'apellido_paterno', 'genero', 'nivel'];

try {
    $consulta = construirConsultaSQL($filtros_prueba, $columnas_prueba, 'info_estudiantil');
    echo "<p style='color: green;'>✓ Función construirConsultaSQL funciona</p>";
    echo "<p>SQL generado: <code>" . htmlspecialchars($consulta['sql']) . "</code></p>";
    echo "<p>Parámetros: <pre>" . print_r($consulta['params'], true) . "</pre></p>";
    
    // Ejecutar la consulta
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ Consulta ejecutada exitosamente</p>";
    echo "<p>Resultados encontrados: <strong>" . count($resultados) . "</strong></p>";
    
    if (!empty($resultados)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Nombres</th><th>Apellido Paterno</th><th>Género</th><th>Nivel</th></tr>";
        foreach (array_slice($resultados, 0, 5) as $fila) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fila['nombres'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['apellido_paterno'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['genero'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['nivel'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        if (count($resultados) > 5) {
            echo "<p><em>Mostrando primeros 5 resultados de " . count($resultados) . " totales</em></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error en la generación: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 5. Probar guardado de reporte
echo "<h3>Prueba de Guardado de Reporte</h3>";

try {
    $nombre_prueba = "Reporte de Prueba " . date('Y-m-d H:i:s');
    $resultado = guardarReporte($nombre_prueba, 'info_estudiantil', 'Reporte de prueba', $filtros_prueba, $columnas_prueba);
    
    if ($resultado['success']) {
        echo "<p style='color: green;'>✓ Reporte guardado exitosamente</p>";
        echo "<p>ID del reporte: <strong>{$resultado['id_reporte']}</strong></p>";
        
        // Verificar que se guardó correctamente
        $stmt = $conn->prepare("SELECT * FROM reportes_guardados WHERE id_reporte = ?");
        $stmt->execute([$resultado['id_reporte']]);
        $reporte_guardado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reporte_guardado) {
            echo "<p style='color: green;'>✓ Reporte verificado en la base de datos</p>";
            
            // Verificar columnas guardadas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reportes_guardados_columnas WHERE id_reporte = ?");
            $stmt->execute([$resultado['id_reporte']]);
            $columnas_guardadas = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Columnas guardadas: <strong>{$columnas_guardadas['total']}</strong></p>";
            
            // Verificar filtros guardados
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reportes_guardados_filtros WHERE id_reporte = ?");
            $stmt->execute([$resultado['id_reporte']]);
            $filtros_guardados = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Filtros guardados: <strong>{$filtros_guardados['total']}</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ No se encontró el reporte guardado</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error al guardar reporte: " . htmlspecialchars($resultado['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error en el guardado: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='constructor_reporte.php?tipo=info_estudiantil'>Ir al Constructor de Reportes</a></p>";
echo "<p><a href='reportes.php'>Ir a la Lista de Reportes</a></p>";

?>
