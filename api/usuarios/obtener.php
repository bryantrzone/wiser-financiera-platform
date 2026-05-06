<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = requireLoginApi();
if ($user['role'] !== ROLE_ADMIN) {
    enviarRespuestaJson('error', 'Acceso denegado', null, 403);
}

verificarMetodoHttp('GET');
$id = (int)($_GET['id'] ?? 0);
if (!$id) enviarRespuestaJson('error', 'ID requerido', null, 400);

try {
    $conn = obtenerConexionBaseDatos();
    $stmt = $conn->prepare(
        "SELECT id, full_name, email, role, active, last_login, created_at FROM users WHERE id = ?"
    );
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) enviarRespuestaJson('error', 'Usuario no encontrado', null, 404);
    enviarRespuestaJson('success', 'OK', $u);
} catch (Exception $e) {
    enviarRespuestaJson('error', 'Error al obtener usuario', null, 500);
}
