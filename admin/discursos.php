<?php
/**
 * Gerenciamento de Tempo de Fala (Discursos)
 */
require_once '../config/functions.php';
verificarAdmin();
require_once '../config/database.php';

// Gerar token CSRF
$csrf_token = gerarCSRFToken();

$mensagem = '';
$tipo_mensagem = '';

// Buscar estado atual
$discurso = $pdo->query("SELECT d.*, e.nome, e.foto, e.logo_partido FROM controle_discurso d LEFT JOIN eleitores e ON d.eleitor_id = e.id WHERE d.id = 1")->fetch();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRFToken()) {
        $mensagem = 'Erro de segurança: Token CSRF inválido.';
        $tipo_mensagem = 'error';
    } else {
        $acao = $_POST['acao'] ?? '';
        
        try {
            if ($acao === 'iniciar') {
                $eleitor_id = intval($_POST['eleitor_id']);
                $minutos = intval($_POST['minutos']);
                $segundos = $minutos * 60;
                
                $stmt = $pdo->prepare("UPDATE controle_discurso SET eleitor_id = ?, inicio = NOW(), duracao_segundos = ?, status = 'ativo', tempo_restante_pausa = 0 WHERE id = 1");
                $stmt->execute([$eleitor_id, $segundos]);
                
                $mensagem = 'Discurso iniciado!';
                $tipo_mensagem = 'success';
                
            } elseif ($acao === 'parar') {
                $pdo->exec("UPDATE controle_discurso SET status = 'encerrado' WHERE id = 1");
                $mensagem = 'Discurso encerrado!';
                $tipo_mensagem = 'success';
                
            } elseif ($acao === 'pausar') {
                if ($discurso && $discurso['status'] === 'ativo') {
                    // Calcular tempo restante
                    $inicio = strtotime($discurso['inicio']);
                    $agora = time();
                    $decorrido = $agora - $inicio;
                    $restante = max(0, $discurso['duracao_segundos'] - $decorrido);
                    
                    $stmt = $pdo->prepare("UPDATE controle_discurso SET status = 'pausado', tempo_restante_pausa = ? WHERE id = 1");
                    $stmt->execute([$restante]);
                    
                    $mensagem = 'Discurso pausado!';
                    $tipo_mensagem = 'success';
                }
                
            } elseif ($acao === 'retomar') {
                if ($discurso && $discurso['status'] === 'pausado') {
                    $restante = $discurso['tempo_restante_pausa'];
                    
                    $stmt = $pdo->prepare("UPDATE controle_discurso SET inicio = NOW(), duracao_segundos = ?, status = 'ativo' WHERE id = 1");
                    $stmt->execute([$restante]);
                    
                    $mensagem = 'Discurso retomado!';
                    $tipo_mensagem = 'success';
                }
                
            } elseif ($acao === 'adicionar_tempo') {
                $segundos_extra = intval($_POST['segundos']);
                if ($discurso && $discurso['status'] === 'ativo') {
                    $stmt = $pdo->prepare("UPDATE controle_discurso SET duracao_segundos = duracao_segundos + ? WHERE id = 1");
                    $stmt->execute([$segundos_extra]);
                    $mensagem = 'Tempo adicionado!';
                    $tipo_mensagem = 'success';
                }
            }
            
            // Recarregar dados após alteração
            $discurso = $pdo->query("SELECT d.*, e.nome, e.foto, e.logo_partido FROM controle_discurso d LEFT JOIN eleitores e ON d.eleitor_id = e.id WHERE d.id = 1")->fetch();
            
        } catch (PDOException $e) {
            $mensagem = 'Erro ao processar: ' . $e->getMessage();
            $tipo_mensagem = 'error';
        }
    }
}

// Buscar eleitores para o select
$eleitores = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();

