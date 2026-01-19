<?php
/**
 * Exportação de Votos em PDF - Template Moderno e Profissional
 */
require_once '../config/database.php';
require_once '../config/functions.php';

// Verificar se Dompdf está disponível
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Erro: Biblioteca Dompdf não encontrada. Execute: composer require dompdf/dompdf');
}

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;
if (!$votacao_id) {
    die('ID da votação não informado.');
}

// Carregar configurações do sistema
$configFile = __DIR__ . '/../config/settings.json';
$settings = [
    'sistema_nome' => 'VotaCâmara',
    'sistema_cor' => 'blue',
];
if (file_exists($configFile)) {
    $savedSettings = json_decode(file_get_contents($configFile), true);
    if ($savedSettings) {
        $settings = array_merge($settings, $savedSettings);
    }
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
$stmt = $pdo->prepare('SELECT v.*, vt.titulo, vt.descricao, vt.status FROM votos v JOIN votacoes vt ON v.votacao_id = vt.id WHERE v.votacao_id = ?' . $where . ' ORDER BY v.criado_em ASC');
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
$percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
$percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;

// HTML do PDF com design moderno
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", "Arial", sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.5;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }
        .header h1 {
            font-size: 22pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12pt;
            opacity: 0.95;
        }
        .info-box {
            background: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h2 {
            font-size: 14pt;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            color: #4b5563;
        }
        .info-value {
            display: table-cell;
            color: #1f2937;
        }
        .stats {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        .stat-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
            margin: 0 5px;
        }
        .stat-number {
            font-size: 24pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 9pt;
            color: #6b7280;
            text-transform: uppercase;
        }
        .stat-sim {
            border-top: 4px solid #10b981;
        }
        .stat-nao {
            border-top: 4px solid #ef4444;
        }
        .stat-total {
            border-top: 4px solid #6366f1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        thead {
            background: #1e40af;
            color: white;
        }
        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        tbody tr:hover {
            background: #f9fafb;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-sim {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-nao {
            background: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 8pt;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($settings['sistema_nome']) . '</h1>
        <div class="subtitle">Relatório de Votação</div>
    </div>

    <div class="info-box">
        <h2>Informações da Votação</h2>
        <div class="info-row">
            <span class="info-label">Título:</span>
            <span class="info-value">' . htmlspecialchars($votacao['titulo']) . '</span>
        </div>';

if ($votacao['descricao']) {
    $html .= '
        <div class="info-row">
            <span class="info-label">Descrição:</span>
            <span class="info-value">' . htmlspecialchars($votacao['descricao']) . '</span>
        </div>';
}

$html .= '
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">' . strtoupper($votacao['status']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Data de Criação:</span>
            <span class="info-value">' . date('d/m/Y H:i:s', strtotime($votacao['criada_em'])) . '</span>
        </div>';

if ($start && $end) {
    $html .= '
        <div class="info-row">
            <span class="info-label">Período:</span>
            <span class="info-value">' . date('d/m/Y', strtotime($start)) . ' até ' . date('d/m/Y', strtotime($end)) . '</span>
        </div>';
}

$html .= '
    </div>

    <div class="stats">
        <div class="stat-box stat-sim">
            <div class="stat-number">' . $total_sim . '</div>
            <div class="stat-label">Votos Sim (' . $percentual_sim . '%)</div>
        </div>
        <div class="stat-box stat-nao">
            <div class="stat-number">' . $total_nao . '</div>
            <div class="stat-label">Votos Não (' . $percentual_nao . '%)</div>
        </div>
        <div class="stat-box stat-total">
            <div class="stat-number">' . $total_geral . '</div>
            <div class="stat-label">Total de Votos</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Cargo</th>
                <th>Voto</th>
                <th>Data/Hora</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>';

foreach ($votos as $index => $voto) {
    $voto_lower = strtolower($voto['voto']);
    $badge_class = $voto_lower === 'sim' ? 'badge-sim' : 'badge-nao';
    
    $html .= '
            <tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($voto['nome']) . '</td>
                <td>' . formatarCPF($voto['cpf']) . '</td>
                <td>' . htmlspecialchars($voto['cargo'] ?? '-') . '</td>
                <td><span class="badge ' . $badge_class . '">' . strtoupper($voto['voto']) . '</span></td>
                <td>' . date('d/m/Y H:i:s', strtotime($voto['criado_em'])) . '</td>
                <td>' . htmlspecialchars($voto['ip_address'] ?? '-') . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        <p>Documento gerado em ' . date('d/m/Y H:i:s') . ' | ' . htmlspecialchars($settings['sistema_nome']) . '</p>
        <p>Este é um documento oficial gerado automaticamente pelo sistema de votação.</p>
    </div>
</body>
</html>';

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nome do arquivo
$filename = 'votacao_' . $votacao_id . '_relatorio_' . date('Y-m-d') . '.pdf';

// Output
$dompdf->stream($filename, ['Attachment' => true]);
exit;
