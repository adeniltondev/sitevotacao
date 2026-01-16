<?php
/**
 * Processamento do Voto
 */
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$votacao_id = intval($_POST['votacao_id'] ?? 0);
$cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
$voto = $_POST['voto'] ?? '';

// Validações
if (empty($votacao_id) || empty($cpf) || empty($voto)) {
    header('Location: index.php?erro=' . urlencode('Preencha todos os campos obrigatórios'));
    exit;
}

if (!validarCPF($cpf)) {
    header('Location: index.php?erro=' . urlencode('CPF inválido'));
    exit;
}

// Buscar eleitor cadastrado pelo CPF
$stmt = $pdo->prepare("SELECT * FROM eleitores WHERE cpf = ?");
$stmt->execute([$cpf]);
$eleitor = $stmt->fetch();

if (!$eleitor) {
    header('Location: index.php?erro=' . urlencode('CPF não cadastrado. Entre em contato com o administrador.'));
    exit;
}

$nome = $eleitor['nome'];
$cargo = $eleitor['cargo'];
$foto = $eleitor['foto'];

if (!in_array($voto, ['sim', 'nao'])) {
    header('Location: index.php?erro=' . urlencode('Opção de voto inválida'));
    exit;
}

// Verificar se a votação está aberta
$stmt = $pdo->prepare("SELECT * FROM votacoes WHERE id = ? AND status = 'aberta'");
$stmt->execute([$votacao_id]);
$votacao = $stmt->fetch();

if (!$votacao) {
    header('Location: index.php?erro=' . urlencode('Votação não encontrada ou encerrada'));
    exit;
}

// Verificar se já votou (por CPF)
$stmt = $pdo->prepare("SELECT id FROM votos WHERE votacao_id = ? AND cpf = ?");
$stmt->execute([$votacao_id, $cpf]);
if ($stmt->fetch()) {
    header('Location: index.php?erro=' . urlencode('Você já votou nesta votação'));
    exit;
}

// Obter IP do usuário
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

// Inserir voto
try {
    $stmt = $pdo->prepare("
        INSERT INTO votos (votacao_id, nome, cpf, cargo, foto, voto, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $votacao_id,
        $nome,
        $cpf,
        $cargo ?: null,
        $foto,
        $voto,
        $ip_address
    ]);
    
    header('Location: index.php?sucesso=1');
    exit;
} catch (PDOException $e) {
    // Se for erro de duplicata (mesmo com verificação anterior, pode acontecer em concorrência)
    if ($e->getCode() == 23000) {
        header('Location: index.php?erro=' . urlencode('Você já votou nesta votação'));
    } else {
        header('Location: index.php?erro=' . urlencode('Erro ao registrar voto. Tente novamente.'));
    }
    exit;
}
