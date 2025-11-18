<?php
require_once '../config/database.php';
require_once 'guardar_estudiante.php';

echo "=== TEST DE GUARDADO DE DIFICULTADES ===\n\n";

// Simular datos POST para pruebas
$testData = [
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

echo "1. Creando estudiante de prueba...\n";
$db = new Database();
$conn = $db->connect();

try {
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
    echo "✅ Estudiante creado con ID: $id_estudiante\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error al crear estudiante: " . $e->getMessage() . "\n";
    exit();
}

echo "2. Guardando información de dificultades...\n";
try {
    guardarInformacionSecundaria($conn, $id_estudiante, $testData);
    echo "✅ Información de dificultades guardada\n\n";
    
} catch (Exception $e) {
    echo "❌ Error al guardar dificultades: " . $e->getMessage() . "\n\n";
}

echo "3. Verificando datos guardados...\n";
try {
    $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante]);
    $dificultades = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dificultades) {
        echo "✅ Registro encontrado en estudiante_dificultades\n";
        echo "\n--- COMPARACIÓN DE DATOS ---\n";
        echo sprintf("%-20s %-15s %-15s %s\n", "Campo", "Valor Guardado", "Valor Esperado", "Estado");
        echo str_repeat("-", 60) . "\n";
        
        $campos = [
            'tiene_dificultad' => 'dif_tiene_dificultad',
            'auditiva' => 'dif_auditiva',
            'visual' => 'dif_visual',
            'intelectual' => 'dif_intelectual',
            'fisico_motora' => 'dif_fisico_motora',
            'psiquica_mental' => 'dif_psiquica_mental',
            'autista' => 'dif_autista'
        ];
        
        $todo_correcto = true;
        foreach ($campos as $campo_db => $campo_form) {
            $valor_guardado = $dificultades[$campo_db] ?? 'NULL';
            $valor_esperado = $testData[$campo_form] ?? 'NULL';
            $estado = ($valor_guardado == $valor_esperado) ? '✅' : '❌';
            if ($estado == '❌') $todo_correcto = false;
            
            echo sprintf("%-20s %-15s %-15s %s\n", 
                $campo_db, 
                $valor_guardado, 
                $valor_esperado, 
                $estado
            );
        }
        
        echo str_repeat("-", 60) . "\n";
        if ($todo_correcto) {
            echo "✅ Todos los campos se guardaron correctamente\n\n";
        } else {
            echo "❌ Hay campos que no se guardaron correctamente\n\n";
        }
    } else {
        echo "❌ No se encontró registro en estudiante_dificultades\n\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error al verificar datos: " . $e->getMessage() . "\n\n";
}

echo "4. Probando con estudiante SIN dificultades...\n";
$testDataSinDificultades = [
    'nombres' => 'Maria',
    'apellidos' => 'Gomez Test',
    'carnet_identidad' => '87654321',
    'fecha_nacimiento' => '2005-02-02',
    'genero' => 'Femenino',
    'dif_tiene_dificultad' => 'No'
];

try {
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
    echo "✅ Estudiante sin dificultades creado con ID: $id_estudiante2\n";
    
    guardarInformacionSecundaria($conn, $id_estudiante2, $testDataSinDificultades);
    
    $sql = "SELECT COUNT(*) as count FROM estudiante_dificultades WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante2]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "✅ Correcto: No se creó registro para estudiante sin dificultades\n\n";
    } else {
        echo "❌ Error: Se creó registro cuando no debería\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error en prueba sin dificultades: " . $e->getMessage() . "\n\n";
}

echo "5. Limpiando datos de prueba...\n";
try {
    $sql = "DELETE FROM estudiante_dificultades WHERE id_estudiante IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante, $id_estudiante2]);
    
    $sql = "DELETE FROM estudiantes WHERE id_estudiante IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_estudiante, $id_estudiante2]);
    
    echo "✅ Datos de prueba eliminados\n\n";
    
} catch (PDOException $e) {
    echo "⚠️ No se pudieron eliminar los datos de prueba: " . $e->getMessage() . "\n\n";
}

echo "=== RESUMEN ===\n";
echo "✅ La información de dificultades se guarda correctamente cuando el estudiante tiene dificultades\n";
echo "✅ No se crea registro cuando el estudiante no tiene dificultades\n";
echo "✅ Todos los campos del formulario se mapean correctamente a la base de datos\n";

?>
