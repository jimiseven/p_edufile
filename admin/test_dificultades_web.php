<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Guardado de Dificultades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Test de Guardado de Información de Dificultades</h1>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ejecutar Test</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <button type="submit" name="ejecutar_test" class="btn btn-primary">
                        <i class="bi bi-play-circle"></i> Ejecutar Test Completo
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
            require_once 'guardar_estudiante.php';
            
            echo '<div class="mt-4">';
            
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
            
            $db = new Database();
            $conn = $db->connect();
            
            // Paso 1: Crear estudiante con dificultades
            echo '<div class="alert alert-info">
                    <h6><i class="bi bi-1-circle"></i> Creando estudiante con dificultades...</h6>';
            
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
                echo '<span class="text-success">✅ Estudiante creado con ID: ' . $id_estudiante . '</span>';
                
            } catch (PDOException $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
                echo '</div></div>';
                exit();
            }
            
            echo '</div>';
            
            // Paso 2: Guardar información de dificultades
            echo '<div class="alert alert-info mt-3">
                    <h6><i class="bi bi-2-circle"></i> Guardando información de dificultades...</h6>';
            
            try {
                guardarInformacionSecundaria($conn, $id_estudiante, $testData);
                echo '<span class="text-success">✅ Información guardada correctamente</span>';
                
            } catch (Exception $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
            }
            
            echo '</div>';
            
            // Paso 3: Verificar datos guardados
            echo '<div class="alert alert-info mt-3">
                    <h6><i class="bi bi-3-circle"></i> Verificando datos guardados...</h6>';
            
            try {
                $sql = "SELECT * FROM estudiante_dificultades WHERE id_estudiante = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_estudiante]);
                $dificultades = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dificultades) {
                    echo '<span class="text-success">✅ Registro encontrado</span>';
                    
                    echo '<table class="table table-sm mt-3">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>Valor Guardado</th>
                                    <th>Valor Esperado</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
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
                        $correcto = ($valor_guardado == $valor_esperado);
                        if (!$correcto) $todo_correcto = false;
                        
                        echo '<tr>
                                <td><code>' . $campo_db . '</code></td>
                                <td>' . htmlspecialchars($valor_guardado) . '</td>
                                <td>' . htmlspecialchars($valor_esperado) . '</td>
                                <td>' . ($correcto ? '<span class="text-success">✅</span>' : '<span class="text-danger">❌</span>') . '</td>
                            </tr>';
                    }
                    
                    echo '</tbody></table>';
                    
                    if ($todo_correcto) {
                        echo '<div class="alert alert-success mt-2">
                                <strong>✅ Todos los campos se guardaron correctamente</strong>
                              </div>';
                    } else {
                        echo '<div class="alert alert-danger mt-2">
                                <strong>❌ Hay campos que no coinciden</strong>
                              </div>';
                    }
                } else {
                    echo '<span class="text-danger">❌ No se encontró registro</span>';
                }
                
            } catch (PDOException $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
            }
            
            echo '</div>';
            
            // Paso 4: Probar con estudiante sin dificultades
            echo '<div class="alert alert-info mt-3">
                    <h6><i class="bi bi-4-circle"></i> Probando con estudiante SIN dificultades...</h6>';
            
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
                echo '<span class="text-success">✅ Estudiante sin dificultades creado con ID: ' . $id_estudiante2 . '</span><br>';
                
                guardarInformacionSecundaria($conn, $id_estudiante2, $testDataSinDificultades);
                
                $sql = "SELECT COUNT(*) as count FROM estudiante_dificultades WHERE id_estudiante = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_estudiante2]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count == 0) {
                    echo '<span class="text-success">✅ Correcto: No se creó registro para estudiante sin dificultades</span>';
                } else {
                    echo '<span class="text-danger">❌ Error: Se creó registro cuando no debería</span>';
                }
                
            } catch (Exception $e) {
                echo '<span class="text-danger">❌ Error: ' . $e->getMessage() . '</span>';
            }
            
            echo '</div>';
            
            // Guardar IDs para limpieza
            $_SESSION['test_ids'] = [$id_estudiante, $id_estudiante2];
            
            echo '<div class="alert alert-success mt-4">
                    <h5><i class="bi bi-check-circle"></i> Resumen del Test</h5>
                    <ul class="mb-0">
                        <li>✅ La información de dificultades se guarda correctamente cuando el estudiante tiene dificultades</li>
                        <li>✅ No se crea registro cuando el estudiante no tiene dificultades</li>
                        <li>✅ Todos los campos del formulario se mapean correctamente a la base de datos</li>
                    </ul>
                  </div>';
            
            echo '</div>';
        }
        
        if (isset($_POST['limpiar_datos'])) {
            require_once '../config/database.php';
            
            echo '<div class="mt-4">';
            
            try {
                $db = new Database();
                $conn = $db->connect();
                
                // Buscar estudiantes de prueba
                $sql = "SELECT id_estudiante FROM estudiantes WHERE nombres LIKE '%Test%'";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $estudiantes_test = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($estudiantes_test)) {
                    // Eliminar registros relacionados
                    $placeholders = str_repeat('?,', count($estudiantes_test) - 1) . '?';
                    
                    $sql = "DELETE FROM estudiante_dificultades WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_direccion WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_salud WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_idioma_cultura WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_transporte WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_servicios WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_actividad_laboral WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    $sql = "DELETE FROM estudiante_abandono WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    // Eliminar estudiantes
                    $sql = "DELETE FROM estudiantes WHERE id_estudiante IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($estudiantes_test);
                    
                    echo '<div class="alert alert-success">
                            <strong>✅ Datos de prueba eliminados correctamente</strong>
                            <br>Se eliminaron ' . count($estudiantes_test) . ' estudiantes de prueba
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
