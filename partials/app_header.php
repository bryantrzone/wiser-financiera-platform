<?php
/**
 * app_header.php — Barra superior
 * Variables esperadas: $user (array con id, full_name, role)
 */
$userInitial = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
?>
<header class="app-header fixed top-0 left-0 right-0 z-40 shadow-sm"
    style="background:#1f2937; border-bottom:1px solid rgba(255,255,255,0.08);">
    <div class="flex items-center justify-between h-16 px-4">

        <!-- Izquierda: hamburger + logo -->
        <div class="flex items-center space-x-3">
            <button id="menu-hamburguesa" class="p-2 rounded-lg transition-colors" style="color:rgba(255,255,255,0.7);"
                onmouseover="this.style.background='rgba(255,255,255,0.08)'"
                onmouseout="this.style.background='transparent'" aria-label="Abrir menú">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
            <a href="/wiser-financiera-project/index.php" class="flex items-center">
                <img src="/wiser-financiera-project/assets/img/logo-wiser-website.svg" alt="<?= APP_NAME ?>"
                    class="h-12 w-auto"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='inline';">
                <span class="hidden font-bold text-white text-lg">Wiser Financiera</span>
            </a>
        </div>

        <!-- Derecha: usuario -->
        <div class="flex items-center space-x-3">
            <div class="hidden sm:flex flex-col items-end">
                <span class="text-sm font-medium text-white"><?= htmlspecialchars($user['full_name'] ?? '') ?></span>
                <span class="text-xs capitalize"
                    style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($user['role'] ?? '') ?></span>
            </div>
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm"
                style="background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.2);">
                <?= $userInitial ?>
            </div>
        </div>
    </div>
</header>
<!-- Espaciador para el header fijo -->
<div class="h-16"></div>