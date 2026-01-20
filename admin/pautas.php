<?php
require_once '../config/database.php';
require_once '../config/functions.php';

verificarAdmin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validar CSRF
    if (!validarCSRFToken()) {
        die('Token CSRF inválido');
    }

    if ($acao === 'criar_pauta') {
        $titulo = sanitizar($_POST['titulo'] ?? '');
        $conteudo = $_POST['conteudo'] ?? ''; // Conteúdo pode ter HTML, cuidado com sanitização
        $data_sessao = $_POST['data_sessao'] ?? '';

        if (!empty($titulo) && !empty($data_sessao)) {
            $stmt = $pdo->prepare("INSERT INTO pautas (titulo, conteudo, data_sessao) VALUES (?, ?, ?)");
            $stmt->execute([$titulo, $conteudo, $data_sessao]);
            header('Location: pautas.php?sucesso=pauta_criada');
            exit;
        }
    }

    if ($acao === 'editar_pauta') {
        $id = intval($_POST['id'] ?? 0);
        $titulo = sanitizar($_POST['titulo'] ?? '');
        $conteudo = $_POST['conteudo'] ?? '';
        $data_sessao = $_POST['data_sessao'] ?? '';

        if ($id > 0 && !empty($titulo) && !empty($data_sessao)) {
            $stmt = $pdo->prepare("UPDATE pautas SET titulo = ?, conteudo = ?, data_sessao = ? WHERE id = ?");
            $stmt->execute([$titulo, $conteudo, $data_sessao, $id]);
            header('Location: pautas.php?sucesso=pauta_editada');
            exit;
        }
    }

    if ($acao === 'excluir_pauta') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM pautas WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: pautas.php?sucesso=pauta_excluida');
            exit;
        }
    }
}

// Buscar pautas
$stmt = $pdo->query("SELECT * FROM pautas ORDER BY data_sessao DESC");
$pautas = $stmt->fetchAll();

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="md:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-300">
    <div class="p-6 md:p-10 space-y-8 fade-in">
        
        <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Pautas das Sessões</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Gerencie as pautas das sessões da câmara.</p>
            </div>
            
            <button onclick="abrirModalCriar()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium shadow-lg shadow-blue-600/20 transition-all transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                Nova Pauta
            </button>
        </header>

        <!-- Lista de Pautas -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Data da Sessão</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Título</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($pautas)): ?>
                            <tr>
                                <td colspan="3" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    Nenhuma pauta cadastrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pautas as $pauta): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="p-4 text-gray-800 dark:text-gray-200">
                                        <?= date('d/m/Y', strtotime($pauta['data_sessao'])) ?>
                                    </td>
                                    <td class="p-4 text-gray-800 dark:text-gray-200 font-medium">
                                        <?= htmlspecialchars($pauta['titulo']) ?>
                                    </td>
                                    <td class="p-4 text-right space-x-2">
                                        <button onclick='abrirModalEditar(<?= json_encode($pauta) ?>)' class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm">Editar</button>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir esta pauta?');">
                                            <input type="hidden" name="acao" value="excluir_pauta">
                                            <input type="hidden" name="id" value="<?= $pauta['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium text-sm">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Criar/Editar Pauta -->
<div id="modalPauta" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="fecharModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form method="POST" action="">
                <input type="hidden" name="acao" id="formAcao" value="criar_pauta">
                <input type="hidden" name="id" id="pautaId" value="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modalTitle">Nova Pauta</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="titulo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                    <input type="text" name="titulo" id="titulo" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="data_sessao" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Sessão</label>
                                    <input type="date" name="data_sessao" id="data_sessao" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="conteudo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conteúdo da Pauta</label>
                                    <textarea name="conteudo" id="conteudo" rows="10" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Salvar
                    </button>
                    <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalCriar() {
        document.getElementById('modalTitle').innerText = 'Nova Pauta';
        document.getElementById('formAcao').value = 'criar_pauta';
        document.getElementById('pautaId').value = '';
        document.getElementById('titulo').value = '';
        document.getElementById('data_sessao').value = '';
        document.getElementById('conteudo').value = '';
        document.getElementById('modalPauta').classList.remove('hidden');
    }

    function abrirModalEditar(pauta) {
        document.getElementById('modalTitle').innerText = 'Editar Pauta';
        document.getElementById('formAcao').value = 'editar_pauta';
        document.getElementById('pautaId').value = pauta.id;
        document.getElementById('titulo').value = pauta.titulo;
        document.getElementById('data_sessao').value = pauta.data_sessao;
        document.getElementById('conteudo').value = pauta.conteudo; // Cuidado com caracteres especiais aqui, pode precisar de tratamento se usar editor rico
        document.getElementById('modalPauta').classList.remove('hidden');
    }

    function fecharModal() {
        document.getElementById('modalPauta').classList.add('hidden');
    }
</script>

<?php require_once 'footer.php'; ?>
