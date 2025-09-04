<?php
// Incluir conexión a la base de datos
include 'conexion.php';

// Verificar si se recibió el ID del estudiante
if (!isset($_GET['student_id'])) {
    header("Location: estudiantes.php?error=No se especificó el estudiante a eliminar");
    exit;
}

$student_id = $_GET['student_id'];

try {
    // Iniciar una transacción
    $conn->begin_transaction();

    // Obtener el ID del estudiante basado en el rude_number
    $query = "SELECT id FROM students WHERE rude_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("El estudiante no existe en la base de datos.");
    }

    $student_db_id = $result->fetch_assoc()['id'];

    // Eliminar relaciones en la tabla `student_courses`
    $query = "DELETE FROM student_courses WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();

    // Eliminar estudiante de la tabla `students`
    $query = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();

    // Confirmar los cambios
    $conn->commit();

    // Redirigir con éxito con un indicador de éxito
    header("Location: estudiantes.php?status=deleted");
    exit;
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollback();

    // Redirigir con mensaje de error
    header("Location: estudiantes.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>
