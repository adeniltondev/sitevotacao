<?php
/**
 * API para retornar resultados em JSON
 * Usado pelo painel de resultados para atualização em tempo real
 */
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$votacao_id = intval($_GET['votacao_id'] ?? 0);

if (!$votacao_id) {
    respostaJSON(false, 'ID da votação não informado');
}

// Verificar se a votação existe e está aberta
$stmt = $pdo->prepare("SELECT * FROM votacoes WHERE id = ? AND status = 'aberta'");
$stmt->execute([$votacao_id]);
$votacao = $stmt->fetch();

if (!$votacao) {
    respostaJSON(false, 'Votação não encontrada ou encerrada');
}

// Buscar resultados
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'");
$stmt->execute([$votacao_id]);
$total_sim = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'");
$stmt->execute([$votacao_id]);
$total_nao = $stmt->fetch()['total'];

$total_geral = $total_sim + $total_nao;
$percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
$percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;

respostaJSON(true, 'Resultados obtidos com sucesso', [
    'total_sim' => $total_sim,
    'total_nao' => $total_nao,
    'total_geral' => $total_geral,
    'percentual_sim' => $percentual_sim,
    'percentual_nao' => $percentual_nao
]);
