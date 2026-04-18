<div class="min-h-screen flex items-center justify-center p-6">
  <div class="rt-card rounded-3xl shadow-xl p-8 w-full max-w-md">
    <h1 class="text-2xl font-bold mb-2">Definir nova senha</h1>
    <p class="text-slate-600 mb-6">Escolha uma nova senha para o painel administrativo.</p>
    <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="token" value="<?= h($token ?? '') ?>">
      <div class="rt-input-wrap"><label>Nova senha</label><input type="password" name="password" required minlength="6"></div>
      <div class="rt-input-wrap"><label>Confirmar senha</label><input type="password" name="password_confirmation" required minlength="6"></div>
      <button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold w-full">Salvar nova senha</button>
      <a href="index.php?route=admin/login" class="block text-center text-sm text-slate-500 underline">Voltar ao login</a>
    </form>
  </div>
</div>
