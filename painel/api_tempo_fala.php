<?php
/**
 * API para retornar o tempo de fala atual
 * Retorna JSON com dados do orador atual, tempo restante, partido, etc.
 */
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Exemplo: buscar o tempo de fala do orador atual na tabela tempo_fala
// Ajuste conforme sua estrutura real
$stmt = $pdo->query("SELECT tf.*, e.nome, e.cargo, e.foto, e.partido, e.logo_partido FROM tempo_fala tf LEFT JOIN eleitores e ON tf.eleitor_id = e.id WHERE tf.status = 'ativo' ORDER BY tf.iniciado_em DESC LIMIT 1");
$tempo_fala = $stmt->fetch();

if (!$tempo_fala) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Nenhum tempo de fala ativo.'
    ]);
    exit;
}

// Calcular tempo restante
$duracao = intval($tempo_fala['duracao'] ?? 0); // em segundos
$iniciado_em = strtotime($tempo_fala['iniciado_em']);
$agora = time();
$passado = $agora - $iniciado_em;
$restante = max(0, $duracao - $passado);

// Montar resposta
$resposta = [
    'sucesso' => true,
    'nome' => $tempo_fala['nome'],
    'cargo' => $tempo_fala['cargo'],
    'foto' => $tempo_fala['foto'],
    'partido' => $tempo_fala['partido'] ?? '',
    'logo_partido' => $tempo_fala['logo_partido'] ?? '',
    'tempo_restante' => $restante,
    'tempo_total' => $duracao,
    'status' => $tempo_fala['status'],
    'iniciado_em' => $tempo_fala['iniciado_em'],
];

echo json_encode($resposta);