<?php
session_start();
require_once '../config/database.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Obtener estudiantes con filtro de búsqueda si existe
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "
    SELECT
        e.id_estudiante,
        e.nombres,
        e.apellido_paterno,
        e.apellido_materno,
        e.carnet_identidad AS ci,
        e.genero,
        e.rude,
        e.fecha_nacimiento,
        c.nivel,
        c.curso,
        c.paralelo,
        CONCAT(c.nivel, ' ', c.curso, '° ', c.paralelo) AS nombre_curso,
        r.nombres AS resp_nombres,
        r.apellido_paterno AS resp_apellido_paterno,
        r.apellido_materno AS resp_apellido_materno,
        r.parentesco,
        r.celular AS resp_celular
    FROM estudiantes e
    LEFT JOIN cursos c ON e.id_curso = c.id_curso
    LEFT JOIN responsables r ON e.id_responsable = r.id_responsable
";

if (!empty($search)) {
    $sql .= " WHERE e.carnet_identidad LIKE :search
              OR e.nombres LIKE :search
              OR e.apellido_paterno LIKE :search
              OR e.apellido_materno LIKE :search";
}

$sql .= " ORDER BY e.apellido_paterno ASC, e.apellido_materno ASC";
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $stmt->bindParam(':search', $searchTerm);
}

