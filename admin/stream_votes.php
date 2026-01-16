<?php
// SSE endpoint para emitir atualizações de votos
require_once __DIR__ . '/../config/database.php';

default_timezone_set('America/Sao_Paulo');
set_time_limit(0);

$votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;
$start = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] . ' 00:00:00' : null;
$end = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] . ' 23:59:59' : null;

header("Content-Type: text/event-stream\n\n");
header('Cache-Control: no-cache');

if (!$votacao_id) {
    echo "event: error\n";
    echo "data: {\"error\":\"votacao_id missing\"}\n\n";
    flush();
    exit;
}

$where = '';
$paramsBase = [$votacao_id];
if ($start && $end) {
    $where = ' AND criado_em BETWEEN ? AND ?';
    $paramsBase[] = $start;
    $paramsBase[] = $end;
}

$lastHash = '';

while (true) {
    // calcular dados
    $stmtSim = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'" . $where);
    $stmtSim->execute($paramsBase);
    $sim = (int)$stmtSim->fetch()['total'];

    $stmtNao = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'" . $where);
    $stmtNao->execute($paramsBase);
    $nao = (int)$stmtNao->fetch()['total'];

    $total = $sim + $nao;
    $percSim = $total > 0 ? round(($sim / $total) * 100, 1) : 0;
    $percNao = $total > 0 ? round(($nao / $total) * 100, 1) : 0;

    // trend últimos 14 dias
    $days = 14;
    $trend = [];
    $labels = [];
    $startDate = new DateTime();
    $startDate->sub(new DateInterval('P'.($days-1).'D'));
    for ($i = 0; $i < $days; $i++) {
        $d = $startDate->format('Y-m-d');
        $labels[] = $startDate->format('d/m');
        $trend[$d] = ['sim' => 0, 'nao' => 0];
        $startDate->add(new DateInterval('P1D'));
    }

    $trendQuery = "SELECT DATE(criado_em) as d, SUM(CASE WHEN voto='sim' THEN 1 ELSE 0 END) as sim, SUM(CASE WHEN voto='nao' THEN 1 ELSE 0 END) as nao FROM votos WHERE votacao_id = ?" . $where . " AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(criado_em)";
    $paramsTrend = $paramsBase;
    $paramsTrend[] = $days - 1;
    $stmtTrend = $pdo->prepare($trendQuery);
    $stmtTrend->execute($paramsTrend);
    $rows = $stmtTrend->fetchAll();
    foreach ($rows as $r) {
        $trend[$r['d']] = ['sim' => (int)$r['sim'], 'nao' => (int)$r['nao']];
    }

    $trend_sim = array_values(array_map(function($v){ return $v['sim']; }, $trend));
    $trend_nao = array_values(array_map(function($v){ return $v['nao']; }, $trend));

    // últimos 5 votos
    $lastParams = $paramsBase;
    $lastQuery = "SELECT nome, cpf, voto, criado_em FROM votos WHERE votacao_id = ?" . $where . " ORDER BY criado_em DESC LIMIT 5";
    $lastStmt = $pdo->prepare($lastQuery);
    $lastStmt->execute($lastParams);
    $last_votes = $lastStmt->fetchAll();

    $payload = [
        'sim' => $sim,
        'nao' => $nao,
        'total' => $total,
        'percentual_sim' => $percSim,
        'percentual_nao' => $percNao,
        'trend_labels' => $labels,
        'trend_sim' => $trend_sim,
        'trend_nao' => $trend_nao,
        'last_votes' => $last_votes,
        'timestamp' => time()
    ];

    $hash = md5(json_encode($payload));
    if ($hash !== $lastHash) {
        echo "data: " . json_encode($payload) . "\n\n";
        flush();
        $lastHash = $hash;
    } else {
        // enviar um comentário para manter a conexão viva
        echo ": heartbeat\n\n";
        flush();
    }

    // aguardar 5 segundos
    sleep(5);
}
*** End Patch