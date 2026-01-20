<?php
require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/../sql/pautas.sql';
if (!file_exists($sqlFile)) {
    echo "Arquivo SQL não encontrado: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
try {
    $pdo->exec($sql);
    echo "Tabela 'pautas' criada com sucesso.\n";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage() . "\n";
    exit(1);
}