$page_title = 'Controle de Discursos';
require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8">
        <!-- Cabeçalho -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Controle de Tempo de Fala</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Gerencie o tempo de discurso dos vereadores na sessão.</p>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="p-4 rounded-lg mb-6 <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Card de Controle -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6">Iniciar Novo Discurso</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="acao" value="iniciar">
                    
                    <div>
                        <label for="eleitor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vereador</label>
                        <select name="eleitor_id" id="eleitor_id" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">Selecione um vereador...</option>
                            <?php foreach ($eleitores as $eleitor): ?>
                                <option value="<?= $eleitor['id'] ?>" <?= ($discurso['eleitor_id'] == $eleitor['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($eleitor['nome']) ?> (<?= htmlspecialchars($eleitor['partido'] ?? 'Vereador') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tempo de Fala</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" onclick="setTempo(3)" class="btn-tempo px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-center transition-all focus:ring-2 ring-blue-500">
                                <span class="block text-lg font-bold text-gray-800 dark:text-white">3 min</span>
                            </button>
                            <button type="button" onclick="setTempo(5)" class="btn-tempo px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-center transition-all focus:ring-2 ring-blue-500">
                                <span class="block text-lg font-bold text-gray-800 dark:text-white">5 min</span>
                            </button>
                            <button type="button" onclick="setTempo(10)" class="btn-tempo px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-center transition-all focus:ring-2 ring-blue-500">
                                <span class="block text-lg font-bold text-gray-800 dark:text-white">10 min</span>
                            </button>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <input type="number" name="minutos" id="input_minutos" required min="1" value="5" class="w-24 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <span class="text-gray-500">minutos (personalizado)</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-600/30 transition-all transform hover:scale-[1.02]">
                        INICIAR DISCURSO
                    </button>
                </form>
            </div>
            
            <!-- Status Atual -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full <?= $discurso['status'] === 'ativo' ? 'bg-green-500 animate-pulse' : ($discurso['status'] === 'pausado' ? 'bg-yellow-500' : 'bg-gray-400') ?>"></span>
                        Status Atual: <span class="uppercase text-blue-600 dark:text-blue-400 ml-1"><?= $discurso['status'] ?></span>
                    </h2>
                    
                    <?php if ($discurso['status'] !== 'encerrado' && $discurso['nome']): ?>
                        <div class="text-center py-8">
                            <?php if ($discurso['foto']): ?>
                                <img src="../uploads/<?= htmlspecialchars($discurso['foto']) ?>" alt="" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 ring-4 ring-blue-100 dark:ring-blue-900">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full bg-blue-100 dark:bg-blue-900 mx-auto mb-4 flex items-center justify-center text-4xl font-bold text-blue-600 dark:text-blue-300">
                                    <?= strtoupper(substr($discurso['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($discurso['nome']) ?></h3>
                            <?php if ($discurso['logo_partido']): ?>
                                <img src="../uploads/<?= htmlspecialchars($discurso['logo_partido']) ?>" class="h-8 mx-auto mt-2 object-contain">
                            <?php endif; ?>
                            
                            <!-- Timer Preview (Simulado via JS) -->
                            <div class="text-5xl font-mono font-bold text-gray-900 dark:text-white mt-6" id="timer-display">
                                --:--
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-400">
                            <svg class="w-20 h-20 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            <p class="text-lg">Nenhum orador no momento.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($discurso['status'] !== 'encerrado'): ?>
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <?php if ($discurso['status'] === 'ativo'): ?>
                            <form method="POST" action="" class="w-full">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="acao" value="pausar">
                                <button type="submit" class="w-full py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl transition-all">
                                    PAUSAR
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="" class="w-full">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="acao" value="retomar">
                                <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all">
                                    RETOMAR
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="w-full" onsubmit="return confirm('Encerrar discurso?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="acao" value="parar">
                            <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all">
                                ENCERRAR
                            </button>
                        </form>
                        
                        <form method="POST" action="" class="col-span-2 mt-2">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="acao" value="adicionar_tempo">
                            <input type="hidden" name="segundos" value="60">
                            <button type="submit" class="w-full py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold rounded-lg transition-all">
                                + 1 Minuto
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function setTempo(min) {
    document.getElementById('input_minutos').value = min;
}

// Timer Script (para preview)
<?php if ($discurso['status'] === 'ativo'): ?>
    const inicio = new Date("<?= $discurso['inicio'] ?>").getTime();
    const duracao = <?= $discurso['duracao_segundos'] ?> * 1000;
    const fim = inicio + duracao;
    
    function updateTimer() {
        const agora = new Date().getTime(); // Browser time, might differ slightly from server
        // To be precise we should sync with server time, but for admin preview this is okay
        // Actually, let's use the difference calculated from PHP to be safer or just countdown
        
        let restante = fim - agora;
        // Correction for timezone offset if needed, but easier:
        // Use PHP calculated remaining seconds
        
    }
    
    // Better Approach: Pass remaining seconds from PHP
    <?php 
        $agora = time();
        $inicio_ts = strtotime($discurso['inicio']);
        $decorrido = $agora - $inicio_ts;
        $restante_inicial = max(0, $discurso['duracao_segundos'] - $decorrido);
    ?>
    
    let restanteSegundos = <?= $restante_inicial ?>;
    
    function startTimer() {
        const display = document.getElementById('timer-display');
        
        const interval = setInterval(() => {
            if (restanteSegundos <= 0) {
                clearInterval(interval);
                display.textContent = "00:00";
                display.classList.add('text-red-600');
                return;
            }
            
            restanteSegundos--;
            
            const m = Math.floor(restanteSegundos / 60);
            const s = restanteSegundos % 60;
            display.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            
            if (restanteSegundos < 30) {
                display.classList.add('text-red-500');
                display.classList.add('animate-pulse');
            }
        }, 1000);
        
        // Initial render
        const m = Math.floor(restanteSegundos / 60);
        const s = restanteSegundos % 60;
        display.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    
    startTimer();

<?php elseif ($discurso['status'] === 'pausado'): ?>
    let restanteSegundos = <?= $discurso['tempo_restante_pausa'] ?>;
    const display = document.getElementById('timer-display');
    const m = Math.floor(restanteSegundos / 60);
    const s = restanteSegundos % 60;
    display.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    display.classList.add('text-yellow-500');
<?php endif; ?>
</script>
</body>
</html>