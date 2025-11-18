<?php
session_start();
require_once '../config/database.php';
require_once 'report_generator.php';

// Verificar solo para administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Obtener datos del POST
$filtros = json_decode($_POST['filtros'] ?? '[]', true);
$columnas = json_decode($_POST['columnas'] ?? '[]', true);
$columnas_orden = json_decode($_POST['columnas_orden'] ?? '{}', true);
$tipo_base = $_POST['tipo_base'] ?? '';
$nombre_reporte = $_POST['nombre_reporte'] ?? 'Reporte Temporal';

error_log("=== DEPURACIÓN DOWNLOAD_TEMPORAL_REPORT ===");
error_log("Filtros: " . print_r($filtros, true));
error_log("Columnas: " . print_r($columnas, true));
error_log("Orden de Columnas: " . print_r($columnas_orden, true));
error_log("Tipo Base: " . $tipo_base);

// Ordenar columnas según el orden definido por el usuario
if (!empty($columnas) && !empty($columnas_orden)) {
    // Crear un array asociativo con el orden de cada columna
    $columnas_con_orden = [];
    foreach ($columnas as $campo) {
        if (isset($columnas_orden[$campo])) {
            $columnas_con_orden[$campo] = (int)$columnas_orden[$campo];
        }
    }
    
    // Ordenar las columnas por su valor de orden
    asort($columnas_con_orden);
    $columnas = array_keys($columnas_con_orden);
    
    error_log("Columnas ordenadas: " . print_r($columnas, true));
}

// Obtener los datos del reporte
$consulta = construirConsultaSQL($filtros, $columnas, $tipo_base);
$resultados = [];

try {
    $conn = (new Database())->connect();
    $stmt = $conn->prepare($consulta['sql']);
    $stmt->execute($consulta['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Resultados obtenidos: " . count($resultados) . " filas");
} catch (Exception $e) {
    error_log("Error en consulta: " . $e->getMessage());
    $resultados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombre_reporte); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .loading {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #666;
        }
        .error {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #d32f2f;
            background-color: #ffebee;
            border: 1px solid #f8bbd9;
            border-radius: 4px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="loading">
        <i class="fas fa-spinner fa-spin"></i> Generando PDF...
    </div>

    <script>
        // Inicializar jsPDF
        window.jsPDF = window.jspdf.jsPDF;
        
        // Función para generar el PDF
        function generarPDF() {
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'pt',
                format: 'letter'
            });
            
            // Datos del reporte
            const reporteData = {
                nombre: <?php echo json_encode(htmlspecialchars_decode($nombre_reporte)); ?>,
                descripcion: '',
                tipo: <?php echo json_encode($tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'); ?>,
                fecha: <?php echo json_encode(date('d/m/Y H:i:s')); ?>,
                id: 'TEMP',
                columnas: <?php echo json_encode($columnas); ?>,
                resultados: <?php echo json_encode($resultados); ?>
            };
            
            console.log("=== DEPURACIÓN JAVASCRIPT TEMPORAL ===");
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
            doc.setLineWidth(1);
            doc.line(40, 30, doc.internal.pageSize.width - 40, 30);
            
            // Título del reporte
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(44, 62, 80);
            doc.text(reporteData.nombre, doc.internal.pageSize.width / 2, 60, { align: 'center' });
            
            // Información del reporte
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 100, 100);
            
            let yPos = 80;
            
            if (reporteData.descripcion) {
                const lines = doc.splitTextToSize(reporteData.descripcion, doc.internal.pageSize.width - 80);
                doc.text(lines, doc.internal.pageSize.width / 2, yPos, { align: 'center' });
                yPos += lines.length * 15 + 10;
            }
            
            // Metadatos
            const metaInfo = [
                `Tipo: ${reporteData.tipo}`,
                `Fecha: ${reporteData.fecha}`,
                `ID: ${reporteData.id}`
            ];
            
            metaInfo.forEach(info => {
                doc.text(info, 40, yPos);
                yPos += 15;
            });
            
            yPos += 10;
            
            // Línea separadora
            doc.setDrawColor(200, 200, 200);
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
                            
                            // Si es muy largo, abreviar
                            if (nombreCompleto.length > 40) {
                                const palabras = nombreCompleto.split(' ');
                                if (palabras.length > 3) {
                                    nombreCompleto = palabras.slice(0, 2).join(' ') + ' ' + 
                                                     palabras.slice(2).map(p => p.charAt(0) + '.').join(' ');
                                }
                            }
                            valor = nombreCompleto;
                        }
                        
                        return valor;
                    })
                );
                
                // Calcular anchos de columna responsivos
                const totalWidth = doc.internal.pageSize.width - 80;
                const columnCount = headers.length;
                const columnWidths = {};
                let remainingWidth = totalWidth;
                let fixedColumns = 0;
                
                // Asignar anchos fijos para columnas específicas
                reporteData.columnas.forEach((col, index) => {
                    if (col === 'id_estudiante') {
                        columnWidths[index] = 40;
                        fixedColumns++;
                    } else if (col === 'edad') {
                        columnWidths[index] = 30;
                        fixedColumns++;
                    } else if (col === 'genero') {
                        columnWidths[index] = 35;
                        fixedColumns++;
                    } else if (col === 'nivel' || col === 'curso' || col === 'paralelo') {
                        columnWidths[index] = 45;
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
                        cellPadding: 4,
                        font: 'helvetica',
                        textColor: 50,
                        lineColor: 200,
                        lineWidth: 0.1
                    },
                    headStyles: {
                        fillColor: [44, 62, 80],
                        textColor: 255,
                        fontStyle: 'bold',
                        fontSize: 10
                    },
                    alternateRowStyles: {
                        fillColor: [248, 248, 248]
                    },
                    columnStyles: finalColumnStyles,
                    margin: {
                        left: 40,
                        right: 40
                    }
                });
                
                // Pie de página
                const finalY = doc.lastAutoTable.finalY || yPos + 15;
                doc.setFontSize(10);
                doc.setTextColor(150, 150, 150);
                doc.text(`Total de registros: ${reporteData.resultados.length}`, 40, finalY + 20);
                doc.text('Página 1 de 1', doc.internal.pageSize.width - 40, finalY + 20, { align: 'right' });
                
            } else {
                // Mensaje si no hay datos
                doc.setFontSize(14);
                doc.setTextColor(150, 150, 150);
                doc.text('No se encontraron datos para mostrar', doc.internal.pageSize.width / 2, yPos + 50, { align: 'center' });
            }
            
            // Guardar el PDF
            doc.save('reporte_temporal.pdf');
            
            // Cerrar la ventana después de generar el PDF
            setTimeout(() => {
                window.close();
            }, 1000);
        }
        
        // Generar PDF cuando se carga la página
        window.onload = function() {
            try {
                generarPDF();
            } catch (error) {
                console.error('Error al generar PDF:', error);
                document.body.innerHTML = `
                    <div class="error">
                        <h3>Error al generar el PDF</h3>
                        <p>${error.message}</p>
                        <button onclick="window.close()">Cerrar</button>
                    </div>
                `;
            }
        };
    </script>
</body>
</html>
