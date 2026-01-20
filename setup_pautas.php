<?php
require_once 'config/database.php';

$sql = "
CREATE TABLE IF NOT EXISTS pautas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT,
    data_sessao DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_data_sessao ON pautas(data_sessao);
";

try {
    $pdo->exec($sql);
    echo "<h1>Sucesso!</h1><p>A tabela 'pautas' foi criada/verificada com sucesso.</p>";
    echo "<p><a href='admin/dashboard.php'>Ir para o Admin</a></p>";
} catch (PDOException $e) {
    echo "<h1>Erro</h1><p>Erro ao criar tabela: " . $e->getMessage() . "</p>";
}
