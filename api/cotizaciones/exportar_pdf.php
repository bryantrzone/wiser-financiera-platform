<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/amortizacion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
               t.descripcion AS tasa_desc, t.tasa_anual,
               p.nombre AS producto_nombre
        FROM cotizaciones c
        JOIN clientes cl ON cl.id = c.cliente_id
        JOIN tasas t     ON t.id  = c.tasa_id
        JOIN productos p ON p.id  = c.producto_id
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
    die('Error al obtener datos: ' . $e->getMessage());
}

// ── TCPDF — Portrait A4 ────────────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Wiser Financiera');
$pdf->SetAuthor('Wiser Financiera');
$pdf->SetTitle('Cotizacion ' . $cot['credito_no']);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 12);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// ── Paleta de colores ──────────────────────────────────────────────────────────
$NAVY   = [30, 58, 138];
$ORANGE = [220, 80, 10];
$BLUE   = [37, 99, 235];
$LGRAY  = [243, 244, 246];
$LBLUE  = [239, 246, 255];
$DARK   = [17, 24, 39];
$MID    = [107, 114, 128];
$HLBLUE = [219, 234, 254];
$WHITE  = [255, 255, 255];

$font     = 'dejavusans';
$logoPath = __DIR__ . '/../../assets/img/logo-wiserfinanciera.png';

$meses    = ['enero','febrero','marzo','abril','mayo','junio','julio',
             'agosto','septiembre','octubre','noviembre','diciembre'];
$fechaHoy = date('j') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');
$tasaPct  = round((float)$cot['tasa_anual'] * 100, 0);

// ══════════════════════════════════════════════════════════════════════════════
// 1. ENCABEZADO
// ══════════════════════════════════════════════════════════════════════════════
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 7, 45, 20, 'PNG');
}

$pdf->SetXY(115, 8);
$pdf->SetFont($font, '', 8);
$pdf->SetTextColor($MID[0], $MID[1], $MID[2]);
$pdf->Cell(85, 5, 'Fecha: ' . $fechaHoy, 0, 1, 'R');

$pdf->SetX(115);
$pdf->SetFont($font, 'B', 12);
$pdf->SetTextColor($NAVY[0], $NAVY[1], $NAVY[2]);
$pdf->Cell(85, 7, 'FOLIO: ' . $cot['credito_no'], 0, 0, 'R');

$pdf->SetDrawColor(210, 214, 220);
$pdf->SetLineWidth(0.4);
$pdf->Line(10, 30, 200, 30);

// ══════════════════════════════════════════════════════════════════════════════
// 2. TÍTULO Y CLIENTE
// ══════════════════════════════════════════════════════════════════════════════
$pdf->SetXY(10, 33);
$pdf->SetFont($font, 'B', 16);
$pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
$pdf->Cell(0, 8, 'Cotización', 0, 1, 'L');

$pdf->SetFont($font, 'B', 10);
$pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
$pdf->Cell(0, 5.5, 'CLIENTE: ' . mb_strtoupper($cot['cliente_nombre'], 'UTF-8'), 0, 1, 'L');
$pdf->Ln(2);

// ══════════════════════════════════════════════════════════════════════════════
// 3. SECCIÓN DE INFORMACIÓN (2 columnas)
// ══════════════════════════════════════════════════════════════════════════════
$yInfo = $pdf->GetY();
$rowH  = 5.5;

// Encabezado columna derecha
$pdf->SetXY(108, $yInfo);
$pdf->SetFont($font, 'B', 9);
$pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
$pdf->Cell(92, 5, 'Esquema a Financiar:', 0, 0, 'L');
$pdf->SetDrawColor(180, 190, 210);
$pdf->SetLineWidth(0.3);
$pdf->Line(108, $yInfo + 5.5, 200, $yInfo + 5.5);

$yData = $yInfo + 6.5;

$arrendatario = $cot['cliente_nombre'] . ($cot['empresa'] ? ' — ' . $cot['empresa'] : '');

