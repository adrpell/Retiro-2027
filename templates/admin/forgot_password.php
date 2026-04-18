<div class="min-h-screen flex items-center justify-center p-6">
  <div class="rt-card rounded-3xl shadow-xl p-8 w-full max-w-md">
    <h1 class="text-2xl font-bold mb-2">Recuperar senha</h1>
    <p class="text-slate-600 mb-6">Informe o e-mail do administrador. Se o endereço existir, um link de redefinição será gerado.</p>
    <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= $msg ?></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="rt-input-wrap"><label>E-mail do administrador</label><input type="email" name="email" required></div>
      <button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold w-full">Gerar link de recuperação</button>
      <a href="index.php?route=admin/login" class="block text-center text-sm text-slate-500 underline">Voltar ao login</a>
    </form>
  </div>
</div>
