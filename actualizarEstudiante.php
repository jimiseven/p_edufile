<?php
// Incluir conexión a la base de datos
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir datos del formulario y convertir a mayúsculas
    $original_rude = strtoupper($_POST['original_rude']);
    $rude_number = strtoupper($_POST['rude_number']);
    $first_name = strtoupper($_POST['first_name']);
    $last_name_father = isset($_POST['last_name_father']) ? strtoupper($_POST['last_name_father']) : null;
    $last_name_mother = isset($_POST['last_name_mother']) ? strtoupper($_POST['last_name_mother']) : null;
    $identity_card = isset($_POST['identity_card']) ? strtoupper($_POST['identity_card']) : null;
    $gender = strtoupper($_POST['gender']);
    $birth_date = $_POST['birth_date'] ?? null;
    $student_status = $_POST['student_status']; // Nuevo campo para el estado

    // Datos del responsable en mayúsculas
    $guardian_first_name = isset($_POST['guardian_first_name']) ? strtoupper($_POST['guardian_first_name']) : null;
    $guardian_last_name = isset($_POST['guardian_last_name']) ? strtoupper($_POST['guardian_last_name']) : null;
    $guardian_identity_card = isset($_POST['guardian_identity_card']) ? strtoupper($_POST['guardian_identity_card']) : null;
    $guardian_phone_number = isset($_POST['guardian_phone_number']) ? strtoupper($_POST['guardian_phone_number']) : null;
    $guardian_relationship = isset($_POST['guardian_relationship']) ? strtoupper($_POST['guardian_relationship']) : null;

    // Verificar si el RUDE ya existe en otro estudiante
    $queryCheck = "SELECT * FROM students WHERE rude_number = ? AND rude_number != ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("ss", $rude_number, $original_rude);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        header("Location: editarEstudiante.php?student_id=$original_rude&error=El RUDE ya está registrado.");
        exit;
    }

    // Iniciar transacción
    $conn->begin_transaction();
    try {
        // Actualizar datos del estudiante
        $queryStudent = "
            UPDATE students 
            SET 
                rude_number = ?, 
                first_name = ?, 
                last_name_father = ?, 
                last_name_mother = ?, 
                gender = ?, 
                identity_card = ?, 
                birth_date = ?, 
                guardian_first_name = ?, 
                guardian_last_name = ?, 
                guardian_identity_card = ?, 
                guardian_phone_number = ?, 
                guardian_relationship = ? 
            WHERE rude_number = ?
        ";
        $stmtStudent = $conn->prepare($queryStudent);
        $stmtStudent->bind_param(
            "sssssssssssss",
            $rude_number,
            $first_name,
            $last_name_father,
            $last_name_mother,
            $gender,
            $identity_card,
            $birth_date,
            $guardian_first_name,
            $guardian_last_name,
            $guardian_identity_card,
            $guardian_phone_number,
            $guardian_relationship,
            $original_rude
        );
        $stmtStudent->execute();

        // Actualizar estado del estudiante en student_courses
        $queryStatus = "
            UPDATE student_courses
            SET status = ?
            WHERE student_id = (
                SELECT id FROM students WHERE rude_number = ?
            )
        ";
        $stmtStatus = $conn->prepare($queryStatus);
        $stmtStatus->bind_param("ss", $student_status, $rude_number);
        $stmtStatus->execute();

        // Confirmar transacción
        $conn->commit();

        // Redirigir a estudiantes.php con un parámetro de éxito
        header("Location: estudiantes.php?status=updated");
        exit;
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();

        // Redirigir con mensaje de error
        header("Location: editarEstudiante.php?student_id=$original_rude&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Redirigir si se accede al archivo directamente
    header("Location: index.php");
    exit;
}

$conn->close();
?>
