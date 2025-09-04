<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listas de Estudiantes Efectivos por Curso</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 10mm;
        }

        /* Contenedor principal para 2 cursos por página */
        .page-container {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            width: 100%;
        }

        /* Contenedor para cada curso */
        .course-container {
            width: 50%;
            padding-right: 10mm;
            box-sizing: border-box;
            margin-bottom: 10mm;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .header-container h1,
        .header-container h2 {
            margin: 0;
            font-size: 18px;
        }

        .header-left,
        .header-center,
        .header-right {
            width: 33%;
            text-align: left;
        }

        .header-center {
            text-align: center;
        }

        .header-right {
            text-align: right;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 3px;
            font-size: 12px;
            word-wrap: break-word;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: normal;
        }

        td {
            text-align: left;
            vertical-align: middle;
        }

        /* Ajuste de anchos de columnas para horizontal */
        .col-numero {
            width: 3%; /* Reduced width for number column */
            text-align: center;
        }

        .col-nombre-completo {
            width: 40%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 11px; /* Reduced font size for names */
        }


        .col-estado {
            display: none; /* Hide estado column */
        }

        .col-apellido-paterno,
        .col-apellido-materno,
        .col-nombres {
            display: none; /* Hide individual name columns */
        }

        .col-asistencia {
            display: none; /* Hide asistencia columns */
        }

        .page-break {
            page-break-after: always;
            clear: both;
            display: block;
        }

        @media print {
            body {
                margin: 5mm;
                size: landscape;
            }

            .page-container {
                flex-direction: row;
            }

            .course-container {
                width: 50%;
                padding-right: 5mm;
                margin-bottom: 5mm;
            }

            .page-break {
                display: block;
                page-break-after: always;
            }

            .header-right {
                font-size: 10px;
            }
        }
    </style>
</head>

<body>
    <?php
    include 'conexion.php';

    // Definir los niveles
    $levels = [
        1 => 'Inicial',
        2 => 'Primaria',
        3 => 'Secundaria'
    ];

    $course_counter = 0;
    $page_started = false; // Flag to track if page-container is started

    echo '<div class="page-container">'; // Start initial page container
    $page_started = true;

    foreach ($levels as $level_id => $level_name) {
        // Obtener los cursos del nivel actual
        $queryCourses = "SELECT id, grade, parallel FROM courses WHERE level_id = $level_id ORDER BY grade, parallel";
        $resultCourses = $conn->query($queryCourses);

        if ($resultCourses->num_rows > 0) {
            while ($course = $resultCourses->fetch_assoc()) {
                $course_id = $course['id'];
                $grade = $course['grade'];
                $parallel = $course['parallel'];

                // Obtener conteo de estudiantes efectivos
                $queryCounts = "SELECT
                    SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS total_efectivos
                FROM student_courses sc
                WHERE sc.course_id = $course_id AND sc.status = 'Efectivo - I'";

                $resultCounts = $conn->query($queryCounts);
                $counts = $resultCounts->fetch_assoc();
                $total_efectivos = $counts['total_efectivos'];

                // Solo procesar cursos con estudiantes efectivos
                if ($total_efectivos > 0) {
                    $course_counter++;

                    // Start course container
                    echo '<div class="course-container">';

                    // Encabezado con nivel y curso
                    echo '<div class="header-container">';
                    echo '<div class="header-left"><h2>Nivel: ' . htmlspecialchars($level_name) . '</h2></div>';
                    echo '<div class="header-center"><h2>Curso: ' . htmlspecialchars($grade) . '° "' . htmlspecialchars($parallel) . '"</h2></div>';
                    echo '<div class="header-right">';
                    echo '<p>Efectivos: ' . $total_efectivos . '</p>';
                    echo '</div>';
                    echo '</div>';

                    // Obtener solo estudiantes efectivos del curso
                    $queryStudents = "SELECT s.first_name, s.last_name_father, s.last_name_mother
                                      FROM students s
                                      INNER JOIN student_courses sc ON s.id = sc.student_id
                                      WHERE sc.course_id = $course_id AND sc.status = 'Efectivo - I'
                                      ORDER BY s.last_name_father, s.last_name_mother, s.first_name";

                    $resultStudents = $conn->query($queryStudents);

                    if ($resultStudents->num_rows > 0) {
                        echo "<table>";
                        echo "<tr>";
                        echo "<th class='col-numero'>N°</th>";
                        echo "<th class='col-nombre-completo'>Apellidos y Nombres</th>";
                        echo "</tr>";
                        $counter = 1;
                        while ($student = $resultStudents->fetch_assoc()) {
                            $fullName = strtoupper(htmlspecialchars($student['last_name_father']) . ' ' . htmlspecialchars($student['last_name_mother']) . ', ' . htmlspecialchars($student['first_name'])); // Convert to uppercase
                            echo "<tr>";
                            echo "<td class='col-numero'>" . $counter . "</td>";
                            echo "<td class='col-nombre-completo'>" . $fullName . "</td>";
                            echo "</tr>";
                            $counter++;
                        }
                        echo "</table>";
                    } else {
                        echo "<p>No hay estudiantes efectivos en este curso.</p>";
                    }
                    echo '</div>'; // End course container

                    if ($course_counter % 2 == 0) {
                        echo '</div>'; // Close page container
                        echo '<div class="page-break"></div>';
                        echo '<div class="page-container">'; // Start new page container
                        $page_started = true;
                    }
                }
            }
        }
    }

    if ($page_started) {
        echo '</div>'; // Ensure last page container is closed if it was started
    }


    $conn->close();
    ?>
</body>

</html>
