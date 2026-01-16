<?php
// Exportação de votos em CSV
require_once '../config/database.php';

$votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;
if (!$votacao_id) {
    die('ID da votação não informado.');
}

// filtros opcionais
$start = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] . ' 00:00:00' : null;
$end = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] . ' 23:59:59' : null;

$where = '';
$params = [$votacao_id];
if ($start && $end) {
    $where = ' AND v.criado_em BETWEEN ? AND ?';
    $params[] = $start;
    $params[] = $end;
}

$stmt = $pdo->prepare('SELECT v.*, vt.titulo FROM votos v JOIN votacoes vt ON v.votacao_id = vt.id WHERE v.votacao_id = ?' . $where . ' ORDER BY v.criado_em ASC');
$stmt->execute($params);
$votos = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="votacao_' . $votacao_id . '_votos.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Votação', 'Nome', 'CPF', 'Cargo', 'Voto', 'Data/Hora', 'IP']);
foreach ($votos as $voto) {
    fputcsv($output, [
        $voto['id'],
        $voto['titulo'],
        $voto['nome'],
        $voto['cpf'],
        $voto['cargo'],
        strtoupper($voto['voto']),
        date('d/m/Y H:i', strtotime($voto['criado_em'])),
        $voto['ip_address']
    ]);
}
fclose($output);
exit;