$leftRows = [
    ['Arrendadora:',  'Wiser Financiera'],
    ['Arrendatario:', $arrendatario],
    ['RFC:',          $cot['rfc'] ?: '—'],
    ['Documento:',    'COTIZACIÓN'],
    ['Fecha:',        CalculadoraAmortizacion::formatearFechaMx($cot['fecha_inicio'])],
];
$rightRows = [
    ['Tipo de Operación:',  mb_strtoupper($cot['producto_nombre'], 'UTF-8')],
    ['Monto del Crédito:',  '$' . number_format((float)$cot['monto_credito'], 2)],
    ['Plazo:',              $cot['plazo_meses'] . ' meses (' . $cot['plazo_dias'] . ' días)'],
    ['Tasa de Interés:',    $tasaPct . '%  ' . $cot['tasa_desc']],
    ['Pago Mensual:',       '$' . number_format((float)$cot['pago_mensual'], 2)],
];

$nRows = max(count($leftRows), count($rightRows));
for ($r = 0; $r < $nRows; $r++) {
    $y = $yData + $r * $rowH;
    if (isset($leftRows[$r])) {
        $pdf->SetXY(10, $y);
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
        $pdf->Cell(33, $rowH, $leftRows[$r][0], 0, 0, 'L');
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(65, $rowH, $leftRows[$r][1], 0, 0, 'L');
    }
    if (isset($rightRows[$r])) {
        $isLast = ($r === count($rightRows) - 1);
        $pdf->SetXY(108, $y);
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
        $pdf->Cell(40, $rowH, $rightRows[$r][0], 0, 0, 'L');
        if ($isLast) {
            $pdf->SetFont($font, 'B', 9);
            $pdf->SetTextColor($ORANGE[0], $ORANGE[1], $ORANGE[2]);
        } else {
            $pdf->SetFont($font, '', 8);
            $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
        }
        $pdf->Cell(52, $rowH, $rightRows[$r][1], 0, 0, 'L');
    }
}

$infoEndY = $yData + $nRows * $rowH + 3;
$pdf->SetDrawColor(210, 214, 220);
$pdf->SetLineWidth(0.4);
$pdf->Line(10, $infoEndY, 200, $infoEndY);
$pdf->SetY($infoEndY + 4);

// ══════════════════════════════════════════════════════════════════════════════
// 4. CAJAS DE MÉTRICAS CLAVE
// ══════════════════════════════════════════════════════════════════════════════
$boxY = $pdf->GetY();
$boxW = 46.5;
$boxH = 16;

$metrics = [
    ['Monto del Crédito', '$' . number_format((float)$cot['monto_credito'], 2),   false],
    ['Pago Mensual',      '$' . number_format((float)$cot['pago_mensual'], 2),    true],
    ['Total a Pagar',     '$' . number_format((float)$cot['total_a_pagar'], 2),   false],
    ['Total Intereses',   '$' . number_format((float)$cot['total_intereses'], 2), false],
];
foreach ($metrics as $i => [$lbl, $val, $hi]) {
    $bx = 10 + $i * ($boxW + 1);
    if ($hi) {
        $pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
        $pdf->SetTextColor(255, 255, 255);
    } else {
        $pdf->SetFillColor($LGRAY[0], $LGRAY[1], $LGRAY[2]);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
    }
    $pdf->Rect($bx, $boxY, $boxW, $boxH, 'F');
    $pdf->SetXY($bx, $boxY + 3);
    $pdf->SetFont($font, '', 7);
    $pdf->Cell($boxW, 4, $lbl, 0, 1, 'C');
    $pdf->SetXY($bx, $boxY + 8);
    $pdf->SetFont($font, 'B', 9.5);
    $pdf->Cell($boxW, 6, $val, 0, 1, 'C');
}
$pdf->SetY($boxY + $boxH + 5);

// ══════════════════════════════════════════════════════════════════════════════
// 5. TABLA DE AMORTIZACIÓN
// ══════════════════════════════════════════════════════════════════════════════
$pdf->SetFont($font, 'B', 9);
$pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
$pdf->Cell(0, 5, 'Tabla de Amortización', 0, 1, 'L');
$pdf->Ln(1);

