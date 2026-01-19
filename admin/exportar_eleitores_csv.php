<?php
/**
 * Exportação de Eleitores em CSV
 */
require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();

// Buscar eleitores
$eleitores = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="eleitores_' . date('Y-m-d') . '.csv"');

echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');

fputcsv($output, ['RELATÓRIO DE ELEITORES CADASTRADOS']);
fputcsv($output, []);
fputcsv($output, ['Total de Eleitores:', count($eleitores)]);
fputcsv($output, ['Data de Geração:', date('d/m/Y H:i:s')]);
fputcsv($output, []);

fputcsv($output, ['Nº', 'Nome Completo', 'CPF', 'Cargo', 'Perfil', 'Status', 'Data de Cadastro']);

foreach ($eleitores as $index => $eleitor) {
    fputcsv($output, [
        $index + 1,
        $eleitor['nome'],
        formatarCPF($eleitor['cpf']),
        $eleitor['cargo'] ?? '-',
        strtoupper($eleitor['perfil'] ?? 'vereador'),
        $eleitor['ativo'] ? 'ATIVO' : 'BLOQUEADO',
        date('d/m/Y H:i:s', strtotime($eleitor['criado_em']))
    ]);
}

fclose($output);
exit;
