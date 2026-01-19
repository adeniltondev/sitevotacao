<?php
/**
 * Histórico de Votos do Vereador
 */

require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();


$cpf = preg_replace('/[^0-9]/', '', $_GET['cpf'] ?? '');
if (!$cpf) {
    die('CPF não informado.');
}

$stmt = $pdo->prepare('SELECT * FROM eleitores WHERE cpf = ?');
$stmt->execute([$cpf]);
$eleitor = $stmt->fetch();
if (!$eleitor) {
    die('Eleitor não encontrado.');
}

$stmt = $pdo->prepare('SELECT v.*, vt.titulo FROM votos v JOIN votacoes vt ON v.votacao_id = vt.id WHERE v.cpf = ? ORDER BY v.criado_em DESC');
$stmt->execute([$cpf]);
$votos = $stmt->fetchAll();

?>
<?php
$page_title = 'Histórico de Votos - ' . ($eleitor['nome'] ?? 'Eleitor');
require_once 'header.php';
require_once 'sidebar.php';
?>
<main class="md:ml-72 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-8">
            <a href="eleitores.php" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Histórico do Eleitor</h1>
        </div>
        
        <!-- Card do Eleitor -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8 flex flex-col md:flex-row items-center gap-6">
                <?php if ($eleitor['foto']): ?>
                    <img src="../uploads/<?= htmlspecialchars($eleitor['foto']) ?>" alt="Foto" class="w-24 h-24 rounded-full object-cover ring-4 ring-gray-50 dark:ring-gray-700 shadow-md">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold text-3xl shadow-md">
                        <?= strtoupper(substr($eleitor['nome'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($eleitor['nome']) ?></h2>
                    <div class="flex flex-col md:flex-row items-center gap-2 md:gap-4 text-gray-500 dark:text-gray-400 text-sm">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .883-.393 1.627-1.008 2.155C8.36 7.64 7.218 8 6 8H5"></path></svg>
                            <?= htmlspecialchars($eleitor['cargo'] ?: 'Sem cargo') ?>
                        </span>
                        <span class="hidden md:inline">•</span>
                        <span class="font-mono"><?= formatarCPF($eleitor['cpf']) ?></span>
                        <span class="hidden md:inline">•</span>
                        <?php if ($eleitor['ativo']): ?>
                            <span class="text-green-600 dark:text-green-400 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Ativo
                            </span>
                        <?php else: ?>
                            <span class="text-red-600 dark:text-red-400 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Bloqueado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Histórico -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Registro de Votos</h3>
                    <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide"><?= count($votos) ?> votos</span>
                </div>
                
                <?php if (count($votos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-4 font-semibold">Data e Hora</th>
                                    <th class="px-6 py-4 font-semibold">Pauta / Votação</th>
                                    <th class="px-6 py-4 font-semibold text-center">Voto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <?php foreach ($votos as $voto): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300 text-sm whitespace-nowrap">
                                            <?= date('d/m/Y', strtotime($voto['criado_em'])) ?>
                                            <span class="text-gray-400 dark:text-gray-500 ml-1"><?= date('H:i', strtotime($voto['criado_em'])) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">
                                            <?= htmlspecialchars($voto['titulo']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($voto['voto'] === 'sim'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                                    SIM
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800">
                                                    NÃO
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-lg font-medium">Nenhum voto registrado</p>
                        <p class="text-sm">Este eleitor ainda não participou de nenhuma votação.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
        }
        
        // Inicializar modo escuro
        if (localStorage.getItem('darkMode') === '1' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            document.getElementById('icone-modo').textContent = '☀️';
        }
    </script>
</body>
</html>
