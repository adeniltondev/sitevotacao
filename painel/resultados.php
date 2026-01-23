                        <!-- Debug Tempo de Fala -->
                        <div id="debug-tempo-fala" class="stat-box rounded-xl p-4 mt-4 bg-gray-100 dark:bg-gray-800 text-xs text-left w-full max-w-2xl mx-auto" style="display:none; visibility:hidden; height:0; overflow:hidden;">
                            <div class="font-bold mb-1">[DEBUG] Tempo de Fala API Response:</div>
                            <pre id="debug-tempo-fala-json" style="white-space:pre-wrap;word-break:break-all;"></pre>
                            <div id="debug-tempo-fala-erro" class="text-red-600 font-bold mt-2"></div>
                        </div>
<?php
/**
 * Painel de Resultados em Tempo Real
 * Estilo Institucional - Fundo Escuro
 */

require_once '../config/database.php';
require_once '../config/functions.php';
iniciarSessao();

// Permitir acesso público para leitura dos totais, mas grid detalhado só para vereador/secretario
$temAcessoDetalhado = false;
if (isset($_SESSION['eleitor_id']) && isset($_SESSION['eleitor_perfil']) && in_array($_SESSION['eleitor_perfil'], ['vereador','secretario'])) {
    $temAcessoDetalhado = true;
}

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
    <link rel="icon" href="<?= !empty($settings['favicon_path']) ? '../' . htmlspecialchars($settings['favicon_path']) : '../assets/favicon.ico' ?>" type="image/x-icon">
    <title>Resultados - <?= htmlspecialchars($settings['sistema_nome'] ?? 'Sistema de Votação') ?></title>
    <!-- <meta http-equiv="refresh" content="300"> Removed in favor of AJAX -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        // Inicializa tema a partir de localStorage ou preferência do sistema
        if (localStorage.getItem('darkMode') === '1' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @media (prefers-reduced-motion: reduce) {
            .fade-in, .pulse-dot, .voter-card, .status-bar { animation: none !important; transition: none !important; }
        }

        .fade-in { animation: fadeIn 0.5s ease-out; }
        .pulse-dot { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

        /* Modern cards */
        .stat-box {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(17, 24, 39, 0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 10px 30px rgba(0,0,0,0.06);
            backdrop-filter: blur(6px);
        }
        .dark .stat-box {
            background: rgba(17, 24, 39, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.35);
        }

        .voter-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(17, 24, 39, 0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }
        .voter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .dark .voter-card {
            background: rgba(17, 24, 39, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.35);
        }

        /* Better dark-mode text colors (readability) */
        .dark .text-gray-900 { color: #f9fafb !important; }
        .dark .text-gray-800 { color: #f3f4f6 !important; }
        .dark .text-gray-700 { color: #e5e7eb !important; }
        .dark .text-gray-600 { color: #d1d5db !important; }
        .dark .text-gray-500 { color: #9ca3af !important; }
        .dark .text-gray-400 { color: #9ca3af !important; }

        .dark .text-blue-600 { color: #60a5fa !important; }
        .dark .text-green-600 { color: #34d399 !important; }
        .dark .text-red-600 { color: #fb7185 !important; }
        .dark .text-yellow-600 { color: #fbbf24 !important; }

        .status-bar { height: 6px; width: 100%; border-radius: 9999px; transition: all 0.5s ease; }
        .status-bar.sim { background: linear-gradient(90deg, #22c55e, #16a34a); }
        .status-bar.nao { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .status-bar.ausente { background: linear-gradient(90deg, #cbd5e1, #94a3b8); }
        .dark .status-bar.ausente { background: linear-gradient(90deg, #334155, #475569); }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100">
    <div class="min-h-screen w-full">
        <!-- Header -->
        <div class="bg-gray-800 border-b border-gray-700 py-4 px-8 fade-in">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
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
                <div class="mt-4 md:mt-0 flex flex-wrap gap-2 justify-start md:justify-end">
                    <a href="qrcode_publico.php" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75v-1.5A2.25 2.25 0 016.75 3h1.5M17.25 3h1.5A2.25 2.25 0 0121 5.25v1.5M21 17.25v1.5A2.25 2.25 0 0118.75 21h-1.5M6.75 21h-1.5A2.25 2.25 0 013 18.75v-1.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h.008v.008H7.5V7.5zm0 4.5h.008v.008H7.5V12zm0 4.5h.008v.008H7.5v-.008zm4.5-9h.008v.008H12V7.5zm0 4.5h.008v.008H12V12zm0 4.5h.008v.008H12v-.008zm4.5-9h.008v.008H16.5V7.5zm0 4.5h.008v.008H16.5V12z" /></svg>
                        QR Code Público
                    </a>
                    <button type="button" onclick="alternarModoEscuro()" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15 transition">
                        <span id="icone-modo">🌙</span>
                        <span id="texto-modo">Modo Escuro</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-8 py-6">
            <?php if ($votacao): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Coluna Esquerda - Informações da Votação -->
                    <div class="lg:col-span-1 space-y-4">
                        <!-- Detalhes da Proposição -->
                        <div class="stat-box rounded-2xl p-6 md:p-7 fade-in">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="inline-flex items-center gap-2 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">
                                        <span class="inline-block h-2 w-2 rounded-full bg-blue-500/80"></span>
                                        VOTAÇÃO ÚNICA
                                    </div>
                                    <h2 class="mt-3 text-xl md:text-2xl font-bold text-gray-900 dark:text-white leading-snug">
                                        <?= htmlspecialchars($votacao['titulo']) ?>
                                    </h2>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-wide">Data</div>
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        <?= date('d/m/Y', strtotime($votacao['criada_em'])) ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($votacao['descricao']): ?>
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                    <?= htmlspecialchars($votacao['descricao']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-6 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 items-start">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">Exportação</div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="exportar_csv.php?votacao_id=<?= $votacao['id'] ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 text-white px-3 py-2 text-xs font-semibold hover:bg-gray-800 transition dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                            CSV
                                        </a>
                                        <a href="exportar_pdf.php?votacao_id=<?= $votacao['id'] ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 text-white px-3 py-2 text-xs font-semibold hover:bg-gray-800 transition dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                            PDF
                                        </a>
                                        <a href="exportar_ata.php?votacao_id=<?= $votacao['id'] ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 text-white px-3 py-2 text-xs font-semibold hover:bg-gray-800 transition dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                            Ata
                                        </a>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Exporta todos os votos desta votação.</div>
                                </div>

                                <div class="flex md:justify-end">
                                    <div class="rounded-xl bg-white/70 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 p-3">
                                        <div class="text-[11px] font-semibold text-gray-600 dark:text-gray-400 text-center mb-2">Acesse pelo celular</div>
                                        <?php
                                        $url_resultados = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                                        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($url_resultados);
                                        $url_display = preg_replace('#^https?://#', '', $url_resultados);
                                        ?>
                                        <img src="<?= $qr_url ?>" alt="QR Code Resultados" class="w-28 h-28 rounded-lg bg-white" loading="lazy" onerror="this.style.display='none'">
                                        <div class="mt-2 text-[10px] text-gray-400 dark:text-gray-500 text-center max-w-[140px] truncate" title="<?= htmlspecialchars($url_display) ?>">
                                            <?= htmlspecialchars($url_display) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status da Votação -->
                        <div class="stat-box rounded-2xl p-6 md:p-7 fade-in bg-gray-50 dark:bg-gray-900/30">
                            <div class="text-xs text-gray-500 uppercase mb-2">FASE</div>
                            <div class="text-lg font-semibold text-gray-800 mb-4"><?= $status_fase ?></div>
                            <?php if ($status_resultado): ?>
                                <div class="text-2xl font-bold text-green-600 mb-2"><?= $status_resultado ?></div>
                                <div class="text-sm text-gray-500">MAIORIA SIMPLES</div>
                            <?php endif; ?>
                        </div>



                        <!-- Estatísticas -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="stat-box rounded-xl p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">QUÓR.</div>
                                <div class="text-3xl font-bold text-blue-600" id="total-geral"><?= $resultados['total_geral'] ?></div>
                            </div>
                            <div class="stat-box rounded-xl p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">FAVOR.</div>
                                <div class="text-3xl font-bold text-green-600" id="total-sim"><?= $resultados['total_sim'] ?></div>
                            </div>
                            <div class="stat-box rounded-xl p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">CONTRA</div>
                                <div class="text-3xl font-bold text-red-600" id="total-nao"><?= $resultados['total_nao'] ?></div>
                            </div>
                            <div class="stat-box rounded-xl p-4 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">ABST.</div>
                                <div class="text-3xl font-bold text-yellow-600">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Direita - Grid de Eleitores + Controle de Tempo de Fala -->
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <div class="stat-box rounded-2xl p-6 md:p-7 fade-in mb-4">
                            <h2 class="text-xl font-bold text-gray-800 mb-6">
                                VOTAÇÃO <?= strtoupper(htmlspecialchars($votacao['titulo'])) ?>
                            </h2>
                            <?php if ($temAcessoDetalhado): ?>
                                <!-- Grid de Eleitores -->
                                <div id="grid-eleitores" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <?php 
                                    // ...existing code...
                                    foreach ($eleitores_para_exibir as $eleitor):
                                        $cpf_limpo = preg_replace('/[^0-9]/', '', $eleitor['cpf']);
                                        $votou = isset($mapa_votantes[$cpf_limpo]);
                                        $voto_info = $votou ? $mapa_votantes[$cpf_limpo] : null;
                                        $status_voto = $votou ? ($voto_info['voto'] == 'sim' ? 'sim' : 'nao') : 'ausente';
                                        $status_texto = $votou ? ($voto_info['voto'] == 'sim' ? 'A FAVOR' : 'CONTRA') : 'AUSENTE';
                                    ?>
                                        <div class="voter-card rounded-xl p-4 fade-in" data-cpf="<?= $cpf_limpo ?>">
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
                                                <div class="status-bar <?= $status_voto ?> mb-2" data-role="status-bar"></div>
                                                <div class="text-xs font-semibold text-gray-700 text-center status-text" data-role="status-text">
                                                    <?= $status_texto ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-gray-500 text-sm my-8">Acesse com login de vereador ou secretário para ver o detalhamento dos votantes.</div>
                            <?php endif; ?>
                        </div>



                        <!-- Status de Atualização -->
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center gap-2 text-xs text-gray-500">
                                <div class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></div>
                                <span>Atualização automática a cada 3 segundos</span>
                            </div>
                        </div>

                        <!-- Controle de Tempo de Fala (único card) -->
                        <div id="card-tempo-fala" class="stat-box rounded-2xl p-6 md:p-7 fade-in bg-blue-50 dark:bg-blue-900/30 flex flex-col items-center justify-center" style="min-height: 220px;">
                            <div class="text-lg md:text-xl font-bold text-blue-800 dark:text-blue-200 mb-4 uppercase tracking-wide">Tempo de Fala</div>
                            <div id="tempo-fala-loading" class="text-gray-500 text-center">Carregando tempo de fala...</div>
                            <div id="tempo-fala-conteudo" style="display:none; width:100%;">
                                <div class="flex flex-col items-center justify-center w-full">
                                    <div class="flex items-center justify-center mb-2 w-full">
                                        <img id="tempo-fala-foto" src="" alt="Foto" class="w-24 h-24 rounded-full object-cover border-4 border-blue-400 shadow-lg hidden">
                                    </div>
                                    <div id="tempo-fala-nome" class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white text-center mb-1 uppercase tracking-tight"></div>
                                    <div class="flex flex-col items-center mb-2">
                                        <img id="tempo-fala-logo-partido" src="" alt="Logo Partido" class="h-8 mb-1 hidden">
                                        <div id="tempo-fala-partido" class="text-base font-bold text-blue-700 dark:text-blue-200 text-center"></div>
                                    </div>
                                    <div id="tempo-fala-cargo" class="text-xs text-gray-600 dark:text-gray-300 mb-2 text-center"></div>
                                    <div class="flex items-center justify-center w-full mb-2">
                                        <span id="tempo-fala-restante" class="text-5xl md:text-6xl font-mono font-extrabold text-gray-900 dark:text-white tracking-widest">00:00</span>
                                    </div>
                                    <div class="flex items-center justify-center gap-2 mb-1">
                                        <span class="inline-block w-3 h-3 rounded-full" id="tempo-fala-status-dot"></span>
                                        <span id="tempo-fala-status" class="text-base font-bold"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-box rounded-2xl p-12 text-center fade-in">
                    <div class="text-5xl text-gray-400 mb-4">📋</div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Nenhuma Votação Ativa</h2>
                    <p class="text-gray-500">Aguardando abertura de nova votação...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Função para formatar segundos em mm:ss
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
                    // Atualizar totais
                    const totalGeralEl = document.getElementById('total-geral');
                    const totalSimEl = document.getElementById('total-sim');
                    const totalNaoEl = document.getElementById('total-nao');
                    const totalGeralAntigo = parseInt(totalGeralEl?.textContent) || 0;
                    const totalSimAntigo = parseInt(totalSimEl?.textContent) || 0;
                    const totalNaoAntigo = parseInt(totalNaoEl?.textContent) || 0;
                    if (totalGeralEl) animarNumero(totalGeralEl, totalGeralAntigo, resultados.total_geral);
                    if (totalSimEl) animarNumero(totalSimEl, totalSimAntigo, resultados.total_sim);
                    if (totalNaoEl) animarNumero(totalNaoEl, totalNaoAntigo, resultados.total_nao);

                    // Atualizar Grid de Eleitores via AJAX
                    const grid = document.getElementById('grid-eleitores');
                    if (grid) {
                        // Montar lista de eleitores (cadastrados + quem votou)
                        let eleitores = (data.dados.eleitores && data.dados.eleitores.length > 0) ? data.dados.eleitores : [];
                        // Se não houver eleitores cadastrados, usar os que votaram
                        if (eleitores.length === 0) {
                            eleitores = resultados.votos.map(v => ({
                                id: null,
                                nome: v.nome,
                                cargo: v.cargo,
                                foto: v.foto,
                                cpf: v.cpf
                            }));
                        }
                        // Map de votos por CPF
                        const votosMap = {};
                        resultados.votos.forEach(v => {
                            const cpfLimpo = v.cpf.replace(/\D/g, '');
                            votosMap[cpfLimpo] = v;
                        });
                        // Montar HTML
                        let html = '';
                        eleitores.forEach(eleitor => {
                            const cpf_limpo = (eleitor.cpf || '').replace(/\D/g, '');
                            const votou = !!votosMap[cpf_limpo];
                            const voto_info = votou ? votosMap[cpf_limpo] : null;
                            const status_voto = votou ? (voto_info.voto === 'sim' ? 'sim' : 'nao') : 'ausente';
                            const status_texto = votou ? (voto_info.voto === 'sim' ? 'A FAVOR' : 'CONTRA') : 'AUSENTE';
                            html += `<div class="voter-card rounded-xl p-4 fade-in" data-cpf="${cpf_limpo}">
                                <div class="flex items-center gap-3 mb-3">
                                    ${eleitor.foto ? `<img src="../uploads/${eleitor.foto}" alt="${eleitor.nome}" class="w-14 h-14 rounded-full object-cover border-2 border-gray-300">` : `<div class="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-400"><span class="text-gray-700 text-lg font-bold">${(eleitor.nome||'').toUpperCase().charAt(0)}</span></div>`}
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-800 truncate">${eleitor.nome || ''}</div>
                                        ${eleitor.cargo ? `<div class="text-xs text-gray-500 truncate">${eleitor.cargo}</div>` : ''}
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="status-bar ${status_voto} mb-2" data-role="status-bar"></div>
                                    <div class="text-xs font-semibold text-gray-700 text-center status-text" data-role="status-text">${status_texto}</div>
                                </div>
                            </div>`;
                        });
                        grid.innerHTML = html;
                    }
                }
            } catch (error) {
                console.error('Erro ao atualizar resultados:', error);
            }
        }
                        partidoDiv.textContent = data.partido;
                    } else {
                        partidoDiv.textContent = '';
                    }
                    if (data.logo_partido) {
                        logoImg.src = '../uploads/' + data.logo_partido;
                        logoImg.classList.remove('hidden');
                    } else {
                        logoImg.classList.add('hidden');
                    }
                } else {
                    conteudo.style.display = 'none';
                    loading.style.display = '';
                    loading.textContent = 'Nenhum tempo de fala ativo.';
                }
            } catch (e) {
                conteudo.style.display = 'none';
                loading.style.display = '';
                loading.textContent = 'Erro ao carregar tempo de fala.';
                debugBox.style.display = '';
                debugJson.textContent = '';
                debugErro.textContent = 'Erro de requisição: ' + e;
            }
        }

        // Atualizar tempo de fala a cada 1s
        setInterval(atualizarTempoFala, 1000);
        setTimeout(atualizarTempoFala, 200);
                function alternarModoEscuro() {
                    const html = document.documentElement;
                    const dark = html.classList.toggle('dark');
                    localStorage.setItem('darkMode', dark ? '1' : '0');
                    document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
                    document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
                }
                // Atualizar ícone ao carregar
                document.addEventListener('DOMContentLoaded', function() {
                    const dark = document.documentElement.classList.contains('dark');
                    document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
                    document.getElementById('texto-modo').textContent = dark ? 'Modo Claro' : 'Modo Escuro';
                });
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
                    
                    // Atualizar Grid de Eleitores sem recarregar
                    const grid = document.getElementById('grid-eleitores');
                    if (grid) {
                        const votosMap = {};
                        resultados.votos.forEach(v => {
                            const cpfLimpo = v.cpf.replace(/\D/g, '');
                            votosMap[cpfLimpo] = v;
                        });

                        const cards = document.querySelectorAll('.voter-card');
                        cards.forEach(card => {
                            const cpf = card.getAttribute('data-cpf');
                            const statusBar = card.querySelector('[data-role="status-bar"]');
                            const statusText = card.querySelector('[data-role="status-text"]');
                            
                            if (cpf && votosMap[cpf]) {
                                const voto = votosMap[cpf].voto;
                                statusBar.classList.remove('ausente', 'sim', 'nao');
                                statusBar.classList.add(voto);
                                statusText.textContent = voto === 'sim' ? 'A FAVOR' : 'CONTRA';
                            } else if (cpf) {
                                statusBar.classList.remove('sim', 'nao');
                                statusBar.classList.add('ausente');
                                statusText.textContent = 'AUSENTE';
                            }
                        });
                        
                        // Se houver votos de quem não tem card (ex: novo cadastro), pode-se atualizar a grid via AJAX futuramente.
                        // Por ora, não recarrega a página inteira para evitar refresh geral.
                        // const temVotoSemCard = resultados.votos.some(v => {
                        //     const cpfLimpo = v.cpf.replace(/\D/g, '');
                        //     return !document.querySelector(`.voter-card[data-cpf="${cpfLimpo}"]`);
                        // });
                        // if (temVotoSemCard) {
                        //     location.reload();
                        // }
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
