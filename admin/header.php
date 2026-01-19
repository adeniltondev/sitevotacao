<?php
iniciarSessao();
$admin_nome = $_SESSION['admin_nome'] ?? '';
require_once __DIR__ . '/../config/database.php';
$votacao_ativa = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        
        // Inicialização do modo escuro
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <style>
        body, html { font-family: 'Inter', sans-serif !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">

<header class="md:ml-64 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 sticky top-0 z-40 transition-all duration-300">
    <div class="px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
        
        <!-- Left: Logo (Mobile) & Status -->
        <div class="flex items-center gap-4">
            <!-- Mobile Logo -->
            <div class="md:hidden flex items-center gap-2">
                <span class="text-xl font-bold text-green-600">Vota<span class="text-blue-600">Câmara</span></span>
            </div>

            <!-- Status Badge -->
            <?php if ($votacao_ativa): ?>
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                    <span class="relative flex h-2.5 w-2.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-600"></span>
                    </span>
                    <span class="text-xs font-bold tracking-wide uppercase">Votação Aberta</span>
                </div>
                <!-- Mobile Status (Simplified) -->
                <div class="sm:hidden flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-600"></span>
                </div>
            <?php else: ?>
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-gray-400"></span>
                    <span class="text-xs font-bold tracking-wide uppercase">Sem Votação</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Actions & Profile -->
        <div class="flex items-center gap-3 sm:gap-6">
            <!-- Public Panel Link -->
            <a href="../painel/resultados.php" target="_blank" class="hidden md:flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span class="hidden lg:inline">Painel Público</span>
            </a>

            <!-- Dark Mode Toggle -->
            <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
            </button>

            <!-- Divider -->
            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden md:block"></div>

            <!-- Profile -->
            <div class="flex items-center gap-3">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white leading-none"><?= htmlspecialchars($admin_nome) ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Administrador</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-blue-500 flex items-center justify-center text-white font-bold shadow-lg shadow-blue-500/30 text-sm">
                    <?= strtoupper(mb_substr($admin_nome, 0, 1, 'UTF-8')) ?>
                </div>
                
                <a href="logout.php" class="p-2 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 transition-colors" title="Sair">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Navigation (Scrollable) -->
    <div class="md:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
        <nav class="flex px-4 py-3 gap-3 overflow-x-auto no-scrollbar">
            <?php
            $links = [
                'dashboard.php' => 'Dashboard',
                'eleitores.php' => 'Eleitores',
                '../painel/resultados.php' => 'Painel Público',
                'configuracoes.php' => 'Configurações'
            ];
            foreach ($links as $url => $label):
                $active = basename($_SERVER['PHP_SELF']) === basename($url);
            ?>
            <a href="<?= $url ?>" class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $active ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<script>
    var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    var themeToggleBtn = document.getElementById('theme-toggle');

    themeToggleBtn.addEventListener('click', function() {

        // toggle icons inside button
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        // if set via local storage previously
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }

        // if NOT set via local storage previously
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
        
    });
</script>