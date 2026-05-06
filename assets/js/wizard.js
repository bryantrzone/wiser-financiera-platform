/**
 * wizard.js — Wiser Financiera
 * Controlador del asistente de cotización (4 pasos)
 */
'use strict';

const Wizard = (() => {
    // ── Estado interno ─────────────────────────────────────
    let estado = {
        paso:          1,
        moneda:        'MXN',
        // Paso 1
        tipo_equipo:   '',
        marca:         '',
        modelo:        '',
        descripcion:   '',
        cantidad:      1,
        costo_unitario: 0,
        tipo_cambio:   17.50,
        // Paso 2
        tipo_financiamiento: 'arrendamiento_financiero',
        plazo_meses:   24,
        tasa_anual:    18,
        anticipo_pct:  0,
        residual_pct:  20,
        seguro_pct:    2.5,
        // Calculados
        anticipo_monto:  0,
        residual_monto:  0,
        pago_equipo:     0,
        pago_seguro:     0,
        subtotal:        0,
        iva:             0,
        pago_mensual:    0,
        // Paso 3
        cliente_nombre:   '',
        cliente_empresa:  '',
        cliente_rfc:      '',
        cliente_email:    '',
        cliente_telefono: '',
        fecha_vencimiento: '',
        notas: '',
    };

    // ── Helpers de formato ─────────────────────────────────
    const fmt = (n, decimals = 2) =>
        new Intl.NumberFormat('es-MX', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(n);

    const fmtMonto = (n) => '$' + fmt(n) + ' ' + estado.moneda;

    // ── Motor de cálculo financiero ────────────────────────
    // Fórmula basada en interés simple (espejo del proyecto Wiser arrendadora)
    function calcular() {
        const costo    = parseFloat(estado.costo_unitario) || 0;
        const cantidad = parseInt(estado.cantidad) || 1;
        const plazo    = parseInt(estado.plazo_meses) || 24;
        const tasa     = parseFloat(estado.tasa_anual) / 100;
        const antPct   = parseFloat(estado.anticipo_pct) / 100;
        const resPct   = parseFloat(estado.residual_pct) / 100;
        const segPct   = parseFloat(estado.seguro_pct) / 100;

        // Costo en MXN
        const costoMXN = estado.moneda === 'USD'
            ? costo * (parseFloat(estado.tipo_cambio) || 17.5)
            : costo;

        const totalBase      = costoMXN * cantidad;
        const anticipoMonto  = totalBase * antPct;
        const baseNeta       = Math.max(0, totalBase - anticipoMonto);
        const margen         = 1 + (plazo * (tasa / 12));
        const costoVenta     = baseNeta * margen;
        const residualMonto  = costoVenta * resPct;
        const pagoEquipo     = costoVenta > 0 ? (costoVenta - residualMonto) / plazo : 0;
        const pagoSeguro     = (costoMXN * segPct) / 12;
        const subtotal       = pagoEquipo + pagoSeguro;
        const iva            = subtotal * 0.16;
        const pagoMensual    = subtotal + iva;

        estado.anticipo_monto = anticipoMonto;
        estado.residual_monto = residualMonto;
        estado.pago_equipo    = pagoEquipo;
        estado.pago_seguro    = pagoSeguro;
        estado.subtotal       = subtotal;
        estado.iva            = iva;
        estado.pago_mensual   = pagoMensual;

        return { anticipoMonto, residualMonto, pagoEquipo, pagoSeguro, subtotal, iva, pagoMensual };
    }

    // ── Actualizar preview del paso 2 ──────────────────────
    function actualizarPreview() {
        const r = calcular();
        const preview = document.getElementById('calc-preview');

        if (estado.costo_unitario > 0) {
            preview.classList.remove('hidden');
            document.getElementById('lbl-pago-equipo').textContent  = fmtMonto(r.pagoEquipo);
            document.getElementById('lbl-pago-seguro').textContent  = fmtMonto(r.pagoSeguro);
            document.getElementById('lbl-iva').textContent          = fmtMonto(r.iva);
            document.getElementById('lbl-total-mensual').textContent = fmtMonto(r.pagoMensual);
        } else {
            preview.classList.add('hidden');
        }

        document.getElementById('lbl-anticipo-monto').textContent =
            fmtMonto(r.anticipoMonto);
        document.getElementById('lbl-residual-monto').textContent =
            fmtMonto(r.residualMonto);
    }

    // ── Cargar catálogos ───────────────────────────────────
    async function cargarCatalogos() {
        try {
            const res  = await fetch('/wiser-financiera-project/api/catalogos/listar.php');
            const data = await res.json();
            if (data.status !== 'success') return;

            const selTipo  = document.getElementById('tipo_equipo');
            const selMarca = document.getElementById('marca');

            (data.data.tipos_equipo || []).forEach(t => {
                selTipo.innerHTML += `<option value="${t.nombre}">${t.nombre}</option>`;
            });
            (data.data.marcas || []).forEach(m => {
                selMarca.innerHTML += `<option value="${m.nombre}">${m.nombre}</option>`;
            });
        } catch (e) {
            console.warn('No se pudieron cargar catálogos:', e);
        }
    }

    // ── Navegación entre pasos ─────────────────────────────
    function irAPaso(nuevo) {
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('hidden'));
        document.getElementById(`step-${nuevo}`)?.classList.remove('hidden');

        estado.paso = nuevo;

        // Botones
        document.getElementById('btn-anterior').disabled = nuevo === 1;
        const btnSig      = document.getElementById('btn-siguiente');
        const btnSigText  = document.getElementById('btn-siguiente-text');
        const btnSigIcon  = document.getElementById('btn-siguiente-icon');

        if (nuevo === 4) {
            btnSigText.textContent = 'Guardar cotización';
            btnSigIcon.setAttribute('data-lucide', 'save');
        } else {
            btnSigText.textContent = 'Siguiente';
            btnSigIcon.setAttribute('data-lucide', 'arrow-right');
        }
        lucide.createIcons();

        // Contador
        document.getElementById('step-counter').textContent = `Paso ${nuevo} de 4`;

        // Barra de progreso
        const pct = ((nuevo - 1) / 3) * 100;
        document.getElementById('progress-bar').style.width = pct + '%';

        // Círculos indicadores
        document.querySelectorAll('.step-indicator').forEach(ind => {
            const n      = parseInt(ind.dataset.step);
            const circle = ind.querySelector('.step-circle');
            const label  = ind.querySelector('.step-label');
            circle.classList.remove('active', 'done');
            label?.classList.remove('active', 'done');
            if (n < nuevo) { circle.classList.add('done');  label?.classList.add('done'); circle.innerHTML = '✓'; }
            else if (n === nuevo) { circle.classList.add('active'); label?.classList.add('active'); circle.innerHTML = n; }
            else { circle.innerHTML = n; }
        });

        if (nuevo === 4) construirResumen();
    }

    // ── Validación por paso ────────────────────────────────
    function validarPasoActual() {
        const errores = [];
        switch (estado.paso) {
            case 1:
                if (!estado.costo_unitario || estado.costo_unitario <= 0) errores.push('Ingresa el costo unitario del equipo.');
                if (!estado.cantidad || estado.cantidad < 1)               errores.push('La cantidad debe ser al menos 1.');
                break;
            case 2:
                if (!estado.plazo_meses) errores.push('Selecciona el plazo.');
                break;
            case 3:
                if (!estado.cliente_nombre.trim()) errores.push('Ingresa el nombre del cliente.');
                break;
        }
        return errores;
    }

    function mostrarErrores(errores) {
        if (!errores.length) return;
        const msg = errores.join('\n');
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 right-6 z-50 bg-red-500 text-white px-5 py-3 rounded-xl shadow-lg text-sm font-medium max-w-sm fade-in';
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // ── Construir resumen paso 4 ────────────────────────────
    function construirResumen() {
        calcular();

        const TIPOS = {
            arrendamiento_financiero: 'Arrendamiento Financiero',
            arrendamiento_puro:       'Arrendamiento Puro',
            credito_simple:           'Crédito Simple',
        };

        function item(dt, dd) {
            return `<div class="resumen-row"><dt>${dt}</dt><dd>${dd || '—'}</dd></div>`;
        }

        document.getElementById('resumen-equipo').innerHTML = [
            item('Tipo de equipo', estado.tipo_equipo),
            item('Marca',          estado.marca),
            item('Modelo',         estado.modelo || '—'),
            item('Cantidad',       estado.cantidad),
            item('Costo unitario', fmtMonto(estado.costo_unitario)),
        ].join('');

        document.getElementById('resumen-financiero').innerHTML = [
            item('Tipo de financiamiento', TIPOS[estado.tipo_financiamiento] || estado.tipo_financiamiento),
            item('Plazo',     estado.plazo_meses + ' meses'),
            item('Tasa anual', estado.tasa_anual + '%'),
            item('Anticipo',  estado.anticipo_pct + '% — ' + fmtMonto(estado.anticipo_monto)),
            item('Residual',  estado.residual_pct + '% — ' + fmtMonto(estado.residual_monto)),
            item('Seguro',    estado.seguro_pct + '% anual'),
        ].join('');

        document.getElementById('resumen-cliente').innerHTML = [
            item('Nombre',   estado.cliente_nombre),
            item('Empresa',  estado.cliente_empresa),
            item('RFC',      estado.cliente_rfc),
            item('Correo',   estado.cliente_email),
            item('Teléfono', estado.cliente_telefono),
        ].join('');

        document.getElementById('resumen-pago-mensual').textContent = '$' + fmt(estado.pago_mensual);
        document.getElementById('resumen-moneda').textContent       = estado.moneda + ' / mes';
        document.getElementById('resumen-plazo').textContent        = estado.plazo_meses + ' pagos mensuales';
    }

    // ── Guardar cotización ─────────────────────────────────
    async function guardarCotizacion() {
        const btn      = document.getElementById('btn-siguiente');
        const text     = document.getElementById('btn-siguiente-text');
        const spinner  = document.getElementById('btn-save-spinner');
        const icon     = document.getElementById('btn-siguiente-icon');
        const statusEl = document.getElementById('save-status');

        btn.disabled         = true;
        text.textContent     = 'Guardando…';
        icon.classList.add('hidden');
        spinner.classList.remove('hidden');

        try {
            const payload = { ...estado };
            const res     = await fetch('/wiser-financiera-project/api/cotizaciones/guardar.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await res.json();

            if (data.status === 'success') {
                statusEl.className = 'mt-5 p-4 rounded-xl text-sm bg-green-50 border border-green-200 text-green-800 flex items-center space-x-2';
                statusEl.innerHTML = `<svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Cotización guardada con folio <strong>${data.data?.folio || ''}</strong>. <a href="/wiser-financiera-project/cotizaciones.php" class="underline">Ver cotizaciones →</a></span>`;
                statusEl.classList.remove('hidden');
                btn.textContent = '¡Guardado!';
                btn.disabled    = true;
            } else {
                throw new Error(data.message || 'Error al guardar');
            }
        } catch (e) {
            statusEl.className = 'mt-5 p-4 rounded-xl text-sm bg-red-50 border border-red-200 text-red-700';
            statusEl.textContent = 'Error: ' + e.message;
            statusEl.classList.remove('hidden');
            btn.disabled     = false;
            text.textContent = 'Reintentar';
        } finally {
            spinner.classList.add('hidden');
            icon.classList.remove('hidden');
            lucide.createIcons();
        }
    }

    // ── Sincronizar estado desde el DOM ────────────────────
    function leerPaso1() {
        estado.tipo_equipo    = document.getElementById('tipo_equipo')?.value    || '';
        estado.marca          = document.getElementById('marca')?.value          || '';
        estado.modelo         = document.getElementById('modelo')?.value         || '';
        estado.descripcion    = document.getElementById('descripcion')?.value    || '';
        estado.cantidad       = parseInt(document.getElementById('cantidad')?.value) || 1;
        estado.costo_unitario = parseFloat(document.getElementById('costo_unitario')?.value) || 0;
        estado.tipo_cambio    = parseFloat(document.getElementById('tipo_cambio')?.value) || 17.5;
    }

    function leerPaso2() {
        const checkedRadio = document.querySelector('input[name="tipo_financiamiento"]:checked');
        estado.tipo_financiamiento = checkedRadio?.value || 'arrendamiento_financiero';
        estado.plazo_meses  = parseInt(document.getElementById('plazo_meses')?.value)  || 24;
        estado.tasa_anual   = parseFloat(document.getElementById('tasa_anual')?.value)  || 0;
        estado.anticipo_pct = parseFloat(document.getElementById('anticipo_pct')?.value) || 0;
        estado.residual_pct = parseFloat(document.getElementById('residual_pct')?.value) || 20;
        estado.seguro_pct   = parseFloat(document.getElementById('seguro_pct')?.value)   || 0;
    }

    function leerPaso3() {
        estado.cliente_nombre   = document.getElementById('cliente_nombre')?.value   || '';
        estado.cliente_empresa  = document.getElementById('cliente_empresa')?.value  || '';
        estado.cliente_rfc      = document.getElementById('cliente_rfc')?.value      || '';
        estado.cliente_email    = document.getElementById('cliente_email')?.value    || '';
        estado.cliente_telefono = document.getElementById('cliente_telefono')?.value || '';
        estado.fecha_vencimiento = document.getElementById('fecha_vencimiento')?.value || '';
        estado.notas            = document.getElementById('notas')?.value            || '';
    }

    // ── Bind de eventos ────────────────────────────────────
    function init() {
        cargarCatalogos();

        // Moneda toggle
        document.querySelectorAll('.moneda-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                estado.moneda = this.dataset.moneda;
                document.querySelectorAll('.moneda-btn').forEach(b => {
                    const activo = b.dataset.moneda === estado.moneda;
                    b.classList.toggle('bg-accent',   activo);
                    b.classList.toggle('text-white',  activo);
                    b.classList.toggle('bg-white',    !activo);
                    b.classList.toggle('text-gray-500', !activo);
                });
                document.getElementById('tipo_cambio').disabled = estado.moneda === 'MXN';
                actualizarPreview();
            });
        });

        // Paso 2 — recalcular al cambiar cualquier input
        ['plazo_meses','tasa_anual','anticipo_pct','residual_pct','seguro_pct'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                leerPaso1(); leerPaso2(); actualizarPreview();
            });
        });

        // Recalcular al cambiar costo o cantidad (desde paso 1)
        ['costo_unitario','cantidad','tipo_cambio'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                leerPaso1(); actualizarPreview();
            });
        });

        // RFC en mayúsculas
        document.getElementById('cliente_rfc')?.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Tipo de financiamiento
        document.querySelectorAll('input[name="tipo_financiamiento"]').forEach(radio => {
            radio.addEventListener('change', () => leerPaso2());
        });

        // Botones de navegación
        document.getElementById('btn-siguiente').addEventListener('click', () => {
            // Leer estado del paso actual
            if (estado.paso === 1) leerPaso1();
            if (estado.paso === 2) leerPaso2();
            if (estado.paso === 3) leerPaso3();

            const errores = validarPasoActual();
            if (errores.length) { mostrarErrores(errores); return; }

            if (estado.paso === 4) {
                guardarCotizacion();
            } else {
                irAPaso(estado.paso + 1);
            }
        });

        document.getElementById('btn-anterior').addEventListener('click', () => {
            if (estado.paso > 1) irAPaso(estado.paso - 1);
        });

        // Iniciar en paso 1
        irAPaso(1);
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => Wizard.init());
