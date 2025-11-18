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

echo "<h2>Depuración de Carga de Reporte</h2>";
echo "<p><strong>ID del Reporte:</strong> $id_reporte</p>";

try {
    // Cargar datos del reporte guardado
    $reporte = cargarReporteGuardado($id_reporte);
    
    if (!$reporte) {
        echo "<div class='alert alert-danger'>El reporte no existe</div>";
        exit;
    }
    
    echo "<h3>✅ Reporte cargado correctamente</h3>";
    
    echo "<h4>Información del Reporte:</h4>";
    echo "<ul>";
    echo "<li><strong>Nombre:</strong> " . htmlspecialchars($reporte['nombre']) . "</li>";
    echo "<li><strong>Descripción:</strong> " . htmlspecialchars($reporte['descripcion'] ?? 'Sin descripción') . "</li>";
    echo "<li><strong>Tipo Base:</strong> " . htmlspecialchars($reporte['tipo_base']) . "</li>";
    echo "<li><strong>Fecha Creación:</strong> " . $reporte['fecha_creacion'] . "</li>";
    echo "</ul>";
    
    echo "<h4>Filtros (" . count($reporte['filtros']) . "):</h4>";
    echo "<pre>" . print_r($reporte['filtros'], true) . "</pre>";
    
    echo "<h4>Columnas (" . count($reporte['columnas']) . "):</h4>";
    echo "<ul>";
    foreach ($reporte['columnas'] as $columna) {
        echo "<li>" . htmlspecialchars($columna) . "</li>";
    }
    echo "</ul>";
    
    echo "<h4>Resultados (" . count($reporte['resultados']) . "):</h4>";
    
    if (empty($reporte['resultados'])) {
        echo "<div class='alert alert-warning'>⚠️ No se encontraron resultados</div>";
        
        // Probar la consulta manualmente
        echo "<h5>Intentando ejecutar consulta manualmente...</h5>";
        $consulta = construirConsultaSQL($reporte['filtros'], $reporte['columnas'], $reporte['tipo_base']);
        echo "<h6>SQL:</h6>";
        echo "<pre>" . htmlspecialchars($consulta['sql']) . "</pre>";
        echo "<h6>Parámetros:</h6>";
        echo "<pre>" . print_r($consulta['params'], true) . "</pre>";
        
        try {
            $stmt = $conn->prepare($consulta['sql']);
            $stmt->execute($consulta['params']);
            $resultados_manuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h6>Resultados Manuales: " . count($resultados_manuales) . "</h6>";
            if (!empty($resultados_manuales)) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr>";
                foreach ($reporte['columnas'] as $col) {
                    echo "<th>" . htmlspecialchars($col) . "</th>";
                }
                echo "</tr>";
                
                foreach ($resultados_manuales as $fila) {
                    echo "<tr>";
                    foreach ($reporte['columnas'] as $col) {
                        echo "<td>" . htmlspecialchars($fila[$col] ?? '') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error en consulta manual: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ Se encontraron " . count($reporte['resultados']) . " resultados</div>";
        
        // Mostrar primeros 5 resultados como ejemplo
        echo "<h5>Primeros 5 resultados:</h5>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach ($reporte['columnas'] as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        
        $limit = 0;
        foreach ($reporte['resultados'] as $fila) {
            if ($limit >= 5) break;
            echo "<tr>";
            foreach ($reporte['columnas'] as $col) {
                $valor = $fila[$col] ?? '';
                if ($col === 'fecha_nacimiento' && $valor) {
                    $valor = date('d/m/Y', strtotime($valor));
                }
                echo "<td>" . htmlspecialchars($valor) . "</td>";
            }
            echo "</tr>";
            $limit++;
        }
        echo "</table>";
        
        if (count($reporte['resultados']) > 5) {
            echo "<p><em>... y " . (count($reporte['resultados']) - 5) . " resultados más</em></p>";
        }
    }
    
    echo "<br><br>";
    echo "<a href='download_report_excel.php?id=$id_reporte' class='btn btn-primary'>📊 Descargar Excel</a>";
    echo " | ";
    echo "<a href='reportes.php' class='btn btn-secondary'>← Volver a Reportes</a>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
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
}
.btn-primary {
    background-color: #007bff;
}
.btn-secondary {
    background-color: #6c757d;
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
}
</style>
