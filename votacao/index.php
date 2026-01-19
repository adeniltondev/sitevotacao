<?php
/**
 * Página de Votação - Requer Login
 */
require_once '../config/database.php';
require_once '../config/functions.php';

// Verificar se eleitor está logado
verificarEleitor();

// Buscar votação ativa
$votacao = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();

$mensagem = '';
$tipo_mensagem = '';

if (isset($_GET['sucesso'])) {
    $mensagem = 'Voto registrado com sucesso!';
    $tipo_mensagem = 'success';
} elseif (isset($_GET['erro'])) {
    $mensagem = htmlspecialchars($_GET['erro']);
    $tipo_mensagem = 'error';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Votação - Câmara</title>
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

        // Inicializar modo escuro
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
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-200">
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="text-center flex-1">
                <h1 class="text-4xl font-bold text-blue-600 dark:text-blue-500 mb-2">Sistema de Votação</h1>
                <p class="text-gray-600 dark:text-gray-400">Câmara Municipal</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($_SESSION['eleitor_nome']) ?></div>
                    <?php if ($_SESSION['eleitor_cargo']): ?>
                        <div class="text-xs text-gray-600 dark:text-gray-400"><?= htmlspecialchars($_SESSION['eleitor_cargo']) ?></div>
                    <?php endif; ?>
                </div>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium">
                    Sair
                </a>
            </div>
        </div>

        <?php if (!$votacao): ?>
            <!-- Nenhuma votação ativa -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-100 dark:border-gray-700">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Nenhuma Votação Ativa</h2>
                <p class="text-gray-600 dark:text-gray-400">Não há votação aberta no momento.</p>
            </div>
        <?php else: ?>
            <!-- QR Code para painel de resultados -->
            <div class="flex justify-end mb-4">
                <?php
                $url_resultados = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../painel/resultados.php';
                // Usar api.qrserver.com
                $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($url_resultados);
                ?>
                <div class="flex flex-col items-center">
                    <img src="<?= $qr_url ?>" alt="QR Code Resultados" class="w-20 h-20 border rounded bg-white p-1 shadow" loading="lazy">
                    <span class="text-xs text-gray-400 dark:text-gray-500 mt-1">Resultados em tempo real</span>
                </div>
            </div>
            <!-- Votação Ativa -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 mb-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2"><?= htmlspecialchars($votacao['titulo']) ?></h2>
                        <?php if ($votacao['descricao']): ?>
                            <p class="text-gray-600 dark:text-gray-300"><?= htmlspecialchars($votacao['descricao']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 px-4 py-2 rounded-full text-sm font-medium border border-green-200 dark:border-green-800">
                        VOTAÇÃO ABERTA
                    </span>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800' ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <?php if (($_SESSION['eleitor_perfil'] ?? 'vereador') !== 'vereador'): ?>
                    <div class="p-4 rounded-lg bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800">
                        Você está logado como <strong><?= htmlspecialchars($_SESSION['eleitor_perfil'] ?? 'desconhecido') ?></strong> e não tem permissão para votar.
                    </div>
                <?php else: ?>
                    <!-- Formulário de Voto -->
                    <form id="formVoto" method="POST" action="votar.php" class="space-y-6">
                        <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                        
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-medium mb-4">Seu Voto *</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="relative cursor-pointer group">
                                    <input 
                                        type="radio" 
                                        name="voto" 
                                        value="sim" 
                                        required
                                        class="peer sr-only"
                                    >
                                    <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-lg p-6 text-center hover:bg-green-100 dark:hover:bg-green-900/30 transition peer-checked:bg-green-500 peer-checked:border-green-600 peer-checked:text-white dark:peer-checked:bg-green-600">
                                        <div class="text-4xl font-bold mb-2 text-green-700 dark:text-green-400 peer-checked:text-white group-hover:text-green-800 dark:group-hover:text-green-300">SIM</div>
                                        <div class="text-sm text-green-600 dark:text-green-500 peer-checked:text-white">Aprovar</div>
                                    </div>
                                </label>
                                
                                <label class="relative cursor-pointer group">
                                    <input 
                                        type="radio" 
                                        name="voto" 
                                        value="nao" 
                                        required
                                        class="peer sr-only"
                                    >
                                    <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg p-6 text-center hover:bg-red-100 dark:hover:bg-red-900/30 transition peer-checked:bg-red-500 peer-checked:border-red-600 peer-checked:text-white dark:peer-checked:bg-red-600">
                                        <div class="text-4xl font-bold mb-2 text-red-700 dark:text-red-400 peer-checked:text-white group-hover:text-red-800 dark:group-hover:text-red-300">NÃO</div>
                                        <div class="text-sm text-red-600 dark:text-red-500 peer-checked:text-white">Rejeitar</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button 
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white py-4 px-6 rounded-lg transition duration-200 font-bold text-lg shadow-md"
                        >
                            Confirmar Voto
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Link para resultados -->
            <div class="text-center">
                <a href="../painel/resultados.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium transition">
                    Ver Resultados em Tempo Real →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            atualizarIconeModo(dark);
        }
        
        function atualizarIconeModo(dark) {
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dark = document.documentElement.classList.contains('dark');
            atualizarIconeModo(dark);
        });
    </script>
</body>
</html>