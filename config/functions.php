<?php
/**
 * Funções auxiliares do sistema
 */

/**
 * Inicia sessão se ainda não estiver iniciada
 */
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica se o usuário está autenticado como admin
 */
function verificarAdmin() {
    iniciarSessao();
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_usuario'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Verifica se o eleitor está autenticado
 */
function verificarEleitor() {
    iniciarSessao();
    if (!isset($_SESSION['eleitor_id']) || !isset($_SESSION['eleitor_cpf'])) {
        // Determinar o caminho baseado na localização do arquivo
        $basePath = dirname($_SERVER['PHP_SELF']);
        if (strpos($basePath, '/votacao') !== false) {
            header('Location: login.php');
        } else {
            header('Location: votacao/login.php');
        }
        exit;
    }
}

/**
 * Formata CPF (000.000.000-00)
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

/**
 * Valida CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

/**
 * Sanitiza string para segurança
 */
function sanitizar($dados) {
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    return htmlspecialchars(strip_tags(trim($dados)), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna resposta JSON
 */
function respostaJSON($sucesso, $mensagem, $dados = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'dados' => $dados
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Valida e faz upload de imagem
 */
function uploadFoto($file, $pasta = 'uploads') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['erro' => 'Erro no upload da imagem'];
    }
    
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $tamanhoMaximo = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $tiposPermitidos)) {
        return ['erro' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF'];
    }
    
    if ($file['size'] > $tamanhoMaximo) {
        return ['erro' => 'Arquivo muito grande. Máximo 2MB'];
    }
    
    $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid('foto_', true) . '.' . $extensao;
    $caminhoCompleto = $pasta . '/' . $nomeArquivo;
    
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        return ['sucesso' => true, 'arquivo' => $nomeArquivo];
    }
    
    return ['erro' => 'Erro ao salvar arquivo'];
}
