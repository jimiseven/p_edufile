<?php
session_start();
require_once '../config/database.php';

// Verificar administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$conn = (new Database())->connect();

// Obtener todos los cursos de secundaria con datos de estudiantes
$stmt = $conn->query("
    SELECT c.id_curso, c.curso, c.paralelo,
           COUNT(e.id_estudiante) as total_estudiantes,
           SUM(CASE WHEN e.genero = 'Masculino' THEN 1 ELSE 0 END) as hombres,
           SUM(CASE WHEN e.genero = 'Femenino' THEN 1 ELSE 0 END) as mujeres
    FROM cursos c
    LEFT JOIN estudiantes e ON c.id_curso = e.id_curso
    WHERE c.nivel = 'Secundaria'
    GROUP BY c.id_curso, c.curso, c.paralelo
    ORDER BY c.curso, c.paralelo
");
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales generales
$total_cursos = count($cursos);
$total_estudiantes = array_sum(array_column($cursos, 'total_estudiantes'));
$total_hombres = array_sum(array_column($cursos, 'hombres'));
$total_mujeres = array_sum(array_column($cursos, 'mujeres'));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cursos de Secundaria</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link id="bootstrap-css" rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body, html {
            height: 100%;
            background-color: #121212;
            color: #eaeaea;
            overflow-x: hidden;
        }
        
        .container-fluid, .row {
            height: 100%;
        }
        
        .sidebar {
            background: #19202a;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        main {
            background: #121212;
            height: 100vh;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .content-wrapper {
            background: var(--content-bg, #1f1f1f);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            margin-top: 25px;
        }

        .table-cursos {
            background: var(--table-bg, #1a1a1a);
        }

        .table-cursos th {
            background: var(--th-bg, #232323);
            color: #99b898;
            text-align: center;
            font-size: 1rem;
        }

        .table-cursos td {
            text-align: center;
            vertical-align: middle;
        }

        .table-cursos tr:hover {
            background: var(--tr-hover, #282828);
        }

        .btn-centralizador {
            background: #99b898;
            color: #222;
            border: none;
            font-weight: 600;
            border-radius: 5px;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-centralizador:hover {
            background: #4c5c68;
            color: #fff;
            transform: scale(1.05);
        }

        .title-box {
            border-left: 6px solid #99b898;
            padding-left: 1rem;
            margin-bottom: 2rem;
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
        }

        body.dark-mode {
            --content-bg: #1f1f1f;
            --table-bg: #1a1a1a;
            --th-bg: #232323;
            --tr-hover: #282828;
        }

        /* Estilos minimalistas para las tarjetas de resumen */
        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s ease;
        }

        .summary-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .summary-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
            line-height: 1;
        }

        .summary-card .label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card .gender-breakdown {
            text-align: left;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }

        .summary-card .gender-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.875rem;
        }

        .summary-card .gender-row:last-child {
            margin-bottom: 0;
        }

        .summary-card .gender-row .gender-label {
            color: #6b7280;
            font-weight: 500;
        }

        .summary-card .gender-row .gender-count {
            color: #1f2937;
            font-weight: 600;
        }

        /* Modo oscuro para las tarjetas */
        body.dark-mode .summary-card {
            background: #1f2937;
            border-color: #374151;
        }

        body.dark-mode .summary-card .number {
            color: #f9fafb;
        }

        body.dark-mode .summary-card .label {
            color: #9ca3af;
        }

        body.dark-mode .summary-card .gender-breakdown {
            border-top-color: #374151;
        }

        body.dark-mode .summary-card .gender-row .gender-label {
            color: #9ca3af;
        }

        body.dark-mode .summary-card .gender-row .gender-count {
            color: #f9fafb;
        }

        /* Estilos para el modal de cierre de sesión */
        .modal-content {
            background-color: #fff !important;
            color: #000 !important;
        }

        .modal-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        .modal-title {
            color: #212529 !important;
            font-weight: 600;
        }

        .modal-body {
            color: #495057 !important;
            font-size: 1rem;
        }

        .modal-footer {
            background-color: #f8f9fa !important;
            border-top: 1px solid #dee2e6 !important;
        }

        .modal-footer .btn {
            color: #fff !important;
        }

        .modal-footer .btn-outline-secondary {
            color: #6c757d !important;
            background-color: transparent !important;
        }

        .modal-footer .btn-outline-secondary:hover {
            color: #fff !important;
            background-color: #6c757d !important;
        }

        .modal-footer .btn-primary {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        .btn-close {
            filter: none !important;
            opacity: 1 !important;
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
                    <div class="title-box mb-4">
                        <h2 class="mb-0" style="color:#99b898;">Cursos de Secundaria</h2>
                        <small class="text-secondary">Seleccione el curso que desea visualizar:</small>
                    </div>

                    <!-- Tarjetas de Resumen -->
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="number"><?php echo $total_cursos; ?></div>
                            <div class="label">Total cursos</div>
                        </div>
                        <div class="summary-card">
                            <div class="number"><?php echo $total_estudiantes; ?></div>
                            <div class="label">Total estudiantes</div>
                        </div>
                        <div class="summary-card">
                            <div class="gender-breakdown">
                                <div class="gender-row">
                                    <span class="gender-label">Hombres</span>
                                    <span class="gender-count"><?php echo $total_hombres; ?></span>
                                </div>
                                <div class="gender-row">
                                    <span class="gender-label">Mujeres</span>
                                    <span class="gender-count"><?php echo $total_mujeres; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-cursos table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Curso</th>
                                    <th style="width: 80px;">Total</th>
                                    <th style="width: 80px;">Hombres</th>
                                    <th style="width: 80px;">Mujeres</th>
                                    <th style="width: 200px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cursos)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="alert alert-warning mb-0">
                                                No hay cursos de secundaria registrados.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $n = 1;
                                    foreach ($cursos as $curso): ?>
                                        <tr>
                                            <td><?php echo $n++; ?></td>
                                            <td><?php echo htmlspecialchars("{$curso['curso']} {$curso['paralelo']}"); ?></td>
                                            <td><?php echo $curso['total_estudiantes']; ?></td>
                                            <td><?php echo $curso['hombres']; ?></td>
                                            <td><?php echo $curso['mujeres']; ?></td>
                                            <td>
                                                <a href="ver_curso.php?id=<?php echo $curso['id_curso']; ?>" class="btn btn-centralizador">
                                                    VER
                                                </a>
                                                <a href="boletin_secundaria.php?id_curso=<?= $curso['id_curso'] ?>"
                                                    class="btn btn-success btn-action">
                                                    <i class="ri-printer-line"></i> Boletín
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
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
    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>