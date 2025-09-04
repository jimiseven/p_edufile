<?php
include 'conexion.php';

if (isset($_GET['course_grade']) && isset($_GET['level_id'])) {
    $course_grade = intval($_GET['course_grade']);
    $level_id = intval($_GET['level_id']);
    $query = "SELECT DISTINCT parallel FROM courses WHERE grade = ? AND level_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $course_grade, $level_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $parallels = [];
    while ($row = $result->fetch_assoc()) {
        $parallels[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($parallels);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros course_grade o level_id faltantes.']);
}
?>
