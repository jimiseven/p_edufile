<?php
/**
 * Funciones auxiliares para el sistema de reportes
 * 
 * Este archivo contiene funciones reutilizables para:
 * - Construcción de consultas SQL dinámicas
 * - Validación de datos de filtros
 * - Formateo de resultados
 */

require_once '../config/database.php';

/**
 * Obtiene los niveles educativos únicos de la tabla cursos
 */
function obtenerNivelesEducativos($conn) {
    $stmt = $conn->query("SELECT DISTINCT nivel FROM cursos ORDER BY nivel");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Obtiene los cursos únicos de la tabla cursos
 */
function obtenerCursos($conn) {
    $stmt = $conn->query("SELECT DISTINCT curso FROM cursos ORDER BY curso");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Obtiene los paralelos únicos de la tabla cursos
 */
function obtenerParalelos($conn) {
    $stmt = $conn->query("SELECT DISTINCT paralelo FROM cursos ORDER BY paralelo");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Valida y limpia los datos de filtros recibidos del formulario
 */
function validarFiltros($filtros) {
    $filtros_limpios = [];
    
    if (!is_array($filtros)) {
        return $filtros_limpios;
    }
    
    foreach ($filtros as $campo => $valor) {
        if (is_array($valor)) {
            // Para campos multi-select, eliminar valores vacíos
            $filtros_limpios[$campo] = array_filter($valor, function($v) {
                return !empty($v) && trim($v) !== '';
            });
        } else {
            // Para campos simples, eliminar espacios en blanco
            $filtros_limpios[$campo] = trim($valor);
        }
    }
    
    return $filtros_limpios;
}

/**
 * Construye la cláusula WHERE para filtros de rango (edad, fechas, etc.)
 */
function construirFiltroRango($campo, $min, $max, $params) {
    $where = [];
    
    if (!empty($min) && is_numeric($min)) {
        $where[] = "$campo >= ?";
        $params[] = $min;
    }
    
    if (!empty($max) && is_numeric($max)) {
        $where[] = "$campo <= ?";
        $params[] = $max;
    }
    
    return ['where' => $where, 'params' => $params];
}

/**
 * Formatea una fecha para mostrar en reportes
 */
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (empty($fecha) || $fecha === '0000-00-00') {
        return '';
    }
    
    $date = DateTime::createFromFormat('Y-m-d', $fecha);
    return $date ? $date->format($formato) : $fecha;
}

/**
 * Calcula la edad a partir de la fecha de nacimiento
 */
function calcularEdad($fecha_nacimiento) {
    if (empty($fecha_nacimiento) || $fecha_nacimiento === '0000-00-00') {
        return '';
    }
    
    $date = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
    if (!$date) {
        return '';
    }
    
    $hoy = new DateTime();
    $edad = $hoy->diff($date)->y;
    
    return $edad;
}

/**
 * Obtiene el nombre completo de una persona
 */
function obtenerNombreCompleto($nombres, $apellido_paterno, $apellido_materno = '') {
    $nombre_completo = trim($nombres);
    
    if (!empty($apellido_paterno)) {
        $nombre_completo .= ' ' . trim($apellido_paterno);
    }
    
    if (!empty($apellido_materno)) {
        $nombre_completo .= ' ' . trim($apellido_materno);
    }
    
    return $nombre_completo;
}

/**
 * Genera un alias legible para nombres de columnas de base de datos
 */
function generarAliasColumna($campo) {
    $aliases = [
        'id_estudiante' => 'ID',
        'nombres' => 'Nombres',
        'apellido_paterno' => 'Apellido Paterno',
        'apellido_materno' => 'Apellido Materno',
        'genero' => 'Género',
        'fecha_nacimiento' => 'Fecha Nacimiento',
        'carnet_identidad' => 'Carnet',
        'rude' => 'RUDE',
        'pais' => 'País',
        'provincia_departamento' => 'Departamento',
        'id_curso' => 'ID Curso',
        'id_responsable' => 'ID Responsable',
        'nivel' => 'Nivel',
        'curso' => 'Curso',
        'paralelo' => 'Paralelo'
    ];
    
    return $aliases[$campo] ?? $campo;
}

/**
 * Valida que se hayan seleccionado columnas válidas
 */
function validarColumnasSeleccionadas($columnas, $columnas_permitidas) {
    if (!is_array($columnas) || empty($columnas)) {
        return false;
    }
    
    foreach ($columnas as $columna) {
        if (!in_array($columna, $columnas_permitidas)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Escapa valores para prevenir inyección SQL (cuando no se usan prepared statements)
 */
function escapeValor($conn, $valor) {
    return $conn->quote($valor);
}

/**
 * Genera un nombre de archivo único para reportes descargables
 */
function generarNombreArchivo($base_name, $extension = 'xlsx') {
    $timestamp = date('Y-m-d_H-i-s');
    $random = substr(md5(uniqid()), 0, 8);
    return "{$base_name}_{$timestamp}_{$random}.{$extension}";
}

/**
 * Registra en el log la generación de un reporte
 */
function registrarGeneracionReporte($conn, $id_reporte, $id_usuario, $tipo_accion = 'generar') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO reportes_log (id_reporte, id_usuario, tipo_accion, fecha_accion) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$id_reporte, $id_usuario, $tipo_accion]);
    } catch (Exception $e) {
        // Silenciosamente ignorar errores de logging
        error_log("Error al registrar log de reporte: " . $e->getMessage());
    }
}

/**
 * Obtiene estadísticas de uso de reportes
 */
function obtenerEstadisticasReportes($conn, $id_usuario = null) {
    $sql = "
        SELECT 
            rg.nombre,
            COUNT(rl.id_log) as usos_totales,
            MAX(rl.fecha_accion) as ultimo_uso,
            rl.tipo_accion
        FROM reportes_guardados rg
        LEFT JOIN reportes_log rl ON rg.id_reporte = rl.id_reporte
    ";
    
    $params = [];
    
    if ($id_usuario) {
        $sql .= " WHERE rg.id_personal = ?";
        $params[] = $id_usuario;
    }
    
    $sql .= "
        GROUP BY rg.id_reporte, rg.nombre, rl.tipo_accion
        ORDER BY usos_totales DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene los niveles académicos únicos de la tabla cursos
 */
function obtenerNivelesAcademicos() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT DISTINCT nivel FROM cursos ORDER BY nivel");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtiene los cursos por nivel académico
 */
function obtenerCursosPorNivel($nivel) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id_curso, curso, paralelo FROM cursos WHERE nivel = ? ORDER BY curso, paralelo");
        $stmt->execute([$nivel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtiene los paralelos únicos
 */
function obtenerParalelosUnicos() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT DISTINCT paralelo FROM cursos ORDER BY paralelo");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Verifica el permiso del usuario para reportes
 */
function verificarPermisoReportes($id_usuario) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT r.nombre FROM personal p JOIN roles r ON p.id_rol = r.id_rol WHERE p.id_personal = ?");
        $stmt->execute([$id_usuario]);
        $rol = $stmt->fetch(PDO::FETCH_COLUMN);
        
        return $rol == 'Administrador';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verifica si un usuario tiene permisos para acceder a un reporte
 */
function verificarPermisoReporte($conn, $id_reporte, $id_usuario, $es_admin = false) {
    if ($es_admin) {
        return true; // Los administradores pueden acceder a todos los reportes
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM reportes_guardados 
            WHERE id_reporte = ? AND id_personal = ?
        ");
        $stmt->execute([$id_reporte, $id_usuario]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}
?>
