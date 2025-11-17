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

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #99b898;
        }

        .page-title {
            color: #99b898;
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
            background: var(--table-bg, #1a1a1a);
            border-radius: 10px;
            overflow: hidden;
        }

        .table-reports {
            margin: 0;
            color: #eaeaea;
        }

        .table-reports th {
            background: var(--th-bg, #232323);
            color: #99b898;
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
            color: #eaeaea;
        }

        .table-reports tr:hover {
            background: var(--tr-hover, #282828);
        }

        .table-reports tbody tr {
            border-bottom: 1px solid var(--table-border, #333);
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
            color: #b0b0b0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #666;
            margin-bottom: 1rem;
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
            --table-bg: #fff;
            --th-bg: #e9ecef;
            --tr-hover: #e0eafc;
            --table-border: #dee2e6;
        }

        body.dark-mode {
            --content-bg: #1f1f1f;
            --table-bg: #1a1a1a;
            --th-bg: #232323;
            --tr-hover: #282828;
            --table-border: #333;
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
                <!-- Toggle Modo Claro/Oscuro -->
                <div class="toggle-switch">
                    <label for="toggleMode">☀️/🌙</label>
                    <input type="checkbox" id="toggleMode" <?php if (isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] == 'on') echo "checked"; ?>>
                </div>
                
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
                                                <a href="#" class="btn-action btn-download" title="Descargar">
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