<?php
include 'conexion.php';

if (isset($_GET['level_id'])) {
    $level_id = intval($_GET['level_id']); // Asegurar que es un número
    $query = "SELECT DISTINCT grade FROM courses WHERE level_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $level_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($courses);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro level_id faltante.']);
}
?>
