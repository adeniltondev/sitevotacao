<?php
/**
 * Exportação de Votos em CSV - Template Moderno e Funcional
 */
require_once '../config/database.php';
require_once '../config/functions.php';

$votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;
if (!$votacao_id) {
    die('ID da votação não informado.');
}

// Buscar dados da votação
$stmt = $pdo->prepare('SELECT * FROM votacoes WHERE id = ?');
$stmt->execute([$votacao_id]);
$votacao = $stmt->fetch();

if (!$votacao) {
    die('Votação não encontrada.');
}

// Filtros opcionais
$start = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] . ' 00:00:00' : null;
$end = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] . ' 23:59:59' : null;

$where = '';
$params = [$votacao_id];
if ($start && $end) {
    $where = ' AND v.criado_em BETWEEN ? AND ?';
    $params[] = $start;
    $params[] = $end;
}

// Buscar votos
$stmt = $pdo->prepare('SELECT v.*, vt.titulo, vt.descricao FROM votos v JOIN votacoes vt ON v.votacao_id = vt.id WHERE v.votacao_id = ?' . $where . ' ORDER BY v.criado_em ASC');
$stmt->execute($params);
$votos = $stmt->fetchAll();

// Calcular totais
$total_sim = 0;
$total_nao = 0;
foreach ($votos as $voto) {
    if (strtolower($voto['voto']) === 'sim') {
        $total_sim++;
    } else {
        $total_nao++;
    }
}
$total_geral = count($votos);
$percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 2) : 0;
$percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 2) : 0;

// Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="votacao_' . $votacao_id . '_relatorio_' . date('Y-m-d') . '.csv"');

// Adicionar BOM para UTF-8 (melhor compatibilidade com Excel)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Cabeçalho informativo
fputcsv($output, ['RELATÓRIO DE VOTAÇÃO - ' . strtoupper($votacao['titulo'])]);
fputcsv($output, []);
fputcsv($output, ['Data de Criação:', date('d/m/Y H:i:s', strtotime($votacao['criada_em']))]);
fputcsv($output, ['Status:', strtoupper($votacao['status'])]);

if ($votacao['descricao']) {
    fputcsv($output, ['Descrição:', $votacao['descricao']]);
}

if ($start && $end) {
    fputcsv($output, ['Período:', date('d/m/Y', strtotime($start)) . ' até ' . date('d/m/Y', strtotime($end))]);
}

fputcsv($output, []);
fputcsv($output, ['RESUMO']);
fputcsv($output, ['Total de Votos SIM:', $total_sim, '(' . $percentual_sim . '%)']);
fputcsv($output, ['Total de Votos NÃO:', $total_nao, '(' . $percentual_nao . '%)']);
fputcsv($output, ['Total Geral:', $total_geral]);
fputcsv($output, []);

// Cabeçalho da tabela
fputcsv($output, [
    'Nº',
    'Nome Completo',
    'CPF',
    'Cargo',
    'Voto',
    'Data',
    'Hora',
    'Data/Hora Completa',
    'Endereço IP'
]);

// Dados dos votos
foreach ($votos as $index => $voto) {
    $data_hora = date('d/m/Y H:i:s', strtotime($voto['criado_em']));
    $data = date('d/m/Y', strtotime($voto['criado_em']));
    $hora = date('H:i:s', strtotime($voto['criado_em']));
    
    fputcsv($output, [
        $index + 1,
        $voto['nome'],
        formatarCPF($voto['cpf']),
        $voto['cargo'] ?? '-',
        strtoupper($voto['voto']),
        $data,
        $hora,
        $data_hora,
        $voto['ip_address'] ?? '-'
    ]);
}

fputcsv($output, []);
fputcsv($output, ['Documento gerado em:', date('d/m/Y H:i:s')]);

fclose($output);
exit;
