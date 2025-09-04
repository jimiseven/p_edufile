<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Lista de Estudiantes</title>
    <style>
        body {
            background-color: #ffffff;
            color: #000000;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 10.79cm; /* Nueva dimensión: ancho */
            height: 35.56cm; /* Nueva dimensión: alto */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            width: 100%;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 2px 0;
        }

        .header h2 {
            font-size: 11px;
            margin: 1px 0;
        }

        .btn-print {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 10px;
            padding: 3px 6px;
        }

        .table-container {
            width: 95%; /* Ajuste para márgenes dentro del contenedor */
            margin: 0 auto; /* Centrar el contenedor */
        }

        .table {
            width: 100%; /* La tabla ocupa todo el ancho del contenedor */
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 8px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 1px;
            text-align: left;
        }

        .table th {
            background-color: #1F618D;
            color: #ffffff;
            text-align: center;
        }

        /* Ajustar el ancho de la columna del número */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 10%; /* Ancho ajustado para una sola columna - Más angosta */
            text-align: center;
            padding: 1px;
        }

        /* Ajustar el ancho de la columna de nombres combinados */
        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 70%; /* Ancho ajustado para una sola columna - Más angosta */
            text-align: left;
            padding-left: 3px;
            font-size: 10px; /* Aumentando el tamaño de la letra de los nombres */
        }


        @media print {
            body {
                width: 10.79cm; /* Nueva dimensión: ancho en impresión */
                height: 35.56cm; /* Nueva dimensión: alto en impresión */
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
            }

            .btn-print {
                display: none;
            }

            .header h1 {
                font-size: 12px;
            }

            .header h2 {
                font-size: 9px;
            }

            .table-container {
                width: 100%;
                padding: 0;
            }

            .table {
                width: 100%;
                font-size: 7px;
                margin-top: 2px;
            }

            .table th,
            .table td {
                padding: 1px;
                text-align: left;
            }
             /* Ajustar el ancho de la columna del número en impresión */
            .table th:nth-child(1),
            .table td:nth-child(1) {
                width: 10%; /* Ancho ajustado para una sola columna en impresión - Más angosta */
                text-align: center;
                padding: 1px;
            }
            /* Ajustar el ancho de la columna de nombres combinados en impresión */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                width: 70%; /* Ancho ajustado para una sola columna en impresión - Más angosta */
                text-align: left;
                padding-left: 2px;
                font-size: 11px; /* Aumentando el tamaño de la letra de los nombres en impresion */
            }
        }
    </style>
</head>

<body>
    <?php
    include 'conexion.php';

    // Obtener parámetros desde la URL
    $grade = isset($_GET['grade']) ? $_GET['grade'] : '';
    $parallel = isset($_GET['parallel']) ? $_GET['parallel'] : '';
    $levelName = isset($_GET['level']) ? $_GET['level'] : '';

    // Verificar que los parámetros sean válidos
    if (empty($grade) || empty($parallel) || empty($levelName)) {
        echo "<div class='alert alert-danger'>Parámetros de curso no válidos.</div>";
        exit;
    }

    // Obtener level_id basado en el nombre del nivel
    $levelQuery = "SELECT id FROM levels WHERE name = ?";
    $stmt = $conn->prepare($levelQuery);
    $stmt->bind_param("s", $levelName);
    $stmt->execute();
    $levelResult = $stmt->get_result();

    if ($levelResult && $levelResult->num_rows > 0) {
        $levelData = $levelResult->fetch_assoc();
        $levelId = $levelData['id'];
    } else {
        echo "<div class='alert alert-danger'>Nivel no válido.</div>";
        exit;
    }

    // Obtener course_id basado en grade, parallel y level_id
    $courseQuery = "SELECT id FROM courses WHERE grade = ? AND parallel = ? AND level_id = ?";
    $stmt = $conn->prepare($courseQuery);
    $stmt->bind_param("ssi", $grade, $parallel, $levelId);
    $stmt->execute();
    $courseResult = $stmt->get_result();

    if ($courseResult && $courseResult->num_rows > 0) {
        $courseData = $courseResult->fetch_assoc();
        $courseId = $courseData['id'];
    } else {
        echo "<div class='alert alert-danger'>Curso no válido.</div>";
        exit;
    }

    // Obtener estudiantes con estado "Efectivo - I" para el curso específico
    $query = "SELECT
                UPPER(s.last_name_father) AS last_name_father,
                UPPER(s.last_name_mother) AS last_name_mother,
                UPPER(s.first_name) AS first_name
              FROM students s
              INNER JOIN student_courses sc ON s.id = sc.student_id
              WHERE sc.course_id = ? AND sc.status = 'Efectivo - I'
              ORDER BY
                s.last_name_father ASC,
                s.last_name_mother ASC,
                s.first_name ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <!-- Botón de imprimir -->
    <a href="javascript:window.print();" class="btn btn-primary btn-print">Imprimir</a>

    <div class="header">
        <h1>U.E. SIMÓN BOLÍVAR</h1>
        <h2>LISTA DE ESTUDIANTES</h2>
        <h2>NIVEL: <?php echo htmlspecialchars($levelName); ?></h2>
        <h2>CURSO: <?php echo htmlspecialchars($grade . ' "' . $parallel . '"'); ?></h2>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>APELLIDOS Y NOMBRES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $index = 1;
                $maxRows = 35;

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullName = htmlspecialchars($row['last_name_father']) . ' ' . htmlspecialchars($row['last_name_mother']) . ', ' . htmlspecialchars($row['first_name']);
                        echo "<tr>
                                <td>{$index}</td>
                                <td>{$fullName}</td>
                              </tr>";
                        $index++;
                        if ($index > $maxRows) break;
                    }
                } else {
                    echo "<tr><td colspan='2'>No se encontraron estudiantes con el estado 'Efectivo - I' para este curso.</td></tr>";
                }

                // Agregar filas vacías si no se alcanzan las 35
                for ($i = $index; $i <= $maxRows; $i++) {
                    echo "<tr>
                            <td>{$i}</td>
                            <td></td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>
