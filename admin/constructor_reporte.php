<?php
session_start();
require_once '../config/database.php';
require_once 'includes/report_functions.php';
require_once 'report_generator.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Verificar si se está editando un reporte
$editando = false;
$reporte_editar = null;
$id_reporte_editar = $_GET['editar'] ?? 0;

if ($id_reporte_editar) {
    $datos_reporte = cargarReporteGuardado($id_reporte_editar);
    
    if ($datos_reporte) {
        $editando = true;
        $reporte_editar = $datos_reporte['reporte'];
        $filtros_editar = $datos_reporte['filtros'];
        $columnas_editar = $datos_reporte['columnas'];
        $tipo_base = $reporte_editar['tipo_base'];
    } else {
        // Si no se encuentra el reporte, mostrar error
        echo "<div class='alert alert-danger'>Reporte no encontrado (ID: $id_reporte_editar)</div>";
        exit;
    }
}

// Si no se está editando, determinar el tipo de reporte
if (!$editando) {
    $tipo_reporte = $_GET['tipo'] ?? 'info_estudiantil';
    $tipo_base = $tipo_reporte;
} else {
    // Si se está editando, usar el tipo base del reporte guardado
    $tipo_reporte = $tipo_base;
}

// Inicializar variables por defecto (esto asegura que siempre existan)
$nombre_reporte = '';
$descripcion_reporte = '';
$filtros = [];
$columnas = [];

// Obtener datos para los selectores
$cursos = $conn->query("SELECT id_curso, nivel, curso, paralelo FROM cursos ORDER BY nivel, curso, paralelo")->fetchAll(PDO::FETCH_ASSOC);
$niveles_academicos = obtenerNivelesAcademicos();
$paralelos = obtenerParalelos($conn);

// Procesar formulario si se envía
$reporte_generado = false;
$mensaje_reporte = '';
$datos_guardados_temporalmente = false;

