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

// Requer login
verificarEleitor();

// Permitir votar apenas para perfil vereador
protegerPorPerfil('vereador');

// Validação CSRF
if (!validarCSRFToken()) {
    registrarLog('Voto falhou', ['motivo' => 'CSRF token inválido']);
    header('Location: index.php?erro=' . urlencode('Token de segurança inválido. Recarregue a página.'));
    exit;
}

$votacao_id = intval($_POST['votacao_id'] ?? 0);
$voto = $_POST['voto'] ?? '';

if (empty($votacao_id) || empty($voto)) {
    registrarLog('Voto falhou', ['motivo' => 'Campos obrigatórios vazios', 'votacao_id' => $votacao_id]);
    header('Location: index.php?erro=' . urlencode('Preencha todos os campos obrigatórios'));
    exit;
}

if (!in_array($voto, ['sim', 'nao'], true)) {
    registrarLog('Voto falhou', ['motivo' => 'Opção de voto inválida', 'voto' => $voto]);
    header('Location: index.php?erro=' . urlencode('Opção de voto inválida'));
    exit;
}

// Usar dados da sessão do eleitor logado
$cpf = preg_replace('/[^0-9]/', '', $_SESSION['eleitor_cpf'] ?? '');
$nome = $_SESSION['eleitor_nome'] ?? '';
$cargo = $_SESSION['eleitor_cargo'] ?? null;
$foto = $_SESSION['eleitor_foto'] ?? null;

if ($cpf === '' || $nome === '') {
    registrarLog('Voto falhou', ['motivo' => 'Sessão incompleta']);
    header('Location: logout.php');
    exit;
}

// Verificar se eleitor está ativo
$stmt = $pdo->prepare("SELECT ativo FROM eleitores WHERE cpf = ? LIMIT 1");
$stmt->execute([$cpf]);
$eleitor = $stmt->fetch();
if (!$eleitor || empty($eleitor['ativo'])) {
    registrarLog('Voto bloqueado', ['cpf' => $cpf, 'motivo' => 'Eleitor inativo/bloqueado']);
    header('Location: index.php?erro=' . urlencode('Seu acesso ao voto está bloqueado. Procure a administração.'));
    exit;
}

// Verificar se a votação está aberta e obter tipo
$stmt = $pdo->prepare("SELECT id, tipo_votacao FROM votacoes WHERE id = ? AND status = 'aberta'");
$stmt->execute([$votacao_id]);
$votacao = $stmt->fetch();

if (!$votacao) {
    registrarLog('Voto falhou', ['motivo' => 'Votação não encontrada ou encerrada', 'votacao_id' => $votacao_id]);
    header('Location: index.php?erro=' . urlencode('Votação não encontrada ou encerrada'));
    exit;
}

$tipo_votacao = $votacao['tipo_votacao'] ?? 'nominal';

// Verificar se já votou
// Para votação anônima, verificamos por eleitor_id (sessão)
// Para nominal, verificamos por CPF
if ($tipo_votacao === 'anonima') {
    $stmt = $pdo->prepare("SELECT id FROM votos WHERE votacao_id = ? AND eleitor_id = ? LIMIT 1");
    $stmt->execute([$votacao_id, $_SESSION['eleitor_id']]);
} else {
    $stmt = $pdo->prepare("SELECT id FROM votos WHERE votacao_id = ? AND cpf = ? LIMIT 1");
    $stmt->execute([$votacao_id, $cpf]);
}

if ($stmt->fetch()) {
    registrarLog('Voto duplicado bloqueado', ['votacao_id' => $votacao_id, 'cpf' => $cpf]);
    header('Location: index.php?erro=' . urlencode('Você já votou nesta votação'));
    exit;
}

// Obter IP do usuário
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

try {
    // Se votação anônima, salvar apenas dados essenciais
    if ($tipo_votacao === 'anonima') {
        $stmt = $pdo->prepare("
            INSERT INTO votos (votacao_id, eleitor_id, voto, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $votacao_id,
            $_SESSION['eleitor_id'],
            $voto,
            $ip_address
        ]);
    } else {
        // Votação nominal: salvar todos os dados
        $stmt = $pdo->prepare("
            INSERT INTO votos (votacao_id, eleitor_id, nome, cpf, cargo, foto, voto, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $votacao_id,
            $_SESSION['eleitor_id'],
            $nome,
            $cpf,
            $cargo ?: null,
            $foto,
            $voto,
            $ip_address
        ]);
    }

    $tipo_msg = $tipo_votacao === 'anonima' ? 'anônimo' : 'nominal';
    registrarLog('Voto registrado', ['votacao_id' => $votacao_id, 'tipo' => $tipo_votacao, 'voto' => $voto]);
    header('Location: index.php?sucesso=1');
    exit;
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        registrarLog('Voto duplicado bloqueado', ['votacao_id' => $votacao_id, 'cpf' => $cpf, 'erro' => $e->getMessage()]);
        header('Location: index.php?erro=' . urlencode('Você já votou nesta votação'));
    } else {
        registrarLog('Erro ao registrar voto', ['erro' => $e->getMessage()]);
        header('Location: index.php?erro=' . urlencode('Erro ao registrar voto. Tente novamente.'));
    }
    exit;
}
