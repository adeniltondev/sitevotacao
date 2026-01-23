<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';

try {
    // Buscar discurso ativo/pausado
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
            // Verifica se tem os campos necessários
            if (!empty($discurso['inicio']) && !empty($discurso['duracao_segundos'])) {
                $inicio = strtotime($discurso['inicio']);
                $agora = time();
                $decorrido = $agora - $inicio;
                $restante = max(0, intval($discurso['duracao_segundos']) - $decorrido);
                $response['tempo_restante'] = $restante;
                
                // Se o tempo acabou, atualizar status para encerrado
                if ($restante <= 0) {
                    $pdo->query("UPDATE controle_discurso SET status = 'encerrado' WHERE id = 1");
                    $response['status'] = 'encerrado';
                }
            } else {
                // Se não tem os campos, considerar como encerrado
                $response['status'] = 'encerrado';
                $response['tempo_restante'] = 0;
            }
            
        } elseif ($discurso['status'] === 'pausado') {
            // Quando pausado, usar o tempo salvo na pausa
            $response['tempo_restante'] = intval($discurso['tempo_restante_pausa'] ?? 0);
            
        } elseif ($discurso['status'] === 'encerrado') {
            // Quando encerrado, tempo é zero
            $response['tempo_restante'] = 0;
        }

        echo json_encode($response);
        
    } else {
        // Registro não encontrado
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
} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false, 
        'status' => 'erro', 
        'erro' => $e->getMessage()
    ]);
}