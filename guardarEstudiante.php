<?php
// guardarEstudiante.php

// Conexión a la base de datos
include('conexion.php');

// Obtener los datos enviados desde el formulario
$first_name = $_POST['first_name'];
$last_name_father = $_POST['last_name_father'];
$last_name_mother = $_POST['last_name_mother'];
$identity_card = $_POST['identity_card'];
$gender = $_POST['gender'];
$birth_date = $_POST['birth_date'];
$rude_number = $_POST['rude_number'];
$guardian_first_name = $_POST['guardian_first_name'];
$guardian_last_name = $_POST['guardian_last_name'];
$guardian_identity_card = $_POST['guardian_identity_card'];
$guardian_phone_number = $_POST['guardian_phone_number'];
$guardian_relationship = $_POST['guardian_relationship'];
$grade = $_POST['grade'];
$parallel = $_POST['parallel'];
$status = $_POST['status'];

// Query para insertar los datos en la base de datos
$query = "INSERT INTO students (first_name, last_name_father, last_name_mother, identity_card, gender, birth_date, rude_number, guardian_first_name, guardian_last_name, guardian_identity_card, guardian_phone_number, guardian_relationship) 
VALUES ('$first_name', '$last_name_father', '$last_name_mother', '$identity_card', '$gender', '$birth_date', '$rude_number', '$guardian_first_name', '$guardian_last_name', '$guardian_identity_card', '$guardian_phone_number', '$guardian_relationship')";

if (mysqli_query($conn, $query)) {
    // Redirigir a vistaGenCurso.php después de guardar
    header("Location: vistaGenCurso.php?grade=$grade&parallel=$parallel");
    exit(); // Asegúrate de detener el script después de redirigir
} else {
    echo "Error: " . $query . "<br>" . mysqli_error($conn);
}

// Cerrar la conexión
mysqli_close($conn);
?>
