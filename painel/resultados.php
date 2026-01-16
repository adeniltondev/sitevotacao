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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'spin-slow': 'spin 3s linear infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.5); }
            50% { box-shadow: 0 0 40px rgba(34, 197, 94, 0.8), 0 0 60px rgba(34, 197, 94, 0.4); }
        }
        @keyframes glow-red {
            0%, 100% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 40px rgba(239, 68, 68, 0.8), 0 0 60px rgba(239, 68, 68, 0.4); }
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes numberPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .glow-green {
            animation: glow 2s ease-in-out infinite;
        }
        .glow-red {
            animation: glow-red 2s ease-in-out infinite;
        }
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        .number-pop {
            animation: numberPop 0.4s ease-out;
        }
        
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        @media (min-width: 1920px) {
            body {
                font-size: 1.1rem;
            }
        }
        
        /* Garantir que elementos sejam visíveis mesmo à distância */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .card-shadow {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .progress-bar-container {
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar {
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .pie-chart {
            position: relative;
            width: 350px;
            height: 350px;
        }
        
        .pie-segment {
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .big-number {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 2px;
        }
        
        .status-badge {
            animation: pulse 2s infinite;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full px-8 py-6">
        <!-- Header -->
        <div class="text-center mb-8 slide-in">
            <div class="inline-block bg-white rounded-2xl px-8 py-5 mb-6 card-shadow border-2 border-gray-200">
                <h1 class="text-4xl font-black text-gray-800 mb-3">
                    RESULTADOS DA VOTAÇÃO
                </h1>
                <?php if ($votacao): ?>
                    <h2 class="text-2xl font-bold text-gray-700 mb-4">
                        <?= htmlspecialchars($votacao['titulo']) ?>
                    </h2>
                    <div class="inline-block bg-green-500 text-white px-6 py-3 rounded-full text-lg font-bold status-badge glow-green">
                        ✓ VOTAÇÃO ABERTA
                    </div>
                <?php else: ?>
                    <div class="inline-block bg-gray-500 text-white px-6 py-3 rounded-full text-lg font-bold">
                        NENHUMA VOTAÇÃO ATIVA
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($votacao): ?>
            <!-- Estatísticas Principais -->
            <div class="grid grid-cols-3 gap-6 mb-8">
                <!-- Total de Votos -->
                <div class="bg-white rounded-2xl p-8 text-center card-shadow stat-card border-2 border-gray-200">
                    <div class="text-5xl font-black text-blue-600 mb-3 big-number" id="total-geral" style="line-height: 1.1;">
                        <?= $resultados['total_geral'] ?>
                    </div>
                    <div class="text-xl font-bold text-gray-700 uppercase tracking-wide">Total de Votos</div>
                    <div class="mt-3 text-4xl">📊</div>
                </div>
                
                <!-- SIM -->
                <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-2xl p-8 text-center card-shadow stat-card glow-green border-4 border-green-300">
                    <div class="text-6xl font-black text-white mb-3 big-number" id="total-sim" style="line-height: 1.1; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                        <?= $resultados['total_sim'] ?>
                    </div>
                    <div class="text-2xl font-black text-white mb-3 uppercase tracking-wider" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                        SIM
                    </div>
                    <div class="text-4xl font-black text-white" id="percentual-sim" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                        <?= $resultados['percentual_sim'] ?>%
                    </div>
                    <div class="mt-3 text-4xl">✅</div>
                </div>
                
                <!-- NÃO -->
                <div class="bg-gradient-to-br from-red-400 to-red-600 rounded-2xl p-8 text-center card-shadow stat-card glow-red border-4 border-red-300">
                    <div class="text-6xl font-black text-white mb-3 big-number" id="total-nao" style="line-height: 1.1; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                        <?= $resultados['total_nao'] ?>
                    </div>
                    <div class="text-2xl font-black text-white mb-3 uppercase tracking-wider" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                        NÃO
                    </div>
                    <div class="text-4xl font-black text-white" id="percentual-nao" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                        <?= $resultados['percentual_nao'] ?>%
                    </div>
                    <div class="mt-3 text-4xl">❌</div>
                </div>
            </div>

            <!-- Seção de Visualizações -->
            <div class="grid grid-cols-2 gap-6 mb-8">
                <!-- Gráfico de Pizza SVG -->
                <div class="bg-white rounded-2xl p-8 card-shadow border-2 border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center uppercase tracking-wide">
                        Distribuição Percentual
                    </h3>
                    <div class="flex justify-center items-center">
                        <svg class="pie-chart" viewBox="0 0 400 400">
                            <circle cx="200" cy="200" r="150" fill="#e5e7eb" stroke="#fff" stroke-width="4"/>
                            <?php 
                            $simAngle = ($resultados['percentual_sim'] / 100) * 360;
                            $naoAngle = ($resultados['percentual_nao'] / 100) * 360;
                            $simStart = -90;
                            $naoStart = $simStart + $simAngle;
                            
                            // Calcular coordenadas
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
                                  fill="#22c55e" id="pie-sim" stroke="#fff" stroke-width="4"/>
                            <?php endif; ?>
                            <!-- NÃO -->
                            <?php if ($resultados['percentual_nao'] > 0): ?>
                            <path class="pie-segment" d="M 200 200 L <?= number_format($naoStartX, 2) ?> <?= number_format($naoStartY, 2) ?> A 150 150 0 <?= $naoAngle > 180 ? 1 : 0 ?> 1 <?= number_format($naoEndX, 2) ?> <?= number_format($naoEndY, 2) ?> Z" 
                                  fill="#ef4444" id="pie-nao" stroke="#fff" stroke-width="4"/>
                            <?php endif; ?>
                            <!-- Centro -->
                            <circle cx="200" cy="200" r="80" fill="white" stroke="#e5e7eb" stroke-width="2"/>
                            <text x="200" y="195" text-anchor="middle" font-size="20" font-weight="bold" fill="#1f2937">Total</text>
                            <text x="200" y="225" text-anchor="middle" font-size="28" font-weight="900" fill="#3b82f6" id="pie-total"><?= $resultados['total_geral'] ?></text>
                        </svg>
                    </div>
                </div>

                <!-- Barras de Progresso Melhoradas -->
                <div class="bg-white rounded-2xl p-8 card-shadow border-2 border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center uppercase tracking-wide">
                        Comparação Visual
                    </h3>
                    
                    <!-- Barra SIM -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xl font-bold text-green-700 uppercase">SIM</span>
                            <span class="text-2xl font-black text-green-700" id="bar-percent-sim"><?= $resultados['percentual_sim'] ?>%</span>
                        </div>
                        <div class="progress-bar-container h-12 bg-gray-200 rounded-xl overflow-hidden shadow-inner">
                            <div class="progress-bar h-full bg-gradient-to-r from-green-400 to-green-600 rounded-xl" 
                                 id="barra-sim" style="width: <?= $resultados['percentual_sim'] ?>%"></div>
                        </div>
                        <div class="text-lg text-gray-600 mt-2 text-right" id="bar-count-sim">
                            <?= $resultados['total_sim'] ?> votos
                        </div>
                    </div>
                    
                    <!-- Barra NÃO -->
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xl font-bold text-red-700 uppercase">NÃO</span>
                            <span class="text-2xl font-black text-red-700" id="bar-percent-nao"><?= $resultados['percentual_nao'] ?>%</span>
                        </div>
                        <div class="progress-bar-container h-12 bg-gray-200 rounded-xl overflow-hidden shadow-inner">
                            <div class="progress-bar h-full bg-gradient-to-r from-red-400 to-red-600 rounded-xl" 
                                 id="barra-nao" style="width: <?= $resultados['percentual_nao'] ?>%"></div>
                        </div>
                        <div class="text-lg text-gray-600 mt-2 text-right" id="bar-count-nao">
                            <?= $resultados['total_nao'] ?> votos
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra Horizontal Comparativa -->
            <div class="bg-white rounded-2xl p-6 card-shadow mb-6 border-2 border-gray-200">
                <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center uppercase">Distribuição dos Votos</h3>
                <div class="relative h-16 bg-gray-200 rounded-xl overflow-hidden shadow-inner">
                    <div class="absolute left-0 top-0 h-full bg-gradient-to-r from-green-400 via-green-500 to-green-600 progress-bar transition-all duration-1000" 
                         id="barra-comparativa-sim" style="width: <?= $resultados['percentual_sim'] ?>%">
                        <div class="h-full flex items-center justify-end pr-3">
                            <span class="text-xl font-black text-white drop-shadow-lg" id="label-sim-bar">
                                SIM <?= $resultados['percentual_sim'] ?>%
                            </span>
                        </div>
                    </div>
                    <div class="absolute right-0 top-0 h-full bg-gradient-to-l from-red-400 via-red-500 to-red-600 progress-bar transition-all duration-1000" 
                         id="barra-comparativa-nao" style="width: <?= $resultados['percentual_nao'] ?>%">
                        <div class="h-full flex items-center justify-start pl-3">
                            <span class="text-xl font-black text-white drop-shadow-lg" id="label-nao-bar">
                                NÃO <?= $resultados['percentual_nao'] ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indicador de Atualização -->
            <div class="text-center">
                <div class="inline-flex items-center gap-3 bg-gray-100 rounded-full px-6 py-3 border-2 border-gray-300">
                    <div class="w-4 h-4 bg-green-500 rounded-full pulse-animation shadow-lg" id="status-indicator"></div>
                    <span class="text-lg font-semibold text-gray-700">Atualização automática a cada 3 segundos</span>
                    <div class="w-4 h-4 border-3 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl p-12 text-center card-shadow border-2 border-gray-200">
                <div class="text-6xl mb-6">📋</div>
                <h2 class="text-3xl font-black text-gray-800 mb-3">Nenhuma Votação Ativa</h2>
                <p class="text-gray-600 text-xl">Aguardando abertura de nova votação...</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const votacaoId = <?= $votacao ? $votacao['id'] : 'null' ?>;
        
        // Função para animar número
        function animarNumero(elemento, valorAntigo, valorNovo, duracao = 1000) {
            if (valorAntigo === valorNovo) return;
            
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
                    elemento.classList.add('number-pop');
                    setTimeout(() => elemento.classList.remove('number-pop'), 400);
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
                    const totalGeralAntigo = parseInt(document.getElementById('total-geral').textContent) || 0;
                    const totalSimAntigo = parseInt(document.getElementById('total-sim').textContent) || 0;
                    const totalNaoAntigo = parseInt(document.getElementById('total-nao').textContent) || 0;
                    
                    // Atualizar valores com animação
                    const totalGeralEl = document.getElementById('total-geral');
                    const totalSimEl = document.getElementById('total-sim');
                    const totalNaoEl = document.getElementById('total-nao');
                    
                    animarNumero(totalGeralEl, totalGeralAntigo, resultados.total_geral);
                    animarNumero(totalSimEl, totalSimAntigo, resultados.total_sim);
                    animarNumero(totalNaoEl, totalNaoAntigo, resultados.total_nao);
                    
                    // Atualizar percentuais
                    atualizarValor('percentual-sim', resultados.percentual_sim + '%');
                    atualizarValor('percentual-nao', resultados.percentual_nao + '%');
                    atualizarValor('bar-percent-sim', resultados.percentual_sim + '%');
                    atualizarValor('bar-percent-nao', resultados.percentual_nao + '%');
                    atualizarValor('bar-count-sim', resultados.total_sim + ' votos');
                    atualizarValor('bar-count-nao', resultados.total_nao + ' votos');
                    
                    // Atualizar barras verticais
                    document.getElementById('barra-sim').style.width = resultados.percentual_sim + '%';
                    document.getElementById('barra-nao').style.width = resultados.percentual_nao + '%';
                    
                    // Atualizar barra comparativa horizontal
                    document.getElementById('barra-comparativa-sim').style.width = resultados.percentual_sim + '%';
                    document.getElementById('barra-comparativa-nao').style.width = resultados.percentual_nao + '%';
                    document.getElementById('label-sim-bar').textContent = `SIM ${resultados.percentual_sim}%`;
                    document.getElementById('label-nao-bar').textContent = `NÃO ${resultados.percentual_nao}%`;
                    
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
                elemento.classList.add('number-pop');
                elemento.textContent = novoValor;
                setTimeout(() => {
                    elemento.classList.remove('number-pop');
                }, 400);
            }
        }
        
        // Atualizar a cada 3 segundos
        setInterval(atualizarResultados, 3000);
        
        // Atualizar imediatamente ao carregar
        setTimeout(atualizarResultados, 500);
    </script>
</body>
</html>
