<?php
header('Content-Type: application/json');

include 'conexion.php';

$query = "
    SELECT
        UPPER(CONCAT(s.last_name_father, ' ', s.last_name_mother, ' ', s.first_name)) AS nombre_completo,
        l.name AS nivel,
        c.grade,
        c.parallel
    FROM students s
    INNER JOIN student_courses sc ON s.id = sc.student_id
    INNER JOIN courses c ON sc.course_id = c.id
    INNER JOIN levels l ON c.level_id = l.id
    WHERE sc.status = 'Efectivo - I'  -- Filter for 'Efectivo - I' status
    ORDER BY l.name ASC, c.grade ASC, c.parallel ASC, s.last_name_father ASC, s.last_name_mother ASC, s.first_name ASC
";

$result = $conn->query($query);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courseKey = $row['nivel'] . ' ' . $row['grade'] . ' ' . $row['parallel'];
        $data[$courseKey][] = $row;
    }
}

echo json_encode($data);

$conn->close();
?>
