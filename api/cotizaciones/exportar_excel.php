<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/amortizacion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    die('ID de cotización requerido');
}

try {
    $conn = obtenerConexionBaseDatos();

    $stmt = $conn->prepare("
        SELECT c.*, cl.nombre AS cliente_nombre, cl.empresa, cl.rfc,
               COALESCE(t.descripcion, 'Tasa manual') AS tasa_desc,
               COALESCE(t.tasa_anual, c.tasa_anual_custom) AS tasa_anual,
               p.nombre AS producto_nombre
        FROM cotizaciones c
        JOIN clientes cl      ON cl.id = c.cliente_id
        LEFT JOIN tasas t     ON t.id  = c.tasa_id
        LEFT JOIN productos p ON p.id  = c.producto_id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $cot = $stmt->fetch();

    if (!$cot) {
        http_response_code(404);
        die('Cotización no encontrada');
    }

    $stmt2 = $conn->prepare("SELECT * FROM cotizacion_periodos WHERE cotizacion_id = ? ORDER BY periodo ASC");
    $stmt2->execute([$id]);
    $periodos = $stmt2->fetchAll();

} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}

// ── Spreadsheet ────────────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Amortización');

$tasaPct        = round((float)$cot['tasa_anual'] * 100, 0);
$gastosContrato = (float)($cot['gastos_contrato'] ?? 0);
$montoFinanciado = (float)$cot['monto_credito'] - $gastosContrato;

// Cabecera de datos
$infoRows = [
    ['Cliente:',           $cot['cliente_nombre'], '',                          'RFC:',          $cot['rfc'] ?? '----'],
    ['Crédito No.',        $cot['credito_no'],      '',                          'Plazo:',        $cot['plazo_meses'] . ' meses'],
    ['Fecha Inicio:',      CalculadoraAmortizacion::formatearFechaMx($cot['fecha_inicio']),
     '',                   'Plazo en Días:',        $cot['plazo_dias']],
    ['Fecha Límite:',      CalculadoraAmortizacion::formatearFechaMx($cot['fecha_limite_pago']),
     '',                   '# Meses:',              $cot['plazo_meses']],
    ['Monto del Crédito:', (float)$cot['monto_credito'], '', 'Moneda:',         $cot['moneda']],
    ['Tasa Interés:',      $tasaPct . '%',          '',                          'Producto:',     $cot['producto_nombre']],
    ['Tipo de Interés:',   'Anual ordinario Fijo',  '',                          'Pago Mensual:', (float)$cot['pago_mensual']],
    ['',                   '',                      '',                          'Total Intereses:', (float)$cot['total_intereses']],
    ['',                   '',                      '',                          'Total a Pagar:', (float)$cot['total_a_pagar']],
];

if ($gastosContrato > 0) {
    $infoRows[] = ['Gastos por Contrato:', $gastosContrato, '', 'Monto Financiado:', $montoFinanciado];
}

$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', 'Tabla de Amortización — ' . $cot['credito_no']);
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A8A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(22);

$row = 2;
foreach ($infoRows as $ir) {
    $sheet->setCellValue('A' . $row, $ir[0]);
    $sheet->setCellValue('B' . $row, $ir[1]);
    $sheet->setCellValue('D' . $row, $ir[3]);
    $sheet->setCellValue('E' . $row, $ir[4]);

    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row)->getFont()->setBold(true);

    if (is_float($ir[1]) && $ir[1] > 0) {
        $sheet->getStyle('B' . $row)->getNumberFormat()
              ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    }
    if (is_float($ir[4]) && $ir[4] > 0) {
        $sheet->getStyle('E' . $row)->getNumberFormat()
              ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    }
    $row++;
}

$row++;

// Encabezados de tabla
$headers = [
    'A' => '#', 'B' => 'Periodo', 'C' => 'F. Vencimiento', 'D' => 'F. Corte',
    'E' => 'Días', 'F' => 'Saldo Insoluto', 'G' => 'Pago Capital',
    'H' => 'Interés Ordinario', 'I' => 'IVA Interés',
    'J' => 'Pago Calculado', 'K' => 'Pago Integrado',
];

$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
];

$headerRow = $row;
foreach ($headers as $col => $label) {
    $sheet->setCellValue($col . $row, $label);
}
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($headerStyle);
$sheet->getRowDimension($row)->setRowHeight(18);
$row++;

$moneyFmt = '"$"#,##0.00';
$moneyCols = ['F', 'G', 'H', 'I', 'J', 'K'];
$fillOn  = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]];
$fillOff = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']]];

foreach ($periodos as $idx => $p) {
    $sheet->setCellValue('A' . $row, $idx + 1);
    $sheet->setCellValue('B' . $row, $p['periodo']);
    $sheet->setCellValue('C' . $row, CalculadoraAmortizacion::formatearFechaMx($p['fecha_vencimiento']));
    $sheet->setCellValue('D' . $row, CalculadoraAmortizacion::formatearFechaMx($p['fecha_corte']));
    $sheet->setCellValue('E' . $row, $p['dias']);
    $sheet->setCellValue('F' . $row, (float)$p['saldo_insoluto']);
    $sheet->setCellValue('G' . $row, (float)$p['pago_capital']);
    $sheet->setCellValue('H' . $row, (float)$p['interes_ordinario']);
    $sheet->setCellValue('I' . $row, (float)$p['iva_interes']);
    $sheet->setCellValue('J' . $row, (float)$p['pago_calculado']);
    $sheet->setCellValue('K' . $row, (float)$p['pago_integrado']);

    $fillStyle = ($idx % 2 === 0) ? $fillOff : $fillOn;
    $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($fillStyle);

    foreach ($moneyCols as $mc) {
        $sheet->getStyle($mc . $row)->getNumberFormat()->setFormatCode($moneyFmt);
    }
    $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DDDDDD');
    $row++;
}

// Fila de totales
$totStyle = [
    'font'    => ['bold' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1E3A8A']]],
];
$sheet->setCellValue('A' . $row, 'TOTAL');
$sheet->mergeCells('A' . $row . ':E' . $row);
$sheet->setCellValue('F' . $row, '');
$sheet->setCellValue('G' . $row, array_sum(array_column($periodos, 'pago_capital')));
$sheet->setCellValue('H' . $row, array_sum(array_column($periodos, 'interes_ordinario')));
$sheet->setCellValue('I' . $row, array_sum(array_column($periodos, 'iva_interes')));
$sheet->setCellValue('J' . $row, array_sum(array_column($periodos, 'pago_calculado')));
$sheet->setCellValue('K' . $row, array_sum(array_column($periodos, 'pago_integrado')));
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totStyle);
foreach ($moneyCols as $mc) {
    $sheet->getStyle($mc . $row)->getNumberFormat()->setFormatCode($moneyFmt);
}

// Anchos de columna
$widths = ['A'=>6,'B'=>10,'C'=>18,'D'=>16,'E'=>7,'F'=>18,'G'=>16,'H'=>20,'I'=>14,'J'=>16,'K'=>16];
foreach ($widths as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

// ── Salida ─────────────────────────────────────────────────────────────────────
$filename = 'Cotizacion_' . $cot['credito_no'] . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
