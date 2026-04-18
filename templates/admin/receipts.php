<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Comprovantes</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8">
    <div class="mb-6"><h1 class="text-3xl font-bold">Comprovantes</h1><p class="text-slate-600">Arquivos enviados pelos inscritos.</p></div>
    <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead><tr class="text-left text-slate-500"><th class="pb-3">Tipo</th><th class="pb-3">Código</th><th class="pb-3">Responsável</th><th class="pb-3">Pagamento</th><th class="pb-3">Arquivo</th><th class="pb-3">Data</th></tr></thead>
        <tbody>
          <?php foreach ($receipts as $row): ?>
          <tr class="border-t border-slate-200"><td class="py-3"><?= h($row['receipt_type_label'] ?? 'Comprovante') ?></td><td class="py-3"><?= h($row['access_code']) ?></td><td class="py-3"><?= h($row['responsible_name']) ?></td><td class="py-3"><?= h(payment_label($row['payment_method'] ?? 'nao_definido')) ?></td><td class="py-3"><a class="underline text-indigo-600" target="_blank" href="<?= h($row['receipt_file']) ?>">Abrir</a></td><td class="py-3"><?= h($row['created_at']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
