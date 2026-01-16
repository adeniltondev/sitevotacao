<?php

require_once '../config/database.php';
require_once '../config/functions.php';

verificarAdmin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar_votacao') {
        $titulo = sanitizar($_POST['titulo'] ?? '');
        $descricao = sanitizar($_POST['descricao'] ?? '');

        if (!empty($titulo)) {
            $stmt = $pdo->prepare("INSERT INTO votacoes (titulo, descricao, status) VALUES (?, ?, 'encerrada')");
            $stmt->execute([$titulo, $descricao]);
            header('Location: dashboard.php?sucesso=votacao_criada');
            exit;
        }
    }

    if ($acao === 'abrir_votacao') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $pdo->exec("UPDATE votacoes SET status = 'encerrada', encerrada_em = NOW() WHERE status = 'aberta'");
        $stmt = $pdo->prepare("UPDATE votacoes SET status = 'aberta', aberta_em = NOW() WHERE id = ?");
        $stmt->execute([$votacao_id]);
        header('Location: dashboard.php?sucesso=votacao_aberta');
        exit;
    }

    if ($acao === 'encerrar_votacao') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE votacoes SET status = 'encerrada', encerrada_em = NOW() WHERE id = ?");
        $stmt->execute([$votacao_id]);
        header('Location: dashboard.php?sucesso=votacao_encerrada');
        exit;
    }

    if ($acao === 'resetar_votos') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM votos WHERE votacao_id = ?");
        $stmt->execute([$votacao_id]);
        header('Location: dashboard.php?sucesso=votos_resetados');
        exit;
    }
}

// Buscar votação ativa
$votacao_ativa = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();

// Buscar todas as votações
$votacoes = $pdo->query("SELECT * FROM votacoes ORDER BY criada_em DESC")->fetchAll();

// Buscar todos os votos da votação ativa
$votos = [];
if ($votacao_ativa) {
    $votosStmt = $pdo->prepare("SELECT * FROM votos WHERE votacao_id = ? ORDER BY criado_em DESC");
    $votosStmt->execute([$votacao_ativa['id']]);
    $votos = $votosStmt->fetchAll();

    $total_sim_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'");
    $total_sim_stmt->execute([$votacao_ativa['id']]);
    $total_sim = $total_sim_stmt->fetch()['total'];

    $total_nao_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'");
    $total_nao_stmt->execute([$votacao_ativa['id']]);
    $total_nao = $total_nao_stmt->fetch()['total'];

    $total_geral = $total_sim + $total_nao;
    $percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
    $percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;
} else {
    $total_sim = $total_nao = $total_geral = 0;
    $percentual_sim = $percentual_nao = 0;
}

$sucesso = $_GET['sucesso'] ?? '';

// Filtro de período (GET)
$start_date = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] : null;
$end_date = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] : null;

