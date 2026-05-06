<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';

if (isLoggedIn()) {
    header('Location: /wiser-financiera-project/index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'login') {
            $email    = trim($_POST['email']    ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                $error = 'Por favor, completa todos los campos.';
            } else {
                $user = authenticateUser($email, $password);
                if ($user) {
                    createUserSession($user);
                    header('Location: /wiser-financiera-project/index.php');
                    exit;
                } else {
                    $error = 'Correo o contraseña incorrectos.';
                }
            }

        } elseif ($action === 'forgot') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $error = 'Ingresa tu correo electrónico.';
            } elseif (requestPasswordReset($email)) {
                $success = 'Se ha enviado un enlace de recuperación a tu correo.';
            } else {
                $error = 'Correo no encontrado en el sistema.';
            }
        }
    } catch (Exception $e) {
        $error = 'Error del sistema. Inténtalo más tarde.';
        error_log('Error login: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: { extend: { colors: { accent: '#4A90E2', 'accent-dark': '#2563eb' } } }
    }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .login-bg {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f5f3ff 100%);
        }
        .card-shadow { box-shadow: 0 8px 32px rgba(74,144,226,0.12), 0 2px 8px rgba(0,0,0,0.06); }
        .btn-accent {
            background: #4A90E2;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-accent:hover  { background: #2563eb; }
        .btn-accent:active { transform: scale(0.98); }
        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .fade-up {
            animation: fadeUp 0.4s ease both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform: translateY(16px); }
            to   { opacity:1; transform: translateY(0); }
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        <!-- Logo -->
        <div class="text-center mb-8 fade-up">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white card-shadow mb-4">
                <img src="/wiser-financiera-project/assets/img/logo-wiser-website.svg"
                     alt="<?= APP_NAME ?>"
                     class="w-10 h-10 object-contain"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                <span class="hidden font-bold text-accent text-2xl">W</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900"><?= APP_NAME ?></h1>
            <p class="text-gray-500 text-sm mt-1">Plataforma de cotización financiera</p>
        </div>

        <!-- Formulario de Login -->
        <div id="form-login" class="bg-white rounded-2xl card-shadow p-8 fade-up" style="animation-delay:0.1s">

            <?php if ($error): ?>
            <div class="mb-5 flex items-start space-x-2 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-5 flex items-start space-x-2 p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" novalidate>
                <input type="hidden" name="action" value="login">

                <div class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm
                                      focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent
                                      transition-all placeholder-gray-400"
                               placeholder="nombre@empresa.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required autocomplete="current-password"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm pr-10
                                          focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent
                                          transition-all placeholder-gray-400"
                                   placeholder="Tu contraseña">
                            <button type="button" id="togglePassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="btnLogin"
                            class="btn-accent w-full text-white py-3 px-4 rounded-xl font-semibold text-sm
                                   flex items-center justify-center space-x-2">
                        <span id="btnLoginText">Iniciar Sesión</span>
                        <div id="btnLoginSpinner" class="spinner hidden"></div>
                    </button>
                </div>
            </form>

            <div class="mt-5 text-center">
                <button id="btnForgot" class="text-sm text-accent hover:text-accent-dark transition-colors">
                    ¿Olvidaste tu contraseña?
                </button>
            </div>
        </div>

        <!-- Formulario de recuperación -->
        <div id="form-forgot" class="bg-white rounded-2xl card-shadow p-8 fade-up hidden">
            <div class="text-center mb-6">
                <h2 class="text-lg font-bold text-gray-900">Recuperar contraseña</h2>
                <p class="text-sm text-gray-500 mt-1">Ingresa tu correo y te enviaremos un enlace</p>
            </div>

            <form id="forgotForm" method="POST" novalidate>
                <input type="hidden" name="action" value="forgot">
                <div class="space-y-4">
                    <div>
                        <label for="forgotEmail" class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                        <input type="email" id="forgotEmail" name="email" required
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm
                                      focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent transition-all"
                               placeholder="tu@correo.com">
                    </div>
                    <button type="submit" id="btnForgotSubmit"
                            class="btn-accent w-full text-white py-3 rounded-xl font-semibold text-sm
                                   flex items-center justify-center space-x-2">
                        <span>Enviar enlace</span>
                        <div class="spinner hidden"></div>
                    </button>
                </div>
            </form>

            <div class="mt-5 text-center">
                <button id="btnBackLogin" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                    ← Volver al login
                </button>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">&copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos los derechos reservados.</p>
    </div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword')?.addEventListener('click', function() {
        const input = document.getElementById('password');
        input.type  = input.type === 'password' ? 'text' : 'password';
    });

    // Toggle formularios
    document.getElementById('btnForgot')?.addEventListener('click', function() {
        document.getElementById('form-login').classList.add('hidden');
        document.getElementById('form-forgot').classList.remove('hidden');
    });
    document.getElementById('btnBackLogin')?.addEventListener('click', function() {
        document.getElementById('form-forgot').classList.add('hidden');
        document.getElementById('form-login').classList.remove('hidden');
    });

    // Loading state al enviar login
    document.getElementById('loginForm')?.addEventListener('submit', function() {
        document.getElementById('btnLoginText').textContent = 'Iniciando…';
        document.getElementById('btnLoginSpinner').classList.remove('hidden');
        document.getElementById('btnLogin').disabled = true;
    });
</script>
</body>
</html>
