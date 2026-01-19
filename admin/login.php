<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/database.php';
iniciarSessao();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRFToken()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
        registrarLog('Login admin falhou', ['motivo' => 'CSRF token inválido']);
    } else {
        $usuario = sanitizar($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';
        if (empty($usuario) || empty($senha)) {
            $erro = 'Preencha todos os campos';
            registrarLog('Login admin falhou', ['usuario' => $usuario, 'motivo' => 'Campos vazios']);
        } else {
            $stmt = $pdo->prepare("SELECT id, usuario, senha, nome FROM administradores WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($senha, $admin['senha'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                $_SESSION['admin_nome'] = $admin['nome'];
                registrarLog('Login admin realizado', ['usuario' => $usuario]);
                header('Location: dashboard.php');
                exit;
            } else {
                $erro = 'Usuário ou senha incorretos';
                registrarLog('Login admin falhou', ['usuario' => $usuario, 'motivo' => 'Credenciais inválidas']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
        
        // Inicializar modo escuro antes do render
        if (localStorage.getItem('darkMode') === '1' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center transition-colors duration-200">
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>
    <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md w-full max-w-md border border-gray-200 dark:border-gray-700">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600 dark:text-blue-500">Sistema de Votação</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Acesso Administrativo</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
            <div class="mb-4">
                <label for="usuario" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">Usuário</label>
                <input 
                    type="text" 
                    id="usuario" 
                    name="usuario" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="Digite seu usuário"
                >
            </div>
            
            <div class="mb-6">
                <label for="senha" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">Senha</label>
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="Digite sua senha"
                >
            </div>
            
            <button 
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 font-medium shadow-md"
            >
                Entrar
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
            <p>Credenciais padrão: <strong>admin</strong> / <strong>admin123</strong></p>
        </div>
    </div>
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
    </script>
</body>
</html>
