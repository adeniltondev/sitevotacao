<?php
/**
 * Gerenciamento de Eleitores
 */
require_once '../config/database.php';
require_once '../config/functions.php';

verificarAdmin();

$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'cadastrar_eleitor') {
        $nome = sanitizar($_POST['nome'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $cargo = sanitizar($_POST['cargo'] ?? '');
        
        if (empty($nome) || empty($cpf)) {
            $mensagem = 'Preencha todos os campos obrigatórios';
            $tipo_mensagem = 'error';
        } elseif (!validarCPF($cpf)) {
            $mensagem = 'CPF inválido';
            $tipo_mensagem = 'error';
        } else {
            // Verificar se CPF já existe
            $stmt = $pdo->prepare("SELECT id FROM eleitores WHERE cpf = ?");
            $stmt->execute([$cpf]);
            if ($stmt->fetch()) {
                $mensagem = 'CPF já cadastrado';
                $tipo_mensagem = 'error';
            } else {
                // Processar upload de foto
                $foto = null;
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $resultado = uploadFoto($_FILES['foto'], '../uploads');
                    if (!isset($resultado['erro'])) {
                        $foto = $resultado['arquivo'];
                    }
                }
                
                // Inserir eleitor
                $stmt = $pdo->prepare("INSERT INTO eleitores (nome, cpf, cargo, foto) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $cpf, $cargo ?: null, $foto]);
                
                $mensagem = 'Eleitor cadastrado com sucesso!';
                $tipo_mensagem = 'success';
            }
        }
    }
    
    if ($acao === 'excluir_eleitor') {
            if ($acao === 'bloquear_eleitor') {
                $eleitor_id = intval($_POST['eleitor_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE eleitores SET ativo = 0 WHERE id = ?");
                $stmt->execute([$eleitor_id]);
                $mensagem = 'Eleitor bloqueado com sucesso!';
                $tipo_mensagem = 'success';
            }
            if ($acao === 'desbloquear_eleitor') {
                $eleitor_id = intval($_POST['eleitor_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE eleitores SET ativo = 1 WHERE id = ?");
                $stmt->execute([$eleitor_id]);
                $mensagem = 'Eleitor desbloqueado com sucesso!';
                $tipo_mensagem = 'success';
            }
        $eleitor_id = intval($_POST['eleitor_id'] ?? 0);
        
        // Buscar foto para excluir
        $stmt = $pdo->prepare("SELECT foto FROM eleitores WHERE id = ?");
        $stmt->execute([$eleitor_id]);
        $eleitor = $stmt->fetch();
        
        if ($eleitor && $eleitor['foto']) {
            $caminho_foto = '../uploads/' . $eleitor['foto'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM eleitores WHERE id = ?");
        $stmt->execute([$eleitor_id]);
        
        $mensagem = 'Eleitor excluído com sucesso!';
        $tipo_mensagem = 'success';
    }
}

// Buscar todos os eleitores
$eleitores = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Eleitores - Sistema de Votação</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Voltar</a>
                    <h1 class="text-2xl font-bold text-blue-600">Gerenciar Eleitores</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-700"><?= htmlspecialchars($_SESSION['admin_nome']) ?></span>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($mensagem): ?>
            <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Cadastro -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Cadastrar Novo Eleitor</h2>
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="acao" value="cadastrar_eleitor">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nome" class="block text-gray-700 font-medium mb-2">Nome Completo *</label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Digite o nome completo"
                        >
                    </div>
                    
                    <div>
                        <label for="cpf" class="block text-gray-700 font-medium mb-2">CPF *</label>
                        <input 
                            type="text" 
                            id="cpf" 
                            name="cpf" 
                            required
                            maxlength="14"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="000.000.000-00"
                        >
                    </div>
                </div>
                
                <div>
                    <label for="cargo" class="block text-gray-700 font-medium mb-2">Cargo (Opcional)</label>
                    <input 
                        type="text" 
                        id="cargo" 
                        name="cargo"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Vereador, Secretário, etc."
                    >
                </div>
                
                <div>
                    <label for="foto" class="block text-gray-700 font-medium mb-2">Foto (Opcional)</label>
                    <input 
                        type="file" 
                        id="foto" 
                        name="foto"
                        accept="image/jpeg,image/jpg,image/png,image/gif"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-sm text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</p>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    Cadastrar Eleitor
                </button>
            </form>
        </div>

        <!-- Lista de Eleitores -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Eleitores Cadastrados (<?= count($eleitores) ?>)</h2>
            
            <?php if (count($eleitores) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cargo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($eleitores as $eleitor): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($eleitor['foto']): ?>
                                            <img 
                                                src="../uploads/<?= htmlspecialchars($eleitor['foto']) ?>" 
                                                alt="Foto"
                                                class="w-12 h-12 rounded-full object-cover"
                                            >
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-gray-600"><?= strtoupper(substr($eleitor['nome'], 0, 1)) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($eleitor['nome']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= formatarCPF($eleitor['cpf']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $eleitor['cargo'] ? htmlspecialchars($eleitor['cargo']) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        // Exemplo: status ativo/inativo (futuro: campo na tabela)
                                        $ativo = $eleitor['ativo'] ?? 1;
                                        echo $ativo
                                            ? '<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold">Ativo</span>'
                                            : '<span class="bg-gray-200 text-gray-700 px-2 py-1 rounded-full text-xs font-semibold">Inativo</span>';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-2">
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este eleitor?')">
                                            <input type="hidden" name="acao" value="excluir_eleitor">
                                            <input type="hidden" name="eleitor_id" value="<?= $eleitor['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                        </form>
                                        <?php if ($eleitor['ativo']): ?>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="acao" value="bloquear_eleitor">
                                                <input type="hidden" name="eleitor_id" value="<?= $eleitor['id'] ?>">
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">Bloquear</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="acao" value="desbloquear_eleitor">
                                                <input type="hidden" name="eleitor_id" value="<?= $eleitor['id'] ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900">Desbloquear</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="historico.php?cpf=<?= urlencode($eleitor['cpf']) ?>" class="text-blue-600 hover:text-blue-900">Histórico</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-center py-8">Nenhum eleitor cadastrado ainda.</p>
            <?php endif; ?>
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
