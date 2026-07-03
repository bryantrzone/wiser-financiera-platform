<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/constants.php';

requireLogin();

$user = [
    'id' => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['role'],
];
$pageTitle = 'Cotizaciones — ' . APP_NAME;
$currentPage = 'cotizaciones';

$conn = obtenerConexionBaseDatos();
$buscar = trim($_GET['q'] ?? '');

// Eliminar cotización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    $delId = (int) $_POST['eliminar_id'];
    $conn->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$delId]);
    header('Location: cotizaciones.php');
    exit;
}

$where = $buscar ? "AND (cl.nombre LIKE :q OR cl.empresa LIKE :q OR c.credito_no LIKE :q)" : "";
$sql = "
    SELECT c.id, c.credito_no, c.fecha_inicio, c.monto_credito, c.plazo_meses,
           c.pago_mensual, c.total_a_pagar, c.created_at,
           cl.nombre AS cliente_nombre, cl.empresa,
           COALESCE(t.tasa_anual, c.tasa_anual_custom) AS tasa_anual,
           COALESCE(t.descripcion, 'Tasa manual') AS tasa_desc,
           p.nombre AS producto_nombre
    FROM cotizaciones c
    JOIN clientes cl      ON cl.id = c.cliente_id
    LEFT JOIN tasas t     ON t.id  = c.tasa_id
    JOIN productos p      ON p.id  = c.producto_id
    WHERE 1 {$where}
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
$buscar ? $stmt->execute([':q' => '%' . $buscar . '%']) : $stmt->execute();
$cotizaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'partials/brand_head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include 'partials/app_header.php'; ?>
    <?php include 'partials/app_menu.php'; ?>

    <main class="max-w-screen-xl mx-auto px-4 py-6">

        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cotizaciones</h1>
                <p class="text-gray-400 text-sm mt-0.5">
                    <?= count($cotizaciones) ?> cotización<?= count($cotizaciones) !== 1 ? 'es' : '' ?>
                    <?= $buscar ? "para \"{$buscar}\"" : 'registradas' ?>
                </p>
            </div>
            <a href="<?= APP_BASE_PATH ?>/index.php" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white
                      bg-blue-900 hover:bg-blue-800 rounded-lg transition-colors">
                <i data-lucide="file-plus-2" class="w-4 h-4"></i>
                Nueva Cotización
            </a>
        </div>

        <!-- Búsqueda -->
        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
                placeholder="Buscar por cliente, empresa o número de crédito..." class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <button type="submit" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm
                           text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                Buscar
            </button>
            <?php if ($buscar): ?>
                <a href="cotizaciones.php" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Crédito No.</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Cliente</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Producto / Tasa</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Monto</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Plazo</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Pago Mensual</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Fecha</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cotizaciones)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 text-gray-400">
                                <?php if ($buscar): ?>
                                    No se encontraron cotizaciones para "<?= htmlspecialchars($buscar) ?>"
                                <?php else: ?>
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="inbox" class="w-10 h-10 text-gray-300"></i>
                                        <p>Aún no hay cotizaciones. <a href="index.php"
                                                class="text-blue-700 hover:underline">Crear la primera</a></p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cotizaciones as $c): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <span class="font-mono font-semibold text-blue-900 text-xs bg-blue-50
                                                 px-2 py-0.5 rounded">
                                        <?= htmlspecialchars($c['credito_no']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($c['cliente_nombre']) ?></p>
                                    <?php if ($c['empresa']): ?>
                                        <p class="text-xs text-gray-400"><?= htmlspecialchars($c['empresa']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-gray-700"><?= htmlspecialchars($c['producto_nombre']) ?></p>
                                    <p class="text-xs text-gray-400">
                                        <?= round((float) $c['tasa_anual'] * 100, 0) ?>% —
                                        <?= htmlspecialchars($c['tasa_desc']) ?>
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-gray-800">
                                    $<?= number_format((float) $c['monto_credito'], 2) ?>
                                </td>
                                <td class="px-5 py-3 text-center text-gray-600">
                                    <?= $c['plazo_meses'] ?> meses
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-gray-800">
                                    $<?= number_format((float) $c['pago_mensual'], 2) ?>
                                </td>
                                <td class="px-5 py-3 text-gray-500 text-xs">
                                    <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="<?= APP_BASE_PATH ?>/ver_cotizacion.php?id=<?= $c['id'] ?>"
                                            title="Ver detalle"
                                            class="p-1.5 text-gray-500 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="<?= APP_BASE_PATH ?>/api/cotizaciones/exportar_pdf.php?id=<?= $c['id'] ?>"
                                            title="Descargar PDF" target="_blank"
                                            class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                            <i data-lucide="file-down" class="w-4 h-4"></i>
                                        </a>
                                        <a href="<?= APP_BASE_PATH ?>/api/cotizaciones/exportar_excel.php?id=<?= $c['id'] ?>"
                                            title="Descargar Excel"
                                            class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded transition-colors">
                                            <i data-lucide="sheet" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" class="inline"
                                            onsubmit="return confirm('¿Eliminar cotización <?= htmlspecialchars(addslashes($c['credito_no'])) ?>?')">
                                            <input type="hidden" name="eliminar_id" value="<?= $c['id'] ?>">
                                            <button type="submit" title="Eliminar"
                                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>

</html>