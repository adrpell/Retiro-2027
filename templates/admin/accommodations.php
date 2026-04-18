<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Acomodações</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8 space-y-6">
    <div class="mb-2"><h1 class="text-3xl font-bold">Acomodações</h1><p class="text-slate-600">O alojamento é dividido automaticamente em masculino e feminino conforme o sexo informado no cadastro.</p></div>
    <div class="grid md:grid-cols-3 gap-4">
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Chalés</div><div class="text-3xl font-bold mt-2"><?= h($capacity['chale_occupied']) ?>/<?= h($capacity['chale_total']) ?></div></div>
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Alojamento masculino</div><div class="text-3xl font-bold mt-2"><?= h($lodgingGender['male_occupied']) ?>/<?= h($lodgingGender['male_total']) ?></div></div>
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Alojamento feminino</div><div class="text-3xl font-bold mt-2"><?= h($lodgingGender['female_occupied']) ?>/<?= h($lodgingGender['female_total']) ?></div></div>
    </div>
    <div class="rt-card rounded-3xl p-6">
      <h2 class="font-semibold text-xl mb-4">Resumo por inscrição</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="text-left text-slate-500 border-b border-slate-200"><th class="py-2">Tipo</th><th class="py-2">Total</th></tr></thead>
          <tbody>
            <?php foreach ($groupsAccommodation as $row): ?>
            <tr class="border-b border-slate-100"><td class="py-2"><?= h($row['group_accommodation']) ?></td><td class="py-2"><?= h($row['total']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
