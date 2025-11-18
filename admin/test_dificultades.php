<?php
require_once '../config/database.php';
require_once 'report_generator.php';

$db = new Database();
$conn = $db->connect();

// Prueba de filtros de dificultades
echo "<h2>Prueba de filtros de dificultades</h2>";

// Caso 1: Filtro tiene_dificultad = 1 (con dificultad)
$filtros = ['tiene_dificultad' => '1'];
$columnas = ['nombres', 'apellido_paterno', 'apellido_materno', 'tiene_dificultad'];
$consulta = construirConsultaSQL($filtros, $columnas, 'info_estudiantil');

echo "<h3>Caso 1: Estudiantes con dificultad</h3>";
echo "<p><strong>SQL:</strong><br>" . htmlspecialchars($consulta['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong><br>" . print_r($consulta['params'], true) . "</p>";

try {
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Resultados:</strong> " . count($resultados) . " estudiantes</p>";
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Caso 2: Filtro dificultad_auditiva = 'leve'
$filtros = ['dificultad_auditiva' => 'leve'];
$columnas = ['nombres', 'apellido_paterno', 'apellido_materno', 'dificultad_auditiva'];
$consulta = construirConsultaSQL($filtros, $columnas, 'info_estudiantil');

echo "<h3>Caso 2: Estudiantes con dificultad auditiva leve</h3>";
echo "<p><strong>SQL:</strong><br>" . htmlspecialchars($consulta['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong><br>" . print_r($consulta['params'], true) . "</p>";

try {
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Resultados:</strong> " . count($resultados) . " estudiantes</p>";
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Caso 3: Múltiples filtros de dificultades
$filtros = [
    'tiene_dificultad' => '1',
    'dificultad_visual' => 'ninguna',
    'dificultad_auditiva' => 'leve'
];
$columnas = ['nombres', 'apellido_paterno', 'apellido_materno', 'tiene_dificultad', 'dificultad_visual', 'dificultad_auditiva'];
$consulta = construirConsultaSQL($filtros, $columnas, 'info_estudiantil');

echo "<h3>Caso 3: Múltiples filtros de dificultades</h3>";
echo "<p><strong>SQL:</strong><br>" . htmlspecialchars($consulta['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong><br>" . print_r($consulta['params'], true) . "</p>";

try {
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Resultados:</strong> " . count($resultados) . " estudiantes</p>";
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Caso 4: Columnas de dificultades sin filtros
$filtros = [];
$columnas = ['nombres', 'apellido_paterno', 'apellido_materno', 'tiene_dificultad', 'dificultad_auditiva', 'dificultad_visual'];
$consulta = construirConsultaSQL($filtros, $columnas, 'info_estudiantil');

echo "<h3>Caso 4: Columnas de dificultades sin filtros</h3>";
echo "<p><strong>SQL:</strong><br>" . htmlspecialchars($consulta['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong><br>" . print_r($consulta['params'], true) . "</p>";

try {
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Resultados:</strong> " . count($resultados) . " estudiantes</p>";
    
    if (!empty($resultados)) {
        echo "<table border='1'>";
        echo "<tr><th>Nombres</th><th>Apellido Paterno</th><th>Apellido Materno</th><th>Tiene Dificultad</th><th>Dificultad Auditiva</th><th>Dificultad Visual</th></tr>";
        foreach (array_slice($resultados, 0, 5) as $fila) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fila['nombres'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['apellido_paterno'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['apellido_materno'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['tiene_dificultad'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['dificultad_auditiva'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['dificultad_visual'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        if (count($resultados) > 5) {
            echo "<p>... y " . (count($resultados) - 5) . " más</p>";
        }
    }
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<h2>Prueba completada</h2>";
?>
