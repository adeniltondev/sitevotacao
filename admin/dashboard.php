<?php

require_once '../config/database.php';
require_once '../config/functions.php';

verificarAdmin();

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
                            <!-- Todas as Votações (tabela compacta) -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-semibold">Todas as Votações</h2>
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Total: <?= count($votacoes) ?></span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Título</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Criada</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <?php foreach ($votacoes as $votacao): ?>
                                                <?php $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ?"); $stmt->execute([$votacao['id']]); $total_votos = $stmt->fetch()['total']; ?>
                                                <tr>
                                                    <td class="px-4 py-3 text-sm">#<?= $votacao['id'] ?></td>
                                                    <td class="px-4 py-3 text-sm"><?= htmlspecialchars($votacao['titulo']) ?></td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <?php if ($votacao['status'] === 'aberta'): ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium">ABERTA</span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium">ENCERRADA</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300"><?= date('d/m/Y H:i', strtotime($votacao['criada_em'])) ?></td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <div class="flex items-center gap-2">
                                                            <?php if ($votacao['status'] === 'encerrada'): ?>
                                                                <form method="POST" action="" class="inline">
                                                                    <input type="hidden" name="acao" value="abrir_votacao">
                                                                    <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                                    <button type="submit" class="text-green-600 hover:underline">Abrir</button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Tem certeza que deseja resetar os votos?')">
                                                                <input type="hidden" name="acao" value="resetar_votos">
                                                                <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                                <button type="submit" class="text-orange-600 hover:underline">Resetar</button>
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
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                    <h2 class="text-lg font-semibold mb-4">Votantes (<?= count($votos) ?>)</h2>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <?php foreach ($votos as $voto): ?>
                                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 flex flex-col">
                                                <div class="flex items-center gap-4 mb-3">
                                                    <?php if ($voto['foto']): ?>
                                                        <img src="../uploads/<?= htmlspecialchars($voto['foto']) ?>" alt="Foto" class="w-14 h-14 rounded-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-14 h-14 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-lg font-semibold text-gray-700 dark:text-gray-200"><?= strtoupper(substr($voto['nome'], 0, 1)) ?></div>
                                                    <?php endif; ?>
                                                    <div class="flex-1">
                                                        <div class="font-semibold"><?= htmlspecialchars($voto['nome']) ?></div>
                                                        <?php if ($voto['cargo']): ?>
                                                            <div class="text-sm text-gray-500 dark:text-gray-300"><?= htmlspecialchars($voto['cargo']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-sm">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full <?= $voto['voto'] === 'sim' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' ?> text-xs font-medium"><?= strtoupper($voto['voto']) ?></span>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-auto"><?= formatarCPF($voto['cpf']) ?> • <?= date('d/m/Y H:i', strtotime($voto['criado_em'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </main>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    // Dark mode toggle behavior
                    const darkToggle = document.getElementById('darkToggle');
                    const darkText = document.getElementById('darkText');
                    const iconLight = document.getElementById('iconLight');
                    const iconDark = document.getElementById('iconDark');
                    function updateDarkUI(){
                        const dark = document.documentElement.classList.contains('dark');
                        if(dark){ iconLight.classList.remove('hidden'); iconDark.classList.add('hidden'); darkText.textContent = 'Claro'; }
                        else { iconLight.classList.add('hidden'); iconDark.classList.remove('hidden'); darkText.textContent = 'Escuro'; }
                    }
                    darkToggle.addEventListener('click', ()=>{
                        const dark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('darkMode', dark ? '1' : '0');
                        updateDarkUI();
                    });
                    updateDarkUI();

                    // Chart
                    const ctx = document.getElementById('graficoVotos')?.getContext('2d');
                    if(ctx){
                        new Chart(ctx, {
                            type: 'pie',
                            data: { labels: ['SIM','NÃO'], datasets: [{ data: [<?= $total_sim ?>, <?= $total_nao ?>], backgroundColor: ['#16a34a','#ef4444'] }] },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                        });
                    }
                </script>
            </body>
            </html>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 flex flex-col items-center border-t-4 border-red-600 dark:border-red-400">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-red-600"></span>
                            <span class="text-gray-500 text-sm">NÃO (%)</span>
                        </div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            <?= $percentual_nao ?>%
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Pizza (SIM/NÃO) -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8 flex flex-col md:flex-row gap-8 items-center">
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">Distribuição dos Votos</h2>
                        <canvas id="graficoVotos" width="400" height="180"></canvas>
                    </div>
                    <div class="flex-1 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-green-600"></span>
                            <span class="text-gray-700 dark:text-gray-200">SIM: <span class="font-bold text-green-600 dark:text-green-400"><?= $total_sim ?></span> (<?= $percentual_sim ?>%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-red-600"></span>
                            <span class="text-gray-700 dark:text-gray-200">NÃO: <span class="font-bold text-red-600 dark:text-red-400"><?= $total_nao ?></span> (<?= $percentual_nao ?>%)</span>
                        </div>
                        <div class="flex items-center gap-2 mt-4">
                            <span class="inline-block w-3 h-3 rounded-full bg-gray-400"></span>
                            <span class="text-gray-700 dark:text-gray-200">Total: <span class="font-bold text-gray-800 dark:text-gray-100"><?= $total_geral ?></span></span>
                        </div>
                    </div>
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
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
                        <?php $mensagens = [ 'votacao_criada' => 'Votação criada com sucesso!', 'votacao_aberta' => 'Votação aberta com sucesso!', 'votacao_encerrada' => 'Votação encerrada com sucesso!', 'votos_resetados' => 'Votos resetados com sucesso!' ]; echo $mensagens[$sucesso] ?? 'Operação realizada com sucesso!'; ?>
                    </div>
                <?php endif; ?>

                <!-- Seção de Votação Ativa -->
                <?php if ($votacao_ativa): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 border-l-4 border-green-600 dark:border-green-400">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Votação Ativa</h2>
                            <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium">ABERTA</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2"><?= htmlspecialchars($votacao_ativa['titulo']) ?></h3>
                        <?php if ($votacao_ativa['descricao']): ?>
                            <p class="text-gray-600 dark:text-gray-300 mb-4"><?= htmlspecialchars($votacao_ativa['descricao']) ?></p>
                        <?php endif; ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                                <div class="text-sm text-gray-600 dark:text-gray-200">Total de Votos</div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= $total_geral ?></div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                                <div class="text-sm text-gray-600 dark:text-gray-200">SIM</div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?= $total_sim ?> (<?= $percentual_sim ?>%)</div>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
                                <div class="text-sm text-gray-600 dark:text-gray-200">NÃO</div>
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $total_nao ?> (<?= $percentual_nao ?>%)</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="acao" value="encerrar_votacao">
                                <input type="hidden" name="votacao_id" value="<?= $votacao_ativa['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">Encerrar Votação</button>
                            </form>
                            <a href="../painel/resultados.php" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Ver Painel de Resultados</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-400 text-yellow-700 dark:text-yellow-200 px-4 py-3 rounded mb-6">
                        Nenhuma votação aberta no momento.
                    </div>
                <?php endif; ?>

                <!-- Gerenciar Eleitores -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 border-l-4 border-blue-600 dark:border-blue-400">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Gerenciar Eleitores</h2>
                        <a href="eleitores.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Cadastrar Eleitores</a>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300">Cadastre os eleitores que poderão votar nas votações.</p>
                </div>

                <!-- Criar Nova Votação -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 border-l-4 border-green-600 dark:border-green-400">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Criar Nova Votação</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="criar_votacao">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                        <div class="mb-4">
                            <label for="titulo" class="block text-gray-700 dark:text-gray-200 font-medium mb-2">Título *</label>
                            <input type="text" id="titulo" name="titulo" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: Aprovação do Projeto de Lei 123/2024">
                        </div>
                        <div class="mb-4">
                            <label for="descricao" class="block text-gray-700 dark:text-gray-200 font-medium mb-2">Descrição</label>
                            <textarea id="descricao" name="descricao" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Descrição opcional da votação"></textarea>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Criar Votação</button>
                    </form>
                </div>

                <!-- Todas as Votações -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 border-l-4 border-gray-600 dark:border-gray-400">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Todas as Votações</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Título</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Criada em</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($votacoes as $votacao): ?>
                                    <?php $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE votacao_id = ?"); $stmt->execute([$votacao['id']]); $total_votos = $stmt->fetch()['total']; ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?= $votacao['id'] ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?= htmlspecialchars($votacao['titulo']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($votacao['status'] === 'aberta'): ?>
                                                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full text-xs font-medium">ABERTA</span>
                                            <?php else: ?>
                                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1 rounded-full text-xs font-medium">ENCERRADA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= date('d/m/Y H:i', strtotime($votacao['criada_em'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <?php if ($votacao['status'] === 'encerrada'): ?>
                                                    <form method="POST" action="" class="inline">
                                                        <input type="hidden" name="acao" value="abrir_votacao">
                                                        <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                        <button type="submit" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-200">Abrir</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="" class="inline" onsubmit="return confirm('Tem certeza que deseja resetar os votos?')">
                                                    <input type="hidden" name="acao" value="resetar_votos">
                                                    <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                                    <button type="submit" class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-200">Resetar</button>
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
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-gray-600 dark:border-gray-400">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Votantes (<?= count($votos) ?>)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($votos as $voto): ?>
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                                    <div class="flex items-center gap-4 mb-2">
                                        <?php if ($voto['foto']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($voto['foto']) ?>" alt="Foto" class="w-16 h-16 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-16 h-16 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center">
                                                <span class="text-gray-600 dark:text-gray-300 text-xl"><?= strtoupper(substr($voto['nome'], 0, 1)) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($voto['nome']) ?></div>
                                            <?php if ($voto['cargo']): ?>
                                                <div class="text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($voto['cargo']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-300"><?= formatarCPF($voto['cpf']) ?></span>
                                        <span class="<?= $voto['voto'] === 'sim' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' ?> px-2 py-1 rounded text-sm font-medium">
                                            <?= strtoupper($voto['voto']) ?>
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <?= date('d/m/Y H:i', strtotime($voto['criado_em'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
