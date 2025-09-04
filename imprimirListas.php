<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listas de Estudiantes por Curso</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 20px;
        }

        /* Contenedor del encabezado */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            margin-bottom: 10px;
        }

        .header-container h1,
        .header-container h2 {
            margin: 0;
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
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 4px;
            font-size: 14px;
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

        /* Ajuste de anchos */
        .col-numero {
            width: 3%;
            text-align: center;
        }

        .col-apellido-paterno,
        .col-apellido-materno,
        .col-nombres {
            width: 15%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .col-estado {
            width: 5%;
            text-align: center;
        }

        .col-asistencia {
            width: calc(47% / 6); /* Ajustar según sea necesario */
            text-align: center;
        }

        .page-break {
            page-break-after: always;
            clear: both;
        }

        @media print {
            body {
                margin: 10mm;
            }

            .page-break {
                display: block;
                page-break-after: always;
            }

            .header-right {
                font-size: 12px;
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

    foreach ($levels as $level_id => $level_name) {

        // Obtener los cursos del nivel actual
        $queryCourses = "SELECT id, grade, parallel FROM courses WHERE level_id = $level_id ORDER BY grade, parallel";
        $resultCourses = $conn->query($queryCourses);

        if ($resultCourses->num_rows > 0) {
            while ($course = $resultCourses->fetch_assoc()) {
                $course_id = $course['id'];
                $grade = $course['grade'];
                $parallel = $course['parallel'];

                // Obtener conteo de estudiantes por estado
                $queryCounts = "SELECT 
                    SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS total_efectivos,
                    SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END) AS total_no_inscritos
                FROM student_courses sc
                WHERE sc.course_id = $course_id";

                $resultCounts = $conn->query($queryCounts);
                $counts = $resultCounts->fetch_assoc();

                $total_efectivos = $counts['total_efectivos'];
                $total_no_inscritos = $counts['total_no_inscritos'];

                // Encabezado con nivel, curso y resumen
                echo '<div class="header-container">';
                echo '<div class="header-left"><h2>Nivel: ' . htmlspecialchars($level_name) . '</h2></div>';
                echo '<div class="header-center"><h2>Curso: ' . htmlspecialchars($grade) . '° - Paralelo: ' . htmlspecialchars($parallel) . '</h2></div>';
                echo '<div class="header-right">';
                echo '<p>E: ' . $total_efectivos . ' | N: ' . $total_no_inscritos . '</p>';
                echo '</div>';
                echo '</div>';

                // Obtener estudiantes efectivos y no inscritos del curso
                $queryStudents = "SELECT s.first_name, s.last_name_father, s.last_name_mother, sc.status
                                  FROM students s
                                  INNER JOIN student_courses sc ON s.id = sc.student_id
                                  WHERE sc.course_id = $course_id AND sc.status IN ('Efectivo - I', 'No Inscrito')
                                  ORDER BY FIELD(sc.status, 'Efectivo - I', 'No Inscrito'), s.last_name_father, s.last_name_mother, s.first_name";

                $resultStudents = $conn->query($queryStudents);

                if ($resultStudents->num_rows > 0) {
                    echo "<table>";
                    echo "<tr>";
                    echo "<th class='col-numero'>N°</th>";
                    echo "<th class='col-apellido-paterno'>Apellido Paterno</th>";
                    echo "<th class='col-apellido-materno'>Apellido Materno</th>";
                    echo "<th class='col-nombres'>Nombres</th>";
                    echo "<th class='col-estado'>E/N</th>";

                    // Agregar 6 columnas vacías para marcar asistencia
                    for ($i = 1; $i <= 6; $i++) {
                        echo "<th class='col-asistencia'>&nbsp;</th>";
                    }

                    echo "</tr>";
                    $counter = 1;
                    while ($student = $resultStudents->fetch_assoc()) {
                        // Reemplazar los estados por sus iniciales
                        $estado_abreviado = '';
                        if ($student['status'] == 'Efectivo - I') {
                            $estado_abreviado = 'E';
                        } elseif ($student['status'] == 'No Inscrito') {
                            $estado_abreviado = 'N';
                        } else {
                            $estado_abreviado = substr($student['status'], 0, 1);
                        }

                        echo "<tr>";
                        echo "<td class='col-numero'>" . $counter . "</td>";
                        echo "<td class='col-apellido-paterno'>" . htmlspecialchars($student['last_name_father']) . "</td>";
                        echo "<td class='col-apellido-materno'>" . htmlspecialchars($student['last_name_mother']) . "</td>";
                        echo "<td class='col-nombres'>" . htmlspecialchars($student['first_name']) . "</td>";
                        echo "<td class='col-estado'>" . htmlspecialchars($estado_abreviado) . "</td>";

                        // Agregar 6 celdas vacías
                        for ($i = 1; $i <= 6; $i++) {
                            echo "<td class='col-asistencia'>&nbsp;</td>";
                        }

                        echo "</tr>";
                        $counter++;
                    }
                    echo "</table>";
                } else {
                    echo "<p>No hay estudiantes en este curso.</p>";
                }

                // Agregar un salto de página después de cada curso
                echo "<div class='page-break'></div>";
            }
        } else {
            // Si no hay cursos en el nivel
            echo "<p>No se encontraron cursos en el nivel $level_name.</p>";
        }
    }

    $conn->close();
    ?>
</body>

</html>
