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
    <title>Login Administrativo - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .dark .glass {
            background: rgba(17, 24, 39, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        }
        .dark .glass-card {
            background: rgba(31, 41, 55, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300  flex items-center justify-center p-4">
    
    <!-- Dark Mode Toggle -->
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 p-2 rounded-lg glass hover:bg-white/50 dark:hover:bg-gray-800/50 transition-all duration-300">
        <span id="icone-modo" class="text-xl">🌙</span>
    </button>

    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <?php if (!empty($settings['logo_path']) && file_exists('../' . $settings['logo_path'])): ?>
                <img src="../<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= htmlspecialchars($settings['sistema_nome'] ?? 'Logo') ?>" class="h-20 w-auto mx-auto mb-4">
            <?php else: ?>
                <div class="inline-flex items-center justify-center p-3 bg-red-600 rounded-xl mb-4 shadow-lg shadow-red-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            <?php endif; ?>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                <?php if (!empty($settings['logo_path']) && file_exists('../' . $settings['logo_path'])): ?>
                    <?= htmlspecialchars($settings['sistema_nome'] ?? 'Painel Administrativo') ?>
                <?php else: ?>
                    Vota<span class="text-red-600">Admin</span>
                <?php endif; ?>
            </h1>
            <p class="text-gray-500 dark:text-gray-400">Painel Administrativo</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-2xl p-8 shadow-xl backdrop-blur-xl">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-6 text-center">Acesso Restrito</h2>

            <?php if ($erro): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800/30">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                
                <div class="space-y-2">
                    <label for="usuario" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            required
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-white/50 dark:bg-gray-800/50 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-all duration-200"
                            placeholder="Digite seu usuário"
                            autofocus
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="senha" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            required
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-white/50 dark:bg-gray-800/50 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-all duration-200"
                            placeholder="Digite sua senha"
                        >
                    </div>
                </div>

                <button 
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 hover:shadow-lg hover:shadow-red-600/30"
                >
                    Entrar no Painel
                </button>
            </form>
        </div>

        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Credenciais padrão: <strong>admin</strong> / <strong>admin123</strong></p>
        </div>
    </div>

    <script>
        // Inicializar modo escuro
        if (localStorage.getItem('darkMode') === '1' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
        }

        // Set icon on load
        const dark = document.documentElement.classList.contains('dark');
        document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
    </script>
</body>
</html>
