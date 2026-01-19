<?php
/**
 * Gerenciamento de Eleitores
 */
require_once '../config/functions.php';
verificarAdmin();
require_once '../config/database.php';

// Gerar token CSRF
$csrf_token = gerarCSRFToken();

$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRFToken()) {
        $mensagem = 'Erro de segurança: Token CSRF inválido.';
        $tipo_mensagem = 'error';
    } else {
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
            try {
                // Verificar se CPF já existe
                $stmt = $pdo->prepare("SELECT id FROM eleitores WHERE cpf = ?");
                $stmt->execute([$cpf]);
                if ($stmt->fetch()) {
                    $mensagem = 'CPF já cadastrado';
                    $tipo_mensagem = 'error';
                } else {
                    // Processar upload de foto (usar caminho absoluto para evitar problemas de working dir)
                    $foto = null;
                    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                        $resultado = uploadFoto($_FILES['foto'], __DIR__ . '/../uploads');
                        if (!isset($resultado['erro'])) {
                            $foto = $resultado['arquivo'];
                        } else {
                            // log do erro de upload, mas não interrompe o cadastro
                            registrarLog('upload_foto_erro', ['erro' => $resultado['erro']]);
                        }
                    }

                    // Inserir eleitor
                    $perfil = $_POST['perfil'] ?? 'vereador';
                    $stmt = $pdo->prepare("INSERT INTO eleitores (nome, cpf, cargo, foto, perfil) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, $cpf, $cargo ?: null, $foto, $perfil]);
                    $mensagem = 'Eleitor cadastrado com sucesso!';
                    $tipo_mensagem = 'success';
                }
            } catch (Exception $e) {
                // Registrar erro e mostrar mensagem amigável em vez de 500
                registrarLog('cadastrar_eleitor_erro', ['mensagem' => $e->getMessage()]);
                $mensagem = 'Ocorreu um erro ao cadastrar o eleitor. Verifique os logs.';
                $tipo_mensagem = 'error';
            }
        }
    }
    
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

    if ($acao === 'excluir_eleitor') {
        $eleitor_id = intval($_POST['eleitor_id'] ?? 0);

        // Buscar foto para excluir
        $stmt = $pdo->prepare("SELECT foto FROM eleitores WHERE id = ?");
        $stmt->execute([$eleitor_id]);
        $eleitor = $stmt->fetch();

        if ($eleitor && $eleitor['foto']) {
            $caminho_foto = __DIR__ . '/../uploads/' . $eleitor['foto'];
            if (file_exists($caminho_foto)) {
                @unlink($caminho_foto);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM eleitores WHERE id = ?");
        $stmt->execute([$eleitor_id]);

        $mensagem = 'Eleitor excluído com sucesso!';
        $tipo_mensagem = 'success';
    }
    }
}

// Buscar todos os eleitores
$eleitores = $pdo->query("SELECT * FROM eleitores ORDER BY nome ASC")->fetchAll();

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Gerenciar Eleitores</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Cadastre e gerencie os vereadores aptos a votar.</p>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="mb-6 p-4 rounded-lg flex items-center gap-3 <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800' ?>">
                <?php if ($tipo_mensagem === 'success'): ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <?php else: ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php endif; ?>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Cadastro -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg text-blue-600 dark:text-blue-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Cadastrar Novo Eleitor</h2>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="acao" value="cadastrar_eleitor">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="Ex: João da Silva">
                        </div>
                        
                        <div>
                            <label for="cpf" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CPF *</label>
                            <input type="text" id="cpf" name="cpf" required maxlength="14" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="000.000.000-00" oninput="mascaraCPF(this)">
                        </div>
                        
                        <div>
                            <label for="cargo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cargo</label>
                            <input type="text" id="cargo" name="cargo" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="Ex: Vereador">
                        </div>

                        <div>
                            <label for="perfil" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Perfil de Acesso</label>
                            <select id="perfil" name="perfil" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="vereador">Vereador</option>
                                <option value="secretario">Secretário</option>
                                <option value="presidente">Presidente</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto (Opcional)</label>
                        <input type="file" id="foto" name="foto" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition transform hover:scale-105">
                            Cadastrar Eleitor
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Eleitores -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Eleitores Cadastrados</h2>
                    <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full text-sm font-semibold"><?= count($eleitores) ?> total</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                                <th class="px-6 py-4 font-semibold">Eleitor</th>
                                <th class="px-6 py-4 font-semibold">CPF</th>
                                <th class="px-6 py-4 font-semibold">Cargo</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php foreach ($eleitores as $eleitor): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($eleitor['foto']): ?>
                                                <img src="../uploads/<?= htmlspecialchars($eleitor['foto']) ?>" alt="" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700">
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold">
                                                    <?= strtoupper(substr($eleitor['nome'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($eleitor['nome']) ?></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($eleitor['perfil'] ?? 'vereador') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-mono text-sm">
                                        <?= formatarCPF($eleitor['cpf']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                        <?= htmlspecialchars($eleitor['cargo'] ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($eleitor['ativo']): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Bloqueado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="historico.php?cpf=<?= $eleitor['cpf'] ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">Histórico</a>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Tem certeza?')">
                                                <input type="hidden" name="eleitor_id" value="<?= $eleitor['id'] ?>">
                                                <?php if ($eleitor['ativo']): ?>
                                                    <input type="hidden" name="acao" value="bloquear_eleitor">
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" title="Bloquear">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="acao" value="desbloquear_eleitor">
                                                    <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Desbloquear">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Excluir permanentemente este eleitor?')">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="acao" value="excluir_eleitor">
                                                <input type="hidden" name="eleitor_id" value="<?= $eleitor['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Excluir">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function alternarModoEscuro() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', dark ? '1' : '0');
            document.getElementById('icone-modo').textContent = dark ? '☀️' : '🌙';
        }
        
        // Inicializar modo escuro
        if (localStorage.getItem('darkMode') === '1' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            document.getElementById('icone-modo').textContent = '☀️';
        }

        function mascaraCPF(i) {
            var v = i.value;
            if(isNaN(v[v.length-1])){
                i.value = v.substring(0, v.length-1);
                return;
            }
            i.setAttribute("maxlength", "14");
            if (v.length == 3 || v.length == 7) i.value += ".";
            if (v.length == 11) i.value += "-";
        }
    </script>
</body>
</html>
                            placeholder="Ex: Vereador, Secretário, etc."
                        >
                    </div>
                    <div>
                        <label for="perfil" class="block text-gray-700 font-medium mb-2">Permissão/Perfil</label>
                        <select id="perfil" name="perfil" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="vereador">Vereador</option>
                            <option value="secretario">Secretário</option>
                            <option value="admin">Administrador</option>
                        </select>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissão</th>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($eleitor['perfil'] ?? 'vereador') ?>
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
