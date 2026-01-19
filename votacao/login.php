<?php
/**
 * Login de Eleitores
 */
require_once '../config/database.php';
require_once '../config/functions.php';

iniciarSessao();

// Se já estiver logado, redireciona para votação
if (isset($_SESSION['eleitor_id'])) {
    header('Location: index.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRFToken()) {
        $mensagem = 'Token de segurança inválido. Recarregue a página.';
        $tipo_mensagem = 'error';
        registrarLog('Login eleitor falhou', ['motivo' => 'CSRF token inválido']);
    } else {
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        if (empty($cpf)) {
            $mensagem = 'Informe seu CPF';
            $tipo_mensagem = 'error';
            registrarLog('Login eleitor falhou', ['cpf' => $cpf, 'motivo' => 'Campo vazio']);
        } elseif (!validarCPF($cpf)) {
            $mensagem = 'CPF inválido';
            $tipo_mensagem = 'error';
            registrarLog('Login eleitor falhou', ['cpf' => $cpf, 'motivo' => 'CPF inválido']);
        } else {
            // Buscar eleitor pelo CPF
            $stmt = $pdo->prepare("SELECT * FROM eleitores WHERE cpf = ?");
            $stmt->execute([$cpf]);
            $eleitor = $stmt->fetch();
            if ($eleitor) {
                // Criar sessão do eleitor
                $_SESSION['eleitor_id'] = $eleitor['id'];
                $_SESSION['eleitor_cpf'] = $eleitor['cpf'];
                $_SESSION['eleitor_nome'] = $eleitor['nome'];
                $_SESSION['eleitor_cargo'] = $eleitor['cargo'];
                $_SESSION['eleitor_foto'] = $eleitor['foto'];
                    $_SESSION['eleitor_perfil'] = $eleitor['perfil'];
                registrarLog('Login eleitor realizado', ['cpf' => $cpf]);
                header('Location: index.php');
                exit;
            } else {
                $mensagem = 'CPF não cadastrado. Entre em contato com o administrador.';
                $tipo_mensagem = 'error';
                registrarLog('Login eleitor falhou', ['cpf' => $cpf, 'motivo' => 'CPF não cadastrado']);
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
            <div class="inline-flex items-center justify-center p-3 bg-blue-600 rounded-xl mb-4 shadow-lg shadow-blue-600/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">Vota<span class="text-blue-600">Câmara</span></h1>
            <p class="text-gray-500 dark:text-gray-400">Área de Votação</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-2xl p-8 shadow-xl backdrop-blur-xl">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-6 text-center">Login do Eleitor</h2>

            <?php if ($mensagem): ?>
                <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-400 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800/30' ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                
                <div class="space-y-2">
                    <label for="cpf" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .6.4 1 1 1 1 1 0 011 1v3" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="cpf" 
                            name="cpf" 
                            required
                            maxlength="14"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-white/50 dark:bg-gray-800/50 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200"
                            placeholder="000.000.000-00"
                            autofocus
                        >
                    </div>
                </div>

                <button 
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:shadow-lg hover:shadow-blue-600/30"
                >
                    Entrar no Sistema
                </button>
            </form>
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Precisa de ajuda? <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Contate o suporte</a>
            </p>
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

        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });

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
