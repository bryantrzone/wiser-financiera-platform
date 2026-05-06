<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = requireLoginApi();
if ($user['role'] !== ROLE_ADMIN) {
    enviarRespuestaJson('error', 'Acceso denegado', null, 403);
}

verificarMetodoHttp('POST');
$data = json_decode(file_get_contents('php://input'), true);

$faltantes = validarCamposRequeridos($data ?? [], ['full_name', 'email', 'password', 'role']);
if ($faltantes) {
    enviarRespuestaJson('error', 'Campos requeridos: ' . implode(', ', $faltantes), null, 422);
}

$fullName = sanitizarEntrada($data['full_name']);
$email    = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$password = $data['password'];
$role     = in_array($data['role'], [ROLE_ADMIN, ROLE_VENDOR, ROLE_CLIENT]) ? $data['role'] : ROLE_VENDOR;
$active   = isset($data['active']) ? (int)$data['active'] : 1;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    enviarRespuestaJson('error', 'Correo electrónico inválido', null, 422);
}
if (strlen($password) < 8) {
    enviarRespuestaJson('error', 'La contraseña debe tener al menos 8 caracteres', null, 422);
}

try {
    $conn = obtenerConexionBaseDatos();

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        enviarRespuestaJson('error', 'Ya existe un usuario con ese correo', null, 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO users (full_name, email, password, role, active) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$fullName, $email, $hash, $role, $active]);

    enviarRespuestaJson('success', 'Usuario creado correctamente', ['id' => $conn->lastInsertId()]);

} catch (Exception $e) {
    error_log('Error creando usuario: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al crear el usuario', null, 500);
}
