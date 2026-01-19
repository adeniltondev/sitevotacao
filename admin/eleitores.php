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
    
        if ($acao === 'cadastrar_eleitor' || $acao === 'editar_eleitor') {
            $nome = sanitizar($_POST['nome'] ?? '');
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            $cargo = sanitizar($_POST['cargo'] ?? '');
            $perfil = $_POST['perfil'] ?? 'vereador';
            $eleitor_id = isset($_POST['eleitor_id']) ? intval($_POST['eleitor_id']) : 0;
            
            if (empty($nome) || empty($cpf)) {
                $mensagem = 'Preencha todos os campos obrigatórios';
                $tipo_mensagem = 'error';
            } elseif (!validarCPF($cpf)) {
                $mensagem = 'CPF inválido';
                $tipo_mensagem = 'error';
            } else {
                try {
                    // Verificar se CPF já existe (ignorando o próprio usuário na edição)
                    $sql_check = "SELECT id FROM eleitores WHERE cpf = ?";
                    $params_check = [$cpf];
                    if ($acao === 'editar_eleitor' && $eleitor_id > 0) {
                        $sql_check .= " AND id != ?";
                        $params_check[] = $eleitor_id;
                    }
                    
                    $stmt = $pdo->prepare($sql_check);
                    $stmt->execute($params_check);
                    
                    if ($stmt->fetch()) {
                        $mensagem = 'CPF já cadastrado para outro eleitor';
                        $tipo_mensagem = 'error';
                    } else {
                        // Processar upload de foto
                        $foto = null;
                        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                            $resultado = uploadFoto($_FILES['foto'], __DIR__ . '/../uploads');
                            if (!isset($resultado['erro'])) {
                                $foto = $resultado['arquivo'];
                                
                                // Se for edição e tiver foto nova, deletar a antiga se desejar (opcional, mas boa prática)
                                if ($acao === 'editar_eleitor' && $eleitor_id > 0) {
                                     $stmt_old = $pdo->prepare("SELECT foto FROM eleitores WHERE id = ?");
                                     $stmt_old->execute([$eleitor_id]);
                                     $old = $stmt_old->fetch();
                                     if ($old && $old['foto'] && file_exists(__DIR__ . '/../uploads/' . $old['foto'])) {
                                         @unlink(__DIR__ . '/../uploads/' . $old['foto']);
                                     }
                                }
                            } else {
                                registrarLog('upload_foto_erro', ['erro' => $resultado['erro']]);
                            }
                        }

                        if ($acao === 'cadastrar_eleitor') {
                            $stmt = $pdo->prepare("INSERT INTO eleitores (nome, cpf, cargo, foto, perfil) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$nome, $cpf, $cargo ?: null, $foto, $perfil]);
                            $mensagem = 'Eleitor cadastrado com sucesso!';
                        } else {
                            // Edição
                            $sql_update = "UPDATE eleitores SET nome = ?, cpf = ?, cargo = ?, perfil = ?";
                            $params_update = [$nome, $cpf, $cargo ?: null, $perfil];
                            
                            if ($foto) {
                                $sql_update .= ", foto = ?";
                                $params_update[] = $foto;
                            }
                            
                            $sql_update .= " WHERE id = ?";
                            $params_update[] = $eleitor_id;
                            
                            $stmt = $pdo->prepare($sql_update);
                            $stmt->execute($params_update);
                            $mensagem = 'Eleitor atualizado com sucesso!';
                        }
                        
                        $tipo_mensagem = 'success';
                    }
                } catch (Exception $e) {
                    registrarLog('eleitor_erro', ['mensagem' => $e->getMessage()]);
                    $mensagem = 'Ocorreu um erro ao processar. Verifique os logs.';
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Eleitores - VotaCâmara</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Mobile Header -->
    <div class="md:hidden bg-white dark:bg-gray-800 shadow p-4 flex justify-between items-center">
        <span class="text-xl font-bold text-green-600">Vota<span class="text-blue-600">Câmara</span></span>
        <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-gray-600 dark:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
    </div>
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-gray-800 border-b dark:border-gray-700 p-4 space-y-2">
        <a href="dashboard.php" class="block text-gray-700 dark:text-gray-200 py-2">Dashboard</a>
        <a href="eleitores.php" class="block text-blue-600 font-bold py-2">Eleitores</a>
        <a href="relatorios.php" class="block text-gray-700 dark:text-gray-200 py-2">Relatórios</a>
        <a href="auditoria.php" class="block text-gray-700 dark:text-gray-200 py-2">Auditoria</a>
        <a href="configuracoes.php" class="block text-gray-700 dark:text-gray-200 py-2">Configurações</a>
        <a href="logout.php" class="block text-red-600 py-2">Sair</a>
    </div>

    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen">
        <!-- Top Bar (Desktop) -->
        <header class="hidden md:flex justify-between items-center bg-white dark:bg-gray-800 shadow-sm px-8 py-4">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gerenciar Eleitores</h1>
            <div class="flex items-center gap-4">
                <button onclick="alternarModoEscuro()" class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition">
                    <span id="icone-modo" class="text-xl">🌙</span>
                </button>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($_SESSION['admin_nome'] ?? 'Admin') ?></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Administrador</div>
                    </div>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium">Sair</a>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

            <!-- Formulário de Cadastro/Edição -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8 transition-all duration-300" id="card-formulario">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg text-blue-600 dark:text-blue-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white" id="form-titulo">Cadastrar Novo Eleitor</h2>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6" id="form-eleitor">
                    <input type="hidden" name="acao" id="acao" value="cadastrar_eleitor">
                    <input type="hidden" name="eleitor_id" id="eleitor_id" value="">
                    
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
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="aviso-foto-edit" style="display:none;">Deixe em branco para manter a foto atual.</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" id="btn-cancelar" onclick="cancelarEdicao()" class="hidden px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancelar
                        </button>
                        <button type="submit" id="btn-submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition transform hover:scale-105">
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
                                            <button type="button" onclick='editarEleitor(<?= json_encode([
                                                "id" => $eleitor["id"],
                                                "nome" => $eleitor["nome"],
                                                "cpf" => $eleitor["cpf"],
                                                "cargo" => $eleitor["cargo"],
                                                "perfil" => $eleitor["perfil"] ?? "vereador"
                                            ]) ?>)' class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                                                Editar
                                            </button>
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
