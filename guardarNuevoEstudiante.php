<?php
// Incluir la conexión a la base de datos
include('conexion.php');

// Verificar si se enviaron los datos desde el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar los datos del formulario
    $nivel = $_POST['nivel']; // "Inicial", "Primario", "Secundario"
    $curso = $_POST['curso']; // 1, 2, 3, ..., 6
    $paralelo = $_POST['paralelo']; // "A", "B", "C"
    $first_name = $_POST['first_name'];
    $last_name_father = $_POST['last_name_father'];
    $last_name_mother = $_POST['last_name_mother'];
    $identity_card = $_POST['identity_card'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $rude_number = $_POST['rude_number'];
    $guardian_first_name = $_POST['guardian_first_name'] ?? null;
    $guardian_last_name = $_POST['guardian_last_name'] ?? null;
    $guardian_identity_card = $_POST['guardian_identity_card'] ?? null;
    $guardian_phone_number = $_POST['guardian_phone_number'] ?? null;
    $guardian_relationship = $_POST['guardian_relationship'] ?? null;

    // Verificar si el CI ya existe
    if (!empty($identity_card)) {
        $query = "SELECT COUNT(*) as count FROM students WHERE identity_card = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $identity_card);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data['count'] > 0) {
            echo "<script>alert('Error: El CI ya está registrado.'); window.history.back();</script>";
            exit();
        }
    }

    // Verificar si el RUDE ya existe
    if (!empty($rude_number)) {
        $query = "SELECT COUNT(*) as count FROM students WHERE rude_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $rude_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data['count'] > 0) {
            echo "<script>alert('Error: El RUDE ya está registrado.'); window.history.back();</script>";
            exit();
        }
    }

    // Insertar datos en la tabla de estudiantes
    $query = "INSERT INTO students (first_name, last_name_father, last_name_mother, identity_card, gender, birth_date, rude_number, guardian_first_name, guardian_last_name, guardian_identity_card, guardian_phone_number, guardian_relationship) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssssssssss",
        $first_name,
        $last_name_father,
        $last_name_mother,
        $identity_card,
        $gender,
        $birth_date,
        $rude_number,
        $guardian_first_name,
        $guardian_last_name,
        $guardian_identity_card,
        $guardian_phone_number,
        $guardian_relationship
    );

    if ($stmt->execute()) {
        // Obtener el ID del estudiante recién creado
        $student_id = $stmt->insert_id;

        // Buscar el curso correspondiente en la tabla courses
        $query_course = "SELECT id FROM courses WHERE level_id = (SELECT id FROM levels WHERE name = ?) AND grade = ? AND parallel = ?";
        $stmt_course_lookup = $conn->prepare($query_course);
        $stmt_course_lookup->bind_param("sis", $nivel, $curso, $paralelo);
        $stmt_course_lookup->execute();
        $result_course = $stmt_course_lookup->get_result();

        if ($result_course->num_rows > 0) {
            $row_course = $result_course->fetch_assoc();
            $course_id = $row_course['id'];

            // Insertar en la tabla intermedia student_courses
            $query_student_course = "INSERT INTO student_courses (student_id, course_id, status) VALUES (?, ?, 'Efectivo - I')";
            $stmt_student_course = $conn->prepare($query_student_course);
            $stmt_student_course->bind_param("ii", $student_id, $course_id);

            if ($stmt_student_course->execute()) {
                // Redirigir a la vista del curso correspondiente después del registro exitoso
                header("Location: vistaGenCurso.php?nivel=$nivel&curso=$curso&paralelo=$paralelo");
                exit();
            } else {
                echo "Error al asignar el curso: " . $stmt_student_course->error;
            }
        } else {
            // Error si no se encuentra el curso
            echo "No se encontró un curso válido para el nivel: $nivel, curso: $curso, paralelo: $paralelo.";
            exit();
        }
    } else {
        // Error al guardar el estudiante
        echo "Error al guardar los datos del estudiante: " . $stmt->error;
    }

    // Cerrar las consultas
    $stmt->close();
    if (isset($stmt_course_lookup)) {
        $stmt_course_lookup->close();
    }
    if (isset($stmt_student_course)) {
        $stmt_student_course->close();
    }
} else {
    echo "Solicitud no válida.";
}

// Cerrar la conexión
$conn->close();
?>
