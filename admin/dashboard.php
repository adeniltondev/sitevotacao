<?php
require_once '../config/database.php';
require_once '../config/functions.php';

verificarAdmin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validar CSRF
    if (!validarCSRFToken()) {
        die('Token CSRF inválido');
    }

    if ($acao === 'criar_votacao') {
        $titulo = sanitizar($_POST['titulo'] ?? '');
        $descricao = sanitizar($_POST['descricao'] ?? '');
        $tipo_votacao = $_POST['tipo_votacao'] ?? 'nominal';

        // Validar tipo de votação
        if (!in_array($tipo_votacao, ['nominal', 'anonima'])) {
            $tipo_votacao = 'nominal';
        }

        if (!empty($titulo)) {
            $stmt = $pdo->prepare("INSERT INTO votacoes (titulo, descricao, tipo_votacao, status) VALUES (?, ?, ?, 'encerrada')");
            $stmt->execute([$titulo, $descricao, $tipo_votacao]);

            $mensagem = $tipo_votacao === 'anonima'
                ? 'Votação anônima criada com sucesso!'
                : 'Votação nominal criada com sucesso!';

            setFlashMessage('success', $mensagem);
            header('Location: dashboard.php');
            exit;
        }
    }

    if ($acao === 'abrir_votacao') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $pdo->exec("UPDATE votacoes SET status = 'encerrada', encerrada_em = NOW() WHERE status = 'aberta'");
        $stmt = $pdo->prepare("UPDATE votacoes SET status = 'aberta', aberta_em = NOW() WHERE id = ?");
        $stmt->execute([$votacao_id]);
        registrarLog('abrir_votacao', ['id' => $votacao_id]);
        header('Location: dashboard.php?sucesso=votacao_aberta');
        exit;
    }

    if ($acao === 'encerrar_votacao') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE votacoes SET status = 'encerrada', encerrada_em = NOW() WHERE id = ?");
        $stmt->execute([$votacao_id]);
        registrarLog('encerrar_votacao', ['id' => $votacao_id]);
        header('Location: dashboard.php?sucesso=votacao_encerrada');
        exit;
    }

    if ($acao === 'resetar_votos') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM votos WHERE votacao_id = ?");
        $stmt->execute([$votacao_id]);
        registrarLog('resetar_votos', ['id' => $votacao_id]);
        header('Location: dashboard.php?sucesso=votos_resetados');
        exit;
    }
}

// Buscar votação ativa
$votacao_ativa = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();

// Buscar todas as votações
$votacoes = $pdo->query("SELECT * FROM votacoes ORDER BY criada_em DESC")->fetchAll();

// Filtro de período (GET)
$start_date = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] : null;
$end_date = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] : null;

// Buscar todos os votos da votação ativa (com suporte a filtro de data)
$votos = [];
$total_sim = 0;
$total_nao = 0;
$total_geral = 0;
$percentual_sim = 0;
$percentual_nao = 0;

if ($votacao_ativa) {
    $whereDate = "";
    $params = [$votacao_ativa['id']];
    if ($start_date && $end_date) {
        $whereDate = " AND criado_em BETWEEN ? AND ?";
        $params[] = $start_date . ' 00:00:00';
        $params[] = $end_date . ' 23:59:59';
    }

    $votosStmt = $pdo->prepare("SELECT * FROM votos WHERE votacao_id = ?" . $whereDate . " ORDER BY criado_em DESC");
    $votosStmt->execute($params);
    $votos = $votosStmt->fetchAll();

    $total_sim_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'" . $whereDate);
    $total_sim_stmt->execute($params);
    $total_sim = (int) $total_sim_stmt->fetch()['total'];

    $total_nao_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'" . $whereDate);
    $total_nao_stmt->execute($params);
    $total_nao = (int) $total_nao_stmt->fetch()['total'];

    $total_geral = $total_sim + $total_nao;
    $percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
    $percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;
}

