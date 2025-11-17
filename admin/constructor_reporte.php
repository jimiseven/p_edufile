<?php
session_start();
require_once '../config/database.php';
require_once 'includes/report_functions.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Obtener el tipo de reporte desde el parámetro GET
$tipo_reporte = $_GET['tipo'] ?? 'info_estudiantil';

// Obtener datos para los selectores
$cursos = $conn->query("SELECT id_curso, nivel, curso, paralelo FROM cursos ORDER BY nivel, curso, paralelo")->fetchAll(PDO::FETCH_ASSOC);
$niveles_academicos = obtenerNivelesAcademicos();
$paralelos = obtenerParalelos($conn);

// Procesar formulario si se envía
$reporte_generado = false;
$mensaje_reporte = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'report_generator.php';
    
    // Logging completo de POST
    error_log("=== POST RECIBIDO ===");
    error_log("Datos POST: " . print_r($_POST, true));
    
    $accion = $_POST['accion'] ?? '';
    $tipo_base = $_POST['tipo_base'] ?? '';
    $nombre_reporte = $_POST['nombre_reporte'] ?? '';
    $descripcion_reporte = $_POST['descripcion_reporte'] ?? '';
    
    error_log("Acción: $accion");
    error_log("Nombre Reporte: $nombre_reporte");
    error_log("Tipo Base: $tipo_base");
    
    // Procesar filtros del formulario
    $filtros = [];
    
    // Filtros académicos
    if (!empty($_POST['nivel'])) {
        $filtros['nivel'] = is_array($_POST['nivel']) ? $_POST['nivel'] : [$_POST['nivel']];
    }
    if (!empty($_POST['curso'])) {
        $filtros['curso'] = is_array($_POST['curso']) ? $_POST['curso'] : [$_POST['curso']];
    }
    if (!empty($_POST['paralelo'])) {
        $filtros['paralelo'] = is_array($_POST['paralelo']) ? $_POST['paralelo'] : [$_POST['paralelo']];
    }
    
    // Filtros demográficos
    if (!empty($_POST['genero'])) {
        $filtros['genero'] = $_POST['genero'];
    }
    if (!empty($_POST['edad_min'])) {
        $filtros['edad_min'] = $_POST['edad_min'];
    }
    if (!empty($_POST['edad_max'])) {
        $filtros['edad_max'] = $_POST['edad_max'];
    }
    if (!empty($_POST['pais'])) {
        $filtros['pais'] = $_POST['pais'];
    }
    
    // Filtros de documentación
    if (!empty($_POST['con_carnet'])) {
        $filtros['con_carnet'] = $_POST['con_carnet'];
    }
    if (!empty($_POST['con_rude'])) {
        $filtros['con_rude'] = $_POST['con_rude'];
    }
    
    // Columnas seleccionadas
    $columnas = $_POST['columnas'] ?? [];
    error_log("Columnas recibidas: " . print_r($columnas, true));
    
    if ($accion == 'guardar') {
        // Guardar configuración del reporte
        error_log("=== DEPURACIÓN GUARDAR REPORTE ===");
        error_log("Nombre: " . $nombre_reporte);
        error_log("Tipo Base: " . $tipo_base);
        error_log("Filtros: " . print_r($filtros, true));
        error_log("Columnas: " . print_r($columnas, true));
        
        $resultado = guardarReporte($nombre_reporte, $tipo_base, $descripcion_reporte, $filtros, $columnas);
        
        error_log("Resultado: " . print_r($resultado, true));
        
        if ($resultado['success']) {
            $mensaje_reporte = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>¡Reporte guardado exitosamente!</strong> ID del reporte: ' . $resultado['id_reporte'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            $reporte_generado = true;
        } else {
            $mensaje_reporte = '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error al guardar el reporte:</strong> ' . htmlspecialchars($resultado['error']) . '
            </div>';
        }
    } elseif ($accion == 'generar') {
        $reporte_generado = true;
    }
}

