<?php
// Exportação de ata da votação (texto simples)
require_once '../config/database.php';

$votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;
if (!$votacao_id) {
    die('ID da votação não informado.');
}

$stmt = $pdo->prepare('SELECT * FROM votacoes WHERE id = ?');
$stmt->execute([$votacao_id]);
$votacao = $stmt->fetch();
if (!$votacao) die('Votação não encontrada.');

$stmt = $pdo->prepare('SELECT * FROM votos WHERE votacao_id = ? ORDER BY criado_em ASC');
$stmt->execute([$votacao_id]);
$votos = $stmt->fetchAll();

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="ata_votacao_' . $votacao_id . '.txt"');

echo "ATA DA VOTAÇÃO\n";
echo "-----------------------------\n";
echo "Título: " . $votacao['titulo'] . "\n";
echo "Descrição: " . $votacao['descricao'] . "\n";
echo "Data de Criação: " . date('d/m/Y H:i', strtotime($votacao['criada_em'])) . "\n";
echo "\nVotos:\n";
foreach ($votos as $voto) {
    echo "- " . $voto['nome'] . " (" . $voto['cpf'] . ") - " . strtoupper($voto['voto']) . " - " . date('d/m/Y H:i', strtotime($voto['criado_em'])) . "\n";
}
echo "\nTotal de votos: " . count($votos) . "\n";
exit;
