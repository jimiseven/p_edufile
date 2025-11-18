<?php
require_once '../config/database.php';
require_once 'report_generator.php';

// Prueba de la nueva implementación de dificultad simplificada
echo "<h2>Prueba de Filtro de Dificultad Simplificado</h2>";

// Caso 1: Filtro de "Tiene Dificultad" = Sí
echo "<h3>Caso 1: Tiene Dificultad = Sí</h3>";
$filtros1 = [
    'tiene_dificultad' => '1'
];
$columnas1 = ['nombre_completo', 'tipo_dificultad'];
$resultado1 = construirConsultaSQL($filtros1, $columnas1, 'info_estudiantil');
echo "<p><strong>SQL:</strong> " . htmlspecialchars($resultado1['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong> " . print_r($resultado1['params'], true) . "</p>";

// Caso 2: Filtro de "Tiene Dificultad" = No
echo "<h3>Caso 2: Tiene Dificultad = No</h3>";
$filtros2 = [
    'tiene_dificultad' => '0'
];
$columnas2 = ['nombre_completo', 'tipo_dificultad'];
$resultado2 = construirConsultaSQL($filtros2, $columnas2, 'info_estudiantil');
echo "<p><strong>SQL:</strong> " . htmlspecialchars($resultado2['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong> " . print_r($resultado2['params'], true) . "</p>";

// Caso 3: Sin filtro de dificultad
echo "<h3>Caso 3: Sin filtro de dificultad</h3>";
$filtros3 = [];
$columnas3 = ['nombre_completo', 'tipo_dificultad'];
$resultado3 = construirConsultaSQL($filtros3, $columnas3, 'info_estudiantil');
echo "<p><strong>SQL:</strong> " . htmlspecialchars($resultado3['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong> " . print_r($resultado3['params'], true) . "</p>";

// Caso 4: Columna tipo_dificultad con otros filtros
echo "<h3>Caso 4: Columna tipo_dificultad con otros filtros</h3>";
$filtros4 = [
    'genero' => 'Femenino',
    'tiene_dificultad' => '1'
];
$columnas4 = ['nombre_completo', 'genero', 'tipo_dificultad', 'nivel'];
$resultado4 = construirConsultaSQL($filtros4, $columnas4, 'info_estudiantil');
echo "<p><strong>SQL:</strong> " . htmlspecialchars($resultado4['sql']) . "</p>";
echo "<p><strong>Parámetros:</strong> " . print_r($resultado4['params'], true) . "</p>";

// Prueba de ejecución de consulta
echo "<h3>Prueba de Ejecución de Consulta</h3>";
$db = new Database();
$conn = $db->connect();

try {
    $stmt = $conn->prepare($resultado1['sql']);
    $stmt->execute($resultado1['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Número de resultados:</strong> " . count($resultados) . "</p>";
    
    if (!empty($resultados)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Nombre Completo</th><th>Tipo Dificultad</th></tr>";
        
        foreach ($resultados as $fila) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fila['nombre_completo'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($fila['tipo_dificultad'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No se encontraron resultados.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p><strong>Error en la consulta:</strong> " . $e->getMessage() . "</p>";
}

?>
