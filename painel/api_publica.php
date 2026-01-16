<?php
// API pública de resultados em tempo real
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$votacao = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();
if (!$votacao) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Nenhuma votação aberta',
        'dados' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Totais
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'");
$stmt->execute([$votacao['id']]);
$total_sim = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'");
$stmt->execute([$votacao['id']]);
$total_nao = $stmt->fetch()['total'];

$total_geral = $total_sim + $total_nao;
$percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
$percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;

// Votos detalhados (sem dados pessoais)
$stmt = $pdo->prepare("SELECT voto, criado_em FROM votos WHERE votacao_id = ? ORDER BY criado_em DESC");
$stmt->execute([$votacao['id']]);
$votos = $stmt->fetchAll();

// Resposta
$resposta = [
    'sucesso' => true,
    'mensagem' => 'Resultados em tempo real',
    'dados' => [
        'votacao' => [
            'id' => $votacao['id'],
            'titulo' => $votacao['titulo'],
            'descricao' => $votacao['descricao'],
            'criada_em' => $votacao['criada_em'],
        ],
        'total_sim' => $total_sim,
        'total_nao' => $total_nao,
        'total_geral' => $total_geral,
        'percentual_sim' => $percentual_sim,
        'percentual_nao' => $percentual_nao,
        'votos' => $votos
    ]
];
echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
