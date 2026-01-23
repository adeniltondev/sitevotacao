<!-- Modal para Cadastro/Edição de Candidato -->
<div id="modal-eleitor"
  class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4 transition-all duration-300">
  <div
    class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-95 opacity-0"
    id="modal-eleitor-content">
    <!-- Cabeçalho com Gradiente -->
    <div class="relative bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-white/20 backdrop-blur-sm rounded-xl">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-white" id="form-titulo-modal">Cadastrar Novo Candidato</h2>
        </div>
        <button onclick="fecharModalEleitor()"
          class="p-2 hover:bg-white/20 rounded-lg transition-all duration-200 group">
          <svg class="w-6 h-6 text-white group-hover:rotate-90 transition-transform duration-300" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Corpo do Modal -->
    <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] custom-scrollbar-eleitor">
      <form method="POST" action="" enctype="multipart/form-data" class="space-y-6" id="form-eleitor-modal">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="acao" id="acao-modal" value="cadastrar_candidato">
        <input type="hidden" name="eleitor_id" id="eleitor_id-modal" value="">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="group">
            <label for="nome-modal" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Nome Completo <span class="text-red-500">*</span>
            </label>
            <input type="text" id="nome-modal" name="nome" required
              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500"
              placeholder="Ex: João da Silva">
          </div>

          <div class="group">
            <label for="cpf-modal" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              CPF <span class="text-red-500">*</span>
            </label>
            <input type="text" id="cpf-modal" name="cpf" required maxlength="14"
              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500"
              placeholder="000.000.000-00" oninput="mascaraCPF(this)">
          </div>

          <div class="group">
            <label for="cargo-modal"
              class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cargo</label>
            <input type="text" id="cargo-modal" name="cargo"
              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500"
              placeholder="Ex: Vereador">
          </div>

          <div class="group">
            <label for="perfil-modal" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Perfil
              de Acesso</label>
            <select id="perfil-modal" name="perfil"
              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-500">
              <option value="vereador">Vereador</option>
              <option value="secretario">Secretário</option>
              <option value="presidente">Presidente</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="group">
            <label for="foto-modal" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Foto
              (Opcional)</label>
            <input type="file" id="foto-modal" name="foto" accept="image/*"
              class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-blue-50 file:to-indigo-50 file:text-blue-700 hover:file:from-blue-100 hover:file:to-indigo-100 dark:file:from-blue-900/30 dark:file:to-indigo-900/30 dark:file:text-blue-400 file:transition-all file:duration-200 file:cursor-pointer">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1" id="aviso-foto-edit-modal"
              style="display:none;">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Deixe em branco para manter a foto atual.
            </p>
          </div>

          <div class="group">
            <label for="logo_partido-modal"
              class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Logo do Partido
              (Opcional)</label>
            <input type="file" id="logo_partido-modal" name="logo_partido" accept="image/*"
              class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-blue-50 file:to-indigo-50 file:text-blue-700 hover:file:from-blue-100 hover:file:to-indigo-100 dark:file:from-blue-900/30 dark:file:to-indigo-900/30 dark:file:text-blue-400 file:transition-all file:duration-200 file:cursor-pointer">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1"
              id="aviso-logo-partido-edit-modal" style="display:none;">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Deixe em branco para manter a logo atual.
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t-2 border-gray-100 dark:border-gray-700">
          <button type="button" onclick="fecharModalEleitor()"
            class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-semibold">
            Cancelar
          </button>
          <button type="submit"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all duration-200 transform hover:scale-105">
            Cadastrar Candidato
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  /* Scrollbar personalizada */
  .custom-scrollbar-eleitor::-webkit-scrollbar {
    width: 8px;
  }

  .custom-scrollbar-eleitor::-webkit-scrollbar-track {
    background: transparent;
  }

  .custom-scrollbar-eleitor::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
  }

  .dark .custom-scrollbar-eleitor::-webkit-scrollbar-thumb {
    background: #475569;
  }

  .custom-scrollbar-eleitor::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  /* Animação de entrada */
  #modal-eleitor.flex #modal-eleitor-content {
    animation: modalEleitorEnter 0.3s ease-out forwards;
  }

  @keyframes modalEleitorEnter {
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
  function abrirModalEleitor() {
    const modal = document.getElementById('modal-eleitor');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  function fecharModalEleitor() {
    const modal = document.getElementById('modal-eleitor');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
  }

  // Fechar com ESC
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      fecharModalEleitor();
    }
  });

  // Fechar ao clicar no backdrop
  document.getElementById('modal-eleitor')?.addEventListener('click', function (e) {
    if (e.target === this) {
      fecharModalEleitor();
    }
  });
</script>