<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Depuración de Sesión</h1>";

echo "<h2>Estado de la sesión:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "</pre>";

echo "<h2>Variables de sesión:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: red;'>❌ No hay user_id en sesión</div>";
} else {
    echo "<div style='color: green;'>✅ user_id: " . $_SESSION['user_id'] . "</div>";
}

if (!isset($_SESSION['user_role'])) {
    echo "<div style='color: red;'>❌ No hay user_role en sesión</div>";
} else {
    echo "<div style='color: green;'>✅ user_role: " . $_SESSION['user_role'] . "</div>";
}

// Probar conexión a BD
require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "<div style='color: green;'>✅ Conexión a BD exitosa</div>";
    
    // Verificar tabla
    $stmt = $conn->query("SHOW TABLES LIKE 'reportes_guardados'");
    $tabla = $stmt->fetch();
    if ($tabla) {
        echo "<div style='color: green;'>✅ Tabla reportes_guardados existe</div>";
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM reportes_guardados");
        $count = $stmt->fetch();
        echo "<div>Total de reportes: " . $count['total'] . "</div>";
    } else {
        echo "<div style='color: red;'>❌ Tabla reportes_guardados NO existe</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Error en conexión a BD</div>";
}

echo "<br><a href='../index.php'>Ir al login</a>";
?>
