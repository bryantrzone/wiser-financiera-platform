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
if (!$id) { http_response_code(400); die('ID requerido'); }

try {
    $conn = obtenerConexionBaseDatos();
    $stmt = $conn->prepare("
        SELECT c.*, cl.nombre AS cliente_nombre, cl.empresa, cl.rfc,
               COALESCE(t.tasa_anual, c.tasa_anual_custom) AS tasa_anual,
               p.nombre AS producto_nombre, p.reca AS producto_reca
        FROM cotizaciones c
        JOIN clientes cl      ON cl.id = c.cliente_id
        LEFT JOIN tasas t     ON t.id  = c.tasa_id
        LEFT JOIN productos p ON p.id  = c.producto_id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $cot = $stmt->fetch();
    if (!$cot) { http_response_code(404); die('Cotización no encontrada'); }

    $stmt2 = $conn->prepare("SELECT * FROM cotizacion_periodos WHERE cotizacion_id = ? ORDER BY periodo ASC");
    $stmt2->execute([$id]);
    $periodos = $stmt2->fetchAll();
} catch (Exception $e) {
    http_response_code(500); die('Error: ' . $e->getMessage());
}

$logoPath  = __DIR__ . '/../../assets/img/logo-website-transparente.png';
$tasaPct   = round((float)$cot['tasa_anual'] * 100, 0);
$fechaElab = date('d/m/Y');
$horaElab  = date('H:i');
$reca      = trim($cot['producto_reca'] ?? '');

// ── Constantes de layout ───────────────────────────────────────────────────────
$LM       = 10;
$RM       = 10;
$TM       = 36;
$FM       = 14;
$WFI_NO   = 'WFI230707229';
$ADDR     = 'Primer Retorno de Osa Menor No. 2 Int. OF-102 A, Reserva Territorial Atlixcáyotl, San Andrés Cholula, Puebla, C.P. 72820';

// ── Clase PDF ─────────────────────────────────────────────────────────────────
class WiserPDF extends TCPDF {
    public string $wLogo  = '';
    public string $wReca  = '';
    public string $wFecha = '';
    public string $wHora  = '';
    public string $wWfi   = '';
    public string $wAddr  = '';
    public float  $wLM    = 10;
    public float  $wFM    = 14;

    public function Header(): void {
        $lm = $this->wLM;
        $pw = $this->getPageWidth();
        $cw = $pw - $lm * 2 - 42;

        // Logo
        if ($this->wLogo && file_exists($this->wLogo)) {
            $this->Image($this->wLogo, $lm, 6, 38, 16, 'PNG');
        }

        // Razón social
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(20, 20, 20);
        $this->SetXY($lm + 42, 6);
        $this->Cell($cw, 6, 'WISER FINANCIERA, S.A.P.I. DE C.V., SOFOM, E.N.R.', 0, 1, 'C');

        // Número registro
        $this->SetFont('helvetica', 'B', 8.5);
        $this->SetX($lm + 42);
        $this->Cell($cw, 5, $this->wWfi, 0, 1, 'C');

        // Dirección
        $this->SetFont('helvetica', '', 6);
        $this->SetTextColor(110, 110, 110);
        $this->SetX($lm + 42);
        $this->Cell($cw, 4, $this->wAddr, 0, 1, 'C');

        // Separador
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line($lm, 23, $pw - $lm, 23);

        // Título
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(20, 20, 20);
        $this->SetXY($lm, 24);
        $this->Cell(0, 7, 'Tabla de Amortización', 0, 1, 'C');
        $this->Line($lm, $this->GetY(), $pw - $lm, $this->GetY());
    }

    public function Footer(): void {
        $lm = $this->wLM;
        $pw = $this->getPageWidth();
        $cw = ($pw - $lm * 2) / 3;

        // SetY negativo = posición desde el fondo de la página (patrón estándar TCPDF)
        $this->SetY(-12);
        $y = $this->GetY();

        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line($lm, $y, $pw - $lm, $y);

        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY($lm, $y + 1);
        $this->Cell($cw, 4, 'Fecha de elaboración: ' . $this->wFecha, 0, 0, 'L');
        $this->Cell($cw, 4, 'Hora de elaboración: ' . $this->wHora . '   |   Tabla de Amortización', 0, 0, 'C');
        $this->Cell($cw, 4, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 1, 'R');

        $this->SetFont('helvetica', 'B', 6.5);
        $this->SetX($lm);
        $recaText = $this->wReca !== '' ? $this->wReca : '—';
        $this->Cell(0, 4, 'RECA: ' . $recaText, 0, 0, 'C');
    }
}

// ── Instancia ─────────────────────────────────────────────────────────────────
$pdf = new WiserPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->wLogo  = $logoPath;
$pdf->wReca  = $reca;
$pdf->wFecha = $fechaElab;
$pdf->wHora  = $horaElab;
$pdf->wWfi   = $WFI_NO;
$pdf->wAddr  = $ADDR;
$pdf->wLM    = $LM;
$pdf->wFM    = $FM;
$pdf->SetCreator('Wiser Financiera');
$pdf->SetAuthor('Wiser Financiera');
$pdf->SetTitle('Cotización ' . ($cot['credito_no'] ?? ''));
$pdf->SetMargins($LM, $TM, $RM);
$pdf->SetAutoPageBreak(true, $FM);
$pdf->SetHeaderMargin(4);
$pdf->SetFooterMargin($FM);
$pdf->AddPage();

// ── Datos del crédito ─────────────────────────────────────────────────────────
$fechaInicio = CalculadoraAmortizacion::formatearFechaMx($cot['fecha_inicio']);
$fechaLimite = CalculadoraAmortizacion::formatearFechaMx($cot['fecha_limite_pago']);
$pw   = $pdf->getPageWidth();
$half = ($pw - $LM * 2) / 2;
$lbW  = 42;
$vW   = $half - $lbW - 2;
$rowH = 5.5;

$gastosContrato = (float)($cot['gastos_contrato'] ?? 0);
$montoFinanciado = (float)$cot['monto_credito'] - $gastosContrato;

$leftCol = [
    ['Cliente:',              $cot['cliente_nombre'] . ($cot['empresa'] ? ' — ' . $cot['empresa'] : '')],
    ['Crédito No.:',          $cot['credito_no'] ?? ''],
    ['Fecha Inicio:',         $fechaInicio],
    ['Fecha Límite de Pago:', $fechaLimite],
    ['Monto del Crédito:',    '$ ' . number_format((float)$cot['monto_credito'], 2)],
    ['Tasa Interés:',         $tasaPct . '%'],
    ['Tipo de Interés:',      'Anual ordinario Fijo'],
];
$rightCol = [
    ['RFC:',           $cot['rfc'] ?: '----'],
    ['Plazo:',         '30 días'],
    ['Plazo en Días:', $cot['plazo_dias']],
    ['# Meses:',       $cot['plazo_meses']],
    ['Moneda:',        'Pesos Mexicanos'],
    ['Producto:',      $cot['producto_nombre'] ?? 'N/A'],
    ['', ''],
];

if ($gastosContrato > 0) {
    $leftCol[] = ['Gastos por Contrato:', '$ ' . number_format($gastosContrato, 2)];
    $leftCol[] = ['Monto Financiado:',    '$ ' . number_format($montoFinanciado, 2)];
    $rightCol[] = ['', ''];
    $rightCol[] = ['', ''];
}

$y0 = $pdf->GetY() + 2;

for ($r = 0; $r < count($leftCol); $r++) {
    $ry = $y0 + $r * $rowH;

    $pdf->SetXY($LM, $ry);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell($lbW, $rowH, $leftCol[$r][0], 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(20, 20, 20);
    $pdf->Cell($vW, $rowH, $leftCol[$r][1], 0, 0, 'L');

    $pdf->SetX($LM + $half + 2);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell($lbW, $rowH, $rightCol[$r][0], 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(20, 20, 20);
    $pdf->Cell($vW, $rowH, $rightCol[$r][1], 0, 0, 'L');
}

$pdf->SetY($y0 + count($leftCol) * $rowH + 5);

// ── Tabla de amortización ─────────────────────────────────────────────────────
// Anchos: 13+22+10+26+25+26+21+22+25 = 190mm (A4 portrait con márgenes 10mm)
$cols = [
    ['label' => 'Periodo',                        'w' => 13, 'align' => 'C'],
    ['label' => "Fecha\nVencimiento",             'w' => 22, 'align' => 'C'],
    ['label' => 'Días',                           'w' => 10, 'align' => 'C'],
    ['label' => 'Saldo Insoluto',                 'w' => 26, 'align' => 'R'],
    ['label' => 'Pago de Capital',                'w' => 25, 'align' => 'R'],
    ['label' => "Interés Ordinario\nGenerado",    'w' => 26, 'align' => 'R'],
    ['label' => 'IVA Interés',                    'w' => 21, 'align' => 'R'],
    ['label' => "Importe\nComisión",              'w' => 22, 'align' => 'R'],
    ['label' => 'Pago Integrado',                 'w' => 25, 'align' => 'R'],
];

$headerH = 9;
$rowH2   = 5.5;
$fill    = false;

function drawAmortHeader(WiserPDF $pdf, array $cols, float $lm, float $hh): void {
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->SetFillColor(30, 58, 138);
    $pdf->SetTextColor(255, 255, 255);
    $y = $pdf->GetY();
    $x = $lm;
    foreach ($cols as $col) {
        $pdf->MultiCell($col['w'], $hh, $col['label'], 1, 'C', true, 0, $x, $y,
                        true, 0, false, true, $hh, 'M');
        $x += $col['w'];
    }
    $pdf->SetXY($lm, $y + $hh);
    $pdf->SetTextColor(20, 20, 20);
}

drawAmortHeader($pdf, $cols, $LM, $headerH);

foreach ($periodos as $p) {
    if ($pdf->GetY() + $rowH2 > $pdf->getPageHeight() - $FM - 2) {
        $pdf->AddPage();
        drawAmortHeader($pdf, $cols, $LM, $headerH);
        $fill = false;
    }

    if ($fill) {
        $pdf->SetFillColor(240, 244, 255);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(20, 20, 20);

    $comision = (float)$p['importe_comision'];
    $row = [
        $p['periodo'],
        CalculadoraAmortizacion::formatearFechaMx($p['fecha_vencimiento']),
        $p['dias'],
        '$ ' . number_format((float)$p['saldo_insoluto'],    2),
        '$ ' . number_format((float)$p['pago_capital'],      2),
        '$ ' . number_format((float)$p['interes_ordinario'], 2),
        '$ ' . number_format((float)$p['iva_interes'],       2),
        $comision > 0 ? '$ ' . number_format($comision, 2) : '-',
        '$ ' . number_format((float)$p['pago_integrado'],    2),
    ];

    foreach ($cols as $i => $col) {
        $pdf->Cell($col['w'], $rowH2, $row[$i], 'LR', 0, $col['align'], $fill);
    }
    $pdf->Ln();
    $fill = !$fill;
}

// Borde inferior de la tabla
foreach ($cols as $col) {
    $pdf->Cell($col['w'], 0, '', 'T', 0);
}
$pdf->Ln(2);

// Fila de totales
$pdf->SetFont('helvetica', 'B', 6.5);
$pdf->SetFillColor(220, 220, 240);
$pdf->SetTextColor(20, 20, 20);
$totRow = [
    'TOTALES', '', '',
    '',
    '$ ' . number_format(array_sum(array_column($periodos, 'pago_capital')),      2),
    '$ ' . number_format(array_sum(array_column($periodos, 'interes_ordinario')), 2),
    '$ ' . number_format(array_sum(array_column($periodos, 'iva_interes')),       2),
    '$ ' . number_format(array_sum(array_column($periodos, 'importe_comision')),  2),
    '$ ' . number_format(array_sum(array_column($periodos, 'pago_integrado')),    2),
];
foreach ($cols as $i => $col) {
    $pdf->Cell($col['w'], $rowH2 + 1, $totRow[$i], 1, 0, $col['align'], true);
}
$pdf->Ln();

// ── Salida ────────────────────────────────────────────────────────────────────
$filename = 'Cotizacion_' . ($cot['credito_no'] ?? 'WF') . '_'
          . preg_replace('/[^a-zA-Z0-9]/', '', $cot['cliente_nombre'] ?? '') . '.pdf';
$pdf->Output($filename, 'D');
