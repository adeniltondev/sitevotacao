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

// 3. Criar tabela controle_discurso
$sqlDiscurso = "
CREATE TABLE IF NOT EXISTS controle_discurso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleitor_id INT NULL,
    inicio DATETIME NULL,
    duracao_segundos INT DEFAULT 0,
    status ENUM('ativo', 'pausado', 'encerrado') DEFAULT 'encerrado',
    tempo_restante_pausa INT DEFAULT 0,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (eleitor_id) REFERENCES eleitores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
executarSQL($pdo, $sqlDiscurso, "Criar tabela 'controle_discurso'");

// Inserir registro inicial se não existir
$sqlInitDiscurso = "INSERT INTO controle_discurso (id, status) SELECT 1, 'encerrado' WHERE NOT EXISTS (SELECT 1 FROM controle_discurso WHERE id = 1);";
executarSQL($pdo, $sqlInitDiscurso, "Inicializar tabela 'controle_discurso'");

echo "<hr><p><a href='admin/dashboard.php'>Ir para o Admin</a></p>";
