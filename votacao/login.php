<?php
/**
 * Login de Eleitores
 */
require_once '../config/database.php';
require_once '../config/functions.php';

iniciarSessao();

// Se já estiver logado, redireciona para votação
if (isset($_SESSION['eleitor_id'])) {
    header('Location: index.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    
    if (empty($cpf)) {
        $mensagem = 'Informe seu CPF';
        $tipo_mensagem = 'error';
    } elseif (!validarCPF($cpf)) {
        $mensagem = 'CPF inválido';
        $tipo_mensagem = 'error';
    } else {
        // Buscar eleitor pelo CPF
        $stmt = $pdo->prepare("SELECT * FROM eleitores WHERE cpf = ?");
        $stmt->execute([$cpf]);
        $eleitor = $stmt->fetch();
        
        if ($eleitor) {
            // Criar sessão do eleitor
            $_SESSION['eleitor_id'] = $eleitor['id'];
            $_SESSION['eleitor_cpf'] = $eleitor['cpf'];
            $_SESSION['eleitor_nome'] = $eleitor['nome'];
            $_SESSION['eleitor_cargo'] = $eleitor['cargo'];
            $_SESSION['eleitor_foto'] = $eleitor['foto'];
            
            header('Location: index.php');
            exit;
        } else {
            $mensagem = 'CPF não cadastrado. Entre em contato com o administrador.';
            $tipo_mensagem = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-600 mb-2">Sistema de Votação</h1>
            <p class="text-gray-600">Câmara Municipal</p>
        </div>

        <!-- Card de Login -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login do Eleitor</h2>

            <?php if ($mensagem): ?>
                <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400' ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="cpf" class="block text-gray-700 font-medium mb-2">CPF</label>
                    <input 
                        type="text" 
                        id="cpf" 
                        name="cpf" 
                        required
                        maxlength="14"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                        placeholder="000.000.000-00"
                        autofocus
                    >
                    <p class="text-sm text-gray-500 mt-1">Informe seu CPF para acessar o sistema</p>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-bold text-lg"
                >
                    Entrar
                </button>
            </form>
        </div>

        <div class="text-center mt-6 text-sm text-gray-500">
            <p>Você precisa estar cadastrado para votar</p>
        </div>
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
