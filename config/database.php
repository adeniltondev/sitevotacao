<?php
/**
 * Configuração do Banco de Dados
 * Ajuste as credenciais conforme seu ambiente Hostinger
 */

 define('DB_HOST', 'localhost');
 define('DB_NAME', 'alunofaculdadepr_tess');
 define('DB_USER', 'alunofaculdadepr_dasdsads');
 define('DB_PASS', 'p7xZrhk51#Wd*BXe');
 define('DB_CHARSET', 'utf8mb4');

/**
 * Conexão PDO com tratamento de erros
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
