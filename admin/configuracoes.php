<?php
require_once '../config/database.php';
require_once '../config/functions.php';
verificarAdmin();

$configFile = __DIR__ . '/../config/settings.json';
$mensagem = '';
$tipo_mensagem = '';

// Valores padrão
$settings = [
    'sistema_nome' => 'VotaCâmara',
    'sistema_cor' => 'blue',
    'modo_escuro' => false
];

// Carregar configurações existentes
if (file_exists($configFile)) {
    $savedSettings = json_decode(file_get_contents($configFile), true);
    if ($savedSettings) {
        $settings = array_merge($settings, $savedSettings);
    }
}

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['sistema_nome'] = sanitizar($_POST['sistema_nome'] ?? 'VotaCâmara');
    $settings['sistema_cor'] = sanitizar($_POST['sistema_cor'] ?? 'blue');
    $settings['modo_escuro'] = isset($_POST['modo_escuro']);

    if (file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $mensagem = 'Configurações salvas com sucesso!';
        $tipo_mensagem = 'success';
        registrarLog('atualizar_configuracoes', $settings);
    } else {
        $mensagem = 'Erro ao salvar configurações.';
        $tipo_mensagem = 'error';
    }
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8">
        <!-- Cabeçalho -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Configurações</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Personalize o comportamento e a aparência do sistema.</p>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="p-4 rounded-lg mb-6 <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-blue-100/20 dark:shadow-black/20 overflow-hidden border border-gray-100 dark:border-gray-700 p-8">
            <form method="POST" class="space-y-6 max-w-2xl">
                <div>
                    <label for="sistema_nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome do Sistema</label>
                    <input type="text" id="sistema_nome" name="sistema_nome" value="<?= htmlspecialchars($settings['sistema_nome']) ?>" 
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="sistema_cor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cor Principal</label>
                    <select id="sistema_cor" name="sistema_cor" 
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="blue" <?= $settings['sistema_cor'] === 'blue' ? 'selected' : '' ?>>Azul</option>
                        <option value="green" <?= $settings['sistema_cor'] === 'green' ? 'selected' : '' ?>>Verde</option>
                        <option value="red" <?= $settings['sistema_cor'] === 'red' ? 'selected' : '' ?>>Vermelho</option>
                        <option value="purple" <?= $settings['sistema_cor'] === 'purple' ? 'selected' : '' ?>>Roxo</option>
                    </select>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="modo_escuro" name="modo_escuro" value="1" <?= $settings['modo_escuro'] ? 'checked' : '' ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="modo_escuro" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                        Forçar Modo Escuro por padrão
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full md:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg shadow-blue-600/30 transition-all transform hover:scale-[1.02]">
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
</body>
</html>
