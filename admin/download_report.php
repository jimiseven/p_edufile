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

error_log("=== DEPURACIÓN DOWNLOAD_REPORT ===");
error_log("ID Reporte: " . $id_reporte);
error_log("Filtros: " . print_r($filtros, true));
error_log("Columnas: " . print_r($columnas, true));
error_log("Tipo Base: " . $tipo_base);

try {
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Resultados obtenidos: " . count($resultados) . " filas");
    if (!empty($resultados)) {
        error_log("Primera fila: " . print_r($resultados[0], true));
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error en consulta: " . $error);
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
                tipo: <?php echo json_encode($tipo_base == 'info_estudentil' ? 'Información Estudiantil' : 'Información Académica'); ?>,
                fecha: <?php echo json_encode(date('d/m/Y H:i:s')); ?>,
                id: <?php echo $id_reporte; ?>,
                columnas: <?php echo json_encode($columnas); ?>,
                resultados: <?php echo json_encode($resultados); ?>
            };
            
            console.log("=== DEPURACIÓN JAVASCRIPT ===");
            console.log("Reporte Data:", reporteData);
            console.log("Columnas:", reporteData.columnas);
            console.log("Resultados:", reporteData.resultados);
            console.log("Número de resultados:", reporteData.resultados.length);
            
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
                        
                        // Optimizar el formato de nombres y apellidos
                        if (col === 'nombres' || col === 'apellido_paterno' || col === 'apellido_materno') {
                            // Eliminar espacios extra y normalizar
                            valor = valor.toString().trim().replace(/\s+/g, ' ');
                            // Para nombres muy largos, usar abreviaturas inteligentes
                            if (valor.length > 25) {
                                const palabras = valor.split(' ');
                                if (palabras.length > 2) {
                                    // Mantener primeras palabras intactas y abreviar el resto
                                    valor = palabras.slice(0, 2).join(' ') + ' ' + 
                                           palabras.slice(2).map(p => p.charAt(0) + '.').join(' ');
                                } else if (palabras.length === 2 && palabras[1].length > 15) {
                                    // Si el segundo apellido es muy largo, abreviarlo
                                    valor = palabras[0] + ' ' + palabras[1].charAt(0) + '.';
                                }
                                // Si aún es muy largo, truncar
                                if (valor.length > 30) {
                                    valor = valor.substring(0, 28) + '...';
                                }
                            }
                        } else if (col === 'nombre_completo') {
                            // Combinar nombres y apellidos de forma optimizada
                            const nombres = (fila['nombres'] || '').toString().trim().replace(/\s+/g, ' ');
                            const apPaterno = (fila['apellido_paterno'] || '').toString().trim().replace(/\s+/g, ' ');
                            const apMaterno = (fila['apellido_materno'] || '').toString().trim().replace(/\s+/g, ' ');
                            
                            // Construir nombre completo de forma compacta
                            let nombreCompleto = `${nombres} ${apPaterno} ${apMaterno}`.trim().replace(/\s+/g, ' ');
                            
                            // Optimizar espacio para nombres completos muy largos
                            if (nombreCompleto.length > 35) {
                                const palabras = nombreCompleto.split(' ');
                                if (palabras.length > 4) {
                                    // Abreviar apellidos si hay demasiadas palabras
                                    nombreCompleto = palabras.slice(0, 2).join(' ') + ' ' + 
                                                     palabras.slice(2).map(p => p.charAt(0) + '.').join(' ');
                                } else if (palabras.length === 4) {
                                    // Formato: Nombre Apellido1 A.
                                    nombreCompleto = palabras[0] + ' ' + palabras[1] + ' ' + 
                                                     palabras.slice(2).map(p => p.charAt(0) + '.').join(' ');
                                }
                            }
                            
                            // Truncar si aún es muy largo
                            if (nombreCompleto.length > 40) {
                                nombreCompleto = nombreCompleto.substring(0, 38) + '...';
                            }
                            
                            valor = nombreCompleto;
                        } else if (col === 'fecha_nacimiento' && valor) {
                            valor = new Date(valor).toLocaleDateString('es-ES');
                        }
                        
                        return valor;
                    })
                );
                
                // Calcular anchos de columna responsivos
                const totalWidth = doc.internal.pageSize.width - 80; // 40 margen izquierdo + 40 derecho
                const columnCount = reporteData.columnas.length;
                
                // Definir anchos mínimos y preferidos para cada tipo de columna
                const columnWidths = {};
                let remainingWidth = totalWidth;
                let fixedColumns = 0;
                
                // Asignar anchos fijos para columnas específicas
                reporteData.columnas.forEach((col, index) => {
                    if (col === 'id_estudiante') {
                        columnWidths[index] = 40; // ID muy estrecho
                        fixedColumns++;
                    } else if (col === 'edad') {
                        columnWidths[index] = 30; // Edad muy estrecho
                        fixedColumns++;
                    } else if (col === 'genero') {
                        columnWidths[index] = 35; // Género estrecho
                        fixedColumns++;
                    } else if (col === 'nivel' || col === 'curso' || col === 'paralelo') {
                        columnWidths[index] = 45; // Columnas de curso/paralelo
                        fixedColumns++;
                    }
                });
                
                // Calcular el ancho restante para las columnas de texto
                const fixedWidthTotal = Object.values(columnWidths).reduce((a, b) => a + b, 0);
                const flexibleColumns = columnCount - fixedColumns;
                const flexibleWidth = flexibleColumns > 0 ? (remainingWidth - fixedWidthTotal) / flexibleColumns : 0;
                
                // Asignar anchos finales
                const finalColumnStyles = {};
                reporteData.columnas.forEach((col, index) => {
                    if (columnWidths[index]) {
                        finalColumnStyles[index] = { cellWidth: columnWidths[index] };
                    } else {
                        // Para columnas de texto como nombres, apellidos, etc.
                        let cellWidth = flexibleWidth;
                        
                        // Dar más espacio a nombres y apellidos
                        if (col === 'nombres' || col === 'nombre_completo') {
                            cellWidth = Math.max(flexibleWidth * 1.5, 80);
                        } else if (col === 'apellido_paterno' || col === 'apellido_materno') {
                            cellWidth = Math.max(flexibleWidth * 1.2, 70);
                        } else if (col === 'carnet_identidad' || col === 'rude') {
                            cellWidth = Math.max(flexibleWidth * 0.8, 60);
                        } else if (col === 'fecha_nacimiento') {
                            cellWidth = Math.max(flexibleWidth * 0.9, 65);
                        }
                        
                        finalColumnStyles[index] = { cellWidth: cellWidth };
                    }
                });
                
                doc.autoTable({
                    head: [headers],
                    body: data,
                    startY: yPos + 15,
                    theme: 'striped',
                    styles: {
                        fontSize: 9,
                        cellPadding: 4, // Reducir padding para más espacio
                        font: 'helvetica',
                        lineColor: [189, 195, 199],
                        textColor: [52, 73, 94],
                        overflow: 'linebreak', // Permitir salto de línea si es necesario
                        cellWidth: 'auto',
                        valign: 'middle', // Alinear verticalmente al centro
                        halign: 'left' // Alinear horizontalmente a la izquierda
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
                    columnStyles: finalColumnStyles,
                    didParseCell: function(data) {
                        // Ajustar tamaño de fuente para textos largos
                        if (data.column.index !== undefined) {
                            const colName = reporteData.columnas[data.column.index];
                            const text = data.cell.text;
                            const textLength = text ? text.toString().length : 0;
                            
                            // Ajustar fuente según longitud del texto y tipo de columna
                            if (colName === 'nombres' || colName === 'nombre_completo') {
                                if (textLength > 35) {
                                    data.cell.styles.fontSize = 7;
                                } else if (textLength > 25) {
                                    data.cell.styles.fontSize = 8;
                                }
                            } else if (colName === 'apellido_paterno' || colName === 'apellido_materno') {
                                if (textLength > 30) {
                                    data.cell.styles.fontSize = 7;
                                } else if (textLength > 20) {
                                    data.cell.styles.fontSize = 8;
                                }
                            } else if (colName === 'carnet_identidad' || colName === 'rude') {
                                if (textLength > 20) {
                                    data.cell.styles.fontSize = 8;
                                }
                            } else if (textLength > 25) {
                                // Para cualquier otra columna con texto largo
                                data.cell.styles.fontSize = 8;
                            }
                            
                            // Alinear mejor el contenido según el tipo de dato
                            if (colName === 'edad' || colName === 'id_estudiante') {
                                data.cell.styles.halign = 'center';
                            } else if (colName === 'genero') {
                                data.cell.styles.halign = 'center';
                            } else if (colName === 'fecha_nacimiento') {
                                data.cell.styles.halign = 'center';
                            }
                        }
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
