<?php
require_once '../config/database.php';
require_once 'includes/report_functions.php';
require_once 'report_generator.php';

session_start();

// Verificar si hay un ID de reporte
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<h2>Error: No se proporcionó ID de reporte</h2>";
    echo "<a href='reportes.php'>Volver a Reportes</a>";
    exit;
}

$id_reporte = $_GET['id'];

echo "<h2>🔧 Depuración de ver_reporte.php</h2>";
echo "<p><strong>ID del Reporte:</strong> $id_reporte</p>";

try {
    // Cargar datos del reporte guardado
    $datos_reporte = cargarReporteGuardado($id_reporte);
    
    if (!$datos_reporte) {
        echo "<div class='alert alert-danger'>❌ El reporte no existe</div>";
        exit;
    }
    
    echo "<h3>✅ Reporte cargado correctamente</h3>";
    
    // Verificar estructura de datos
    echo "<h4>📋 Estructura de datos recibida:</h4>";
    echo "<ul>";
    echo "<li><strong>Tiene 'reporte'?</strong> " . (isset($datos_reporte['reporte']) ? "✅ Sí" : "❌ No") . "</li>";
    echo "<li><strong>Tiene 'filtros'?</strong> " . (isset($datos_reporte['filtros']) ? "✅ Sí" : "❌ No") . "</li>";
    echo "<li><strong>Tiene 'columnas'?</strong> " . (isset($datos_reporte['columnas']) ? "✅ Sí" : "❌ No") . "</li>";
    echo "<li><strong>Tiene 'resultados'?</strong> " . (isset($datos_reporte['resultados']) ? "✅ Sí" : "❌ No") . "</li>";
    echo "</ul>";
    
    // Extraer datos como lo hace ver_reporte.php
    $reporte = $datos_reporte['reporte'];
    $filtros = $datos_reporte['filtros'];
    $columnas = $datos_reporte['columnas'];
    $tipo_base = $reporte['tipo_base'];
    
    echo "<h4>📊 Información del Reporte:</h4>";
    echo "<ul>";
    echo "<li><strong>Nombre:</strong> " . htmlspecialchars($reporte['nombre'] ?? 'SIN NOMBRE') . "</li>";
    echo "<li><strong>Descripción:</strong> " . htmlspecialchars($reporte['descripcion'] ?? 'Sin descripción') . "</li>";
    echo "<li><strong>Tipo Base:</strong> " . htmlspecialchars($tipo_base) . "</li>";
    echo "<li><strong>Fecha Creación:</strong> " . ($reporte['fecha_creacion'] ?? 'SIN FECHA') . "</li>";
    echo "<li><strong>Fecha Modificación:</strong> " . ($reporte['fecha_modificacion'] ?? 'SIN FECHA') . "</li>";
    echo "<li><strong>Creador:</strong> " . htmlspecialchars(($reporte['nombres'] ?? '') . ' ' . ($reporte['apellidos'] ?? '')) . "</li>";
    echo "</ul>";
    
    echo "<h4>🔍 Filtros (" . count($filtros) . "):</h4>";
    if (empty($filtros)) {
        echo "<p><em>No hay filtros aplicados</em></p>";
    } else {
        echo "<pre>" . print_r($filtros, true) . "</pre>";
    }
    
    echo "<h4>📑 Columnas (" . count($columnas) . "):</h4>";
    if (empty($columnas)) {
        echo "<p><em>⚠️ No hay columnas seleccionadas - se usarán columnas por defecto</em></p>";
    } else {
        echo "<ul>";
        foreach ($columnas as $columna) {
            echo "<li>" . htmlspecialchars($columna) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<h4>📈 Resultados (" . count($datos_reporte['resultados']) . "):</h4>";
    if (empty($datos_reporte['resultados'])) {
        echo "<div class='alert alert-warning'>⚠️ No se encontraron resultados</div>";
    } else {
        echo "<div class='alert alert-success'>✅ Se encontraron " . count($datos_reporte['resultados']) . " resultados</div>";
    }
    
    // Probar la consulta SQL
    echo "<h4>🧪 Probando consulta SQL...</h4>";
    $consulta = construirConsultaSQL($filtros, $columnas, $tipo_base);
    
    echo "<h5>SQL generado:</h5>";
    echo "<pre>" . htmlspecialchars($consulta['sql']) . "</pre>";
    
    echo "<h5>Parámetros:</h5>";
    echo "<pre>" . print_r($consulta['params'], true) . "</pre>";
    
    try {
        $stmt = $conn->prepare($consulta['sql']);
        $stmt->execute($consulta['params']);
        $resultados_prueba = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h5>✅ Consulta ejecutada correctamente - Resultados: " . count($resultados_prueba) . "</h5>";
        
        if (!empty($resultados_prueba) && count($resultados_prueba) <= 3) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            if (!empty($columnas)) {
                foreach ($columnas as $col) {
                    echo "<th>" . htmlspecialchars($col) . "</th>";
                }
            } else {
                echo "<th>Nombres</th><th>Apellido Paterno</th><th>Apellido Materno</th>";
            }
            echo "</tr>";
            
            foreach ($resultados_prueba as $fila) {
                echo "<tr>";
                if (!empty($columnas)) {
                    foreach ($columnas as $col) {
                        echo "<td>" . htmlspecialchars($fila[$col] ?? '') . "</td>";
                    }
                } else {
                    echo "<td>" . htmlspecialchars($fila['nombres'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($fila['apellido_paterno'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($fila['apellido_materno'] ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Error en consulta: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<br><br>";
    echo "<div class='actions'>";
    echo "<a href='ver_reporte.php?id=$id_reporte' class='btn btn-primary'>👁️ Ver Reporte</a>";
    echo "<a href='download_report_excel.php?id=$id_reporte' class='btn btn-success'>📊 Descargar Excel</a>";
    echo "<a href='reportes.php' class='btn btn-secondary'>← Volver a Reportes</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error general: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
.alert {
    padding: 10px 15px;
    margin: 10px 0;
    border-radius: 4px;
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.btn {
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    color: white;
    display: inline-block;
    margin-right: 10px;
    margin-bottom: 10px;
}
.btn-primary {
    background-color: #007bff;
}
.btn-success {
    background-color: #28a745;
}
.btn-secondary {
    background-color: #6c757d;
}
.actions {
    margin-top: 20px;
}
table {
    border-collapse: collapse;
    margin: 10px 0;
}
th, td {
    border: 1px solid #ddd;
    padding: 8px;
}
th {
    background-color: #f2f2f2;
    font-weight: bold;
}
pre {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
}
h4 {
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 5px;
}
</style>