// Columnas: suma total = 10+22+10+28+26+26+22+23+23 = 190mm (= 210 - 10 - 10)
$cols = [
    ['label' => '#',                 'w' => 10, 'align' => 'C'],
    ['label' => 'F. Vencimiento',    'w' => 22, 'align' => 'C'],
    ['label' => 'Días',              'w' => 10, 'align' => 'C'],
    ['label' => 'Saldo Insoluto',    'w' => 28, 'align' => 'R'],
    ['label' => 'Pago Capital',      'w' => 26, 'align' => 'R'],
    ['label' => 'Interés Ordinario', 'w' => 26, 'align' => 'R'],
    ['label' => 'IVA Interés',       'w' => 22, 'align' => 'R'],
    ['label' => 'Pago Calculado',    'w' => 23, 'align' => 'R'],
    ['label' => 'Pago Integrado',    'w' => 23, 'align' => 'R'],
];

$pdf->SetFont($font, 'B', 7);
$pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(160, 174, 210);
$pdf->SetLineWidth(0.15);
foreach ($cols as $col) {
    $pdf->Cell($col['w'], 7, $col['label'], 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont($font, '', 6.5);
$rowH2 = 5.5;
$fill  = false;
foreach ($periodos as $p) {
    $pdf->SetFillColor($fill ? $LBLUE[0] : 255, $fill ? $LBLUE[1] : 255, $fill ? $LBLUE[2] : 255);
    $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
    $rowData = [
        $p['periodo'],
        CalculadoraAmortizacion::formatearFechaMx($p['fecha_vencimiento']),
        $p['dias'],
        '$' . number_format((float)$p['saldo_insoluto'],    2),
        '$' . number_format((float)$p['pago_capital'],      2),
        '$' . number_format((float)$p['interes_ordinario'], 2),
        '$' . number_format((float)$p['iva_interes'],       2),
        '$' . number_format((float)$p['pago_calculado'],    2),
        '$' . number_format((float)$p['pago_integrado'],    2),
    ];
    foreach ($cols as $idx => $col) {
        $pdf->Cell($col['w'], $rowH2, $rowData[$idx], 'LR', 0, $col['align'], $fill);
    }
    $pdf->Ln();
    $fill = !$fill;
}

// Fila de totales
$pdf->SetFont($font, 'B', 7);
$pdf->SetFillColor($HLBLUE[0], $HLBLUE[1], $HLBLUE[2]);
$pdf->SetTextColor($NAVY[0], $NAVY[1], $NAVY[2]);
$totalCells = [
    'TOTALES', '', '', '',
    '$' . number_format(array_sum(array_column($periodos, 'pago_capital')),      2),
    '$' . number_format(array_sum(array_column($periodos, 'interes_ordinario')), 2),
    '$' . number_format(array_sum(array_column($periodos, 'iva_interes')),       2),
    '$' . number_format(array_sum(array_column($periodos, 'pago_calculado')),    2),
    '$' . number_format(array_sum(array_column($periodos, 'pago_integrado')),    2),
];
foreach ($cols as $idx => $col) {
    $pdf->Cell($col['w'], $rowH2 + 1, $totalCells[$idx], 1, 0, $idx === 0 ? 'C' : $col['align'], true);
}
$pdf->Ln();

// ══════════════════════════════════════════════════════════════════════════════
// 6. PIE DE PÁGINA
// ══════════════════════════════════════════════════════════════════════════════
$pdf->Ln(4);
$pdf->SetDrawColor(210, 214, 220);
$pdf->SetLineWidth(0.3);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont($font, 'I', 7);
$pdf->SetTextColor($MID[0], $MID[1], $MID[2]);
$pdf->Cell(63, 5, '* Montos en ' . $cot['moneda'], 0, 0, 'L');
$pdf->Cell(64, 5, '* Tipo de interés anual ordinario fijo', 0, 0, 'C');
$pdf->Cell(63, 5, 'Vigencia: ' . CalculadoraAmortizacion::formatearFechaMx($cot['fecha_limite_pago']), 0, 0, 'R');

// ── Salida ─────────────────────────────────────────────────────────────────────
$filename = 'Cotizacion_' . $cot['credito_no'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $cot['cliente_nombre']) . '.pdf';
$pdf->Output($filename, 'D');
