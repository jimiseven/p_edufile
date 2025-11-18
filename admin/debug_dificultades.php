<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Debug de Dificultades</h1>";

// 1. Ver todos los estudiantes
echo "<h2>1. Todos los estudiantes:</h2>";
$sql = "SELECT id_estudiante, nombres, apellido_paterno, apellido_materno FROM estudiantes LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->execute();
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nombre</th><th>¿Tiene dificultades?</th></tr>";

foreach ($estudiantes as $est) {
    echo "<tr>";
    echo "<td>" . $est['id_estudiante'] . "</td>";
    echo "<td>" . $est['nombres'] . " " . $est['apellido_paterno'] . "</td>";
    
    // Verificar si tiene dificultades
    $sql_dif = "SELECT tiene_dificultad FROM estudiante_dificultades WHERE id_estudiante = ?";
    $stmt_dif = $conn->prepare($sql_dif);
    $stmt_dif->execute([$est['id_estudiante']]);
    $dificultad = $stmt_dif->fetch(PDO::FETCH_ASSOC);
    
    if ($dificultad) {
        echo "<td style='color:green; font-weight:bold'>" . $dificultad['tiene_dificultad'] . "</td>";
    } else {
        echo "<td style='color:red'>No tiene registro</td>";
    }
    echo "</tr>";
}
echo "</table>";

// 2. Ver estudiantes con dificultades
echo "<h2>2. Estudiantes con dificultades (tiene_dificultad = 'Si'):</h2>";
$sql = "SELECT e.id_estudiante, e.nombres, e.apellido_paterno, ed.tiene_dificultad 
        FROM estudiantes e 
        LEFT JOIN estudiante_dificultades ed ON e.id_estudiante = ed.id_estudiante 
        WHERE ed.tiene_dificultad = 'Si'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$con_dificultades = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Se encontraron " . count($con_dificultades) . " estudiantes con dificultades</p>";

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Tiene Dificultad</th></tr>";
foreach ($con_dificultades as $est) {
    echo "<tr>";
    echo "<td>" . $est['id_estudiante'] . "</td>";
    echo "<td>" . $est['nombres'] . " " . $est['apellido_paterno'] . "</td>";
    echo "<td>" . $est['tiene_dificultad'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Ver estudiantes sin dificultades
echo "<h2>3. Estudiantes sin dificultades:</h2>";
$sql = "SELECT e.id_estudiante, e.nombres, e.apellido_paterno, ed.tiene_dificultad 
        FROM estudiantes e 
        LEFT JOIN estudiante_dificultades ed ON e.id_estudiante = ed.id_estudiante 
        WHERE ed.tiene_dificultad = 'No' OR ed.tiene_dificultad IS NULL";
$stmt = $conn->prepare($sql);
$stmt->execute();
$sin_dificultades = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Se encontraron " . count($sin_dificultades) . " estudiantes sin dificultades</p>";

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Tiene Dificultad</th></tr>";
foreach ($sin_dificultades as $est) {
    echo "<tr>";
    echo "<td>" . $est['id_estudiante'] . "</td>";
    echo "<td>" . $est['nombres'] . " " . $est['apellido_paterno'] . "</td>";
    echo "<td>" . ($est['tiene_dificultad'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Ver qué valores únicos hay en tiene_dificultad
echo "<h2>4. Valores únicos en tiene_dificultad:</h2>";
$sql = "SELECT DISTINCT tiene_dificultad FROM estudiante_dificultades";
$stmt = $conn->prepare($sql);
$stmt->execute();
$valores = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<ul>";
foreach ($valores as $valor) {
    echo "<li>'" . $valor . "'</li>";
}
echo "</ul>";

// 5. Ver si el filtro está llegando correctamente
echo "<h2>5. Simulación de filtro:</h2>";
if (isset($_GET['filtro'])) {
    $filtro = $_GET['filtro'];
    echo "<p>Filtro recibido: " . $filtro . "</p>";
    
    if ($filtro == '1') {
        $sql = "SELECT e.id_estudiante, e.nombres, e.apellido_paterno 
                FROM estudiantes e 
                LEFT JOIN estudiante_dificultades ed ON e.id_estudiante = ed.id_estudiante 
                WHERE ed.tiene_dificultad = 'Si'";
    } elseif ($filtro == '0') {
        $sql = "SELECT e.id_estudiante, e.nombres, e.apellido_paterno 
                FROM estudiantes e 
                LEFT JOIN estudiante_dificultades ed ON e.id_estudiante = ed.id_estudiante 
                WHERE ed.tiene_dificultad = 'No' OR ed.tiene_dificultad IS NULL";
    } else {
        $sql = "SELECT e.id_estudiante, e.nombres, e.apellido_paterno FROM estudiantes e";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Resultados encontrados: " . count($resultados) . "</p>";
} else {
    echo "<p><a href='?filtro=1'>Probar filtro: Con dificultades (1)</a></p>";
    echo "<p><a href='?filtro=0'>Probar filtro: Sin dificultades (0)</a></p>";
}

?>
