<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/amortizacion.php';

header('Content-Type: application/json; charset=utf-8');

requireLoginApi();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$data = array_merge($_GET, $_POST, $body);

$required = ['monto_credito', 'plazo_meses', 'fecha_inicio'];
$faltantes = validarCamposRequeridos($data, $required);
if ($faltantes) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Campos requeridos: ' . implode(', ', $faltantes)]);
    exit;
}

$monto           = (float) $data['monto_credito'];
$plazo           = (int)   $data['plazo_meses'];
$fecha           = $data['fecha_inicio'];
$tasa_anual_pct  = isset($data['tasa_anual_pct']) ? (float) $data['tasa_anual_pct'] : null;

$gastos_contrato = 0;
if (!empty($data['gastos_contrato_activo']) && (int) $data['gastos_contrato_activo'] === 1) {
    $gastos_contrato = min((float) ($data['gastos_contrato_monto'] ?? 0), 15000.00);
    $gastos_contrato = max(0, $gastos_contrato);
}

if ($monto <= 0 || $plazo <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Monto y plazo deben ser mayores a cero']);
    exit;
}

if ($tasa_anual_pct === null || $tasa_anual_pct < 3 || $tasa_anual_pct > 60) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'La tasa debe estar entre 3% y 60%']);
    exit;
}

$tasa_anual_dec  = $tasa_anual_pct / 100;
$tasa_mensual_dec = $tasa_anual_dec / 12;
$tasa_desc       = round($tasa_anual_pct, 2) . '% anual';

try {
    $conn = obtenerConexionBaseDatos();

    $producto = null;
    $comision_pct = 0;
    if (!empty($data['producto_id'])) {
        $stmt2 = $conn->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
        $stmt2->execute([(int)$data['producto_id']]);
        $producto = $stmt2->fetch();
        if ($producto) {
            $comision_pct = (float) ($producto['comision_apertura'] ?? 0);
        }
    }

    $comision_monto   = $comision_pct > 0 ? round($monto * $comision_pct, 2) : 0;
    $monto_financiado = $monto - $comision_monto - $gastos_contrato;

    $aplicar_iva = isset($data['aplicar_iva']) ? (int) $data['aplicar_iva'] : 1;
    $iva_rate    = $aplicar_iva === 0 ? 0.0 : IVA;

    // PMT sobre el monto completo; la comisión se cobra por separado.
    $params = [
        'monto_credito'         => $monto,
        'plazo_meses'           => $plazo,
        'fecha_inicio'          => $fecha,
        'tasa_anual'            => $tasa_anual_dec,
        'comision_apertura_pct' => 0,
        'aplicar_iva'           => $aplicar_iva,
    ];

    $pago_mensual = CalculadoraAmortizacion::calcularPMT($tasa_mensual_dec, $plazo, $monto, $iva_rate);
    $periodos     = CalculadoraAmortizacion::generarPeriodos($params);
    $totales      = CalculadoraAmortizacion::calcularTotales($periodos);

    $fecha_dt        = new DateTime($fecha);
    $fecha_limite_dt = clone $fecha_dt;
    $fecha_limite_dt->modify('+' . ($plazo * 30) . ' days');

    $cabecera = [
        'plazo_dias'        => $plazo * 30,
        'plazo_meses'       => $plazo,
        'fecha_inicio'      => $fecha_dt->format('d/m/Y'),
        'fecha_limite'      => $fecha_limite_dt->format('d/m/Y'),
        'monto_credito'     => $monto,
        'monto_financiado'  => $monto_financiado,
        'comision_monto'    => $comision_monto,
        'gastos_contrato'   => $gastos_contrato,
        'tasa_anual'        => $tasa_anual_dec,
        'tasa_pct'          => round($tasa_anual_pct, 2) . '%',
        'tasa_mensual'      => $tasa_mensual_dec,
        'tasa_descripcion'  => $tasa_desc,
        'producto'          => $producto ? $producto['nombre'] : 'N/A',
        'moneda'            => 'Pesos Mexicanos',
        'pago_mensual'      => $pago_mensual,
        'total_intereses'   => $totales['total_intereses'],
        'total_comision'    => $totales['total_comision'],
        'total_a_pagar'     => $totales['total_a_pagar'],
        'comision_pct'      => $comision_pct,
        'comision_pct_fmt'  => $comision_pct > 0 ? round($comision_pct * 100, 2) . '%' : 'Sin comisión',
    ];

    echo json_encode([
        'status'   => 'ok',
        'cabecera' => $cabecera,
        'periodos' => $periodos,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
}
