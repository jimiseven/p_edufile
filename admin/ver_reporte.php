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

$id_reporte = $_GET['id'] ?? 0;

if (!$id_reporte) {
    header('Location: reportes.php');
    exit();
}

// Cargar reporte guardado
$datos_reporte = cargarReporteGuardado($id_reporte);

if (!$datos_reporte) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo 'Reporte no encontrado.';
    echo '</div>';
    exit();
}

$reporte = $datos_reporte['reporte'];
$filtros = $datos_reporte['filtros'];
$columnas = $datos_reporte['columnas'];
$tipo_base = $reporte['tipo_base'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Reporte: <?php echo htmlspecialchars($reporte['nombre']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f5f5f5;
            --card-bg: #ffffff;
            --text-color: #333333;
            --border-color: #dee2e6;
            --header-bg: #343a40;
            --header-text: #ffffff;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-color: #e0e0e0;
            --border-color: #404040;
            --header-bg: #000000;
            --header-text: #ffffff;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .report-header {
            background-color: var(--header-bg);
            color: var(--header-text);
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }

        .report-container {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .report-info {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .report-filters {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid var(--border-color);
        }

        .table-responsive {
            padding: 20px;
        }

        .table {
            color: var(--text-color);
        }

        .table thead th {
            background-color: var(--header-bg);
            color: var(--header-text);
            border-bottom: 2px solid var(--border-color);
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .filter-tag {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            margin: 2px;
            font-size: 0.85em;
        }

        .back-button {
            margin-bottom: 20px;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .stats-summary {
            padding: 20px;
            background-color: rgba(0, 123, 255, 0.1);
            border-left: 4px solid #007bff;
            margin: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle">
        <label class="toggle-switch">
            <input type="checkbox" id="darkModeToggle">
            <span class="slider"></span>
        </label>
    </div>

    <div class="container-fluid">
        <!-- Botón de vuelta -->
        <div class="back-button">
            <a href="reportes.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Reportes
            </a>
        </div>

        <!-- Contenedor del Reporte -->
        <div class="report-container">
            <!-- Encabezado -->
            <div class="report-header">
                <h2><i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($reporte['nombre']); ?></h2>
                <p class="mb-0">
                    <i class="fas fa-user me-2"></i>Creado por: <?php echo htmlspecialchars($reporte['nombres'] . ' ' . $reporte['apellidos']); ?>
                    <span class="ms-3"><i class="fas fa-calendar me-2"></i>Fecha: <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])); ?></span>
                </p>
            </div>

            <!-- Información del Reporte -->
            <div class="report-info">
                <h5><i class="fas fa-info-circle me-2"></i>Información del Reporte</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tipo de Reporte:</strong> <?php echo $tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'; ?></p>
                        <p><strong>Columnas Seleccionadas:</strong> <?php echo count($columnas); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Filtros Aplicados:</strong> <?php echo count($filtros); ?></p>
                        <p><strong>ID del Reporte:</strong> #<?php echo $id_reporte; ?></p>
                    </div>
                </div>
            </div>

            <!-- Filtros Aplicados -->
            <?php if (!empty($filtros)): ?>
            <div class="report-filters">
                <h5><i class="fas fa-filter me-2"></i>Filtros Aplicados</h5>
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
                            default: $campo_nombre = ucfirst($campo); break;
                        }
                        
                        if (is_array($valor)) {
                            $valor_mostrar = implode(', ', $valor);
                        } else {
                            switch($valor) {
                                case '1': $valor_mostrar = 'Sí'; break;
                                case '0': $valor_mostrar = 'No'; break;
                                default: $valor_mostrar = $valor; break;
                            }
                        }
                        ?>
                        <span class="filter-tag">
                            <strong><?php echo $campo_nombre; ?>:</strong> <?php echo htmlspecialchars($valor_mostrar); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Resultados del Reporte -->
            <div class="table-responsive">
                <?php generarReporteHTML($filtros, $columnas, $tipo_base); ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            darkModeToggle.checked = true;
        }
        
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            }
        });

        // Auto-refresh cada 30 segundos para datos en tiempo real
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
