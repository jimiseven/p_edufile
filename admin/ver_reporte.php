<?php
session_start();
require_once '../config/database.php';
require_once 'includes/report_functions.php';
require_once 'report_generator.php';

// Funciones auxiliares para evitar redundancia
function obtenerNombreCampo($campo) {
    $nombres = [
        'nivel' => 'Nivel',
        'curso' => 'Curso', 
        'paralelo' => 'Paralelo',
        'genero' => 'Género',
        'edad_min' => 'Edad Mínima',
        'edad_max' => 'Edad Máxima',
        'pais' => 'País',
        'con_carnet' => 'Con Carnet',
        'con_rude' => 'Con RUDE'
    ];
    return $nombres[$campo] ?? ucfirst($campo);
}

function obtenerValorMostrar($valor) {
    if (is_array($valor)) {
        return implode(', ', $valor);
    }
    
    $valores_especiales = [
        '1' => 'Sí',
        '0' => 'No'
    ];
    return $valores_especiales[$valor] ?? $valor;
}

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
            --content-bg: #ffffff;
            --card-bg: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --sidebar-bg: #2c3e50;
            --header-bg: #34495e;
        }

        body {
            background-color: #f8f9fa;
            color: #333333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .content-wrapper {
            background: var(--content-bg);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .page-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .report-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ffffff;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            margin-bottom: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .report-container {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .report-info {
            padding: 2rem;
            border-bottom: 1px solid var(--border-color);
            background: #f8f9fa;
        }

        .report-filters {
            padding: 2rem;
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid var(--border-color);
        }

        .table-responsive {
            padding: 2rem;
        }

        .table {
            color: var(--text-primary);
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }

        .filter-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #e9ecef;
            border-radius: 20px;
            margin: 0.25rem;
            font-size: 0.9rem;
        }

        .text-light-50 {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
                margin: 1rem;
            }
            
            .report-header {
                padding: 1rem;
            }
            
            .table-responsive {
                padding: 1rem;
            }
        }

        /* Sidebar fijo */
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }

        .row.position-relative {
            margin-left: 0;
            margin-right: 0;
        }

        /* Asegurar que el sidebar quede fijo */
        #sidebarMenu {
            position: fixed !important;
            top: 0;
            left: 0;
            height: 100vh !important;
            z-index: 1000;
            overflow-y: auto;
            width: 16.666667% !important; /* col-md-2 */
        }

        /* Ajustar el contenido principal para que no se superponga con el sidebar */
        main {
            margin-left: 16.666667% !important;
            width: calc(100% - 16.666667%) !important;
            min-height: 100vh;
        }

        @media (max-width: 991px) and (min-width: 768px) {
            #sidebarMenu {
                width: 25% !important; /* col-md-3 */
            }
            
            main {
                margin-left: 25% !important;
                width: calc(100% - 25%) !important;
            }
        }

        @media (max-width: 767px) {
            #sidebarMenu {
                position: fixed !important;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px !important;
            }
            
            #sidebarMenu.show {
                transform: translateX(0);
            }
            
            main {
                margin-left: 0 !important;
                width: 100% !important;
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
                        <i class="fas fa-file-alt me-2"></i>
                        Ver Reporte: <?php echo htmlspecialchars($reporte['nombre']); ?>
                    </h1>
            <!-- Encabezado -->
            <div class="report-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="mb-2"><i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($reporte['nombre']); ?></h2>
                        <?php if (!empty($reporte['descripcion'])): ?>
                        <p class="mb-2 text-light-50">
                            <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($reporte['descripcion']); ?>
                        </p>
                        <?php endif; ?>
                        <p class="mb-0">
                            <i class="fas fa-user me-2"></i>Creado por: <?php echo htmlspecialchars($reporte['nombres'] . ' ' . $reporte['apellidos']); ?>
                            <span class="ms-3"><i class="fas fa-calendar me-2"></i>Fecha: <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])); ?></span>
                        </p>
                    </div>
                    <div class="header-actions">
                        <a href="constructor_reporte.php?editar=<?php echo $id_reporte; ?>" class="btn btn-edit">
                            <i class="fas fa-edit me-2"></i>Editar Reporte
                        </a>
                    </div>
                </div>
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
                        <span class="filter-tag">
                            <strong><?php echo obtenerNombreCampo($campo); ?>:</strong> <?php echo htmlspecialchars(obtenerValorMostrar($valor)); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Resultados del Reporte -->
            <div class="table-responsive">
                <?php generarReporteHTML($filtros, $columnas, $tipo_base); ?>
            </div>

            <!-- Botones de acción -->
            <div class="mt-4 d-flex gap-3">
                <button onclick="descargarExcel(<?php echo $id_reporte; ?>)" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>Descargar Excel
                </button>
                <a href="reportes.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Volver a Reportes
                </a>
            </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para descargar Excel con orden actual de la tabla
        function descargarExcel(idReporte) {
            // Capturar el orden actual de la tabla si existe
            const tableBody = document.getElementById('reportTableBody');
            let sortedData = [];
            
            if (tableBody) {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    const rowData = [];
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        rowData.push(cell.dataset.value || cell.textContent.trim());
                    });
                    sortedData.push(rowData);
                });
            }
            
            // Crear formulario para enviar datos por POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'download_report_excel.php';
            
            // Agregar ID del reporte
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = idReporte;
            form.appendChild(inputId);
            
            // Agregar datos ordenados si existen
            if (sortedData.length > 0) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'sorted_data';
                input.value = JSON.stringify(sortedData);
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Auto-refresh cada 30 segundos para datos en tiempo real
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
