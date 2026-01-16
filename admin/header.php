<?php
iniciarSessao();
$admin_nome = $_SESSION['admin_nome'] ?? '';
?>
<header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div class="flex flex-col md:flex-row md:items-center gap-4 flex-1">
            <a href="dashboard.php" class="text-2xl font-bold text-blue-600 dark:text-blue-400 hover:underline">Câmara Municipal</a>
            <nav class="flex flex-wrap gap-2 md:gap-4 ml-0 md:ml-8">
                <a href="dashboard.php" class="text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 font-semibold">Dashboard</a>
                <a href="eleitores.php" class="text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 font-semibold">Eleitores</a>
                <a href="historico.php?cpf=" class="text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 font-semibold">Histórico</a>
                <a href="../painel/resultados.php" target="_blank" class="text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 font-semibold">Painel Público</a>
            </nav>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    <?= htmlspecialchars($admin_nome) ?>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400" id="relogio"></div>
            </div>
            <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">Sair</a>
        </div>
    </div>
    <script>
    function atualizarRelogio() {
        const agora = new Date();
        const data = agora.toLocaleDateString('pt-BR');
        const hora = agora.toLocaleTimeString('pt-BR');
        document.getElementById('relogio').textContent = `${data} ${hora}`;
    }
    setInterval(atualizarRelogio, 1000);
    atualizarRelogio();
    </script>
</header>
