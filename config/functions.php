<?php
/**
 * Funções auxiliares do sistema
 */

/**
 * Registra log de auditoria em arquivo
 * @param string $acao Descrição da ação
 * @param array $dados Dados adicionais (opcional)
 */
function registrarLog($acao, $dados = []) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $usuario = $_SESSION['admin_usuario'] ?? $_SESSION['eleitor_cpf'] ?? 'desconhecido';
    $data = date('Y-m-d H:i:s');
    $registro = [
        'data' => $data,
        'ip' => $ip,
        'usuario' => $usuario,
        'acao' => $acao,
        'dados' => $dados
    ];
    $linha = json_encode($registro, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/auditoria.log', $linha, FILE_APPEND);
}

/**
 * Gera e armazena um token CSRF na sessão
 * @return string
 */
function gerarCSRFToken() {
    iniciarSessao();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado via POST
 * @return bool
 */
function validarCSRFToken() {
    iniciarSessao();
        /**
         * Verifica se o eleitor logado possui perfil específico
         * @param string|array $perfis Perfil ou array de perfis permitidos
         * @return bool
         */
        function eleitorTemPerfil($perfis) {
            iniciarSessao();
            if (!isset($_SESSION['eleitor_id']) || !isset($_SESSION['eleitor_cpf'])) {
                return false;
            }
            $perfil = $_SESSION['eleitor_perfil'] ?? null;
            if (is_array($perfis)) {
                return in_array($perfil, $perfis);
            }
            return $perfil === $perfis;
        }

        /**
         * Protege rota para perfis específicos de eleitor
         * @param string|array $perfis Perfil ou array de perfis permitidos
         */
        function protegerPorPerfil($perfis) {
            if (!eleitorTemPerfil($perfis)) {
                header('HTTP/1.1 403 Forbidden');
                echo '<h2>Acesso negado: perfil insuficiente.</h2>';
                exit;
            }
        }

        /**
         * Verifica se o admin está autenticado e, opcionalmente, se é admin master
         */
        function verificarAdminPerfil($perfil = 'admin') {
            iniciarSessao();
            if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_usuario'])) {
                header('Location: /admin/login.php');
                exit;
            }
            // Futuro: checar perfil do admin se necessário
        }

    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
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
