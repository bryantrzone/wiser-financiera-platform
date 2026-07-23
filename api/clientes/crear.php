<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = requireLoginApi();
verificarMetodoHttp('POST');
$data = json_decode(file_get_contents('php://input'), true);

$faltantes = validarCamposRequeridos($data ?? [], ['nombre']);
if ($faltantes) {
    enviarRespuestaJson('error', 'Campos requeridos: ' . implode(', ', $faltantes), null, 422);
}

$nombre       = sanitizarEntrada($data['nombre']);
$empresa      = sanitizarEntrada($data['empresa'] ?? '');
$rfc          = sanitizarEntrada($data['rfc'] ?? '');
$email        = sanitizarEntrada($data['email'] ?? '');
$telefono     = sanitizarEntrada($data['telefono'] ?? '');
$tipo_cliente = ($data['tipo_cliente'] ?? 'interno') === 'externo' ? 'externo' : 'interno';

if ($nombre === '') {
    enviarRespuestaJson('error', 'El nombre es requerido', null, 422);
}

try {
    $conn = obtenerConexionBaseDatos();
    $stmt = $conn->prepare(
        "INSERT INTO clientes (nombre, empresa, rfc, email, telefono, tipo_cliente)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nombre, $empresa, $rfc, $email, $telefono, $tipo_cliente]);

    enviarRespuestaJson('success', 'Cliente creado correctamente', [
        'id'           => (int) $conn->lastInsertId(),
        'nombre'       => $nombre,
        'empresa'      => $empresa,
        'tipo_cliente' => $tipo_cliente,
    ]);
} catch (Exception $e) {
    error_log('Error creando cliente: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al crear el cliente', null, 500);
}
