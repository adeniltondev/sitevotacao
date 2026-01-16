<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votar - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('darkMode') === '1' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        .dark body { background: #18181b !important; color: #f3f4f6 !important; }
        .dark .bg-white { background: #23232a !important; color: #f3f4f6 !important; }
        .dark .text-gray-800 { color: #f3f4f6 !important; }
        .dark .text-gray-600 { color: #d1d5db !important; }
        .dark .bg-gray-50 { background: #23232a !important; }
        .dark .bg-green-100 { background: #14532d !important; color: #bbf7d0 !important; }
        .dark .bg-red-100 { background: #7f1d1d !important; color: #fecaca !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <button onclick="alternarModoEscuro()" class="fixed top-4 right-4 z-50 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded shadow hover:bg-gray-700 dark:hover:bg-gray-300 transition">
        <span id="icone-modo">🌙</span> <span id="texto-modo">Modo Escuro</span>
    </button>
    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        }
        window.onload = function() {
            const dark = document.documentElement.classList.contains('dark');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
            document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
        };
    </script>
    <!-- Existing content of the page goes here -->
    <!-- For example, the voting form or other elements -->
    
    <!-- End of existing content -->
    
    
    
<?php
/**
 * Processamento do Voto
 */

require_once '../config/database.php';
require_once '../config/functions.php';

// Permitir votar apenas para perfil vereador
protegerPorPerfil('vereador');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validação CSRF
if (!validarCSRFToken()) {
    registrarLog('Voto falhou', ['motivo' => 'CSRF token inválido']);
    header('Location: index.php?erro=' . urlencode('Token de segurança inválido. Recarregue a página.'));
    exit;
}

// Verificar se eleitor está logado
verificarEleitor();

$votacao_id = intval($_POST['votacao_id'] ?? 0);
$voto = $_POST['voto'] ?? '';

// Validações
if (empty($votacao_id) || empty($voto)) {
    registrarLog('Voto falhou', ['motivo' => 'Campos obrigatórios vazios', 'votacao_id' => $votacao_id]);
    header('Location: index.php?erro=' . urlencode('Preencha todos os campos obrigatórios'));
    exit;
}


// Usar dados da sessão do eleitor logado
$cpf = preg_replace('/[^0-9]/', '', $_SESSION['eleitor_cpf']);
$nome = $_SESSION['eleitor_nome'];
$cargo = $_SESSION['eleitor_cargo'] ?? null;
$foto = $_SESSION['eleitor_foto'] ?? null;

// Verificar se eleitor está ativo
$stmt = $pdo->prepare("SELECT ativo FROM eleitores WHERE cpf = ?");
$stmt->execute([$cpf]);
$eleitor = $stmt->fetch();
if (!$eleitor || !$eleitor['ativo']) {
    registrarLog('Voto bloqueado', ['cpf' => $cpf, 'motivo' => 'Eleitor inativo/bloqueado']);
    header('Location: index.php?erro=' . urlencode('Seu acesso ao voto está bloqueado. Procure a administração.'));
    exit;
}

if (!in_array($voto, ['sim', 'nao'])) {
    registrarLog('Voto falhou', ['motivo' => 'Opção de voto inválida', 'voto' => $voto]);
    header('Location: index.php?erro=' . urlencode('Opção de voto inválida'));
    exit;
}

// Verificar se a votação está aberta
$stmt = $pdo->prepare("SELECT * FROM votacoes WHERE id = ? AND status = 'aberta'");
$stmt->execute([$votacao_id]);
$votacao = $stmt->fetch();

if (!$votacao) {
    registrarLog('Voto falhou', ['motivo' => 'Votação não encontrada ou encerrada', 'votacao_id' => $votacao_id]);
    header('Location: index.php?erro=' . urlencode('Votação não encontrada ou encerrada'));
    exit;
}

// Verificar se já votou (por CPF)
$stmt = $pdo->prepare("SELECT id FROM votos WHERE votacao_id = ? AND cpf = ?");
$stmt->execute([$votacao_id, $cpf]);
if ($stmt->fetch()) {
    registrarLog('Voto duplicado bloqueado', ['votacao_id' => $votacao_id, 'cpf' => $cpf]);
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
    registrarLog('Voto registrado', ['votacao_id' => $votacao_id, 'cpf' => $cpf, 'voto' => $voto]);
    header('Location: index.php?sucesso=1');
    exit;
} catch (PDOException $e) {
    // Se for erro de duplicata (mesmo com verificação anterior, pode acontecer em concorrência)
    if ($e->getCode() == 23000) {
        registrarLog('Voto duplicado bloqueado', ['votacao_id' => $votacao_id, 'cpf' => $cpf, 'erro' => $e->getMessage()]);
        header('Location: index.php?erro=' . urlencode('Você já votou nesta votação'));
    } else {
        registrarLog('Erro ao registrar voto', ['erro' => $e->getMessage()]);
        header('Location: index.php?erro=' . urlencode('Erro ao registrar voto. Tente novamente.'));
    }
    exit;
}
