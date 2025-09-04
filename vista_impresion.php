<?php
// Incluir la conexión a la base de datos
include 'conexion.php';

// Consulta global para contar estudiantes por género y estado
$queryGlobal = "SELECT 
                    SUM(CASE WHEN gender = 'M' THEN 1 ELSE 0 END) AS hombres,
                    SUM(CASE WHEN gender = 'F' THEN 1 ELSE 0 END) AS mujeres,
                    SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS efectivos,
                    SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END) AS no_inscritos
                FROM students s
                LEFT JOIN student_courses sc ON s.id = sc.student_id";
$resultGlobal = $conn->query($queryGlobal);
$globalData = $resultGlobal->fetch_assoc();

// Consultar estadísticas por nivel, género y estado
$queryByLevel = "SELECT 
                    l.name AS level_name,
                    SUM(CASE WHEN s.gender = 'M' THEN 1 ELSE 0 END) AS hombres,
                    SUM(CASE WHEN s.gender = 'F' THEN 1 ELSE 0 END) AS mujeres,
                    SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS efectivos,
                    SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END) AS no_inscritos
                FROM students s
                INNER JOIN student_courses sc ON s.id = sc.student_id
                INNER JOIN courses c ON sc.course_id = c.id
                INNER JOIN levels l ON c.level_id = l.id
                GROUP BY l.name";
$resultByLevel = $conn->query($queryByLevel);

// Consultar todos los cursos con estudiantes efectivos (incluyendo cursos sin estudiantes efectivos)
$queryByCourse = "SELECT 
                    l.name AS level_name,
                    CONCAT(c.grade, ' ', c.parallel) AS course_name,
                    SUM(CASE WHEN s.gender = 'M' AND sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS hombres,
                    SUM(CASE WHEN s.gender = 'F' AND sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS mujeres,
                    SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS total
                FROM courses c
                INNER JOIN levels l ON c.level_id = l.id
                LEFT JOIN student_courses sc ON c.id = sc.course_id
                LEFT JOIN students s ON sc.student_id = s.id
                GROUP BY l.name, c.grade, c.parallel
                ORDER BY l.name, c.grade, c.parallel";
$resultByCourse = $conn->query($queryByCourse);

// Consultar estudiantes con estado "Efectivo" por nivel (inicial, primaria, secundaria)
$queryByLevelEfectivo = "SELECT 
                            l.name AS level_name,
                            SUM(CASE WHEN s.gender = 'M' THEN 1 ELSE 0 END) AS hombres,
                            SUM(CASE WHEN s.gender = 'F' THEN 1 ELSE 0 END) AS mujeres,
                            COUNT(*) AS total
                        FROM students s
                        INNER JOIN student_courses sc ON s.id = sc.student_id
                        INNER JOIN courses c ON sc.course_id = c.id
                        INNER JOIN levels l ON c.level_id = l.id
                        WHERE sc.status = 'Efectivo - I'
                        GROUP BY l.name";
$resultByLevelEfectivo = $conn->query($queryByLevelEfectivo);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista de Impresión - Estadísticas de Estudiantes</title>
    <style>
        /* Estilos para la impresión */
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 0;
            padding: 20px;
        }

        h1, h2, h3 {
            text-align: center;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .no-print {
            display: none;
        }

        .page-break {
            page-break-before: always;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }
    </style>
</head>

<body>
    <h1>Estadísticas de Estudiantes</h1>
    <p class="text-center">Fecha de generación: <?= date("d/m/Y H:i:s") ?></p>

    <!-- Totales Generales -->
    <h2>Totales Generales</h2>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Hombres</th>
                <th>Mujeres</th>
                <th>Efectivos</th>
                <th>No Inscritos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total</td>
                <td><?= $globalData['hombres'] ?></td>
                <td><?= $globalData['mujeres'] ?></td>
                <td><?= $globalData['efectivos'] ?></td>
                <td><?= $globalData['no_inscritos'] ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Estadísticas por Nivel -->
    <h2 class="mt-4">Estadísticas por Nivel Educativo</h2>
    <?php while ($level = $resultByLevel->fetch_assoc()) { ?>
        <h3><?= htmlspecialchars($level['level_name']) ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Hombres</th>
                    <th>Mujeres</th>
                    <th>Efectivos</th>
                    <th>No Inscritos</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total</td>
                    <td><?= $level['hombres'] ?></td>
                    <td><?= $level['mujeres'] ?></td>
                    <td><?= $level['efectivos'] ?></td>
                    <td><?= $level['no_inscritos'] ?></td>
                </tr>
            </tbody>
        </table>
    <?php } ?>

    <!-- Estudiantes con estado "Efectivo" por Curso -->
    <h2 class="mt-4">Estudiantes Efectivos por Curso</h2>
    <?php
    // Organizar los cursos por nivel
    $cursosPorNivel = [];
    while ($curso = $resultByCourse->fetch_assoc()) {
        $nivel = $curso['level_name'];
        if (!isset($cursosPorNivel[$nivel])) {
            $cursosPorNivel[$nivel] = [];
        }
        $cursosPorNivel[$nivel][] = $curso;
    }

    // Mostrar los cursos agrupados por nivel
    foreach ($cursosPorNivel as $nivel => $cursos) {
        echo "<h3>Nivel: $nivel</h3>";
        echo "<table>";
        echo "<thead>
                <tr>
                    <th>Curso</th>
                    <th>Hombres</th>
                    <th>Mujeres</th>
                    <th>Total</th>
                </tr>
              </thead>
              <tbody>";
        foreach ($cursos as $curso) {
            echo "<tr>
                    <td>{$curso['course_name']}</td>
                    <td>{$curso['hombres']}</td>
                    <td>{$curso['mujeres']}</td>
                    <td>{$curso['total']}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    }
    ?>

    <!-- Estudiantes con estado "Efectivo" por Nivel (Inicial, Primaria, Secundaria) -->
    <h2 class="mt-4">Estudiantes Efectivos por Nivel</h2>
    <table>
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Hombres</th>
                <th>Mujeres</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($levelEfectivo = $resultByLevelEfectivo->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($levelEfectivo['level_name']) ?></td>
                    <td><?= $levelEfectivo['hombres'] ?></td>
                    <td><?= $levelEfectivo['mujeres'] ?></td>
                    <td><?= $levelEfectivo['total'] ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Botón de impresión (solo visible en el navegador) -->
    <div class="no-print text-center mt-4">
        <button onclick="window.print()" class="btn-print">Imprimir</button>
        <a href="index.php" class="btn-back">Volver al Inicio</a>
    </div>
</body>

</html>