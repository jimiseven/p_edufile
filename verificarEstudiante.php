<?php
include('conexion.php');

$response = ["status" => "success", "message" => ""];

// Verificar CI
if (!empty($_POST['identity_card'])) {
    $identity_card = $_POST['identity_card'];
    $query = "SELECT COUNT(*) as count FROM students WHERE identity_card = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $identity_card);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data['count'] > 0) {
        $response = ["status" => "error", "message" => "El CI ya está registrado."];
    }
}

// Verificar RUDE
if (!empty($_POST['rude_number']) && $response['status'] === "success") {
    $rude_number = $_POST['rude_number'];
    $query = "SELECT COUNT(*) as count FROM students WHERE rude_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $rude_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data['count'] > 0) {
        $response = ["status" => "error", "message" => "El RUDE ya está registrado."];
    }
}

echo json_encode($response);
$conn->close();
?>
