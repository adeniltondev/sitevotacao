<?php
/**
 * Logout de Eleitores
 */
require_once '../config/functions.php';

iniciarSessao();
registrarLog('Logout eleitor realizado');
// Destruir sessão do eleitor
unset($_SESSION['eleitor_id']);
unset($_SESSION['eleitor_cpf']);
unset($_SESSION['eleitor_nome']);
unset($_SESSION['eleitor_cargo']);
unset($_SESSION['eleitor_foto']);
unset($_SESSION['eleitor_perfil']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('darkMode') === '1' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        .dark body { background: #18181b !important; color: #f3f4f6 !important; }
        .dark .bg-white { background: #23232a !important; color: #f3f4f6 !important; }
        .dark .text-gray-800 { color: #f3f4f6 !important; }
        .dark .text-gray-600 { color: #d1d5db !important; }
        .dark .bg-gray-50 { background: #23232a !important; }
        .dark .bg-green-100 { background: #14532d !important; color: #bbf7d0 !important; }
        .dark .bg-red-100 { background: #7f1d1d !important; color: #fecaca !important; }
        .dark .bg-blue-600 { background: #1e40af !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-md p-8 max-w-md w-full mx-4 text-center">
        <h1 class="text-2xl font-bold text-blue-600 mb-4">Logout realizado</h1>
        <p class="text-gray-600 mb-6">Você saiu do sistema com sucesso.</p>
        <a href="login.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Voltar ao Login</a>
    </div>
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>
    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const dark = document.documentElement.classList.contains('dark');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        });
        setTimeout(function() { window.location.href = 'login.php'; }, 2000);
    </script>
</body>
</html>
<?php
exit;
