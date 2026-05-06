<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/constants.php';

requireRole(ROLE_ADMIN);

$user = [
    'id'        => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'],
    'email'     => $_SESSION['email'],
    'role'      => $_SESSION['role'],
];
$pageTitle   = 'Usuarios — ' . APP_NAME;
$currentPage = 'usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'partials/brand_head.php'; ?>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'partials/app_header.php'; ?>
<?php include 'partials/app_menu.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-8">

    <!-- Cabecera -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Administración de Usuarios</h1>
            <p class="text-sm text-gray-400 mt-1">Gestiona los accesos y roles del sistema</p>
        </div>
        <button id="btn-nuevo-usuario"
                class="flex items-center space-x-2 px-5 py-2.5 bg-accent text-white
                       rounded-xl text-sm font-semibold hover:bg-accent-dark transition-all">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Nuevo usuario</span>
        </button>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-5 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="buscar-usuario" placeholder="Buscar por nombre o correo…"
                       class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm
                              focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent">
            </div>
            <select id="filtro-rol" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm
                                          focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent bg-white">
                <option value="">Todos los roles</option>
                <option value="admin">Admin</option>
                <option value="vendor">Vendedor</option>
                <option value="client">Cliente</option>
            </select>
            <select id="filtro-estado" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm
                                             focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent bg-white">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Correo</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Último acceso</th>
                        <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-usuarios">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center space-y-2 text-gray-400">
                                <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-accent"></i>
                                <p class="text-sm">Cargando usuarios…</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div id="paginacion" class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
            <p class="text-sm text-gray-400" id="info-paginacion">—</p>
            <div class="flex space-x-2" id="btns-paginacion"></div>
        </div>
    </div>
</main>

<!-- ════════════════════════════════════
     MODAL — Crear / Editar usuario
════════════════════════════════════ -->
<div id="modal-usuario" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-overlay"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg z-10">

        <!-- Header modal -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <h3 id="modal-titulo" class="text-base font-bold text-gray-900">Nuevo usuario</h3>
            <button id="modal-cerrar" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Cuerpo modal -->
        <form id="form-usuario" class="px-6 py-5 space-y-4" novalidate>
            <input type="hidden" id="modal-user-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="wf-label">Nombre completo *</label>
                    <input type="text" id="modal-full-name" class="wf-input" placeholder="Nombre y apellidos" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="wf-label">Correo electrónico *</label>
                    <input type="email" id="modal-email" class="wf-input" placeholder="correo@empresa.com" required>
                </div>
                <div>
                    <label class="wf-label">Contraseña <span id="lbl-pass-hint" class="text-gray-400 font-normal">(requerida)</span></label>
                    <div class="relative">
                        <input type="password" id="modal-password" class="wf-input pr-10" placeholder="Mín. 8 caracteres">
                        <button type="button" id="toggle-modal-pass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="wf-label">Rol *</label>
                    <select id="modal-role" class="wf-select" required>
                        <option value="vendor">Vendedor</option>
                        <option value="admin">Administrador</option>
                        <option value="client">Cliente</option>
                    </select>
                </div>
                <div class="sm:col-span-2 flex items-center space-x-3">
                    <input type="checkbox" id="modal-active" checked
                           class="w-4 h-4 rounded border-gray-300 text-accent focus:ring-accent">
                    <label for="modal-active" class="text-sm font-medium text-gray-700">Usuario activo</label>
                </div>
            </div>

            <!-- Error del modal -->
            <div id="modal-error" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"></div>

            <!-- Botones -->
            <div class="flex space-x-3 pt-2">
                <button type="button" id="modal-cancelar"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium
                               text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="modal-guardar"
                        class="flex-1 py-2.5 bg-accent text-white rounded-xl text-sm font-semibold
                               hover:bg-accent-dark transition-all flex items-center justify-center space-x-2">
                    <span id="modal-guardar-text">Guardar</span>
                    <div class="spinner hidden" id="modal-spinner"></div>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal confirmación eliminar -->
<div id="modal-confirmar" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm z-10 p-6 text-center">
        <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="trash-2" class="w-6 h-6 text-red-500"></i>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-2">¿Eliminar usuario?</h3>
        <p class="text-sm text-gray-500 mb-6" id="confirmar-nombre">Esta acción no se puede deshacer.</p>
        <div class="flex space-x-3">
            <button id="confirmar-cancelar"
                    class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">
                Cancelar
            </button>
            <button id="confirmar-eliminar"
                    class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold hover:bg-red-600 transition-colors">
                Eliminar
            </button>
        </div>
    </div>
</div>

<script src="/wiser-financiera-project/assets/js/usuarios.js?v=<?= time() ?>"></script>
<script>lucide.createIcons();</script>
</body>
</html>
