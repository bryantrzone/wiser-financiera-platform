<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/amortizacion.php';

$user = requireLoginApi();
verificarMetodoHttp('POST');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    enviarRespuestaJson('error', 'Datos inválidos', null, 400);
}

$faltantes = validarCamposRequeridos($data, [
    'cliente_id', 'monto_credito', 'plazo_meses', 'fecha_inicio', 'producto_id'
]);
if ($faltantes) {
    enviarRespuestaJson('error', 'Campos requeridos: ' . implode(', ', $faltantes), null, 422);
}

$tasa_anual_pct = isset($data['tasa_anual_pct']) ? (float) $data['tasa_anual_pct'] : null;
if ($tasa_anual_pct === null || $tasa_anual_pct < 3 || $tasa_anual_pct > 60) {
    enviarRespuestaJson('error', 'La tasa debe estar entre 3% y 60%', null, 422);
}

$monto           = (float) $data['monto_credito'];
$plazo           = (int)   $data['plazo_meses'];
$fecha           = $data['fecha_inicio'];
$producto_id     = (int)   $data['producto_id'];
$cliente_id      = (int)   $data['cliente_id'];
$tasa_anual_dec  = $tasa_anual_pct / 100;
$tasa_mensual_dec = $tasa_anual_dec / 12;

$gastos_contrato = 0;
if (!empty($data['gastos_contrato_activo']) && (int) $data['gastos_contrato_activo'] === 1) {
    $gastos_contrato = min((float) ($data['gastos_contrato_monto'] ?? 0), 15000.00);
    $gastos_contrato = max(0, $gastos_contrato);
}

try {
    $conn = obtenerConexionBaseDatos();

    $stmt2 = $conn->prepare("SELECT comision_apertura FROM productos WHERE id = ? AND activo = 1");
    $stmt2->execute([$producto_id]);
    $producto = $stmt2->fetch();
    $comision_pct = (float) ($producto['comision_apertura'] ?? 0);

    $comision_monto   = $comision_pct > 0 ? round($monto * $comision_pct, 2) : 0;
    $monto_financiado = $monto - $comision_monto - $gastos_contrato;

    $params = [
        'monto_credito'         => $monto_financiado,
        'plazo_meses'           => $plazo,
        'fecha_inicio'          => $fecha,
        'tasa_anual'            => $tasa_anual_dec,
        'comision_apertura_pct' => 0,
    ];

    $pago_mensual = CalculadoraAmortizacion::calcularPMT($tasa_mensual_dec, $plazo, $monto_financiado);
    $periodos     = CalculadoraAmortizacion::generarPeriodos($params);
    $totales      = CalculadoraAmortizacion::calcularTotales($periodos);

    $fecha_dt = new DateTime($fecha);
    $fecha_dt->modify('+' . $plazo * 30 . ' days');
    $fecha_limite = $fecha_dt->format('Y-m-d');

    $conn->beginTransaction();

    $credito_no = CalculadoraAmortizacion::generarCreditoNo($conn);

    $stmtC = $conn->prepare("
        INSERT INTO cotizaciones
            (credito_no, cliente_id, user_id, fecha_inicio, monto_credito, gastos_contrato,
             plazo_meses, plazo_dias, tasa_id, tasa_anual_custom, producto_id, moneda,
             pago_mensual, total_intereses, total_a_pagar, fecha_limite_pago)
        VALUES
            (:cn, :cli, :uid, :fi, :monto, :gc,
             :plazo, :dias, NULL, :tac, :pid, 'Pesos Mexicanos',
             :pm, :ti, :tp, :fl)
    ");
    $stmtC->execute([
        ':cn'    => $credito_no,
        ':cli'   => $cliente_id,
        ':uid'   => $user['id'],
        ':fi'    => $fecha,
        ':monto' => $monto,
        ':gc'    => $gastos_contrato,
        ':plazo' => $plazo,
        ':dias'  => $plazo * 30,
        ':tac'   => $tasa_anual_dec,
        ':pid'   => $producto_id,
        ':pm'    => $pago_mensual,
        ':ti'    => $totales['total_intereses'],
        ':tp'    => $totales['total_a_pagar'],
        ':fl'    => $fecha_limite,
    ]);

    $cotizacion_id = (int) $conn->lastInsertId();

    $stmtP = $conn->prepare("
        INSERT INTO cotizacion_periodos
            (cotizacion_id, periodo, fecha_inicio_mes, fecha_vencimiento, fecha_corte,
             dias, saldo_insoluto, pago_capital, interes_ordinario, iva_interes,
             importe_comision, excedente_pagado, pago_anticipado, pago_calculado, pago_integrado)
        VALUES
            (:cid, :per, :fim, :fv, :fc,
             :dias, :si, :pc, :io, :iv,
             :ic, :ep, :pa, :pcalc, :pint)
    ");

    foreach ($periodos as $p) {
        $stmtP->execute([
            ':cid'   => $cotizacion_id,
            ':per'   => $p['periodo'],
            ':fim'   => $p['fecha_inicio_mes'],
            ':fv'    => $p['fecha_vencimiento'],
            ':fc'    => $p['fecha_corte'],
            ':dias'  => $p['dias'],
            ':si'    => $p['saldo_insoluto'],
            ':pc'    => $p['pago_capital'],
            ':io'    => $p['interes_ordinario'],
            ':iv'    => $p['iva_interes'],
            ':ic'    => $p['importe_comision'],
            ':ep'    => $p['excedente_pagado'],
            ':pa'    => $p['pago_anticipado'],
            ':pcalc' => $p['pago_calculado'],
            ':pint'  => $p['pago_integrado'],
        ]);
    }

    $conn->commit();

    enviarRespuestaJson('success', 'Cotización guardada correctamente', [
        'cotizacion_id' => $cotizacion_id,
        'credito_no'    => $credito_no,
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error guardando cotización: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al guardar: ' . $e->getMessage(), null, 500);
}
