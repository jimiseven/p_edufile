<?php
session_start();
require_once '../config/database.php';
require_once 'includes/report_functions.php';
require_once 'report_generator.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$id_reporte = $_GET['id'] ?? 0;

if (!$id_reporte) {
    header('Location: reportes.php');
    exit();
}

// Cargar reporte guardado
$datos_reporte = cargarReporteGuardado($id_reporte);

if (!$datos_reporte) {
    $_SESSION['mensaje'] = 'Reporte no encontrado.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: reportes.php');
    exit();
}

$reporte = $datos_reporte['reporte'];
$filtros = $datos_reporte['filtros'];
$columnas = $datos_reporte['columnas'];
$tipo_base = $reporte['tipo_base'];

// Obtener los datos del reporte
$consulta = construirConsultaSQL($filtros, $columnas, $tipo_base);
$resultados = [];

try {
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Funciones auxiliares
function obtenerNombreCampoPDF($campo) {
    $nombres = [
        'nivel' => 'Nivel',
        'curso' => 'Curso', 
        'paralelo' => 'Paralelo',
        'genero' => 'Género',
        'edad_min' => 'Edad Mínima',
        'edad_max' => 'Edad Máxima',
        'pais' => 'País',
        'con_carnet' => 'Con Carnet',
        'con_rude' => 'Con RUDE'
    ];
    return $nombres[$campo] ?? ucfirst($campo);
}

function obtenerValorMostrarPDF($valor) {
    if (is_array($valor)) {
        return implode(', ', $valor);
    }
    $valores_especiales = ['1' => 'Sí', '0' => 'No'];
    return $valores_especiales[$valor] ?? $valor;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($reporte['nombre']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 15px;
            color: #333;
            line-height: 1.2;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 9px;
        }
        
        .report-info {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        
        .report-info h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .report-info p {
            margin: 2px 0;
            font-size: 9px;
        }
        
        .filters-section {
            margin-bottom: 15px;
            padding: 10px;
            background: #f1f3f4;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        
        .filters-section h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .filter-tag {
            display: inline-block;
            padding: 2px 5px;
            background: #e9ecef;
            border-radius: 2px;
            margin: 1px;
            font-size: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .no-data {
            text-align: center;
            padding: 15px;
            color: #666;
            font-style: italic;
            font-size: 10px;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            font-size: 16px;
            color: #666;
        }
        
        .btn-generate {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 20px auto;
            display: block;
        }
        
        .btn-generate:hover {
            background: #218838;
        }
        
        /* Optimización para impresión */
        @media print {
            body {
                margin: 10px;
                font-size: 8px;
            }
            
            .header h1 {
                font-size: 14px;
            }
            
            .header p {
                font-size: 7px;
            }
            
            .report-info, .filters-section {
                padding: 5px;
                margin-bottom: 10px;
            }
            
            .report-info h3, .filters-section h3 {
                font-size: 10px;
            }
            
            .filter-tag {
                font-size: 7px;
                padding: 1px 3px;
            }
            
            table {
                font-size: 7px;
            }
            
            th, td {
                padding: 2px;
            }
            
            th {
                font-size: 6px;
            }
            
            .footer {
                font-size: 6px;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <p><i class="fas fa-spinner fa-spin"></i> Generando PDF del reporte...</p>
        <p>Por favor espere un momento...</p>
    </div>
    
    <div id="content" style="display: none;">
        <div class="header">
            <h1><?php echo htmlspecialchars($reporte['nombre']); ?></h1>
            <p>Generado el: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Tipo: <?php echo $tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'; ?></p>
        </div>
        
        <div class="report-info">
            <h3>Información del Reporte</h3>
            <p><strong>Tipo de Reporte:</strong> <?php echo $tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'; ?></p>
            <p><strong>Columnas Seleccionadas:</strong> <?php echo count($columnas); ?></p>
            <p><strong>Filtros Aplicados:</strong> <?php echo count($filtros); ?></p>
            <p><strong>ID del Reporte:</strong> #<?php echo $id_reporte; ?></p>
        </div>
        
        <?php if (!empty($filtros)): ?>
        <div class="filters-section">
            <h3>Filtros Aplicados</h3>
            <?php foreach ($filtros as $campo => $valor): ?>
                <span class="filter-tag">
                    <strong><?php echo obtenerNombreCampoPDF($campo); ?>:</strong> <?php echo htmlspecialchars(obtenerValorMostrarPDF($valor)); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (isset($error)): ?>
                <div class="no-data">Error al generar el reporte: <?php echo htmlspecialchars($error); ?></div>
            <?php elseif (empty($resultados)): ?>
                <div class="no-data">No se encontraron resultados con los filtros seleccionados.</div>
            <?php else: ?>
                <?php
                // Mapeo de nombres de columnas para mostrar
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
                
                echo '<table>';
                echo '<thead><tr>';
                
                foreach ($columnas as $columna) {
                    echo '<th>' . htmlspecialchars($columnas_disponibles[$columna] ?? ucfirst($columna)) . '</th>';
                }
                
                echo '</tr></thead><tbody>';
                
                foreach ($resultados as $fila) {
                    echo '<tr>';
                    foreach ($columnas as $columna) {
                        $valor = $fila[$columna] ?? '';
                        if ($columna === 'fecha_nacimiento' && $valor) {
                            $valor = date('d/m/Y', strtotime($valor));
                        }
                        echo '<td>' . htmlspecialchars($valor) . '</td>';
                    }
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                ?>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Reporte generado por el Sistema de Gestión Educativa</p>
            <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <script>
        // Esperar a que la página cargue completamente
        window.addEventListener('load', function() {
            // Generar PDF automáticamente
            setTimeout(function() {
                generarPDF();
            }, 500);
        });
        
        function generarPDF() {
            const { jsPDF } = window.jspdf;
            
            // Crear documento PDF con formato letter (hoja carta)
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'pt',
                format: 'letter'
            });
            
            // Datos del reporte
            const reporteData = {
                nombre: <?php echo json_encode(htmlspecialchars_decode($reporte['nombre'])); ?>,
                descripcion: <?php echo json_encode(htmlspecialchars_decode($reporte['descripcion'] ?? '')); ?>,
                tipo: <?php echo json_encode($tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'); ?>,
                fecha: <?php echo json_encode(date('d/m/Y H:i:s')); ?>,
                id: <?php echo $id_reporte; ?>,
                columnas: <?php echo json_encode($columnas); ?>,
                resultados: <?php echo json_encode($resultados); ?>
            };
            
            // Mapeo de nombres de columnas
            const columnasDisponibles = {
                'id_estudiante': 'ID Estudiante',
                'nombres': 'Nombres',
                'apellido_paterno': 'Apellido Paterno',
                'apellido_materno': 'Apellido Materno',
                'genero': 'Género',
                'fecha_nacimiento': 'Fecha de Nacimiento',
                'edad': 'Edad',
                'carnet_identidad': 'Carnet de Identidad',
                'rude': 'RUDE',
                'pais': 'País',
                'provincia_departamento': 'Provincia/Departamento',
                'nivel': 'Nivel',
                'curso': 'Curso',
                'paralelo': 'Paralelo',
                'nombre_completo': 'Nombre Completo'
            };
            
            // Encabezado con línea decorativa
            doc.setDrawColor(44, 62, 80);
            doc.setLineWidth(2);
            doc.line(40, 30, doc.internal.pageSize.width - 40, 30);
            
            // Título principal
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(44, 62, 80);
            doc.text(reporteData.nombre, doc.internal.pageSize.width / 2, 60, { align: 'center' });
            
            // Línea decorativa bajo el título
            doc.setDrawColor(52, 152, 219);
            doc.setLineWidth(1);
            doc.line(100, 75, doc.internal.pageSize.width - 100, 75);
            
            // Descripción (si existe)
            let yPos = 100;
            if (reporteData.descripcion && reporteData.descripcion.trim()) {
                doc.setFontSize(12);
                doc.setFont('helvetica', 'italic');
                doc.setTextColor(52, 73, 94);
                
                // Dividir descripción larga en múltiples líneas
                const splitDescription = doc.splitTextToSize(reporteData.descripcion, doc.internal.pageSize.width - 80);
                doc.text(splitDescription, doc.internal.pageSize.width / 2, yPos, { align: 'center' });
                yPos += splitDescription.length * 15 + 10;
            }
            
            // Información de generación
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(127, 140, 141);
            doc.text(`Fecha de generación: ${reporteData.fecha}`, doc.internal.pageSize.width / 2, yPos, { align: 'center' });
            doc.text(`Tipo: ${reporteData.tipo} | ID: #${reporteData.id}`, doc.internal.pageSize.width / 2, yPos + 15, { align: 'center' });
            
            // Línea separadora antes de la tabla
            yPos += 35;
            doc.setDrawColor(189, 195, 199);
            doc.setLineWidth(0.5);
            doc.line(40, yPos, doc.internal.pageSize.width - 40, yPos);
            
            // Tabla de datos
            if (reporteData.resultados.length > 0) {
                const headers = reporteData.columnas.map(col => columnasDisponibles[col] || col);
                const data = reporteData.resultados.map(fila => 
                    reporteData.columnas.map(col => {
                        let valor = fila[col] || '';
                        if (col === 'fecha_nacimiento' && valor) {
                            valor = new Date(valor).toLocaleDateString('es-ES');
                        }
                        return valor;
                    })
                );
                
                doc.autoTable({
                    head: [headers],
                    body: data,
                    startY: yPos + 15,
                    theme: 'striped',
                    styles: {
                        fontSize: 9,
                        cellPadding: 5,
                        font: 'helvetica',
                        lineColor: [189, 195, 199],
                        textColor: [52, 73, 94]
                    },
                    headStyles: {
                        fillColor: [44, 62, 80],
                        textColor: 255,
                        fontStyle: 'bold',
                        fontSize: 10,
                        lineWidth: 0.5,
                        lineColor: [189, 195, 199]
                    },
                    alternateRowStyles: {
                        fillColor: [236, 240, 241]
                    },
                    margin: { left: 40, right: 40 },
                    columnStyles: {
                        0: { cellWidth: 50, fontStyle: 'bold' } // ID Estudiante más estrecho y en negrita
                    },
                    didDrawPage: function (data) {
                        // Pie de página elegante
                        const pageCount = doc.internal.getNumberOfPages();
                        const pageHeight = doc.internal.pageSize.height;
                        
                        doc.setFontSize(8);
                        doc.setFont('helvetica', 'italic');
                        doc.setTextColor(149, 165, 166);
                        
                        // Línea superior del pie
                        doc.setDrawColor(189, 195, 199);
                        doc.setLineWidth(0.5);
                        doc.line(40, pageHeight - 25, doc.internal.pageSize.width - 40, pageHeight - 25);
                        
                        // Texto del pie
                        doc.text('Sistema de Gestión Educativa', doc.internal.pageSize.width / 2, pageHeight - 15, { align: 'center' });
                        doc.text(`Página ${data.pageNumber} de ${pageCount}`, doc.internal.pageSize.width / 2, pageHeight - 8, { align: 'center' });
                    }
                });
            } else {
                // Mensaje cuando no hay datos
                yPos += 40;
                doc.setFontSize(12);
                doc.setFont('helvetica', 'italic');
                doc.setTextColor(149, 165, 166);
                doc.text('No se encontraron resultados con los filtros seleccionados.', doc.internal.pageSize.width / 2, yPos, { align: 'center' });
            }
            
            // Descargar el PDF
            const filename = `reporte_${reporteData.id}_${new Date().toISOString().slice(0, 10).replace(/-/g, '_')}.pdf`;
            doc.save(filename);
            
            // Redirigir de vuelta a la lista de reportes
            setTimeout(function() {
                window.location.href = 'reportes.php';
            }, 1000);
        }
    </script>
</body>
</html>
