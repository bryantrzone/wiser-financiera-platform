<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/constants.php';

requireLogin();

$user        = [
    'id'        => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'],
    'email'     => $_SESSION['email'],
    'role'      => $_SESSION['role'],
];
$pageTitle   = 'Nueva Cotización — ' . APP_NAME;
$currentPage = 'index';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'partials/brand_head.php'; ?>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'partials/app_header.php'; ?>
<?php include 'partials/app_menu.php'; ?>

<!-- Contenido principal -->
<main class="max-w-4xl mx-auto px-4 py-8">

    <!-- Título -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Nueva Cotización</h1>
        <p class="text-gray-500 text-sm mt-1">Completa los 4 pasos para generar tu cotización financiera</p>
    </div>

    <!-- Indicador de pasos -->
    <div class="mb-8">
        <div class="flex items-center justify-between relative">
            <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 z-0"></div>
            <div id="progress-bar" class="absolute top-4 left-0 h-0.5 bg-accent z-0 transition-all duration-500" style="width:0%"></div>

            <?php
            $steps = [
                ['num' => 1, 'icon' => 'package',       'label' => 'Equipo'],
                ['num' => 2, 'icon' => 'calculator',    'label' => 'Financiamiento'],
                ['num' => 3, 'icon' => 'user',          'label' => 'Cliente'],
                ['num' => 4, 'icon' => 'clipboard-list','label' => 'Resumen'],
            ];
            foreach ($steps as $s):
            ?>
            <div class="step-indicator relative z-10 flex flex-col items-center" data-step="<?= $s['num'] ?>">
                <div class="step-circle w-9 h-9 rounded-full border-2 border-gray-300 bg-white
                            flex items-center justify-center transition-all duration-300
                            text-gray-400 font-semibold text-sm">
                    <?= $s['num'] ?>
                </div>
                <span class="step-label mt-2 text-xs font-medium text-gray-400 hidden sm:block"><?= $s['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Card del wizard -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        <!-- ══════════════════════════════════════════
             PASO 1 — Datos del equipo
        ══════════════════════════════════════════ -->
        <div id="step-1" class="wizard-step p-8">
            <div class="flex items-center space-x-3 mb-7">
                <div class="w-10 h-10 rounded-xl bg-accent-light flex items-center justify-center">
                    <i data-lucide="package" class="w-5 h-5 text-accent"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Datos del Equipo</h2>
                    <p class="text-sm text-gray-400">¿Qué equipo se va a financiar?</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="wf-label">Tipo de equipo *</label>
                    <select id="tipo_equipo" class="wf-select">
                        <option value="">Selecciona un tipo…</option>
                    </select>
                </div>
                <div>
                    <label class="wf-label">Marca *</label>
                    <select id="marca" class="wf-select">
                        <option value="">Selecciona una marca…</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="wf-label">Modelo</label>
                    <input type="text" id="modelo" class="wf-input" placeholder="Ej. CAT 924K, Toyota 8FGU25…">
                </div>
                <div class="md:col-span-2">
                    <label class="wf-label">Descripción adicional</label>
                    <textarea id="descripcion" rows="2" class="wf-input resize-none" placeholder="Especificaciones, año, serie…"></textarea>
                </div>
                <div>
                    <label class="wf-label">Cantidad *</label>
                    <input type="number" id="cantidad" class="wf-input" value="1" min="1" max="999">
                </div>
                <div>
                    <label class="wf-label">Moneda *</label>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200">
                        <button type="button" data-moneda="MXN"
                                class="moneda-btn flex-1 py-3 text-sm font-semibold transition-all bg-accent text-white">
                            MXN
                        </button>
                        <button type="button" data-moneda="USD"
                                class="moneda-btn flex-1 py-3 text-sm font-semibold transition-all bg-white text-gray-500 hover:bg-gray-50">
                            USD
                        </button>
                    </div>
                </div>
                <div>
                    <label class="wf-label">Costo unitario *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium" id="sym-moneda">$</span>
                        <input type="number" id="costo_unitario" class="wf-input pl-8" placeholder="0.00" min="0" step="0.01">
                    </div>
                </div>
                <div>
                    <label class="wf-label">Tipo de cambio (si USD)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input type="number" id="tipo_cambio" class="wf-input pl-8" value="17.50" min="1" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             PASO 2 — Condiciones financieras
        ══════════════════════════════════════════ -->
        <div id="step-2" class="wizard-step p-8 hidden">
            <div class="flex items-center space-x-3 mb-7">
                <div class="w-10 h-10 rounded-xl bg-accent-light flex items-center justify-center">
                    <i data-lucide="calculator" class="w-5 h-5 text-accent"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Condiciones Financieras</h2>
                    <p class="text-sm text-gray-400">Define las condiciones del financiamiento</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="wf-label">Tipo de financiamiento *</label>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach (FINANCIAMIENTO_TIPOS as $key => $label): ?>
                        <label class="tipo-fin-option cursor-pointer">
                            <input type="radio" name="tipo_financiamiento" value="<?= $key ?>"
                                   class="sr-only" <?= $key === 'arrendamiento_financiero' ? 'checked' : '' ?>>
                            <div class="tipo-fin-card border-2 border-gray-200 rounded-xl p-3 text-center
                                        transition-all hover:border-accent/50 text-sm font-medium text-gray-600">
                                <?= $label ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="wf-label">Plazo *</label>
                    <select id="plazo_meses" class="wf-select">
                        <option value="12">12 meses</option>
                        <option value="18">18 meses</option>
                        <option value="24" selected>24 meses</option>
                        <option value="36">36 meses</option>
                        <option value="48">48 meses</option>
                        <option value="60">60 meses</option>
                    </select>
                </div>
                <div>
                    <label class="wf-label">Tasa anual (%)</label>
                    <div class="relative">
                        <input type="number" id="tasa_anual" class="wf-input pr-8" value="18.00" min="0" max="100" step="0.01">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                </div>
                <div>
                    <label class="wf-label">Anticipo (%)</label>
                    <div class="relative">
                        <input type="number" id="anticipo_pct" class="wf-input pr-8" value="0" min="0" max="80" step="0.01">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Monto: <span id="lbl-anticipo-monto" class="font-medium text-gray-600">$0.00 MXN</span></p>
                </div>
                <div>
                    <label class="wf-label">Valor residual (%)</label>
                    <div class="relative">
                        <input type="number" id="residual_pct" class="wf-input pr-8" value="20" min="0" max="80" step="0.01">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Monto: <span id="lbl-residual-monto" class="font-medium text-gray-600">$0.00 MXN</span></p>
                </div>
                <div>
                    <label class="wf-label">Seguro anual (% sobre costo)</label>
                    <div class="relative">
                        <input type="number" id="seguro_pct" class="wf-input pr-8" value="2.50" min="0" max="20" step="0.01">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                </div>
            </div>

            <!-- Resumen de pago estimado -->
            <div id="calc-preview" class="mt-7 p-5 bg-accent-light rounded-2xl border border-accent/20 hidden">
                <p class="text-xs font-semibold text-accent uppercase tracking-wide mb-3">Estimado de pago mensual</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Pago equipo</p>
                        <p id="lbl-pago-equipo" class="text-sm font-bold text-gray-800">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Seguro</p>
                        <p id="lbl-pago-seguro" class="text-sm font-bold text-gray-800">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">IVA (16%)</p>
                        <p id="lbl-iva" class="text-sm font-bold text-gray-800">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Total mensual</p>
                        <p id="lbl-total-mensual" class="text-lg font-bold text-accent">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             PASO 3 — Datos del cliente
        ══════════════════════════════════════════ -->
        <div id="step-3" class="wizard-step p-8 hidden">
            <div class="flex items-center space-x-3 mb-7">
                <div class="w-10 h-10 rounded-xl bg-accent-light flex items-center justify-center">
                    <i data-lucide="user" class="w-5 h-5 text-accent"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Datos del Cliente</h2>
                    <p class="text-sm text-gray-400">Información del cliente para la cotización</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="wf-label">Nombre completo *</label>
                    <input type="text" id="cliente_nombre" class="wf-input" placeholder="Nombre del contacto">
                </div>
                <div>
                    <label class="wf-label">Empresa</label>
                    <input type="text" id="cliente_empresa" class="wf-input" placeholder="Razón social o nombre comercial">
                </div>
                <div>
                    <label class="wf-label">RFC</label>
                    <input type="text" id="cliente_rfc" class="wf-input uppercase" placeholder="XAXX010101000" maxlength="13">
                </div>
                <div>
                    <label class="wf-label">Correo electrónico</label>
                    <input type="email" id="cliente_email" class="wf-input" placeholder="correo@empresa.com">
                </div>
                <div>
                    <label class="wf-label">Teléfono</label>
                    <input type="tel" id="cliente_telefono" class="wf-input" placeholder="55 1234 5678">
                </div>
                <div>
                    <label class="wf-label">Vigencia de cotización</label>
                    <input type="date" id="fecha_vencimiento" class="wf-input"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="wf-label">Notas internas</label>
                    <textarea id="notas" rows="3" class="wf-input resize-none" placeholder="Observaciones para uso interno…"></textarea>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             PASO 4 — Resumen y confirmación
        ══════════════════════════════════════════ -->
        <div id="step-4" class="wizard-step p-8 hidden">
            <div class="flex items-center space-x-3 mb-7">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                    <i data-lucide="clipboard-list" class="w-5 h-5 text-green-500"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Resumen de Cotización</h2>
                    <p class="text-sm text-gray-400">Verifica los datos antes de guardar</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Datos del equipo -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 flex items-center space-x-2">
                        <i data-lucide="package" class="w-4 h-4"></i><span>Equipo</span>
                    </h3>
                    <dl class="space-y-2 text-sm" id="resumen-equipo"></dl>
                </div>

                <!-- Datos financieros -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 flex items-center space-x-2">
                        <i data-lucide="trending-up" class="w-4 h-4"></i><span>Financiamiento</span>
                    </h3>
                    <dl class="space-y-2 text-sm" id="resumen-financiero"></dl>
                </div>

                <!-- Datos del cliente -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 flex items-center space-x-2">
                        <i data-lucide="user" class="w-4 h-4"></i><span>Cliente</span>
                    </h3>
                    <dl class="space-y-2 text-sm" id="resumen-cliente"></dl>
                </div>

                <!-- Pago mensual destacado -->
                <div class="bg-accent rounded-xl p-5 flex flex-col items-center justify-center text-white text-center">
                    <p class="text-sm font-medium opacity-80 mb-1">Pago mensual total (c/IVA)</p>
                    <p id="resumen-pago-mensual" class="text-4xl font-bold">—</p>
                    <p id="resumen-moneda" class="text-sm opacity-70 mt-1">MXN</p>
                    <div class="mt-3 pt-3 border-t border-white/20 w-full text-center">
                        <p class="text-xs opacity-70">Plazo: <span id="resumen-plazo">—</span></p>
                    </div>
                </div>
            </div>

            <!-- Mensajes de estado -->
            <div id="save-status" class="hidden mt-5 p-4 rounded-xl text-sm"></div>
        </div>

        <!-- Barra de navegación del wizard -->
        <div class="px-8 py-5 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
            <button id="btn-anterior"
                    class="flex items-center space-x-2 px-5 py-2.5 rounded-xl border border-gray-200
                           text-sm font-medium text-gray-600 hover:bg-gray-100 transition-all disabled:opacity-30"
                    disabled>
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Anterior</span>
            </button>

            <span class="text-sm text-gray-400" id="step-counter">Paso 1 de 4</span>

            <button id="btn-siguiente"
                    class="flex items-center space-x-2 px-6 py-2.5 rounded-xl
                           bg-accent text-white text-sm font-semibold hover:bg-accent-dark transition-all">
                <span id="btn-siguiente-text">Siguiente</span>
                <i data-lucide="arrow-right" class="w-4 h-4" id="btn-siguiente-icon"></i>
                <div class="spinner hidden" id="btn-save-spinner"></div>
            </button>
        </div>
    </div>
</main>

<script src="/wiser-financiera-project/assets/js/wizard.js?v=<?= time() ?>"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
