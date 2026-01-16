<?php
// Script de migração simples: aplica um arquivo SQL localizado em ../sql
require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/../sql/add_perfil_eleitores.sql';
if (!file_exists($sqlFile)) {
    echo "Arquivo SQL não encontrado: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
try {
    $pdo->exec($sql);
    echo "Migração aplicada com sucesso.\n";
} catch (PDOException $e) {
    echo "Erro ao aplicar migração: " . $e->getMessage() . "\n";
    exit(1);
}
