<?php
require_once 'config/database.php';

function executarSQL($pdo, $sql, $mensagem) {
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>Sucesso: $mensagem</p>";
    } catch (PDOException $e) {
        // Ignora erro se coluna já existe (código 42S21 ou mensagem específica)
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
             echo "<p style='color: orange;'>Aviso: $mensagem (Já existia)</p>";
        } else {
             echo "<p style='color: red;'>Erro: $mensagem - " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h1>Atualização do Banco de Dados</h1>";

// 1. Criar tabela pautas
$sqlPautas = "
CREATE TABLE IF NOT EXISTS pautas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT,
    data_sessao DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
executarSQL($pdo, $sqlPautas, "Criar tabela 'pautas'");

$sqlIndexPautas = "CREATE INDEX idx_data_sessao ON pautas(data_sessao);";
executarSQL($pdo, $sqlIndexPautas, "Criar índice na tabela 'pautas'");

// 2. Adicionar coluna logo_partido em eleitores
$sqlLogo = "ALTER TABLE eleitores ADD COLUMN logo_partido VARCHAR(255) NULL AFTER foto;";
executarSQL($pdo, $sqlLogo, "Adicionar coluna 'logo_partido' em 'eleitores'");

echo "<hr><p><a href='admin/dashboard.php'>Ir para o Admin</a></p>";
