<aside id="sidebar" class="flex flex-col w-72 h-screen bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 shadow-xl fixed top-0 left-0 z-50 transition-transform duration-300 -translate-x-full md:translate-x-0">
    <div class="flex items-center justify-between h-24 px-8 border-b border-gray-100 dark:border-gray-800">
        <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Vota<span class="text-blue-600">Câmara</span></span>
        <button id="close-sidebar" class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <nav class="flex-1 flex flex-col gap-2 mt-8 px-6 overflow-y-auto">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $menuItems = [
            ['href' => 'dashboard.php', 'icon' => 'M3 12l2-2 7-7 7 7 2 2M13 5v6h6', 'label' => 'Dashboard'],
            ['href' => 'dashboard.php#votacoes', 'icon' => 'M9 11h6M9 15h6M9 7h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z', 'label' => 'Votações'],
            ['href' => 'eleitores.php', 'icon' => 'M17 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2M12 7a4 4 0 100-8 4 4 0 000 8z', 'label' => 'Vereadores'],
            ['href' => '../painel/resultados.php', 'icon' => 'M3 3v18h18M18 13v6M12 8v11M6 16v5', 'label' => 'Resultados', 'target' => '_blank'],
            ['href' => 'relatorios.php', 'icon' => 'M7 2h6l5 5v13a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2zM7 8h6', 'label' => 'Relatórios'],
            ['href' => 'auditoria.php', 'icon' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9.5 12.5l1.5 1.5 3-3', 'label' => 'Auditoria'],
            ['href' => 'configuracoes.php', 'icon' => 'M12 15.5A3.5 3.5 0 1112 8.5a3.5 3.5 0 010 7zM19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06A2 2 0 013.28 16.9l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09c.7 0 1.28-.4 1.51-1a1.65 1.65 0 00-.33-1.82L4.3 5.28a2 2 0 012.83-2.83l.06.06c.5.5 1.18.68 1.82.33.5-.28 1.08-.28 1.58 0 .64.35 1.32.17 1.82-.33l.06-.06A2 2 0 0116.72 4.3l-.06.06c-.28.5-.28 1.08 0 1.58.35.64.17 1.32-.33 1.82-.28.5-.28 1.08 0 1.58.35.64.17 1.32-.33 1.82l-.06.06a2 2 0 01-2.83 0l-.06-.06c-.5-.5-1.18-.68-1.82-.33-.5.28-1.08.28-1.58 0-.64-.35-1.32-.17-1.82.33l-.06.06A2 2 0 014.3 16.72l.06-.06c.28-.5.28-1.08 0-1.58-.35-.64-.17-1.32.33-1.82.28-.5.28-1.08 0-1.58-.35-.64-.17-1.32.33-1.82l.06-.06A2 2 0 0111.28 3.28l.06.06c.5.5 1.18.68 1.82.33.5-.28 1.08-.28 1.58 0 .64.35 1.32.17 1.82-.33l.06-.06A2 2 0 0119.4 8.6l-.06.06c-.5.5-.68 1.18-.33 1.82.28.5.28 1.08 0 1.58-.35.64-.17 1.32.33 1.82l.06.06a2 2 0 01.33 1.82z', 'label' => 'Configurações']
        ];

        foreach ($menuItems as $item):
            $isActive = $currentPage === basename($item['href']) || ($item['href'] === 'dashboard.php' && $currentPage === 'index.php');
            // Check if href contains hash
            if (strpos($item['href'], '#') !== false) {
                 $parts = explode('#', $item['href']);
                 if ($currentPage === basename($parts[0])) {
                     // Keep active logic simple for now, relies on exact match mostly
                     // If we are on dashboard.php, both Dashboard and Votacoes might look active if we don't be careful
                     // Let's make "Votações" active only if explicitly requested or if we want to treat it as separate.
                     // For now, let's stick to page-based active state.
                     // The user image shows "Dashboard" active.
                     $isActive = $currentPage === basename($parts[0]) && $item['label'] === 'Dashboard' && !isset($_GET['tab']);
                 }
            }
            
            // Override for exact match
            if ($currentPage === basename($item['href'])) {
                $isActive = true;
            }
        ?>
        <a href="<?= $item['href'] ?>" <?= isset($item['target']) ? 'target="' . $item['target'] . '"' : '' ?> 
           class="group flex items-center gap-4 px-5 py-4 rounded-2xl font-semibold text-base transition-all duration-200 
                  <?= $isActive 
                      ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 translate-x-1' 
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400 hover:translate-x-1' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-transform duration-200 group-hover:scale-110 <?= $isActive ? 'text-white' : 'text-gray-400 group-hover:text-blue-600 dark:text-gray-500 dark:group-hover:text-blue-400' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="<?= $item['icon'] ?>" />
            </svg>
            <span><?= $item['label'] ?></span>
            <?php if ($isActive): ?>
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/50"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="p-6 border-t border-gray-100 dark:border-gray-800">
        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
            <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-blue-600 to-blue-500 flex items-center justify-center text-white text-xs font-bold">
                V
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Versão 1.0.0</p>
                <p class="text-[10px] text-gray-400">Sistema Atualizado</p>
            </div>
        </div>
    </div>
</aside>
