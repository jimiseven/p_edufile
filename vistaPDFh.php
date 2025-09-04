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
            width: 21.59cm;
            height: 13.97cm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start; /* Cambiado a flex-start para subir el contenido */
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
            width: 100%;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 3px 0;
        }

        .header h2 {
            font-size: 12px;
            margin: 2px 0;
        }

        .btn-print {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
            padding: 5px 10px;
        }

        .table-container {
            display: flex;
            justify-content: space-between;
            gap: 5px;
            width: 100%;
            padding: 0 5px;
        }

        .table {
            width: 49%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 9px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 2px;
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
            width: 5%;
            text-align: center;
            padding: 1px;
        }

        /* Ajustar el ancho de la columna de nombres combinados */
        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 65%;
            text-align: left;
            padding-left: 5px;
        }
        /* Ocultar las columnas originales de apellidos y nombres */
        .table th:nth-child(3),
        .table td:nth-child(3),
        .table th:nth-child(4),
        .table td:nth-child(4) {
            display: none;
        }


        @media print {
            body {
                width: 21.59cm;
                height: 13.97cm;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start; /* Cambiado a flex-start para subir el contenido en impresión */
            }

            .btn-print {
                display: none;
            }

            .header h1 {
                font-size: 14px;
            }

            .header h2 {
                font-size: 10px;
            }

            .table-container {
                width: 100%;
                padding: 0;
                gap: 3px;
            }

            .table {
                width: 49%;
                font-size: 8px;
                padding: 1px;
            }

            .table th,
            .table td {
                padding: 1px;
                text-align: left;
            }
             /* Ajustar el ancho de la columna del número en impresión */
            .table th:nth-child(1),
            .table td:nth-child(1) {
                width: 5%;
                text-align: center;
                padding: 1px;
            }
            /* Ajustar el ancho de la columna de nombres combinados en impresión */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                width: 65%;
                text-align: left;
                padding-left: 3px;
            }
            /* Ocultar las columnas originales de apellidos y nombres en impresión */
            .table th:nth-child(3),
            .table td:nth-child(3),
            .table th:nth-child(4),
            .table td:nth-child(4) {
                display: none;
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
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $index = 1;
                $maxRows = 17;

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullName = htmlspecialchars($row['last_name_father']) . ' ' . htmlspecialchars($row['last_name_mother']) . ', ' . htmlspecialchars($row['first_name']);
                        echo "<tr>
                                <td>{$index}</td>
                                <td>{$fullName}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                              </tr>";
                        $index++;
                        if ($index > $maxRows) break;
                    }
                } else {
                    echo "<tr><td colspan='9'>No se encontraron estudiantes con el estado 'Efectivo - I' para este curso.</td></tr>";
                }

                // Agregar filas vacías si no se alcanzan las 17
                for ($i = $index; $i <= $maxRows; $i++) {
                    echo "<tr>
                            <td>{$i}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>APELLIDOS Y NOMBRES</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $index = 18;

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if ($index > 35) break;
                        $fullName = htmlspecialchars($row['last_name_father']) . ' ' . htmlspecialchars($row['last_name_mother']) . ', ' . htmlspecialchars($row['first_name']);
                        echo "<tr>
                                <td>{$index}</td>
                                <td>{$fullName}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                              </tr>";
                        $index++;
                    }
                }

                // Agregar filas vacías si no se alcanzan las 35
                for ($i = $index; $i <= 35; $i++) {
                    echo "<tr>
                            <td>{$i}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>";
                }

                $stmt->close();
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>