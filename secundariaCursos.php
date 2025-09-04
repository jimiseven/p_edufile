<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Metadatos -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFile - Cursos de Secundaria</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <style>
        body {
            background-color: #1E2A38;
            color: #ffffff;
        }

        .sidebar {
            background-color: #000;
            color: #fff;
            min-width: 250px;
            min-height: 100vh;
        }

        .sidebar .nav-link,
        .sidebar .nav-link:hover {
            color: #fff;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
        }

        /* Estilos del acordeón */
        .accordion-button {
            background-color: #1F618D;
            color: #ffffff;
        }

        .accordion-button:not(.collapsed) {
            background-color: #1A5276;
            color: #ffffff;
        }

        .accordion-item {
            background-color: #2C3E50;
            border: none;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        /* Estilos de las tarjetas */
        .course-card {
            background-color: #34495E;
            border: none;
            border-radius: 10px;
            margin-bottom: 15px;
            color: #ffffff;
            padding: 10px;
            text-align: center;
        }

        .course-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1ABC9C;
        }

        .stat-badge {
            font-size: 0.75rem;
            padding: 3px 6px;
            border-radius: 12px;
            margin-right: 3px;
            margin-bottom: 3px;
            color: #ffffff;
        }

        .badge-total {
            background-color: #34495E;
        }

        .badge-efectivos {
            background-color: #27AE60;
        }

        .badge-noinscritos {
            background-color: #C0392B;
        }

        .progress {
            height: 6px;
            background-color: #34495E;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-bar {
            background-color: #1ABC9C;
        }

        .btn-view {
            background-color: #1F618D;
            border: none;
            font-weight: bold;
            color: #ffffff;
            width: 100%;
            padding: 6px;
            font-size: 0.9rem;
        }

        .btn-view:hover {
            background-color: #1A5276;
        }

        .porcentaje-efectivos {
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }

        /* Ajuste de columnas para mejorar distribución */
        .col-course {
            padding-left: 5px;
            padding-right: 5px;
        }

        .nav-link.active {
            background-color: #1F618D;
            color: #ffffff !important;
        }

        /* Mejoras en dispositivos móviles */
        @media (max-width: 768px) {
            .course-card {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>


        <!-- Main Content -->
        <div class="main-content">
            <h2 class="mb-4">Nivel Secundaria - Cursos</h2>

            <div class="accordion" id="accordionGrados">
                <?php
                // Incluir el archivo de conexión
                include 'conexion.php';

                // Consultar cursos del nivel secundaria
                $query = "SELECT id, grade, parallel FROM courses WHERE level_id = 3 ORDER BY grade, parallel";
                $result = $conn->query($query);

                $coursesByGrade = [];

                if ($result->num_rows > 0) {
                    while ($course = $result->fetch_assoc()) {
                        $grade = $course['grade'];
                        $coursesByGrade[$grade][] = $course;
                    }
                }

                foreach ($coursesByGrade as $grade => $courses) {
                    $collapseId = 'collapseGrade' . $grade;
                    $courseCount = count($courses); // Contar cursos

                    echo '<div class="accordion-item">';
                    echo '<h2 class="accordion-header" id="heading' . $grade . '">';
                    echo '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
                    echo 'Grado: ' . $grade . '° (' . $courseCount . ' Cursos)';
                    echo '</button>';
                    echo '</h2>';
                    echo '<div id="' . $collapseId . '" class="accordion-collapse collapse" aria-labelledby="heading' . $grade . '" data-bs-parent="#accordionGrados">';
                    echo '<div class="accordion-body">';
                    echo '<div class="row">';

                    foreach ($courses as $course) {
                        $course_id = $course['id'];
                        $parallel = $course['parallel'];

                        // Consultar estadísticas para este curso
                        $statsQuery = "SELECT 
                            COUNT(DISTINCT sc.student_id) AS total_registrados,
                            IFNULL(SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END), 0) AS total_efectivos,
                            IFNULL(SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END), 0) AS total_no_inscritos
                        FROM student_courses sc
                        WHERE sc.course_id = '$course_id'";

                        $statsResult = $conn->query($statsQuery);
                        $stats = $statsResult->fetch_assoc();

                        // Calcular porcentaje de efectivos
                        $porcentajeEfectivos = ($stats['total_registrados'] > 0) ? ($stats['total_efectivos'] / $stats['total_registrados']) * 100 : 0;

                        // Mostrar la tarjeta del curso
                        echo '<div class="col-6 col-md-4 col-lg-3 col-course">';
                        echo '<div class="course-card">';
                        echo '<div class="course-title">Paralelo: ' . htmlspecialchars($parallel) . '</div>';
                        echo '<div class="mb-1">';
                        echo '<span class="stat-badge badge-total">Total: ' . $stats['total_registrados'] . '</span>';
                        echo '<span class="stat-badge badge-efectivos">Efectivos: ' . $stats['total_efectivos'] . '</span>';
                        echo '<span class="stat-badge badge-noinscritos">No Inscritos: ' . $stats['total_no_inscritos'] . '</span>';
                        echo '</div>';
                        echo '<div class="progress mb-1">';
                        echo '<div class="progress-bar" role="progressbar" style="width: ' . $porcentajeEfectivos . '%;" aria-valuenow="' . $porcentajeEfectivos . '" aria-valuemin="0" aria-valuemax="100"></div>';
                        echo '</div>';
                        echo '<div class="porcentaje-efectivos mb-1">' . number_format($porcentajeEfectivos, 2) . '% Efectivos</div>';
                        echo '<a href="vistaGenCurso.php?grade=' . urlencode($grade) . '&parallel=' . urlencode($parallel) . '&level=Secundario" class="btn btn-view btn-sm">Ver <i class="bi bi-eye"></i></a>';
                        echo '</div>';
                        echo '</div>';
                    }

                    echo '</div>'; // Cierre de row
                    echo '</div>'; // Cierre de accordion-body
                    echo '</div>'; // Cierre de collapse
                    echo '</div>'; // Cierre de accordion-item
                }

                $conn->close();
                ?>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>
