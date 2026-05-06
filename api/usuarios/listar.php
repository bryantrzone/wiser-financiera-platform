<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = requireLoginApi();

if ($user['role'] !== ROLE_ADMIN) {
    enviarRespuestaJson('error', 'Acceso denegado', null, 403);
}

verificarMetodoHttp('GET');

$pagina    = max(1, (int)($_GET['pagina']    ?? 1));
$porPagina = min(100, max(1, (int)($_GET['por_pagina'] ?? 15)));
$buscar    = trim($_GET['buscar'] ?? '');
$rol       = trim($_GET['rol']    ?? '');
$activo    = isset($_GET['activo']) && $_GET['activo'] !== '' ? (int)$_GET['activo'] : null;
$offset    = ($pagina - 1) * $porPagina;

try {
    $conn   = obtenerConexionBaseDatos();
    $where  = ['1=1'];
    $params = [];

    if ($buscar !== '') {
        $where[]  = '(full_name LIKE :buscar OR email LIKE :buscar2)';
        $params[':buscar']  = '%' . $buscar . '%';
        $params[':buscar2'] = '%' . $buscar . '%';
    }
    if ($rol !== '') {
        $where[]        = 'role = :rol';
        $params[':rol'] = $rol;
    }
    if ($activo !== null) {
        $where[]           = 'active = :activo';
        $params[':activo'] = $activo;
    }

    $whereStr = implode(' AND ', $where);

    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM users WHERE {$whereStr}");
    $stmtCount->execute($params);
    $total = (int) $stmtCount->fetchColumn();

    $stmtUsers = $conn->prepare(
        "SELECT id, full_name, email, role, active, last_login, created_at
         FROM users WHERE {$whereStr}
         ORDER BY created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) $stmtUsers->bindValue($k, $v);
    $stmtUsers->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
    $stmtUsers->bindValue(':offset', $offset,    PDO::PARAM_INT);
    $stmtUsers->execute();
    $usuarios = $stmtUsers->fetchAll();

    enviarRespuestaJson('success', 'OK', [
        'usuarios'      => $usuarios,
        'total'         => $total,
        'pagina'        => $pagina,
        'por_pagina'    => $porPagina,
        'total_paginas' => (int) ceil($total / $porPagina),
    ]);

} catch (Exception $e) {
    error_log('Error listando usuarios: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al listar usuarios', null, 500);
}
