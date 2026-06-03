<?php
/**
 * app_menu.php — Menú offcanvas para todas las páginas.
 *
 * Variables esperadas:
 *   $user        array   id, full_name, role
 *   $currentPage string  Identificador de la página activa
 *                        Valores: index | cotizaciones | usuarios | catalogos
 */
$currentPage = $currentPage ?? '';
$_uid = $user['id'] ?? 0;
$_init = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$_role = $user['role'] ?? '';
$_isAdmin = $_role === ROLE_ADMIN;
$_canManage = in_array($_role, [ROLE_ADMIN, ROLE_VENDOR]);

function _navCls(string $page, string $current): string
{
    return $page === $current
        ? 'bg-gray-100 text-gray-900'
        : 'text-gray-600 hover:bg-gray-100';
}
function _navTextCls(string $page, string $current): string
{
    return $page === $current ? 'font-semibold' : 'font-medium';
}
?>

<div id="panel-menu" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" id="superposicion-menu"></div>

    <div class="absolute left-0 top-0 h-full w-72 max-w-[88vw] bg-white shadow-xl flex flex-col
                transform -translate-x-full transition-transform duration-300" id="contenido-menu">

        <!-- Cabecera -->
        <div class="flex items-center justify-between px-4 py-3.5 border-b bg-gray-50">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Menú</span>
            <button id="cerrar-menu" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-200 transition-colors">
                <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Usuario -->
        <div class="flex items-center gap-3 px-4 py-4 border-b bg-gray-50">
            <span class="w-9 h-9 rounded-full bg-gray-200 text-gray-600 flex items-center
                         justify-center font-semibold text-sm shrink-0">
                <?= $_init ?>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">
                    <?= htmlspecialchars($user['full_name'] ?? '') ?>
                </p>
                <p class="text-xs text-gray-400 capitalize">
                    <?= htmlspecialchars($_role) ?>
                </p>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 overflow-y-auto p-2 space-y-0.5">

            <!-- Nueva Cotización -->
            <button id="menu-nueva-cotizacion" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                           <?= _navCls('index', $currentPage) ?>">
                <i data-lucide="file-plus-2" class="w-4 h-4 shrink-0 text-gray-400" aria-hidden="true"></i>
                <div class="text-left">
                    <div class="text-sm <?= _navTextCls('index', $currentPage) ?>">Nueva Cotización</div>
                    <div class="text-xs text-gray-400">Crear cotización</div>
                </div>
            </button>

            <?php if ($_canManage): ?>

                <!-- Cotizaciones -->
                <button id="menu-mis-cotizaciones" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                           <?= _navCls('cotizaciones', $currentPage) ?>">
                    <i data-lucide="files" class="w-4 h-4 shrink-0 text-gray-400" aria-hidden="true"></i>
                    <div class="text-left">
                        <div class="text-sm <?= _navTextCls('cotizaciones', $currentPage) ?>">Cotizaciones</div>
                        <div class="text-xs text-gray-400">Ver y administrar</div>
                    </div>
                </button>

            <?php endif; ?>

            <?php if ($_isAdmin): ?>

                <!-- Separador Administración -->
                <div class="pt-3 pb-1 px-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-300">
                        Administración
                    </span>
                </div>

                <button id="menu-usuarios" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                           <?= _navCls('usuarios', $currentPage) ?>">
                    <i data-lucide="settings" class="w-4 h-4 shrink-0 text-gray-400" aria-hidden="true"></i>
                    <div class="text-left">
                        <div class="text-sm <?= _navTextCls('usuarios', $currentPage) ?>">Usuarios</div>
                        <div class="text-xs text-gray-400">Accesos y roles</div>
                    </div>
                </button>

                <button id="menu-catalogos" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                           <?= _navCls('catalogos', $currentPage) ?>">
                    <i data-lucide="layers" class="w-4 h-4 shrink-0 text-gray-400" aria-hidden="true"></i>
                    <div class="text-left">
                        <div class="text-sm <?= _navTextCls('catalogos', $currentPage) ?>">Catálogos</div>
                        <div class="text-xs text-gray-400">Tipos, marcas y modelos</div>
                    </div>
                </button>

            <?php endif; ?>

        </nav>

        <!-- Pie -->
        <div class="p-2 border-t bg-gray-50">
            <button id="menu-logout" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg
                           text-red-500 hover:bg-red-50 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4" aria-hidden="true"></i>
                <span class="text-sm font-medium">Cerrar sesión</span>
            </button>
        </div>

    </div>
</div>

<script>
    (function () {
        function _open() {
            var p = document.getElementById('panel-menu');
            var c = document.getElementById('contenido-menu');
            if (!p || !c) return;
            p.classList.remove('hidden');
            requestAnimationFrame(function () { c.classList.remove('-translate-x-full'); });
        }
        function _close() {
            var p = document.getElementById('panel-menu');
            var c = document.getElementById('contenido-menu');
            if (!p || !c) return;
            c.classList.add('-translate-x-full');
            setTimeout(function () { p.classList.add('hidden'); }, 300);
        }
        function _bind(id, fn) {
            var el = document.getElementById(id);
            if (el && !el.__menuBound) { el.__menuBound = true; el.addEventListener('click', fn); }
        }
        function _nav(id, url) {
            _bind(id, function () { _close(); setTimeout(function () { window.location.href = url; }, 50); });
        }

        _bind('menu-hamburguesa', _open);
        _bind('cerrar-menu', _close);
        _bind('superposicion-menu', _close);

        _nav('menu-nueva-cotizacion', '/index.php');
        _nav('menu-mis-cotizaciones', '/cotizaciones.php');
        _nav('menu-usuarios', '/usuarios.php');
        _nav('menu-catalogos', '/catalogos.php');

        _bind('menu-logout', function () {
            _close();
            setTimeout(function () { window.location.href = '/logout.php'; }, 50);
        });
    })();
</script>