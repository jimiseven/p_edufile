<?php
// Incluir conexión a la base de datos
include 'conexion.php';

// Verificar si se recibieron todos los parámetros necesarios
if (!isset($_GET['student_id'])) {
    die("Error: Faltan parámetros en la URL. Se requiere student_id.");
}

$student_id = $_GET['student_id'];

// Obtener datos del estudiante y su estado
$query = "SELECT s.*, sc.status, c.grade, c.parallel, l.name AS level_name 
          FROM students s
          LEFT JOIN student_courses sc ON s.id = sc.student_id
          LEFT JOIN courses c ON sc.course_id = c.id
          LEFT JOIN levels l ON c.level_id = l.id
          WHERE s.rude_number = ? 
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: El estudiante no existe.");
}

$student = $result->fetch_assoc();

// Obtener la fuente de la solicitud
$source = isset($_GET['source']) ? $_GET['source'] : 'estudiantes';
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$parallel = isset($_GET['parallel']) ? $_GET['parallel'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1E2A38;
            color: #ffffff;
        }

        .form-container {
            background-color: #2C3E50;
            padding: 2rem;
            border-radius: 10px;
            max-width: 1200px;
            margin: 2rem auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #34495E;
            border-radius: 8px;
        }

        .form-section h4 {
            color: #ffffff;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #47535f;
        }

        .form-label {
            color: #ECF0F1;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            background-color: #3E4A59;
            border: 1px solid #47535f;
            color: #ffffff;
            padding: 0.5rem 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #47535f;
            border-color: #3498db;
            box-shadow: none;
            color: #ffffff;
        }

        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 0.5rem 1.5rem;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-delete {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .status-select {
            max-width: 200px;
            margin-left: auto;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
            border: none;
            margin-bottom: 1rem;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }
        .form-section.curso-info h4 {
            color: #ECF0F1; /* Color de texto para "Información del Curso" */
        }
        .form-section.curso-info .form-control {
            color: #ECF0F1; /* Color de texto para los inputs de "Información del Curso" */
        }
        /* Estilos para las pestañas */
        .nav-tabs .nav-link {
            background-color: #34495E;
            color: #ECF0F1;
            border: 1px solid #47535f;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            padding: 1rem 1.5rem;
            margin-right: 0.2rem;
        }

        .nav-tabs .nav-link.active {
            background-color: #3E4A59;
            color: #ffffff;
            border-bottom: none;
        }
        .tab-content {
            background-color: #3E4A59;
            padding: 1.5rem;
            border-radius: 0 0 8px 8px;
            border: 1px solid #47535f;
        }
        .form-section.curso-info .row > div {
            margin-bottom: 1rem;
        }
        .form-section.curso-info .form-control {
            background-color: #47535f;
            border: 1px solid #47535f;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="main-content flex-grow-1 p-3">
            <div class="form-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Editar Estudiante</h2>
                    <button class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        Eliminar Estudiante
                    </button>
                </div>
                <!-- Botón de Atrás -->
                <button class="btn btn-back" onclick="goBack()">Atrás</button>

                <form action="actualizarEstudiante.php" method="POST">
                    <!-- Asegurarse de que estos campos están incluidos en el formulario -->
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                    <input type="hidden" name="original_rude" value="<?php echo htmlspecialchars($student['rude_number']); ?>">
                    <?php if ($source === 'vistaGenCurso') : ?>
                        <input type="hidden" name="grade" value="<?php echo htmlspecialchars($grade); ?>">
                        <input type="hidden" name="parallel" value="<?php echo htmlspecialchars($parallel); ?>">
                        <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
                    <?php endif; ?>

                    <!-- Estado del Estudiante -->
                    <div class="d-flex justify-content-end mb-4">
                        <div class="status-select">
                            <label for="student_status" class="form-label">Estado del Estudiante</label>
                            <select class="form-select" id="student_status" name="student_status" required>
                                <option value="Efectivo - I" <?php echo ($student['status'] === 'Efectivo - I') ? 'selected' : ''; ?>>Efectivo - I</option>
                                <option value="No Inscrito" <?php echo ($student['status'] === 'No Inscrito') ? 'selected' : ''; ?>>No Inscrito</option>
                            </select>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="student-info-tab" data-bs-toggle="tab" data-bs-target="#student-info" type="button" role="tab" aria-controls="student-info" aria-selected="true">Información del Estudiante</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="guardian-info-tab" data-bs-toggle="tab" data-bs-target="#guardian-info" type="button" role="tab" aria-controls="guardian-info" aria-selected="false">Información del Responsable</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="course-info-tab" data-bs-toggle="tab" data-bs-target="#course-info" type="button" role="tab" aria-controls="course-info" aria-selected="false">Información del Curso</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="student-info" role="tabpanel" aria-labelledby="student-info-tab">
                            <!-- Información del Estudiante -->
                            <div class="form-section">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="first_name" class="form-label">Nombres</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                            value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="last_name_father" class="form-label">Apellido Paterno</label>
                                        <input type="text" class="form-control" id="last_name_father" name="last_name_father"
                                            value="<?php echo htmlspecialchars($student['last_name_father']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="last_name_mother" class="form-label">Apellido Materno</label>
                                        <input type="text" class="form-control" id="last_name_mother" name="last_name_mother"
                                            value="<?php echo htmlspecialchars($student['last_name_mother']); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="identity_card" class="form-label">CI</label>
                                        <input type="text" class="form-control" id="identity_card" name="identity_card"
                                            value="<?php echo htmlspecialchars($student['identity_card']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gender" class="form-label">Sexo</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="M" <?php echo $student['gender'] === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="F" <?php echo $student['gender'] === 'F' ? 'selected' : ''; ?>>Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="birth_date" class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date"
                                            value="<?php echo htmlspecialchars($student['birth_date']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="rude_number" class="form-label">RUDE</label>
                                        <input type="text" class="form-control" id="rude_number" name="rude_number"
                                            value="<?php echo htmlspecialchars($student['rude_number']); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="guardian-info" role="tabpanel" aria-labelledby="guardian-info-tab">
                            <!-- Información del Responsable -->
                            <div class="form-section">
                                <h4>Información del Responsable</h4>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="guardian_first_name" class="form-label">Nombres</label>
                                        <input type="text" class="form-control" id="guardian_first_name" name="guardian_first_name"
                                            value="<?php echo htmlspecialchars($student['guardian_first_name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="guardian_last_name" class="form-label">Apellidos</label>
                                        <input type="text" class="form-control" id="guardian_last_name" name="guardian_last_name"
                                            value="<?php echo htmlspecialchars($student['guardian_last_name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="guardian_identity_card" class="form-label">CI</label>
                                        <input type="text" class="form-control" id="guardian_identity_card" name="guardian_identity_card"
                                            value="<?php echo htmlspecialchars($student['guardian_identity_card']); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="guardian_phone_number" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" id="guardian_phone_number" name="guardian_phone_number"
                                            value="<?php echo htmlspecialchars($student['guardian_phone_number']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="guardian_relationship" class="form-label">Relación</label>
                                        <select class="form-select" id="guardian_relationship" name="guardian_relationship">
                                            <option value="padre" <?php echo $student['guardian_relationship'] === 'padre' ? 'selected' : ''; ?>>Padre</option>
                                            <option value="madre" <?php echo $student['guardian_relationship'] === 'madre' ? 'selected' : ''; ?>>Madre</option>
                                            <option value="tutor" <?php echo $student['guardian_relationship'] === 'tutor' ? 'selected' : ''; ?>>Tutor</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="course-info" role="tabpanel" aria-labelledby="course-info-tab">
                            <!-- Información del Curso -->
                            <div class="form-section curso-info">
                                <h4>Información del Curso</h4>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Nivel</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['level_name']); ?>" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Grado</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['grade']); ?>" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Paralelo</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['parallel']); ?>" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="volanteEstudiante.php?student_id=<?php echo $student_id; ?>" target="_blank" class="btn btn-secondary">Vista Volante</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                </div>
                <div class="modal-body" style="color:rgb(0, 0, 0);">
                    <p>¿Está seguro de que desea eliminar al estudiante
                        <strong><?php echo htmlspecialchars($student['first_name']); ?></strong>? Esta acción no se
                        puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="eliminarEstudiante.php?student_id=<?php echo $student_id; ?>" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        function goBack() {
            // Usar el historial del navegador para regresar a la página anterior
            if (document.referrer.includes('vistaGenCurso.php')) {
                window.location.href = 'vistaGenCurso.php?grade=<?php echo urlencode($grade); ?>&parallel=<?php echo urlencode($parallel); ?>&level=<?php echo urlencode($level); ?>';
            } else {
                window.location.href = 'estudiantes.php';
            }
        }
    </script>
</body>

</html>
