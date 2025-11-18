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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de Reportes</title>
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

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #99b898;
        }

        .page-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .btn-new-report {
            background: #99b898;
            color: #222;
            border: none;
            font-weight: 600;
            border-radius: 5px;
            padding: 0.5rem 1.5rem;
            transition: background 0.2s, transform 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-new-report:hover {
            background: #4c5c68;
            color: #fff;
            transform: scale(1.05);
            text-decoration: none;
        }

        .reports-table {
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-reports {
            margin: 0;
            color: #333333;
        }

        .table-reports th {
            background: #e9ecef;
            color: #2c3e50;
            text-align: center;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table-reports td {
            text-align: center;
            vertical-align: middle;
            border: none;
            padding: 0.75rem 1rem;
            color: #333333;
        }

        .table-reports tr:hover {
            background: #f8f9fa;
        }

        .table-reports tbody tr {
            border-bottom: 1px solid #dee2e6;
        }

        .btn-action {
            padding: 0.25rem 0.75rem;
            margin: 0 0.25rem;
            border-radius: 5px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #17a2b8;
            color: #fff;
            border: none;
        }

        .btn-view:hover {
            background: #138496;
            color: #fff;
            transform: scale(1.05);
        }

        .btn-download {
            background: #28a745;
            color: #fff;
            border: none;
        }

        .btn-download:hover {
            background: #218838;
            color: #fff;
            transform: scale(1.05);
        }

        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
        }

        .btn-delete:hover {
            background: #c82333;
            color: #fff;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .btn-action {
                margin: 0.25rem 0.1rem;
                padding: 0.2rem 0.5rem;
                font-size: 0.8rem;
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
                    <!-- Mensajes de éxito/error -->
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['mensaje'];
                            unset($_SESSION['mensaje']);
                            unset($_SESSION['tipo_mensaje']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Header con título y botón -->
                    <div class="header-section">
                        <h2 class="page-title">
                            <i class="fas fa-list me-2"></i>
                            Lista de Reportes
                        </h2>
                        <a href="constructor_reporte.php?tipo=info_estudiantil" class="btn-new-report">
                            <i class="fas fa-plus me-1"></i>
                            Reporte Nuevo
                        </a>
                    </div>

                    <!-- Tabla de Reportes -->
                    <div class="reports-table">
                        <?php
                        // Obtener reportes guardados de la base de datos usando la tabla correcta
                        $stmt = $conn->query("SELECT rg.id_reporte, rg.nombre, rg.fecha_creacion, p.nombres, p.apellidos 
                                             FROM reportes_guardados rg 
                                             LEFT JOIN personal p ON rg.id_personal = p.id_personal 
                                             ORDER BY rg.fecha_creacion DESC");
                        $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (count($reportes) > 0): ?>
                            <table class="table table-reports">
                                <thead>
                                    <tr>
                                        <th>Num</th>
                                        <th>Nombre Reporte</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $num = 1;
                                    foreach ($reportes as $reporte): 
                                    ?>
                                        <tr>
                                            <td><?php echo $num++; ?></td>
                                            <td><?php echo htmlspecialchars($reporte['nombre']); ?></td>
                                            <td>
                                                <a href="#" class="btn-action btn-view" title="Ver Reporte" onclick="viewReport(<?php echo $reporte['id_reporte']; ?>); return false;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="btn-action btn-download" title="Descargar" onclick="downloadReport(<?php echo $reporte['id_reporte']; ?>); return false;">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="#" class="btn-action btn-delete" title="Eliminar" onclick="deleteReport(<?php echo $reporte['id_reporte']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h4>No hay reportes guardados</h4>
                                <p>No se encontraron reportes en el sistema. Crea tu primer reporte usando el botón "Reporte Nuevo".</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Funciones para manejar reportes
        function openNewReportModal() {
            // Aquí puedes abrir un modal para crear un nuevo reporte
            alert('Función para crear nuevo reporte - pendiente de implementar');
        }

        function viewReport(id) {
            // Función para ver un reporte
            window.location.href = 'ver_reporte.php?id=' + id;
        }

        function downloadReport(id) {
            // Función para descargar un reporte
            window.location.href = 'download_report.php?id=' + id;
        }

        function deleteReport(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este reporte?')) {
                // Aquí puedes implementar la eliminación vía AJAX o redirección
                window.location.href = 'delete_report.php?id=' + id;
            }
        }
    </script>
</body>
</html>