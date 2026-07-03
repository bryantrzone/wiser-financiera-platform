<?php
require_once __DIR__ . '/functions.php';

// Autenticar usuario con email y contraseña
function authenticateUser(string $email, string $password): ?array
{
    try {
        $conn = obtenerConexionBaseDatos();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);
            return $user;
        }
        return null;
    } catch (Exception $e) {
        error_log('Error en autenticación: ' . $e->getMessage());
        return null;
    }
}

// Crear sesión de usuario
function createUserSession(array $user): void
{
    iniciarSesion();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['expires'] = time() + SESSION_LIFETIME;

    try {
        $conn = obtenerConexionBaseDatos();
        $sessionId = session_id();
        $expires = date('Y-m-d H:i:s', $_SESSION['expires']);
        $data = json_encode($_SESSION);
        $conn->prepare(
            "INSERT INTO user_sessions (id, user_id, expires, data)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE expires = VALUES(expires), data = VALUES(data)"
        )->execute([$sessionId, $user['id'], $expires, $data]);
    } catch (Exception $e) {
        error_log('Error guardando sesión: ' . $e->getMessage());
    }
}

// Verificar si hay sesión activa
function isLoggedIn(): bool
{
    iniciarSesion();
    if (empty($_SESSION['user_id']) || empty($_SESSION['expires']))
        return false;
    if (time() > $_SESSION['expires']) {
        destroyUserSession();
        return false;
    }
    // Renovar si le quedan menos de 23 h
    if (($_SESSION['expires'] - time()) < (SESSION_LIFETIME - 3600)) {
        $_SESSION['expires'] = time() + SESSION_LIFETIME;
        _updateSessionDb();
    }
    return true;
}

// Destruir sesión
function destroyUserSession(): void
{
    iniciarSesion();
    if (!empty($_SESSION['user_id'])) {
        try {
            $conn = obtenerConexionBaseDatos();
            $conn->prepare("DELETE FROM user_sessions WHERE id = ?")
                ->execute([session_id()]);
        } catch (Exception $e) {
            error_log('Error eliminando sesión: ' . $e->getMessage());
        }
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Redirigir a login si no hay sesión
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . APP_BASE_PATH . '/login.php');
        exit;
    }
}

// Redirigir si no tiene el rol requerido
function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die('Acceso denegado');
    }
}

// Verificar que el usuario tiene alguno de los roles permitidos
function requireAnyRole(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        die('Acceso denegado');
    }
}

// Actualizar expiración de sesión en BD
function _updateSessionDb(): void
{
    try {
        $conn = obtenerConexionBaseDatos();
        $expires = date('Y-m-d H:i:s', $_SESSION['expires']);
        $data = json_encode($_SESSION);
        $conn->prepare("UPDATE user_sessions SET expires = ?, data = ? WHERE id = ?")
            ->execute([$expires, $data, session_id()]);
    } catch (Exception $e) {
        error_log('Error actualizando sesión: ' . $e->getMessage());
    }
}

// Solicitar recuperación de contraseña
function requestPasswordReset(string $email): bool
{
    try {
        $conn = obtenerConexionBaseDatos();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        if (!$stmt->fetch())
            return false;

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?")
            ->execute([$token, $expires, $email]);

        return true;
    } catch (Exception $e) {
        error_log('Error en reset de contraseña: ' . $e->getMessage());
        return false;
    }
}

// Limpiar sesiones expiradas (1% de probabilidad por request)
if (random_int(1, 100) === 1) {
    try {
        $conn = obtenerConexionBaseDatos();
        $conn->exec("DELETE FROM user_sessions WHERE expires < NOW()");
    } catch (Exception $e) { /* silencioso */
    }
}
