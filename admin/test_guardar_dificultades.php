<?php
require_once '../config/database.php';
require_once 'guardar_estudiante.php';

echo "<h2>Test de Guardado de Información de Dificultades</h2>";

// Simular datos POST para pruebas
$testData = [
    // Datos básicos del estudiante
    'nombres' => 'Juan',
    'apellidos' => 'Perez Test',
    'carnet_identidad' => '12345678',
    'fecha_nacimiento' => '2005-01-01',
    'genero' => 'Masculino',
    
    // Datos de dificultades
    'dif_tiene_dificultad' => 'Si',
    'dif_auditiva' => 'Si',
    'dif_visual' => 'No',
    'dif_intelectual' => 'Si',
    'dif_fisico_motora' => 'No',
    'dif_psiquica_mental' => 'No',
    'dif_autista' => 'Si'
];

echo "<h3>1. Creando estudiante de prueba...</h3>";

$db = new Database();
$conn = $db->connect();

try {
    // Insertar estudiante básico
    $sql = "INSERT INTO estudiantes (nombres, apellidos, carnet_identidad, fecha_nacimiento, genero) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $testData['nombres'],
        $testData['apellidos'],
        $testData['carnet_identidad'],
        $testData['fecha_nacimiento'],
        $testData['genero']
    ]);
    
    $id_estudiante = $conn->lastInsertId();
    echo "<p style='color: green;'>✅ Estudiante creado con ID: $id_estudiante</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error al crear estudiante: " . $e->getMessage() . "</p>";
    exit();
}

echo "<h3>2. Guardando información de dificultades...</h3>";

try {
    // Llamar a la función para guardar información secundaria
    guardarInformacionSecundaria($conn, $id_estudiante, $testData);
    echo "<p style='color: green;'>✅ Información de dificultades guardada</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al guardar dificultades: " . $e->getMessage() . "</p>";
}

echo "<h3>3. Verificando datos guardados en la base de datos...</h3>";

try {
    $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante]);
    $dificultades = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dificultades) {
        echo "<p style='color: green;'>✅ Registro encontrado en estudiante_dificultades</p>";
        echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
        echo "<tr><th>Campo</th><th>Valor Guardado</th><th>Valor Esperado</th><th>Estado</th></tr>";
        
        $campos = [
            'tiene_dificultad' => 'dif_tiene_dificultad',
            'auditiva' => 'dif_auditiva',
            'visual' => 'dif_visual',
            'intelectual' => 'dif_intelectual',
            'fisico_motora' => 'dif_fisico_motora',
            'psiquica_mental' => 'dif_psiquica_mental',
            'autista' => 'dif_autista'
        ];
        
        foreach ($campos as $campo_db => $campo_form) {
            $valor_guardado = $dificultades[$campo_db];
            $valor_esperado = $testData[$campo_form];
            $estado = ($valor_guardado == $valor_esperado) ? 
                     "<span style='color: green;'>✅ Correcto</span>" : 
                     "<span style='color: red;'>❌ Incorrecto</span>";
            
            echo "<tr>";
            echo "<td>$campo_db</td>";
            echo "<td>" . htmlspecialchars($valor_guardado ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($valor_esperado ?? 'NULL') . "</td>";
            echo "<td>$estado</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No se encontró registro en estudiante_dificultades</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error al verificar datos: " . $e->getMessage() . "</p>";
}

echo "<h3>4. Probando con estudiante SIN dificultades...</h3>";

// Crear otro estudiante sin dificultades
$testDataSinDificultades = [
    'nombres' => 'Maria',
    'apellidos' => 'Gomez Test',
    'carnet_identidad' => '87654321',
    'fecha_nacimiento' => '2005-02-02',
    'genero' => 'Femenino',
    'dif_tiene_dificultad' => 'No'
];

try {
    // Insertar estudiante básico
    $sql = "INSERT INTO estudiantes (nombres, apellidos, carnet_identidad, fecha_nacimiento, genero) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $testDataSinDificultades['nombres'],
        $testDataSinDificultades['apellidos'],
        $testDataSinDificultades['carnet_identidad'],
        $testDataSinDificultades['fecha_nacimiento'],
        $testDataSinDificultades['genero']
    ]);
    
    $id_estudiante2 = $conn->lastInsertId();
    echo "<p style='color: green;'>✅ Estudiante sin dificultades creado con ID: $id_estudiante2</p>";
    
    // Intentar guardar información (no debería crear registro)
    guardarInformacionSecundaria($conn, $id_estudiante2, $testDataSinDificultades);
    
    // Verificar que no se creó registro
    $sql = "SELECT COUNT(*) as count FROM estudiante_dificultades WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante2]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "<p style='color: green;'>✅ Correcto: No se creó registro para estudiante sin dificultades</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: Se creó registro cuando no debería</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en prueba sin dificultades: " . $e->getMessage() . "</p>";
}

echo "<h3>5. Limpiando datos de prueba...</h3>";

try {
    // Eliminar registros de prueba
    $sql = "DELETE FROM estudiante_dificultades WHERE id_estudiante IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante, $id_estudiante2]);
    
    $sql = "DELETE FROM estudiantes WHERE id_estudiante IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante, $id_estudiante2]);
    
    echo "<p style='color: green;'>✅ Datos de prueba eliminados</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: orange;'>⚠️ No se pudieron eliminar los datos de prueba: " . $e->getMessage() . "</p>";
}

echo "<h3>Resumen</h3>";
echo "<p>El test ha verificado que:</p>";
echo "<ul>";
echo "<li>La información de dificultades se guarda correctamente cuando el estudiante tiene dificultades</li>";
echo "<li>No se crea registro cuando el estudiante no tiene dificultades</li>";
echo "<li>Todos los campos del formulario se mapean correctamente a la base de datos</li>";
echo "</ul>";

?>
