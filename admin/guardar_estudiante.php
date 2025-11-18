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

        // Guardar información secundaria (opcional)
        guardarInformacionSecundaria($conn, $id_estudiante, $_POST);

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

// Función para guardar información secundaria
function guardarInformacionSecundaria($conn, $id_estudiante, $postData) {
    try {
        // Dirección
        if (!empty($postData['dir_direccion']) || !empty($postData['dir_zona'])) {
            $sql = "INSERT INTO estudiante_direccion (id_estudiante, direccion, zona, telefono_casa, celular, email, referencia) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['dir_direccion'] ?? null,
                $postData['dir_zona'] ?? null,
                $postData['dir_telefono'] ?? null,
                $postData['dir_celular'] ?? null,
                $postData['dir_email'] ?? null,
                $postData['dir_referencia'] ?? null
            ]);
        }

        // Salud
        if (!empty($postData['sal_tipo_sangre']) || !empty($postData['sal_discapacidad']) || 
            !empty($postData['sal_alergias']) || !empty($postData['sal_medicamentos'])) {
            $sql = "INSERT INTO estudiante_salud (id_estudiante, tipo_sangre, alergias, medicamentos, enfermedades_cronicas, 
                    discapacidad, tipo_discapacidad, observaciones_medicas) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['sal_tipo_sangre'] ?? null,
                $postData['sal_alergias'] ?? null,
                $postData['sal_medicamentos'] ?? null,
                $postData['sal_enfermedades'] ?? null,
                $postData['sal_discapacidad'] ?? 'No',
                $postData['sal_tipo_discapacidad'] ?? null,
                $postData['sal_observaciones'] ?? null
            ]);
        }

        // Idioma/Cultura
        if (!empty($postData['idi_idioma_materno']) || !empty($postData['idi_nivel_espanol']) || 
            !empty($postData['idi_idiomas_adicionales'])) {
            $sql = "INSERT INTO estudiante_idioma_cultura (id_estudiante, idioma_materno, idiomas_adicionales, 
                    nivel_espanol, pueblo_indigena, tradiciones, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['idi_idioma_materno'] ?? null,
                $postData['idi_idiomas_adicionales'] ?? null,
                $postData['idi_nivel_espanol'] ?? null,
                $postData['idi_pueblo_indigena'] ?? null,
                $postData['idi_tradiciones'] ?? null,
                $postData['idi_observaciones'] ?? null
            ]);
        }

        // Transporte
        if (!empty($postData['trans_tipo'])) {
            $sql = "INSERT INTO estudiante_transporte (id_estudiante, tipo_transporte, tiempo_viaje, distancia, costo_mensual, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['trans_tipo'],
                $postData['trans_tiempo'] ?? null,
                $postData['trans_distancia'] ?? null,
                $postData['trans_costo'] ?? null,
                $postData['trans_observaciones'] ?? null
            ]);
        }

        // Servicios
        $servicios = [
            'comedor' => $postData['serv_comedor'] ?? 'No',
            'transporte_escolar' => $postData['serv_transporte'] ?? 'No',
            'biblioteca' => $postData['serv_biblioteca'] ?? 'No',
            'laboratorio' => $postData['serv_laboratorio'] ?? 'No',
            'deportes' => $postData['serv_deportes'] ?? 'No',
            'arte_cultura' => $postData['serv_arte'] ?? 'No'
        ];
        
        $sql = "INSERT INTO estudiante_servicios (id_estudiante, comedor, transporte_escolar, biblioteca, 
                laboratorio, deportes, arte_cultura, observaciones) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $id_estudiante,
            $servicios['comedor'],
            $servicios['transporte_escolar'],
            $servicios['biblioteca'],
            $servicios['laboratorio'],
            $servicios['deportes'],
            $servicios['arte_cultura'],
            $postData['serv_observaciones'] ?? null
        ]);

        // Actividad Laboral
        if (!empty($postData['lab_trabaja']) && $postData['lab_trabaja'] === 'Si') {
            $sql = "INSERT INTO estudiante_actividad_laboral (id_estudiante, trabaja, lugar_trabajo, cargo, 
                    horario_trabajo, ingreso_mensual, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['lab_trabaja'],
                $postData['lab_lugar'] ?? null,
                $postData['lab_cargo'] ?? null,
                $postData['lab_horario'] ?? null,
                $postData['lab_ingreso'] ?? null,
                $postData['lab_observaciones'] ?? null
            ]);
        }

        // Dificultades
        if (!empty($postData['dif_tipo']) || !empty($postData['dif_descripcion'])) {
            $sql = "INSERT INTO estudiante_dificultades (id_estudiante, tipo_dificultad, descripcion, 
                    diagnostico, tratamiento, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['dif_tipo'] ?? null,
                $postData['dif_descripcion'] ?? null,
                $postData['dif_diagnostico'] ?? null,
                $postData['dif_tratamiento'] ?? null,
                $postData['dif_observaciones'] ?? null
            ]);
        }

        // Abandono
        if (!empty($postData['aba_fecha']) || !empty($postData['aba_motivo'])) {
            $sql = "INSERT INTO estudiante_abandono (id_estudiante, fecha_abandono, motivo_abandono, 
                    observaciones, fecha_regreso) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['aba_fecha'] ?? null,
                $postData['aba_motivo'] ?? null,
                $postData['aba_observaciones'] ?? null,
                $postData['aba_fecha_regreso'] ?? null
            ]);
        }

    } catch (PDOException $e) {
        error_log("Error al guardar información secundaria: " . $e->getMessage());
        // No lanzar excepción para no interrumpir el flujo principal
    }
}
?>