// Mantener mensaje de éxito si existe en sesión
if (isset($_SESSION['mensaje_reporte'])) {
    $mensaje_reporte = $_SESSION['mensaje_reporte'];
    unset($_SESSION['mensaje_reporte']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    
    // Columnas seleccionadas con orden
    $columnas = $_POST['columnas'] ?? [];
    $columnas_orden = $_POST['columnas_orden'] ?? [];

    // Ordenar columnas según el orden definido por el usuario
    if (!empty($columnas) && !empty($columnas_orden)) {
        // Crear un array asociativo con el orden de cada columna
        $columnas_con_orden = [];
        foreach ($columnas as $campo) {
            if (isset($columnas_orden[$campo])) {
                $columnas_con_orden[$campo] = (int)$columnas_orden[$campo];
            }
        }
        
        // Ordenar las columnas por su valor de orden
        asort($columnas_con_orden);
        $columnas = array_keys($columnas_con_orden);
    }

    error_log("Columnas recibidas con orden: " . print_r($columnas, true));
    
    if ($accion == 'guardar') {
        // Guardar configuración del reporte
        error_log("=== DEPURACIÓN GUARDAR REPORTE ===");
        error_log("Nombre: " . $nombre_reporte);
        error_log("Tipo Base: " . $tipo_base);
        error_log("Filtros: " . print_r($filtros, true));
        error_log("Columnas: " . print_r($columnas, true));
        
        // Verificar si se está editando
        $id_reporte_editar_post = $_POST['id_reporte_editar'] ?? 0;
        
        $resultado = guardarReporte($nombre_reporte, $tipo_base, $descripcion_reporte, $filtros, $columnas, $id_reporte_editar_post);
        
        error_log("Resultado: " . print_r($resultado, true));
        
        if ($resultado['success']) {
            $mensaje_reporte = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>¡Reporte ' . ($id_reporte_editar_post ? 'actualizado' : 'guardado') . ' exitosamente!</strong> ID del reporte: ' . $resultado['id_reporte'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            
            // Guardar mensaje en sesión para que persista después de redirección
            $_SESSION['mensaje_reporte'] = $mensaje_reporte;
            
            $reporte_generado = true;
            // Mantener datos temporales para mostrar resultados después de guardar
            $datos_guardados_temporalmente = true;
            
            // Guardar datos en sesión para mostrar resultados
            $_SESSION['reporte_temporal'] = [
                'filtros' => $filtros,
                'columnas' => $columnas,
                'tipo_base' => $tipo_base
            ];
        } else {
            $mensaje_reporte = '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error al guardar el reporte:</strong> ' . htmlspecialchars($resultado['error']) . '
            </div>';
            // Si hay error al guardar, mantener los datos para reintentar
            $reporte_generado = true;
            $datos_guardados_temporalmente = true;
            
            // Mantener datos en sesión para reintentar
            $_SESSION['reporte_temporal'] = [
                'filtros' => $filtros,
                'columnas' => $columnas,
                'tipo_base' => $tipo_base
            ];
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
} elseif ($editando) {
    // Si se está editando, cargar los datos del reporte
    // Los filtros y columnas ya vienen procesados desde cargarReporteGuardado()
                
    // Precargar columnas seleccionadas
    $columnas = $columnas_editar;
    
    // Precargar nombre y descripción del reporte
    $nombre_reporte = $reporte_editar['nombre'];
    $descripcion_reporte = $reporte_editar['descripcion'] ?? '';
    
    // CORRECCIÓN: Usar directamente los filtros procesados desde cargarReporteGuardado
    // Sin hacer conversiones adicionales
    $filtros = $filtros_editar;
} elseif (isset($_SESSION['reporte_temporal'])) {
    // Si hay datos temporales y no es POST, cargarlos para mantener el estado
    $datos_temp = $_SESSION['reporte_temporal'];
    $filtros = $datos_temp['filtros'] ?? [];
    $columnas = $datos_temp['columnas'] ?? [];
    $tipo_base = $datos_temp['tipo_base'] ?? '';
    $datos_guardados_temporalmente = true;
} else {
    // Las variables ya están inicializadas por defecto al principio
    // No se necesita hacer nada aquí
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
    <title>Constructor de Reportes - <?php echo $editando ? 'Editar Reporte' : ucfirst(str_replace('_', ' ', $tipo_reporte)); ?></title>
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
            margin: 2rem 0;
            flex-wrap: wrap;
            padding: 0 1rem;
        }

        .save-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #99b898;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(153, 184, 152, 0.1);
        }

        .save-section h5 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .save-section .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .save-buttons-container {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
            flex-wrap: wrap;
        }

        .save-buttons-container .btn-action {
            min-width: 160px;
            padding: 0.8rem 1.8rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .save-buttons-container .btn-save {
            background: linear-gradient(135deg, #99b898 0%, #7ca87c 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(153, 184, 152, 0.3);
        }

        .save-buttons-container .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(153, 184, 152, 0.4);
        }

        .save-buttons-container .btn-clear {
            background: #6c757d;
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2);
        }

        .save-buttons-container .btn-clear:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .save-buttons-container .btn-generate {
            background: #17a2b8;
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.2);
        }

        .save-buttons-container .btn-generate:hover {
            background: #138496;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .save-buttons-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .save-buttons-container .btn-action {
                width: 100%;
            }
        }

        .column-selection {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .selected-columns h6,
        .available-columns h6 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #495057;
        }

        .columns-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        .columns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem;
        }

        .column-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .column-item.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: 2px solid #5a67d8;
        }

        .column-item.available {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .column-item.selected:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .column-item.available:hover {
            background-color: #e9ecef;
        }

        .column-controls {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .column-name {
            font-weight: 500;
            flex: 1;
        }

        .column-order-controls {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .btn-order {
            background: none;
            border: none;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }

        .btn-order:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .btn-order.btn-remove {
            color: #ff6b6b;
        }

        .btn-order.btn-remove:hover {
            background-color: rgba(255, 107, 107, 0.2);
        }

        .btn-add {
            background: none;
            border: 1px solid #28a745;
            color: #28a745;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-align: left;
            font-size: 0.9rem;
        }

        .btn-add:hover {
            background-color: #28a745;
            color: white;
            transform: translateY(-1px);
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
            
            .columns-grid {
                grid-template-columns: 1fr;
            }
            
            .column-order-controls {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .btn-order {
                padding: 0.25rem;
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
                        <?php echo $editando ? 'Editar Reporte' : 'Constructor de Reportes - ' . ucfirst(str_replace('_', ' ', $tipo_reporte)); ?>
                    </h1>

                    <form id="formConstructor" method="POST" action="">
                        <input type="hidden" name="tipo_base" value="<?php echo htmlspecialchars($tipo_reporte); ?>">
                        <?php if ($editando): ?>
                        <input type="hidden" name="id_reporte_editar" value="<?php echo $id_reporte_editar; ?>">
                        
                        <!-- Información del Reporte (solo en modo edición) -->
                        <?php if ($editando): ?>
                        <div class="report-info" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                            <h5 style="color: #2c3e50; margin-bottom: 1rem; font-weight: 600;">
                                <i class="fas fa-info-circle me-2"></i>Información del Reporte
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p style="margin-bottom: 0.5rem;"><strong>Tipo de Reporte:</strong> <?php echo $tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'; ?></p>
                                    <p style="margin-bottom: 0.5rem;"><strong>Columnas Seleccionadas:</strong> <?php echo count($columnas); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p style="margin-bottom: 0.5rem;"><strong>Filtros Aplicados:</strong> <?php echo count($filtros); ?></p>
                                    <p style="margin-bottom: 0;"><strong>ID del Reporte:</strong> #<?php echo $id_reporte_editar; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Filtros Aplicados (solo en modo edición) -->
                        <?php if ($editando && !empty($filtros)): ?>
                        <div class="report-filters" style="background-color: rgba(0, 0, 0, 0.02); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #667eea;">
                            <h5 style="color: #667eea; margin-bottom: 1rem; font-weight: 600;">
                                <i class="fas fa-filter me-2"></i>Filtros Aplicados
                            </h5>
                            <div class="filters-list">
                                <?php foreach ($filtros as $campo => $valor): ?>
                                    <?php
                                    $campo_nombre = '';
                                    switch($campo) {
                                        case 'nivel': $campo_nombre = 'Nivel'; break;
                                        case 'curso': $campo_nombre = 'Curso'; break;
                                        case 'paralelo': $campo_nombre = 'Paralelo'; break;
                                        case 'genero': $campo_nombre = 'Género'; break;
                                        case 'edad_min': $campo_nombre = 'Edad Mínima'; break;
                                        case 'edad_max': $campo_nombre = 'Edad Máxima'; break;
                                        case 'pais': $campo_nombre = 'País'; break;
                                        case 'con_carnet': $campo_nombre = 'Con Carnet'; break;
                                        case 'con_rude': $campo_nombre = 'Con RUDE'; break;
                                        case 'carnet_identidad': $campo_nombre = 'Carnet de Identidad'; break;
                                        case 'certificado_nacimiento': $campo_nombre = 'Certificado de Nacimiento'; break;
                                        default: $campo_nombre = ucfirst($campo); break;
                                    }
                                    
                                    if (is_array($valor)) {
                                        $valor_mostrar = implode(', ', $valor);
                                    } else {
                                        switch($valor) {
                                            case '1': $valor_mostrar = 'Sí'; break;
                                            case '0': $valor_mostrar = 'No'; break;
                                            case 'con': $valor_mostrar = 'Con'; break;
                                            case 'sin': $valor_mostrar = 'Sin'; break;
                                            default: $valor_mostrar = $valor; break;
                                        }
                                    }
                                    ?>
                                    <span class="filter-tag" style="display: inline-block; padding: 0.4rem 0.8rem; background-color: #e9ecef; border-radius: 15px; margin: 0.2rem; font-size: 0.85rem; color: #495057;">
                                        <strong><?php echo $campo_nombre; ?>:</strong> <?php echo htmlspecialchars($valor_mostrar); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Información del Reporte -->
                        <?php if (!($reporte_generado && $datos_guardados_temporalmente)): ?>
                        <div class="filter-section">
                            <h5><i class="fas fa-info-circle"></i> Información del Reporte</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre del Reporte <?php echo $editando ? '*' : '(Opcional)' ?></label>
                                    <input type="text" class="form-control" name="nombre_reporte" 
                                           value="<?php echo htmlspecialchars($nombre_reporte); ?>"
                                           placeholder="<?php echo $editando ? 'Ingresa el nombre del reporte' : 'Opcional: Solo si deseas guardar el reporte'; ?>"
                                           <?php echo $editando ? 'required' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Descripción</label>
                                    <input type="text" class="form-control" name="descripcion_reporte" 
                                           value="<?php echo htmlspecialchars($descripcion_reporte); ?>"
                                           placeholder="Descripción opcional del reporte">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

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
                                <div class="selected-columns" id="selectedColumns">
                                    <h6><i class="fas fa-list"></i> Columnas Seleccionadas (Arrastra o usa flechas para ordenar)</h6>
                                    <div class="columns-list" id="columnsList">
                                        <?php
                                        // Obtener columnas seleccionadas con su orden
                                        $columnas_con_orden = [];
                                        if (isset($columnas) && !empty($columnas)) {
                                            foreach ($columnas as $index => $campo) {
                                                $columnas_con_orden[$campo] = $index + 1;
                                            }
                                        }
                                        
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

                                        // Mostrar primero las columnas seleccionadas en orden
                                        if (!empty($columnas_con_orden)) {
                                            foreach ($columnas_con_orden as $campo => $orden) {
                                                if (isset($columnas_disponibles[$campo])) {
                                                    $alias = $columnas_disponibles[$campo];
                                                    echo '<div class="column-item selected" data-column="' . $campo . '">';
                                                    echo '<div class="column-controls">';
                                                    echo '<input type="hidden" name="columnas[]" value="' . $campo . '">';
                                                    echo '<input type="hidden" name="columnas_orden[' . $campo . ']" value="' . $orden . '" class="orden-input">';
                                                    echo '<span class="column-name">' . $alias . '</span>';
                                                    echo '</div>';
                                                    echo '<div class="column-order-controls">';
                                                    echo '<button type="button" class="btn-order btn-up" onclick="moveColumn(\'' . $campo . '\', \'up\')"><i class="fas fa-arrow-up"></i></button>';
                                                    echo '<button type="button" class="btn-order btn-down" onclick="moveColumn(\'' . $campo . '\', \'down\')"><i class="fas fa-arrow-down"></i></button>';
                                                    echo '<button type="button" class="btn-order btn-remove" onclick="removeColumn(\'' . $campo . '\')"><i class="fas fa-times"></i></button>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="available-columns">
                                    <h6><i class="fas fa-plus-circle"></i> Columnas Disponibles</h6>
                                    <div class="columns-grid">
                                        <?php
                                        foreach ($columnas_disponibles as $campo => $alias):
                                            if (!isset($columnas_con_orden[$campo])):
                                        ?>
                                            <div class="column-item available" data-column="<?php echo $campo; ?>">
                                                <button type="button" class="btn-add" onclick="addColumn('<?php echo $campo; ?>', '<?php echo addslashes($alias); ?>')">
                                                    <i class="fas fa-plus"></i> <?php echo $alias; ?>
                                                </button>
                                            </div>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <?php if ($reporte_generado && $datos_guardados_temporalmente): ?>
                            <!-- Sección para guardar reporte generado -->
                            <div class="save-section">
                                <h5><i class="fas fa-save"></i> ¿Desea guardar este reporte?</h5>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre del Reporte *</label>
                                        <input type="text" class="form-control" name="nombre_reporte" required 
                                               placeholder="Ingrese un nombre para el reporte" 
                                               value="<?php echo isset($nombre_reporte) ? htmlspecialchars($nombre_reporte) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" class="form-control" name="descripcion_reporte" 
                                               placeholder="Descripción opcional" 
                                               value="<?php echo isset($descripcion_reporte) ? htmlspecialchars($descripcion_reporte) : ''; ?>">
                                    </div>
                                </div>
                                <div class="save-buttons-container">
                                    <button type="submit" name="accion" value="guardar" class="btn-action btn-save">
                                        <i class="fas fa-save me-2"></i> Guardar Reporte
                                    </button>
                                    <button type="button" class="btn-action btn-clear" onclick="limpiarFormulario()">
                                        <i class="fas fa-eraser me-2"></i> Limpiar Filtros
                                    </button>
                                    <button type="button" class="btn-action btn-generate" onclick="generarNuevo()">
                                        <i class="fas fa-redo me-2"></i> Generar Nuevo
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Botones iniciales -->
                            <div class="action-buttons">
                                <button type="submit" name="accion" value="generar" class="btn-action btn-generate">
                                    <i class="fas fa-play"></i> Generar Reporte
                                </button>
                                <button type="button" class="btn-action btn-clear" onclick="limpiarFormulario()">
                                    <i class="fas fa-eraser"></i> Limpiar Filtros
                                </button>
                            </div>
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
            var accion = document.querySelector('input[name="accion"]:checked')?.value || 
                         document.querySelector('button[name="accion"]')?.value;
            
            if (accion === 'guardar') {
                // Buscar el campo nombre_reporte que esté visible
                const nombreReporteInput = document.querySelector('input[name="nombre_reporte"]:not([style*="display: none"])') 
                    || document.querySelector('.save-section input[name="nombre_reporte"]') 
                    || document.querySelector('input[name="nombre_reporte"]');
                const nombreReporte = nombreReporteInput ? nombreReporteInput.value : '';
                
                if (!nombreReporte.trim()) {
                    e.preventDefault();
                    alert('Por favor, ingresa un nombre para el reporte.');
                    nombreReporteInput.focus();
                    return;
                }
            }
            
            // Validar que se hayan seleccionado columnas
            const selectedColumns = document.querySelectorAll('#columnsList .column-item.selected');
            if (selectedColumns.length === 0) {
                e.preventDefault();
                alert('Por favor, selecciona al menos una columna para mostrar.');
                return;
            }
        });

        // Funciones para manejo de columnas
        function addColumn(campo, alias) {
            const columnsList = document.getElementById('columnsList');
            const availableItem = document.querySelector(`.column-item.available[data-column="${campo}"]`);
            
            if (availableItem) {
                // Crear nuevo elemento de columna seleccionada
                const columnItem = document.createElement('div');
                columnItem.className = 'column-item selected';
                columnItem.dataset.column = campo;
                
                // Obtener el siguiente orden
                const nextOrder = columnsList.children.length + 1;
                
                columnItem.innerHTML = `
                    <div class="column-controls">
                        <input type="hidden" name="columnas[]" value="${campo}">
                        <input type="hidden" name="columnas_orden[${campo}]" value="${nextOrder}" class="orden-input">
                        <span class="column-name">${alias}</span>
                    </div>
                    <div class="column-order-controls">
                        <button type="button" class="btn-order btn-up" onclick="moveColumn('${campo}', 'up')"><i class="fas fa-arrow-up"></i></button>
                        <button type="button" class="btn-order btn-down" onclick="moveColumn('${campo}', 'down')"><i class="fas fa-arrow-down"></i></button>
                        <button type="button" class="btn-order btn-remove" onclick="removeColumn('${campo}')"><i class="fas fa-times"></i></button>
                    </div>
                `;
                
                columnsList.appendChild(columnItem);
                availableItem.remove();
                
                // Actualizar órdenes
                updateColumnOrders();
            }
        }

        function removeColumn(campo) {
            const columnItem = document.querySelector(`#columnsList .column-item.selected[data-column="${campo}"]`);
            const availableColumns = document.querySelector('.columns-grid');
            
            if (columnItem) {
                // Obtener el alias de la columna
                const alias = columnItem.querySelector('.column-name').textContent;
                
                // Crear elemento disponible
                const availableItem = document.createElement('div');
                availableItem.className = 'column-item available';
                availableItem.dataset.column = campo;
                availableItem.innerHTML = `
                    <button type="button" class="btn-add" onclick="addColumn('${campo}', '${alias.replace(/'/g, "\\'")}')">
                        <i class="fas fa-plus"></i> ${alias}
                    </button>
                `;
                
                availableColumns.appendChild(availableItem);
                columnItem.remove();
                
                // Actualizar órdenes
                updateColumnOrders();
            }
        }

        function moveColumn(campo, direction) {
            const columnsList = document.getElementById('columnsList');
            const currentColumn = document.querySelector(`#columnsList .column-item.selected[data-column="${campo}"]`);
            
            if (!currentColumn) return;
            
            if (direction === 'up') {
                const previousColumn = currentColumn.previousElementSibling;
                if (previousColumn) {
                    columnsList.insertBefore(currentColumn, previousColumn);
                }
            } else if (direction === 'down') {
                const nextColumn = currentColumn.nextElementSibling;
                if (nextColumn) {
                    columnsList.insertBefore(nextColumn, currentColumn);
                }
            }
            
            // Actualizar órdenes
            updateColumnOrders();
        }

        function updateColumnOrders() {
            const selectedColumns = document.querySelectorAll('#columnsList .column-item.selected');
            
            selectedColumns.forEach((column, index) => {
                const ordenInput = column.querySelector('.orden-input');
                if (ordenInput) {
                    ordenInput.value = index + 1;
                }
            });
        }
    </script>
</body>
</html>
