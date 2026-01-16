<aside class="hidden md:flex flex-col w-64 h-screen bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 shadow-lg fixed top-0 left-0 z-50">
    <div class="flex items-center h-20 px-6 border-b border-gray-100 dark:border-gray-800">
        <span class="text-2xl font-bold text-green-600">Vota<span class="text-blue-600">Câmara</span></span>
    </div>
    <nav class="flex-1 flex flex-col gap-1 mt-6 px-4">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" /></svg>
            Dashboard
        </a>
        <a href="dashboard.php#votacoes" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 018 0v2M9 17H7a2 2 0 01-2-2v-2a2 2 0 012-2h2m0 0V7a4 4 0 118 0v4m-8 0h8" /></svg>
            Votações
        </a>
        <a href="eleitores.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition <?= basename($_SERVER['PHP_SELF']) === 'eleitores.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Vereadores
        </a>
        <a href="../painel/resultados.php" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 018 0v2M9 17H7a2 2 0 01-2-2v-2a2 2 0 012-2h2m0 0V7a4 4 0 118 0v4m-8 0h8" /></svg>
            Resultados
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-400 dark:text-gray-600 cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 018 0v2M9 17H7a2 2 0 01-2-2v-2a2 2 0 012-2h2m0 0V7a4 4 0 118 0v4m-8 0h8" /></svg>
            Relatórios
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-400 dark:text-gray-600 cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 018 0v2M9 17H7a2 2 0 01-2-2v-2a2 2 0 012-2h2m0 0V7a4 4 0 118 0v4m-8 0h8" /></svg>
            Auditoria
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-400 dark:text-gray-600 cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 018 0v2M9 17H7a2 2 0 01-2-2v-2a2 2 0 012-2h2m0 0V7a4 4 0 118 0v4m-8 0h8" /></svg>
            Configurações
        </a>
    </nav>
    <div class="flex-1"></div>
    <div class="p-4 text-xs text-gray-400 dark:text-gray-600">Versão 1.0.0</div>
</aside>
