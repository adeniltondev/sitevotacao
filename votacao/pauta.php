<?php
/**
 * Página de Visualização de Pauta - Requer Login
 */
require_once '../config/database.php';
require_once '../config/functions.php';

// Verificar se eleitor está logado
verificarEleitor();

// Buscar pauta do dia
$hoje = date('Y-m-d');
$pauta = $pdo->query("SELECT * FROM pautas WHERE data_sessao = '$hoje' LIMIT 1")->fetch();

// Se não houver pauta hoje, buscar a próxima futura ou a última passada
if (!$pauta) {
    // Tenta próxima futura
    $pauta = $pdo->query("SELECT * FROM pautas WHERE data_sessao > '$hoje' ORDER BY data_sessao ASC LIMIT 1")->fetch();
    if (!$pauta) {
        // Tenta última passada
        $pauta = $pdo->query("SELECT * FROM pautas WHERE data_sessao < '$hoje' ORDER BY data_sessao DESC LIMIT 1")->fetch();
    }
}

// Buscar lista de datas com pauta para navegação
$datas_pautas = $pdo->query("SELECT id, data_sessao, titulo FROM pautas ORDER BY data_sessao DESC LIMIT 10")->fetchAll();

// Se o usuário selecionou uma pauta específica
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $pauta = $pdo->query("SELECT * FROM pautas WHERE id = $id LIMIT 1")->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pauta da Sessão - Câmara Municipal</title>
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
        .fade-in { animation: fadeIn 0.5s ease-out; }
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
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100">
    <div class="min-h-screen w-full flex flex-col">
        <!-- Header -->
        <div class="bg-gray-800 border-b border-gray-700 py-4 px-4 sm:px-8 fade-in shadow-lg z-10">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                     <?php if (!empty($settings['logo_path']) && file_exists('../' . $settings['logo_path'])): ?>
                        <img src="../<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="h-12 w-auto">
                    <?php else: ?>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white tracking-tight">CÂMARA MUNICIPAL</h1>
                            <p class="text-xs md:text-sm text-gray-400 font-medium tracking-wide uppercase">Sistema de Votação</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-4 justify-between md:justify-end w-full md:w-auto">
                    <!-- Menu Navegação -->
                    <nav class="flex gap-4 text-sm font-medium text-gray-300">
                        <a href="index.php" class="hover:text-white transition-colors">Votação</a>
                        <a href="pauta.php" class="text-white border-b-2 border-blue-500 pb-1">Pauta do Dia</a>
                    </nav>

                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['eleitor_nome']) ?></div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium shadow-sm hover:shadow">Sair</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-grow flex flex-col items-center justify-start pt-8 pb-12 px-4 sm:px-6">
            <div class="w-full max-w-5xl fade-in space-y-6">
                
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Sidebar com datas -->
                    <div class="w-full md:w-1/4">
                        <div class="stat-box rounded-2xl p-4">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">Outras Sessões</h3>
                            <ul class="space-y-2">
                                <?php foreach ($datas_pautas as $dp): ?>
                                    <li>
                                        <a href="?id=<?= $dp['id'] ?>" class="block p-2 rounded-lg transition-colors <?= (isset($pauta['id']) && $pauta['id'] == $dp['id']) ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400' ?>">
                                            <div class="font-medium"><?= date('d/m/Y', strtotime($dp['data_sessao'])) ?></div>
                                            <div class="text-xs truncate"><?= htmlspecialchars($dp['titulo']) ?></div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($datas_pautas)): ?>
                                    <li class="text-sm text-gray-500">Nenhuma pauta encontrada.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Conteúdo da Pauta -->
                    <div class="w-full md:w-3/4">
                        <?php if ($pauta): ?>
                            <div class="stat-box rounded-2xl p-8 min-h-[500px]">
                                <div class="mb-6 pb-6 border-b border-gray-100 dark:border-gray-700">
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 text-xs font-semibold mb-2">
                                        Sessão de <?= date('d/m/Y', strtotime($pauta['data_sessao'])) ?>
                                    </span>
                                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($pauta['titulo']) ?></h2>
                                </div>
                                
                                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                    <?= nl2br(htmlspecialchars($pauta['conteudo'])) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="stat-box rounded-2xl p-10 text-center">
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Nenhuma Pauta Encontrada</h2>
                                <p class="text-gray-500">Selecione uma data ao lado ou aguarde a publicação.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
