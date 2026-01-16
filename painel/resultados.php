<?php
/**
 * Painel de Resultados em Tempo Real
 * Estilo Institucional - Fundo Escuro
 */
require_once '../config/database.php';
require_once '../config/functions.php';

// Buscar votação ativa
$votacao = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();

// Função para buscar resultados
function buscarResultados($pdo, $votacao_id) {
    if (!$votacao_id) {
        return [
            'total_sim' => 0,
            'total_nao' => 0,
            'total_geral' => 0,
            'percentual_sim' => 0,
            'percentual_nao' => 0,
            'votos' => []
        ];
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'");
    $stmt->execute([$votacao_id]);
    $total_sim = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'");
    $stmt->execute([$votacao_id]);
    $total_nao = $stmt->fetch()['total'];
    
    $total_geral = $total_sim + $total_nao;
    $percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
    $percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;
    
    // Buscar todos os votos com dados dos eleitores
    $stmt = $pdo->prepare("SELECT * FROM votos WHERE votacao_id = ? ORDER BY criado_em DESC");
    $stmt->execute([$votacao_id]);
    $votos = $stmt->fetchAll();
    
    return [
        'total_sim' => $total_sim,
        'total_nao' => $total_nao,
        'total_geral' => $total_geral,
        'percentual_sim' => $percentual_sim,
        'percentual_nao' => $percentual_nao,
        'votos' => $votos
    ];
}

// Buscar todos os eleitores cadastrados (para mostrar quem ainda não votou)
$eleitores_cadastrados = [];
if ($votacao) {
    $eleitores_cadastrados = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();
}

$resultados = buscarResultados($pdo, $votacao ? $votacao['id'] : null);

// Determinar status da votação
$status_fase = 'VOTAÇÃO ABERTA';
$status_resultado = '';
if ($resultados['total_geral'] > 0) {
    if ($resultados['total_nao'] == 0) {
        $status_resultado = 'APROVADO POR UNANIMIDADE';
    } elseif ($resultados['percentual_sim'] > 50) {
        $status_resultado = 'APROVADO';
    } else {
        $status_resultado = 'REJEITADO';
    }
}

// Criar mapa de quem já votou (por CPF)
$mapa_votantes = [];
foreach ($resultados['votos'] as $voto) {
    $mapa_votantes[$voto['cpf']] = $voto;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados - Sistema de Votação</title>
    <meta http-equiv="refresh" content="300">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        body {
            background: #f3f4f6;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
        }
        
        .stat-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .voter-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .voter-card:hover {
            background: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .status-bar {
            height: 8px;
            width: 100%;
            border-radius: 4px;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .status-bar.sim {
            background: #22c55e;
        }
        
        .status-bar.nao {
            background: #ef4444;
        }
        
        .status-bar.ausente {
            background: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full">
        <!-- Header -->
        <div class="bg-gray-800 border-b border-gray-700 py-4 px-8 fade-in">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-2xl font-bold text-white mb-1">CÂMARA MUNICIPAL</h1>
                <?php if ($votacao): ?>
                    <p class="text-sm text-gray-200">
                        <?= htmlspecialchars($votacao['titulo']) ?>
                    </p>
                    <p class="text-xs text-gray-300 mt-1">
                        <?= date('d/m/Y H:i') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-8 py-6">
            <?php if ($votacao): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Coluna Esquerda - Informações da Votação -->
                    <div class="lg:col-span-1 space-y-4">
                        <!-- Detalhes da Proposição -->
                        <div class="stat-box rounded-lg p-6 fade-in">
                            <div class="text-xs text-gray-500 uppercase mb-2">VOTAÇÃO ÚNICA</div>
                            <h2 class="text-xl font-bold text-gray-800 mb-3">
                                <?= htmlspecialchars($votacao['titulo']) ?>
                            </h2>
                            <?php if ($votacao['descricao']): ?>
                                <p class="text-sm text-gray-600 mb-4">
                                    <?= htmlspecialchars($votacao['descricao']) ?>
                                </p>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500">
                                DATA: <?= date('d/m/Y', strtotime($votacao['criada_em'])) ?>
                            </div>
                        </div>

                        <!-- Status da Votação -->
                        <div class="stat-box rounded-lg p-6 fade-in bg-gray-50">
                            <div class="text-xs text-gray-500 uppercase mb-2">FASE</div>
                            <div class="text-lg font-semibold text-gray-800 mb-4"><?= $status_fase ?></div>
                            <?php if ($status_resultado): ?>
                                <div class="text-2xl font-bold text-green-600 mb-2"><?= $status_resultado ?></div>
                                <div class="text-sm text-gray-500">MAIORIA SIMPLES</div>
                            <?php endif; ?>
                        </div>

                        <!-- Estatísticas -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="stat-box rounded-lg p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">QUÓR.</div>
                                <div class="text-3xl font-bold text-blue-600" id="total-geral"><?= $resultados['total_geral'] ?></div>
                            </div>
                            <div class="stat-box rounded-lg p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">FAVOR.</div>
                                <div class="text-3xl font-bold text-green-600" id="total-sim"><?= $resultados['total_sim'] ?></div>
                            </div>
                            <div class="stat-box rounded-lg p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">CONTRA</div>
                                <div class="text-3xl font-bold text-red-600" id="total-nao"><?= $resultados['total_nao'] ?></div>
                            </div>
                            <div class="stat-box rounded-lg p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">ABST.</div>
                                <div class="text-3xl font-bold text-yellow-600">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Direita - Grid de Eleitores -->
                    <div class="lg:col-span-2">
                        <div class="stat-box rounded-lg p-6 fade-in mb-4">
                            <h2 class="text-xl font-bold text-gray-800 mb-6">
                                VOTAÇÃO <?= strtoupper(htmlspecialchars($votacao['titulo'])) ?>
                            </h2>
                            
                            <!-- Grid de Eleitores -->
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                <?php 
                                // Mostrar eleitores cadastrados ou votantes
                                $eleitores_para_exibir = count($eleitores_cadastrados) > 0 ? $eleitores_cadastrados : [];
                                
                                // Se não houver eleitores cadastrados, usar os que votaram
                                if (count($eleitores_para_exibir) == 0) {
                                    foreach ($resultados['votos'] as $voto) {
                                        $eleitores_para_exibir[] = [
                                            'id' => null,
                                            'nome' => $voto['nome'],
                                            'cargo' => $voto['cargo'],
                                            'foto' => $voto['foto'],
                                            'cpf' => $voto['cpf']
                                        ];
                                    }
                                }
                                
                                foreach ($eleitores_para_exibir as $eleitor):
                                    $cpf_limpo = preg_replace('/[^0-9]/', '', $eleitor['cpf']);
                                    $votou = isset($mapa_votantes[$cpf_limpo]);
                                    $voto_info = $votou ? $mapa_votantes[$cpf_limpo] : null;
                                    $status_voto = $votou ? ($voto_info['voto'] == 'sim' ? 'sim' : 'nao') : 'ausente';
                                    $status_texto = $votou ? ($voto_info['voto'] == 'sim' ? 'A FAVOR' : 'CONTRA') : 'AUSENTE';
                                ?>
                                    <div class="voter-card rounded-lg p-4 fade-in">
                                        <!-- Foto e Informações -->
                                        <div class="flex items-center gap-3 mb-3">
                                            <?php if ($eleitor['foto']): ?>
                                                <img 
                                                    src="../uploads/<?= htmlspecialchars($eleitor['foto']) ?>" 
                                                    alt="<?= htmlspecialchars($eleitor['nome']) ?>"
                                                    class="w-14 h-14 rounded-full object-cover border-2 border-gray-300"
                                                >
                                            <?php else: ?>
                                                <div class="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-400">
                                                    <span class="text-gray-700 text-lg font-bold">
                                                        <?= strtoupper(substr($eleitor['nome'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-semibold text-gray-800 truncate">
                                                    <?= htmlspecialchars($eleitor['nome']) ?>
                                                </div>
                                                <?php if ($eleitor['cargo']): ?>
                                                    <div class="text-xs text-gray-500 truncate">
                                                        <?= htmlspecialchars($eleitor['cargo']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Barra de Status -->
                                        <div class="mt-3">
                                            <div class="status-bar <?= $status_voto ?> mb-2"></div>
                                            <div class="text-xs font-semibold text-gray-700 text-center">
                                                <?= $status_texto ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Status de Atualização -->
                        <div class="text-center">
                            <div class="inline-flex items-center gap-2 text-xs text-gray-500">
                                <div class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></div>
                                <span>Atualização automática a cada 3 segundos</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-box rounded-lg p-12 text-center fade-in">
                    <div class="text-5xl text-gray-400 mb-4">📋</div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Nenhuma Votação Ativa</h2>
                    <p class="text-gray-500">Aguardando abertura de nova votação...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const votacaoId = <?= $votacao ? $votacao['id'] : 'null' ?>;
        
        // Função para animar número
        function animarNumero(elemento, valorAntigo, valorNovo, duracao = 800) {
            if (valorAntigo === valorNovo || !elemento) return;
            
            const inicio = performance.now();
            const diferenca = valorNovo - valorAntigo;
            
            function atualizar(timestamp) {
                const progresso = Math.min((timestamp - inicio) / duracao, 1);
                const valorAtual = Math.round(valorAntigo + diferenca * easeOutCubic(progresso));
                elemento.textContent = valorAtual;
                
                if (progresso < 1) {
                    requestAnimationFrame(atualizar);
                } else {
                    elemento.textContent = valorNovo;
                }
            }
            
            requestAnimationFrame(atualizar);
        }
        
        function easeOutCubic(t) {
            return 1 - Math.pow(1 - t, 3);
        }
        
        // Função para atualizar resultados
        async function atualizarResultados() {
            if (!votacaoId) {
                setTimeout(() => location.reload(), 5000);
                return;
            }
            
            try {
                const response = await fetch(`api_resultados.php?votacao_id=${votacaoId}`);
                const data = await response.json();
                
                if (data.sucesso) {
                    const resultados = data.dados;
                    
                    // Obter valores antigos
                    const totalGeralEl = document.getElementById('total-geral');
                    const totalSimEl = document.getElementById('total-sim');
                    const totalNaoEl = document.getElementById('total-nao');
                    
                    const totalGeralAntigo = parseInt(totalGeralEl?.textContent) || 0;
                    const totalSimAntigo = parseInt(totalSimEl?.textContent) || 0;
                    const totalNaoAntigo = parseInt(totalNaoEl?.textContent) || 0;
                    
                    // Atualizar valores com animação
                    if (totalGeralEl) animarNumero(totalGeralEl, totalGeralAntigo, resultados.total_geral);
                    if (totalSimEl) animarNumero(totalSimEl, totalSimAntigo, resultados.total_sim);
                    if (totalNaoEl) animarNumero(totalNaoEl, totalNaoAntigo, resultados.total_nao);
                    
                    // Recarregar página para atualizar grid de eleitores
                    if (totalGeralAntigo !== resultados.total_geral) {
                        setTimeout(() => location.reload(), 1000);
                    }
                }
            } catch (error) {
                console.error('Erro ao atualizar resultados:', error);
            }
        }
        
        // Atualizar a cada 3 segundos
        setInterval(atualizarResultados, 3000);
        
        // Atualizar imediatamente ao carregar
        setTimeout(atualizarResultados, 500);
    </script>
</body>
</html>
