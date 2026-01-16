<?php
/**
 * Script auxiliar para gerar hash de senha
 * Execute este arquivo uma vez para gerar o hash, depois delete-o
 * 
 * Uso: Acesse este arquivo no navegador ou execute: php gerar_hash_senha.php
 */

$senha = 'admin123'; // Altere aqui se necessário

$hash = password_hash($senha, PASSWORD_BCRYPT);

echo "Senha: " . $senha . "\n";
echo "Hash: " . $hash . "\n";
echo "\n";
echo "Para usar no SQL:\n";
echo "UPDATE administradores SET senha = '" . $hash . "' WHERE usuario = 'admin';\n";
