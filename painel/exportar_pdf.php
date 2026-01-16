<?php
// Exportação de votos em PDF
require_once '../config/database.php';
require_once '../config/functions.php';

require_once __DIR__ . '/../vendor/autoload.php'; // Dompdf
use Dompdf\Dompdf;

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

$html = '<h2>Relatório de Votação</h2>';
$html .= '<table border="1" cellpadding="5" cellspacing="0"><thead><tr>';
$html .= '<th>ID</th><th>Votação</th><th>Nome</th><th>CPF</th><th>Cargo</th><th>Voto</th><th>Data/Hora</th><th>IP</th>';
$html .= '</tr></thead><tbody>';
foreach ($votos as $voto) {
    $html .= '<tr>';
    $html .= '<td>' . $voto['id'] . '</td>';
    $html .= '<td>' . htmlspecialchars($voto['titulo']) . '</td>';
    $html .= '<td>' . htmlspecialchars($voto['nome']) . '</td>';
    $html .= '<td>' . htmlspecialchars($voto['cpf']) . '</td>';
    $html .= '<td>' . htmlspecialchars($voto['cargo']) . '</td>';
    $html .= '<td>' . strtoupper($voto['voto']) . '</td>';
    $html .= '<td>' . date('d/m/Y H:i', strtotime($voto['criado_em'])) . '</td>';
    $html .= '<td>' . htmlspecialchars($voto['ip_address']) . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('votacao_' . $votacao_id . '_votos.pdf', ['Attachment' => true]);
exit;
