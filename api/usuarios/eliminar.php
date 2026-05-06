<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = requireLoginApi();
if ($user['role'] !== ROLE_ADMIN) {
    enviarRespuestaJson('error', 'Acceso denegado', null, 403);
}

verificarMetodoHttp('POST');
$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);
if (!$id) enviarRespuestaJson('error', 'ID requerido', null, 400);

// No permitir que el admin se elimine a sí mismo
if ($id === (int)$user['id']) {
    enviarRespuestaJson('error', 'No puedes eliminar tu propia cuenta', null, 400);
}

try {
    $conn = obtenerConexionBaseDatos();
    $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    enviarRespuestaJson('success', 'Usuario eliminado correctamente');
} catch (Exception $e) {
    error_log('Error eliminando usuario: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al eliminar el usuario', null, 500);
}
