<?php
session_start();
require_once '../config/database.php';
require_once 'report_generator.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Obtener ID del reporte
$id_reporte = $_GET['id'] ?? 0;
if (!$id_reporte) {
    die('ID de reporte no válido');
}

// Cargar datos del reporte
$datos_reporte = cargarReporteGuardado($id_reporte);
if (!$datos_reporte) {
    die('Reporte no encontrado');
}

$reporte = $datos_reporte['reporte'];
$filtros = $datos_reporte['filtros'];
$columnas = $datos_reporte['columnas'];
$tipo_base = $reporte['tipo_base'];

// Construir consulta SQL
$consulta = construirConsultaSQL($filtros, $columnas, $tipo_base);
$resultados = [];

try {
    $conn = (new Database())->connect();
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Error al obtener datos: ' . $e->getMessage());
}

// Mapeo de nombres de columnas
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Datos - Reporte <?php echo $id_reporte; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .debug { background: #f0f0f0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f8f8f8; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Test de Datos - Reporte <?php echo $id_reporte; ?></h1>
    
    <div class="section debug">
        <h2>Información del Reporte</h2>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($reporte['nombre']); ?></p>
        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($reporte['descripcion'] ?? 'N/A'); ?></p>
        <p><strong>Tipo Base:</strong> <?php echo htmlspecialchars($tipo_base); ?></p>
        <p><strong>Fecha Creación:</strong> <?php echo $reporte['fecha_creacion']; ?></p>
    </div>

    <div class="section debug">
        <h2>Filtros Aplicados</h2>
        <pre><?php echo htmlspecialchars(print_r($filtros, true)); ?></pre>
    </div>

    <div class="section debug">
        <h2>Columnas Seleccionadas</h2>
        <ul>
            <?php foreach ($columnas as $columna): ?>
                <li><?php echo htmlspecialchars($columna_disponibles[$columna] ?? $columna); ?> (<?php echo htmlspecialchars($columna); ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="section debug">
        <h2>Consulta SQL</h2>
        <pre><?php echo htmlspecialchars($consulta['sql']); ?></pre>
        <p><strong>Parámetros:</strong> <?php echo htmlspecialchars(print_r($consulta['params'], true)); ?></p>
    </div>

    <div class="section">
        <h2>Resultados (<?php echo count($resultados); ?> registros)</h2>
        <?php if (count($resultados) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columnas as $columna): ?>
                            <th><?php echo htmlspecialchars($columnas_disponibles[$columna] ?? $columna); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $fila): ?>
                        <tr>
                            <?php foreach ($columnas as $columna): ?>
                                <td><?php echo htmlspecialchars($fila[$columna] ?? ''); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron resultados</p>
        <?php endif; ?>
    </div>

    <div class="section debug">
        <h2>Primer Registro (Completo)</h2>
        <pre><?php echo htmlspecialchars(print_r($resultados[0] ?? 'No hay resultados', true)); ?></pre>
    </div>

    <div style="margin-top: 20px;">
        <a href="download_report_new.php?id=<?php echo $id_reporte; ?>" target="_blank">
            <button>Generar PDF</button>
        </a>
        <a href="reportes.php">
            <button>Volver a Reportes</button>
        </a>
    </div>
</body>
</html>
