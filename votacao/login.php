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
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>
    <div class="max-w-md w-full mx-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-600 mb-2">Sistema de Votação</h1>
            <p class="text-gray-600">Câmara Municipal</p>
        </div>

        <!-- Card de Login -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login do Eleitor</h2>

            <?php if ($mensagem): ?>
                <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400' ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                <div>
                    <label for="cpf" class="block text-gray-700 font-medium mb-2">CPF</label>
                    <input 
                        type="text" 
                        id="cpf" 
                        name="cpf" 
                        required
                        maxlength="14"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                        placeholder="000.000.000-00"
                        autofocus
                    >
                    <p class="text-sm text-gray-500 mt-1">Informe seu CPF para acessar o sistema</p>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-bold text-lg"
                >
                    Entrar
                </button>
            </form>
        </div>

        <div class="text-center mt-6 text-sm text-gray-500">
            <p>Você precisa estar cadastrado para votar</p>
        </div>
    </div>

    <script>
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
    </script>
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
