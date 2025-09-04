<?php
// Incluir conexión a la base de datos
include 'conexion.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir datos del formulario
    $student_id = $_POST['student_id'] ?? null;
    $estado = $_POST['estado'] ?? null;

    // Definir colores asociados a cada estado
    $estado_colors = [
        'Efectivo - I' => '#28a745',
        'Efectivo - T' => '#28a745',
        'Traslado' => '#007bff',
        'Retirado' => '#17a2b8',
        'No Inscrito' => '#dc3545',
    ];

    // Validar datos
    if (!$student_id || !$estado) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit;
    }

    // Actualizar estado del estudiante en la base de datos
    $query = "UPDATE student_courses SET status = ? WHERE student_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
        exit;
    }

    $stmt->bind_param("si", $estado, $student_id);

    try {
        $stmt->execute();

        // Obtener el color correspondiente al estado
        $color = $estado_colors[$estado] ?? '#6c757d';

        echo json_encode(['success' => true, 'color' => $color]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Cerrar la conexión
$conn->close();
