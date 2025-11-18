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
$datos_guardados_temporalmente = false;

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
    $filtros = $_POST['filtros'] ?? [];
    
    // Procesar filtros especiales
    if (isset($filtros['carnet_identidad'])) {
        if ($filtros['carnet_identidad'] == 'con') {
            $filtros['con_carnet'] = '1';
        } elseif ($filtros['carnet_identidad'] == 'sin') {
            $filtros['con_carnet'] = '0';
        }
        unset($filtros['carnet_identidad']);
    }
    
    if (isset($filtros['certificado_nacimiento'])) {
        if ($filtros['certificado_nacimiento'] == 'con') {
            $filtros['con_rude'] = '1';
        } elseif ($filtros['certificado_nacimiento'] == 'sin') {
            $filtros['con_rude'] = '0';
        }
        unset($filtros['certificado_nacimiento']);
    }
    
    error_log("Filtros procesados: " . print_r($filtros, true));
    
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
            // Limpiar datos temporales después de guardar
            unset($_SESSION['reporte_temporal']);
        } else {
            $mensaje_reporte = '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error al guardar el reporte:</strong> ' . htmlspecialchars($resultado['error']) . '
            </div>';
        }
    } elseif ($accion == 'generar') {
        $reporte_generado = true;
        // Guardar datos temporalmente en sesión para posible guardado posterior
        $_SESSION['reporte_temporal'] = [
            'filtros' => $filtros,
            'columnas' => $columnas,
            'tipo_base' => $tipo_base
        ];
        $datos_guardados_temporalmente = true;
    }
} elseif (isset($_SESSION['reporte_temporal'])) {
    // Si hay datos temporales y no es POST, cargarlos para mantener el estado
    $datos_temp = $_SESSION['reporte_temporal'];
    $filtros = $datos_temp['filtros'] ?? [];
    $columnas = $datos_temp['columnas'] ?? [];
    $tipo_base = $datos_temp['tipo_base'] ?? '';
    $datos_guardados_temporalmente = true;
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
            background-color: #f8f9fa;
            color: #333333;
        }

        .content-wrapper {
            background: #ffffff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            margin-top: 25px;
        }

        .page-title {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }

        .filter-section {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .filter-section h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .form-label {
            color: #333333;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #333333;
        }

        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: #4CAF50;
            color: #333333;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .form-control::placeholder {
            color: #6c757d;
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
            background: #4CAF50;
            color: #ffffff;
            border: none;
        }

        .btn-generate:hover {
            background: #45a049;
            color: #ffffff;
            transform: scale(1.05);
        }

        .btn-save {
            background: #2196F3;
            color: #ffffff;
            border: none;
        }

        .btn-save:hover {
            background: #1976D2;
            color: #ffffff;
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

        .results-table {
            margin-top: 2rem;
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .table {
            color: #333333;
        }

        .table th {
            background: #e9ecef;
            color: #2c3e50;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            border: none;
            border-bottom: 1px solid #dee2e6;
        }

        .table tr:hover {
            background: #f8f9fa;
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
                                            <option value="Inicial" <?php echo (isset($filtros['nivel']) && in_array('Inicial', $filtros['nivel'])) ? 'selected' : ''; ?>>Inicial</option>
                                            <option value="Primaria" <?php echo (isset($filtros['nivel']) && in_array('Primaria', $filtros['nivel'])) ? 'selected' : ''; ?>>Primaria</option>
                                            <option value="Secundaria" <?php echo (isset($filtros['nivel']) && in_array('Secundaria', $filtros['nivel'])) ? 'selected' : ''; ?>>Secundaria</option>
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
                                                    $selected = (isset($filtros['curso']) && in_array($curso['curso'], $filtros['curso'])) ? 'selected' : '';
                                                    echo "<option value='{$curso['curso']}' $selected>{$curso['curso']}</option>";
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
                                                    $selected = (isset($filtros['paralelo']) && in_array($curso['paralelo'], $filtros['paralelo'])) ? 'selected' : '';
                                                    echo "<option value='{$curso['paralelo']}' $selected>{$curso['paralelo']}</option>";
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
                                            <option value="" <?php echo (isset($filtros['genero']) && $filtros['genero'] == '') ? 'selected' : ''; ?>>Todos</option>
                                            <option value="Masculino" <?php echo (isset($filtros['genero']) && $filtros['genero'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="Femenino" <?php echo (isset($filtros['genero']) && $filtros['genero'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Edad Mínima</label>
                                        <input type="number" class="form-control" name="filtros[edad_min]" 
                                               min="0" max="99" placeholder="0" value="<?php echo isset($filtros['edad_min']) ? htmlspecialchars($filtros['edad_min']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Edad Máxima</label>
                                        <input type="number" class="form-control" name="filtros[edad_max]" 
                                               min="0" max="99" placeholder="99" value="<?php echo isset($filtros['edad_max']) ? htmlspecialchars($filtros['edad_max']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">País</label>
                                        <select class="form-select" name="filtros[pais]">
                                            <option value="" <?php echo (isset($filtros['pais']) && $filtros['pais'] == '') ? 'selected' : ''; ?>>Todos</option>
                                            <option value="Bolivia" <?php echo (isset($filtros['pais']) && $filtros['pais'] == 'Bolivia') ? 'selected' : ''; ?>>Bolivia</option>
                                            <option value="Chile" <?php echo (isset($filtros['pais']) && $filtros['pais'] == 'Chile') ? 'selected' : ''; ?>>Chile</option>
                                            <option value="Argentina" <?php echo (isset($filtros['pais']) && $filtros['pais'] == 'Argentina') ? 'selected' : ''; ?>>Argentina</option>
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
                                            <option value="" <?php echo (isset($filtros['carnet_identidad']) && $filtros['carnet_identidad'] == '') ? 'selected' : ''; ?>>Todos</option>
                                            <option value="con" <?php echo (isset($filtros['carnet_identidad']) && $filtros['carnet_identidad'] == 'con') ? 'selected' : ''; ?>>Con Carnet</option>
                                            <option value="sin" <?php echo (isset($filtros['carnet_identidad']) && $filtros['carnet_identidad'] == 'sin') ? 'selected' : ''; ?>>Sin Carnet</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Certificado de Nacimiento</label>
                                        <select class="form-select" name="filtros[certificado_nacimiento]">
                                            <option value="" <?php echo (isset($filtros['certificado_nacimiento']) && $filtros['certificado_nacimiento'] == '') ? 'selected' : ''; ?>>Todos</option>
                                            <option value="con" <?php echo (isset($filtros['certificado_nacimiento']) && $filtros['certificado_nacimiento'] == 'con') ? 'selected' : ''; ?>>Con Certificado</option>
                                            <option value="sin" <?php echo (isset($filtros['certificado_nacimiento']) && $filtros['certificado_nacimiento'] == 'sin') ? 'selected' : ''; ?>>Sin Certificado</option>
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
                                            <option value="Inicial" <?php echo (isset($filtros['nivel']) && in_array('Inicial', $filtros['nivel'])) ? 'selected' : ''; ?>>Inicial</option>
                                            <option value="Primaria" <?php echo (isset($filtros['nivel']) && in_array('Primaria', $filtros['nivel'])) ? 'selected' : ''; ?>>Primaria</option>
                                            <option value="Secundaria" <?php echo (isset($filtros['nivel']) && in_array('Secundaria', $filtros['nivel'])) ? 'selected' : ''; ?>>Secundaria</option>
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
                                            <option value="" <?php echo (isset($filtros['genero']) && $filtros['genero'] == '') ? 'selected' : ''; ?>>Todos</option>
                                            <option value="Masculino" <?php echo (isset($filtros['genero']) && $filtros['genero'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="Femenino" <?php echo (isset($filtros['genero']) && $filtros['genero'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
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
                                               <?php echo (isset($columnas) && in_array($campo, $columnas)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="col_<?php echo $campo; ?>">
                                            <?php echo $alias; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="action-buttons">
                            <?php if ($reporte_generado && $datos_guardados_temporalmente): ?>
                                <!-- Mostrar botón de guardar después de generar -->
                                <button type="submit" name="accion" value="guardar" class="btn-action btn-save">
                                    <i class="fas fa-save"></i> Guardar Reporte Generado
                                </button>
                                <button type="button" class="btn-action btn-clear" onclick="limpiarFormulario()">
                                    <i class="fas fa-eraser"></i> Limpiar Filtros
                                </button>
                                <button type="button" class="btn-action btn-generate" onclick="generarNuevo()">
                                    <i class="fas fa-redo"></i> Generar Nuevo
                                </button>
                            <?php else: ?>
                                <!-- Botones iniciales -->
                                <button type="submit" name="accion" value="generar" class="btn-action btn-generate">
                                    <i class="fas fa-play"></i> Generar Reporte
                                </button>
                                <button type="button" class="btn-action btn-clear" onclick="limpiarFormulario()">
                                    <i class="fas fa-eraser"></i> Limpiar Filtros
                                </button>
                            <?php endif; ?>
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
        // Función para limpiar formulario
        function limpiarFormulario() {
            if (confirm('¿Estás seguro de que deseas limpiar todos los filtros?')) {
                document.getElementById('formConstructor').reset();
                // Limpiar datos temporales de sesión
                window.location.href = 'constructor_reporte.php?tipo=<?php echo $tipo_reporte; ?>';
            }
        }

        // Función para generar nuevo reporte
        function generarNuevo() {
            if (confirm('¿Generar un nuevo reporte? Se perderán los filtros actuales.')) {
                // Limpiar datos temporales de sesión y recargar
                window.location.href = 'constructor_reporte.php?tipo=<?php echo $tipo_reporte; ?>';
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