// Función para generar opciones de select
function generarOpcionesSelect($array, $valor_key, $texto_key, $seleccionados = []) {
    $options = '';
    foreach ($array as $item) {
        $selected = in_array($item[$valor_key], $seleccionados) ? 'selected' : '';
        $options .= "<option value='{$item[$valor_key]}' $selected>{$item[$texto_key]}</option>";
    }
    return $options;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constructor de Reportes - <?php echo ucfirst(str_replace('_', ' ', $tipo_reporte)); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link id="bootstrap-css" rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #eaeaea;
        }

        .content-wrapper {
            background: var(--content-bg, #1f1f1f);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            margin-top: 25px;
        }

        .page-title {
            color: #99b898;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }

        .filter-section {
            background: var(--card-bg, #2a2a2a);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--card-border, #333);
        }

        .filter-section h5 {
            color: #99b898;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label {
            color: #b0b0b0;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            background: var(--input-bg, #333);
            border: 1px solid var(--input-border, #444);
            color: #eaeaea;
            border-radius: 5px;
        }

        .form-control:focus, .form-select:focus {
            background: var(--input-bg, #333);
            border-color: #99b898;
            color: #eaeaea;
            box-shadow: 0 0 0 0.2rem rgba(153, 184, 152, 0.25);
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-generate {
            background: #99b898;
            color: #222;
            border: none;
        }

        .btn-generate:hover {
            background: #7a9680;
            color: #fff;
            transform: scale(1.05);
        }

        .btn-save {
            background: #17a2b8;
            color: #fff;
            border: none;
        }

        .btn-save:hover {
            background: #138496;
            color: #fff;
            transform: scale(1.05);
        }

        .btn-clear {
            background: #6c757d;
            color: #fff;
            border: none;
        }

        .btn-clear:hover {
            background: #5a6268;
            color: #fff;
            transform: scale(1.05);
        }

        .btn-back {
            background: #dc3545;
            color: #fff;
            border: none;
        }

        .btn-back:hover {
            background: #c82333;
            color: #fff;
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .column-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .form-check {
            margin-bottom: 0.5rem;
        }

        .form-check-input:checked {
            background-color: #99b898;
            border-color: #99b898;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 7px;
            position: absolute;
            right: 32px;
            top: 32px;
        }

        .toggle-switch label {
            font-size: .95rem;
            font-weight: 600;
            color: #99b898;
            cursor: pointer;
        }

        .toggle-switch input[type="checkbox"] {
            width: 28px;
            height: 16px;
            position: relative;
            appearance: none;
            background: #aaa;
            outline: none;
            border-radius: 20px;
            transition: background 0.2s;
        }

        .toggle-switch input[type="checkbox"]:checked {
            background: #99b898;
        }

        .toggle-switch input[type="checkbox"]::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            transition: left 0.2s;
        }

        .toggle-switch input[type="checkbox"]:checked::after {
            left: 14px;
        }

        body:not(.dark-mode) {
            --content-bg: #f8f9fa;
            --card-bg: #fff;
            --card-border: #dee2e6;
            --input-bg: #fff;
            --input-border: #ced4da;
        }

        body.dark-mode {
            --content-bg: #1f1f1f;
            --card-bg: #2a2a2a;
            --card-border: #333;
            --input-bg: #333;
            --input-border: #444;
        }

        .results-table {
            margin-top: 2rem;
            background: var(--card-bg, #2a2a2a);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--card-border, #333);
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .table {
            color: #eaeaea;
        }

        .table th {
            background: var(--th-bg, #232323);
            color: #99b898;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            border: none;
            border-bottom: 1px solid var(--table-border, #333);
        }

        .table tr:hover {
            background: var(--tr-hover, #282828);
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .column-selection {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row position-relative">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 position-relative">
                <!-- Toggle Modo Claro/Oscuro -->
                <div class="toggle-switch">
                    <label for="toggleMode">☀️/🌙</label>
                    <input type="checkbox" id="toggleMode" <?php if (isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] == 'on') echo "checked"; ?>>
                </div>
                
                <div class="content-wrapper">
                    <h1 class="page-title">
                        <i class="fas fa-cogs me-2"></i>
                        Constructor de Reportes - <?php echo ucfirst(str_replace('_', ' ', $tipo_reporte)); ?>
                    </h1>

                    <form id="formConstructor" method="POST" action="">
                        <input type="hidden" name="tipo_base" value="<?php echo htmlspecialchars($tipo_reporte); ?>">
                        
                        <!-- Información del Reporte -->
                        <div class="filter-section">
                            <h5><i class="fas fa-info-circle"></i> Información del Reporte</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre del Reporte *</label>
                                    <input type="text" class="form-control" name="nombre_reporte" required 
                                           placeholder="Ej: Lista de Estudiantes por Curso">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Descripción</label>
                                    <input type="text" class="form-control" name="descripcion_reporte" 
                                           placeholder="Descripción opcional del reporte">
                                </div>
                            </div>
                        </div>

                        <?php if ($tipo_reporte == 'info_estudiantil'): ?>
                            <!-- Filtros Académicos -->
                            <div class="filter-section">
                                <h5><i class="fas fa-graduation-cap"></i> Filtros Académicos</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Nivel Educativo</label>
                                        <select class="form-select" name="filtros[nivel][]" multiple>
                                            <option value="Inicial">Inicial</option>
                                            <option value="Primaria">Primaria</option>
                                            <option value="Secundaria">Secundaria</option>
                                        </select>
                                        <small class="text-muted">Mantener presionado Ctrl para seleccionar múltiples</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Curso</label>
                                        <select class="form-select" name="filtros[curso][]" multiple>
                                            <?php
                                            $cursos_unicos = [];
                                            foreach ($cursos as $curso) {
                                                if (!in_array($curso['curso'], $cursos_unicos)) {
                                                    $cursos_unicos[] = $curso['curso'];
                                                    echo "<option value='{$curso['curso']}'>{$curso['curso']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <small class="text-muted">Mantener presionado Ctrl para seleccionar múltiples</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Paralelo</label>
                                        <select class="form-select" name="filtros[paralelo][]" multiple>
                                            <?php
                                            $paralelos_unicos = [];
                                            foreach ($cursos as $curso) {
                                                if (!in_array($curso['paralelo'], $paralelos_unicos)) {
                                                    $paralelos_unicos[] = $curso['paralelo'];
                                                    echo "<option value='{$curso['paralelo']}'>{$curso['paralelo']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <small class="text-muted">Mantener presionado Ctrl para seleccionar múltiples</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros Demográficos -->
                            <div class="filter-section">
                                <h5><i class="fas fa-users"></i> Filtros Demográficos</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Género</label>
                                        <select class="form-select" name="filtros[genero]">
                                            <option value="">Todos</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Femenino">Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Edad Mínima</label>
                                        <input type="number" class="form-control" name="filtros[edad_min]" 
                                               min="0" max="99" placeholder="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Edad Máxima</label>
                                        <input type="number" class="form-control" name="filtros[edad_max]" 
                                               min="0" max="99" placeholder="99">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">País</label>
                                        <select class="form-select" name="filtros[pais]">
                                            <option value="">Todos</option>
                                            <option value="Bolivia">Bolivia</option>
                                            <option value="Chile">Chile</option>
                                            <option value="Argentina">Argentina</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros de Documentación -->
                            <div class="filter-section">
                                <h5><i class="fas fa-file-alt"></i> Filtros de Documentación</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Carnet de Identidad</label>
                                        <select class="form-select" name="filtros[carnet_identidad]">
                                            <option value="">Todos</option>
                                            <option value="con">Con Carnet</option>
                                            <option value="sin">Sin Carnet</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Certificado de Nacimiento</label>
                                        <select class="form-select" name="filtros[certificado_nacimiento]">
                                            <option value="">Todos</option>
                                            <option value="con">Con Certificado</option>
                                            <option value="sin">Sin Certificado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($tipo_reporte == 'info_academica'): ?>
                            <!-- Filtros Académicos para Info Académica -->
                            <div class="filter-section">
                                <h5><i class="fas fa-graduation-cap"></i> Filtros Académicos</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Nivel Educativo</label>
                                        <select class="form-select" name="filtros[nivel][]" multiple>
                                            <option value="Inicial">Inicial</option>
                                            <option value="Primaria">Primaria</option>
                                            <option value="Secundaria">Secundaria</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Trimestre</label>
                                        <select class="form-select" name="filtros[trimestre]">
                                            <option value="">Todos</option>
                                            <option value="1">Primer Trimestre</option>
                                            <option value="2">Segundo Trimestre</option>
                                            <option value="3">Tercer Trimestre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Estado Académico</label>
                                        <select class="form-select" name="filtros[estado]">
                                            <option value="">Todos</option>
                                            <option value="aprobado">Aprobado</option>
                                            <option value="reprobado">Reprobado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros de Excelencia -->
                            <div class="filter-section">
                                <h5><i class="fas fa-trophy"></i> Filtros de Excelencia</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Género</label>
                                        <select class="form-select" name="filtros[genero]">
                                            <option value="">Todos</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Femenino">Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Top N Promedios</label>
                                        <input type="number" class="form-control" name="filtros[top_promedios]" 
                                               min="1" max="50" placeholder="Ej: 10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Promedio Mínimo</label>
                                        <input type="number" class="form-control" name="filtros[promedio_min]" 
                                               min="0" max="100" step="0.1" placeholder="Ej: 85">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Selección de Columnas -->
                        <div class="filter-section">
                            <h5><i class="fas fa-columns"></i> Columnas a Mostrar</h5>
                            <div class="column-selection">
                                <?php
                                $columnas_disponibles = [
                                    'id_estudiante' => 'ID Estudiante',
                                    'nombres' => 'Nombres',
                                    'apellido_paterno' => 'Apellido Paterno',
                                    'apellido_materno' => 'Apellido Materno',
                                    'genero' => 'Género',
                                    'fecha_nacimiento' => 'Fecha de Nacimiento',
                                    'edad' => 'Edad',
                                    'carnet_identidad' => 'Carnet de Identidad',
                                    'rude' => 'RUDE',
                                    'pais' => 'País',
                                    'provincia_departamento' => 'Provincia/Departamento',
                                    'nivel' => 'Nivel',
                                    'curso' => 'Curso',
                                    'paralelo' => 'Paralelo',
                                    'nombre_completo' => 'Nombre Completo'
                                ];

                                foreach ($columnas_disponibles as $campo => $alias):
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columnas[]" 
                                               value="<?php echo $campo; ?>" id="col_<?php echo $campo; ?>"
                                               <?php echo in_array($campo, ['nombres', 'apellido_paterno']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="col_<?php echo $campo; ?>">
                                            <?php echo $alias; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="action-buttons">
                            <button type="submit" name="accion" value="generar" class="btn-action btn-generate">
                                <i class="fas fa-play"></i> Generar Reporte
                            </button>
                            <button type="submit" name="accion" value="guardar" class="btn-action btn-save">
                                <i class="fas fa-save"></i> Guardar Reporte
                            </button>
                            <button type="button" class="btn-action btn-clear" onclick="limpiarFormulario()">
                                <i class="fas fa-eraser"></i> Limpiar Filtros
                            </button>
                            <a href="reportes.php" class="btn-action btn-back">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </form>

                    <!-- Resultados del Reporte -->
                    <?php if ($reporte_generado): ?>
                        <div class="results-table">
                            <h5><i class="fas fa-table"></i> Resultados del Reporte</h5>
                            <?php 
                            if (!empty($mensaje_reporte)) {
                                echo $mensaje_reporte;
                            }
                            generarReporteHTML($filtros, $columnas, $tipo_base); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Modo claro/oscuro con persistencia en cookie
        const toggle = document.getElementById('toggleMode');

        function setMode(dark) {
            if (dark) {
                document.body.classList.add('dark-mode');
                document.cookie = "darkmode=on;path=/;max-age=31536000";
            } else {
                document.body.classList.remove('dark-mode');
                document.cookie = "darkmode=off;path=/;max-age=31536000";
            }
        }
        
        toggle.addEventListener('change', function() {
            setMode(this.checked);
        });
        
        // Estado inicial al cargar
        window.onload = function() {
            if (document.cookie.indexOf('darkmode=on') !== -1) {
                document.body.classList.add('dark-mode');
                toggle.checked = true;
            }
        }

        // Función para limpiar formulario
        function limpiarFormulario() {
            if (confirm('¿Estás seguro de que deseas limpiar todos los filtros?')) {
                document.getElementById('formConstructor').reset();
            }
        }

        // Validación básica del formulario
        document.getElementById('formConstructor').addEventListener('submit', function(e) {
            const accion = document.querySelector('button[name="accion"]:focus')?.value;
            
            if (accion === 'guardar') {
                const nombreReporte = document.querySelector('input[name="nombre_reporte"]').value;
                if (!nombreReporte.trim()) {
                    e.preventDefault();
                    alert('Por favor, ingresa un nombre para el reporte.');
                    document.querySelector('input[name="nombre_reporte"]').focus();
                    return;
                }

                const columnasSeleccionadas = document.querySelectorAll('input[name="columnas[]"]:checked');
                if (columnasSeleccionadas.length === 0) {
                    e.preventDefault();
                    alert('Por favor, selecciona al menos una columna para mostrar.');
                    return;
                }
            }
        });
    </script>
</body>
</html>
