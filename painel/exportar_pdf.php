<?php
/**
 * Exportação de Votos em PDF - Versão sem Composer (Browser Print)
 * Esta versão usa a impressão do navegador para gerar PDFs
 */
require_once '../config/database.php';
require_once '../config/functions.php';

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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Votação - <?= htmlspecialchars($votacao['titulo']) ?></title>
    <style>
        /* Estilos para tela */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f3f4f6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .header .subtitle {
            font-size: 18px;
            opacity: 0.95;
        }
        
        .info-box {
            background: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 6px;
        }
        
        .info-box h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #4b5563;
            min-width: 180px;
        }
        
        .info-value {
            color: #1f2937;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box {
            text-align: center;
            padding: 25px;
            background: #f9fafb;
            border-radius: 12px;
            border-top: 4px solid;
        }
        
        .stat-sim { border-color: #10b981; }
        .stat-nao { border-color: #ef4444; }
        .stat-total { border-color: #6366f1; }
        
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        thead {
            background: #1e40af;
            color: white;
        }
        
        th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
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
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Estilos para impressão */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                max-width: 100%;
                padding: 20px;
                box-shadow: none;
                border-radius: 0;
            }
            
            .print-button {
                display: none;
            }
            
            .header {
                page-break-inside: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            .stat-box {
                page-break-inside: avoid;
            }
        }
        
        @page {
            margin: 1.5cm;
            size: A4;
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        <svg style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
        </svg>
        Imprimir / Salvar PDF
    </button>

    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($settings['sistema_nome']) ?></h1>
            <div class="subtitle">Relatório de Votação</div>
        </div>

        <div class="info-box">
            <h2>Informações da Votação</h2>
            <div class="info-row">
                <span class="info-label">Título:</span>
                <span class="info-value"><?= htmlspecialchars($votacao['titulo']) ?></span>
            </div>
            <?php if ($votacao['descricao']): ?>
            <div class="info-row">
                <span class="info-label">Descrição:</span>
                <span class="info-value"><?= htmlspecialchars($votacao['descricao']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?= strtoupper($votacao['status']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Data de Criação:</span>
                <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($votacao['criada_em'])) ?></span>
            </div>
            <?php if ($start && $end): ?>
            <div class="info-row">
                <span class="info-label">Período:</span>
                <span class="info-value"><?= date('d/m/Y', strtotime($start)) ?> até <?= date('d/m/Y', strtotime($end)) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="stats">
            <div class="stat-box stat-sim">
                <div class="stat-number"><?= $total_sim ?></div>
                <div class="stat-label">Votos Sim (<?= $percentual_sim ?>%)</div>
            </div>
            <div class="stat-box stat-nao">
                <div class="stat-number"><?= $total_nao ?></div>
                <div class="stat-label">Votos Não (<?= $percentual_nao ?>%)</div>
            </div>
            <div class="stat-box stat-total">
                <div class="stat-number"><?= $total_geral ?></div>
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
            <tbody>
                <?php foreach ($votos as $index => $voto): ?>
                <?php
                    $voto_lower = strtolower($voto['voto']);
                    $badge_class = $voto_lower === 'sim' ? 'badge-sim' : 'badge-nao';
                ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($voto['nome']) ?></td>
                    <td><?= formatarCPF($voto['cpf']) ?></td>
                    <td><?= htmlspecialchars($voto['cargo'] ?? '-') ?></td>
                    <td><span class="badge <?= $badge_class ?>"><?= strtoupper($voto['voto']) ?></span></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($voto['criado_em'])) ?></td>
                    <td><?= htmlspecialchars($voto['ip_address'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            <p><strong>Documento gerado em <?= date('d/m/Y H:i:s') ?> | <?= htmlspecialchars($settings['sistema_nome']) ?></strong></p>
            <p>Este é um documento oficial gerado automaticamente pelo sistema de votação.</p>
        </div>
    </div>

    <script>
        // Adicionar suporte para Ctrl+P
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
