<?php
/**
 * Generador de Reportes Dinámicos
 * 
 * Este archivo maneja:
 * - Generación de reportes en tiempo real
 * - Guardado de configuraciones de reportes en la BD
 * - Construcción dinámica de consultas SQL
 */

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

/**
 * Construye consulta SQL dinámica basada en filtros y columnas
 */
function construirConsultaSQL($filtros, $columnas, $tipo_base) {
    global $conn;
    
    error_log("=== construirConsultaSQL INICIO ===");
    error_log("Filtros recibidos: " . print_r($filtros, true));
    error_log("Columnas: " . print_r($columnas, true));
    error_log("Tipo Base: " . $tipo_base);
    
    $sql = "SELECT ";
    $where = [];
    $params = [];
    $join_cursos = false;
    
    if ($tipo_base == 'info_estudiantil') {
        // Columnas base para información estudiantil
        if (!empty($columnas)) {
            $select_columns = [];
            foreach ($columnas as $columna) {
                switch($columna) {
                    case 'id_estudiante':
                        $select_columns[] = "e.id_estudiante";
                        break;
                    case 'nombres':
                        $select_columns[] = "e.nombres";
                        break;
                    case 'apellido_paterno':
                        $select_columns[] = "e.apellido_paterno";
                        break;
                    case 'apellido_materno':
                        $select_columns[] = "e.apellido_materno";
                        break;
                    case 'genero':
                        $select_columns[] = "e.genero";
                        break;
                    case 'fecha_nacimiento':
                        $select_columns[] = "e.fecha_nacimiento";
                        break;
                    case 'edad':
                        $select_columns[] = "TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) as edad";
                        break;
                    case 'carnet_identidad':
                        $select_columns[] = "e.carnet_identidad";
                        break;
                    case 'rude':
                        $select_columns[] = "e.rude";
                        break;
                    case 'pais':
                        $select_columns[] = "e.pais";
                        break;
                    case 'provincia_departamento':
                        $select_columns[] = "e.provincia_departamento";
                        break;
                    case 'nivel':
                        $select_columns[] = "c.nivel";
                        $join_cursos = true;
                        break;
                    case 'curso':
                        $select_columns[] = "c.curso";
                        $join_cursos = true;
                        break;
                    case 'paralelo':
                        $select_columns[] = "c.paralelo";
                        $join_cursos = true;
                        break;
                    case 'nombre_completo':
                        $select_columns[] = "CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo";
                        break;
                    default:
                        $select_columns[] = "e.$columna";
                }
            }
            $sql .= implode(", ", $select_columns);
        } else {
            $sql .= "e.nombres, e.apellido_paterno, e.apellido_materno";
        }
        
        $sql .= " FROM estudiantes e";
        
        // Agregar JOIN con cursos si se necesitan columnas de cursos O si hay filtros de cursos
        if ($join_cursos || (!empty($filtros) && (isset($filtros['nivel']) || isset($filtros['curso']) || isset($filtros['paralelo'])))) {
            $sql .= " LEFT JOIN cursos c ON e.id_curso = c.id_curso";
        }
        
        // Construir WHERE basado en filtros
        if (!empty($filtros)) {
            foreach ($filtros as $campo => $valor) {
                if (empty($valor)) {
                    continue;
                }
                
                switch($campo) {
                    case 'nivel':
                        if (is_array($valor) && !empty($valor)) {
                            $placeholders = str_repeat('?,', count($valor) - 1) . '?';
                            $where[] = "c.nivel IN ($placeholders)";
                            $params = array_merge($params, $valor);
                        }
                        break;
                        
                    case 'curso':
                        if (is_array($valor) && !empty($valor)) {
                            $placeholders = str_repeat('?,', count($valor) - 1) . '?';
                            $where[] = "c.curso IN ($placeholders)";
                            $params = array_merge($params, $valor);
                        }
                        break;
                        
                    case 'paralelo':
                        if (is_array($valor) && !empty($valor)) {
                            $placeholders = str_repeat('?,', count($valor) - 1) . '?';
                            $where[] = "c.paralelo IN ($placeholders)";
                            $params = array_merge($params, $valor);
                        }
                        break;
                        
                    case 'genero':
                        if (!empty($valor)) {
                            $where[] = "e.genero = ?";
                            $params[] = $valor;
                        }
                        break;
                        
                    case 'edad_min':
                        if (!empty($valor) && is_numeric($valor)) {
                            $where[] = "TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) >= ?";
                            $params[] = $valor;
                        }
                        break;
                        
                    case 'edad_max':
                        if (!empty($valor) && is_numeric($valor)) {
                            $where[] = "TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) <= ?";
                            $params[] = $valor;
                        }
                        break;
                        
                    case 'pais':
                        if (!empty($valor)) {
                            $where[] = "e.pais = ?";
                            $params[] = $valor;
                        }
                        break;
                        
                    case 'con_carnet':
                        if ($valor == '1') {
                            $where[] = "e.carnet_identidad IS NOT NULL AND e.carnet_identidad != ''";
                        } elseif ($valor == '0') {
                            $where[] = "(e.carnet_identidad IS NULL OR e.carnet_identidad = '')";
                        }
                        break;
                        
                    case 'con_rude':
                        if ($valor == '1') {
                            $where[] = "e.rude IS NOT NULL AND e.rude != ''";
                        } elseif ($valor == '0') {
                            $where[] = "(e.rude IS NULL OR e.rude = '')";
                        }
                        break;
                }
            }
        }
        
    } elseif ($tipo_base == 'info_academica') {
        // Lógica para reportes académicos (se puede expandir después)
        $sql .= "e.nombres, e.apellido_paterno, e.apellido_materno, c.nivel, c.curso, c.paralelo";
        $sql .= " FROM estudiantes e LEFT JOIN cursos c ON e.id_curso = c.id_curso";
        $join_cursos = true;
        
        // Aquí se pueden agregar filtros específicos para información académica
        // como notas, trimestres, etc.
    }
    
    // Agregar cláusula WHERE si hay filtros
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres";
    
    error_log("SQL generado: " . $sql);
    error_log("Parámetros: " . print_r($params, true));
    
    return [
        'sql' => $sql,
        'params' => $params
    ];
}

