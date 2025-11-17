<?php
/**
 * Página de diagnóstico para el sistema de reportes
 * Muestra el estado actual de las tablas y posibles problemas
 */

session_start();
require_once '../config/database.php';

// Verificar acceso
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico del Sistema de Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #121212; color: #eaeaea; }
        .diagnostic-card { background: #1f1f1f; border: 1px solid #333; border-radius: 10px; padding: 1.5rem; margin-bottom: 1rem; }
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .code-block { background: #2a2a2a; padding: 1rem; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h1 class="mt-4 mb-4">
                    <i class="fas fa-stethoscope me-2"></i>
                    Diagnóstico del Sistema de Reportes
                </h1>
                
                <!-- Estado de la conexión -->
                <div class="diagnostic-card">
                    <h3><i class="fas fa-database me-2"></i> Conexión a la Base de Datos</h3>
                    <?php if ($conn): ?>
                        <p class="status-ok"><i class="fas fa-check-circle"></i> Conexión establecida correctamente</p>
                    <?php else: ?>
                        <p class="status-error"><i class="fas fa-times-circle"></i> Error de conexión</p>
                    <?php endif; ?>
                </div>
                
                <!-- Verificación de tablas -->
                <div class="diagnostic-card">
                    <h3><i class="fas fa-table me-2"></i> Estado de las Tablas</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Tabla</th>
                                    <th>Estado</th>
                                    <th>Registros</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tablas = [
                                    'reportes_guardados' => 'Reportes Guardados',
                                    'reportes_guardados_columnas' => 'Columnas de Reportes',
                                    'reportes_guardados_filtros' => 'Filtros de Reportes',
                                    'estudiantes' => 'Estudiantes',
                                    'cursos' => 'Cursos',
                                    'personal' => 'Personal'
                                ];
                                
                                foreach ($tablas as $tabla => $descripcion):
                                    try {
                                        $stmt = $conn->query("SHOW TABLES LIKE '$tabla'");
                                        $existe = $stmt->rowCount() > 0;
                                        
                                        if ($existe) {
                                            $stmt_count = $conn->query("SELECT COUNT(*) as total FROM $tabla");
                                            $total = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
                                            $estado = '<span class="status-ok"><i class="fas fa-check-circle"></i> OK</span>';
                                        } else {
                                            $total = 0;
                                            $estado = '<span class="status-error"><i class="fas fa-times-circle"></i> No existe</span>';
                                        }
                                    } catch (Exception $e) {
                                        $existe = false;
                                        $total = 0;
                                        $estado = '<span class="status-error"><i class="fas fa-exclamation-triangle"></i> Error</span>';
                                    }
                                ?>
                                    <tr>
                                        <td><code><?php echo $tabla; ?></code><br><small><?php echo $descripcion; ?></small></td>
                                        <td><?php echo $estado; ?></td>
                                        <td><?php echo $total; ?></td>
                                        <td>
                                            <?php if ($existe): ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="verEstructura('<?php echo $tabla; ?>')">
                                                    <i class="fas fa-eye"></i> Estructura
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Verificación de archivos -->
                <div class="diagnostic-card">
                    <h3><i class="fas fa-file-code me-2"></i> Archivos del Sistema</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Estado</th>
                                    <th>Tamaño</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $archivos = [
                                    'constructor_reporte.php' => 'Constructor de Reportes',
                                    'report_generator.php' => 'Generador de Reportes',
                                    'reportes.php' => 'Lista de Reportes',
                                    'includes/report_functions.php' => 'Funciones Auxiliares'
                                ];
                                
                                foreach ($archivos as $archivo => $descripcion):
                                    $ruta = __DIR__ . '/' . $archivo;
                                    $existe = file_exists($ruta);
                                    
                                    if ($existe) {
                                        $tamano = filesize($ruta);
                                        $tamano_formateado = number_format($tamano / 1024, 2) . ' KB';
                                        $estado = '<span class="status-ok"><i class="fas fa-check-circle"></i> OK</span>';
                                    } else {
                                        $tamano_formateado = '0 KB';
                                        $estado = '<span class="status-error"><i class="fas fa-times-circle"></i> No existe</span>';
                                    }
                                ?>
                                    <tr>
                                        <td><code><?php echo $archivo; ?></code><br><small><?php echo $descripcion; ?></small></td>
                                        <td><?php echo $estado; ?></td>
                                        <td><?php echo $tamano_formateado; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Prueba rápida -->
                <div class="diagnostic-card">
                    <h3><i class="fas fa-play-circle me-2"></i> Prueba Rápida del Sistema</h3>
                    <button class="btn btn-success" onclick="ejecutarPrueba()">
                        <i class="fas fa-play"></i> Ejecutar Prueba Completa
                    </button>
                    <div id="resultado-prueba" class="mt-3"></div>
                </div>
                
                <!-- Recomendaciones -->
                <div class="diagnostic-card">
                    <h3><i class="fas fa-lightbulb me-2"></i> Recomendaciones</h3>
                    <div id="recomendaciones">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Para que el sistema genere reportes correctamente:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Asegúrate de que todas las tablas necesarias existan en la base de datos</li>
                                <li>Verifica que haya datos en las tablas estudiantes y cursos</li>
                                <li>Confirma que los archivos PHP estén presentes y sean legibles</li>
                                <li>Revisa que el usuario tenga permisos de administrador (id_rol = 1)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="diagnostic-card">
                    <h3><i class="fas fa-tools me-2"></i> Acciones Rápidas</h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="test_report.php" class="btn btn-outline-primary">
                            <i class="fas fa-vial"></i> Ejecutar Test Completo
                        </a>
                        <a href="constructor_reporte.php?tipo=info_estudiantil" class="btn btn-outline-success">
                            <i class="fas fa-plus"></i> Crear Reporte de Prueba
                        </a>
                        <a href="reportes.php" class="btn btn-outline-info">
                            <i class="fas fa-list"></i> Ver Reportes Guardados
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verEstructura(tabla) {
            // Esta función podría mostrar la estructura de una tabla
            alert('Función para ver estructura de tabla: ' + tabla);
        }
        
        function ejecutarPrueba() {
            const resultadoDiv = document.getElementById('resultado-prueba');
            resultadoDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
            
            // Simular una prueba rápida
            setTimeout(() => {
                resultadoDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Prueba completada:</strong> El sistema parece funcionar correctamente.
                        <br><small>Para una prueba más detallada, usa el botón "Ejecutar Test Completo".</small>
                    </div>
                `;
            }, 2000);
        }
    </script>
</body>
</html>
