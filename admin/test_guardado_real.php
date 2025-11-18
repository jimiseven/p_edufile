<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Guardado Real con Valores del Formulario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Test de Guardado Real (Valores del Formulario)</h1>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ejecutar Test</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <button type="submit" name="ejecutar_test" class="btn btn-primary">
                        <i class="bi bi-play-circle"></i> Ejecutar Test con Valores Reales
                    </button>
                    <button type="submit" name="limpiar_datos" class="btn btn-warning ms-2">
                        <i class="bi bi-trash"></i> Limpiar Datos de Prueba
                    </button>
                </form>
            </div>
        </div>

        <?php
        if (isset($_POST['ejecutar_test'])) {
            require_once '../config/database.php';
            
            echo '<div class="mt-4">';
            
            // Simular datos POST EXACTAMENTE como los envía el formulario
            $testData = [
                'nombres' => 'Juan',
                'apellido_paterno' => 'Perez',
                'apellido_materno' => 'Test',
                'carnet_identidad' => '12345678',
                'fecha_nacimiento' => '2005-01-01',
                'genero' => 'Masculino',
                
                // Datos de dificultades - VALORES REALES DEL FORMULARIO
                'dif_tiene_dificultad' => '1',  // El formulario envía "1" para Sí
                'dif_auditiva' => 'leve',
                'dif_visual' => 'ninguna',
                'dif_intelectual' => 'grave',
                'dif_fisico_motora' => 'ninguna',
                'dif_psiquica_mental' => 'ninguna',
                'dif_autista' => 'leve',
                
                // Datos de salud - VALORES REALES DEL FORMULARIO
                'sal_tiene_seguro' => '1',
                'sal_acceso_posta' => '0',
                'sal_acceso_centro_salud' => '1',
                'sal_acceso_hospital' => '0'
            ];
            
            $db = new Database();
            $conn = $db->connect();
            
            // Paso 1: Crear estudiante
            echo '<div class="alert alert-info">
                    <h6><i class="bi bi-1-circle"></i> Creando estudiante...</h6>';
            
            try {
                $sql = "INSERT INTO estudiantes (nombres, apellido_paterno, apellido_materno, carnet_identidad, fecha_nacimiento, genero, id_curso) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $testData['nombres'],
                    $testData['apellido_paterno'],
                    $testData['apellido_materno'],
                    $testData['carnet_identidad'],
                    $testData['fecha_nacimiento'],
                    $testData['genero'],
                    128  // id_curso = 128
                ]);
                
                $id_estudiante = $conn->lastInsertId();
                echo '<span class="text-success">✅ Estudiante creado con ID: ' . $id_estudiante . '</span>';
                
            } catch (PDOException $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
                echo '</div></div>';
                exit();
            }
            
            echo '</div>';
            
            // Paso 2: Guardar información secundaria (usando la función real)
            echo '<div class="alert alert-info mt-3">
                    <h6><i class="bi bi-2-circle"></i> Guardando información secundaria...</h6>';
            
            try {
                // Incluir la función real de guardarInformacionSecundaria
                function guardarInformacionSecundaria($conn, $id_estudiante, $postData) {
                    // Dirección
                    if (!empty($postData['dir_departamento']) || !empty($postData['dir_provincia']) || 
                        !empty($postData['dir_municipio']) || !empty($postData['dir_localidad'])) {
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

                    // Dificultades - CORREGIDO para aceptar '1'
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
                }
                
                guardarInformacionSecundaria($conn, $id_estudiante, $testData);
                echo '<span class="text-success">✅ Información secundaria guardada correctamente</span>';
                
            } catch (Exception $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
            }
            
            echo '</div>';
            
            // Paso 3: Verificar datos guardados
            echo '<div class="alert alert-info mt-3">
                    <h6><i class="bi bi-3-circle"></i> Verificando datos guardados...</h6>';
            
            try {
                // Verificar dificultades
                $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_estudiante]);
                $dificultades = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dificultades) {
                    echo '<h6 class="text-success">✅ Dificultades encontradas:</h6>';
                    echo '<table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>Valor Guardado</th>
                                    <th>Valor Enviado</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
                    $campos_dif = [
                        'tiene_dificultad' => 'dif_tiene_dificultad',
                        'auditiva' => 'dif_auditiva',
                        'visual' => 'dif_visual',
                        'intelectual' => 'dif_intelectual',
                        'fisico_motora' => 'dif_fisico_motora',
                        'psiquica_mental' => 'dif_psiquica_mental',
                        'autista' => 'dif_autista'
                    ];
                    
                    foreach ($campos_dif as $campo_db => $campo_form) {
                        $valor_guardado = $dificultades[$campo_db] ?? 'NULL';
                        $valor_enviado = $testData[$campo_form] ?? 'NULL';
                        $correcto = ($valor_guardado == $valor_enviado);
                        
                        echo '<tr>
                                <td><code>' . $campo_db . '</code></td>
                                <td>' . htmlspecialchars($valor_guardado) . '</td>
                                <td>' . htmlspecialchars($valor_enviado) . '</td>
                                <td>' . ($correcto ? '<span class="text-success">✅</span>' : '<span class="text-danger">❌</span>') . '</td>
                            </tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<span class="text-danger">❌ No se encontró registro de dificultades</span>';
                }
                
                // Verificar salud
                echo '<br><h6 class="text-success">✅ Salud encontrada:</h6>';
                $sql = "SELECT * FROM estudiante_salud WHERE id_estudiante = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_estudiante]);
                $salud = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($salud) {
                    echo '<table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>Valor Guardado</th>
                                    <th>Valor Enviado</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
                    $campos_sal = [
                        'tiene_seguro' => 'sal_tiene_seguro',
                        'acceso_posta' => 'sal_acceso_posta',
                        'acceso_centro_salud' => 'sal_acceso_centro_salud',
                        'acceso_hospital' => 'sal_acceso_hospital'
                    ];
                    
                    foreach ($campos_sal as $campo_db => $campo_form) {
                        $valor_guardado = $salud[$campo_db] ?? 'NULL';
                        $valor_enviado = $testData[$campo_form] ?? 'NULL';
                        $correcto = ($valor_guardado == $valor_enviado);
                        
                        echo '<tr>
                                <td><code>' . $campo_db . '</code></td>
                                <td>' . htmlspecialchars($valor_guardado) . '</td>
                                <td>' . htmlspecialchars($valor_enviado) . '</td>
                                <td>' . ($correcto ? '<span class="text-success">✅</span>' : '<span class="text-danger">❌</span>') . '</td>
                            </tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<span class="text-danger">❌ No se encontró registro de salud</span>';
                }
                
            } catch (PDOException $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
            }
            
            echo '</div>';
            
            echo '<div class="alert alert-success mt-4">
                    <h5><i class="bi bi-check-circle"></i> Test Completado</h5>
                    <p class="mb-0">Ahora puedes probar los filtros con este estudiante (ID: ' . $id_estudiante . ')</p>
                  </div>';
            
            echo '</div>';
        }
        
        if (isset($_POST['limpiar_datos'])) {
            require_once '../config/database.php';
            
            echo '<div class="mt-4">';
            
            try {
                $db = new Database();
                $conn = $db->connect();
                
                $sql = "SELECT id_estudiante FROM estudiantes WHERE nombres LIKE '%Test%'";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $estudiantes_test = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($estudiantes_test)) {
                    $placeholders = str_repeat('?,', count($estudiantes_test) - 1) . '?';
                    
                    $tablas = ['estudiante_direccion', 'estudiante_salud', 'estudiante_dificultades', 'estudiante_abandono'];
                    foreach ($tablas as $tabla) {
                        $sql = "DELETE FROM $tabla WHERE id_estudiante IN ($placeholders)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($estudiantes_test);
                    }
                    
                    $sql = "DELETE FROM estudiantes WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    echo '<div class="alert alert-success">
                            <strong>✅ Datos de prueba eliminados correctamente</strong>
                          </div>';
                } else {
                    echo '<div class="alert alert-info">
                            <strong>ℹ️ No se encontraron datos de prueba</strong>
                          </div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">
                        <strong>❌ Error al eliminar datos: </strong>' . $e->getMessage() . '
                      </div>';
            }
            
            echo '</div>';
        }
        ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
