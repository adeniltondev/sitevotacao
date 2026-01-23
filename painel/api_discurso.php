<?php
header('Content-Type: application/json');
// Desativar exibição de erros no output para não quebrar o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';

try {
    // Tenta query completa primeiro
    try {
        $stmt = $pdo->query("
            SELECT d.*, e.nome, e.foto, e.cargo
            FROM controle_discurso d 
            LEFT JOIN eleitores e ON d.eleitor_id = e.id 
            WHERE d.id = 1
        ");
        $discurso = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($discurso) {
            $discurso['partido'] = null;
            $discurso['logo_partido'] = null;
        }
    } catch (PDOException $e) {
        // Se falhar, retorna erro
        echo json_encode(['sucesso' => false, 'status' => 'erro', 'erro' => $e->getMessage()]);
        exit;
    }

    if ($discurso) {
        $response = [
            'sucesso' => true,
            'status' => $discurso['status'],
            'nome' => $discurso['nome'],
            'foto' => $discurso['foto'],
            'partido' => $discurso['partido'],
            'logo_partido' => $discurso['logo_partido'],
            'cargo' => $discurso['cargo'],
            'tempo_restante' => 0
        ];

        if ($discurso['status'] === 'ativo') {
            $inicio = strtotime($discurso['inicio']);
            $agora = time();
            $decorrido = $agora - $inicio;
            $restante = max(0, $discurso['duracao_segundos'] - $decorrido);
            $response['tempo_restante'] = $restante;
            
        } elseif ($discurso['status'] === 'pausado') {
            $response['tempo_restante'] = intval($discurso['tempo_restante_pausa']);
        }

        echo json_encode($response);
    } else {
        // Tabela existe mas registro 1 não encontrado
        echo json_encode(['sucesso' => false, 'status' => 'encerrado', 'mensagem' => 'Sistema aguardando']);
    }

} catch (PDOException $e) {
    // Captura erro de tabela inexistente ou outros erros de banco
    // Retorna JSON válido em vez de erro 500 para não quebrar o JS
    echo json_encode([
        'sucesso' => false, 
        'status' => 'erro', 
        'erro' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false, 
        'status' => 'erro', 
        'erro' => $e->getMessage()
    ]);
}
