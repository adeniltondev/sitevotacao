<?php
/**
 * Histórico de Votos do Vereador
 */

require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();
require_once 'header.php';

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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Votos - <?= htmlspecialchars($eleitor['nome']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('darkMode') === '1' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        .dark body { background: #18181b !important; color: #f3f4f6 !important; }
        .dark .bg-white { background: #23232a !important; color: #f3f4f6 !important; }
        .dark .text-gray-800 { color: #f3f4f6 !important; }
        .dark .text-gray-600 { color: #d1d5db !important; }
        .dark .bg-gray-50 { background: #23232a !important; }
        .dark .bg-green-100 { background: #14532d !important; color: #bbf7d0 !important; }
        .dark .bg-red-100 { background: #7f1d1d !important; color: #fecaca !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>
    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        }
        window.onload = function() {
            const dark = document.documentElement.classList.contains('dark');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        };
    </script>
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        <div class="md:ml-64 max-w-3xl mx-auto px-4 py-8">
            <a href="eleitores.php" class="text-blue-600 hover:underline">← Voltar</a>
            <h1 class="text-2xl font-bold text-blue-700 mt-4 mb-2">Histórico de Votos</h1>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center gap-4 mb-4">
                    <?php if ($eleitor['foto']): ?>
                        <img src="../uploads/<?= htmlspecialchars($eleitor['foto']) ?>" alt="Foto" class="w-16 h-16 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-gray-600 text-xl"><?= strtoupper(substr($eleitor['nome'], 0, 1)) ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="font-bold text-lg text-gray-800"><?= htmlspecialchars($eleitor['nome']) ?></div>
                        <div class="text-sm text-gray-600">CPF: <?= formatarCPF($eleitor['cpf']) ?></div>
                        <div class="text-sm text-gray-600">Cargo: <?= htmlspecialchars($eleitor['cargo'] ?: '-') ?></div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold">Ativo</span>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Votos Registrados (<?= count($votos) ?>)</h2>
                <?php if (count($votos) > 0): ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Votação</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Voto</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($votos as $voto): ?>
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                        <?= date('d/m/Y H:i', strtotime($voto['criado_em'])) ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                        <?= htmlspecialchars($voto['titulo']) ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                        <span class="<?= $voto['voto'] === 'sim' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-2 py-1 rounded-full text-xs font-semibold">
                                            <?= strtoupper($voto['voto']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-gray-600">Nenhum voto registrado para este vereador.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
