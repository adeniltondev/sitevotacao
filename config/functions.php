<?php
/**
 * Funções auxiliares do sistema
 */

/**
 * Registra log de auditoria em arquivo
 * @param string $acao Descrição da ação
 * @param array $dados Dados adicionais (opcional)
 */
function registrarLog($acao, $dados = [])
{
    iniciarSessao();
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
    $dirLogs = __DIR__ . '/../logs';
    if (!is_dir($dirLogs)) {
        @mkdir($dirLogs, 0755, true);
    }
    @file_put_contents($dirLogs . '/auditoria.log', $linha, FILE_APPEND);
}

/**
 * Gera e armazena um token CSRF na sessão
 * @return string
 */
function gerarCSRFToken()
{
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
function validarCSRFToken()
{
    iniciarSessao();
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
/**
 * Inicia sessão se ainda não estiver iniciada
 */
function iniciarSessao()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica se o usuário está autenticado como admin
 */
function verificarAdmin()
{
    iniciarSessao();
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_usuario'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Verifica se o eleitor está autenticado
 */
function verificarEleitor()
{
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
 * Restringe acesso por perfil de eleitor (ex: vereador, secretario)
 * @param string|array $perfisPermitidos
 */
function protegerPorPerfil($perfisPermitidos)
{
    iniciarSessao();

    // Garante que está logado
    verificarEleitor();

    $perfil = $_SESSION['eleitor_perfil'] ?? null;
    $permitidos = is_array($perfisPermitidos) ? $perfisPermitidos : [$perfisPermitidos];

    if (!$perfil || !in_array($perfil, $permitidos, true)) {
        registrarLog('Acesso negado por perfil', [
            'perfil' => $perfil,
            'permitidos' => $permitidos,
            'rota' => $_SERVER['REQUEST_URI'] ?? null,
        ]);
        header('Location: index.php?erro=' . urlencode('Você não tem permissão para executar esta ação.'));
        exit;
    }
}

/**
 * Formata CPF (000.000.000-00)
 */
function formatarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

/**
 * Valida CPF
 */
function validarCPF($cpf)
{
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
 * Valida e sanitiza dados de entrada
 */
function sanitizar($dados)
{
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    return htmlspecialchars(strip_tags(trim($dados)), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna resposta JSON
 */
function respostaJSON($sucesso, $mensagem, $dados = null)
{
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
function uploadFoto($file, $pasta = 'uploads')
{
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

/**
 * Define mensagem flash na sessão
 * @param string $type Tipo: success, error, warning, info
 * @param string $message Mensagem
 */
function setFlashMessage($type, $message)
{
    iniciarSessao();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Recupera e remove mensagem flash
 * @return array|null
 */
function getFlashMessage()
{
    iniciarSessao();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Valida dados usando a classe Validator
 * @param array $rules Regras de validação
 * @param array $data Dados a validar
 * @return Validator
 */
function validateInput($rules, $data)
{
    require_once __DIR__ . '/Validator.php';
    $validator = new Validator($data);

    foreach ($rules as $field => $fieldRules) {
        foreach ($fieldRules as $rule => $params) {
            if (is_numeric($rule)) {
                // Regra sem parâmetros (ex: 'required')
                $rule = $params;
                $params = [];
            }

            if (!is_array($params)) {
                $params = [$params];
            }

            // Chamar método de validação
            call_user_func_array([$validator, $rule], array_merge([$field], $params));
        }
    }

    return $validator;
}

/**
 * Trata erro e exibe mensagem amigável
 * @param string $error Mensagem de erro
 * @param string $redirectTo URL para redirecionar (opcional)
 */
function handleError($error, $redirectTo = null)
{
    registrarLog('erro', ['mensagem' => $error]);

    if ($redirectTo) {
        setFlashMessage('error', $error);
        header("Location: $redirectTo");
        exit;
    }

    // Exibir erro inline
    echo "<div class='error-message'>{$error}</div>";
}

/**
 * Redireciona com mensagem de sucesso
 * @param string $message Mensagem
 * @param string $redirectTo URL
 */
function redirectWithSuccess($message, $redirectTo)
{
    setFlashMessage('success', $message);
    header("Location: $redirectTo");
    exit;
}

/**
 * Redireciona com mensagem de erro
 * @param string $message Mensagem
 * @param string $redirectTo URL
 */
function redirectWithError($message, $redirectTo)
{
    setFlashMessage('error', $message);
    header("Location: $redirectTo");
    exit;
}

