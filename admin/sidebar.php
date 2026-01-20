<aside id="sidebar" class="flex flex-col w-64 h-screen bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 shadow-2xl fixed top-0 left-0 z-50 transition-transform duration-300 -translate-x-full md:translate-x-0 font-sans antialiased">
    <div class="flex items-center justify-between h-24 px-8 border-b border-gray-100 dark:border-gray-800">
        <?php if (!empty($settings['logo_path']) && file_exists(__DIR__ . '/../' . $settings['logo_path'])): ?>
            <img src="../<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= htmlspecialchars($settings['sistema_nome'] ?? 'Logo') ?>" class="h-10 w-auto object-contain">
        <?php else: ?>
            <span class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                Vota<span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Câmara</span>
            </span>
        <?php endif; ?>
        <button id="close-sidebar" class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    
    <nav class="flex-1 flex flex-col gap-2 mt-8 px-5 overflow-y-auto no-scrollbar">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $menuItems = [
            ['href' => 'dashboard.php', 'icon' => 'M3 12l2-2 7-7 7 7 2 2M13 5v6h6', 'label' => 'Dashboard'],
            ['href' => 'dashboard.php#votacoes', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'label' => 'Votações'],
            ['href' => 'pautas.php', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Pauta do Dia'],
            ['href' => 'discursos.php', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Controle de Tempo'],
            ['href' => 'eleitores.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Vereadores'],
            ['href' => '../painel/resultados.php', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'label' => 'Resultados', 'target' => '_blank'],
            ['href' => 'relatorios.php', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Relatórios'],
            ['href' => 'auditoria.php', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => 'Auditoria'],
            ['href' => 'configuracoes.php', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'Configurações']
        ];

        foreach ($menuItems as $item):
            $isActive = $currentPage === basename($item['href']) || ($item['href'] === 'dashboard.php' && $currentPage === 'index.php');
            // Check if href contains hash
            if (strpos($item['href'], '#') !== false) {
                 $parts = explode('#', $item['href']);
                 if ($currentPage === basename($parts[0])) {
                     // Only active if explicitly checking for tab or default dashboard
                     $isActive = $currentPage === basename($parts[0]) && $item['label'] === 'Dashboard' && !isset($_GET['tab']);
                 }
            }
            
            // Override for exact match
            if ($currentPage === basename($item['href'])) {
                $isActive = true;
            }
        ?>
        <a href="<?= $item['href'] ?>" <?= isset($item['target']) ? 'target="' . $item['target'] . '"' : '' ?> 
           class="group flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium text-sm transition-all duration-300 relative overflow-hidden
                  <?= $isActive 
                      ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-600/20 translate-x-1' 
                      : 'text-gray-600 dark:text-gray-400 hover:bg-blue-50 dark:hover:bg-gray-800/80 hover:text-blue-600 dark:hover:text-blue-400' ?>">
            
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform duration-300 group-hover:scale-110 <?= $isActive ? 'text-white' : 'text-gray-400 group-hover:text-blue-600 dark:text-gray-500 dark:group-hover:text-blue-400' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="<?= $item['icon'] ?>" />
            </svg>
            <span class="tracking-wide"><?= $item['label'] ?></span>
            <?php if ($isActive): ?>
                <div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-white shadow-sm animate-pulse"></div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="p-5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-sm">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow-md">
                <?= strtoupper(substr($_SESSION['admin_nome'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-800 dark:text-white truncate">
                    <?= htmlspecialchars($_SESSION['admin_nome'] ?? 'Admin') ?>
                </p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">
                    Administrador
                </p>
            </div>
        </div>
        <div class="mt-3 text-center">
             <p class="text-[10px] text-gray-400 dark:text-gray-500">Versão 1.0.0 • Sistema Atualizado</p>
        </div>
    </div>
    
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</aside>
