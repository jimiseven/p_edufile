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
    <title><?php echo htmlspecialchars($reporte['nombre']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <div style="padding: 20px; font-family: Arial, sans-serif;">
        <h1>Generando PDF...</h1>
        <p>Por favor espere mientras se genera el reporte.</p>
        <div id="debug-info" style="background: #f0f0f0; padding: 10px; margin: 10px 0;">
            <h3>Información de Depuración:</h3>
            <p><strong>ID Reporte:</strong> <?php echo $id_reporte; ?></p>
            <p><strong>Nombre Reporte:</strong> <?php echo htmlspecialchars($reporte['nombre']); ?></p>
            <p><strong>Tipo Base:</strong> <?php echo htmlspecialchars($tipo_base); ?></p>
            <p><strong>Columnas:</strong> <?php echo htmlspecialchars(implode(', ', $columnas)); ?></p>
            <p><strong>Número de resultados:</strong> <?php echo count($resultados); ?></p>
        </div>
    </div>

    <script>
        // Datos del reporte
        const reporteData = {
            nombre: <?php echo json_encode($reporte['nombre']); ?>,
            descripcion: <?php echo json_encode($reporte['descripcion'] ?? ''); ?>,
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

                // Configuración de página con márgenes modernos
                const pageWidth = doc.internal.pageSize.getWidth();
                const margin = 20;
                const contentWidth = pageWidth - 2 * margin;

                // Encabezado moderno con gradiente simulado
                doc.setFillColor(52, 152, 219);
                doc.rect(margin, 15, contentWidth, 8, 'F');
                doc.setFillColor(41, 128, 185);
                doc.rect(margin, 23, contentWidth, 32, 'F');
                
                // Título con sombra sutil
                doc.setTextColor(255);
                doc.setFontSize(24);
                doc.setFont('helvetica', 'bold');
                doc.text(reporteData.nombre, pageWidth / 2, 42, { align: 'center' });
                
                // Subtítulo decorativo
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(220, 240, 255);
                doc.text('SISTEMA EDUCATIVO', pageWidth / 2, 50, { align: 'center' });

                // Línea divisoria moderna
                doc.setDrawColor(52, 152, 219);
                doc.setLineWidth(2);
                doc.line(margin, 58, pageWidth - margin, 58);
                
                // Línea secundaria fina
                doc.setDrawColor(189, 195, 199);
                doc.setLineWidth(0.5);
                doc.line(margin, 60, pageWidth - margin, 60);

                // Tarjeta de información con diseño moderno
                doc.setFillColor(248, 249, 250);
                doc.roundedRect(margin, 68, contentWidth, 35, 3, 3, 'F');
                doc.setDrawColor(189, 195, 199);
                doc.setLineWidth(0.5);
                doc.roundedRect(margin, 68, contentWidth, 35, 3, 3, 'S');

                // Información del reporte en tarjeta
                doc.setTextColor(52, 73, 94);
                doc.setFontSize(11);
                doc.setFont('helvetica', 'bold');
                
                // Iconos simulados con texto
                doc.setFontSize(14);
                doc.text('●', margin + 8, 85);
                doc.setFontSize(10);
                doc.text('Tipo:', margin + 18, 85);
                doc.setFont('helvetica', 'normal');
                doc.text(reporteData.tipo, margin + 50, 85);
                
                doc.setFontSize(14);
                doc.text('●', margin + 8, 95);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.text('Fecha:', margin + 18, 95);
                doc.setFont('helvetica', 'normal');
                doc.text(reporteData.fecha, margin + 50, 95);
                
                doc.setFontSize(14);
                doc.text('●', margin + 150, 85);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.text('ID:', margin + 160, 85);
                doc.setFont('helvetica', 'normal');
                doc.text(String(reporteData.id), margin + 175, 85);

                // Descripción si existe
                let startY = 115;
                if (reporteData.descripcion && reporteData.descripcion.trim()) {
                    doc.setFillColor(236, 240, 241);
                    doc.roundedRect(margin, 108, contentWidth, 20, 2, 2, 'F');
                    doc.setDrawColor(189, 195, 199);
                    doc.setLineWidth(0.5);
                    doc.roundedRect(margin, 108, contentWidth, 20, 2, 2, 'S');
                    
                    doc.setTextColor(52, 73, 94);
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'bold');
                    doc.text('Descripción:', margin + 10, 120);
                    doc.setFont('helvetica', 'normal');
                    
                    const descLimit = 70;
                    const descripcion = reporteData.descripcion.length > descLimit ? 
                        reporteData.descripcion.substring(0, descLimit) + '...' : 
                        reporteData.descripcion;
                    doc.text(descripcion, margin + 55, 120);
                    startY = 138;
                }

                // Preparar datos para la tabla moderna
                let headers = [];
                let data = [];

                if (reporteData.columnas && reporteData.columnas.length > 0) {
                    // Mapeo elegante de columnas
                    const columnasMap = {
                        'nombres': 'Nombres',
                        'apellido_paterno': 'Apellido Paterno',
                        'apellido_materno': 'Apellido Materno',
                        'genero': 'Género',
                        'edad': 'Edad',
                        'fecha_nacimiento': 'Fecha Nac.',
                        'carnet_identidad': 'C.I.',
                        'rude': 'RUDE',
                        'nivel': 'Nivel',
                        'curso': 'Curso',
                        'paralelo': 'Paralelo'
                    };
                    
                    headers = reporteData.columnas.map(col => columnasMap[col] || col);
                    
                    data = reporteData.resultados.map(fila => {
                        return reporteData.columnas.map(col => {
                            let valor = fila[col] || '';
                            
                            // Formateo especial
                            if (col === 'fecha_nacimiento' && valor) {
                                valor = new Date(valor).toLocaleDateString('es-ES', { 
                                    day: '2-digit', 
                                    month: 'short', 
                                    year: 'numeric' 
                                });
                            } else if (col === 'genero' && valor) {
                                valor = valor === 'M' ? 'MASCULINO' : 'FEMENINO';
                            } else if (col === 'edad' && valor) {
                                valor = String(valor) + ' a';
                            }
                            
                            valor = String(valor).trim();
                            if (valor.length > 30) {
                                valor = valor.substring(0, 27) + '...';
                            }
                            
                            return valor;
                        });
                    });
                } else {
                    headers = ['Nombres', 'Apellido Paterno', 'Apellido Materno', 'Edad', 'Curso', 'Paralelo'];
                    data = reporteData.resultados.map(fila => [
                        String(fila.nombres || '').trim(),
                        String(fila.apellido_paterno || '').trim(),
                        String(fila.apellido_materno || '').trim(),
                        fila.edad ? String(fila.edad) + ' a' : '',
                        String(fila.curso || '').trim(),
                        String(fila.paralelo || '').trim()
                    ]);
                }

                console.log('Headers:', headers);
                console.log('Data:', data);

                // Tabla moderna con diseño atractivo
                if (data.length > 0) {
                    doc.autoTable({
                        head: [headers],
                        body: data,
                        startY: startY,
                        theme: 'striped',
                        styles: {
                            fontSize: 9,
                            cellPadding: 6,
                            font: 'helvetica',
                            lineColor: [236, 240, 241],
                            lineWidth: 0.5,
                            textColor: [44, 62, 80]
                        },
                        headStyles: {
                            fillColor: [52, 152, 219],
                            textColor: 255,
                            fontStyle: 'bold',
                            lineWidth: 0.5,
                            lineColor: [41, 128, 185],
                            halign: 'center',
                            fontSize: 10
                        },
                        alternateRowStyles: {
                            fillColor: [248, 249, 250]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255]
                        },
                        margin: { left: margin, right: margin },
                        tableWidth: contentWidth,
                        columnStyles: (() => {
                            const styles = {};
                            const numColumns = headers.length;
                            
                            reporteData.columnas.forEach((col, index) => {
                                if (col === 'nombres') {
                                    styles[index] = { cellWidth: contentWidth * 0.42, halign: 'left', fontStyle: 'normal' };
                                } else if (col === 'apellido_paterno' || col === 'apellido_materno') {
                                    styles[index] = { cellWidth: contentWidth * 0.28, halign: 'left' };
                                } else if (col === 'edad') {
                                    styles[index] = { cellWidth: contentWidth * 0.08, halign: 'center', fontStyle: 'bold' };
                                } else if (col === 'genero') {
                                    styles[index] = { cellWidth: contentWidth * 0.10, halign: 'center' };
                                } else if (col === 'curso') {
                                    styles[index] = { cellWidth: contentWidth * 0.08, halign: 'center' };
                                } else if (col === 'paralelo') {
                                    styles[index] = { cellWidth: contentWidth * 0.06, halign: 'center' };
                                } else if (col === 'fecha_nacimiento') {
                                    styles[index] = { cellWidth: contentWidth * 0.12, halign: 'center' };
                                } else if (col === 'carnet_identidad' || col === 'rude') {
                                    styles[index] = { cellWidth: contentWidth * 0.11, halign: 'center' };
                                } else if (col === 'nivel') {
                                    styles[index] = { cellWidth: contentWidth * 0.08, halign: 'center' };
                                } else {
                                    styles[index] = { cellWidth: contentWidth / numColumns, halign: 'left' };
                                }
                            });
                            
                            return styles;
                        })(),
                        didDrawRow: (data) => {
                            // Línea separadora después de cada 5 filas
                            if (data.row.index % 5 === 4 && data.row.index < data.table.body.length - 1) {
                                doc.setDrawColor(189, 195, 199);
                                doc.setLineWidth(0.3);
                                const rowHeight = data.row.height;
                                const yPos = data.row.y + rowHeight;
                                doc.line(margin, yPos, pageWidth - margin, yPos);
                            }
                        }
                    });

                    // Pie de página moderno
                    const finalY = doc.lastAutoTable.finalY || startY;
                    
                    // Tarjeta de pie con gradiente
                    doc.setFillColor(52, 152, 219);
                    doc.roundedRect(margin, finalY + 20, contentWidth, 8, 2, 2, 'F');
                    doc.setFillColor(41, 128, 185);
                    doc.roundedRect(margin, finalY + 28, contentWidth, 20, 2, 2, 'F');
                    
                    // Información del pie
                    doc.setTextColor(255);
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'bold');
                    doc.text(`Registros: ${data.length}`, margin + 15, finalY + 40);
                    
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'normal');
                    doc.text(new Date().toLocaleDateString('es-ES'), margin + 15, finalY + 45);
                    
                    // Derecha
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'bold');
                    doc.text(`Pág. ${doc.internal.getCurrentPageInfo().pageNumber}`, pageWidth - margin - 15, finalY + 40, { align: 'right' });
                    
                    doc.setFontSize(8);
                    doc.setFont('helvetica', 'normal');
                    doc.text('Reportes Educativos v2.0', pageWidth - margin - 15, finalY + 45, { align: 'right' });
                    
                } else {
                    // Mensaje moderno sin datos
                    doc.setFillColor(248, 249, 250);
                    doc.roundedRect(margin, 120, contentWidth, 40, 3, 3, 'F');
                    doc.setDrawColor(189, 195, 199);
                    doc.setLineWidth(0.5);
                    doc.roundedRect(margin, 120, contentWidth, 40, 3, 3, 'S');
                    
                    doc.setTextColor(149, 165, 166);
                    doc.setFontSize(16);
                    doc.setFont('helvetica', 'bold');
                    doc.text('⚠ No se encontraron datos', pageWidth / 2, 145, { align: 'center' });
                    
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'normal');
                    doc.text('El reporte no contiene información para mostrar', pageWidth / 2, 152, { align: 'center' });
                }

                // Guardar PDF
                doc.save(`reporte_${reporteData.id}_${Date.now()}.pdf`);
                
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
