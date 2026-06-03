<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/constants.php';

requireLogin();

$user = [
    'id'        => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'],
    'email'     => $_SESSION['email'],
    'role'      => $_SESSION['role'],
];
$pageTitle   = 'Clientes — ' . APP_NAME;
$currentPage = 'clientes';

$conn = obtenerConexionBaseDatos();
$msg  = '';
$err  = '';

// Alta rápida de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            $err = 'El nombre es requerido.';
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (nombre, empresa, rfc, email, telefono) VALUES (?,?,?,?,?)");
            $stmt->execute([
                sanitizarEntrada($nombre),
                sanitizarEntrada($_POST['empresa'] ?? ''),
                sanitizarEntrada($_POST['rfc']     ?? ''),
                sanitizarEntrada($_POST['email']   ?? ''),
                sanitizarEntrada($_POST['telefono'] ?? ''),
            ]);
            $msg = 'Cliente creado correctamente.';
        }
    } elseif ($accion === 'eliminar') {
        $cid = (int)($_POST['cliente_id'] ?? 0);
        if ($cid) {
            $conn->prepare("UPDATE clientes SET activo=0 WHERE id=?")->execute([$cid]);
            $msg = 'Cliente eliminado.';
        }
    }
}

$buscar   = trim($_GET['q'] ?? '');
$sqlWhere = $buscar ? "AND (nombre LIKE :q OR empresa LIKE :q OR rfc LIKE :q)" : "";
$stmt     = $conn->prepare("SELECT * FROM clientes WHERE activo=1 {$sqlWhere} ORDER BY nombre ASC");
$buscar ? $stmt->execute([':q' => '%' . $buscar . '%']) : $stmt->execute();
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'partials/brand_head.php'; ?>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include 'partials/app_header.php'; ?>
    <?php include 'partials/app_menu.php'; ?>

    <main class="max-w-5xl mx-auto px-4 py-6">

        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
                <p class="text-gray-400 text-sm mt-0.5">Gestión de clientes para cotizaciones</p>
            </div>
            <button onclick="document.getElementById('modal-nuevo').classList.remove('hidden')"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white
                           bg-blue-900 hover:bg-blue-800 rounded-lg transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Nuevo Cliente
            </button>
        </div>

        <?php if ($msg): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700 mb-4">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600 mb-4">
                <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <!-- Búsqueda -->
        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
                   placeholder="Buscar por nombre, empresa o RFC..."
                   class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                    class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm
                           text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                Buscar
            </button>
            <?php if ($buscar): ?>
                <a href="clientes.php"
                   class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Empresa</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">RFC</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-400">
                                <?= $buscar ? 'No se encontraron clientes para "' . htmlspecialchars($buscar) . '"' : 'Aún no hay clientes registrados.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cl): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900">
                                    <?= htmlspecialchars($cl['nombre']) ?>
                                </td>
                                <td class="px-5 py-3 text-gray-600">
                                    <?= htmlspecialchars($cl['empresa'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3 text-gray-500 font-mono text-xs">
                                    <?= htmlspecialchars($cl['rfc'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3 text-gray-500 text-xs">
                                    <?= htmlspecialchars($cl['email'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3 text-gray-500 text-xs">
                                    <?= htmlspecialchars($cl['telefono'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="/wiser-financiera-project/index.php?cliente_id=<?= $cl['id'] ?>"
                                       class="inline-flex items-center gap-1 text-xs text-blue-700 hover:text-blue-900
                                              font-medium px-2 py-1 rounded hover:bg-blue-50 transition-colors">
                                        <i data-lucide="file-plus-2" class="w-3.5 h-3.5"></i>
                                        Cotizar
                                    </a>
                                    <form method="POST" class="inline"
                                          onsubmit="return confirm('¿Eliminar cliente <?= htmlspecialchars(addslashes($cl['nombre'])) ?>?')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="cliente_id" value="<?= $cl['id'] ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-700
                                                       font-medium px-2 py-1 rounded hover:bg-red-50 transition-colors ml-1">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal nuevo cliente -->
    <div id="modal-nuevo" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Nuevo Cliente</h3>
                <button onclick="document.getElementById('modal-nuevo').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="accion" value="crear">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Nombre <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="nombre" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Nombre completo">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Empresa</label>
                    <input type="text" name="empresa"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Razón social">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">RFC</label>
                        <input type="text" name="rfc" maxlength="20"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="RFC">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Teléfono</label>
                        <input type="tel" name="telefono"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="10 dígitos">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                    <input type="email" name="email"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="correo@empresa.com">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button"
                            onclick="document.getElementById('modal-nuevo').classList.add('hidden')"
                            class="flex-1 border border-gray-200 text-gray-600 font-medium py-2 rounded-lg
                                   text-sm hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2
                                   rounded-lg text-sm transition-colors">
                        Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>lucide.createIcons();</script>
</body>
</html>
