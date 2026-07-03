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
$pageTitle = 'Nueva Cotización — ' . APP_NAME;
$currentPage = 'index';

// Cargar catálogos para los selects
$conn = obtenerConexionBaseDatos();
$clientes = $conn->query("SELECT id, nombre, empresa FROM clientes WHERE activo=1 ORDER BY nombre ASC")->fetchAll();

$productos = $conn->query("SELECT id, nombre, comision_apertura FROM productos WHERE activo=1 ORDER BY nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'partials/brand_head.php'; ?>
    <style>
        .preview-placeholder {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
        }

        .table-amort {
            font-size: 12px;
        }

        .table-amort th {
            background: #1e3a8a;
            color: #fff;
            padding: 6px 8px;
            text-align: center;
            font-weight: 600;
            white-space: nowrap;
        }

        .table-amort td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .table-amort tr:nth-child(even) td {
            background: #f0f4ff;
        }

        .table-amort tr:hover td {
            background: #dbeafe;
        }

        .table-amort td.num {
            text-align: right;
            font-family: monospace;
        }

        .cabecera-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 4px 16px;
        }

        .cabecera-item {
            display: flex;
            gap: 6px;
            font-size: 13px;
        }

        .cabecera-label {
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }

        .cabecera-val {
            color: #111827;
        }

        #preview-section {
            display: none;
        }

        .spinner {
            border: 3px solid #e5e7eb;
            border-top-color: #1e3a8a;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .panel-left {
            width: 320px;
            flex-shrink: 0;
        }

        @media(max-width: 900px) {
            .main-layout {
                flex-direction: column !important;
            }

            .panel-left {
                width: 100%;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include 'partials/app_header.php'; ?>
    <?php include 'partials/app_menu.php'; ?>

    <main class="max-w-screen-2xl mx-auto px-4 py-6">

        <!-- Título -->
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Nueva Cotización</h1>
                <p class="text-gray-400 text-sm mt-0.5">Ingresa los datos y la tabla se generará automáticamente</p>
            </div>
            <a href="/cotizaciones.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600
                      bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="list" class="w-4 h-4"></i>
                Ver cotizaciones
            </a>
        </div>

        <!-- Layout split -->
        <div class="flex gap-5 main-layout">

            <!-- Panel izquierdo: formulario -->
            <div class="panel-left">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-4">
                    <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="calculator" class="w-5 h-5 text-blue-600"></i>
                        Datos del Crédito
                    </h2>

                    <form id="form-cotizacion" class="space-y-4" novalidate>

                        <!-- Cliente -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Cliente <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2 min-w-0">
                                <select id="cliente_id" name="cliente_id" class="flex-1 min-w-0 truncate border border-gray-200 rounded-lg px-3 py-2 text-sm
                                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($clientes as $cl): ?>
                                            <option value="<?= $cl['id'] ?>">
                                                <?= htmlspecialchars($cl['nombre']) ?>
                                                <?= $cl['empresa'] ? ' — ' . htmlspecialchars($cl['empresa']) : '' ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="/clientes.php" title="Gestionar clientes"
                                    class="p-2 border border-gray-200 rounded-lg text-gray-400
                                          hover:text-blue-600 hover:border-blue-300 transition-colors">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Monto -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Monto del Crédito (MXN) <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                <input type="number" id="monto_credito" name="monto_credito" min="1" step="1000"
                                    placeholder="50,000" class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Plazo -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Plazo (meses) <span class="text-red-400">*</span>
                            </label>
                            <input type="number" id="plazo_meses" name="plazo_meses" min="1" max="240" placeholder="12"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Fecha inicio -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Fecha de Inicio <span class="text-red-400">*</span>
                            </label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-d') ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Producto -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Producto</label>
                            <select id="producto_id" name="producto_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($productos as $pr): ?>
                                        <option value="<?= $pr['id'] ?>">
                                            <?= htmlspecialchars($pr['nombre']) ?>
                                            <?php
                                            $com = (float) $pr['comision_apertura'];
                                            echo $com > 0
                                                ? ' — ' . rtrim(rtrim(number_format($com * 100, 2), '0'), '.') . '% comisión por apertura'
                                                : ' — Sin comisión por apertura';
                                            ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tasa -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Tasa de Interés Anual <span class="text-red-400">*</span>
                            </label>
                            <div class="space-y-2">
                                <!-- Input manual principal -->
                                <div class="relative">
                                    <input type="number" id="tasa_anual_pct" name="tasa_anual_pct"
                                        min="3" max="60" step="0.5" value="24"
                                        placeholder="24"
                                        class="w-full border border-gray-200 rounded-lg pl-3 pr-8 py-2 text-sm font-semibold
                                               text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500
                                               [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none
                                               [&::-webkit-outer-spin-button]:appearance-none">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400
                                                 font-semibold pointer-events-none select-none">%</span>
                                </div>
                                <!-- Slider -->
                                <input type="range" id="tasa_slider" min="3" max="60" step="0.5" value="24"
                                    class="w-full h-1.5 rounded-lg appearance-none cursor-pointer accent-blue-700 bg-gray-200">
                                <!-- Marcas del rango -->
                                <div class="flex justify-between text-xs text-gray-400 select-none -mt-1">
                                    <span>3%</span>
                                    <span>20%</span>
                                    <span>40%</span>
                                    <span>60%</span>
                                </div>
                                <!-- Alerta de rango -->
                                <div id="tasa-alerta" class="hidden items-center gap-1.5 bg-amber-50 border border-amber-200
                                                              rounded-lg px-3 py-2 text-xs text-amber-700">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                                    <span id="tasa-alerta-msg"></span>
                                </div>
                            </div>
                        </div>

                        <!-- IVA sobre interés -->
                        <div class="flex items-center gap-2 py-1">
                            <input type="checkbox" id="aplicar_iva" name="aplicar_iva" checked class="w-4 h-4 rounded border-gray-300 text-blue-600
                                          focus:ring-blue-500 cursor-pointer">
                            <label for="aplicar_iva"
                                class="text-xs font-semibold text-gray-600 cursor-pointer select-none">
                                Aplicar IVA sobre interés (16%)
                            </label>
                        </div>

                        <!-- Gastos por contrato -->
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2 py-1">
                                <input type="checkbox" id="gastos_contrato_activo" name="gastos_contrato_activo"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <label for="gastos_contrato_activo"
                                    class="text-xs font-semibold text-gray-600 cursor-pointer select-none">
                                    Gastos por contrato
                                </label>
                            </div>
                            <div id="gastos-contrato-campo" class="hidden space-y-1.5">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                    <input type="number" id="gastos_contrato_monto" name="gastos_contrato_monto"
                                        min="0" max="15000" step="100" placeholder="0.00"
                                        class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                               focus:outline-none focus:ring-2 focus:ring-blue-500
                                               [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none
                                               [&::-webkit-outer-spin-button]:appearance-none">
                                </div>
                                <div id="gastos-alerta" class="hidden items-center gap-1.5 bg-amber-50 border border-amber-200
                                                                rounded-lg px-3 py-2 text-xs text-amber-700">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                                    <span>El máximo es $15,000 MXN. Se aplicará ese límite.</span>
                                </div>
                                <p class="text-xs text-gray-400">Máx. $15,000 MXN — se descuentan del monto del crédito</p>
                            </div>
                        </div>

                        <!-- Error message -->
                        <div id="form-error"
                            class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-600"></div>

                        <!-- Acciones -->
                        <div class="pt-2 space-y-2">
                            <button type="button" id="btn-guardar" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-semibold
                                           py-2.5 px-4 rounded-lg text-sm transition-colors
                                           flex items-center justify-center gap-2 disabled:opacity-50" disabled>
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Guardar Cotización
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Panel derecho: previsualización -->
            <div class="flex-1 min-w-0">

                <!-- Estado vacío -->
                <div id="empty-state" class="preview-placeholder rounded-2xl border-2 border-dashed border-blue-200
                            flex flex-col items-center justify-center text-center p-12 min-h-64">
                    <i data-lucide="table-2" class="w-14 h-14 text-blue-300 mb-3"></i>
                    <p class="text-gray-500 font-medium">La tabla de amortización aparecerá aquí</p>
                    <p class="text-gray-400 text-sm mt-1">Completa el monto, plazo, fecha y tasa</p>
                </div>

                <!-- Spinner -->
                <div id="loading-state" class="hidden flex flex-col items-center justify-center min-h-64">
                    <div class="spinner mb-3"></div>
                    <p class="text-gray-400 text-sm">Calculando...</p>
                </div>

                <!-- Preview de cotización -->
                <div id="preview-section" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <!-- Resumen de cabecera -->
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-blue-600"></i>
                                <span id="preview-credito-no" class="text-blue-900">COTIZACIÓN</span>
                            </h3>
                            <span class="text-xs text-gray-400 font-medium">Vista previa</span>
                        </div>
                        <div class="cabecera-grid" id="cabecera-data"></div>
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
                            <tbody id="tabla-periodos"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        (function () {
            const BASE = '/wiser-financiera-project-com';
            const fields = ['monto_credito', 'plazo_meses', 'fecha_inicio', 'tasa_anual_pct'];
            let debounceTimer = null;
            let lastData = null;

            const $ = id => document.getElementById(id);

            function fmtMXN(v) {
                return '$' + parseFloat(v).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function fmtDate(d) {
                if (!d) return '';
                const [y, m, dd] = d.split('-');
                return `${dd}/${m}/${y}`;
            }

            function getFormData() {
                const gastosActivo = $('gastos_contrato_activo').checked;
                const gastosMonto  = gastosActivo ? Math.min(parseFloat($('gastos_contrato_monto').value) || 0, 15000) : 0;
                return {
                    monto_credito:          $('monto_credito').value,
                    plazo_meses:            $('plazo_meses').value,
                    fecha_inicio:           $('fecha_inicio').value,
                    tasa_anual_pct:         $('tasa_anual_pct').value,
                    producto_id:            $('producto_id').value,
                    aplicar_iva:            $('aplicar_iva').checked ? 1 : 0,
                    gastos_contrato_activo: gastosActivo ? 1 : 0,
                    gastos_contrato_monto:  gastosMonto,
                };
            }

            function isComplete(data) {
                const pct = parseFloat(data.tasa_anual_pct);
                return data.monto_credito > 0 && data.plazo_meses > 0 &&
                    data.fecha_inicio && pct >= 3 && pct <= 60;
            }

            async function calcular() {
                const data = getFormData();
                if (!isComplete(data)) return;

                $('empty-state').classList.add('hidden');
                $('preview-section').style.display = 'none';
                $('loading-state').classList.remove('hidden');
                $('form-error').classList.add('hidden');

                try {
                    const resp = await fetch(BASE + '/api/cotizaciones/calcular.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    const json = await resp.json();
                    if (json.status !== 'ok') throw new Error(json.message);
                    renderPreview(json);
                    lastData = data;
                    $('btn-guardar').disabled = false;
                } catch (e) {
                    $('loading-state').classList.add('hidden');
                    $('empty-state').classList.remove('hidden');
                    const errEl = $('form-error');
                    errEl.textContent = e.message || 'Error al calcular';
                    errEl.classList.remove('hidden');
                    $('btn-guardar').disabled = true;
                }
            }

            function renderPreview(json) {
                const cab = json.cabecera;
                $('loading-state').classList.add('hidden');

                // Cabecera
                const items = [
                    ['Crédito No.', 'COTIZACIÓN'],
                    ['Plazo:', cab.plazo_meses + ' meses'],
                    ['Fecha Inicio:', cab.fecha_inicio],
                    ['Plazo en Días:', cab.plazo_dias],
                    ['Total Intereses:', fmtMXN(cab.total_intereses)],
                    ['Fecha Límite:', cab.fecha_limite],
                    ['# Meses:', cab.plazo_meses],
                    ['Total a Pagar:', fmtMXN(cab.total_a_pagar)],
                    ['Monto Crédito:', fmtMXN(cab.monto_credito)],
                    ['Tasa Anual:', cab.tasa_pct + ' — ' + cab.tasa_descripcion],
                    ['Producto:', cab.producto],
                    ['Comisión Apertura:', cab.comision_pct_fmt + (cab.comision_monto > 0 ? ' — ' + fmtMXN(cab.comision_monto) : '')],
                    ...(cab.gastos_contrato > 0 ? [['Gastos por Contrato:', '− ' + fmtMXN(cab.gastos_contrato)]] : []),
                    ...(cab.gastos_contrato > 0 ? [['Monto Financiado:', fmtMXN(cab.monto_financiado)]] : []),
                ];

                const cabeceraDiv = $('cabecera-data');
                cabeceraDiv.innerHTML = items.map(([label, val]) =>
                    `<div class="cabecera-item">
                <span class="cabecera-label">${label}</span>
                <span class="cabecera-val">${val}</span>
            </div>`
                ).join('');

                // Tabla de periodos
                const tbody = $('tabla-periodos');
                tbody.innerHTML = json.periodos.map(p =>
                    `<tr>
                <td class="text-center">${p.periodo}</td>
                <td class="text-center">${fmtDate(p.fecha_vencimiento)}</td>
                <td class="text-center">${p.dias}</td>
                <td class="num">${fmtMXN(p.saldo_insoluto)}</td>
                <td class="num">${fmtMXN(p.pago_capital)}</td>
                <td class="num">${fmtMXN(p.interes_ordinario)}</td>
                <td class="num">${fmtMXN(p.iva_interes)}</td>
                <td class="num">${fmtMXN(p.pago_integrado)}</td>
            </tr>`
                ).join('');

                $('preview-section').style.display = 'block';
            }

            function onFieldChange() {
                clearTimeout(debounceTimer);
                $('btn-guardar').disabled = true;
                debounceTimer = setTimeout(calcular, 500);
            }

            // ── Tasa: helpers ──────────────────────────────────────────
            const TASA_DEFAULT = 24;
            let tasaAlertaTimer = null;

            function setTasa(v) {
                $('tasa_anual_pct').value = v;
                $('tasa_slider').value    = v;
            }

            function mostrarTasaAlerta(msg) {
                const el = $('tasa-alerta');
                $('tasa-alerta-msg').textContent = msg;
                el.classList.remove('hidden');
                el.classList.add('flex');
                lucide.createIcons({ nodes: [el] });
                clearTimeout(tasaAlertaTimer);
                tasaAlertaTimer = setTimeout(() => {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }, 4000);
            }

            function ocultarTasaAlerta() {
                clearTimeout(tasaAlertaTimer);
                $('tasa-alerta').classList.add('hidden');
                $('tasa-alerta').classList.remove('flex');
            }

            // Slider → sincroniza input y calcula
            $('tasa_slider').addEventListener('input', () => {
                $('tasa_anual_pct').value = $('tasa_slider').value;
                ocultarTasaAlerta();
                onFieldChange();
            });

            // Mientras escribe → sincroniza slider (sin validar aún)
            $('tasa_anual_pct').addEventListener('input', () => {
                const v = parseFloat($('tasa_anual_pct').value);
                if (!isNaN(v)) {
                    $('tasa_slider').value = Math.min(60, Math.max(3, v));
                }
            });

            // Al salir del campo → valida rango, alerta y restablece si es inválido
            $('tasa_anual_pct').addEventListener('blur', () => {
                const v = parseFloat($('tasa_anual_pct').value);
                if (isNaN(v) || v < 3) {
                    mostrarTasaAlerta(`El mínimo permitido es 3%. Se restableció el valor a ${TASA_DEFAULT}%.`);
                    setTasa(TASA_DEFAULT);
                    onFieldChange();
                } else if (v > 60) {
                    mostrarTasaAlerta(`El máximo permitido es 60%. Se restableció el valor a ${TASA_DEFAULT}%.`);
                    setTasa(TASA_DEFAULT);
                    onFieldChange();
                } else {
                    ocultarTasaAlerta();
                }
            });

            // Listeners generales
            fields.forEach(name => {
                const el = document.getElementById(name);
                if (el) {
                    el.addEventListener('change', onFieldChange);
                    el.addEventListener('input', onFieldChange);
                }
            });
            $('producto_id')?.addEventListener('change', onFieldChange);
            $('aplicar_iva').addEventListener('change', onFieldChange);

            // Gastos por contrato
            $('gastos_contrato_activo').addEventListener('change', function () {
                const campo = $('gastos-contrato-campo');
                if (this.checked) {
                    campo.classList.remove('hidden');
                } else {
                    campo.classList.add('hidden');
                    $('gastos_contrato_monto').value = '';
                    $('gastos-alerta').classList.add('hidden');
                    $('gastos-alerta').classList.remove('flex');
                }
                onFieldChange();
            });

            $('gastos_contrato_monto').addEventListener('input', function () {
                const v = parseFloat(this.value) || 0;
                const alertEl = $('gastos-alerta');
                if (v > 15000) {
                    alertEl.classList.remove('hidden');
                    alertEl.classList.add('flex');
                    lucide.createIcons({ nodes: [alertEl] });
                } else {
                    alertEl.classList.add('hidden');
                    alertEl.classList.remove('flex');
                }
                onFieldChange();
            });

            // Guardar
            $('btn-guardar').addEventListener('click', async () => {
                const clienteId = $('cliente_id').value;
                if (!clienteId) {
                    alert('Por favor selecciona un cliente antes de guardar.');
                    $('cliente_id').focus();
                    return;
                }
                const productoId = $('producto_id').value;
                if (!productoId) {
                    alert('Por favor selecciona un producto antes de guardar.');
                    $('producto_id').focus();
                    return;
                }

                const data = { ...getFormData(), cliente_id: clienteId };

                $('btn-guardar').disabled = true;
                $('btn-guardar').innerHTML = '<span class="spinner" style="width:16px;height:16px;margin-right:6px"></span> Guardando...';

                try {
                    const resp = await fetch(BASE + '/api/cotizaciones/guardar.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    const json = await resp.json();
                    if (json.status !== 'success') throw new Error(json.message);
                    window.location.href = BASE + '/ver_cotizacion.php?id=' + json.data.cotizacion_id;
                } catch (e) {
                    const errEl = $('form-error');
                    errEl.textContent = 'Error al guardar: ' + (e.message || 'Error desconocido');
                    errEl.classList.remove('hidden');
                    $('btn-guardar').disabled = false;
                    $('btn-guardar').innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> Guardar Cotización';
                    lucide.createIcons();
                }
            });

            lucide.createIcons();
        })();
    </script>
</body>

</html>