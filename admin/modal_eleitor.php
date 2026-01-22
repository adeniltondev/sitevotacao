<!-- Modal para Cadastro/Edição de Eleitor -->
<div id="modal-eleitor" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-blue-100/20 dark:shadow-black/20 overflow-hidden border border-gray-100 dark:border-gray-700 p-8 w-full max-w-3xl relative">
    <button onclick="fecharModalEleitor()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-2xl">&times;</button>
    <div class="flex items-center gap-3 mb-6">
      <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
      </div>
      <h2 class="text-xl font-bold text-gray-800 dark:text-white" id="form-titulo-modal">Cadastrar Novo Eleitor</h2>
    </div>
    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6" id="form-eleitor-modal">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="acao" id="acao-modal" value="cadastrar_eleitor">
      <input type="hidden" name="eleitor_id" id="eleitor_id-modal" value="">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="nome-modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome Completo *</label>
          <input type="text" id="nome-modal" name="nome" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Ex: João da Silva">
        </div>
        <div>
          <label for="cpf-modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CPF *</label>
          <input type="text" id="cpf-modal" name="cpf" required maxlength="14" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="000.000.000-00" oninput="mascaraCPF(this)">
        </div>
        <div>
          <label for="cargo-modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cargo</label>
          <input type="text" id="cargo-modal" name="cargo" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Ex: Vereador">
        </div>
        <div>
          <label for="perfil-modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Perfil de Acesso</label>
          <select id="perfil-modal" name="perfil" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            <option value="vereador">Vereador</option>
            <option value="secretario">Secretário</option>
            <option value="presidente">Presidente</option>
          </select>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="foto-modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Foto (Opcional)</label>
          <input type="file" id="foto-modal" name="foto" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400">
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="aviso-foto-edit-modal" style="display:none;">Deixe em branco para manter a foto atual.</p>
        </div>
        <div>
          <label for="logo_partido-modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo do Partido (Opcional)</label>
          <input type="file" id="logo_partido-modal" name="logo_partido" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400">
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="aviso-logo-partido-edit-modal" style="display:none;">Deixe em branco para manter a logo atual.</p>
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-4">
        <button type="button" onclick="fecharModalEleitor()" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">Cancelar</button>
        <button type="submit" class="w-full md:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg shadow-blue-600/30 transition-all transform hover:scale-[1.02]">Cadastrar Eleitor</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirModalEleitor() {
  document.getElementById('modal-eleitor').classList.remove('hidden');
}
function fecharModalEleitor() {
  document.getElementById('modal-eleitor').classList.add('hidden');
}
</script>
