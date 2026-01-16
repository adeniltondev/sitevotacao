<?php
/**
 * Painel de Resultados em Tempo Real
 * Ideal para exibição em TV/Telão
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
            'percentual_nao' => 0
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
    
    return [
        'total_sim' => $total_sim,
        'total_nao' => $total_nao,
        'total_geral' => $total_geral,
        'percentual_sim' => $percentual_sim,
        'percentual_nao' => $percentual_nao
    ];
}

$resultados = buscarResultados($pdo, $votacao ? $votacao['id'] : null);
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
            background: #f8f9fa;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .professional-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .header-bar {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        
        .stat-number {
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        
        .progress-bar {
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .pie-chart {
            width: 320px;
            height: 320px;
        }
        
        .status-badge {
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full">
        <!-- Header Institucional -->
        <div class="header-bar text-white py-6 px-8 fade-in">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold mb-2">Resultados da Votação</h1>
                <?php if ($votacao): ?>
                    <h2 class="text-xl font-normal text-blue-100 mb-3">
                        <?= htmlspecialchars($votacao['titulo']) ?>
                    </h2>
                    <div class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded status-badge">
                        <div class="w-2 h-2 bg-white rounded-full pulse-dot"></div>
                        <span>VOTAÇÃO ABERTA</span>
                    </div>
                <?php else: ?>
                    <div class="inline-flex items-center gap-2 bg-gray-500 text-white px-4 py-2 rounded status-badge">
                        <span>NENHUMA VOTAÇÃO ATIVA</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-8 py-8">
            <?php if ($votacao): ?>
                <!-- Estatísticas Principais -->
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <!-- Total de Votos -->
                    <div class="professional-card rounded-lg p-6 text-center fade-in">
                        <div class="text-4xl font-bold text-blue-600 mb-2 stat-number" id="total-geral">
                            <?= $resultados['total_geral'] ?>
                        </div>
                        <div class="text-sm font-medium text-gray-600 uppercase tracking-wide">
                            Total de Votos
                        </div>
                    </div>
                    
                    <!-- SIM -->
                    <div class="professional-card rounded-lg p-6 text-center border-l-4 border-green-500 fade-in">
                        <div class="text-5xl font-bold text-green-600 mb-2 stat-number" id="total-sim">
                            <?= $resultados['total_sim'] ?>
                        </div>
                        <div class="text-base font-semibold text-gray-800 mb-1 uppercase tracking-wide">
                            SIM
                        </div>
                        <div class="text-2xl font-bold text-green-700" id="percentual-sim">
                            <?= $resultados['percentual_sim'] ?>%
                        </div>
                    </div>
                    
                    <!-- NÃO -->
                    <div class="professional-card rounded-lg p-6 text-center border-l-4 border-red-500 fade-in">
                        <div class="text-5xl font-bold text-red-600 mb-2 stat-number" id="total-nao">
                            <?= $resultados['total_nao'] ?>
                        </div>
                        <div class="text-base font-semibold text-gray-800 mb-1 uppercase tracking-wide">
                            NÃO
                        </div>
                        <div class="text-2xl font-bold text-red-700" id="percentual-nao">
                            <?= $resultados['percentual_nao'] ?>%
                        </div>
                    </div>
                </div>

                <!-- Visualizações -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Gráfico de Pizza -->
                    <div class="professional-card rounded-lg p-6 fade-in">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6 text-center">
                            Distribuição Percentual
                        </h3>
                        <div class="flex justify-center items-center">
                            <svg class="pie-chart" viewBox="0 0 400 400">
                                <circle cx="200" cy="200" r="150" fill="#f3f4f6" stroke="#e5e7eb" stroke-width="2"/>
                                <?php 
                                $simAngle = ($resultados['percentual_sim'] / 100) * 360;
                                $naoAngle = ($resultados['percentual_nao'] / 100) * 360;
                                $simStart = -90;
                                $naoStart = $simStart + $simAngle;
                                
                                $simStartX = 200 + 150 * cos(deg2rad($simStart));
                                $simStartY = 200 + 150 * sin(deg2rad($simStart));
                                $simEndX = 200 + 150 * cos(deg2rad($simStart + $simAngle));
                                $simEndY = 200 + 150 * sin(deg2rad($simStart + $simAngle));
                                $naoStartX = 200 + 150 * cos(deg2rad($naoStart));
                                $naoStartY = 200 + 150 * sin(deg2rad($naoStart));
                                $naoEndX = 200 + 150 * cos(deg2rad($naoStart + $naoAngle));
                                $naoEndY = 200 + 150 * sin(deg2rad($naoStart + $naoAngle));
                                ?>
                                <!-- SIM -->
                                <?php if ($resultados['percentual_sim'] > 0): ?>
                                <path class="pie-segment" d="M 200 200 L <?= number_format($simStartX, 2) ?> <?= number_format($simStartY, 2) ?> A 150 150 0 <?= $simAngle > 180 ? 1 : 0 ?> 1 <?= number_format($simEndX, 2) ?> <?= number_format($simEndY, 2) ?> Z" 
                                      fill="#22c55e" id="pie-sim" stroke="#ffffff" stroke-width="3"/>
                                <?php endif; ?>
                                <!-- NÃO -->
                                <?php if ($resultados['percentual_nao'] > 0): ?>
                                <path class="pie-segment" d="M 200 200 L <?= number_format($naoStartX, 2) ?> <?= number_format($naoStartY, 2) ?> A 150 150 0 <?= $naoAngle > 180 ? 1 : 0 ?> 1 <?= number_format($naoEndX, 2) ?> <?= number_format($naoEndY, 2) ?> Z" 
                                      fill="#ef4444" id="pie-nao" stroke="#ffffff" stroke-width="3"/>
                                <?php endif; ?>
                                <!-- Centro -->
                                <circle cx="200" cy="200" r="90" fill="white" stroke="#e5e7eb" stroke-width="2"/>
                                <text x="200" y="195" text-anchor="middle" font-size="18" font-weight="600" fill="#4b5563">Total</text>
                                <text x="200" y="220" text-anchor="middle" font-size="24" font-weight="700" fill="#1e3a8a" id="pie-total"><?= $resultados['total_geral'] ?></text>
                            </svg>
                        </div>
                    </div>

                    <!-- Barras de Progresso -->
                    <div class="professional-card rounded-lg p-6 fade-in">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6 text-center">
                            Comparação de Votos
                        </h3>
                        
                        <!-- Barra SIM -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700 uppercase">SIM</span>
                                <span class="text-lg font-bold text-green-600" id="bar-percent-sim"><?= $resultados['percentual_sim'] ?>%</span>
                            </div>
                            <div class="h-8 bg-gray-100 rounded overflow-hidden">
                                <div class="progress-bar h-full bg-green-500" 
                                     id="barra-sim" style="width: <?= $resultados['percentual_sim'] ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 text-right" id="bar-count-sim">
                                <?= $resultados['total_sim'] ?> votos
                            </div>
                        </div>
                        
                        <!-- Barra NÃO -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700 uppercase">NÃO</span>
                                <span class="text-lg font-bold text-red-600" id="bar-percent-nao"><?= $resultados['percentual_nao'] ?>%</span>
                            </div>
                            <div class="h-8 bg-gray-100 rounded overflow-hidden">
                                <div class="progress-bar h-full bg-red-500" 
                                     id="barra-nao" style="width: <?= $resultados['percentual_nao'] ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 text-right" id="bar-count-nao">
                                <?= $resultados['total_nao'] ?> votos
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra Comparativa Horizontal -->
                <div class="professional-card rounded-lg p-6 mb-6 fade-in">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                        Distribuição dos Votos
                    </h3>
                    <div class="relative h-12 bg-gray-100 rounded overflow-hidden">
                        <div class="absolute left-0 top-0 h-full bg-green-500 progress-bar flex items-center justify-end pr-3" 
                             id="barra-comparativa-sim" style="width: <?= $resultados['percentual_sim'] ?>%">
                            <?php if ($resultados['percentual_sim'] > 15): ?>
                            <span class="text-sm font-semibold text-white" id="label-sim-bar">
                                SIM <?= $resultados['percentual_sim'] ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="absolute right-0 top-0 h-full bg-red-500 progress-bar flex items-center justify-start pl-3" 
                             id="barra-comparativa-nao" style="width: <?= $resultados['percentual_nao'] ?>%">
                            <?php if ($resultados['percentual_nao'] > 15): ?>
                            <span class="text-sm font-semibold text-white" id="label-nao-bar">
                                NÃO <?= $resultados['percentual_nao'] ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Rodapé com Status -->
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 text-sm text-gray-600">
                        <div class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></div>
                        <span>Atualização automática a cada 3 segundos</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="professional-card rounded-lg p-12 text-center fade-in">
                    <div class="text-5xl text-gray-400 mb-4">📋</div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Nenhuma Votação Ativa</h2>
                    <p class="text-gray-600">Aguardando abertura de nova votação...</p>
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
        
        // Função para atualizar gráfico de pizza
        function atualizarPizza(simPercent, naoPercent, total) {
            const svg = document.querySelector('.pie-chart');
            if (!svg) return;
            
            const simAngle = (simPercent / 100) * 360;
            const naoAngle = (naoPercent / 100) * 360;
            const simStart = -90;
            const naoStart = simStart + simAngle;
            const centerX = 200;
            const centerY = 200;
            const radius = 150;
            
            // Atualizar segmento SIM
            const pieSim = document.getElementById('pie-sim');
            if (pieSim && simPercent > 0) {
                const simEndX = centerX + radius * Math.cos((simStart + simAngle) * Math.PI / 180);
                const simEndY = centerY + radius * Math.sin((simStart + simAngle) * Math.PI / 180);
                const simStartX = centerX + radius * Math.cos(simStart * Math.PI / 180);
                const simStartY = centerY + radius * Math.sin(simStart * Math.PI / 180);
                
                pieSim.setAttribute('d', `M ${centerX} ${centerY} L ${simStartX} ${simStartY} A ${radius} ${radius} 0 ${simAngle > 180 ? 1 : 0} 1 ${simEndX} ${simEndY} Z`);
            }
            
            // Atualizar segmento NÃO
            const pieNao = document.getElementById('pie-nao');
            if (pieNao && naoPercent > 0) {
                const naoEndX = centerX + radius * Math.cos((naoStart + naoAngle) * Math.PI / 180);
                const naoEndY = centerY + radius * Math.sin((naoStart + naoAngle) * Math.PI / 180);
                const naoStartX = centerX + radius * Math.cos(naoStart * Math.PI / 180);
                const naoStartY = centerY + radius * Math.sin(naoStart * Math.PI / 180);
                
                pieNao.setAttribute('d', `M ${centerX} ${centerY} L ${naoStartX} ${naoStartY} A ${radius} ${radius} 0 ${naoAngle > 180 ? 1 : 0} 1 ${naoEndX} ${naoEndY} Z`);
            }
            
            // Atualizar total no centro
            const pieTotal = document.getElementById('pie-total');
            if (pieTotal) {
                pieTotal.textContent = total;
            }
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
                    
                    // Atualizar percentuais
                    atualizarValor('percentual-sim', resultados.percentual_sim + '%');
                    atualizarValor('percentual-nao', resultados.percentual_nao + '%');
                    atualizarValor('bar-percent-sim', resultados.percentual_sim + '%');
                    atualizarValor('bar-percent-nao', resultados.percentual_nao + '%');
                    atualizarValor('bar-count-sim', resultados.total_sim + ' votos');
                    atualizarValor('bar-count-nao', resultados.total_nao + ' votos');
                    
                    // Atualizar barras verticais
                    const barraSim = document.getElementById('barra-sim');
                    const barraNao = document.getElementById('barra-nao');
                    if (barraSim) barraSim.style.width = resultados.percentual_sim + '%';
                    if (barraNao) barraNao.style.width = resultados.percentual_nao + '%';
                    
                    // Atualizar barra comparativa horizontal
                    const barraComparativaSim = document.getElementById('barra-comparativa-sim');
                    const barraComparativaNao = document.getElementById('barra-comparativa-nao');
                    if (barraComparativaSim) {
                        barraComparativaSim.style.width = resultados.percentual_sim + '%';
                        const labelSim = document.getElementById('label-sim-bar');
                        if (labelSim && resultados.percentual_sim > 15) {
                            labelSim.textContent = `SIM ${resultados.percentual_sim}%`;
                        }
                    }
                    if (barraComparativaNao) {
                        barraComparativaNao.style.width = resultados.percentual_nao + '%';
                        const labelNao = document.getElementById('label-nao-bar');
                        if (labelNao && resultados.percentual_nao > 15) {
                            labelNao.textContent = `NÃO ${resultados.percentual_nao}%`;
                        }
                    }
                    
                    // Atualizar gráfico de pizza
                    atualizarPizza(resultados.percentual_sim, resultados.percentual_nao, resultados.total_geral);
                }
            } catch (error) {
                console.error('Erro ao atualizar resultados:', error);
            }
        }
        
        function atualizarValor(id, novoValor) {
            const elemento = document.getElementById(id);
            if (elemento && elemento.textContent != novoValor) {
                elemento.textContent = novoValor;
            }
        }
        
        // Atualizar a cada 3 segundos
        setInterval(atualizarResultados, 3000);
        
        // Atualizar imediatamente ao carregar
        setTimeout(atualizarResultados, 500);
    </script>
</body>
</html>
