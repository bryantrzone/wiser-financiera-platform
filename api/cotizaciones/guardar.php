<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = requireLoginApi();
verificarMetodoHttp('POST');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    enviarRespuestaJson('error', 'Datos inválidos', null, 400);
}

$faltantes = validarCamposRequeridos($data, ['costo_unitario', 'plazo_meses', 'cliente_nombre']);
if ($faltantes) {
    enviarRespuestaJson('error', 'Campos requeridos: ' . implode(', ', $faltantes), null, 422);
}

try {
    $conn  = obtenerConexionBaseDatos();
    $folio = generarFolioUnico();

    $conn->beginTransaction();

    // Header
    $stmtH = $conn->prepare("
        INSERT INTO cotizacion_header
            (folio, user_id, estado, tipo_financiamiento,
             cliente_nombre, cliente_empresa, cliente_rfc, cliente_email, cliente_telefono,
             moneda, tipo_cambio, notas, fecha_vencimiento)
        VALUES
            (:folio, :user_id, 'borrador', :tipo_fin,
             :nom, :emp, :rfc, :email, :tel,
             :moneda, :tc, :notas, :fv)
    ");
    $stmtH->execute([
        ':folio'   => $folio,
        ':user_id' => $user['id'],
        ':tipo_fin'=> sanitizarEntrada($data['tipo_financiamiento'] ?? 'arrendamiento_financiero'),
        ':nom'     => sanitizarEntrada($data['cliente_nombre']   ?? ''),
        ':emp'     => sanitizarEntrada($data['cliente_empresa']  ?? ''),
        ':rfc'     => sanitizarEntrada($data['cliente_rfc']      ?? ''),
        ':email'   => sanitizarEntrada($data['cliente_email']    ?? ''),
        ':tel'     => sanitizarEntrada($data['cliente_telefono'] ?? ''),
        ':moneda'  => sanitizarEntrada($data['moneda']           ?? 'MXN'),
        ':tc'      => (float)($data['tipo_cambio'] ?? 1),
        ':notas'   => sanitizarEntrada($data['notas']            ?? ''),
        ':fv'      => !empty($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null,
    ]);

    $cotizacionId = (int) $conn->lastInsertId();

    // Detail
    $stmtD = $conn->prepare("
        INSERT INTO cotizacion_detail
            (cotizacion_id, tipo_equipo, marca, modelo, descripcion,
             cantidad, costo_unitario, anticipo_pct, anticipo_monto,
             plazo_meses, tasa_anual, residual_pct, residual_monto,
             seguro_pct, pago_seguro, pago_equipo, subtotal_mensual, iva_mensual, pago_mensual)
        VALUES
            (:cid, :te, :marca, :modelo, :desc,
             :cant, :costo, :ant_pct, :ant_monto,
             :plazo, :tasa, :res_pct, :res_monto,
             :seg_pct, :seg_pago, :pago_eq, :sub, :iva, :pm)
    ");
    $stmtD->execute([
        ':cid'      => $cotizacionId,
        ':te'       => sanitizarEntrada($data['tipo_equipo']  ?? ''),
        ':marca'    => sanitizarEntrada($data['marca']        ?? ''),
        ':modelo'   => sanitizarEntrada($data['modelo']       ?? ''),
        ':desc'     => sanitizarEntrada($data['descripcion']  ?? ''),
        ':cant'     => (int)($data['cantidad']        ?? 1),
        ':costo'    => (float)($data['costo_unitario'] ?? 0),
        ':ant_pct'  => (float)($data['anticipo_pct']  ?? 0),
        ':ant_monto'=> (float)($data['anticipo_monto'] ?? 0),
        ':plazo'    => (int)($data['plazo_meses']     ?? 24),
        ':tasa'     => (float)($data['tasa_anual']    ?? 0),
        ':res_pct'  => (float)($data['residual_pct']  ?? 20),
        ':res_monto'=> (float)($data['residual_monto'] ?? 0),
        ':seg_pct'  => (float)($data['seguro_pct']    ?? 0),
        ':seg_pago' => (float)($data['pago_seguro']   ?? 0),
        ':pago_eq'  => (float)($data['pago_equipo']   ?? 0),
        ':sub'      => (float)($data['subtotal']      ?? 0),
        ':iva'      => (float)($data['iva']            ?? 0),
        ':pm'       => (float)($data['pago_mensual']  ?? 0),
    ]);

    $conn->commit();

    enviarRespuestaJson('success', 'Cotización guardada correctamente', [
        'id'    => $cotizacionId,
        'folio' => $folio,
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log('Error guardando cotización: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al guardar la cotización', null, 500);
}