$stmt->execute();
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Estudiantes</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; margin: 0; padding: 0; }
        .main-title { margin: 0; font-weight: bold; color: #11305e; }
        .btn-nuevo { background-color: #28a745; color: white; border-radius: 5px; }
        .btn-nuevo:hover { background-color: #218838; color: white; }
        .tabla-box { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; }
        .table-estudiantes th { background-color: #11305e; color: white; font-weight: 500; position: sticky; top: 0; }
        .table-estudiantes tr:nth-child(even) { background-color: #f4f8fb; }
        .table-estudiantes tr:hover { background-color: #e9f5ff; }
        .acciones-cell { display: flex; gap: 5px; }
        .btn-accion { padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; display: flex; align-items: center; gap: 3px; }
        .btn-editar { background-color: #17a2b8; color: white; }
        .btn-editar:hover { background-color: #138496; color: white; }
        .btn-eliminar { background-color: #dc3545; color: white; }
        .btn-eliminar:hover { background-color: #c82333; color: white; }
        .modal-xl { max-width: 1000px; }
        .modal-header { background: #11305e; color: white; }
        .modal-title { font-size: 1.1rem; }
        .form-label { font-size: 0.95rem; font-weight: 500; }
        .form-control, .form-select { font-size: 0.96rem; }
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
        @media (max-width: 991px) {
            .tabla-box, .table-responsive { max-height: 55vh; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid g-0">
        <div class="row g-0">
            <?php include '../includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="main-title">Listado de Estudiantes</h1>
                    <div class="d-flex gap-3 align-items-center">
                        <form class="d-flex" method="get" action="estudiantes.php">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text"
                                       name="search"
                                       id="searchInput"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por CI, nombre o apellido"
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                       oninput="filtrarEstudiantes()">
                                <?php if (!empty($search)): ?>
                                <button id="clearSearch" class="btn btn-outline-secondary border-start-0" type="button" onclick="window.location.href='estudiantes.php'">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        <script>
                        function filtrarEstudiantes() {
                            const input = document.getElementById('searchInput');
                            const filter = input.value.toLowerCase();
                            const table = document.querySelector('.table-estudiantes');
                            const rows = table.querySelectorAll('tbody tr');
                            
                            rows.forEach(row => {
                                const text = row.textContent.toLowerCase();
                                row.style.display = text.includes(filter) ? '' : 'none';
                            });
                        }
                        </script>
                        <button type="button" class="btn-nuevo" data-bs-toggle="modal" data-bs-target="#modalNuevoEstudiante">
                            <i class="bi bi-plus-lg"></i> Nuevo Estudiante
                        </button>
                    </div>
                </div>

                <div class="tabla-box">
                    <div class="table-responsive" style="max-height:70vh;">
                        <table class="table table-hover table-estudiantes align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Ap. Paterno</th>
                                    <th>Ap. Materno</th>
                                    <th>Nombres</th>
                                    <th>CI</th>
                                    <th>Género</th>
                                    <th>RUDE</th>
                                    <th>Responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estudiantes as $estudiante): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($estudiante['apellido_paterno']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['apellido_materno']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['ci']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['genero']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['rude']); ?></td>
                                    <td>
                                        <?php if ($estudiante['resp_nombres']): ?>
                                            <small class="text-muted">
                                                <strong><?php echo htmlspecialchars($estudiante['resp_nombres'] . ' ' . $estudiante['resp_apellido_paterno']); ?></strong><br>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($estudiante['parentesco']); ?></span>
                                                <?php if ($estudiante['resp_celular']): ?>
                                                    <br><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($estudiante['resp_celular']); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">Sin responsable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="editar_estudiante.php?id=<?php echo $estudiante['id_estudiante']; ?>"
                                               class="btn btn-accion btn-editar" title="Editar">
                                               <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="eliminar_estudiante.php?id=<?php echo $estudiante['id_estudiante']; ?>"
                                               class="btn btn-accion btn-eliminar"
                                               onclick="return confirm('¿Está seguro de eliminar este estudiante?')" title="Eliminar">
                                               <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Nuevo Estudiante -->
    <div class="modal fade" id="modalNuevoEstudiante" tabindex="-1" aria-labelledby="modalNuevoEstudianteLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title mb-0" id="modalNuevoEstudianteLabel">Registro de Estudiante</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formNuevoEstudiante" action="guardar_estudiante.php" method="POST">
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
                                    <input type="text" class="form-control" id="nombres" name="nombres" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellido_paterno" class="form-label">Ap. Paterno*</label>
                                    <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellido_materno" class="form-label">Ap. Materno</label>
                                    <input type="text" class="form-control" id="apellido_materno" name="apellido_materno">
                                </div>
                                <div class="col-md-3">
                                    <label for="rude" class="form-label">RUDE*</label>
                                    <input type="text" class="form-control" id="rude" name="rude" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="ci" class="form-label">CI*</label>
                                    <input type="text" class="form-control" id="ci" name="ci" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="fecha_nacimiento" class="form-label">F. Nacimiento*</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="genero" class="form-label">Género*</label>
                                    <select class="form-select" id="genero" name="genero" required>
                                        <option value="">Seleccionar</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Femenino">Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="pais" class="form-label">País*</label>
                                    <select class="form-select" id="pais" name="pais" required>
                                        <option value="">Seleccionar</option>
                                        <option value="Bolivia">Bolivia</option>
                                        <option value="Chile">Chile</option>
                                        <option value="Argentina">Argentina</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="provincia_departamento" class="form-label">Provincia/Departamento*</label>
                                    <input type="text" class="form-control" id="provincia_departamento" name="provincia_departamento" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="curso" class="form-label">Curso*</label>
                                    <select class="form-select" id="curso" name="curso" required>
                                        <option value="">Seleccionar</option>
                                        <?php
                                        $sqlCursos = "SELECT id_curso, CONCAT(nivel, ' ', curso, '° ', paralelo) AS nombre FROM cursos ORDER BY nivel, curso, paralelo";
                                        $stmtCursos = $conn->query($sqlCursos);
                                        while ($curso = $stmtCursos->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="'.$curso['id_curso'].'">'.$curso['nombre'].'</option>';
                                        }
                                        ?>
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
                                <input type="text" class="form-control" id="resp_nombres" name="resp_nombres" required>
                            </div>
                            <div class="col-md-4">
                                <label for="resp_apellido_paterno" class="form-label">Ap. Paterno*</label>
                                <input type="text" class="form-control" id="resp_apellido_paterno" name="resp_apellido_paterno" required>
                            </div>
                            <div class="col-md-4">
                                <label for="resp_apellido_materno" class="form-label">Ap. Materno</label>
                                <input type="text" class="form-control" id="resp_apellido_materno" name="resp_apellido_materno">
                            </div>
                            <div class="col-md-3">
                                <label for="resp_ci" class="form-label">CI del Responsable*</label>
                                <input type="text" class="form-control" id="resp_ci" name="resp_ci" required>
                            </div>
                            <div class="col-md-3">
                                <label for="resp_fecha_nacimiento" class="form-label">F. Nacimiento</label>
                                <input type="date" class="form-control" id="resp_fecha_nacimiento" name="resp_fecha_nacimiento">
                            </div>
                            <div class="col-md-3">
                                <label for="resp_parentesco" class="form-label">Parentesco*</label>
                                <select class="form-select" id="resp_parentesco" name="resp_parentesco" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Padre">Padre</option>
                                    <option value="Madre">Madre</option>
                                    <option value="Tutor">Tutor</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="resp_celular" class="form-label">Celular</label>
                                <input type="text" class="form-control" id="resp_celular" name="resp_celular">
                            </div>
                            <div class="col-md-6">
                                <label for="resp_grado_instruccion" class="form-label">Grado de Instrucción</label>
                                <select class="form-select" id="resp_grado_instruccion" name="resp_grado_instruccion">
                                    <option value="">Seleccionar</option>
                                    <option value="Ninguno">Ninguno</option>
                                    <option value="Primaria">Primaria</option>
                                    <option value="Secundaria">Secundaria</option>
                                    <option value="Técnico">Técnico</option>
                                    <option value="Universitario">Universitario</option>
                                    <option value="Postgrado">Postgrado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="resp_idioma_frecuente" class="form-label">Idioma Frecuente</label>
                                <select class="form-select" id="resp_idioma_frecuente" name="resp_idioma_frecuente">
                                    <option value="">Seleccionar</option>
                                    <option value="Español">Español</option>
                                    <option value="Inglés">Inglés</option>
                                    <option value="Quechua">Quechua</option>
                                    <option value="Aymara">Aymara</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formNuevoEstudiante" class="btn btn-sm btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
