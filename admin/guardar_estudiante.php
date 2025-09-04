<?php
session_start();
require_once '../config/database.php';

// Solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos del estudiante
    $nombres = trim($_POST['nombres'] ?? '');
    $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
    $apellido_materno = trim($_POST['apellido_materno'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $rude = trim($_POST['rude'] ?? '');
    $carnet_identidad = trim($_POST['ci'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? null);
    $pais = trim($_POST['pais'] ?? '');
    $provincia_departamento = trim($_POST['provincia_departamento'] ?? '');
    $id_curso = intval($_POST['curso'] ?? 0);

    // Datos del responsable
    $resp_nombres = trim($_POST['resp_nombres'] ?? '');
    $resp_apellido_paterno = trim($_POST['resp_apellido_paterno'] ?? '');
    $resp_apellido_materno = trim($_POST['resp_apellido_materno'] ?? '');
    $resp_ci = trim($_POST['resp_ci'] ?? '');
    $resp_fecha_nacimiento = trim($_POST['resp_fecha_nacimiento'] ?? null);
    $resp_parentesco = trim($_POST['resp_parentesco'] ?? '');
    $resp_celular = trim($_POST['resp_celular'] ?? '');
    $resp_grado_instruccion = trim($_POST['resp_grado_instruccion'] ?? '');
    $resp_idioma_frecuente = trim($_POST['resp_idioma_frecuente'] ?? '');

    // Validaciones básicas del estudiante
    if (
        $nombres === '' ||
        $apellido_paterno === '' ||
        $rude === '' ||
        $carnet_identidad === '' ||
        $fecha_nacimiento === '' ||
        $genero === '' ||
        $pais === '' ||
        $provincia_departamento === '' ||
        !$id_curso
    ) {
        $_SESSION['error'] = "Por favor, complete todos los campos obligatorios del estudiante.";
        header('Location: estudiantes.php');
        exit();
    }

    // Validaciones básicas del responsable
    if (
        $resp_nombres === '' ||
        $resp_apellido_paterno === '' ||
        $resp_ci === '' ||
        $resp_parentesco === ''
    ) {
        $_SESSION['error'] = "Por favor, complete todos los campos obligatorios del responsable.";
        header('Location: estudiantes.php');
        exit();
    }

    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Iniciar transacción
        $conn->beginTransaction();

        // Primero insertar el responsable
        $sqlResponsable = "INSERT INTO responsables 
            (nombres, apellido_paterno, apellido_materno, carnet_identidad, fecha_nacimiento, 
             grado_instruccion, idioma_frecuente, parentesco, celular)
            VALUES
            (:nombres, :apellido_paterno, :apellido_materno, :carnet_identidad, :fecha_nacimiento, 
             :grado_instruccion, :idioma_frecuente, :parentesco, :celular)";
        
        $stmtResponsable = $conn->prepare($sqlResponsable);
        $stmtResponsable->bindParam(':nombres', $resp_nombres);
        $stmtResponsable->bindParam(':apellido_paterno', $resp_apellido_paterno);
        $stmtResponsable->bindParam(':apellido_materno', $resp_apellido_materno);
        $stmtResponsable->bindParam(':carnet_identidad', $resp_ci);
        $stmtResponsable->bindParam(':fecha_nacimiento', $resp_fecha_nacimiento);
        $stmtResponsable->bindParam(':grado_instruccion', $resp_grado_instruccion);
        $stmtResponsable->bindParam(':idioma_frecuente', $resp_idioma_frecuente);
        $stmtResponsable->bindParam(':parentesco', $resp_parentesco);
        $stmtResponsable->bindParam(':celular', $resp_celular);
        
        $stmtResponsable->execute();
        $id_responsable = $conn->lastInsertId();

        // Luego insertar el estudiante con referencia al responsable
        $sqlEstudiante = "INSERT INTO estudiantes 
            (nombres, apellido_paterno, apellido_materno, genero, rude, carnet_identidad, fecha_nacimiento, pais, provincia_departamento, id_curso, id_responsable)
            VALUES
            (:nombres, :apellido_paterno, :apellido_materno, :genero, :rude, :carnet_identidad, :fecha_nacimiento, :pais, :provincia_departamento, :id_curso, :id_responsable)";
        
        $stmtEstudiante = $conn->prepare($sqlEstudiante);
        $stmtEstudiante->bindParam(':nombres', $nombres);
        $stmtEstudiante->bindParam(':apellido_paterno', $apellido_paterno);
        $stmtEstudiante->bindParam(':apellido_materno', $apellido_materno);
        $stmtEstudiante->bindParam(':genero', $genero);
        $stmtEstudiante->bindParam(':rude', $rude);
        $stmtEstudiante->bindParam(':carnet_identidad', $carnet_identidad);
        $stmtEstudiante->bindParam(':fecha_nacimiento', $fecha_nacimiento);
        $stmtEstudiante->bindParam(':pais', $pais);
        $stmtEstudiante->bindParam(':provincia_departamento', $provincia_departamento);
        $stmtEstudiante->bindParam(':id_curso', $id_curso, PDO::PARAM_INT);
        $stmtEstudiante->bindParam(':id_responsable', $id_responsable, PDO::PARAM_INT);
        
        $stmtEstudiante->execute();

        // Confirmar transacción
        $conn->commit();

        $_SESSION['success'] = "Estudiante y responsable registrados correctamente.";
        header('Location: estudiantes.php');
        exit();
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        $_SESSION['error'] = "Error al guardar el estudiante y responsable: " . $e->getMessage();
        header('Location: estudiantes.php');
        exit();
    }
} else {
    header('Location: estudiantes.php');
    exit();
}
?>
