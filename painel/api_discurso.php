<?php
/**
 * API de Controle de Discurso/Tempo de Fala
 * Corrigida para retornar dados consistentes
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';

try {
    // Buscar discurso ativo
    $stmt = $pdo->query("
        SELECT d.*, e.nome, e.foto, e.cargo, e.partido, e.logo_partido
        FROM controle_discurso d 
        LEFT JOIN eleitores e ON d.eleitor_id = e.id 
        WHERE d.id = 1
        LIMIT 1
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

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } else {
        // Nenhum registro encontrado - criar um padrão
        try {
            $pdo->exec("INSERT INTO controle_discurso (id, status) VALUES (1, 'encerrado') ON DUPLICATE KEY UPDATE id=1");
        } catch (Exception $e) {
            // Ignorar erro se já existir
        }
        
        echo json_encode([
            'sucesso' => true, 
            'status' => 'encerrado', 
            'nome' => '',
            'foto' => null,
            'partido' => null,
            'logo_partido' => null,
            'cargo' => '',
            'tempo_restante' => 0,
            'mensagem' => 'Nenhum controle de tempo ativo'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false, 
        'status' => 'erro', 
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}