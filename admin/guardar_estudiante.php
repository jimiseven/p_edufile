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
        if (!empty($postData['dir_departamento']) || !empty($postData['dir_zona']) || !empty($postData['dir_telefono'])) {
            $sql = "INSERT INTO estudiante_direccion (id_estudiante, departamento, provincia, municipio, localidad, comunidad, zona, numero_vivienda, telefono_casa, celular) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['dir_departamento'] ?? null,
                $postData['dir_provincia'] ?? null,
                $postData['dir_municipio'] ?? null,
                $postData['dir_localidad'] ?? null,
                $postData['dir_comunidad'] ?? null,
                $postData['dir_zona'] ?? null,
                $postData['dir_numero_vivienda'] ?? null,
                $postData['dir_telefono'] ?? null,
                $postData['dir_celular'] ?? null
            ]);
        }

        // Salud
        if (!empty($postData['sal_tiene_seguro']) || !empty($postData['sal_acceso_posta']) || 
            !empty($postData['sal_acceso_centro_salud']) || !empty($postData['sal_acceso_hospital'])) {
            $sql = "INSERT INTO estudiante_salud (id_estudiante, tiene_seguro, acceso_posta, acceso_centro_salud, acceso_hospital) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['sal_tiene_seguro'] ?? null,
                $postData['sal_acceso_posta'] ?? null,
                $postData['sal_acceso_centro_salud'] ?? null,
                $postData['sal_acceso_hospital'] ?? null
            ]);
        }

        // Idioma/Cultura
        if (!empty($postData['idi_idioma']) || !empty($postData['idi_cultura'])) {
            $sql = "INSERT INTO estudiante_idioma_cultura (id_estudiante, idioma, cultura) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['idi_idioma'] ?? null,
                $postData['idi_cultura'] ?? null
            ]);
        }

        // Transporte
        if (!empty($postData['trans_medio']) || !empty($postData['trans_tiempo_llegada'])) {
            $sql = "INSERT INTO estudiante_transporte (id_estudiante, medio, tiempo_llegada) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['trans_medio'] ?? null,
                $postData['trans_tiempo_llegada'] ?? null
            ]);
        }

        // Servicios
        $servicios = [
            'agua_caneria' => $postData['serv_agua_caneria'] ?? 'No',
            'bano' => $postData['serv_bano'] ?? 'No',
            'alcantarillado' => $postData['serv_alcantarillado'] ?? 'No',
            'internet' => $postData['serv_internet'] ?? 'No',
            'energia' => $postData['serv_energia'] ?? 'No',
            'recojo_basura' => $postData['serv_recojo_basura'] ?? 'No',
            'tipo_vivienda' => $postData['serv_tipo_vivienda'] ?? null
        ];
        
        $sql = "INSERT INTO estudiante_servicios (id_estudiante, agua_caneria, bano, alcantarillado, 
                internet, energia, recojo_basura, tipo_vivienda) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $id_estudiante,
            $servicios['agua_caneria'],
            $servicios['bano'],
            $servicios['alcantarillado'],
            $servicios['internet'],
            $servicios['energia'],
            $servicios['recojo_basura'],
            $servicios['tipo_vivienda']
        ]);

        // Actividad Laboral
        if (!empty($postData['lab_trabajo']) && $postData['lab_trabajo'] === 'Si') {
            $meses_trabajo = isset($postData['lab_meses_trabajo']) ? implode(',', $postData['lab_meses_trabajo']) : '';
            $sql = "INSERT INTO estudiante_actividad_laboral (id_estudiante, trabajo, meses_trabajo, actividad, 
                    turno_manana, turno_tarde, turno_noche, frecuencia) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['lab_trabajo'],
                $meses_trabajo,
                $postData['lab_actividad'] ?? null,
                $postData['lab_turno_manana'] ?? 'No',
                $postData['lab_turno_tarde'] ?? 'No',
                $postData['lab_turno_noche'] ?? 'No',
                $postData['lab_frecuencia'] ?? null
            ]);
        }

        // Dificultades
        if (!empty($postData['dif_tiene_dificultad']) && $postData['dif_tiene_dificultad'] === '1') {
            $sql = "INSERT INTO estudiante_dificultades (id_estudiante, tiene_dificultad, auditiva, visual, 
                    intelectual, fisico_motora, psiquica_mental, autista) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['dif_tiene_dificultad'],
                $postData['dif_auditiva'] ?? 'ninguna',
                $postData['dif_visual'] ?? 'ninguna',
                $postData['dif_intelectual'] ?? 'ninguna',
                $postData['dif_fisico_motora'] ?? 'ninguna',
                $postData['dif_psiquica_mental'] ?? 'ninguna',
                $postData['dif_autista'] ?? 'ninguna'
            ]);
        }

        // Abandono
        if (!empty($postData['aba_abandono']) && $postData['aba_abandono'] === 'Si') {
            $sql = "INSERT INTO estudiante_abandono (id_estudiante, abandono, motivo) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $id_estudiante,
                $postData['aba_abandono'],
                $postData['aba_motivo'] ?? null
            ]);
        }

    } catch (PDOException $e) {
        error_log("Error al guardar información secundaria: " . $e->getMessage());
        // No lanzar excepción para no interrumpir el flujo prindcipal
    }
}
?>