// Endpoint AJAX para atualizações dinâmicas
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    if (!$votacao_ativa) {
        echo json_encode(["sim" => 0, "nao" => 0, "total" => 0, "percentual_sim" => 0, "percentual_nao" => 0, "trend_labels" => [], "trend_sim" => [], "trend_nao" => [], "last_votes" => []]);
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
    $total_sim_ajax = (int) $total_sim_stmt->fetch()['total'];

    $total_nao_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'" . $whereDate);
    $total_nao_stmt->execute($params);
    $total_nao_ajax = (int) $total_nao_stmt->fetch()['total'];

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
    $period = new DatePeriod((clone $today)->sub(new DateInterval('P' . ($days - 1) . 'D')), $interval, $days);
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
        $map[$r['d']] = ['sim' => (int) $r['sim'], 'nao' => (int) $r['nao']];
    }
    // preencher arrays
    $start = (clone $today)->sub(new DateInterval('P' . ($days - 1) . 'D'));
    for ($i = 0; $i < $days; $i++) {
        $d = $start->format('Y-m-d');
        if (isset($map[$d])) {
            $trend_sim[$i] = $map[$d]['sim'];
            $trend_nao[$i] = $map[$d]['nao'];
        }
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

// Preparar dados iniciais para o gráfico de tendência (lado do servidor)
$days = 14;
$trend_labels = [];
$trend_sim = array_fill(0, $days, 0);
$trend_nao = array_fill(0, $days, 0);
if ($votacao_ativa) {
    $today = new DateTime();
    $interval = new DateInterval('P1D');
    $period = new DatePeriod((clone $today)->sub(new DateInterval('P' . ($days - 1) . 'D')), $interval, $days);
    foreach ($period as $i => $dt) {
        $trend_labels[] = $dt->format('d/m');
    }

    // Mesma query do AJAX
    $params = [$votacao_ativa['id']];
    if ($start_date && $end_date) {
        $params[] = $start_date . ' 00:00:00';
        $params[] = $end_date . ' 23:59:59';
    }
    $trendQuery = "SELECT DATE(criado_em) as d, SUM(CASE WHEN voto='sim' THEN 1 ELSE 0 END) as sim, SUM(CASE WHEN voto='nao' THEN 1 ELSE 0 END) as nao FROM votos WHERE votacao_id = ?" . ($start_date && $end_date ? " AND criado_em BETWEEN ? AND ?" : "") . " AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(criado_em)";
    $paramsTrend = $params;
    $paramsTrend[] = $days - 1;
    $stmtTrend = $pdo->prepare($trendQuery);
    $stmtTrend->execute($paramsTrend);
    $rows = $stmtTrend->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['d']] = ['sim' => (int) $r['sim'], 'nao' => (int) $r['nao']];
    }
    $start = (clone $today)->sub(new DateInterval('P' . ($days - 1) . 'D'));
    for ($i = 0; $i < $days; $i++) {
        $d = $start->format('Y-m-d');
        if (isset($map[$d])) {
            $trend_sim[$i] = $map[$d]['sim'];
            $trend_nao[$i] = $map[$d]['nao'];
        }
        $start->add($interval);
    }
}

