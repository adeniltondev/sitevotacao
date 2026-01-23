<?php
/**
 * Handler Global de Erros
 * Captura e trata exceções e erros do sistema
 */

class ErrorHandler
{
    private static $debugMode = false;
    private static $logFile = __DIR__ . '/../logs/errors.log';

    /**
     * Inicializa o handler de erros
     */
    public static function init($debugMode = false)
    {
        self::$debugMode = $debugMode;

        // Configurar handler de exceções
        set_exception_handler([self::class, 'handleException']);

        // Configurar handler de erros
        set_error_handler([self::class, 'handleError']);

        // Configurar handler de shutdown (erros fatais)
        register_shutdown_function([self::class, 'handleShutdown']);

        // Criar diretório de logs se não existir
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    /**
     * Trata exceções não capturadas
     */
    public static function handleException($exception)
    {
        self::logError([
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        self::displayError('Ocorreu um erro inesperado. Por favor, tente novamente.');
    }

    /**
     * Trata erros PHP
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Não processar erros suprimidos com @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorTypes = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        $type = $errorTypes[$errno] ?? 'Unknown Error';

        self::logError([
            'type' => $type,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]);

        // Não exibir erros menores em produção
        if (!self::$debugMode && in_array($errno, [E_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT])) {
            return true;
        }

        return false;
    }

    /**
     * Trata erros fatais
     */
    public static function handleShutdown()
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::logError([
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            self::displayError('Erro fatal no sistema. Por favor, contate o administrador.');
        }
    }

    /**
     * Registra erro em arquivo de log
     */
    private static function logError($error)
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $url = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';

        $logEntry = [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'url' => $url,
            'error' => $error
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

        @file_put_contents(self::$logFile, $logLine, FILE_APPEND);
    }

    /**
     * Exibe erro para o usuário
     */
    private static function displayError($message)
    {
        // Se for requisição AJAX, retornar JSON
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $message
            ]);
            exit;
        }

        // Exibir página de erro
        http_response_code(500);

        if (self::$debugMode) {
            // Modo debug: mostrar detalhes
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro</title>";
            echo "<style>body{font-family:sans-serif;padding:20px;background:#f5f5f5;}";
            echo ".error{background:#fff;border-left:4px solid #ef4444;padding:20px;border-radius:8px;}</style>";
            echo "</head><body><div class='error'><h1>Erro</h1><p>{$message}</p></div></body></html>";
        } else {
            // Produção: mensagem genérica
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro</title>";
            echo "<style>body{font-family:sans-serif;padding:40px;text-align:center;background:#f5f5f5;}";
            echo ".error{background:#fff;max-width:500px;margin:0 auto;padding:40px;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}";
            echo "h1{color:#ef4444;margin-bottom:16px;}p{color:#6b7280;}</style>";
            echo "</head><body><div class='error'><h1>⚠️ Ops!</h1><p>{$message}</p>";
            echo "<p style='margin-top:20px;'><a href='javascript:history.back()' style='color:#3b82f6;text-decoration:none;'>← Voltar</a></p>";
            echo "</div></body></html>";
        }

        exit;
    }

    /**
     * Valida e sanitiza dados de entrada
     */
    public static function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }

        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
