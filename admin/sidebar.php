<aside class="hidden md:flex flex-col w-64 h-screen bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 shadow-lg fixed top-0 left-0 z-50">
    <div class="flex items-center h-20 px-6 border-b border-gray-100 dark:border-gray-800">
        <span class="text-2xl font-bold text-green-600">Vota<span class="text-blue-600">Câmara</span></span>
    </div>
    <nav class="flex-1 flex flex-col gap-1 mt-6 px-4">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">
            <!-- Heroicon: Home -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12l2-2 7-7 7 7 2 2" />
                <path d="M13 5v6h6" />
            </svg>
            Dashboard
        </a>
        <a href="dashboard.php#votacoes" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
            <!-- Heroicon: Clipboard List -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11h6M9 15h6M9 7h6" />
                <path d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
            </svg>
            Votações
        </a>
        <a href="eleitores.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition <?= basename($_SERVER['PHP_SELF']) === 'eleitores.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">
            <!-- Heroicon: Users -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            Vereadores
        </a>
        <a href="../painel/resultados.php" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
            <!-- Heroicon: Chart Bar -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18" />
                <path d="M18 13v6M12 8v11M6 16v5" />
            </svg>
            Resultados
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-400 dark:text-gray-600 cursor-not-allowed">
            <!-- Heroicon: Document -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 2h6l5 5v13a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2z" />
                <path d="M7 8h6" />
            </svg>
            Relatórios
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-400 dark:text-gray-600 cursor-not-allowed">
            <!-- Heroicon: Shield Check -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                <path d="M9.5 12.5l1.5 1.5 3-3" />
            </svg>
            Auditoria
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-gray-400 dark:text-gray-600 cursor-not-allowed">
            <!-- Heroicon: Cog -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 15.5A3.5 3.5 0 1112 8.5a3.5 3.5 0 010 7z" />
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06A2 2 0 013.28 16.9l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09c.7 0 1.28-.4 1.51-1a1.65 1.65 0 00-.33-1.82L4.3 5.28a2 2 0 012.83-2.83l.06.06c.5.5 1.18.68 1.82.33.5-.28 1.08-.28 1.58 0 .64.35 1.32.17 1.82-.33l.06-.06A2 2 0 0116.72 4.3l-.06.06c-.28.5-.28 1.08 0 1.58.35.64.17 1.32-.33 1.82-.28.5-.28 1.08 0 1.58.35.64.17 1.32-.33 1.82l-.06.06a2 2 0 01-2.83 0l-.06-.06c-.5-.5-1.18-.68-1.82-.33-.5.28-1.08.28-1.58 0-.64-.35-1.32-.17-1.82.33l-.06.06A2 2 0 014.3 16.72l.06-.06c.28-.5.28-1.08 0-1.58-.35-.64-.17-1.32.33-1.82.28-.5.28-1.08 0-1.58-.35-.64-.17-1.32.33-1.82l.06-.06A2 2 0 0111.28 3.28l.06.06c.5.5 1.18.68 1.82.33.5-.28 1.08-.28 1.58 0 .64.35 1.32.17 1.82-.33l.06-.06A2 2 0 0119.4 8.6l-.06.06c-.5.5-.68 1.18-.33 1.82.28.5.28 1.08 0 1.58-.35.64-.17 1.32.33 1.82l.06.06a2 2 0 01.33 1.82z" />
            </svg>
            Configurações
        </a>
    </nav>
    <div class="flex-1"></div>
    <div class="p-4 text-xs text-gray-400 dark:text-gray-600">Versão 1.0.0</div>
</aside>