/**
 * Guarda configuración del reporte en la base de datos
 */
function guardarReporte($nombre, $tipo_base, $descripcion, $filtros, $columnas) {
    global $conn;
    
    error_log("=== guardarReporte INICIO ===");
    error_log("Nombre: " . $nombre);
    error_log("Tipo Base: " . $tipo_base);
    error_log("Descripción: " . $descripcion);
    error_log("Filtros: " . print_r($filtros, true));
    error_log("Columnas: " . print_r($columnas, true));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO'));
    
    try {
        // Iniciar transacción
        $conn->beginTransaction();
        error_log("Transacción iniciada");
        
        // Insertar en reportes_guardados
        $stmt = $conn->prepare("
            INSERT INTO reportes_guardados (nombre, tipo_base, id_personal) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$nombre, $tipo_base, $_SESSION['user_id']]);
        $id_reporte = $conn->lastInsertId();
        error_log("Reporte principal insertado, ID: " . $id_reporte);
        
        // Insertar filtros
        if (!empty($filtros)) {
            error_log("Insertando filtros...");
            $stmt_filtro = $conn->prepare("
                INSERT INTO reportes_guardados_filtros (id_reporte, campo, operador, valor1, valor2) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($filtros as $campo => $valor) {
                if (empty($valor)) continue;
                
                if (is_array($valor)) {
                    // Para valores múltiples (multi-select)
                    // Guardar como un solo valor separado por comas
                    $valor_como_string = implode(',', $valor);
                    $stmt_filtro->execute([$id_reporte, $campo, 'in', $valor_como_string, null]);
                    error_log("Filtro insertado - Campo: $campo, Operador: in, Valor: $valor_como_string");
                } else {
                    // Para valores simples
                    $stmt_filtro->execute([$id_reporte, $campo, '=', $valor, null]);
                    error_log("Filtro insertado - Campo: $campo, Operador: =, Valor: $valor");
                }
            }
        } else {
            error_log("No hay filtros para insertar");
        }
        
        // Insertar columnas
        if (!empty($columnas)) {
            error_log("Insertando columnas...");
            $stmt_columna = $conn->prepare("
                INSERT INTO reportes_guardados_columnas (id_reporte, campo, alias_mostrar, orden) 
                VALUES (?, ?, ?, ?)
            ");
            
            $orden = 1;
            $columnas_disponibles = [
                'id_estudiante' => 'ID Estudiante',
                'nombres' => 'Nombres',
                'apellido_paterno' => 'Apellido Paterno',
                'apellido_materno' => 'Apellido Materno',
                'genero' => 'Género',
                'fecha_nacimiento' => 'Fecha de Nacimiento',
                'edad' => 'Edad',
                'carnet_identidad' => 'Carnet de Identidad',
                'rude' => 'RUDE',
                'pais' => 'País',
                'provincia_departamento' => 'Provincia/Departamento',
                'nivel' => 'Nivel',
                'curso' => 'Curso',
                'paralelo' => 'Paralelo',
                'nombre_completo' => 'Nombre Completo'
            ];
            
            foreach ($columnas as $columna) {
                $alias = $columnas_disponibles[$columna] ?? $columna;
                $stmt_columna->execute([$id_reporte, $columna, $alias, $orden]);
                error_log("Columna insertada - Campo: $columna, Alias: $alias, Orden: $orden");
                $orden++;
            }
        } else {
            error_log("No hay columnas para insertar");
        }
        
        // Confirmar transacción
        $conn->commit();
        error_log("Transacción confirmada");
        
        return ['success' => true, 'id_reporte' => $id_reporte];
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        error_log("ERROR: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Carga un reporte guardado desde la base de datos
 */
function cargarReporteGuardado($id_reporte) {
    global $conn;
    
    try {
        // Obtener información básica del reporte
        $stmt = $conn->prepare("
            SELECT rg.*, p.nombres, p.apellidos 
            FROM reportes_guardados rg 
            LEFT JOIN personal p ON rg.id_personal = p.id_personal 
            WHERE rg.id_reporte = ?
        ");
        $stmt->execute([$id_reporte]);
        $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reporte) {
            return null;
        }
        
        // Obtener filtros
        $stmt = $conn->prepare("
            SELECT campo, operador, valor1, valor2 
            FROM reportes_guardados_filtros 
            WHERE id_reporte = ?
        ");
        $stmt->execute([$id_reporte]);
        $filtros_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar filtros
        $filtros = [];
        foreach ($filtros_db as $filtro) {
            if ($filtro['operador'] == 'in') {
                // Convertir string separado por comas a array
                $filtros[$filtro['campo']] = explode(',', $filtro['valor1']);
            } else {
                $filtros[$filtro['campo']] = $filtro['valor1'];
            }
        }
        
        // Obtener columnas
        $stmt = $conn->prepare("
            SELECT campo, alias_mostrar, orden 
            FROM reportes_guardados_columnas 
            WHERE id_reporte = ? 
            ORDER BY orden
        ");
        $stmt->execute([$id_reporte]);
        $columnas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Extraer solo los nombres de campos
        $columnas = [];
        foreach ($columnas_db as $columna) {
            $columnas[] = $columna['campo'];
        }
        
        return [
            'reporte' => $reporte,
            'filtros' => $filtros,
            'columnas' => $columnas
        ];
        
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Elimina un reporte guardado de la base de datos
 */
function eliminarReporte($id_reporte) {
    global $conn;
    
    try {
        // Iniciar transacción
        $conn->beginTransaction();
        
        // Eliminar filtros
        $stmt = $conn->prepare("DELETE FROM reportes_guardados_filtros WHERE id_reporte = ?");
        $stmt->execute([$id_reporte]);
        
        // Eliminar columnas
        $stmt = $conn->prepare("DELETE FROM reportes_guardados_columnas WHERE id_reporte = ?");
        $stmt->execute([$id_reporte]);
        
        // Eliminar reporte principal
        $stmt = $conn->prepare("DELETE FROM reportes_guardados WHERE id_reporte = ?");
        $stmt->execute([$id_reporte]);
        
        // Confirmar transacción
        $conn->commit();
        
        return ['success' => true];
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Genera y muestra el reporte en una tabla HTML
 */
function generarReporteHTML($filtros, $columnas, $tipo_base) {
    global $conn;
    
    $consulta = construirConsultaSQL($filtros, $columnas, $tipo_base);
    
    try {
        $stmt = $conn->prepare($consulta['sql']);
        $stmt->execute($consulta['params']);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($resultados)) {
            echo '<div class="alert alert-info">';
            echo '<i class="fas fa-info-circle me-2"></i>';
            echo 'No se encontraron resultados con los filtros seleccionados.';
            echo '</div>';
            return;
        }
        
        // Generar tabla HTML
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        
        // Encabezados
        echo '<thead><tr>';
        
        $columnas_disponibles = [
            'id_estudiante' => 'ID Estudiante',
            'nombres' => 'Nombres',
            'apellido_paterno' => 'Apellido Paterno',
            'apellido_materno' => 'Apellido Materno',
            'genero' => 'Género',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'edad' => 'Edad',
            'carnet_identidad' => 'Carnet de Identidad',
            'rude' => 'RUDE',
            'pais' => 'País',
            'provincia_departamento' => 'Provincia/Departamento',
            'nivel' => 'Nivel',
            'curso' => 'Curso',
            'paralelo' => 'Paralelo',
            'nombre_completo' => 'Nombre Completo'
        ];
        
        if (!empty($columnas)) {
            foreach ($columnas as $columna) {
                echo '<th>' . htmlspecialchars($columnas_disponibles[$columna] ?? $columna) . '</th>';
            }
        } else {
            echo '<th>Nombres</th><th>Apellido Paterno</th><th>Apellido Materno</th>';
        }
        
        echo '</tr></thead>';
        
        // Datos
        echo '<tbody>';
        foreach ($resultados as $fila) {
            echo '<tr>';
            
            if (!empty($columnas)) {
                foreach ($columnas as $columna) {
                    $valor = $fila[$columna] ?? '';
                    if ($columna == 'fecha_nacimiento' && $valor) {
                        $valor = date('d/m/Y', strtotime($valor));
                    }
                    echo '<td>' . htmlspecialchars($valor) . '</td>';
                }
            } else {
                echo '<td>' . htmlspecialchars($fila['nombres'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($fila['apellido_paterno'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($fila['apellido_materno'] ?? '') . '</td>';
            }
            
            echo '</tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
        echo '</div>';
        
        // Mostrar resumen
        echo '<div class="mt-3 text-muted">';
        echo '<i class="fas fa-chart-bar me-2"></i>';
        echo 'Total de registros: ' . count($resultados);
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo 'Error al generar el reporte: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

// Procesar acciones del formulario - ELIMINADO para evitar duplicación
// El procesamiento ahora se hace en constructor_reporte.php
?>
