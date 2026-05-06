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

try {
    $conn  = obtenerConexionBaseDatos();
    $sets  = [];
    $vals  = [];

    if (!empty($data['full_name'])) {
        $sets[] = 'full_name = ?'; $vals[] = sanitizarEntrada($data['full_name']);
    }
    if (!empty($data['email'])) {
        $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            enviarRespuestaJson('error', 'Correo inválido', null, 422);

        $dup = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $dup->execute([$email, $id]);
        if ($dup->fetch()) enviarRespuestaJson('error', 'Ese correo ya está en uso', null, 409);

        $sets[] = 'email = ?'; $vals[] = $email;
    }
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 8)
            enviarRespuestaJson('error', 'La contraseña debe tener al menos 8 caracteres', null, 422);
        $sets[] = 'password = ?'; $vals[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    if (isset($data['role']) && in_array($data['role'], [ROLE_ADMIN, ROLE_VENDOR, ROLE_CLIENT])) {
        $sets[] = 'role = ?'; $vals[] = $data['role'];
    }
    if (isset($data['active'])) {
        $sets[] = 'active = ?'; $vals[] = (int)$data['active'];
    }

    if (!$sets) enviarRespuestaJson('error', 'Nada que actualizar', null, 400);

    $vals[] = $id;
    $conn->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?")
         ->execute($vals);

    enviarRespuestaJson('success', 'Usuario actualizado correctamente');

} catch (Exception $e) {
    error_log('Error actualizando usuario: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al actualizar el usuario', null, 500);
}
