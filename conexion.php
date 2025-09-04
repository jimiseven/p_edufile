<?php
// Datos de conexión
$host = "localhost";
$usuario = "root";
$contraseña = "";
$base_datos = "bd_edufile3";

// Crear conexión
$conn = new mysqli($host, $usuario, $contraseña, $base_datos);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a utf8
$conn->set_charset("utf8");
?>
