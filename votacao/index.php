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
    <title>Votação - Câmara Municipal</title>
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
    </script>
    <script>
        // Inicializa tema a partir de localStorage ou preferência do sistema
        if (localStorage.getItem('darkMode') === '1' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @media (prefers-reduced-motion: reduce) {
            .fade-in, .pulse-dot, .voter-card, .status-bar { animation: none !important; transition: none !important; }
        }

        .fade-in { animation: fadeIn 0.5s ease-out; }
        
        /* Modern cards */
        .stat-box {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(17, 24, 39, 0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 10px 30px rgba(0,0,0,0.06);
            backdrop-filter: blur(6px);
        }
        .dark .stat-box {
            background: rgba(17, 24, 39, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.35);
        }

        /* Better dark-mode text colors (readability) */
        .dark .text-gray-900 { color: #f9fafb !important; }
        .dark .text-gray-800 { color: #f3f4f6 !important; }
        .dark .text-gray-700 { color: #e5e7eb !important; }
        .dark .text-gray-600 { color: #d1d5db !important; }
        .dark .text-gray-500 { color: #9ca3af !important; }
        .dark .text-gray-400 { color: #9ca3af !important; }

        .dark .text-blue-600 { color: #60a5fa !important; }
        .dark .text-green-600 { color: #34d399 !important; }
        .dark .text-red-600 { color: #fb7185 !important; }
        .dark .text-yellow-600 { color: #fbbf24 !important; }
        
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100">
    <div class="min-h-screen w-full flex flex-col">
        <!-- Header -->
        <div class="bg-gray-800 border-b border-gray-700 py-4 px-4 sm:px-8 fade-in shadow-lg z-10">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-white tracking-tight">CÂMARA MUNICIPAL</h1>
                        <p class="text-xs md:text-sm text-gray-400 font-medium tracking-wide uppercase">Sistema de Votação</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 justify-between md:justify-end w-full md:w-auto">
                    <div class="text-right hidden sm:block mr-2">
                        <div class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['eleitor_nome']) ?></div>
                        <?php if ($_SESSION['eleitor_cargo']): ?>
                            <div class="text-xs text-gray-400"><?= htmlspecialchars($_SESSION['eleitor_cargo']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="alternarModoEscuro()" class="p-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <span id="icone-modo" class="text-lg">🌙</span>
                        </button>
                        
                        <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium shadow-sm hover:shadow flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden sm:inline">Sair</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-grow flex flex-col items-center justify-start pt-8 pb-12 px-4 sm:px-6">
            <div class="w-full max-w-4xl fade-in" style="animation-delay: 0.1s;">
                
                <?php if (!$votacao): ?>
                    <!-- Nenhuma votação ativa -->
                    <div class="stat-box rounded-2xl p-10 text-center max-w-2xl mx-auto">
                        <div class="mb-6 inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 mb-6">
                            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-3">Nenhuma Votação Ativa</h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400">Aguarde o início da próxima sessão de votação.</p>
                    </div>
                <?php else: ?>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Card Principal da Votação -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Info Votação -->
                            <div class="stat-box rounded-2xl p-6 sm:p-8 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-10">
                                    <svg class="w-32 h-32 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/></svg>
                                </div>
                                
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3 mb-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 border border-green-200 dark:border-green-800">
                                            <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                                            VOTAÇÃO EM ANDAMENTO
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">ID: #<?= $votacao['id'] ?></span>
                                    </div>
                                    
                                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-4 leading-tight"><?= htmlspecialchars($votacao['titulo']) ?></h2>
                                    
                                    <?php if ($votacao['descricao']): ?>
                                        <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700/50">
                                            <?= nl2br(htmlspecialchars($votacao['descricao'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Mensagens -->
                            <?php if ($mensagem): ?>
                                <div class="fade-in rounded-xl p-4 flex items-start gap-3 shadow-sm <?= $tipo_mensagem === 'success' ? 'bg-green-50 text-green-800 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800' : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800' ?>">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <?php if ($tipo_mensagem === 'success'): ?>
                                            <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <?php else: ?>
                                            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="font-medium"><?= htmlspecialchars($mensagem) ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Área de Votação -->
                            <div class="stat-box rounded-2xl p-6 sm:p-8">
                                <?php if (($_SESSION['eleitor_perfil'] ?? 'vereador') !== 'vereador'): ?>
                                    <div class="flex flex-col items-center justify-center p-8 text-center bg-yellow-50 dark:bg-yellow-900/10 rounded-xl border border-yellow-100 dark:border-yellow-900/30">
                                        <svg class="w-12 h-12 text-yellow-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-400 mb-1">Acesso Restrito</h3>
                                        <p class="text-yellow-700 dark:text-yellow-500">
                                            Você está logado como <strong><?= htmlspecialchars($_SESSION['eleitor_perfil'] ?? 'desconhecido') ?></strong> e não possui permissão para votar.
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <form id="formVoto" method="POST" action="votar.php" class="space-y-8">
                                        <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                        
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-2">
                                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Registre seu Voto
                                            </h3>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                                <!-- Opção SIM -->
                                                <label class="relative cursor-pointer group">
                                                    <input type="radio" name="voto" value="sim" required class="peer sr-only">
                                                    <div class="h-full flex flex-col items-center justify-center p-8 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-green-500 dark:hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/10 transition-all duration-200 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:shadow-lg dark:peer-checked:bg-green-900/20">
                                                        <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4 text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform peer-checked:bg-green-500 peer-checked:text-white">
                                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                        <span class="text-2xl font-bold text-gray-700 dark:text-gray-200 group-hover:text-green-700 dark:group-hover:text-green-400 peer-checked:text-green-700 dark:peer-checked:text-green-400">SIM</span>
                                                        <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aprovar</span>
                                                    </div>
                                                    <div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 transition-opacity text-green-500">
                                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    </div>
                                                </label>
                                                
                                                <!-- Opção NÃO -->
                                                <label class="relative cursor-pointer group">
                                                    <input type="radio" name="voto" value="nao" required class="peer sr-only">
                                                    <div class="h-full flex flex-col items-center justify-center p-8 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-red-500 dark:hover:border-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 transition-all duration-200 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:shadow-lg dark:peer-checked:bg-red-900/20">
                                                        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4 text-red-600 dark:text-red-400 group-hover:scale-110 transition-transform peer-checked:bg-red-500 peer-checked:text-white">
                                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </div>
                                                        <span class="text-2xl font-bold text-gray-700 dark:text-gray-200 group-hover:text-red-700 dark:group-hover:text-red-400 peer-checked:text-red-700 dark:peer-checked:text-red-400">NÃO</span>
                                                        <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">Rejeitar</span>
                                                    </div>
                                                    <div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 transition-opacity text-red-500">
                                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <button 
                                            type="submit"
                                            class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white py-4 px-6 rounded-xl transition duration-200 font-bold text-lg shadow-lg hover:shadow-blue-500/30 flex items-center justify-center gap-2 transform active:scale-[0.99]"
                                        >
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Confirmar Voto
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sidebar / Informações Extras -->
                        <div class="space-y-6">
                            <!-- Link Resultados -->
                            <div class="stat-box rounded-2xl p-6 text-center">
                                <?php
                                $url_resultados = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../painel/resultados.php';
                                // Usar api.qrserver.com
                                $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($url_resultados);
                                ?>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Acompanhar</h3>
                                <div class="bg-white p-2 rounded-xl inline-block shadow-sm mb-4">
                                    <img src="<?= $qr_url ?>" alt="QR Code Resultados" class="w-32 h-32" loading="lazy">
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Escaneie para ver os resultados em tempo real</p>
                                <a href="../painel/resultados.php" target="_blank" class="block w-full py-2.5 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition text-sm flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    Abrir Painel
                                </a>
                            </div>

                            <!-- Ajuda / Info -->
                            <div class="stat-box rounded-2xl p-6">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Informações</h3>
                                <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>O voto é registrado imediatamente e não pode ser alterado.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span>Seu voto é seguro e autenticado pelo sistema.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <!-- Footer simples -->
        <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-500">
            &copy; <?= date('Y') ?> Câmara Municipal. Todos os direitos reservados.
        </footer>
    </div>

    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            atualizarIconeModo(dark);
        }
        
        function atualizarIconeModo(dark) {
            const icone = document.getElementById('icone-modo');
            if(icone) icone.textContent = dark ? '☀️' : '🌙';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dark = document.documentElement.classList.contains('dark');
            atualizarIconeModo(dark);
        });
    </script>
</body>
</html>
