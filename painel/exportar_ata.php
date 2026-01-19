<?php
/**
 * Exportação de Ata de Votação - Template Formal e Completo
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

// Buscar votos
$stmt = $pdo->prepare('SELECT * FROM votos WHERE votacao_id = ? ORDER BY criado_em ASC');
$stmt->execute([$votacao_id]);
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

// Determinar resultado
$resultado = 'INDEFINIDO';
if ($total_geral > 0) {
    if ($total_nao == 0) {
        $resultado = 'APROVADO POR UNANIMIDADE';
    } elseif ($percentual_sim > 50) {
        $resultado = 'APROVADO';
    } elseif ($percentual_sim == 50 && $total_geral % 2 == 0) {
        $resultado = 'EMPATE';
    } else {
        $resultado = 'REJEITADO';
    }
}

// Headers
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="ata_votacao_' . $votacao_id . '_' . date('Y-m-d') . '.txt"');

// Ata formatada
echo "═══════════════════════════════════════════════════════════════\n";
echo "                        ATA DE VOTAÇÃO                         \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "IDENTIFICAÇÃO DA VOTAÇÃO\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "Número da Votação: #" . $votacao_id . "\n";
echo "Título: " . $votacao['titulo'] . "\n";

if ($votacao['descricao']) {
    echo "Descrição: " . $votacao['descricao'] . "\n";
}

echo "Status: " . strtoupper($votacao['status']) . "\n";
echo "Data de Criação: " . date('d/m/Y', strtotime($votacao['criada_em'])) . "\n";
echo "Hora de Criação: " . date('H:i:s', strtotime($votacao['criada_em'])) . "\n\n";

echo "RESUMO DA VOTAÇÃO\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "Total de Votos SIM:          " . str_pad($total_sim, 5, ' ', STR_PAD_LEFT) . " (" . str_pad(number_format($percentual_sim, 1), 5, ' ', STR_PAD_LEFT) . "%)\n";
echo "Total de Votos NÃO:          " . str_pad($total_nao, 5, ' ', STR_PAD_LEFT) . " (" . str_pad(number_format($percentual_nao, 1), 5, ' ', STR_PAD_LEFT) . "%)\n";
echo "Total de Votos Registrados:  " . str_pad($total_geral, 5, ' ', STR_PAD_LEFT) . "\n";
echo "Resultado:                   " . $resultado . "\n\n";

echo "DETALHAMENTO DOS VOTOS\n";
echo "───────────────────────────────────────────────────────────────\n";
echo str_pad("Nº", 5, ' ', STR_PAD_RIGHT);
echo str_pad("Nome", 35, ' ', STR_PAD_RIGHT);
echo str_pad("CPF", 18, ' ', STR_PAD_RIGHT);
echo str_pad("Cargo", 20, ' ', STR_PAD_RIGHT);
echo str_pad("Voto", 8, ' ', STR_PAD_RIGHT);
echo "Data/Hora\n";
echo "───────────────────────────────────────────────────────────────\n";

foreach ($votos as $index => $voto) {
    $numero = str_pad($index + 1, 5, ' ', STR_PAD_RIGHT);
    $nome = str_pad(substr($voto['nome'], 0, 34), 35, ' ', STR_PAD_RIGHT);
    $cpf = str_pad(formatarCPF($voto['cpf']), 18, ' ', STR_PAD_RIGHT);
    $cargo = str_pad(substr($voto['cargo'] ?? '-', 0, 19), 20, ' ', STR_PAD_RIGHT);
    $voto_valor = str_pad(strtoupper($voto['voto']), 8, ' ', STR_PAD_RIGHT);
    $data_hora = date('d/m/Y H:i:s', strtotime($voto['criado_em']));
    
    echo $numero . $nome . $cpf . $cargo . $voto_valor . $data_hora . "\n";
}

echo "\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "ORDEM DE VOTAÇÃO\n";
echo "───────────────────────────────────────────────────────────────\n";

$ordem = 1;
foreach ($votos as $voto) {
    echo $ordem . ". " . $voto['nome'] . " (" . formatarCPF($voto['cpf']) . ") - Voto: " . strtoupper($voto['voto']) . " - " . date('d/m/Y H:i:s', strtotime($voto['criado_em'])) . "\n";
    $ordem++;
}

echo "\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "RESULTADO FINAL\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "Após a apuração dos votos, a presente votação foi " . strtolower($resultado) . ".\n";
echo "\n";
echo "Total de votos favoráveis (SIM): " . $total_sim . " (" . $percentual_sim . "%)\n";
echo "Total de votos contrários (NÃO): " . $total_nao . " (" . $percentual_nao . "%)\n";
echo "Total de votos registrados:      " . $total_geral . "\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "ATA GERADA AUTOMATICAMENTE PELO SISTEMA DE VOTAÇÃO\n";
echo "Data/Hora de Geração: " . date('d/m/Y H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

exit;
