<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Debug de IDs Específicos: 1646 y 1647</h1>";

$ids = [1646, 1647];

foreach ($ids as $id) {
    echo "<h2>Estudiante ID: $id</h2>";
    
    // 1. Datos básicos del estudiante
    echo "<h3>1. Datos del estudiante:</h3>";
    $sql = "SELECT * FROM estudiantes WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estudiante) {
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($estudiante as $campo => $valor) {
            echo "<tr><td>$campo</td><td>" . htmlspecialchars($valor ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>❌ No se encontró el estudiante con ID $id</p>";
        continue;
    }
    
    // 2. Buscar en estudiante_dificultades
    echo "<h3>2. Registro en estudiante_dificultades:</h3>";
    $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $dificultades = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dificultades) {
        echo "<p style='color:green'>✅ Se encontró registro de dificultades</p>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($dificultades as $campo => $valor) {
            echo "<tr><td>$campo</td><td>" . htmlspecialchars($valor ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>❌ No se encontró registro en estudiante_dificultades</p>";
    }
    
    // 3. Buscar en TODAS las tablas relacionadas
    echo "<h3>3. Buscar en todas las tablas de información adicional:</h3>";
    
    $tablas = [
        'estudiante_direccion' => 'Dirección',
        'estudiante_salud' => 'Salud',
        'estudiante_idioma_cultura' => 'Idioma/Cultura',
        'estudiante_transporte' => 'Transporte',
        'estudiante_servicios' => 'Servicios',
        'estudiante_actividad_laboral' => 'Actividad Laboral',
        'estudiante_dificultades' => 'Dificultades',
        'estudiante_abandono' => 'Abandono'
    ];
    
    echo "<table border='1'>";
    echo "<tr><th>Tabla</th><th>¿Tiene registro?</th><th>Detalles</th></tr>";
    
    foreach ($tablas as $tabla => $nombre) {
        $sql = "SELECT * FROM $tabla WHERE id_estudiante = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td>$nombre</td>";
        
        if ($registro) {
            echo "<td style='color:green'>✅ Sí</td>";
            echo "<td>";
            if ($tabla == 'estudiante_dificultades') {
                echo "Tiene dificultad: " . ($registro['tiene_dificultad'] ?? 'NULL');
            } else {
                echo "Campos: " . count($registro);
            }
            echo "</td>";
        } else {
            echo "<td style='color:red'>❌ No</td>";
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Verificar si el ID está en la tabla correcto
    echo "<h3>4. Verificación SQL directa:</h3>";
    echo "<p>Ejecutando: SELECT * FROM estudiante_dificultades WHERE id_estudiante = $id</p>";
    
    $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = $id";
    $result = $conn->query($sql);
    
    if ($result) {
        $registros = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Registros encontrados: " . count($registros) . "</p>";
        
        if (count($registros) > 0) {
            echo "<pre>";
            print_r($registros[0]);
            echo "</pre>";
        }
    } else {
        echo "<p style='color:red'>❌ Error en la consulta</p>";
    }
    
    echo "<hr>";
}

// 5. Verificar la estructura de la tabla estudiante_dificultades
echo "<h2>5. Estructura de la tabla estudiante_dificultades:</h2>";
$sql = "DESCRIBE estudiante_dificultades";
$stmt = $conn->prepare($sql);
$stmt->execute();
$columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
foreach ($columnas as $columna) {
    echo "<tr>";
    echo "<td>" . $columna['Field'] . "</td>";
    echo "<td>" . $columna['Type'] . "</td>";
    echo "<td>" . $columna['Null'] . "</td>";
    echo "<td>" . $columna['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

?>
