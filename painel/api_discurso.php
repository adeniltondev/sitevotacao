<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';

try {
    // Buscar discurso
    $stmt = $pdo->query("
        SELECT d.*, e.nome, e.foto, e.cargo
        FROM controle_discurso d 
        LEFT JOIN eleitores e ON d.eleitor_id = e.id 
        WHERE d.id = 1
    ");
    $discurso = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($discurso) {
        $response = [
            'sucesso' => true,
            'status' => $discurso['status'],
            'nome' => $discurso['nome'] ?? 'Nome não disponível',
            'foto' => $discurso['foto'] ?? null,
            'partido' => $discurso['partido'] ?? null,
            'logo_partido' => $discurso['logo_partido'] ?? null,
            'cargo' => $discurso['cargo'] ?? 'Cargo não disponível',
            'tempo_restante' => 0
        ];

        // Calcular tempo restante se estiver ativo
        if ($discurso['status'] === 'ativo') {
            if (!empty($discurso['inicio']) && !empty($discurso['duracao_segundos'])) {
                $inicio = strtotime($discurso['inicio']);
                $agora = time();
                $decorrido = $agora - $inicio;
                $duracao = intval($discurso['duracao_segundos']);
                $restante = $duracao - $decorrido;
                
                // Se ainda tem tempo
                if ($restante > 0) {
                    $response['tempo_restante'] = $restante;
                } else {
                    // Tempo acabou - atualizar para encerrado
                    $pdo->query("UPDATE controle_discurso SET status = 'encerrado' WHERE id = 1");
                    $response['status'] = 'encerrado';
                    $response['tempo_restante'] = 0;
                }
            } else {
                $response['status'] = 'encerrado';
                $response['tempo_restante'] = 0;
            }
            
        } elseif ($discurso['status'] === 'pausado') {
            // Quando pausado, mostrar tempo salvo
            $response['tempo_restante'] = intval($discurso['tempo_restante_pausa'] ?? 0);
            
        } else {
            // Encerrado ou outro status
            $response['tempo_restante'] = 0;
        }

        echo json_encode($response);
        
    } else {
        echo json_encode([
            'sucesso' => false, 
            'status' => 'encerrado', 
            'mensagem' => 'Nenhum controle de tempo ativo'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'sucesso' => false, 
        'status' => 'erro', 
        'erro' => $e->getMessage()
    ]);
}