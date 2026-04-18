<div class="min-h-screen flex items-center justify-center p-6">
  <div class="max-w-md w-full rt-card rounded-3xl shadow-xl p-8">
    <a href="index.php" class="text-sm text-slate-500 underline">Voltar ao portal</a>
    <h1 class="text-3xl font-bold mt-3">Login do administrador</h1>
    <p class="text-slate-600 mt-2 mb-6">Acesse o dashboard do sistema.</p>
    <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <label class="block"><span class="text-sm font-medium">E-mail</span><input type="email" name="email" class="mt-1 w-full rounded-xl border-slate-300" required></label>
      <label class="block"><span class="text-sm font-medium">Senha</span><input type="password" name="password" class="mt-1 w-full rounded-xl border-slate-300" required></label>
      <button class="w-full rounded-2xl rt-btn-primary px-6 py-3 font-semibold">Entrar</button>
      <div class="mt-4 text-sm"><a href="index.php?route=admin/forgot-password" class="text-slate-600 underline">Esqueci minha senha</a></div>
</form>
  </div>
</div>