require_once 'header.php';
require_once 'sidebar.php';
?>
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }

    /* Modern cards */
    .stat-box {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(17, 24, 39, 0.08);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 10px 30px rgba(0, 0, 0, 0.06);
        backdrop-filter: blur(6px);
    }

    .dark .stat-box {
        background: rgba(17, 24, 39, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
    }

    /* Better dark-mode text colors (readability) */
    .dark .text-gray-900 {
        color: #f9fafb !important;
    }

    .dark .text-gray-800 {
        color: #f3f4f6 !important;
    }

    .dark .text-gray-700 {
        color: #e5e7eb !important;
    }

    .dark .text-gray-600 {
        color: #d1d5db !important;
    }

    .dark .text-gray-500 {
        color: #9ca3af !important;
    }

    .dark .text-gray-400 {
        color: #9ca3af !important;
    }

    .dark .text-blue-600 {
        color: #60a5fa !important;
    }

    .dark .text-green-600 {
        color: #34d399 !important;
    }

    .dark .text-red-600 {
        color: #fb7185 !important;
    }

    .dark .text-purple-600 {
        color: #c084fc !important;
    }
</style>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8 fade-in">

        <!-- Header da Página -->
        <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Dashboard</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Visão geral do sistema e controle de votações.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" class="flex items-center gap-2 stat-box p-1 rounded-lg">
                    <?php if ($votacao_ativa): ?><input type="hidden" name="votacao_id"
                            value="<?= $votacao_ativa['id'] ?>"><?php endif; ?>
                    <input type="date" name="start" value="<?= htmlspecialchars($start_date ?? '') ?>"
                        class="bg-transparent border-none text-sm text-gray-600 dark:text-gray-300 focus:ring-0 rounded-md">
                    <span class="text-gray-400">-</span>
                    <input type="date" name="end" value="<?= htmlspecialchars($end_date ?? '') ?>"
                        class="bg-transparent border-none text-sm text-gray-600 dark:text-gray-300 focus:ring-0 rounded-md">
                    <button type="submit"
                        class="bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 p-1.5 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </button>
                </form>

                <button onclick="document.getElementById('modalNovaVotacao').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium shadow-lg shadow-blue-600/20 transition-all transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Nova Votação
                </button>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $total_vereadores = $pdo->query("SELECT COUNT(*) FROM eleitores")->fetchColumn(); // candidatos
            $nao_votaram = $total_vereadores - $total_geral;
            $nao_votaram = $nao_votaram >= 0 ? $nao_votaram : 0;
            ?>

            <!-- Card Candidatos -->
            <div class="stat-box rounded-2xl p-6 relative overflow-hidden group">
                <div
                    class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-blue-400 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Candidatos</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white mt-2"><?= $total_vereadores ?></h3>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-600 dark:text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card Votos -->
            <div class="stat-box rounded-2xl p-6 relative overflow-hidden group">
                <div
                    class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-green-400 to-green-600 opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Votos Registrados</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white mt-2" id="badge-votos">
                            <?= $total_geral ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-xl text-green-600 dark:text-green-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card Pendentes -->
            <div class="stat-box rounded-2xl p-6 relative overflow-hidden group">
                <div
                    class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-red-400 to-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendentes</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white mt-2" id="badge-nao">
                            <?= $nao_votaram ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-xl text-red-600 dark:text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card Status -->
            <div class="stat-box rounded-2xl p-6 relative overflow-hidden group">
                <div
                    class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-purple-400 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Votação</p>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mt-2 truncate max-w-[150px]"
                            title="<?= $votacao_ativa ? htmlspecialchars($votacao_ativa['titulo']) : 'Nenhuma' ?>">
                            <?= $votacao_ativa ? 'ABERTA' : 'FECHADA' ?>
                        </h3>
                    </div>
                    <div
                        class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl text-purple-600 dark:text-purple-400 animate-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($votacao_ativa): ?>
            <!-- Painel de Gráficos e Controle -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Gráficos -->
                <div class="xl:col-span-2 space-y-8">
                    <!-- Gráfico de Tendência -->
                    <div class="stat-box rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6">Tendência de Votos (Últimos 14
                            dias)</h3>
                        <div class="h-64 w-full">
                            <canvas id="graficoTendencia"></canvas>
                        </div>
                    </div>

                    <!-- Gráficos de Distribuição -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="stat-box rounded-2xl p-6">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 text-center">Distribuição (Pie)
                            </h3>
                            <div class="h-48 flex justify-center">
                                <canvas id="graficoSimNao"></canvas>
                            </div>
                        </div>
                        <div class="stat-box rounded-2xl p-6">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 text-center">Contagem Absoluta
                            </h3>
                            <div class="h-48 flex justify-center w-full">
                                <canvas id="graficoContagem"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações e Lista Rápida -->
                <div class="space-y-8">
                    <!-- Controle da Votação -->
                    <div class="stat-box rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Controle da Sessão</h3>
                        <p class="text-sm text-gray-500 mb-6">Gerencie o estado da votação atual.</p>

                        <div class="space-y-3">
                            <form method="POST" action="">
                                <input type="hidden" name="acao" value="encerrar_votacao">
                                <input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-400 py-3 rounded-xl font-semibold transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Encerrar Votação
                                </button>
                            </form>

                            <div class="grid grid-cols-2 gap-3">
                                <form method="POST" action=""
                                    onsubmit="return confirm('ATENÇÃO: Isso apagará TODOS os votos desta votação. Tem certeza?')">
                                    <input type="hidden" name="acao" value="resetar_votos">
                                    <input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                    <button type="submit"
                                        class="w-full flex items-center justify-center gap-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 dark:text-yellow-400 py-3 rounded-xl font-semibold transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Resetar
                                    </button>
                                </form>

                                <a href="../painel/resultados.php" target="_blank"
                                    class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 py-3 rounded-xl font-semibold transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd"
                                            d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Telão
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Últimos Votos -->
                    <div class="stat-box rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Últimos Votos</h3>
                        <div class="space-y-4" id="lista-ultimos-votos">
                            <!-- Preenchido via AJAX -->
                            <div class="animate-pulse space-y-3">
                                <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-lg"></div>
                                <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-lg"></div>
                                <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-lg"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Estado vazio / criar votação -->
            <div class="stat-box rounded-2xl p-12 text-center">
                <div
                    class="w-20 h-20 bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center mx-auto mb-6 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Nenhuma votação ativa</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">Inicie uma nova votação para começar a
                    receber votos dos vereadores em tempo real.</p>
                <button onclick="document.getElementById('modalNovaVotacao').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-medium shadow-lg shadow-blue-600/20 transition-all hover:scale-105">
                    Iniciar Votação
                </button>
            </div>
        <?php endif; ?>

        <!-- Lista de Histórico -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Histórico Recente</h3>
            </div>
            <div class="stat-box rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/50">
                                <th
                                    class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Título</th>
                                <th
                                    class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Data</th>
                                <th
                                    class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">
                                    Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <?php foreach ($votacoes as $v): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="p-4">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($v['titulo']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">
                                            <?= htmlspecialchars($v['descricao']) ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($v['status'] === 'aberta'): ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                Aberta
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Encerrada
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                        <?= date('d/m/Y H:i', strtotime($v['criada_em'])) ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <?php if ($v['status'] === 'encerrada'): ?>
                                            <form method="POST" action="" class="inline-block"
                                                onsubmit="return confirm('Reabrir esta votação encerrará a atual. Continuar?')">
                                                <input type="hidden" name="acao" value="abrir_votacao">
                                                <input type="hidden" name="votacao_id" value="<?= $v['id'] ?>">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                <button type="submit"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">Reabrir</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="../painel/exportar_pdf.php?votacao_id=<?= $v['id'] ?>" target="_blank"
                                            class="ml-3 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 text-sm font-medium">PDF</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nova Votação -->
<div id="modalNovaVotacao"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all duration-300 scale-95 opacity-0"
        id="modal-votacao-content">
        <!-- Cabeçalho com Gradiente -->
        <div class="relative bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Nova Votação</h3>
                </div>
                <button onclick="fecharModalVotacao()"
                    class="p-2 hover:bg-white/20 rounded-lg transition-all duration-200 group">
                    <svg class="w-6 h-6 text-white group-hover:rotate-90 transition-transform duration-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Corpo do Modal -->
        <form method="POST" action="">
            <div class="p-6 space-y-5">
                <input type="hidden" name="acao" value="criar_votacao">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">

                <div class="group">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Título / Proposição <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="titulo" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500"
                        placeholder="Ex: PL 001/2024 - Aprovação do Orçamento">
                </div>

                <div class="group">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Descrição (Opcional)
                    </label>
                    <textarea name="descricao" rows="4"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500 resize-none"
                        placeholder="Detalhes adicionais sobre a votação..."></textarea>
                </div>
            </div>

            <!-- Rodapé com Botões -->
            <div
                class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                <button type="button" onclick="fecharModalVotacao()"
                    class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 font-semibold">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all duration-200 transform hover:scale-105">
                    Criar Votação
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Animação de entrada do modal de votação */
    #modalNovaVotacao.flex #modal-votacao-content {
        animation: modalVotacaoEnter 0.3s ease-out forwards;
    }

    @keyframes modalVotacaoEnter {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
</style>

<script>
    function fecharModalVotacao() {
        const modal = document.getElementById('modalNovaVotacao');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // Abrir modal (atualizar os botões existentes)
    function abrirModalVotacao() {
        const modal = document.getElementById('modalNovaVotacao');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    // Fechar com ESC
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            fecharModalVotacao();
        }
    });

    // Fechar ao clicar no backdrop
    document.getElementById('modalNovaVotacao')?.addEventListener('click', function (e) {
        if (e.target === this) {
            fecharModalVotacao();
        }
    });

    // Atualizar botões existentes para usar a nova função
    document.querySelectorAll('[onclick*="modalNovaVotacao"]').forEach(btn => {
        btn.setAttribute('onclick', 'abrirModalVotacao()');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Configuração Charts
    Chart.defaults.color = '#9ca3af';
    Chart.defaults.borderColor = 'rgba(156, 163, 175, 0.1)';

    <?php if ($votacao_ativa): ?>
        // Gráfico Tendência
        const ctxTrend = document.getElementById('graficoTendencia').getContext('2d');
        const trendChart = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= json_encode($trend_labels) ?>,
                datasets: [
                    {
                        label: 'Sim',
                        data: <?= json_encode($trend_sim) ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Não',
                        data: <?= json_encode($trend_nao) ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Gráfico Pizza
        const ctxPie = document.getElementById('graficoSimNao').getContext('2d');
        const pieChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Sim', 'Não'],
                datasets: [{
                    data: [<?= $total_sim ?>, <?= $total_nao ?>],
                    backgroundColor: ['#22c55e', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfico Barra
        const ctxBar = document.getElementById('graficoContagem').getContext('2d');
        const barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Votos'],
                datasets: [
                    { label: 'Sim', data: [<?= $total_sim ?>], backgroundColor: '#22c55e', borderRadius: 6 },
                    { label: 'Não', data: [<?= $total_nao ?>], backgroundColor: '#ef4444', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Atualização em Tempo Real
        function updateDashboard() {
            const urlParams = new URLSearchParams(window.location.search);
            let url = 'dashboard.php?ajax=1';
            if (urlParams.has('start')) url += '&start=' + urlParams.get('start');
            if (urlParams.has('end')) url += '&end=' + urlParams.get('end');

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    // Atualizar Badges
                    document.getElementById('badge-votos').innerText = data.total;
                    const totalCandidatos = <?= $total_vereadores ?>;
                    const pendentes = totalCandidatos - data.total;
                    document.getElementById('badge-nao').innerText = pendentes < 0 ? 0 : pendentes;

                    // Atualizar Charts
                    trendChart.data.datasets[0].data = data.trend_sim;
                    trendChart.data.datasets[1].data = data.trend_nao;
                    trendChart.update('none');

                    pieChart.data.datasets[0].data = [data.sim, data.nao];
                    pieChart.update('none');

                    barChart.data.datasets[0].data = [data.sim];
                    barChart.data.datasets[1].data = [data.nao];
                    barChart.update('none');

                    // Atualizar Lista Últimos Votos
                    const listaContainer = document.getElementById('lista-ultimos-votos');
                    if (data.last_votes.length === 0) {
                        listaContainer.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Nenhum voto registrado ainda.</p>';
                    } else {
                        listaContainer.innerHTML = data.last_votes.map(v => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg animate-fade-in">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center font-bold text-xs">
                                    ${v.nome.charAt(0)}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">${v.nome}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">${new Date(v.criado_em).toLocaleTimeString()}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded text-xs font-bold uppercase ${v.voto === 'sim' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}">
                                ${v.voto}
                            </span>
                        </div>
                    `).join('');
                    }
                });
        }

        setInterval(updateDashboard, 3000);
        updateDashboard(); // Initial call
    <?php endif; ?>
</script>
<?php require_once 'footer.php'; ?>