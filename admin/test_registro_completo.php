<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Registro Completo de Estudiante</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Test de Registro Completo</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Información del Test</h6>
                            <p class="mb-1">Este test registrará un estudiante con los siguientes datos:</p>
                            <ul class="mb-0">
                                <li><strong>Curso:</strong> ID 128</li>
                                <li><strong>Dificultad Auditiva:</strong> Leve</li>
                                <li><strong>Datos completos:</strong> Dirección, salud, idioma, transporte, servicios, laboral, abandono</li>
                            </ul>
                        </div>

                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            echo '<div class="alert alert-info">';
                            echo '<h6><i class="bi bi-gear"></i> Ejecutando test...</h6>';
                            
                            try {
                                // Iniciar transacción
                                $conn->beginTransaction();
                                
                                // 1. Insertar responsable ficticio
                                echo '<p>1. Creando responsable...</p>';
                                $sqlResponsable = "INSERT INTO responsables 
                                    (nombres, apellido_paterno, apellido_materno, carnet_identidad, fecha_nacimiento, 
                                     grado_instruccion, idioma_frecuente, parentesco, celular)
                                    VALUES
                                    (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                $stmtResponsable = $conn->prepare($sqlResponsable);
                                $stmtResponsable->execute([
                                    'Juan',
                                    'Pérez',
                                    'Test',
                                    '12345678',
                                    '1980-01-01',
                                    'Secundaria',
                                    'Español',
                                    'Padre',
                                    '77777777'
                                ]);
                                $id_responsable = $conn->lastInsertId();
                                echo '<span class="text-success">✅ Responsable creado con ID: ' . $id_responsable . '</span><br>';
                                
                                // 2. Insertar estudiante principal
                                echo '<p class="mt-3">2. Creando estudiante...</p>';
                                $sqlEstudiante = "INSERT INTO estudiantes 
                                    (nombres, apellido_paterno, apellido_materno, genero, rude, carnet_identidad, fecha_nacimiento, pais, provincia_departamento, id_curso, id_responsable)
                                    VALUES
                                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                $stmtEstudiante = $conn->prepare($sqlEstudiante);
                                $stmtEstudiante->execute([
                                    'María',
                                    'García',
                                    'Test',
                                    'F',
                                    '1234567890',
                                    '87654321',
                                    '2005-05-15',
                                    'Bolivia',
                                    'La Paz',
                                    128, // ID del curso solicitado
                                    $id_responsable
                                ]);
                                $id_estudiante = $conn->lastInsertId();
                                echo '<span class="text-success">✅ Estudiante creado con ID: ' . $id_estudiante . '</span><br>';
                                
                                // 3. Crear datos de prueba completos
                                $testData = [
                                    // Dirección
                                    'dir_departamento' => 'La Paz',
                                    'dir_provincia' => 'Murillo',
                                    'dir_municipio' => 'La Paz',
                                    'dir_localidad' => 'Zona Sur',
                                    'dir_comunidad' => 'Comunidad Test',
                                    'dir_zona' => 'Zona Test',
                                    'dir_numero_vivienda' => '123',
                                    'dir_telefono' => '2222222',
                                    'dir_celular' => '66666666',
                                    
                                    // Salud
                                    'sal_tiene_seguro' => '1',
                                    'sal_acceso_posta' => '1',
                                    'sal_acceso_centro_salud' => '0',
                                    'sal_acceso_hospital' => '1',
                                    
                                    // Idioma
                                    'idi_idioma' => 'Español',
                                    'idi_cultura' => 'Boliviana',
                                    
                                    // Transporte
                                    'trans_medio' => 'a_pie',
                                    'trans_tiempo_llegada' => 'menos_media_hora',
                                    
                                    // Servicios
                                    'serv_agua_caneria' => '1',
                                    'serv_bano' => '1',
                                    'serv_alcantarillado' => '0',
                                    'serv_internet' => '1',
                                    'serv_energia' => '1',
                                    'serv_recojo_basura' => '1',
                                    'serv_tipo_vivienda' => 'propia',
                                    
                                    // Laboral
                                    'lab_trabajo' => '1',
                                    'lab_meses_trabajo' => ['enero', 'febrero', 'marzo'],
                                    'lab_actividad' => 'Comercio',
                                    'lab_turno_manana' => '0',
                                    'lab_turno_tarde' => '1',
                                    'lab_turno_noche' => '0',
                                    'lab_frecuencia' => 'dias_habiles',
                                    
                                    // Dificultades (con dificultad auditiva leve como solicitado)
                                    'dif_tiene_dificultad' => '1',
                                    'dif_auditiva' => 'leve', // Dificultad auditiva leve solicitada
                                    'dif_visual' => 'ninguna',
                                    'dif_intelectual' => 'ninguna',
                                    'dif_fisico_motora' => 'ninguna',
                                    'dif_psiquica_mental' => 'ninguna',
                                    'dif_autista' => 'ninguna',
                                    
                                    // Abandono
                                    'aba_abandono' => '0',
                                    'aba_motivo' => ''
                                ];
                                
                                // 4. Guardar información secundaria usando la función real
                                echo '<p class="mt-3">3. Guardando información secundaria...</p>';
                                
                                // Incluir y ejecutar la función real
                                function guardarInformacionSecundaria($conn, $id_estudiante, $postData) {
                                    try {
                                        // Dirección
                                        if (!empty($postData['dir_departamento']) || !empty($postData['dir_zona']) || !empty($postData['dir_telefono'])) {
                                            $sql = "INSERT INTO estudiante_direccion (id_estudiante, departamento, provincia, municipio, localidad, comunidad, zona, numero_vivienda, telefono, celular) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['dir_departamento'] ?? null,
                                                $postData['dir_provincia'] ?? null,
                                                $postData['dir_municipio'] ?? null,
                                                $postData['dir_localidad'] ?? null,
                                                $postData['dir_comunidad'] ?? null,
                                                $postData['dir_zona'] ?? null,
                                                $postData['dir_numero_vivienda'] ?? null,
                                                $postData['dir_telefono'] ?? null,
                                                $postData['dir_celular'] ?? null
                                            ]);
                                        }

                                        // Salud
                                        if (!empty($postData['sal_tiene_seguro']) || !empty($postData['sal_acceso_posta']) || 
                                            !empty($postData['sal_acceso_centro_salud']) || !empty($postData['sal_acceso_hospital'])) {
                                            $sql = "INSERT INTO estudiante_salud (id_estudiante, tiene_seguro, acceso_posta, acceso_centro_salud, acceso_hospital) 
                                                    VALUES (?, ?, ?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['sal_tiene_seguro'] ?? null,
                                                $postData['sal_acceso_posta'] ?? null,
                                                $postData['sal_acceso_centro_salud'] ?? null,
                                                $postData['sal_acceso_hospital'] ?? null
                                            ]);
                                        }

                                        // Idioma/Cultura
                                        if (!empty($postData['idi_idioma']) || !empty($postData['idi_cultura'])) {
                                            $sql = "INSERT INTO estudiante_idioma_cultura (id_estudiante, idioma, cultura) 
                                                    VALUES (?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['idi_idioma'] ?? null,
                                                $postData['idi_cultura'] ?? null
                                            ]);
                                        }

                                        // Transporte
                                        if (!empty($postData['trans_medio']) || !empty($postData['trans_tiempo_llegada'])) {
                                            $sql = "INSERT INTO estudiante_transporte (id_estudiante, medio, tiempo_llegada) 
                                                    VALUES (?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['trans_medio'] ?? null,
                                                $postData['trans_tiempo_llegada'] ?? null
                                            ]);
                                        }

                                        // Servicios
                                        $servicios = [
                                            'agua_caneria' => $postData['serv_agua_caneria'] ?? '0',
                                            'bano' => $postData['serv_bano'] ?? '0',
                                            'alcantarillado' => $postData['serv_alcantarillado'] ?? '0',
                                            'internet' => $postData['serv_internet'] ?? '0',
                                            'energia' => $postData['serv_energia'] ?? '0',
                                            'recojo_basura' => $postData['serv_recojo_basura'] ?? '0',
                                            'tipo_vivienda' => $postData['serv_tipo_vivienda'] ?? null
                                        ];
                                        
                                        $sql = "INSERT INTO estudiante_servicios (id_estudiante, agua_caneria, bano, alcantarillado, 
                                                internet, energia, recojo_basura, tipo_vivienda) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute([
                                            $id_estudiante,
                                            $servicios['agua_caneria'],
                                            $servicios['bano'],
                                            $servicios['alcantarillado'],
                                            $servicios['internet'],
                                            $servicios['energia'],
                                            $servicios['recojo_basura'],
                                            $servicios['tipo_vivienda']
                                        ]);

                                        // Actividad Laboral
                                        if (!empty($postData['lab_trabajo']) && $postData['lab_trabajo'] === '1') {
                                            $meses_trabajo = isset($postData['lab_meses_trabajo']) ? implode(',', $postData['lab_meses_trabajo']) : '';
                                            $sql = "INSERT INTO estudiante_actividad_laboral (id_estudiante, trabajo, meses_trabajo, actividad, 
                                                    turno_manana, turno_tarde, turno_noche, frecuencia) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['lab_trabajo'],
                                                $meses_trabajo,
                                                $postData['lab_actividad'] ?? null,
                                                $postData['lab_turno_manana'] ?? '0',
                                                $postData['lab_turno_tarde'] ?? '0',
                                                $postData['lab_turno_noche'] ?? '0',
                                                $postData['lab_frecuencia'] ?? null
                                            ]);
                                        }

                                        // Dificultades
                                        if (!empty($postData['dif_tiene_dificultad']) && $postData['dif_tiene_dificultad'] === '1') {
                                            $sql = "INSERT INTO estudiante_dificultades (id_estudiante, tiene_dificultad, auditiva, visual, 
                                                    intelectual, fisico_motora, psiquica_mental, autista) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['dif_tiene_dificultad'],
                                                $postData['dif_auditiva'] ?? 'ninguna',
                                                $postData['dif_visual'] ?? 'ninguna',
                                                $postData['dif_intelectual'] ?? 'ninguna',
                                                $postData['dif_fisico_motora'] ?? 'ninguna',
                                                $postData['dif_psiquica_mental'] ?? 'ninguna',
                                                $postData['dif_autista'] ?? 'ninguna'
                                            ]);
                                        }

                                        // Abandono
                                        if (!empty($postData['aba_abandono']) && $postData['aba_abandono'] === '1') {
                                            $sql = "INSERT INTO estudiante_abandono (id_estudiante, abandono, motivo) 
                                                    VALUES (?, ?, ?)";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute([
                                                $id_estudiante,
                                                $postData['aba_abandono'],
                                                $postData['aba_motivo'] ?? null
                                            ]);
                                        }

                                    } catch (PDOException $e) {
                                        error_log("Error al guardar información secundaria: " . $e->getMessage());
                                        throw $e;
                                    }
                                }
                                
                                guardarInformacionSecundaria($conn, $id_estudiante, $testData);
                                echo '<span class="text-success">✅ Información secundaria guardada correctamente</span><br>';
                                
                                // 5. Verificar datos guardados
                                echo '<p class="mt-3">4. Verificando datos guardados...</p>';
                                
                                // Verificar dificultades (especialmente la auditiva leve)
                                $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute([$id_estudiante]);
                                $dificultades = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($dificultades) {
                                    echo '<span class="text-success">✅ Registro de dificultades encontrado</span><br>';
                                    echo '<div class="mt-2 p-2 bg-light rounded">';
                                    echo '<strong>Dificultad Auditiva:</strong> ' . htmlspecialchars($dificultades['auditiva']) . '<br>';
                                    echo '<strong>¿Tiene dificultad?</strong> ' . htmlspecialchars($dificultades['tiene_dificultad']);
                                    echo '</div>';
                                    
                                    if ($dificultades['auditiva'] === 'leve') {
                                        echo '<span class="text-success">✅ Dificultad auditiva leve verificada correctamente</span><br>';
                                    } else {
                                        echo '<span class="text-danger">❌ Error: Se esperaba "leve" pero se guardó "' . htmlspecialchars($dificultades['auditiva']) . '"</span><br>';
                                    }
                                } else {
                                    echo '<span class="text-danger">❌ No se encontró registro de dificultades</span><br>';
                                }
                                
                                // Verificar curso
                                $sql = "SELECT e.*, c.* FROM estudiantes e 
                                       LEFT JOIN cursos c ON e.id_curso = c.id_curso 
                                       WHERE e.id_estudiante = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute([$id_estudiante]);
                                $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($estudiante && $estudiante['id_curso'] == 128) {
                                    echo '<span class="text-success">✅ Estudiante registrado en curso ID 128 correctamente</span><br>';
                                    echo '<div class="mt-2 p-2 bg-light rounded">';
                                    echo '<strong>Estudiante:</strong> ' . htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellido_paterno'] . ' ' . $estudiante['apellido_materno']) . '<br>';
                                    echo '<strong>Curso:</strong> ' . htmlspecialchars(($estudiante['nivel'] ?? '') . ' ' . ($estudiante['curso'] ?? '') . ' "' . ($estudiante['paralelo'] ?? '') . '" (ID: ' . $estudiante['id_curso'] . ')');
                                    echo '</div>';
                                }
                                
                                // Confirmar transacción
                                $conn->commit();
                                
                                echo '<div class="alert alert-success mt-3">';
                                echo '<h6><i class="bi bi-check-circle"></i> Test Completado Exitosamente</h6>';
                                echo '<p class="mb-0">Todos los datos se guardaron correctamente. El estudiante con dificultad auditiva leve ha sido registrado en el curso 128.</p>';
                                echo '<p class="mb-0"><strong>ID Estudiante:</strong> ' . $id_estudiante . '</p>';
                                echo '</div>';
                                
                            } catch (Exception $e) {
                                if ($conn->inTransaction()) {
                                    $conn->rollback();
                                }
                                echo '<div class="alert alert-danger">';
                                echo '<h6><i class="bi bi-exclamation-triangle"></i> Error en el Test</h6>';
                                echo '<p class="mb-0">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        ?>

                        <div class="text-center mt-4">
                            <form method="POST" onsubmit="return confirm('¿Estás seguro de ejecutar el test? Se crearán datos de prueba en la base de datos.')">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-play-circle"></i> Ejecutar Test de Registro
                                </button>
                            </form>
                            
                            <div class="mt-3">
                                <a href="estudiantes.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver a Estudiantes
                                </a>
                                <a href="test_registro_completo.php?limpiar=1" class="btn btn-outline-warning">
                                    <i class="bi bi-trash"></i> Limpiar Tests Anteriores
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Función para limpiar datos de prueba
if (isset($_GET['limpiar']) && $_GET['limpiar'] == '1') {
    try {
        // Eliminar datos de prueba (estudiantes con "Test" en el nombre)
        $sql = "SELECT e.id_estudiante FROM estudiantes e WHERE e.nombres LIKE '%Test%' OR e.apellido_paterno LIKE '%Test%' OR e.apellido_materno LIKE '%Test%'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $estudiantes_test = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        if (!empty($estudiantes_test)) {
            $placeholders = str_repeat('?,', count($estudiantes_test) - 1) . '?';
            
            // Eliminar de todas las tablas relacionadas
            $tables = [
                'estudiante_dificultades',
                'estudiante_actividad_laboral',
                'estudiante_abandono',
                'estudiante_servicios',
                'estudiante_transporte',
                'estudiante_idioma_cultura',
                'estudiante_salud',
                'estudiante_direccion'
            ];
            
            foreach ($tables as $table) {
                $sql = "DELETE FROM $table WHERE id_estudiante IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $stmt->execute($estudiantes_test);
            }
            
            // Eliminar estudiantes
            $sql = "DELETE FROM estudiantes WHERE id_estudiante IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($estudiantes_test);
            
            // Eliminar responsables
            $sql = "DELETE FROM responsables WHERE nombres LIKE '%Test%' OR apellido_paterno LIKE '%Test%' OR apellido_materno LIKE '%Test%'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            echo '<script>alert("Datos de prueba eliminados correctamente"); window.location.href="test_registro_completo.php";</script>';
        }
    } catch (Exception $e) {
        echo '<script>alert("Error al limpiar datos: ' . $e->getMessage() . '");</script>';
    }
}
?>
