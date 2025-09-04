<?php
include 'conexion.php';

// Verificar si se recibió el ID del estudiante
if (!isset($_GET['student_id']) || !isset($_GET['grade']) || !isset($_GET['parallel'])) {
    die("No se especificó un estudiante o curso.");
}

$student_id = $_GET['student_id'];
$grade = strtoupper($_GET['grade']);
$parallel = strtoupper($_GET['parallel']);

// Obtener datos del estudiante
$query = "SELECT 
            s.first_name, s.last_name_father, s.last_name_mother, s.rude_number, 
            s.identity_card, s.guardian_first_name, s.guardian_last_name, s.guardian_phone_number 
          FROM students s 
          WHERE s.rude_number = ? 
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No se encontró al estudiante.");
}

$student = $result->fetch_assoc();

// Formatear nombre del estudiante y del responsable en mayúsculas
$nombre_estudiante = strtoupper(trim($student['last_name_father'] . ' ' . $student['last_name_mother'] . ' ' . $student['first_name']));
$nombre_responsable = strtoupper(trim($student['guardian_last_name'] . ' ' . $student['guardian_first_name']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volante del Estudiante</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
            text-transform: uppercase; /* Todo el contenido en mayúsculas */
        }
        .volante-container {
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        h1 {
            font-size: 36px;
            text-align: center;
            margin-bottom: 20px;
        }
        p {
            font-size: 30px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="volante-container">
        <h1>U.E. SIMON BOLIVAR</h1>
        <p><strong>NOMBRE:</strong> <?php echo htmlspecialchars($nombre_estudiante); ?></p>
        <p><strong>CURSO:</strong> <?php echo htmlspecialchars($grade); ?></p>
        <p><strong>PARALELO:</strong> <?php echo htmlspecialchars($parallel); ?></p>
        <p><strong>RUDE:</strong> <?php echo htmlspecialchars($student['rude_number']); ?></p>
        <p><strong>CI:</strong> <?php echo htmlspecialchars($student['identity_card']); ?></p>
        <p><strong>RESP:</strong> <?php echo htmlspecialchars($nombre_responsable); ?></p>
        <p><strong>CEL:</strong> <?php echo htmlspecialchars($student['guardian_phone_number']); ?></p>
    </div>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
