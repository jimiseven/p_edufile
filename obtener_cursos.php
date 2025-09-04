<?php
include 'conexion.php';

$selectedLevel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$levels = ['Inicial', 'Primario', 'Secundario']; // Ajustar según tu BD

// Consulta para cursos con filtro
$queryByCourse = "SELECT 
                l.name AS level_name,
                CONCAT(c.grade, ' ', c.parallel) AS course_name,
                SUM(CASE WHEN s.gender = 'M' AND sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS hombres,
                SUM(CASE WHEN s.gender = 'F' AND sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS mujeres,
                SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS total
            FROM courses c
            INNER JOIN levels l ON c.level_id = l.id
            LEFT JOIN student_courses sc ON c.id = sc.course_id
            LEFT JOIN students s ON sc.student_id = s.id";

if(!empty($selectedLevel) && in_array($selectedLevel, $levels)) {
    $queryByCourse .= " WHERE l.name = '".$conn->real_escape_string($selectedLevel)."'";
}

$queryByCourse .= " GROUP BY l.name, c.grade, c.parallel
                ORDER BY l.name, c.grade, c.parallel";
$resultByCourse = $conn->query($queryByCourse);
?>

<table class="table table-dark table-hover">
    <thead>
        <tr>
            <th>Nivel</th>
            <th>Curso</th>
            <th class="text-end">Hombres</th>
            <th class="text-end">Mujeres</th>
            <th class="text-end">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php while($curso = $resultByCourse->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($curso['level_name']) ?></td>
            <td><?= htmlspecialchars($curso['course_name']) ?></td>
            <td class="text-end"><?= $curso['hombres'] ?></td>
            <td class="text-end"><?= $curso['mujeres'] ?></td>
            <td class="text-end fw-bold"><?= $curso['total'] ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>