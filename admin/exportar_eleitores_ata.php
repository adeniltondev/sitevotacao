<?php
/**
 * Exportação de Ata de Eleitores
 */
require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();

// Buscar eleitores
$eleitores = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="ata_eleitores_' . date('Y-m-d') . '.txt"');

echo "═══════════════════════════════════════════════════════════════\n";
echo "                   ATA DE ELEITORES CADASTRADOS                 \n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "Total de Eleitores Cadastrados: " . count($eleitores) . "\n";
echo "Data de Geração: " . date('d/m/Y H:i:s') . "\n\n";
echo "───────────────────────────────────────────────────────────────\n";
echo str_pad("Nº", 5, ' ', STR_PAD_RIGHT);
echo str_pad("Nome", 40, ' ', STR_PAD_RIGHT);
echo str_pad("CPF", 18, ' ', STR_PAD_RIGHT);
echo str_pad("Cargo", 25, ' ', STR_PAD_RIGHT);
echo str_pad("Perfil", 15, ' ', STR_PAD_RIGHT);
echo "Status\n";
echo "───────────────────────────────────────────────────────────────\n";

foreach ($eleitores as $index => $eleitor) {
    $numero = str_pad($index + 1, 5, ' ', STR_PAD_RIGHT);
    $nome = str_pad(substr($eleitor['nome'], 0, 39), 40, ' ', STR_PAD_RIGHT);
    $cpf = str_pad(formatarCPF($eleitor['cpf']), 18, ' ', STR_PAD_RIGHT);
    $cargo = str_pad(substr($eleitor['cargo'] ?? '-', 0, 24), 25, ' ', STR_PAD_RIGHT);
    $perfil = str_pad(strtoupper($eleitor['perfil'] ?? 'vereador'), 15, ' ', STR_PAD_RIGHT);
    $status = $eleitor['ativo'] ? 'ATIVO' : 'BLOQUEADO';
    
    echo $numero . $nome . $cpf . $cargo . $perfil . $status . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "ATA GERADA AUTOMATICAMENTE PELO SISTEMA DE VOTAÇÃO\n";
echo "═══════════════════════════════════════════════════════════════\n";
exit;
