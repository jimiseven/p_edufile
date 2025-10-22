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
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        .nav-tabs .nav-link {
            border: none;
            border-radius: 0;
            color: #333333 !important;
            font-weight: 600;
            padding: 12px 16px;
            margin-right: 2px;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #000000 !important;
            background-color: #f8f9fa;
        }
        .nav-tabs .nav-link.active {
            color: #000000 !important;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
            border-bottom: 3px solid #007bff;
            font-weight: 700;
        }
        .tab-content {
            background: #fff;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .tab-pane {
            min-height: 200px;
        }
        .nav-tabs .nav-link i {
            margin-right: 5px;
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
                        
                        <!-- Información Secundaria con Pestañas -->
                        <div class="mt-4">
                            <h6 class="text-info mb-3">
                                <i class="bi bi-info-circle"></i> Información Adicional (Opcional)
                            </h6>
                            
                            <!-- Pestañas -->
                            <ul class="nav nav-tabs" id="infoTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="direccion-tab" data-bs-toggle="tab" data-bs-target="#direccion" type="button" role="tab">
                                        <i class="bi bi-geo-alt"></i> Dirección
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="salud-tab" data-bs-toggle="tab" data-bs-target="#salud" type="button" role="tab">
                                        <i class="bi bi-heart-pulse"></i> Salud
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="idioma-tab" data-bs-toggle="tab" data-bs-target="#idioma" type="button" role="tab">
                                        <i class="bi bi-translate"></i> Idioma/Cultura
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="transporte-tab" data-bs-toggle="tab" data-bs-target="#transporte" type="button" role="tab">
                                        <i class="bi bi-bus-front"></i> Transporte
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="servicios-tab" data-bs-toggle="tab" data-bs-target="#servicios" type="button" role="tab">
                                        <i class="bi bi-gear"></i> Servicios
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="laboral-tab" data-bs-toggle="tab" data-bs-target="#laboral" type="button" role="tab">
                                        <i class="bi bi-briefcase"></i> Laboral
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="dificultades-tab" data-bs-toggle="tab" data-bs-target="#dificultades" type="button" role="tab">
                                        <i class="bi bi-exclamation-triangle"></i> Dificultades
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="abandono-tab" data-bs-toggle="tab" data-bs-target="#abandono" type="button" role="tab">
                                        <i class="bi bi-person-x"></i> Abandono
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Contenido de las pestañas -->
                            <div class="tab-content" id="infoTabsContent">
                                <!-- Pestaña Dirección -->
                                <div class="tab-pane fade show active" id="direccion" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="dir_departamento" class="form-label">Departamento</label>
                                                <input type="text" class="form-control" id="dir_departamento" name="dir_departamento">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dir_provincia" class="form-label">Provincia</label>
                                                <input type="text" class="form-control" id="dir_provincia" name="dir_provincia">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dir_municipio" class="form-label">Municipio</label>
                                                <input type="text" class="form-control" id="dir_municipio" name="dir_municipio">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dir_localidad" class="form-label">Localidad</label>
                                                <input type="text" class="form-control" id="dir_localidad" name="dir_localidad">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dir_comunidad" class="form-label">Comunidad</label>
                                                <input type="text" class="form-control" id="dir_comunidad" name="dir_comunidad">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dir_zona" class="form-label">Zona</label>
                                                <input type="text" class="form-control" id="dir_zona" name="dir_zona">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dir_numero_vivienda" class="form-label">Número de Vivienda</label>
                                                <input type="text" class="form-control" id="dir_numero_vivienda" name="dir_numero_vivienda">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="dir_telefono" class="form-label">Teléfono</label>
                                                <input type="text" class="form-control" id="dir_telefono" name="dir_telefono">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="dir_celular" class="form-label">Celular</label>
                                                <input type="text" class="form-control" id="dir_celular" name="dir_celular">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Salud -->
                                <div class="tab-pane fade" id="salud" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="sal_tiene_seguro" class="form-label">¿Tiene seguro médico?</label>
                                                <select class="form-select" id="sal_tiene_seguro" name="sal_tiene_seguro">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="sal_acceso_posta" class="form-label">¿Tiene acceso a posta?</label>
                                                <select class="form-select" id="sal_acceso_posta" name="sal_acceso_posta">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="sal_acceso_centro_salud" class="form-label">¿Tiene acceso a centro de salud?</label>
                                                <select class="form-select" id="sal_acceso_centro_salud" name="sal_acceso_centro_salud">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="sal_acceso_hospital" class="form-label">¿Tiene acceso a hospital?</label>
                                                <select class="form-select" id="sal_acceso_hospital" name="sal_acceso_hospital">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Idioma/Cultura -->
                                <div class="tab-pane fade" id="idioma" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="idi_idioma" class="form-label">Idioma</label>
                                                <input type="text" class="form-control" id="idi_idioma" name="idi_idioma">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="idi_cultura" class="form-label">Cultura</label>
                                                <input type="text" class="form-control" id="idi_cultura" name="idi_cultura">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Transporte -->
                                <div class="tab-pane fade" id="transporte" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="trans_medio" class="form-label">Medio de Transporte</label>
                                                <select class="form-select" id="trans_medio" name="trans_medio">
                                                    <option value="">Seleccionar</option>
                                                    <option value="a_pie">A pie</option>
                                                    <option value="vehiculo">Vehículo</option>
                                                    <option value="fluvial">Fluvial</option>
                                                    <option value="otro">Otro</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="trans_tiempo_llegada" class="form-label">Tiempo de Llegada</label>
                                                <select class="form-select" id="trans_tiempo_llegada" name="trans_tiempo_llegada">
                                                    <option value="">Seleccionar</option>
                                                    <option value="menos_media_hora">Menos de media hora</option>
                                                    <option value="mas_media_hora">Más de media hora</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Servicios -->
                                <div class="tab-pane fade" id="servicios" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="serv_agua_caneria" class="form-label">¿Tiene agua por cañería?</label>
                                                <select class="form-select" id="serv_agua_caneria" name="serv_agua_caneria">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serv_bano" class="form-label">¿Tiene baño?</label>
                                                <select class="form-select" id="serv_bano" name="serv_bano">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serv_alcantarillado" class="form-label">¿Tiene alcantarillado?</label>
                                                <select class="form-select" id="serv_alcantarillado" name="serv_alcantarillado">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serv_internet" class="form-label">¿Tiene internet?</label>
                                                <select class="form-select" id="serv_internet" name="serv_internet">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serv_energia" class="form-label">¿Tiene energía eléctrica?</label>
                                                <select class="form-select" id="serv_energia" name="serv_energia">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serv_recojo_basura" class="form-label">¿Tiene recolección de basura?</label>
                                                <select class="form-select" id="serv_recojo_basura" name="serv_recojo_basura">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serv_tipo_vivienda" class="form-label">Tipo de Vivienda</label>
                                                <select class="form-select" id="serv_tipo_vivienda" name="serv_tipo_vivienda">
                                                    <option value="">Seleccionar</option>
                                                    <option value="alquilada">Alquilada</option>
                                                    <option value="propia">Propia</option>
                                                    <option value="cedida">Cedida</option>
                                                    <option value="anticretico">Anticrético</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Laboral -->
                                <div class="tab-pane fade" id="laboral" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="lab_trabajo" class="form-label">¿Trabaja?</label>
                                                <select class="form-select" id="lab_trabajo" name="lab_trabajo">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lab_meses_trabajo" class="form-label">Meses de Trabajo</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="lab_enero" name="lab_meses_trabajo[]" value="enero">
                                                    <label class="form-check-label" for="lab_enero">Enero</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="lab_febrero" name="lab_meses_trabajo[]" value="febrero">
                                                    <label class="form-check-label" for="lab_febrero">Febrero</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="lab_marzo" name="lab_meses_trabajo[]" value="marzo">
                                                    <label class="form-check-label" for="lab_marzo">Marzo</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="lab_abril" name="lab_meses_trabajo[]" value="abril">
                                                    <label class="form-check-label" for="lab_abril">Abril</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lab_actividad" class="form-label">Actividad</label>
                                                <input type="text" class="form-control" id="lab_actividad" name="lab_actividad">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lab_turno_manana" class="form-label">Turno Mañana</label>
                                                <select class="form-select" id="lab_turno_manana" name="lab_turno_manana">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lab_turno_tarde" class="form-label">Turno Tarde</label>
                                                <select class="form-select" id="lab_turno_tarde" name="lab_turno_tarde">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lab_turno_noche" class="form-label">Turno Noche</label>
                                                <select class="form-select" id="lab_turno_noche" name="lab_turno_noche">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lab_frecuencia" class="form-label">Frecuencia</label>
                                                <select class="form-select" id="lab_frecuencia" name="lab_frecuencia">
                                                    <option value="">Seleccionar</option>
                                                    <option value="todos_dias">Todos los días</option>
                                                    <option value="dias_habiles">Días hábiles</option>
                                                    <option value="fin_de_semana">Fin de semana</option>
                                                    <option value="esporadico">Esporádico</option>
                                                    <option value="dias_festivos">Días festivos</option>
                                                    <option value="vacaciones">Vacaciones</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Dificultades -->
                                <div class="tab-pane fade" id="dificultades" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="dif_tiene_dificultad" class="form-label">¿Tiene dificultades?</label>
                                                <select class="form-select" id="dif_tiene_dificultad" name="dif_tiene_dificultad">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dif_auditiva" class="form-label">Dificultad Auditiva</label>
                                                <select class="form-select" id="dif_auditiva" name="dif_auditiva">
                                                    <option value="ninguna">Ninguna</option>
                                                    <option value="leve">Leve</option>
                                                    <option value="grave">Grave</option>
                                                    <option value="muy_grave">Muy grave</option>
                                                    <option value="multiple">Múltiple</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dif_visual" class="form-label">Dificultad Visual</label>
                                                <select class="form-select" id="dif_visual" name="dif_visual">
                                                    <option value="ninguna">Ninguna</option>
                                                    <option value="leve">Leve</option>
                                                    <option value="grave">Grave</option>
                                                    <option value="muy_grave">Muy grave</option>
                                                    <option value="multiple">Múltiple</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dif_intelectual" class="form-label">Dificultad Intelectual</label>
                                                <select class="form-select" id="dif_intelectual" name="dif_intelectual">
                                                    <option value="ninguna">Ninguna</option>
                                                    <option value="leve">Leve</option>
                                                    <option value="grave">Grave</option>
                                                    <option value="muy_grave">Muy grave</option>
                                                    <option value="multiple">Múltiple</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dif_fisico_motora" class="form-label">Dificultad Físico-Motora</label>
                                                <select class="form-select" id="dif_fisico_motora" name="dif_fisico_motora">
                                                    <option value="ninguna">Ninguna</option>
                                                    <option value="leve">Leve</option>
                                                    <option value="grave">Grave</option>
                                                    <option value="muy_grave">Muy grave</option>
                                                    <option value="multiple">Múltiple</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dif_psiquica_mental" class="form-label">Dificultad Psíquica/Mental</label>
                                                <select class="form-select" id="dif_psiquica_mental" name="dif_psiquica_mental">
                                                    <option value="ninguna">Ninguna</option>
                                                    <option value="leve">Leve</option>
                                                    <option value="grave">Grave</option>
                                                    <option value="muy_grave">Muy grave</option>
                                                    <option value="multiple">Múltiple</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dif_autista" class="form-label">Dificultad Autista</label>
                                                <select class="form-select" id="dif_autista" name="dif_autista">
                                                    <option value="ninguna">Ninguna</option>
                                                    <option value="leve">Leve</option>
                                                    <option value="grave">Grave</option>
                                                    <option value="muy_grave">Muy grave</option>
                                                    <option value="multiple">Múltiple</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Abandono -->
                                <div class="tab-pane fade" id="abandono" role="tabpanel">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="aba_abandono" class="form-label">¿Abandonó estudios?</label>
                                                <select class="form-select" id="aba_abandono" name="aba_abandono">
                                                    <option value="0">No</option>
                                                    <option value="1">Sí</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="aba_motivo" class="form-label">Motivo de Abandono</label>
                                                <select class="form-select" id="aba_motivo" name="aba_motivo">
                                                    <option value="">Seleccionar</option>
                                                    <option value="trabajo">Trabajo</option>
                                                    <option value="falta_dinero">Falta de dinero</option>
                                                    <option value="otro">Otro</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
