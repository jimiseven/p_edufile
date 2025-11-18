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

// Ordenar columnas según el orden definido por el usuario
if (!empty($columnas) && !empty($columnas_orden)) {
    $columnas_con_orden = [];
    foreach ($columnas as $campo) {
        if (isset($columnas_orden[$campo])) {
            $columnas_con_orden[$campo] = (int)$columnas_orden[$campo];
        }
    }
    asort($columnas_con_orden);
    $columnas = array_keys($columnas_con_orden);
}

// Obtener los datos del reporte
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombre_reporte); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <div style="padding: 20px; font-family: Arial, sans-serif;">
        <h1>Generando PDF...</h1>
        <p>Por favor espere mientras se genera el reporte.</p>
        <div id="debug-info" style="background: #f0f0f0; padding: 10px; margin: 10px 0;">
            <h3>Información de Depuración:</h3>
            <p><strong>Nombre Reporte:</strong> <?php echo htmlspecialchars($nombre_reporte); ?></p>
            <p><strong>Tipo Base:</strong> <?php echo htmlspecialchars($tipo_base); ?></p>
            <p><strong>Columnas:</strong> <?php echo htmlspecialchars(implode(', ', $columnas)); ?></p>
            <p><strong>Número de resultados:</strong> <?php echo count($resultados); ?></p>
        </div>
    </div>

    <script>
        // Datos del reporte
        const reporteData = {
            nombre: <?php echo json_encode($nombre_reporte); ?>,
            tipo: <?php echo json_encode($tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica'); ?>,
            fecha: <?php echo json_encode(date('d/m/Y H:i:s')); ?>,
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

        console.log('Datos del reporte:', reporteData);

        // Función para generar PDF
        function generarPDF() {
            try {
                // Inicializar jsPDF
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                // Configuración de página con márgenes equilibrados
                const pageWidth = doc.internal.pageSize.getWidth();
                const margin = 18;
                const contentWidth = pageWidth - 2 * margin;

                // Encabezado elegante
                doc.setFillColor(30, 30, 30);
                doc.rect(margin, 15, contentWidth, 35, 'F');
                
                doc.setTextColor(255);
                doc.setFontSize(20);
                doc.setFont('helvetica', 'bold');
                doc.text(reporteData.nombre, pageWidth / 2, 38, { align: 'center' });

                // Línea decorativa inferior del encabezado
                doc.setDrawColor(60, 60, 60);
                doc.setLineWidth(0.8);
                doc.line(margin, 52, pageWidth - margin, 52);

                // Información del reporte en fila única y alineada
                doc.setTextColor(40, 40, 40);
                doc.setFontSize(11);
                doc.setFont('helvetica', 'normal');
                
                const infoY = 62;
                const infoSpacing = contentWidth / 3;
                
                doc.text(`Tipo: ${reporteData.tipo}`, margin, infoY);
                doc.text(`Fecha: ${reporteData.fecha}`, margin + infoSpacing, infoY);

                // Preparar datos para la tabla administrativa
                let headers = [];
                let data = [];

                if (reporteData.columnas && reporteData.columnas.length > 0) {
                    // Mapeo de columnas a nombres profesionales
                    const columnasMap = {
                        'nombres': 'Nombres',
                        'apellido_paterno': 'Apellido Paterno',
                        'apellido_materno': 'Apellido Materno',
                        'genero': 'Género',
                        'edad': 'Edad',
                        'fecha_nacimiento': 'Fecha Nac.',
                        'carnet_identidad': 'CI',
                        'rude': 'RUDE',
                        'nivel': 'Nivel',
                        'curso': 'Curso',
                        'paralelo': 'Paralelo'
                    };
                    
                    // Crear encabezados profesionales
                    headers = reporteData.columnas.map(col => columnasMap[col] || col);
                    
                    // Crear datos formateados
                    data = reporteData.resultados.map(fila => {
                        return reporteData.columnas.map(col => {
                            let valor = fila[col] || '';
                            
                            // Formatear campos específicos
                            if (col === 'fecha_nacimiento' && valor) {
                                valor = new Date(valor).toLocaleDateString('es-ES', { 
                                    day: '2-digit', 
                                    month: '2-digit', 
                                    year: 'numeric' 
                                });
                            } else if (col === 'genero' && valor) {
                                valor = valor.toUpperCase();
                            } else if (col === 'edad' && valor) {
                                valor = String(valor) + ' años';
                            }
                            
                            // Limpiar y formatear texto
                            valor = String(valor).trim();
                            
                            // Truncar solo si es muy largo
                            if (valor.length > 40) {
                                valor = valor.substring(0, 37) + '...';
                            }
                            
                            return valor;
                        });
                    });
                } else {
                    // Columnas por defecto para reporte estudiantil
                    headers = ['Nombres', 'Apellido Paterno', 'Apellido Materno', 'Edad', 'Curso', 'Paralelo'];
                    data = reporteData.resultados.map(fila => [
                        String(fila.nombres || '').trim(),
                        String(fila.apellido_paterno || '').trim(),
                        String(fila.apellido_materno || '').trim(),
                        fila.edad ? String(fila.edad) + ' años' : '',
                        String(fila.curso || '').trim(),
                        String(fila.paralelo || '').trim()
                    ]);
                }

                console.log('Headers:', headers);
                console.log('Data:', data);

                // Generar tabla administrativa compacta
                if (data.length > 0) {
                    const startY = 74;
                    
                    doc.autoTable({
                        head: [headers],
                        body: data,
                        startY: startY,
                        theme: 'grid',
                        styles: {
                            fontSize: 10,
                            cellPadding: 5,
                            font: 'helvetica',
                            lineColor: [180, 180, 180],
                            lineWidth: 0.3,
                            textColor: [40, 40, 40],
                            fillColor: [255, 255, 255]
                        },
                        headStyles: {
                            fillColor: [245, 245, 245],
                            textColor: [30, 30, 30],
                            fontStyle: 'bold',
                            lineWidth: 0.5,
                            lineColor: [150, 150, 150],
                            halign: 'center'
                        },
                        alternateRowStyles: {
                            fillColor: [250, 250, 250]
                        },
                        margin: { left: margin, right: margin },
                        tableWidth: contentWidth,
                        columnStyles: (() => {
                            const styles = {};
                            const numColumns = headers.length;
                            
                            // Asignar anchos y alineación según tipo de columna
                            reporteData.columnas.forEach((col, index) => {
                                if (col === 'nombres') {
                                    styles[index] = { cellWidth: contentWidth * 0.45, halign: 'left' };
                                } else if (col === 'apellido_paterno' || col === 'apellido_materno') {
                                    styles[index] = { cellWidth: contentWidth * 0.25, halign: 'left' };
                                } else if (col === 'edad') {
                                    styles[index] = { cellWidth: contentWidth * 0.10, halign: 'center' };
                                } else if (col === 'genero') {
                                    styles[index] = { cellWidth: contentWidth * 0.08, halign: 'center' };
                                } else if (col === 'curso') {
                                    styles[index] = { cellWidth: contentWidth * 0.10, halign: 'center' };
                                } else if (col === 'paralelo') {
                                    styles[index] = { cellWidth: contentWidth * 0.08, halign: 'center' };
                                } else if (col === 'fecha_nacimiento') {
                                    styles[index] = { cellWidth: contentWidth * 0.15, halign: 'center' };
                                } else if (col === 'carnet_identidad' || col === 'rude') {
                                    styles[index] = { cellWidth: contentWidth * 0.12, halign: 'center' };
                                } else if (col === 'nivel') {
                                    styles[index] = { cellWidth: contentWidth * 0.10, halign: 'center' };
                                } else {
                                    // Distribuir espacio restante equitativamente
                                    styles[index] = { cellWidth: contentWidth / numColumns, halign: 'left' };
                                }
                            });
                            
                            return styles;
                        })()
                    });

                    // Pie de página corporativo
                    const finalY = doc.lastAutoTable.finalY || startY;
                    
                    // Línea superior del pie
                    doc.setDrawColor(120, 120, 120);
                    doc.setLineWidth(0.5);
                    doc.line(margin, finalY + 15, pageWidth - margin, finalY + 15);
                    
                    // Información del pie en dos columnas
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(80, 80, 80);
                    
                    // Izquierda: información del reporte
                    doc.text(`Total de registros: ${data.length}`, margin, finalY + 25);
                    doc.setFontSize(8);
                    doc.text(`Generado: ${new Date().toLocaleString('es-ES')}`, margin, finalY + 32);
                    
                    // Derecha: información del documento
                    doc.setFontSize(9);
                    doc.text(`Página ${doc.internal.getCurrentPageInfo().pageNumber}`, pageWidth - margin, finalY + 25, { align: 'right' });
                    doc.setFontSize(8);
                    doc.text('Sistema de Reportes Educativos', pageWidth - margin, finalY + 32, { align: 'right' });
                    
                } else {
                    // Mensaje profesional cuando no hay datos
                    doc.setFontSize(14);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(100, 100, 100);
                    doc.text('No se encontraron datos para mostrar', pageWidth / 2, 120, { align: 'center' });
                    
                    // Pie de página incluso sin datos
                    doc.setFontSize(9);
                    doc.setTextColor(120, 120, 120);
                    doc.text(`Generado el: ${new Date().toLocaleString('es-ES')}`, margin, 140);
                }

                // Guardar PDF
                doc.save(`reporte_temporal_${Date.now()}.pdf`);
                
                // Cerrar ventana después de un pequeño delay
                setTimeout(() => {
                    window.close();
                }, 1000);

            } catch (error) {
                console.error('Error al generar PDF:', error);
                document.body.innerHTML = `
                    <div style="padding: 20px; color: red;">
                        <h2>Error al generar PDF</h2>
                        <p>${error.message}</p>
                        <button onclick="window.close()">Cerrar</button>
                    </div>
                `;
            }
        }

        // Generar PDF cuando la página cargue
        window.onload = function() {
            generarPDF();
        };
    </script>
</body>
</html>
