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

// Verificar se eleitor está logado
verificarEleitor();

$votacao_id = intval($_POST['votacao_id'] ?? 0);
$voto = $_POST['voto'] ?? '';

// Validações
if (empty($votacao_id) || empty($voto)) {
    header('Location: index.php?erro=' . urlencode('Preencha todos os campos obrigatórios'));
    exit;
}

// Usar dados da sessão do eleitor logado
$cpf = preg_replace('/[^0-9]/', '', $_SESSION['eleitor_cpf']);
$nome = $_SESSION['eleitor_nome'];
$cargo = $_SESSION['eleitor_cargo'] ?? null;
$foto = $_SESSION['eleitor_foto'] ?? null;

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
