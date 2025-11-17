<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Error: No hay sesión activa</h1>";
    echo "<a href='../index.php'>Iniciar sesión</a>";
    exit();
}

require_once '../config/database.php';
require_once 'report_generator.php';

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir datos
    $accion = $_POST['accion'] ?? '';
    $nombre_reporte = $_POST['nombre_reporte'] ?? 'Reporte de prueba';
    $tipo_base = $_POST['tipo_base'] ?? 'info_estudiantil';
    
    // Columnas seleccionadas (simuladas si no vienen)
    $columnas = $_POST['columnas'] ?? ['nombres', 'apellido_paterno', 'apellido_materno'];
    
    // Filtros (vacíos para prueba)
    $filtros = [];
    
    echo "<h1>Procesando Guardado de Reporte</h1>";
    echo "<h2>Datos recibidos:</h2>";
    echo "<pre>";
    echo "Acción: $accion\n";
    echo "Nombre: $nombre_reporte\n";
    echo "Tipo: $tipo_base\n";
    echo "Columnas: " . print_r($columnas, true) . "\n";
    echo "</pre>";
    
    if ($accion == 'guardar') {
        $resultado = guardarReporte($nombre_reporte, $tipo_base, '', $filtros, $columnas);
        
        if ($resultado['success']) {
            echo "<div style='color: green; font-size: 20px;'>✅ Reporte guardado con ID: " . $resultado['id_reporte'] . "</div>";
            echo "<br><a href='reportes.php'>Ver todos los reportes</a>";
        } else {
            echo "<div style='color: red; font-size: 20px;'>❌ Error al guardar: " . $resultado['error'] . "</div>";
        }
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Guardar Reporte</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <h1>Test Simple de Guardado</h1>
            
            <form method="post" class="border p-4">
                <input type="hidden" name="tipo_base" value="info_estudiantil">
                
                <div class="mb-3">
                    <label class="form-label">Nombre del Reporte:</label>
                    <input type="text" name="nombre_reporte" class="form-control" 
                           value="Reporte Test <?php echo date('H:i:s'); ?>" required>
                </div>
                
                <div class="mb-3">
                    <h5>Columnas (seleccionadas automáticamente):</h5>
                    <div class="form-check">
                        <input type="checkbox" name="columnas[]" value="nombres" checked class="form-check-input">
                        <label class="form-check-label">Nombres</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="columnas[]" value="apellido_paterno" checked class="form-check-input">
                        <label class="form-check-label">Apellido Paterno</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="columnas[]" value="apellido_materno" checked class="form-check-input">
                        <label class="form-check-label">Apellido Materno</label>
                    </div>
                </div>
                
                <button type="submit" name="accion" value="guardar" class="btn btn-primary">
                    Guardar Reporte
                </button>
            </form>
            
            <hr>
            
            <h3>Reportes Existentes:</h3>
            <?php
            $stmt = $conn->query("SELECT rg.*, p.nombres, p.apellidos 
                                 FROM reportes_guardados rg 
                                 LEFT JOIN personal p ON rg.id_personal = p.id_personal 
                                 ORDER BY rg.fecha_creacion DESC LIMIT 5");
            $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($reportes) {
                echo "<table class='table'>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Creador</th><th>Fecha</th></tr>";
                foreach ($reportes as $r) {
                    echo "<tr>";
                    echo "<td>" . $r['id_reporte'] . "</td>";
                    echo "<td>" . htmlspecialchars($r['nombre']) . "</td>";
                    echo "<td>" . htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) . "</td>";
                    echo "<td>" . $r['fecha_creacion'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No hay reportes guardados</p>";
            }
            ?>
        </div>
    </body>
    </html>
    <?php
}
?>
