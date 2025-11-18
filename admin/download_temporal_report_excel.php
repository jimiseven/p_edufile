<?php
// Limpiar cualquier salida anterior
ob_clean();
header_remove();

require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once 'includes/report_functions.php';
require_once 'report_generator.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

// Verificar si hay datos temporales de reporte
if (!isset($_SESSION['reporte_temporal'])) {
    header('Location: constructor_reporte.php?error=No hay datos temporales de reporte');
    exit;
}

try {
    // Obtener datos temporales
    $temporal = $_SESSION['reporte_temporal'];
    $filtros = $temporal['filtros'];
    $columnas = $temporal['columnas'];
    $tipo_base = $temporal['tipo_base'];
    
    // Obtener el orden de columnas si existe
    $columnas_orden = isset($_POST['columnas_orden']) ? $_POST['columnas_orden'] : [];
    
    // Construir consulta SQL
    $sql = construirConsulta($tipo_base, $filtros, $columnas);
    $stmt = $conn->prepare($sql);
    
    // Ejecutar consulta con parámetros
    $params = [];
    $paramIndex = 1;
    
    foreach ($filtros as $campo => $valor) {
        if (is_array($valor)) {
            // Para filtros de tipo 'in'
            $placeholders = str_repeat('?,', count($valor) - 1) . '?';
            $sql = str_replace(":$campo", "($placeholders)", $sql);
            foreach ($valor as $val) {
                $params[] = $val;
            }
        } else {
            $params[] = $valor;
        }
    }
    
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar si se obtuvieron resultados
    if (empty($resultados)) {
        // Si no hay resultados, mostrar mensaje en el Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Reporte Temporal');
        $sheet->setCellValue('A3', 'No se encontraron datos con los filtros seleccionados');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        
        // Configurar headers y descargar
        ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Reporte_Temporal_Sin_Datos.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Expires: 0');
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    // Preparar datos para Excel
    $reporteData = [
        'nombre' => 'Reporte Temporal',
        'descripcion' => 'Reporte generado temporalmente',
        'tipo' => $tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica',
        'fecha' => date('d/m/Y H:i:s'),
        'id' => 'TEMP-' . time(),
        'columnas' => $columnas,
        'resultados' => $resultados
    ];
    
    // Crear nuevo documento Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator("Sistema Educativo")
        ->setTitle("Reporte Temporal")
        ->setSubject($reporteData['tipo']);
    
    // Establecer estilos optimizados para impresión en blanco y negro (similar a repoEx.php)
    $headerStyle = [
        'font' => [
            'bold' => true, 
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
            'startColor' => ['rgb' => '000000']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]
    ];
    
    $infoStyle = [
        'font' => [
            'bold' => true,
            'size' => 10,
            'color' => ['rgb' => '000000']
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
            'startColor' => ['rgb' => 'E6E6E6']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]
    ];
    
    $dataStyle = [
        'font' => [
            'size' => 10,
            'color' => ['rgb' => '000000']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '666666']
            ]
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]
    ];
    
    $numberStyle = [
        'font' => [
            'size' => 10,
            'color' => ['rgb' => '000000']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '666666']
            ]
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]
    ];
    
    // Escribir información del reporte
    $sheet->setCellValue('A1', $reporteData['nombre']);
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
    
    // Información del reporte
    $sheet->setCellValue('A2', 'Tipo:');
    $sheet->setCellValue('B2', $reporteData['tipo']);
    $sheet->setCellValue('C2', 'Fecha:');
    $sheet->setCellValue('D2', $reporteData['fecha']);
    $sheet->getStyle('A2:D2')->applyFromArray($infoStyle);
    
    // ID del reporte
    $sheet->setCellValue('A3', 'ID:');
    $sheet->setCellValue('B3', $reporteData['id']);
    $sheet->setCellValue('C3', 'Descripción:');
    $sheet->setCellValue('D3', substr($reporteData['descripcion'], 0, 50));
    $sheet->getStyle('A3:D3')->applyFromArray($infoStyle);
    
    // Preparar datos para la tabla
    $headers = [];
    $data = [];
    
    // Verificar que tengamos resultados
    if (empty($reporteData['resultados'])) {
        // Si no hay resultados, mostrar mensaje
        $sheet->setCellValue('A5', 'No se encontraron datos para este reporte');
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('A5')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
    } else {
        // Depuración: contar resultados
        $totalResultados = count($reporteData['resultados']);
        
        if ($reporteData['columnas'] && count($reporteData['columnas']) > 0) {
            // Mapeo de columnas a nombres profesionales
            $columnasMap = [
                'nombres' => 'Nombres',
                'apellido_paterno' => 'Apellido Paterno',
                'apellido_materno' => 'Apellido Materno',
                'genero' => 'Género',
                'edad' => 'Edad',
                'fecha_nacimiento' => 'Fecha Nac.',
                'carnet_identidad' => 'CI',
                'rude' => 'RUDE',
                'nivel' => 'Nivel',
                'curso' => 'Curso',
                'paralelo' => 'Paralelo'
            ];
            
            // Crear encabezados
            foreach ($reporteData['columnas'] as $col) {
                $headers[] = $columnasMap[$col] ?? $col;
            }
            
            // Crear datos
            foreach ($reporteData['resultados'] as $index => $fila) {
                $rowData = [];
                foreach ($reporteData['columnas'] as $col) {
                    $valor = $fila[$col] ?? '';
                    
                    // Formatear campos específicos
                    if ($col === 'fecha_nacimiento' && $valor) {
                        $valor = date('d/m/Y', strtotime($valor));
                    } elseif ($col === 'genero' && $valor) {
                        $valor = strtoupper($valor);
                    } elseif ($col === 'edad' && $valor) {
                        $valor = $valor . ' años';
                    }
                    
                    $rowData[] = $valor;
                }
                $data[] = $rowData;
            }
        } else {
            // Columnas por defecto
            $headers = ['Nombres', 'Apellido Paterno', 'Apellido Materno', 'Edad', 'Curso', 'Paralelo'];
            foreach ($reporteData['resultados'] as $fila) {
                $data[] = [
                    $fila['nombres'] ?? '',
                    $fila['apellido_paterno'] ?? '',
                    $fila['apellido_materno'] ?? '',
                    ($fila['edad'] ?? '') . ' años',
                    $fila['curso'] ?? '',
                    $fila['paralelo'] ?? ''
                ];
            }
        }
    }
    
    if (!empty($headers) && !empty($data)) {
        // Escribir encabezados de la tabla
        $startRow = 5;
        $col = 1;
        foreach ($headers as $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . $startRow, $header);
            $col++;
        }
        
        // Aplicar estilo a encabezados
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A' . $startRow . ':' . $lastCol . $startRow)->applyFromArray($headerStyle);
        
        // Escribir datos
        $row = $startRow + 1;
        foreach ($data as $rowData) {
            $col = 1;
            foreach ($rowData as $index => $valor) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($colLetter . $row, $valor);
                
                // Aplicar estilo según tipo de dato
                if (is_numeric($valor) || in_array($reporteData['columnas'][$index] ?? '', ['edad', 'genero', 'curso', 'paralelo'])) {
                    $sheet->getStyle($colLetter . $row)->applyFromArray($numberStyle);
                } else {
                    $sheet->getStyle($colLetter . $row)->applyFromArray($dataStyle);
                }
                
                $col++;
            }
            $row++;
        }
        
        // Aplicar estilos alternados para mejor legibilidad
        for ($r = $startRow + 1; $r < $row; $r++) {
            if ($r % 2 == 0) {
                $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FAFAFA'));
            }
        }
        
        // Ajustar ancho de columnas automáticamente
        for ($col = 1; $col <= count($headers); $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        
        // Ajustar altura de filas
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(15);
        $sheet->getRowDimension(3)->setRowHeight(15);
        $sheet->getRowDimension($startRow)->setRowHeight(18);
        
        // Agregar pie de página con estadísticas
        $footerRow = $row + 1;
        $sheet->setCellValue('A' . $footerRow, 'Total de registros:');
        $sheet->setCellValue('B' . $footerRow, count($data));
        $sheet->setCellValue('C' . $footerRow, 'Generado:');
        $sheet->setCellValue('D' . $footerRow, date('d/m/Y H:i:s'));
        $sheet->mergeCells('C' . $footerRow . ':' . $lastCol . $footerRow);
        $sheet->getStyle('A' . $footerRow . ':' . $lastCol . $footerRow)->applyFromArray($infoStyle);
    }
    
    // Limpiar buffer de salida nuevamente antes de generar Excel
    ob_clean();
    
    // Configurar encabezados para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_Temporal_' . date('Y-m-d_H-i-s') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Deshabilitar output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Generar y enviar archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    header('Location: constructor_reporte.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>
