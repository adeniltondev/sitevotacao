<?php
/**
 * Exportação de Eleitores em PDF
 */
require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Erro: Biblioteca Dompdf não encontrada. Execute: composer require dompdf/dompdf');
}

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Carregar configurações
$configFile = __DIR__ . '/../config/settings.json';
$settings = ['sistema_nome' => 'VotaCâmara'];
if (file_exists($configFile)) {
    $savedSettings = json_decode(file_get_contents($configFile), true);
    if ($savedSettings) {
        $settings = array_merge($settings, $savedSettings);
    }
}

// Buscar eleitores
$eleitores = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
@page { margin: 20mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: "DejaVu Sans", "Arial", sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.5; }
.header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; text-align: center; }
.header h1 { font-size: 22pt; font-weight: bold; margin-bottom: 5px; }
.info-box { background: #f9fafb; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
thead { background: #1e40af; color: white; }
th { padding: 12px 8px; text-align: left; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9pt; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
.badge-ativo { background: #d1fae5; color: #065f46; }
.badge-bloqueado { background: #fee2e2; color: #991b1b; }
.footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 8pt; }
</style></head><body>
<div class="header">
    <h1>' . htmlspecialchars($settings['sistema_nome']) . '</h1>
    <div class="subtitle">Relatório de Eleitores Cadastrados</div>
</div>
<div class="info-box">
    <strong>Total de Eleitores:</strong> ' . count($eleitores) . '
</div>
<table>
    <thead>
        <tr><th>#</th><th>Nome</th><th>CPF</th><th>Cargo</th><th>Perfil</th><th>Status</th></tr>
    </thead>
    <tbody>';

foreach ($eleitores as $index => $eleitor) {
    $status_class = $eleitor['ativo'] ? 'badge-ativo' : 'badge-bloqueado';
    $status_text = $eleitor['ativo'] ? 'ATIVO' : 'BLOQUEADO';
    $html .= '<tr>
        <td>' . ($index + 1) . '</td>
        <td>' . htmlspecialchars($eleitor['nome']) . '</td>
        <td>' . formatarCPF($eleitor['cpf']) . '</td>
        <td>' . htmlspecialchars($eleitor['cargo'] ?? '-') . '</td>
        <td>' . strtoupper($eleitor['perfil'] ?? 'vereador') . '</td>
        <td><span class="badge ' . $status_class . '">' . $status_text . '</span></td>
    </tr>';
}

$html .= '</tbody></table>
<div class="footer">
    <p>Documento gerado em ' . date('d/m/Y H:i:s') . ' | ' . htmlspecialchars($settings['sistema_nome']) . '</p>
</div>
</body></html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('eleitores_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
exit;
