<?php
session_start();
require_once '../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Obtener ID del estudiante
$id_estudiante = $_GET['id'] ?? null;
if (!$id_estudiante) {
    header('Location: estudiantes.php');
    exit();
}

// Obtener datos del estudiante y responsable
$sql = "SELECT e.id_estudiante, e.nombres, e.apellido_paterno, e.apellido_materno, e.genero, 
               e.rude, e.carnet_identidad, e.fecha_nacimiento, e.pais, e.provincia_departamento, 
               e.id_curso, e.id_responsable,
               r.id_responsable as resp_id_responsable, r.nombres as resp_nombres, 
               r.apellido_paterno as resp_apellido_paterno, r.apellido_materno as resp_apellido_materno, 
               r.carnet_identidad as resp_ci, r.fecha_nacimiento as resp_fecha_nacimiento, 
               r.grado_instruccion, r.idioma_frecuente, r.parentesco, r.celular as resp_celular
        FROM estudiantes e 
        LEFT JOIN responsables r ON e.id_responsable = r.id_responsable 
        WHERE e.id_estudiante = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_estudiante]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    header('Location: estudiantes.php');
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Datos del estudiante
        $nombres = trim($_POST['nombres']);
        $apellido_paterno = trim($_POST['apellido_paterno']);
        $apellido_materno = trim($_POST['apellido_materno']);
        $ci = trim($_POST['ci']);
        $genero = $_POST['genero'];
        $rude = trim($_POST['rude']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']) ?: null;
        $pais = trim($_POST['pais']);
        $provincia_departamento = trim($_POST['provincia_departamento']);
        $id_curso = $_POST['curso'];

        // Datos del responsable
        $resp_nombres = trim($_POST['resp_nombres']);
        $resp_apellido_paterno = trim($_POST['resp_apellido_paterno']);
        $resp_apellido_materno = trim($_POST['resp_apellido_materno']);
        $resp_ci = trim($_POST['resp_ci']);
        $resp_fecha_nacimiento = trim($_POST['resp_fecha_nacimiento']) ?: null;
        $resp_parentesco = trim($_POST['resp_parentesco']);
        $resp_celular = trim($_POST['resp_celular']);
        $resp_grado_instruccion = trim($_POST['resp_grado_instruccion']);
        $resp_idioma_frecuente = trim($_POST['resp_idioma_frecuente']);

        // Validaciones básicas del responsable
        if (empty($resp_nombres) || empty($resp_apellido_paterno) || empty($resp_ci) || empty($resp_parentesco)) {
            $error = 'Por favor, complete todos los campos obligatorios del responsable.';
        } else {
            // Debug: Verificar datos antes del procesamiento
            error_log("Estudiante ID: " . $id_estudiante);
            error_log("Estudiante tiene responsable: " . ($estudiante['id_responsable'] ? 'Sí' : 'No'));
            error_log("Responsable ID: " . ($estudiante['resp_id_responsable'] ?? 'N/A'));
            // Iniciar transacción
            $conn->beginTransaction();

            // Actualizar o insertar responsable
            if ($estudiante['id_responsable'] && $estudiante['resp_id_responsable']) {
                // Actualizar responsable existente
                $sqlResponsable = "UPDATE responsables SET 
                    nombres = ?, 
                    apellido_paterno = ?, 
                    apellido_materno = ?, 
                    carnet_identidad = ?, 
                    fecha_nacimiento = ?, 
                    grado_instruccion = ?, 
                    idioma_frecuente = ?, 
                    parentesco = ?, 
                    celular = ? 
                    WHERE id_responsable = ?";
                
                $stmtResponsable = $conn->prepare($sqlResponsable);
                $stmtResponsable->execute([
                    $resp_nombres,
                    $resp_apellido_paterno,
                    $resp_apellido_materno,
                    $resp_ci,
                    $resp_fecha_nacimiento,
                    $resp_grado_instruccion,
                    $resp_idioma_frecuente,
                    $resp_parentesco,
                    $resp_celular,
                    $estudiante['resp_id_responsable']
                ]);
            } else {
                // Insertar nuevo responsable
                $sqlResponsable = "INSERT INTO responsables 
                    (nombres, apellido_paterno, apellido_materno, carnet_identidad, fecha_nacimiento, 
                     grado_instruccion, idioma_frecuente, parentesco, celular)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmtResponsable = $conn->prepare($sqlResponsable);
                $stmtResponsable->execute([
                    $resp_nombres,
                    $resp_apellido_paterno,
                    $resp_apellido_materno,
                    $resp_ci,
                    $resp_fecha_nacimiento,
                    $resp_grado_instruccion,
                    $resp_idioma_frecuente,
                    $resp_parentesco,
                    $resp_celular
                ]);
                
                $id_responsable = $conn->lastInsertId();
            }

            // Actualizar estudiante
            $sqlEstudiante = "UPDATE estudiantes SET 
                nombres = ?, 
                apellido_paterno = ?, 
                apellido_materno = ?, 
                carnet_identidad = ?, 
                genero = ?, 
                rude = ?, 
                fecha_nacimiento = ?,
                pais = ?,
                provincia_departamento = ?,
                id_curso = ?";
            
            $params = [$nombres, $apellido_paterno, $apellido_materno, $ci, $genero, $rude, $fecha_nacimiento, $pais, $provincia_departamento, $id_curso];
            
            // Si se insertó un nuevo responsable, agregar la referencia
            if (!$estudiante['id_responsable'] || !$estudiante['resp_id_responsable']) {
                $sqlEstudiante .= ", id_responsable = ?";
                $params[] = $id_responsable;
            }
            
            $sqlEstudiante .= " WHERE id_estudiante = ?";
            $params[] = $id_estudiante;
            
            $stmtEstudiante = $conn->prepare($sqlEstudiante);
            $stmtEstudiante->execute($params);

            // Confirmar transacción
            $conn->commit();

            $_SESSION['success'] = 'Estudiante y responsable actualizados correctamente';
            header('Location: estudiantes.php');
            exit();
        }
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        $error = 'Error al actualizar: ' . $e->getMessage();
    }
}

