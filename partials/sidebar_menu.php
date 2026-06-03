<?php
/**
 * Menú lateral (offcanvas) — tema claro Wiser Financiera.
 *
 * Variables esperadas:
 *   $user        array  id, full_name, email, role
 *   $currentPage string Página activa para resaltar ítem del menú
 */

$currentPage = $currentPage ?? '';
$userId = $user['id'] ?? 0;
$userInitial = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$isAdmin = ($user['role'] ?? '') === ROLE_ADMIN;
$isVendor = ($user['role'] ?? '') === ROLE_VENDOR;
$puedeGestion = $isAdmin || $isVendor;

function navItemClasses(bool $activo): string
{
    return $activo ? 'brand-nav-active brand-title-active' : '';
}
function navSubClasses(bool $activo): string
{
    return $activo ? 'brand-sub-active' : 'text-gray-500';
}
?>

<div id="panel-menu" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" id="superposicion-menu"></div>

    <div class="absolute left-0 top-0 h-full w-80 max-w-[90vw] brand-sidebar shadow-xl
                transform -translate-x-full transition-transform duration-300 flex flex-col" id="contenido-menu">

        <!-- Logo del encabezado -->
        <div class="flex items-center justify-between p-4 border-b brand-sidebar-section">
            <img src="/assets/img/logo-wiser-website.svg" alt="<?= APP_NAME ?>"
                class="brand-logo h-8 w-auto"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <span class="brand-logo-fallback hidden items-center font-bold text-accent text-lg">WF</span>
            <button id="cerrar-menu" class="p-2 rounded-lg hover:bg-black/5 transition-colors"
                style="color: var(--brand-sidebar-text)">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Avatar + datos del usuario -->
        <div class="p-4 border-b brand-user-section flex items-center space-x-3">
            <div
                class="brand-avatar w-10 h-10 rounded-full flex items-center justify-center font-semibold text-base flex-shrink-0">
                <?= $userInitial ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium truncate text-sm" style="color: var(--brand-sidebar-text)">
                    <?= htmlspecialchars($user['full_name'] ?? 'Usuario') ?>
                </p>
                <p class="text-xs opacity-60 capitalize" style="color: var(--brand-sidebar-text)">
                    <?= htmlspecialchars($user['role'] ?? '') ?>
                </p>
            </div>
        </div>

        <!-- Navegación -->
        <div class="flex-1 overflow-y-auto">
            <nav class="p-4 space-y-1">

                <!-- Nueva Cotización -->
                <button id="menu-nueva-cotizacion" class="w-full flex items-center space-x-3 p-3 rounded-lg brand-nav-item
                               <?= navItemClasses($currentPage === 'index') ?> transition-colors">
                    <i data-lucide="file-plus-2"
                        class="w-5 h-5 <?= $currentPage === 'index' ? 'brand-icon-accent' : '' ?>"
                        style="<?= $currentPage !== 'index' ? 'color:var(--brand-sidebar-text);opacity:.7' : '' ?>"
                        aria-hidden="true"></i>
                    <div class="text-left">
                        <div class="font-medium <?= $currentPage === 'index' ? 'brand-title-active' : '' ?>"
                            style="<?= $currentPage !== 'index' ? 'color:var(--brand-sidebar-text)' : '' ?>">
                            Nueva Cotización
                        </div>
                        <div class="text-xs <?= navSubClasses($currentPage === 'index') ?>">
                            Crear una nueva cotización
                        </div>
                    </div>
                </button>

                <?php if ($puedeGestion): ?>

                    <!-- Gestión de Cotizaciones -->
                    <button id="menu-cotizaciones" class="w-full flex items-center space-x-3 p-3 rounded-lg brand-nav-item
                               <?= navItemClasses($currentPage === 'cotizaciones') ?> transition-colors">
                        <i data-lucide="files" class="w-5 h-5 text-emerald-500" aria-hidden="true"></i>
                        <div class="text-left">
                            <div class="font-medium <?= $currentPage === 'cotizaciones' ? 'brand-title-active' : '' ?>"
                                style="<?= $currentPage !== 'cotizaciones' ? 'color:var(--brand-sidebar-text)' : '' ?>">
                                Cotizaciones
                            </div>
                            <div class="text-xs <?= navSubClasses($currentPage === 'cotizaciones') ?>">
                                Ver y administrar cotizaciones
                            </div>
                        </div>
                    </button>

                    <!-- Clientes -->
                    <button id="menu-clientes" class="w-full flex items-center space-x-3 p-3 rounded-lg brand-nav-item
                               <?= navItemClasses($currentPage === 'clientes') ?> transition-colors">
                        <i data-lucide="users" class="w-5 h-5 text-violet-400" aria-hidden="true"></i>
                        <div class="text-left">
                            <div class="font-medium <?= $currentPage === 'clientes' ? 'brand-title-active' : '' ?>"
                                style="<?= $currentPage !== 'clientes' ? 'color:var(--brand-sidebar-text)' : '' ?>">
                                Clientes
                            </div>
                            <div class="text-xs <?= navSubClasses($currentPage === 'clientes') ?>">
                                Gestión de clientes
                            </div>
                        </div>
                    </button>

                <!-- Clientes -->
                <button id="menu-clientes"
                        class="w-full flex items-center space-x-3 p-3 rounded-lg brand-nav-item
                               <?= navItemClasses($currentPage === 'clientes') ?> transition-colors">
                    <i data-lucide="users" class="w-5 h-5 text-violet-400" aria-hidden="true"></i>
                    <div class="text-left">
                        <div class="font-medium <?= $currentPage === 'clientes' ? 'brand-title-active' : '' ?>"
                             style="<?= $currentPage !== 'clientes' ? 'color:var(--brand-sidebar-text)' : '' ?>">
                            Clientes
                        </div>
                        <div class="text-xs <?= navSubClasses($currentPage === 'clientes') ?>">
                            Gestión de clientes
                        </div>
                    </div>
                </button>

                <?php endif; ?>

                <?php if ($isAdmin): ?>

                    <!-- Usuarios -->
                    <button id="menu-usuarios" class="w-full flex items-center space-x-3 p-3 rounded-lg brand-nav-item
                               <?= navItemClasses($currentPage === 'usuarios') ?> transition-colors">
                        <i data-lucide="settings" class="w-5 h-5 text-slate-400" aria-hidden="true"></i>
                        <div class="text-left">
                            <div class="font-medium <?= $currentPage === 'usuarios' ? 'brand-title-active' : '' ?>"
                                style="<?= $currentPage !== 'usuarios' ? 'color:var(--brand-sidebar-text)' : '' ?>">
                                Usuarios
                            </div>
                            <div class="text-xs <?= navSubClasses($currentPage === 'usuarios') ?>">
                                Administrar accesos y roles
                            </div>
                        </div>
                    </button>

                    <!-- Catálogos -->
                    <button id="menu-catalogos" class="w-full flex items-center space-x-3 p-3 rounded-lg brand-nav-item
                               <?= navItemClasses($currentPage === 'catalogos') ?> transition-colors">
                        <i data-lucide="layers" class="w-5 h-5 text-indigo-400" aria-hidden="true"></i>
                        <div class="text-left">
                            <div class="font-medium <?= $currentPage === 'catalogos' ? 'brand-title-active' : '' ?>"
                                style="<?= $currentPage !== 'catalogos' ? 'color:var(--brand-sidebar-text)' : '' ?>">
                                Catálogos
                            </div>
                            <div class="text-xs <?= navSubClasses($currentPage === 'catalogos') ?>">
                                Tipos, marcas y modelos
                            </div>
                        </div>
                    </button>

                <?php endif; ?>

            </nav>
        </div>

        <!-- Pie del menú -->
        <div class="p-4 border-t brand-sidebar-section">
            <button id="menu-logout" class="w-full flex items-center justify-center space-x-2 p-3 rounded-lg
                           text-red-400 hover:bg-red-50 transition-colors">
                <i data-lucide="log-out" class="w-5 h-5" aria-hidden="true"></i>
                <span class="font-medium text-sm">Cerrar Sesión</span>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const panel    = document.getElementById('contenido-menu');
    const wrapper  = document.getElementById('panel-menu');
    const overlay  = document.getElementById('superposicion-menu');
    const btnOpen  = document.getElementById('abrir-menu');
    const btnClose = document.getElementById('cerrar-menu');

    function openMenu() {
        wrapper.classList.remove('hidden');
        requestAnimationFrame(() => panel.classList.remove('-translate-x-full'));
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        panel.classList.add('-translate-x-full');
        document.body.style.overflow = '';
        panel.addEventListener('transitionend', () => wrapper.classList.add('hidden'), { once: true });
    }

    btnOpen?.addEventListener('click', openMenu);
    btnClose?.addEventListener('click', closeMenu);
    overlay?.addEventListener('click', closeMenu);

    document.getElementById('menu-nueva-cotizacion')?.addEventListener('click', () => {
        window.location.href = '/wiser-financiera-project/index.php';
    });
    document.getElementById('menu-cotizaciones')?.addEventListener('click', () => {
        window.location.href = '/wiser-financiera-project/cotizaciones.php';
    });
    document.getElementById('menu-clientes')?.addEventListener('click', () => {
        window.location.href = '/wiser-financiera-project/clientes.php';
    });
    document.getElementById('menu-usuarios')?.addEventListener('click', () => {
        window.location.href = '/wiser-financiera-project/usuarios.php';
    });
    document.getElementById('menu-catalogos')?.addEventListener('click', () => {
        window.location.href = '/wiser-financiera-project/catalogos.php';
    });
    document.getElementById('menu-logout')?.addEventListener('click', () => {
        if (confirm('¿Deseas cerrar sesión?')) {
            window.location.href = '/wiser-financiera-project/logout.php';
        }

        btnOpen?.addEventListener('click', openMenu);
        btnClose?.addEventListener('click', closeMenu);
        overlay?.addEventListener('click', closeMenu);

        document.getElementById('menu-nueva-cotizacion')?.addEventListener('click', () => {
            window.location.href = '/index.php';
        });
        document.getElementById('menu-cotizaciones')?.addEventListener('click', () => {
            window.location.href = '/cotizaciones.php';
        });
        document.getElementById('menu-clientes')?.addEventListener('click', () => {
            window.location.href = '/clientes.php';
        });
        document.getElementById('menu-usuarios')?.addEventListener('click', () => {
            window.location.href = '/usuarios.php';
        });
        document.getElementById('menu-catalogos')?.addEventListener('click', () => {
            window.location.href = '/catalogos.php';
        });
        document.getElementById('menu-logout')?.addEventListener('click', () => {
            if (confirm('¿Deseas cerrar sesión?')) {
                window.location.href = '/logout.php';
            }
        });
    })();
</script>