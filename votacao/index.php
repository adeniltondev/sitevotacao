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
        .dark .bg-green-50 { background: #14532d !important; color: #bbf7d0 !important; }
        .dark .bg-red-50 { background: #7f1d1d !important; color: #fecaca !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="text-center flex-1">
                <h1 class="text-4xl font-bold text-blue-600 mb-2">Sistema de Votação</h1>
                <p class="text-gray-600">Câmara Municipal</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['eleitor_nome']) ?></div>
                    <?php if ($_SESSION['eleitor_cargo']): ?>
                        <div class="text-xs text-gray-600"><?= htmlspecialchars($_SESSION['eleitor_cargo']) ?></div>
                    <?php endif; ?>
                </div>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                    Sair
                </a>
            </div>
        </div>

        <?php if (!$votacao): ?>
            <!-- Nenhuma votação ativa -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Nenhuma Votação Ativa</h2>
                <p class="text-gray-600">Não há votação aberta no momento.</p>
            </div>
        <?php else: ?>
            <!-- QR Code para painel de resultados -->
            <div class="flex justify-end mb-4">
                <?php
                $url_resultados = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../painel/resultados.php';
                $qr_url = 'https://chart.googleapis.com/chart?chs=120x120&cht=qr&chl=' . urlencode($url_resultados);
                ?>
                <div class="flex flex-col items-center">
                    <img src="<?= $qr_url ?>" alt="QR Code Resultados" class="w-20 h-20 border rounded bg-white shadow" loading="lazy">
                    <span class="text-xs text-gray-400 mt-1">Resultados em tempo real</span>
                </div>
            </div>
            <!-- Votação Ativa -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($votacao['titulo']) ?></h2>
                        <?php if ($votacao['descricao']): ?>
                            <p class="text-gray-600"><?= htmlspecialchars($votacao['descricao']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-medium">
                        VOTAÇÃO ABERTA
                    </span>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário de Voto -->
                <form id="formVoto" method="POST" action="votar.php" class="space-y-6">
                    <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-4">Seu Voto *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="voto" 
                                    value="sim" 
                                    required
                                    class="peer sr-only"
                                >
                                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center hover:bg-green-100 transition peer-checked:bg-green-500 peer-checked:border-green-600 peer-checked:text-white">
                                    <div class="text-4xl font-bold mb-2">SIM</div>
                                    <div class="text-sm">Aprovar</div>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="voto" 
                                    value="nao" 
                                    required
                                    class="peer sr-only"
                                >
                                <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6 text-center hover:bg-red-100 transition peer-checked:bg-red-500 peer-checked:border-red-600 peer-checked:text-white">
                                    <div class="text-4xl font-bold mb-2">NÃO</div>
                                    <div class="text-sm">Rejeitar</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-bold text-lg"
                    >
                        Confirmar Voto
                    </button>
                </form>
            </div>

            <!-- Link para resultados -->
            <div class="text-center">
                <a href="../painel/resultados.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Ver Resultados em Tempo Real →
                </a>
            </div>
        <?php endif; ?>
    </div>

</body>
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
</html>
