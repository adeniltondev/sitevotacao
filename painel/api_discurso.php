<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT d.*, e.nome, e.foto, e.partido, e.logo_partido, e.cargo
        FROM controle_discurso d 
        LEFT JOIN eleitores e ON d.eleitor_id = e.id 
        WHERE d.id = 1
    ");
    $discurso = $stmt->fetch(PDO::FETCH_ASSOC);

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
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum registro encontrado']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