// Obtener cursos
$sqlCursos = "SELECT id_curso, CONCAT(nivel, ' ', curso, '° ', paralelo) AS nombre 
              FROM cursos ORDER BY nivel, curso, paralelo";
$cursos = $conn->query($sqlCursos)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Estudiante</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #212c3a;
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 10px;
        }
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px 24px;
            width: 100%;
            max-width: 1000px;
        }
        .step-container { 
            background: #f8f9fa; 
            border-radius: 8px; 
            padding: 20px; 
            border-left: 4px solid #007bff;
        }
        .step-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
        }
        .step-container:nth-child(2) {
            border-left-color: #28a745;
        }
        .step-header h5 {
            font-weight: 600;
        }
        .step-header small {
            font-size: 0.85rem;
        }
        .form-label { 
            font-size: 0.95rem; 
            font-weight: 500; 
        }
        @media (max-width: 900px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            .main-content {
                margin-left: 0;
                padding: 20px 2px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php include '../includes/sidebar.php'; ?>
    </div>
    <div class="main-content">
        <div class="form-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="main-title">Editar Estudiante</h2>
                <a href="estudiantes.php" class="btn btn-outline-secondary">Volver</a>
            </div>
            
            <form method="POST">
                <!-- Paso 1: Información del Estudiante -->
                <div class="step-container mb-4">
                    <div class="step-header mb-3">
                        <h5 class="text-primary mb-0">
                            <i class="bi bi-person-circle"></i> Paso 1: Información del Estudiante
                        </h5>
                        <small class="text-muted">Complete los datos personales del estudiante</small>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nombres" class="form-label">Nombres*</label>
                            <input type="text" class="form-control" id="nombres" name="nombres"
                                   value="<?php echo htmlspecialchars($estudiante['nombres']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="apellido_paterno" class="form-label">Ap. Paterno*</label>
                            <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno"
                                   value="<?php echo htmlspecialchars($estudiante['apellido_paterno']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="apellido_materno" class="form-label">Ap. Materno</label>
                            <input type="text" class="form-control" id="apellido_materno" name="apellido_materno"
                                   value="<?php echo htmlspecialchars($estudiante['apellido_materno']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="rude" class="form-label">RUDE*</label>
                            <input type="text" class="form-control" id="rude" name="rude"
                                   value="<?php echo htmlspecialchars($estudiante['rude']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="ci" class="form-label">CI*</label>
                            <input type="text" class="form-control" id="ci" name="ci"
                                   value="<?php echo htmlspecialchars($estudiante['carnet_identidad']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_nacimiento" class="form-label">F. Nacimiento*</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                   value="<?php echo $estudiante['fecha_nacimiento'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="genero" class="form-label">Género*</label>
                            <select class="form-select" id="genero" name="genero" required>
                                <option value="">Seleccionar</option>
                                <option value="Masculino" <?php echo $estudiante['genero'] === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="Femenino" <?php echo $estudiante['genero'] === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pais" class="form-label">País*</label>
                            <select class="form-select" id="pais" name="pais" required>
                                <option value="">Seleccionar</option>
                                <option value="Bolivia" <?php echo ($estudiante['pais'] ?? '') === 'Bolivia' ? 'selected' : ''; ?>>Bolivia</option>
                                <option value="Chile" <?php echo ($estudiante['pais'] ?? '') === 'Chile' ? 'selected' : ''; ?>>Chile</option>
                                <option value="Argentina" <?php echo ($estudiante['pais'] ?? '') === 'Argentina' ? 'selected' : ''; ?>>Argentina</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="provincia_departamento" class="form-label">Provincia/Departamento*</label>
                            <input type="text" class="form-control" id="provincia_departamento" name="provincia_departamento"
                                   value="<?php echo htmlspecialchars($estudiante['provincia_departamento'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="curso" class="form-label">Curso*</label>
                            <select class="form-select" id="curso" name="curso" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso['id_curso']; ?>"
                                    <?php echo $curso['id_curso'] == $estudiante['id_curso'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($curso['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Paso 2: Información del Responsable -->
                <div class="step-container mb-4">
                    <div class="step-header mb-3">
                        <h5 class="text-success mb-0">
                            <i class="bi bi-person-badge"></i> Paso 2: Información del Responsable
                        </h5>
                        <small class="text-muted">Complete los datos del responsable del estudiante</small>
                    </div>
                    
                    <div class="row g-3">
                    <div class="col-md-4">
                        <label for="resp_nombres" class="form-label">Nombres del Responsable*</label>
                        <input type="text" class="form-control" id="resp_nombres" name="resp_nombres"
                               value="<?php echo htmlspecialchars($estudiante['resp_nombres'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="resp_apellido_paterno" class="form-label">Ap. Paterno*</label>
                        <input type="text" class="form-control" id="resp_apellido_paterno" name="resp_apellido_paterno"
                               value="<?php echo htmlspecialchars($estudiante['resp_apellido_paterno'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="resp_apellido_materno" class="form-label">Ap. Materno</label>
                        <input type="text" class="form-control" id="resp_apellido_materno" name="resp_apellido_materno"
                               value="<?php echo htmlspecialchars($estudiante['resp_apellido_materno'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="resp_ci" class="form-label">CI del Responsable*</label>
                        <input type="text" class="form-control" id="resp_ci" name="resp_ci"
                               value="<?php echo htmlspecialchars($estudiante['resp_ci'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="resp_fecha_nacimiento" class="form-label">F. Nacimiento</label>
                        <input type="date" class="form-control" id="resp_fecha_nacimiento" name="resp_fecha_nacimiento"
                               value="<?php echo $estudiante['resp_fecha_nacimiento'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="resp_parentesco" class="form-label">Parentesco*</label>
                        <select class="form-select" id="resp_parentesco" name="resp_parentesco" required>
                            <option value="">Seleccionar</option>
                            <option value="Padre" <?php echo ($estudiante['parentesco'] ?? '') === 'Padre' ? 'selected' : ''; ?>>Padre</option>
                            <option value="Madre" <?php echo ($estudiante['parentesco'] ?? '') === 'Madre' ? 'selected' : ''; ?>>Madre</option>
                            <option value="Tutor" <?php echo ($estudiante['parentesco'] ?? '') === 'Tutor' ? 'selected' : ''; ?>>Tutor</option>
                            <option value="Otro" <?php echo ($estudiante['parentesco'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="resp_celular" class="form-label">Celular</label>
                        <input type="text" class="form-control" id="resp_celular" name="resp_celular"
                               value="<?php echo htmlspecialchars($estudiante['resp_celular'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="resp_grado_instruccion" class="form-label">Grado de Instrucción</label>
                        <select class="form-select" id="resp_grado_instruccion" name="resp_grado_instruccion">
                            <option value="">Seleccionar</option>
                            <option value="Ninguno" <?php echo ($estudiante['grado_instruccion'] ?? '') === 'Ninguno' ? 'selected' : ''; ?>>Ninguno</option>
                            <option value="Primaria" <?php echo ($estudiante['grado_instruccion'] ?? '') === 'Primaria' ? 'selected' : ''; ?>>Primaria</option>
                            <option value="Secundaria" <?php echo ($estudiante['grado_instruccion'] ?? '') === 'Secundaria' ? 'selected' : ''; ?>>Secundaria</option>
                            <option value="Técnico" <?php echo ($estudiante['grado_instruccion'] ?? '') === 'Técnico' ? 'selected' : ''; ?>>Técnico</option>
                            <option value="Universitario" <?php echo ($estudiante['grado_instruccion'] ?? '') === 'Universitario' ? 'selected' : ''; ?>>Universitario</option>
                            <option value="Postgrado" <?php echo ($estudiante['grado_instruccion'] ?? '') === 'Postgrado' ? 'selected' : ''; ?>>Postgrado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="resp_idioma_frecuente" class="form-label">Idioma Frecuente</label>
                        <select class="form-select" id="resp_idioma_frecuente" name="resp_idioma_frecuente">
                            <option value="">Seleccionar</option>
                            <option value="Español" <?php echo ($estudiante['idioma_frecuente'] ?? '') === 'Español' ? 'selected' : ''; ?>>Español</option>
                            <option value="Inglés" <?php echo ($estudiante['idioma_frecuente'] ?? '') === 'Inglés' ? 'selected' : ''; ?>>Inglés</option>
                            <option value="Quechua" <?php echo ($estudiante['idioma_frecuente'] ?? '') === 'Quechua' ? 'selected' : ''; ?>>Quechua</option>
                            <option value="Aymara" <?php echo ($estudiante['idioma_frecuente'] ?? '') === 'Aymara' ? 'selected' : ''; ?>>Aymara</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
