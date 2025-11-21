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

// Verificar si se proporcionó un ID de reporte
$id_reporte = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id_reporte || empty($id_reporte)) {
    header('Location: reportes.php?error=No se proporcionó un ID de reporte');
    exit;
}

try {
    // Verificar si se enviaron datos ordenados desde el cliente
    $sorted_data = isset($_POST['sorted_data']) ? json_decode($_POST['sorted_data'], true) : null;
    
    // Cargar datos del reporte guardado
    $reporte = cargarReporteGuardado($id_reporte);
    
    if (!$reporte) {
        header('Location: reportes.php?error=El reporte no existe');
        exit;
    }
    
    // Extraer datos del nuevo formato
    $columnas = $reporte['columnas'];
    $filtros = $reporte['filtros'];
    $tipo_base = $reporte['tipo_base'];
    
    // Si hay datos ordenados, usarlos directamente
    if ($sorted_data && is_array($sorted_data) && count($sorted_data) > 0) {
        // Reconstruir resultados desde los datos ordenados
        $resultados = [];
        foreach ($sorted_data as $rowData) {
            $row = [];
            foreach ($columnas as $index => $columna) {
                $row[$columna] = $rowData[$index] ?? '';
            }
            $resultados[] = $row;
        }
    } else {
        // Si no hay datos ordenados, usar los resultados originales
        $resultados = $reporte['resultados'];
    }
    
    // Preparar datos para Excel
    $reporteData = [
        'nombre' => htmlspecialchars_decode($reporte['nombre']),
        'descripcion' => htmlspecialchars_decode($reporte['descripcion'] ?? ''),
        'tipo' => $tipo_base == 'info_estudiantil' ? 'Información Estudiantil' : 'Información Académica',
        'fecha' => date('d/m/Y H:i:s'),
        'id' => $id_reporte,
        'columnas' => $columnas,
        'resultados' => $resultados
    ];
    
    // Crear nuevo documento Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator("Sistema Educativo")
        ->setTitle("Reporte - " . $reporteData['nombre'])
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
    if (!empty($reporteData['descripcion'])) {
        $sheet->setCellValue('C3', 'Descripción:');
        $sheet->setCellValue('D3', substr($reporteData['descripcion'], 0, 50) . (strlen($reporteData['descripcion']) > 50 ? '...' : ''));
    }
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
    header('Content-Disposition: attachment;filename="Reporte_' . str_replace(' ', '_', $reporteData['nombre']) . '.xlsx"');
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
    header('Location: reportes.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>
