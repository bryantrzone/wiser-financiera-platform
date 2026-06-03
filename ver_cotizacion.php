<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/amortizacion.php';
require_once 'config/constants.php';

requireLogin();

$user = [
    'id' => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['role'],
];

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: cotizaciones.php');
    exit;
}

$conn = obtenerConexionBaseDatos();

$stmt = $conn->prepare("
    SELECT c.*, cl.nombre AS cliente_nombre, cl.empresa, cl.rfc, cl.email AS cliente_email, cl.telefono,
           COALESCE(t.descripcion, 'Tasa manual') AS tasa_desc,
           COALESCE(t.tasa_anual, c.tasa_anual_custom) AS tasa_anual,
           p.nombre AS producto_nombre
    FROM cotizaciones c
    JOIN clientes cl      ON cl.id = c.cliente_id
    LEFT JOIN tasas t     ON t.id  = c.tasa_id
    LEFT JOIN productos p ON p.id  = c.producto_id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$cot = $stmt->fetch();

if (!$cot) {
    header('Location: cotizaciones.php');
    exit;
}

$stmt2 = $conn->prepare("SELECT * FROM cotizacion_periodos WHERE cotizacion_id = ? ORDER BY periodo ASC");
$stmt2->execute([$id]);
$periodos = $stmt2->fetchAll();

$pageTitle = 'Cotización ' . $cot['credito_no'] . ' — ' . APP_NAME;
$currentPage = 'cotizaciones';

$tasaPct = round((float) $cot['tasa_anual'] * 100, 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'partials/brand_head.php'; ?>
    <style>
        .table-amort {
            font-size: 12px;
        }

        .table-amort th {
            background: #1e3a8a;
            color: #fff;
            padding: 7px 10px;
            text-align: center;
            font-weight: 600;
            white-space: nowrap;
        }

        .table-amort td {
            padding: 5px 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .table-amort tr:nth-child(even) td {
            background: #eff6ff;
        }

        .table-amort tr:hover td {
            background: #dbeafe;
        }

        .table-amort td.num {
            text-align: right;
            font-family: monospace;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px 20px;
        }

        @media(max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .info-item {
            display: flex;
            gap: 6px;
            font-size: 13px;
            align-items: baseline;
        }

        .info-label {
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .info-val {
            color: #111827;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include 'partials/app_header.php'; ?>
    <?php include 'partials/app_menu.php'; ?>

    <main class="max-w-screen-xl mx-auto px-4 py-6">

        <!-- Barra de acciones -->
        <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="/cotizaciones.php" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span class="font-mono text-blue-900 bg-blue-50 px-3 py-0.5 rounded-lg text-lg">
                            <?= htmlspecialchars($cot['credito_no']) ?>
                        </span>
                        <span class="text-gray-500 font-normal">— <?= htmlspecialchars($cot['cliente_nombre']) ?></span>
                    </h1>
                    <p class="text-gray-400 text-sm mt-0.5">
                        Generada el <?= date('d/m/Y', strtotime($cot['created_at'])) ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="/api/cotizaciones/exportar_pdf.php?id=<?= $id ?>"
                    target="_blank" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700
                          bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                    <i data-lucide="file-down" class="w-4 h-4"></i>
                    Exportar PDF
                </a>
                <a href="/api/cotizaciones/exportar_excel.php?id=<?= $id ?>" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700
                          bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                    <i data-lucide="sheet" class="w-4 h-4"></i>
                    Exportar Excel
                </a>
                <a href="/index.php" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white
                          bg-blue-900 hover:bg-blue-800 rounded-lg transition-colors">
                    <i data-lucide="file-plus-2" class="w-4 h-4"></i>
                    Nueva Cotización
                </a>
            </div>
        </div>

        <!-- Card de cotización -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">

            <!-- Cabecera informativa -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-0.5">Cliente</p>
                        <p class="text-lg font-bold text-gray-900"><?= htmlspecialchars($cot['cliente_nombre']) ?></p>
                        <?php if ($cot['empresa']): ?>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($cot['empresa']) ?></p>
                        <?php endif; ?>
                        <?php if ($cot['rfc']): ?>
                                <p class="text-xs text-gray-400 font-mono">RFC: <?= htmlspecialchars($cot['rfc']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Crédito No.:</span>
                        <span class="info-val font-mono"><?= htmlspecialchars($cot['credito_no']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Plazo:</span>
                        <span class="info-val"><?= $cot['plazo_meses'] ?> meses (<?= $cot['plazo_dias'] ?> días)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total a Pagar:</span>
                        <span
                            class="info-val font-semibold">$<?= number_format((float) $cot['total_a_pagar'], 2) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha Inicio:</span>
                        <span
                            class="info-val"><?= CalculadoraAmortizacion::formatearFechaMx($cot['fecha_inicio']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"># Meses:</span>
                        <span class="info-val"><?= $cot['plazo_meses'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Intereses:</span>
                        <span class="info-val">$<?= number_format((float) $cot['total_intereses'], 2) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha Límite:</span>
                        <span
                            class="info-val"><?= CalculadoraAmortizacion::formatearFechaMx($cot['fecha_limite_pago']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Monto Crédito:</span>
                        <span class="info-val">$<?= number_format((float) $cot['monto_credito'], 2) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Moneda:</span>
                        <span class="info-val"><?= htmlspecialchars($cot['moneda']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tasa Interés:</span>
                        <span class="info-val"><?= $tasaPct ?>% — <?= htmlspecialchars($cot['tasa_desc']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tipo de Interés:</span>
                        <span class="info-val">Anual ordinario Fijo</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Producto:</span>
                        <span class="info-val"><?= htmlspecialchars($cot['producto_nombre']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Tabla de amortización -->
            <div class="overflow-x-auto">
                <table class="table-amort w-full border-collapse">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>F. Vencimiento</th>
                            <th>Días</th>
                            <th>Saldo Insoluto</th>
                            <th>Pago Capital</th>
                            <th>Interés Ordinario</th>
                            <th>IVA Interés</th>
                            <th>Pago Integrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periodos as $p): ?>
                                <tr>
                                    <td class="text-center font-semibold"><?= $p['periodo'] ?></td>
                                    <td class="text-center">
                                        <?= CalculadoraAmortizacion::formatearFechaMx($p['fecha_vencimiento']) ?></td>
                                    <td class="text-center"><?= $p['dias'] ?></td>
                                    <td class="num">$<?= number_format((float) $p['saldo_insoluto'], 2) ?></td>
                                    <td class="num">$<?= number_format((float) $p['pago_capital'], 2) ?></td>
                                    <td class="num">$<?= number_format((float) $p['interes_ordinario'], 2) ?></td>
                                    <td class="num">$<?= number_format((float) $p['iva_interes'], 2) ?></td>
                                    <td class="num font-semibold text-blue-900">
                                        $<?= number_format((float) $p['pago_integrado'], 2) ?></td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#dbeafe; font-weight:700;">
                            <td colspan="3" class="px-3 py-2 text-center text-sm">TOTALES</td>
                            <td class="num">—</td>
                            <td class="num">
                                $<?= number_format(array_sum(array_column($periodos, 'pago_capital')), 2) ?></td>
                            <td class="num">
                                $<?= number_format(array_sum(array_column($periodos, 'interes_ordinario')), 2) ?></td>
                            <td class="num">
                                $<?= number_format(array_sum(array_column($periodos, 'iva_interes')), 2) ?></td>
                            <td class="num text-blue-900">
                                $<?= number_format(array_sum(array_column($periodos, 'pago_integrado')), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>

</html>