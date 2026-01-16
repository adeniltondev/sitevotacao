<?php
echo 'INICIO'; exit;
/**
 * Painel Administrativo - Dashboard
 */

require_once '../config/database.php';
try {
    require_once '../config/functions.php';
    echo 'APOS FUNCTIONS'; exit;
} catch (Throwable $e) {
    echo 'ERRO FUNCTIONS: ' . $e->getMessage(); exit;
}

verificarAdminPerfil('admin');

// Ativar exibição de erros PHP para diagnóstico de erro 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar_votacao') {
        $titulo = sanitizar($_POST['titulo'] ?? '');
        $descricao = sanitizar($_POST['descricao'] ?? '');
        
        if (!empty($titulo)) {
            $stmt = $pdo->prepare("INSERT INTO votacoes (titulo, descricao, status) VALUES (?, ?, 'encerrada')");
            $stmt->execute([$titulo, $descricao]);
            header('Location: dashboard.php?sucesso=votacao_criada');
            exit;
        }
    }
    
    if ($acao === 'abrir_votacao') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        
        // Fecha todas as outras votações
        $pdo->exec("UPDATE votacoes SET status = 'encerrada', encerrada_em = NOW() WHERE status = 'aberta'");
        
        // Abre a votação selecionada
        $stmt = $pdo->prepare("UPDATE votacoes SET status = 'aberta', aberta_em = NOW() WHERE id = ?");
        $stmt->execute([$votacao_id]);
        
        header('Location: dashboard.php?sucesso=votacao_aberta');
        exit;
    }
    
    if ($acao === 'encerrar_votacao') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE votacoes SET status = 'encerrada', encerrada_em = NOW() WHERE id = ?");
        $stmt->execute([$votacao_id]);
        
        header('Location: dashboard.php?sucesso=votacao_encerrada');
        exit;
    }
    
    if ($acao === 'resetar_votos') {
        $votacao_id = intval($_POST['votacao_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM votos WHERE votacao_id = ?");
        $stmt->execute([$votacao_id]);
        
        header('Location: dashboard.php?sucesso=votos_resetados');
        exit;
    }
}

// Buscar votação ativa
$votacao_ativa = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();

// Buscar todas as votações
$votacoes = $pdo->query("SELECT * FROM votacoes ORDER BY criada_em DESC")->fetchAll();

// Buscar todos os votos da votação ativa
$votos = [];
if ($votacao_ativa) {
    $votos = $pdo->prepare("SELECT * FROM votos WHERE votacao_id = ? ORDER BY criado_em DESC");
    $votos->execute([$votacao_ativa['id']]);
    $votos = $votos->fetchAll();
    
    // Contar votos
    $total_sim = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'sim'");
    $total_sim->execute([$votacao_ativa['id']]);
    $total_sim = $total_sim->fetch()['total'];
    
    $total_nao = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ? AND voto = 'nao'");
    $total_nao->execute([$votacao_ativa['id']]);
    $total_nao = $total_nao->fetch()['total'];
    
    $total_geral = $total_sim + $total_nao;
    $percentual_sim = $total_geral > 0 ? round(($total_sim / $total_geral) * 100, 1) : 0;
    $percentual_nao = $total_geral > 0 ? round(($total_nao / $total_geral) * 100, 1) : 0;
} else {
    $total_sim = $total_nao = $total_geral = 0;
    $percentual_sim = $percentual_nao = 0;
}

$sucesso = $_GET['sucesso'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/heroicons@1.0.6/dist/heroicons.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div class="flex flex-col md:flex-row md:items-center gap-4 flex-1">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-600">Câmara Municipal</h1>
                        <div class="text-xs text-gray-500 font-semibold">Painel Administrativo</div>
                    </div>
                    <div class="hidden md:block border-l h-8 mx-4"></div>
                    <div>
                        <div class="text-sm text-gray-700 font-semibold">Sessão atual:</div>
                        <div class="text-base text-gray-900 font-bold">
                            <?= $votacao_ativa ? htmlspecialchars($votacao_ativa['titulo']) : 'Nenhuma sessão aberta' ?>
                        </div>
                    </div>
                    <div class="hidden md:block border-l h-8 mx-4"></div>
                    <div>
                        <div class="text-sm text-gray-700 font-semibold">Status da votação:</div>
                        <span class="text-xs font-bold px-2 py-1 rounded <?= $votacao_ativa ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>">
                            <?= $votacao_ativa ? 'ABERTA' : 'ENCERRADA' ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-800">
                            <?= htmlspecialchars($_SESSION['admin_nome']) ?>
                        </div>
                        <div class="text-xs text-gray-500" id="relogio"></div>
                    </div>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Sair
                    </a>
                </div>
            </div>
        </div>
        <script>
        function atualizarRelogio() {
            const agora = new Date();
            const data = agora.toLocaleDateString('pt-BR');
            const hora = agora.toLocaleTimeString('pt-BR');
            document.getElementById('relogio').textContent = `${data} ${hora}`;
        }
        setInterval(atualizarRelogio, 1000);
        atualizarRelogio();
        </script>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Indicadores do Dashboard -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
                        <div class="text-gray-500 text-sm">Total de Vereadores</div>
                        <div class="text-3xl font-bold text-blue-600">
                            <?php
                            $total_vereadores = $pdo->query("SELECT COUNT(*) FROM eleitores")->fetchColumn();
                            echo $total_vereadores;
                            ?>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
                        <div class="text-gray-500 text-sm">Total de Votos</div>
                        <div class="text-3xl font-bold text-green-600">
                            <?= $total_geral ?>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
                        <div class="text-gray-500 text-sm">Não Votaram</div>
                        <div class="text-3xl font-bold text-red-600">
                            <?php
                            $nao_votaram = $total_vereadores - $total_geral;
                            echo $nao_votaram >= 0 ? $nao_votaram : 0;
                            ?>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
                        <div class="text-gray-500 text-sm">Última Ação</div>
                        <div class="text-xs text-gray-700 text-center">
                            <?php
                            $log = @file_get_contents(__DIR__ . '/../logs/auditoria.log');
                            $ultima_acao = '';
                            if ($log) {
                                $linhas = explode("\n", trim($log));
                                $ultima = end($linhas);
                                if ($ultima) {
                                    $registro = json_decode($ultima, true);
                                    if ($registro) {
                                        $ultima_acao = $registro['data'] . ' - ' . $registro['acao'];
                                    }
                                }
                            }
                            echo $ultima_acao ?: 'Sem ações registradas';
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Pizza (SIM/NÃO) -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Distribuição dos Votos</h2>
                    <canvas id="graficoVotos" width="400" height="180"></canvas>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const ctx = document.getElementById('graficoVotos').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: ['SIM', 'NÃO'],
                                datasets: [{
                                    data: [<?= $total_sim ?>, <?= $total_nao ?>],
                                    backgroundColor: ['#22c55e', '#ef4444'],
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { position: 'bottom' }
                                }
                            }
                        });
                    </script>
                </div>
        <?php if ($sucesso): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php
                $mensagens = [
                    'votacao_criada' => 'Votação criada com sucesso!',
                    'votacao_aberta' => 'Votação aberta com sucesso!',
                    'votacao_encerrada' => 'Votação encerrada com sucesso!',
                    'votos_resetados' => 'Votos resetados com sucesso!'
                ];
                echo $mensagens[$sucesso] ?? 'Operação realizada com sucesso!';
                ?>
            </div>
        <?php endif; ?>

        <!-- Status da Votação Ativa -->
        <?php if ($votacao_ativa): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Votação Ativa</h2>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        ABERTA
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2"><?= htmlspecialchars($votacao_ativa['titulo']) ?></h3>
                <?php if ($votacao_ativa['descricao']): ?>
                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($votacao_ativa['descricao']) ?></p>
                <?php endif; ?>
                
                <!-- Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600">Total de Votos</div>
                        <div class="text-2xl font-bold text-blue-600"><?= $total_geral ?></div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600">SIM</div>
                        <div class="text-2xl font-bold text-green-600"><?= $total_sim ?> (<?= $percentual_sim ?>%)</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600">NÃO</div>
                        <div class="text-2xl font-bold text-red-600"><?= $total_nao ?> (<?= $percentual_nao ?>%)</div>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <form method="POST" action="" class="inline">
                        <input type="hidden" name="acao" value="encerrar_votacao">
                        <input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                            Encerrar Votação
                        </button>
                    </form>
                    <a href="../painel/resultados.php" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Ver Painel de Resultados
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                Nenhuma votação aberta no momento.
            </div>
        <?php endif; ?>

        <!-- Gerenciar Eleitores -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Gerenciar Eleitores</h2>
                <a href="eleitores.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Cadastrar Eleitores
                </a>
            </div>
            <p class="text-gray-600">Cadastre os eleitores que poderão votar nas votações.</p>
        </div>

        <!-- Criar Nova Votação -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Criar Nova Votação</h2>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="criar_votacao">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                <div class="mb-4">
                    <label for="titulo" class="block text-gray-700 font-medium mb-2">Título *</label>
                    <input 
                        type="text" 
                        id="titulo" 
                        name="titulo" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Aprovação do Projeto de Lei 123/2024"
                    >
                </div>
                <div class="mb-4">
                    <label for="descricao" class="block text-gray-700 font-medium mb-2">Descrição</label>
                    <textarea 
                        id="descricao" 
                        name="descricao" 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Descrição opcional da votação"
                    ></textarea>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    Criar Votação
                </button>
            </form>
        </div>

        <!-- Lista de Votações -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Todas as Votações</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Criada em</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($votacoes as $votacao): ?>
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ?");
                            $stmt->execute([$votacao['id']]);
                            $total_votos = $stmt->fetch()['total'];
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?= $votacao['id'] ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($votacao['titulo']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($votacao['status'] === 'aberta'): ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">ABERTA</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">ENCERRADA</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($votacao['criada_em'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <?php if ($votacao['status'] === 'encerrada'): ?>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="acao" value="abrir_votacao">
                                                <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900">Abrir</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Tem certeza que deseja resetar os votos?')">
                                            <input type="hidden" name="acao" value="resetar_votos">
                                            <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                            <button type="submit" class="text-orange-600 hover:text-orange-900">Resetar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lista de Votantes -->
        <?php if ($votacao_ativa && count($votos) > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Votantes (<?= count($votos) ?>)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($votos as $voto): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center gap-4 mb-2">
                                <?php if ($voto['foto']): ?>
                                    <img 
                                        src="../uploads/<?= htmlspecialchars($voto['foto']) ?>" 
                                        alt="Foto"
                                        class="w-16 h-16 rounded-full object-cover"
                                    >
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-600 text-xl"><?= strtoupper(substr($voto['nome'], 0, 1)) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($voto['nome']) ?></div>
                                    <?php if ($voto['cargo']): ?>
                                        <div class="text-sm text-gray-600"><?= htmlspecialchars($voto['cargo']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-sm text-gray-600"><?= formatarCPF($voto['cpf']) ?></span>
                                <span class="<?= $voto['voto'] === 'sim' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-2 py-1 rounded text-sm font-medium">
                                    <?= strtoupper($voto['voto']) ?>
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= date('d/m/Y H:i', strtotime($voto['criado_em'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
