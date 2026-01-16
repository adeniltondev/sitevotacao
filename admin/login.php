<?php
/**
 * Página de Login Administrativo
 */
require_once '../config/database.php';
require_once '../config/functions.php';

iniciarSessao();

// Se já estiver logado, redireciona
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos';
    } else {
        $stmt = $pdo->prepare("SELECT id, usuario, senha, nome FROM administradores WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($senha, $admin['senha'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_usuario'] = $admin['usuario'];
            $_SESSION['admin_nome'] = $admin['nome'];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Usuário ou senha incorretos';
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
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600">Sistema de Votação</h1>
            <p class="text-gray-600 mt-2">Acesso Administrativo</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="usuario" class="block text-gray-700 font-medium mb-2">Usuário</label>
                <input 
                    type="text" 
                    id="usuario" 
                    name="usuario" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Digite seu usuário"
                >
            </div>
            
            <div class="mb-6">
                <label for="senha" class="block text-gray-700 font-medium mb-2">Senha</label>
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Digite sua senha"
                >
            </div>
            
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200 font-medium"
            >
                Entrar
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Credenciais padrão: <strong>admin</strong> / <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>
