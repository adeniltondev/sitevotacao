<?php
require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();

// Ler arquivo de log
$logFile = __DIR__ . '/../logs/auditoria.log';
$logs = [];

if (file_exists($logFile)) {
    // Ler as últimas 200 linhas para não pesar
    $lines = file($logFile);
    if ($lines) {
        $lines = array_reverse($lines); // Mostrar mais recentes primeiro
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $logs[] = $data;
            }
        }
    }
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-72 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8">
        <!-- Cabeçalho -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Auditoria</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Registro de atividades e segurança do sistema.</p>
            </div>
        </div>

        <!-- Tabela de Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-blue-100/20 dark:shadow-black/20 overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data/Hora</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuário</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ação</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nenhum registro de auditoria encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        <?= date('d/m/Y H:i:s', strtotime($log['data'])) ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($log['usuario']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <?= htmlspecialchars($log['acao']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                        <?= htmlspecialchars($log['ip']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        <?php 
                                            $detalhes = json_encode($log['dados'] ?? [], JSON_UNESCAPED_UNICODE);
                                            echo htmlspecialchars(strlen($detalhes) > 50 ? substr($detalhes, 0, 50) . '...' : $detalhes);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
