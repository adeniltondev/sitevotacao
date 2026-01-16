<?php
/**
 * Página Pública de Votação
 */
require_once '../config/database.php';
require_once '../config/functions.php';

// Buscar votação ativa
$votacao = $pdo->query("SELECT * FROM votacoes WHERE status = 'aberta' LIMIT 1")->fetch();

$mensagem = '';
$tipo_mensagem = '';

if (isset($_GET['sucesso'])) {
    $mensagem = 'Voto registrado com sucesso!';
    $tipo_mensagem = 'success';
} elseif (isset($_GET['erro'])) {
    $mensagem = htmlspecialchars($_GET['erro']);
    $tipo_mensagem = 'error';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Votação - Câmara</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-600 mb-2">Sistema de Votação</h1>
            <p class="text-gray-600">Câmara Municipal</p>
        </div>

        <?php if (!$votacao): ?>
            <!-- Nenhuma votação ativa -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Nenhuma Votação Ativa</h2>
                <p class="text-gray-600">Não há votação aberta no momento.</p>
            </div>
        <?php else: ?>
            <!-- Votação Ativa -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($votacao['titulo']) ?></h2>
                        <?php if ($votacao['descricao']): ?>
                            <p class="text-gray-600"><?= htmlspecialchars($votacao['descricao']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-medium">
                        VOTAÇÃO ABERTA
                    </span>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário de Voto -->
                <form id="formVoto" method="POST" action="votar.php" class="space-y-6">
                    <input type="hidden" name="votacao_id" value="<?= $votacao['id'] ?>">
                    
                    <div>
                        <label for="cpf" class="block text-gray-700 font-medium mb-2">CPF *</label>
                        <input 
                            type="text" 
                            id="cpf" 
                            name="cpf" 
                            required
                            maxlength="14"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                            placeholder="000.000.000-00"
                        >
                        <p class="text-sm text-gray-500 mt-1">Informe seu CPF para votar</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-4">Seu Voto *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="voto" 
                                    value="sim" 
                                    required
                                    class="peer sr-only"
                                >
                                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center hover:bg-green-100 transition peer-checked:bg-green-500 peer-checked:border-green-600 peer-checked:text-white">
                                    <div class="text-4xl font-bold mb-2">SIM</div>
                                    <div class="text-sm">Aprovar</div>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="voto" 
                                    value="nao" 
                                    required
                                    class="peer sr-only"
                                >
                                <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6 text-center hover:bg-red-100 transition peer-checked:bg-red-500 peer-checked:border-red-600 peer-checked:text-white">
                                    <div class="text-4xl font-bold mb-2">NÃO</div>
                                    <div class="text-sm">Rejeitar</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-bold text-lg"
                    >
                        Confirmar Voto
                    </button>
                </form>
            </div>

            <!-- Link para resultados -->
            <div class="text-center">
                <a href="../painel/resultados.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Ver Resultados em Tempo Real →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
