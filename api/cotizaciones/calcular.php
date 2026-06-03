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

$required = ['monto_credito', 'plazo_meses', 'fecha_inicio', 'tasa_id'];
$faltantes = validarCamposRequeridos($data, $required);
if ($faltantes) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Campos requeridos: ' . implode(', ', $faltantes)]);
    exit;
}

$monto      = (float) $data['monto_credito'];
$plazo      = (int)   $data['plazo_meses'];
$fecha      = $data['fecha_inicio'];
$tasa_id    = (int)   $data['tasa_id'];

if ($monto <= 0 || $plazo <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Monto y plazo deben ser mayores a cero']);
    exit;
}

try {
    $conn = obtenerConexionBaseDatos();

    $stmt = $conn->prepare("SELECT * FROM tasas WHERE id = ? AND activo = 1");
    $stmt->execute([$tasa_id]);
    $tasa = $stmt->fetch();

    if (!$tasa) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tasa no encontrada']);
        exit;
    }

    $producto = null;
    if (!empty($data['producto_id'])) {
        $stmt2 = $conn->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
        $stmt2->execute([(int)$data['producto_id']]);
        $producto = $stmt2->fetch();
    }

    $params = [
        'monto_credito' => $monto,
        'plazo_meses'   => $plazo,
        'fecha_inicio'  => $fecha,
        'tasa_anual'    => (float) $tasa['tasa_anual'],
    ];

    $pago_mensual = CalculadoraAmortizacion::calcularPMT((float)$tasa['tasa_anual'] / 12, $plazo, $monto);
    $periodos     = CalculadoraAmortizacion::generarPeriodos($params);
    $totales      = CalculadoraAmortizacion::calcularTotales($periodos);

    $fecha_dt        = new DateTime($fecha);
    $fecha_limite_dt = (clone $fecha_dt)->modify('+' . ($plazo * 30) . ' days');

    $cabecera = [
        'plazo_dias'       => $plazo * 30,
        'plazo_meses'      => $plazo,
        'fecha_inicio'     => $fecha_dt->format('d/m/Y'),
        'fecha_limite'     => $fecha_limite_dt->format('d/m/Y'),
        'monto_credito'    => $monto,
        'tasa_anual'       => (float) $tasa['tasa_anual'],
        'tasa_pct'         => round((float)$tasa['tasa_anual'] * 100, 0) . '%',
        'tasa_mensual'     => (float) $tasa['tasa_mensual'],
        'tasa_descripcion' => $tasa['descripcion'],
        'producto'         => $producto ? $producto['nombre'] : 'N/A',
        'moneda'           => 'Pesos Mexicanos',
        'pago_mensual'     => $pago_mensual,
        'total_intereses'  => $totales['total_intereses'],
        'total_a_pagar'    => $totales['total_a_pagar'],
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
