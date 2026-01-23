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

            <button onclick="abrirModalCriar()"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium shadow-lg shadow-blue-600/20 transition-all transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Nova Pauta
            </button>
        </header>

        <!-- Lista de Pautas -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
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
                                        <button onclick='abrirModalEditar(<?= json_encode($pauta) ?>)'
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm">Editar</button>
                                        <form method="POST" class="inline-block"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta pauta?');">
                                            <input type="hidden" name="acao" value="excluir_pauta">
                                            <input type="hidden" name="id" value="<?= $pauta['id'] ?>">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars(gerarCSRFToken()) ?>">
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium text-sm">Excluir</button>
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
<div id="modalPauta"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-300"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-2xl w-full transform transition-all duration-300 scale-95 opacity-0 overflow-hidden"
        id="modal-pauta-content">
        <form method="POST" action="">
            <input type="hidden" name="acao" id="formAcao" value="criar_pauta">
            <input type="hidden" name="id" id="pautaId" value="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCSRFToken()) ?>">

            <!-- Cabeçalho com Gradiente -->
            <div class="relative bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white" id="modalTitle">Nova Pauta</h3>
                    </div>
                    <button type="button" onclick="fecharModal()"
                        class="p-2 hover:bg-white/20 rounded-lg transition-all duration-200 group">
                        <svg class="w-6 h-6 text-white group-hover:rotate-90 transition-transform duration-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Corpo do Modal -->
            <div class="p-6 space-y-5 max-h-[calc(90vh-180px)] overflow-y-auto custom-scrollbar-pauta">
                <div class="group">
                    <label for="titulo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Título <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="titulo" id="titulo" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500"
                        placeholder="Digite o título da pauta">
                </div>

                <div class="group">
                    <label for="data_sessao" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Data da Sessão <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="data_sessao" id="data_sessao" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500">
                </div>

                <div class="group">
                    <label for="conteudo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Conteúdo da Pauta
                    </label>
                    <textarea name="conteudo" id="conteudo" rows="10"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500 resize-none"
                        placeholder="Descreva o conteúdo da pauta..."></textarea>
                </div>
            </div>

            <!-- Rodapé com Botões -->
            <div
                class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                <button type="button" onclick="fecharModal()"
                    class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 font-semibold">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all duration-200 transform hover:scale-105">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Scrollbar personalizada para modal de pautas */
    .custom-scrollbar-pauta::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar-pauta::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar-pauta::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .dark .custom-scrollbar-pauta::-webkit-scrollbar-thumb {
        background: #475569;
    }

    .custom-scrollbar-pauta::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Animação de entrada do modal de pautas */
    #modalPauta.flex #modal-pauta-content {
        animation: modalPautaEnter 0.3s ease-out forwards;
    }

    @keyframes modalPautaEnter {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
</style>

<script>
    function abrirModalCriar() {
        document.getElementById('modalTitle').innerText = 'Nova Pauta';
        document.getElementById('formAcao').value = 'criar_pauta';
        document.getElementById('pautaId').value = '';
        document.getElementById('titulo').value = '';
        document.getElementById('data_sessao').value = '';
        document.getElementById('conteudo').value = '';
        const modal = document.getElementById('modalPauta');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function abrirModalEditar(pauta) {
        document.getElementById('modalTitle').innerText = 'Editar Pauta';
        document.getElementById('formAcao').value = 'editar_pauta';
        document.getElementById('pautaId').value = pauta.id;
        document.getElementById('titulo').value = pauta.titulo;
        document.getElementById('data_sessao').value = pauta.data_sessao;
        document.getElementById('conteudo').value = pauta.conteudo;
        const modal = document.getElementById('modalPauta');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function fecharModal() {
        const modal = document.getElementById('modalPauta');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // Fechar modal com ESC
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            fecharModal();
        }
    });

    // Fechar ao clicar no backdrop
    document.getElementById('modalPauta')?.addEventListener('click', function (e) {
        if (e.target === this) {
            fecharModal();
        }
    });
</script>

<?php require_once 'footer.php'; ?>