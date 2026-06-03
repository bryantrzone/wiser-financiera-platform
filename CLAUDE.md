# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Contexto del proyecto

**Wiser Financiera** es una aplicación PHP 8.2 para cotizar créditos/financiamientos en México. Genera tablas de amortización, gestiona clientes y exporta cotizaciones en PDF y Excel. Sin framework MVC — PHP procedural puro con PDO.

## Levantar el entorno

```bash
# Iniciar XAMPP (Apache + MySQL)
sudo /Applications/XAMPP_W/xamppfiles/bin/xampp start

# URL principal
http://localhost/login.php
```

**Base de datos:**
```bash
# Schema inicial
mysql -u u106289951_wiserfinance -p u106289951_wiserfinance < sql/setup.sql

# Migración de amortización
mysql -u u106289951_wiserfinance -p u106289951_wiserfinance < sql/amortizacion_migration.sql
```

**Dependencias PHP:**
```bash
php composer.phar install
```

## Arquitectura

### Patrón de rutas
No hay router. Cada archivo PHP raíz es una página (requiere login). Los endpoints JSON viven bajo `api/`:

| Tipo | Ejemplo | Descripción |
|------|---------|-------------|
| Página | `cotizaciones.php` | Vistas HTML con sesión activa |
| API JSON | `api/cotizaciones/calcular.php` | Recibe/devuelve JSON, usa `requireLoginApi()` |
| Exportación | `api/cotizaciones/exportar_pdf.php?id=N` | Descarga binaria |

### Capa de lógica de negocio
- **`includes/amortizacion.php`** — Clase `CalculadoraAmortizacion` con método estático `generarPeriodos()`. Es el núcleo financiero: calcula PMT, interés ordinario, IVA (16% fijo), capital e interés por período.
- **`includes/auth.php`** — Sesiones PHP nativas + tabla `user_sessions`. Funciones: `requireLogin()`, `requireRole()`, `requireLoginApi()`, `authenticateUser()`.
- **`includes/functions.php`** — Utilidades generales.

### Base de datos
Singleton PDO en `config/database.php` (`obtenerConexionBaseDatos()`). Usa `PDO::FETCH_ASSOC`, `PDO::ERRMODE_EXCEPTION`. Prepared statements obligatorios en toda consulta.

Tablas clave:
- `cotizaciones` + `cotizacion_periodos` — Una cotización genera N filas de amortización
- `user_sessions` — Sesiones con expiración a 24 horas, limpieza probabilística (1%)
- `clientes` — Soft delete (`activo=0`)

### Partials HTML
Los partials en `partials/` se incluyen con `require_once`. Cada página construye el layout incluyendo `brand_head.php` → `app_header.php` → `sidebar_menu.php` → contenido.

## Convenciones críticas

**Autenticación en páginas:** toda página privada debe empezar con:
```php
require_once 'includes/auth.php';
requireLogin();
```

**Autenticación en APIs JSON:** los endpoints bajo `api/` deben usar:
```php
require_once '../../includes/auth.php';
requireLoginApi(); // Responde 401 JSON si no autenticado
header('Content-Type: application/json');
```

**Respuesta JSON estándar:**
```php
echo json_encode(['success' => true, 'data' => $resultado]);
// o en error:
echo json_encode(['success' => false, 'message' => 'Descripción del error']);
exit;
```

**PDO — patrón de consulta:**
```php
$pdo = obtenerConexionBaseDatos();
$stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $id]);
$fila = $stmt->fetch(); // FETCH_ASSOC por defecto
```

## Constantes financieras

Definidas en `config/constants.php`:
- `IVA = 0.16` — 16% fijo México
- `DEFAULT_RESIDUAL_PCT = 20.0`
- `DEFAULT_PLAZO_MESES = 24`
- `DEFAULT_MONEDA = 'MXN'`

## Dependencias clave

- **`tecnickcom/tcpdf`** — PDF en `api/cotizaciones/exportar_pdf.php`
- **`phpoffice/phpspreadsheet`** — Excel en `api/cotizaciones/exportar_excel.php`
- Tailwind CSS y Lucide Icons cargados desde CDN (sin build step)

## Roles de usuario

- `ROLE_ADMIN` — Administrador completo
- `ROLE_VENDOR` — Ejecutivo de ventas
- `ROLE_CLIENT` — Cliente (acceso limitado)
