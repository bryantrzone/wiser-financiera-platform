<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLoginApi();
verificarMetodoHttp('GET');

try {
    $conn = obtenerConexionBaseDatos();

    $tipos  = $conn->query("SELECT id, nombre FROM catalogo_tipo_equipo WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $marcas = $conn->query("SELECT id, nombre FROM catalogo_marcas        WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $plazos = $conn->query("SELECT id, meses  FROM catalogo_plazos        WHERE activo = 1 ORDER BY meses")->fetchAll();

    enviarRespuestaJson('success', 'OK', [
        'tipos_equipo' => $tipos,
        'marcas'       => $marcas,
        'plazos'       => $plazos,
    ]);
} catch (Exception $e) {
    error_log('Error catálogos: ' . $e->getMessage());
    enviarRespuestaJson('error', 'Error al obtener catálogos', null, 500);
}
