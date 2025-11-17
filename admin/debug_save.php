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

echo "<h1>Depuración de Guardado de Reportes</h1>";

// Simular datos de un reporte
$nombre = "Reporte de Prueba " . date('H:i:s');
$tipo_base = 'info_estudiantil';
$descripcion = 'Reporte de prueba para depuración';

// Filtros de prueba
$filtros = [
    'nivel' => ['Primaria', 'Secundaria'],
    'genero' => 'Masculino'
];

// Columnas de prueba
$columnas = ['nombres', 'apellido_paterno', 'apellido_materno', 'genero'];

echo "<h2>Datos que se van a guardar:</h2>";
echo "<pre>";
echo "Nombre: " . $nombre . "\n";
echo "Tipo Base: " . $tipo_base . "\n";
echo "Descripción: " . $descripcion . "\n";
echo "Filtros: " . print_r($filtros, true) . "\n";
echo "Columnas: " . print_r($columnas, true) . "\n";
echo "</pre>";

// Intentar guardar
echo "<h2>Intentando guardar...</h2>";
$resultado = guardarReporte($nombre, $tipo_base, $descripcion, $filtros, $columnas);

if ($resultado['success']) {
    echo "<div class='alert alert-success'>";
    echo "¡Reporte guardado exitosamente!<br>";
    echo "ID del reporte: " . $resultado['id_reporte'] . "<br>";
    echo "</div>";
    
    // Verificar que se guardó correctamente
    echo "<h2>Verificación en la base de datos:</h2>";
    
    // Verificar reporte principal
    $stmt = $conn->prepare("SELECT * FROM reportes_guardados WHERE id_reporte = ?");
    $stmt->execute([$resultado['id_reporte']]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reporte) {
        echo "<h3>✅ Reporte principal encontrado:</h3>";
        echo "<pre>" . print_r($reporte, true) . "</pre>";
    } else {
        echo "<h3>❌ Reporte principal NO encontrado</h3>";
    }
    
    // Verificar filtros
    $stmt = $conn->prepare("SELECT * FROM reportes_guardados_filtros WHERE id_reporte = ?");
    $stmt->execute([$resultado['id_reporte']]);
    $filtros_guardados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($filtros_guardados) {
        echo "<h3>✅ Filtros encontrados (" . count($filtros_guardados) . "):</h3>";
        echo "<pre>" . print_r($filtros_guardados, true) . "</pre>";
    } else {
        echo "<h3>❌ Filtros NO encontrados</h3>";
    }
    
    // Verificar columnas
    $stmt = $conn->prepare("SELECT * FROM reportes_guardados_columnas WHERE id_reporte = ?");
    $stmt->execute([$resultado['id_reporte']]);
    $columnas_guardadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($columnas_guardadas) {
        echo "<h3>✅ Columnas encontradas (" . count($columnas_guardadas) . "):</h3>";
        echo "<pre>" . print_r($columnas_guardadas, true) . "</pre>";
    } else {
        echo "<h3>❌ Columnas NO encontradas</h3>";
    }
    
    // Verificar en la lista general
    $stmt = $conn->query("SELECT rg.id_reporte, rg.nombre, rg.fecha_creacion, p.nombres, p.apellidos 
                         FROM reportes_guardados rg 
                         LEFT JOIN personal p ON rg.id_personal = p.id_personal 
                         ORDER BY rg.fecha_creacion DESC LIMIT 5");
    $todos_reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Últimos 5 reportes en la base de datos:</h3>";
    echo "<pre>" . print_r($todos_reportes, true) . "</pre>";
    
} else {
    echo "<div class='alert alert-danger'>";
    echo "Error al guardar el reporte: " . $resultado['error'] . "<br>";
    echo "</div>";
}

// Verificar conexión y tablas
echo "<h2>Verificación de la base de datos:</h2>";

// Verificar tabla reportes_guardados
$stmt = $conn->query("SHOW TABLES LIKE 'reportes_guardados'");
$tabla = $stmt->fetch();
if ($tabla) {
    echo "✅ Tabla 'reportes_guardados' existe<br>";
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reportes_guardados");
    $count = $stmt->fetch();
    echo "   Total de reportes guardados: " . $count['total'] . "<br>";
} else {
    echo "❌ Tabla 'reportes_guardados' NO existe<br>";
}

// Verificar tabla reportes_guardados_filtros
$stmt = $conn->query("SHOW TABLES LIKE 'reportes_guardados_filtros'");
$tabla = $stmt->fetch();
if ($tabla) {
    echo "✅ Tabla 'reportes_guardados_filtros' existe<br>";
} else {
    echo "❌ Tabla 'reportes_guardados_filtros' NO existe<br>";
}

// Verificar tabla reportes_guardados_columnas
$stmt = $conn->query("SHOW TABLES LIKE 'reportes_guardados_columnas'");
$tabla = $stmt->fetch();
if ($tabla) {
    echo "✅ Tabla 'reportes_guardados_columnas' existe<br>";
} else {
    echo "❌ Tabla 'reportes_guardados_columnas' NO existe<br>";
}

// Verificar sesión
echo "<h2>Información de sesión:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'No definido') . "<br>";
echo "User Role: " . ($_SESSION['user_role'] ?? 'No definido') . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'No definido') . "<br>";

echo "<br><a href='reportes.php' class='btn btn-primary'>Ir a Reportes</a>";
echo "<br><a href='constructor_reporte.php' class='btn btn-secondary'>Ir a Constructor</a>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background-color: #f5f5f5;
}
.alert {
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
}
.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
pre {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    text-decoration: none;
    border-radius: 5px;
    color: white;
}
.btn-primary {
    background-color: #007bff;
}
.btn-secondary {
    background-color: #6c757d;
}
</style>
