<?php
// Aplicación
define('APP_NAME',    'Wiser Financiera');
define('APP_VERSION', '1.0.0');
define('APP_COLOR',   '#4A90E2');

// Roles
define('ROLE_ADMIN',  'admin');
define('ROLE_VENDOR', 'vendor');
define('ROLE_CLIENT', 'client');

// Tipos de financiamiento
define('FINANCIAMIENTO_TIPOS', [
    'arrendamiento_financiero' => 'Arrendamiento Financiero',
    'arrendamiento_puro'       => 'Arrendamiento Puro',
    'credito_simple'           => 'Crédito Simple',
]);

// Estados de cotización
define('COTIZACION_ESTADOS', [
    'borrador'  => 'Borrador',
    'enviada'   => 'Enviada',
    'aceptada'  => 'Aceptada',
    'rechazada' => 'Rechazada',
    'vencida'   => 'Vencida',
]);

define('COTIZACION_ESTADO_COLORES', [
    'borrador'  => 'gray',
    'enviada'   => 'blue',
    'aceptada'  => 'green',
    'rechazada' => 'red',
    'vencida'   => 'orange',
]);

// Defaults financieros
define('DEFAULT_RESIDUAL_PCT',  20.0);
define('DEFAULT_PLAZO_MESES',   24);
define('DEFAULT_MONEDA',        'MXN');
define('IVA',                   0.16);

// Sesión
define('SESSION_LIFETIME', 24 * 60 * 60);  // 24 horas

// Folio
define('FOLIO_PREFIX', 'WF');
