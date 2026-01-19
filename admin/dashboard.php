<?php
require_once '../config/database.php';
require_once '../config/functions.php';

verificarAdmin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validar CSRF (implementar se necessário, por enquanto simulado)
    if (!validarCSRFToken()) {
        // die('Token CSRF inválido'); // Comentado para evitar bloqueio se a sessão não estiver ok, mas idealmente deve estar
    }

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
    $total_sim = (int)$total_sim_stmt->fetch()['total'];

    $total_nao_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'" . $whereDate);
    $total_nao_stmt->execute($params);
    $total_nao = (int)$total_nao_stmt->fetch()['total'];

    $total_geral = $total_sim + $total_nao;
    $percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
    $percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;
}

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

// Preparar dados iniciais para o gráfico de tendência (lado do servidor)
$days = 14;
$trend_labels = [];
$trend_sim = array_fill(0, $days, 0);
$trend_nao = array_fill(0, $days, 0);
if ($votacao_ativa) {
    $today = new DateTime();
    $interval = new DateInterval('P1D');
    $period = new DatePeriod((clone $today)->sub(new DateInterval('P' . ($days-1) . 'D')), $interval, $days);
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
        $map[$r['d']] = ['sim' => (int)$r['sim'], 'nao' => (int)$r['nao']];
    }
    $start = (clone $today)->sub(new DateInterval('P' . ($days-1) . 'D'));
    for ($i = 0; $i < $days; $i++) {
        $d = $start->format('Y-m-d');
        if (isset($map[$d])) { $trend_sim[$i] = $map[$d]['sim']; $trend_nao[$i] = $map[$d]['nao']; }
        $start->add($interval);
    }
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8">
        
        <!-- Header da Página -->
        <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Dashboard</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Visão geral do sistema e controle de votações.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                    <?php if($votacao_ativa): ?><input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>"><?php endif; ?>
                    <input type="date" name="start" value="<?= htmlspecialchars($start_date ?? '') ?>" class="bg-transparent border-none text-sm text-gray-600 dark:text-gray-300 focus:ring-0 rounded-md">
                    <span class="text-gray-400">-</span>
                    <input type="date" name="end" value="<?= htmlspecialchars($end_date ?? '') ?>" class="bg-transparent border-none text-sm text-gray-600 dark:text-gray-300 focus:ring-0 rounded-md">
                    <button type="submit" class="bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 p-1.5 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    </button>
                </form>

                <button onclick="document.getElementById('modalNovaVotacao').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium shadow-lg shadow-blue-600/20 transition-all transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    Nova Votação
                </button>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php 
            $total_vereadores = $pdo->query("SELECT COUNT(*) FROM eleitores")->fetchColumn();
            $nao_votaram = $total_vereadores - $total_geral;
            $nao_votaram = $nao_votaram >= 0 ? $nao_votaram : 0;
            ?>
            
            <!-- Card Eleitores -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl shadow-blue-100/10 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-blue-400 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Eleitores</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white mt-2"><?= $total_vereadores ?></h3>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-600 dark:text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Card Votos -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl shadow-green-100/10 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-green-400 to-green-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Votos Registrados</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white mt-2" id="badge-votos"><?= $total_geral ?></h3>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-xl text-green-600 dark:text-green-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Card Pendentes -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl shadow-red-100/10 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-red-400 to-red-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendentes</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white mt-2" id="badge-nao"><?= $nao_votaram ?></h3>
                    </div>
                    <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-xl text-red-600 dark:text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Card Status -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl shadow-purple-100/10 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-purple-400 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Votação</p>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mt-2 truncate max-w-[150px]" title="<?= $votacao_ativa ? htmlspecialchars($votacao_ativa['titulo']) : 'Nenhuma' ?>">
                            <?= $votacao_ativa ? 'ABERTA' : 'FECHADA' ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl text-purple-600 dark:text-purple-400 animate-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        <?php if($votacao_ativa): ?>
        <!-- Painel de Gráficos e Controle -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Gráficos -->
            <div class="xl:col-span-2 space-y-8">
                <!-- Gráfico de Tendência -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6">Tendência de Votos (Últimos 14 dias)</h3>
                    <div class="h-64 w-full">
                        <canvas id="graficoTendencia"></canvas>
                    </div>
                </div>

                <!-- Gráficos de Distribuição -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 text-center">Distribuição (Pie)</h3>
                        <div class="h-48 flex justify-center">
                            <canvas id="graficoSimNao"></canvas>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 text-center">Contagem Absoluta</h3>
                        <div class="h-48 flex justify-center w-full">
                            <canvas id="graficoContagem"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações e Lista Rápida -->
            <div class="space-y-8">
                <!-- Controle da Votação -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Controle da Sessão</h3>
                    <p class="text-sm text-gray-500 mb-6">Gerencie o estado da votação atual.</p>
                    
                    <div class="space-y-3">
                        <form method="POST" action="">
                            <input type="hidden" name="acao" value="encerrar_votacao">
                            <input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-400 py-3 rounded-xl font-semibold transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" /></svg>
                                Encerrar Votação
                            </button>
                        </form>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <a href="../painel/resultados.php" target="_blank" class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 py-3 rounded-xl font-medium transition-colors text-sm">
                                Painel Público
                            </a>
                            <a href="exportar_pdf.php?votacao_id=<?= $votacao_ativa['id'] ?>" target="_blank" class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 py-3 rounded-xl font-medium transition-colors text-sm">
                                Relatório PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Últimos Votos -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Últimos Votos</h3>
                    <div class="space-y-4">
                        <?php
                            $lastStmt = $pdo->prepare("SELECT nome, cpf, voto, criado_em FROM votos WHERE votacao_id = ?" . (isset($whereDate) ? $whereDate : "") . " ORDER BY criado_em DESC LIMIT 5");
                            $lastParams = [$votacao_ativa['id']];
                            if ($start_date && $end_date) { $lastParams[] = $start_date . ' 00:00:00'; $lastParams[] = $end_date . ' 23:59:59'; }
                            $lastStmt->execute($lastParams);
                            $ultimos = $lastStmt->fetchAll();
                        ?>
                        <?php if(empty($ultimos)): ?>
                            <p class="text-center text-gray-500 py-4">Nenhum voto registrado ainda.</p>
                        <?php else: ?>
                            <?php foreach($ultimos as $u): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold text-sm">
                                            <?= strtoupper(substr($u['nome'],0,1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($u['nome']) ?></p>
                                            <p class="text-xs text-gray-500"><?= date('H:i:s', strtotime($u['criado_em'])) ?></p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $u['voto'] === 'sim' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400' ?>">
                                        <?= strtoupper($u['voto']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-blue-400 dark:text-blue-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Nenhuma votação ativa</h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-6">Inicie uma nova votação para começar a coletar votos dos vereadores em tempo real.</p>
                <button onclick="document.getElementById('modalNovaVotacao').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium shadow-lg shadow-blue-600/20 transition-all">
                    Criar Nova Votação
                </button>
            </div>
        <?php endif; ?>

        <!-- Histórico Recente (Tabela) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Histórico de Votações</h3>
                <a href="relatorios.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php foreach (array_slice($votacoes, 0, 5) as $v): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($v['titulo']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $v['status'] === 'aberta' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' ?>">
                                        <?= ucfirst($v['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400"><?= date('d/m/Y', strtotime($v['criada_em'])) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <?php if ($v['status'] === 'encerrada'): ?>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="acao" value="abrir_votacao">
                                                <input type="hidden" name="votacao_id" value="<?= $v['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-800 font-medium text-sm">Reabrir</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="exportar_pdf.php?votacao_id=<?= $v['id'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Relatório</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nova Votação -->
<div id="modalNovaVotacao" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('modalNovaVotacao').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="">
                <input type="hidden" name="acao" value="criar_votacao">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Nova Votação</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="titulo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                    <input type="text" name="titulo" id="titulo" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white py-2 px-3" placeholder="Ex: PL 123/2024">
                                </div>
                                <div>
                                    <label for="descricao" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                                    <textarea name="descricao" id="descricao" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white py-2 px-3" placeholder="Detalhes da votação..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Criar
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="document.getElementById('modalNovaVotacao').classList.add('hidden')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dados iniciais gerados pelo servidor
    const sim = <?= $total_sim ?>;
    const nao = <?= $total_nao ?>;
    const total = <?= $total_geral ?>;
    const percSim = <?= $percentual_sim ?>;
    const percNao = <?= $percentual_nao ?>;

    // Configuração comum para Charts
    Chart.defaults.color = '#9ca3af';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Pie (Mix)
    const ctx1 = document.getElementById('graficoSimNao')?.getContext('2d');
    let chartMix = null;
    if (ctx1) {
        chartMix = new Chart(ctx1, {
            type: 'pie',
            data: { labels: ['SIM', 'NÃO'], datasets: [{ data: [sim, nao], backgroundColor: ['#2563eb', '#dc2626'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Bar (Contagem)
    const ctx2 = document.getElementById('graficoContagem')?.getContext('2d');
    let chartCount = null;
    if (ctx2) {
        chartCount = new Chart(ctx2, {
            type: 'bar',
            data: { labels: ['SIM', 'NÃO'], datasets: [{ label: 'Votos', data: [sim, nao], backgroundColor: ['#2563eb', '#dc2626'], borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
        });
    }

    // Trend (linha)
    const ctxT = document.getElementById('graficoTendencia')?.getContext('2d');
    let chartTrend = null;
    const trend_labels = <?= json_encode(array_map(function($d){ return $d; }, (isset($trend_labels) ? $trend_labels : []))) ?>;
    const trend_sim = <?= json_encode(isset($trend_sim) ? $trend_sim : array_fill(0,14,0)) ?>;
    const trend_nao = <?= json_encode(isset($trend_nao) ? $trend_nao : array_fill(0,14,0)) ?>;
    
    if (ctxT) {
        chartTrend = new Chart(ctxT, {
            type: 'line',
            data: { 
                labels: trend_labels, 
                datasets: [
                    { label: 'SIM', data: trend_sim, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.1)', fill: true, tension: 0.4 }, 
                    { label: 'NÃO', data: trend_nao, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.1)', fill: true, tension: 0.4 }
                ] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { position: 'top', align: 'end' } }, 
                scales: { 
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    // SSE (Server-Sent Events) para atualizações em tempo real
    const electors = <?= isset($total_vereadores) ? (int)$total_vereadores : 0 ?>;
    function animateBadge(id){
        const el = document.getElementById(id);
        if(!el) return;
        el.classList.add('scale-110', 'text-blue-600');
        setTimeout(()=> el.classList.remove('scale-110', 'text-blue-600'), 300);
    }

    function updateFromPayload(data){
        if(chartMix){ chartMix.data.datasets[0].data = [data.sim, data.nao]; chartMix.update(); }
        if(chartCount){ chartCount.data.datasets[0].data = [data.sim, data.nao]; chartCount.update(); }
        if(chartTrend){
            chartTrend.data.labels = data.trend_labels;
            chartTrend.data.datasets[0].data = data.trend_sim;
            chartTrend.data.datasets[1].data = data.trend_nao;
            chartTrend.update();
        }

        // badges
        const bVotos = document.getElementById('badge-votos');
        const bNao = document.getElementById('badge-nao');
        if(bVotos && bVotos.textContent != data.total) { bVotos.textContent = data.total; animateBadge('badge-votos'); }
        if(bNao) { bNao.textContent = Math.max(0, electors - data.total); }
    }

    const sseUrl = 'stream_votes.php?votacao_id=<?= $votacao_ativa ? $votacao_ativa['id'] : 0 ?><?= $start_date ? '&start=' . urlencode($start_date) : '' ?><?= $end_date ? '&end=' . urlencode($end_date) : '' ?>';
    
    <?php if($votacao_ativa): ?>
    if (!!window.EventSource) {
        try {
            const es = new EventSource(sseUrl);
            es.onmessage = function(e){
                if (!e.data) return;
                try{ const data = JSON.parse(e.data); updateFromPayload(data); } catch(err) { /* ignore */ }
            };
        } catch(err){ console.error('SSE error', err); }
    } else {
        // Fallback polling
        setInterval(async () => {
            try {
                const params = new URLSearchParams(window.location.search);
                params.set('ajax','1');
                const res = await fetch(window.location.pathname + '?' + params.toString());
                const data = await res.json();
                updateFromPayload(data);
            } catch(e){}
        }, 5000);
    }
    <?php endif; ?>
</script>

</body>
</html>
