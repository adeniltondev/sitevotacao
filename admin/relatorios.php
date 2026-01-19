<?php
require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();

// Buscar todas as votações
$votacoes = $pdo->query("SELECT * FROM votacoes ORDER BY criada_em DESC")->fetchAll();

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8">
        <!-- Cabeçalho -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Relatórios</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Exporte os resultados das votações em diferentes formatos.</p>
            </div>
        </div>

        <!-- Lista de Votações -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-blue-100/20 dark:shadow-black/20 overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Criação</th>
                            <th class="text-center py-4 px-6 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($votacoes)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nenhuma votação encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($votacoes as $v): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-4 px-6 text-sm font-medium text-gray-900 dark:text-white">#<?= $v['id'] ?></td>
                                    <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                                        <div class="font-semibold"><?= htmlspecialchars($v['titulo']) ?></div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($v['descricao']) ?></div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $v['status'] === 'aberta' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' ?>">
                                            <?= ucfirst($v['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">
                                        <?= date('d/m/Y H:i', strtotime($v['criada_em'])) ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex justify-center gap-2">
                                            <a href="../painel/exportar_pdf.php?votacao_id=<?= $v['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" title="Exportar PDF">
                                                PDF
                                            </a>
                                            <a href="../painel/exportar_csv.php?votacao_id=<?= $v['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors" title="Exportar CSV">
                                                CSV
                                            </a>
                                            <a href="../painel/exportar_ata.php?votacao_id=<?= $v['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" title="Exportar Ata">
                                                Ata
                                            </a>
                                        </div>
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
