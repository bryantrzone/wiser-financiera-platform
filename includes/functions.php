<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Singleton de conexión PDO
function obtenerConexionBaseDatos(): PDO {
    static $conn = null;
    if ($conn === null) {
        $db   = new Database();
        $conn = $db->getConnection();
    }
    return $conn;
}

// Respuesta JSON estandarizada
function enviarRespuestaJson(string $status, string $message, $data = null, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'    => $status,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Iniciar sesión de forma segura
function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

// Sanitizar entrada de usuario
function sanitizarEntrada($data) {
    if (is_array($data)) {
        return array_map('sanitizarEntrada', $data);
    }
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

// Validar campos requeridos — devuelve array de campos faltantes
function validarCamposRequeridos(array $data, array $campos): array {
    $faltantes = [];
    foreach ($campos as $campo) {
        if (!isset($data[$campo]) || $data[$campo] === '' || $data[$campo] === null) {
            $faltantes[] = $campo;
        }
    }
    return $faltantes;
}

// Cache-busting para assets JS/CSS
function jsScript(string $path): string {
    $fullPath = __DIR__ . '/../' . ltrim($path, '/');
    $ts       = file_exists($fullPath) ? filemtime($fullPath) : time();
    return $path . '?v=' . $ts;
}

// Formatear moneda
function formatearMoneda(float $amount, string $currency = 'MXN'): string {
    $sym = $currency === 'USD' ? '$' : '$';
    return $sym . number_format($amount, 2) . ' ' . $currency;
}

// Generar folio único WF-YYYY-XXXXX
function generarFolioUnico(): string {
    try {
        $conn  = obtenerConexionBaseDatos();
        $anio  = date('Y');
        $stmt  = $conn->prepare(
            "SELECT COUNT(*) FROM cotizacion_header WHERE folio LIKE :patron"
        );
        $stmt->execute([':patron' => FOLIO_PREFIX . "-{$anio}-%"]);
        $count = (int) $stmt->fetchColumn();
        return FOLIO_PREFIX . "-{$anio}-" . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return FOLIO_PREFIX . '-' . date('Ymd') . '-' . mt_rand(10000, 99999);
    }
}

// Obtener datos completos del usuario de sesión desde BD
function obtenerUsuarioDeSesion(): ?array {
    iniciarSesion();
    if (empty($_SESSION['user_id'])) return null;
    try {
        $conn = obtenerConexionBaseDatos();
        $stmt = $conn->prepare("SELECT id, full_name, email, role, active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

// Verificar método HTTP
function verificarMetodoHttp(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        enviarRespuestaJson('error', 'Método no permitido', null, 405);
    }
}

// Requerir sesión activa para API endpoints JSON
function requireLoginApi(): array {
    iniciarSesion();
    if (empty($_SESSION['user_id']) || empty($_SESSION['expires']) || time() > $_SESSION['expires']) {
        enviarRespuestaJson('error', 'No autenticado', null, 401);
    }
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? 'Usuario',
        'email'     => $_SESSION['email']     ?? '',
        'role'      => $_SESSION['role']       ?? ROLE_VENDOR,
    ];
}