// Endpoint AJAX para atualizações dinâmicas
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    if (!$votacao_ativa) {
        echo json_encode(["sim"=>0,"nao"=>0,"total"=>0,"percentual_sim"=>0,"percentual_nao"=>0,"trend_labels"=>[],"trend_sim"=>[],"trend_nao"=>[],"last_votes"=>[]]);
        exit;
    }

    // preparar filtro de datas
    $whereDate = "";
    $params = [$votacao_ativa['id']];
    if ($start_date && $end_date) {
        $whereDate = " AND criado_em BETWEEN ? AND ?";
        $params[] = $start_date . ' 00:00:00';
        $params[] = $end_date . ' 23:59:59';
    }

    $total_sim_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'" . $whereDate);
    $total_sim_stmt->execute($params);
    $total_sim_ajax = (int)$total_sim_stmt->fetch()['total'];

    $total_nao_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'" . $whereDate);
    $total_nao_stmt->execute($params);
    $total_nao_ajax = (int)$total_nao_stmt->fetch()['total'];

    $total_geral_ajax = $total_sim_ajax + $total_nao_ajax;
    $percentual_sim_ajax = $total_geral_ajax > 0 ? round(($total_sim_ajax / $total_geral_ajax) * 100, 1) : 0;
    $percentual_nao_ajax = $total_geral_ajax > 0 ? round(($total_nao_ajax / $total_geral_ajax) * 100, 1) : 0;

    // trend (últimos 14 dias)
    $days = 14;
    $trend_labels = [];
    $trend_sim = array_fill(0, $days, 0);
    $trend_nao = array_fill(0, $days, 0);
    $today = new DateTime();
    $interval = new DateInterval('P1D');
    $period = new DatePeriod((clone $today)->sub(new DateInterval('P' . ($days-1) . 'D')), $interval, $days);
    foreach ($period as $i => $dt) {
        $trend_labels[] = $dt->format('d/m');
    }

    $trendQuery = "SELECT DATE(criado_em) as d, SUM(CASE WHEN voto='sim' THEN 1 ELSE 0 END) as sim, SUM(CASE WHEN voto='nao' THEN 1 ELSE 0 END) as nao FROM votos WHERE votacao_id = ?" . $whereDate . " AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(criado_em)";
    $paramsTrend = $params;
    $paramsTrend[] = $days - 1;
    $stmtTrend = $pdo->prepare($trendQuery);
    $stmtTrend->execute($paramsTrend);
    $rows = $stmtTrend->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['d']] = ['sim' => (int)$r['sim'], 'nao' => (int)$r['nao']];
    }
    // preencher arrays
    $start = (clone $today)->sub(new DateInterval('P' . ($days-1) . 'D'));
    for ($i = 0; $i < $days; $i++) {
        $d = $start->format('Y-m-d');
        if (isset($map[$d])) { $trend_sim[$i] = $map[$d]['sim']; $trend_nao[$i] = $map[$d]['nao']; }
        $start->add($interval);
    }

    // últimos 5 votos
    $lastParams = $params;
    $lastQuery = "SELECT nome, cpf, voto, criado_em FROM votos WHERE votacao_id = ?" . $whereDate . " ORDER BY criado_em DESC LIMIT 5";
    $lastStmt = $pdo->prepare($lastQuery);
    $lastStmt->execute($lastParams);
    $last_votes = $lastStmt->fetchAll();

    echo json_encode([
        'sim' => $total_sim_ajax,
        'nao' => $total_nao_ajax,
        'total' => $total_geral_ajax,
        'percentual_sim' => $percentual_sim_ajax,
        'percentual_nao' => $percentual_nao_ajax,
        'trend_labels' => $trend_labels,
        'trend_sim' => $trend_sim,
        'trend_nao' => $trend_nao,
        'last_votes' => $last_votes
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body, html { font-family: 'Inter', sans-serif !important; }</style>
    <title>Dashboard - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-100">
    <!-- Dark mode toggle removed as requested -->

    <div class="flex">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 md:ml-64">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <header class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold">Painel Administrativo</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Visão geral das votações e participação</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="eleitores.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v6M21 12h-6M16 7a4 4 0 11-8 0 4 4 0 018 0zM2 21v-2a4 4 0 014-4h6"/></svg>
                            Cadastrar Eleitores
                        </a>
                        <a href="logout.php" class="inline-flex items-center gap-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Sair</a>
                    </div>
                </header>

                <!-- Top stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                            <!-- Heroicon: Users -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-300">Eleitores</div>
                            <div class="text-2xl font-bold"><?php $total_vereadores = $pdo->query("SELECT COUNT(*) FROM eleitores")->fetchColumn(); echo $total_vereadores; ?></div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="p-3 bg-green-50 dark:bg-green-900 rounded-lg">
                            <!-- Heroicon: Check Circle -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 9v3l2 2" />
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-300">Votos</div>
                            <div class="text-2xl font-bold"><?= $total_geral ?></div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="p-3 bg-red-50 dark:bg-red-900 rounded-lg">
                            <!-- Heroicon: X Circle -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 9l-6 6M9 9l6 6" />
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-300">Não votaram</div>
                            <div class="text-2xl font-bold"><?php $nao_votaram = $total_vereadores - $total_geral; echo $nao_votaram >= 0 ? $nao_votaram : 0; ?></div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900 rounded-lg">
                            <!-- Heroicon: Clock -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600 dark:text-indigo-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-300">Votação</div>
                            <div class="text-2xl font-bold"><?= $votacao_ativa ? htmlspecialchars($votacao_ativa['titulo']) : 'Nenhuma' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Painel principal: gráfico + lista rápida -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 flex flex-col">
                                <div class="mb-3">
                                    <div class="text-sm text-gray-500 dark:text-gray-300">Mix</div>
                                    <div class="text-xl font-semibold">SIM / NÃO</div>
                                </div>
                                <div class="flex-1 flex items-center justify-center">
                                    <div class="w-40 h-40">
                                        <canvas id="graficoSimNao"></canvas>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-4 items-center text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-block w-3 h-3 rounded-full bg-green-600"></span>
                                        <div>
                                            <div class="text-xs">SIM</div>
                                            <div class="font-bold text-green-600 text-lg"><?= $total_sim ?> <span class="text-sm text-gray-500">(<?= $percentual_sim ?>%)</span></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="inline-block w-3 h-3 rounded-full bg-red-600"></span>
                                        <div>
                                            <div class="text-xs">NÃO</div>
                                            <div class="font-bold text-red-600 text-lg"><?= $total_nao ?> <span class="text-sm text-gray-500">(<?= $percentual_nao ?>%)</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 flex flex-col">
                                <div class="mb-3">
                                    <div class="text-sm text-gray-500 dark:text-gray-300">Contagem</div>
                                    <div class="text-xl font-semibold">Absoluta</div>
                                </div>
                                <div class="flex-1 flex items-center justify-center">
                                    <div class="w-full max-w-xs h-36">
                                        <canvas id="graficoContagem"></canvas>
                                    </div>
                                </div>
                                <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">Total de votos: <span class="font-bold text-lg"><?= $total_geral ?></span></div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 flex flex-col">
                                <div class="mb-3">
                                    <div class="text-sm text-gray-500 dark:text-gray-300">Percentual</div>
                                    <div class="text-xl font-semibold">Distribuição</div>
                                </div>
                                <div class="flex-1 flex items-center justify-center">
                                    <div class="w-40 h-40">
                                        <canvas id="graficoPercentual"></canvas>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-4 items-center text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-block w-3 h-3 rounded-full bg-green-600"></span>
                                        <div>
                                            <div class="text-xs">SIM</div>
                                            <div class="font-bold text-green-600 text-lg"><?= $percentual_sim ?>%</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="inline-block w-3 h-3 rounded-full bg-red-600"></span>
                                        <div>
                                            <div class="text-xs">NÃO</div>
                                            <div class="font-bold text-red-600 text-lg"><?= $percentual_nao ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-3">Ações Rápidas</h3>
                        <div class="flex flex-col gap-3">
                            <?php if ($votacao_ativa): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="acao" value="encerrar_votacao">
                                    <input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM6 10a4 4 0 018 0 4 4 0 01-8 0z"/></svg>
                                        Encerrar Votação
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Nenhuma votação aberta.</div>
                            <?php endif; ?>

                            <a href="../painel/resultados.php" target="_blank" class="w-full inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Ver Painel de Resultados</a>
                            <a href="eleitores.php" class="w-full inline-flex items-center justify-center gap-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-4 py-2 rounded-lg">Gerenciar Eleitores</a>
                        </div>
                    </div>
                </div>

                <!-- Todas as Votações (tabela compacta) -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Todas as Votações</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-300">Total: <?= count($votacoes) ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Título</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Criada</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($votacoes as $votacao): ?>
                                    <?php $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ?"); $stmt->execute([$votacao['id']]); $total_votos = $stmt->fetch()['total']; ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm">#<?= $votacao['id'] ?></td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($votacao['titulo']) ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <?php if ($votacao['status'] === 'aberta'): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium">ABERTA</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium">ENCERRADA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300"><?= date('d/m/Y H:i', strtotime($votacao['criada_em'])) ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex items-center gap-2">
                                                <?php if ($votacao['status'] === 'encerrada'): ?>
                                                    <form method="POST" action="" class="inline">
                                                        <input type="hidden" name="acao" value="abrir_votacao">
                                                        <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                        <button type="submit" class="text-green-600 hover:underline">Abrir</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="" class="inline" onsubmit="return confirm('Tem certeza que deseja resetar os votos?')">
                                                    <input type="hidden" name="acao" value="resetar_votos">
                                                    <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                    <button type="submit" class="text-orange-600 hover:underline">Resetar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Lista de Votantes -->
                <?php if ($votacao_ativa && count($votos) > 0): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Votantes (<?= count($votos) ?>)</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($votos as $voto): ?>
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 flex flex-col">
                                    <div class="flex items-center gap-4 mb-3">
                                        <?php if ($voto['foto']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($voto['foto']) ?>" alt="Foto" class="w-14 h-14 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-14 h-14 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-lg font-semibold text-gray-700 dark:text-gray-200"><?= strtoupper(substr($voto['nome'], 0, 1)) ?></div>
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <div class="font-semibold"><?= htmlspecialchars($voto['nome']) ?></div>
                                            <?php if ($voto['cargo']): ?>
                                                <div class="text-sm text-gray-500 dark:text-gray-300"><?= htmlspecialchars($voto['cargo']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full <?= $voto['voto'] === 'sim' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' ?> text-xs font-medium"><?= strtoupper($voto['voto']) ?></span>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-auto"><?= formatarCPF($voto['cpf']) ?> • <?= date('d/m/Y H:i', strtotime($voto['criado_em'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const sim = <?= $total_sim ?>;
        const nao = <?= $total_nao ?>;
        const total = <?= $total_geral ?>;
        const percSim = <?= $percentual_sim ?>;
        const percNao = <?= $percentual_nao ?>;

        const ctx1 = document.getElementById('graficoSimNao')?.getContext('2d');
        if(ctx1){
            new Chart(ctx1, {
                type: 'pie',
                data: { labels: ['SIM','NÃO'], datasets: [{ data: [sim, nao], backgroundColor: ['#16a34a','#ef4444'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        const ctx2 = document.getElementById('graficoContagem')?.getContext('2d');
        if(ctx2){
            new Chart(ctx2, {
                type: 'bar',
                data: { labels: ['SIM','NÃO'], datasets: [{ label: 'Votos', data: [sim, nao], backgroundColor: ['#16a34a','#ef4444'] }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision:0 } } }, plugins: { legend: { display: false } } }
            });
        }

        const ctx3 = document.getElementById('graficoPercentual')?.getContext('2d');
        if(ctx3){
            new Chart(ctx3, {
                type: 'doughnut',
                data: { labels: ['SIM','NÃO'], datasets: [{ data: [percSim, percNao], backgroundColor: ['#16a34a','#ef4444'] }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(context){ return context.label + ': ' + context.raw + '%'; } } } } }
            });
        }
    </script>
</body>
</html>
<?php
