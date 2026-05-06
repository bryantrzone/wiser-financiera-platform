/**
 * usuarios.js — Wiser Financiera
 * Administración de usuarios (CRUD)
 */
'use strict';

const Usuarios = (() => {
    let paginaActual = 1;
    let totalPaginas = 1;
    let usuarioAEliminar = null;
    const POR_PAGINA = 15;

    // ── Cargar y renderizar usuarios ────────────────────────
    async function cargarUsuarios() {
        const buscar = document.getElementById('buscar-usuario')?.value  || '';
        const rol    = document.getElementById('filtro-rol')?.value      || '';
        const activo = document.getElementById('filtro-estado')?.value;

        const params = new URLSearchParams({
            pagina: paginaActual,
            por_pagina: POR_PAGINA,
            ...(buscar && { buscar }),
            ...(rol    && { rol }),
            ...(activo !== '' && activo !== null && activo !== undefined && { activo }),
        });

        const tbody = document.getElementById('tabla-usuarios');
        tbody.innerHTML = `
            <tr><td colspan="6" class="px-6 py-10 text-center">
                <div class="flex flex-col items-center text-gray-400 space-y-2">
                    <div class="spinner-dark"></div>
                    <p class="text-sm">Cargando…</p>
                </div>
            </td></tr>`;

        try {
            const res  = await fetch('/wiser-financiera-project/api/usuarios/listar.php?' + params);
            const data = await res.json();

            if (data.status !== 'success') throw new Error(data.message);

            totalPaginas = data.data.total_paginas || 1;
            renderTabla(data.data.usuarios || []);
            renderPaginacion(data.data.total || 0);
            lucide.createIcons();
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-red-500 text-sm">Error: ${e.message}</td></tr>`;
        }
    }

    function renderTabla(usuarios) {
        const tbody = document.getElementById('tabla-usuarios');
        if (!usuarios.length) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center text-gray-400 space-y-2">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm">No se encontraron usuarios</p>
                    </div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = usuarios.map(u => {
            const inicial     = (u.full_name || 'U')[0].toUpperCase();
            const badgeRol    = rolBadge(u.role);
            const badgeEstado = u.active == 1
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">Activo</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactivo</span>';
            const ultimoAcceso = u.last_login
                ? new Date(u.last_login).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' })
                : 'Nunca';

            return `
            <tr class="tabla-fila" data-id="${u.id}">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-accent flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                            ${inicial}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">${esc(u.full_name)}</p>
                            <p class="text-xs text-gray-400">#${u.id}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">${esc(u.email)}</td>
                <td class="px-4 py-4">${badgeRol}</td>
                <td class="px-4 py-4">${badgeEstado}</td>
                <td class="px-4 py-4 text-sm text-gray-400">${ultimoAcceso}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end space-x-2">
                        <button class="btn-editar p-1.5 rounded-lg text-gray-400 hover:bg-blue-50 hover:text-accent transition-colors"
                                data-id="${u.id}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="btn-toggle-activo p-1.5 rounded-lg transition-colors
                                       ${u.active == 1 ? 'text-gray-400 hover:bg-orange-50 hover:text-orange-500' : 'text-gray-400 hover:bg-green-50 hover:text-green-500'}"
                                data-id="${u.id}" data-activo="${u.active}" title="${u.active == 1 ? 'Desactivar' : 'Activar'}">
                            <i data-lucide="${u.active == 1 ? 'user-x' : 'user-check'}" class="w-4 h-4"></i>
                        </button>
                        <button class="btn-eliminar p-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                                data-id="${u.id}" data-nombre="${esc(u.full_name)}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        // Bind de acciones
        document.querySelectorAll('.btn-editar').forEach(btn =>
            btn.addEventListener('click', () => abrirModalEditar(btn.dataset.id)));
        document.querySelectorAll('.btn-toggle-activo').forEach(btn =>
            btn.addEventListener('click', () => toggleActivo(btn.dataset.id, btn.dataset.activo)));
        document.querySelectorAll('.btn-eliminar').forEach(btn =>
            btn.addEventListener('click', () => confirmarEliminar(btn.dataset.id, btn.dataset.nombre)));
    }

    function renderPaginacion(total) {
        document.getElementById('info-paginacion').textContent =
            total ? `${total} usuario${total !== 1 ? 's' : ''}` : 'Sin resultados';

        const container = document.getElementById('btns-paginacion');
        container.innerHTML = '';
        if (totalPaginas <= 1) return;

        const crearBtn = (label, pagina, activo, deshabilitado) => {
            const btn = document.createElement('button');
            btn.innerHTML    = label;
            btn.disabled     = deshabilitado;
            btn.className    = `px-3 py-1.5 text-sm rounded-lg border transition-colors ${
                activo
                    ? 'bg-accent text-white border-accent'
                    : deshabilitado
                        ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'
            }`;
            if (!deshabilitado && !activo) btn.addEventListener('click', () => { paginaActual = pagina; cargarUsuarios(); });
            return btn;
        };

        container.appendChild(crearBtn('‹', paginaActual - 1, false, paginaActual === 1));
        for (let i = 1; i <= totalPaginas; i++) {
            container.appendChild(crearBtn(i, i, i === paginaActual, false));
        }
        container.appendChild(crearBtn('›', paginaActual + 1, false, paginaActual === totalPaginas));
    }

    // ── Modal crear/editar ──────────────────────────────────
    function abrirModal(titulo, datosUsuario = null) {
        const modal   = document.getElementById('modal-usuario');
        const esEditar = !!datosUsuario;

        document.getElementById('modal-titulo').textContent       = titulo;
        document.getElementById('modal-user-id').value           = datosUsuario?.id   || '';
        document.getElementById('modal-full-name').value         = datosUsuario?.full_name || '';
        document.getElementById('modal-email').value             = datosUsuario?.email || '';
        document.getElementById('modal-password').value          = '';
        document.getElementById('modal-role').value              = datosUsuario?.role  || 'vendor';
        document.getElementById('modal-active').checked          = esEditar ? datosUsuario?.active == 1 : true;
        document.getElementById('lbl-pass-hint').textContent     = esEditar ? '(dejar vacío para no cambiar)' : '(requerida)';
        document.getElementById('modal-error').classList.add('hidden');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('modal-full-name').focus();
    }

    function cerrarModal() {
        const modal = document.getElementById('modal-usuario');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function abrirModalEditar(id) {
        try {
            const res  = await fetch(`/wiser-financiera-project/api/usuarios/obtener.php?id=${id}`);
            const data = await res.json();
            if (data.status === 'success') {
                abrirModal('Editar usuario', data.data);
            }
        } catch (e) {
            alert('No se pudo cargar el usuario.');
        }
    }

    // ── Guardar usuario ─────────────────────────────────────
    async function guardarUsuario(e) {
        e.preventDefault();
        const id       = document.getElementById('modal-user-id').value;
        const esEditar = !!id;
        const payload  = {
            id:        id || null,
            full_name: document.getElementById('modal-full-name').value.trim(),
            email:     document.getElementById('modal-email').value.trim(),
            password:  document.getElementById('modal-password').value,
            role:      document.getElementById('modal-role').value,
            active:    document.getElementById('modal-active').checked ? 1 : 0,
        };

        const errModal = document.getElementById('modal-error');
        errModal.classList.add('hidden');

        if (!payload.full_name || !payload.email) {
            errModal.textContent = 'Nombre y correo son obligatorios.';
            errModal.classList.remove('hidden');
            return;
        }
        if (!esEditar && !payload.password) {
            errModal.textContent = 'La contraseña es requerida al crear un usuario.';
            errModal.classList.remove('hidden');
            return;
        }
        if (payload.password && payload.password.length < 8) {
            errModal.textContent = 'La contraseña debe tener al menos 8 caracteres.';
            errModal.classList.remove('hidden');
            return;
        }

        const btnText   = document.getElementById('modal-guardar-text');
        const spinner   = document.getElementById('modal-spinner');
        const btnGuardar = document.getElementById('modal-guardar');
        btnGuardar.disabled  = true;
        btnText.textContent  = 'Guardando…';
        spinner.classList.remove('hidden');

        try {
            const url    = esEditar
                ? '/wiser-financiera-project/api/usuarios/actualizar.php'
                : '/wiser-financiera-project/api/usuarios/crear.php';
            const res    = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModal();
                cargarUsuarios();
            } else {
                errModal.textContent = data.message || 'Error al guardar.';
                errModal.classList.remove('hidden');
            }
        } catch (err) {
            errModal.textContent = 'Error de red. Intenta de nuevo.';
            errModal.classList.remove('hidden');
        } finally {
            btnGuardar.disabled = false;
            btnText.textContent = 'Guardar';
            spinner.classList.add('hidden');
        }
    }

    // ── Toggle activo ───────────────────────────────────────
    async function toggleActivo(id, activoActual) {
        const nuevoEstado = activoActual == 1 ? 0 : 1;
        try {
            const res  = await fetch('/wiser-financiera-project/api/usuarios/actualizar.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id, active: nuevoEstado }),
            });
            const data = await res.json();
            if (data.status === 'success') cargarUsuarios();
            else alert(data.message || 'Error al actualizar.');
        } catch (e) {
            alert('Error de red.');
        }
    }

    // ── Eliminar ────────────────────────────────────────────
    function confirmarEliminar(id, nombre) {
        usuarioAEliminar = id;
        document.getElementById('confirmar-nombre').textContent =
            `¿Eliminar a "${nombre}"? Esta acción no se puede deshacer.`;
        const modal = document.getElementById('modal-confirmar');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    async function eliminarUsuario() {
        if (!usuarioAEliminar) return;
        try {
            const res  = await fetch('/wiser-financiera-project/api/usuarios/eliminar.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id: usuarioAEliminar }),
            });
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('modal-confirmar').classList.add('hidden');
                document.getElementById('modal-confirmar').classList.remove('flex');
                cargarUsuarios();
            } else {
                alert(data.message || 'Error al eliminar.');
            }
        } catch (e) {
            alert('Error de red.');
        }
        usuarioAEliminar = null;
    }

    // ── Utilidades ──────────────────────────────────────────
    function esc(str) {
        return String(str || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function rolBadge(rol) {
        const map = { admin:'badge-admin', vendor:'badge-vendor', client:'badge-client' };
        const labels = { admin:'Admin', vendor:'Vendedor', client:'Cliente' };
        return `<span class="badge-rol ${map[rol] || ''}">${labels[rol] || rol}</span>`;
    }

    // ── Inicialización ──────────────────────────────────────
    function init() {
        cargarUsuarios();

        // Filtros
        document.getElementById('buscar-usuario')?.addEventListener('input', debounce(() => {
            paginaActual = 1; cargarUsuarios();
        }, 350));
        document.getElementById('filtro-rol')?.addEventListener('change',    () => { paginaActual = 1; cargarUsuarios(); });
        document.getElementById('filtro-estado')?.addEventListener('change', () => { paginaActual = 1; cargarUsuarios(); });

        // Modal nuevo usuario
        document.getElementById('btn-nuevo-usuario')?.addEventListener('click', () => abrirModal('Nuevo usuario'));
        document.getElementById('modal-cerrar')?.addEventListener('click',   cerrarModal);
        document.getElementById('modal-cancelar')?.addEventListener('click', cerrarModal);
        document.getElementById('modal-overlay')?.addEventListener('click',  cerrarModal);

        // Submit del form
        document.getElementById('form-usuario')?.addEventListener('submit', guardarUsuario);

        // Toggle contraseña
        document.getElementById('toggle-modal-pass')?.addEventListener('click', function() {
            const input = document.getElementById('modal-password');
            input.type  = input.type === 'password' ? 'text' : 'password';
        });

        // Confirmar eliminar
        document.getElementById('confirmar-eliminar')?.addEventListener('click', eliminarUsuario);
        document.getElementById('confirmar-cancelar')?.addEventListener('click', () => {
            document.getElementById('modal-confirmar').classList.add('hidden');
            document.getElementById('modal-confirmar').classList.remove('flex');
        });
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => Usuarios.init());
