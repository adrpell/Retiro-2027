<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Backups</div>
        <div class="rt-admin-subtitle">Geração manual, rotina automática por cron e histórico dos arquivos SQL.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
    <main class="rt-admin-content p-6 lg:p-8">
      <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
      <div class="mb-6"><h1 class="text-3xl font-bold">Backup automático do banco</h1><p class="text-slate-600">Use o botão abaixo para gerar um snapshot SQL completo. Para automação, configure o cron apontando para <code>cron_backup.php</code>.</p></div>
      <div class="grid xl:grid-cols-3 gap-6 mb-6">
        <div class="rt-card rounded-3xl shadow p-6 xl:col-span-1">
          <h2 class="font-semibold text-xl mb-4">Gerar backup agora</h2>
          <form method="post" action="index.php?route=admin/backups/create" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <button class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold w-full">Gerar backup SQL</button>
          </form>
          <div class="mt-5 text-sm text-slate-600">
            <p><strong>Cron sugerido:</strong></p>
            <code class="block mt-2 rounded-xl bg-slate-100 p-3 text-xs break-all">php <?= h(realpath(__DIR__ . '/../../cron_backup.php')) ?></code>
          </div>
        </div>
        <div class="rt-card rounded-3xl shadow p-6 xl:col-span-2">
          <h2 class="font-semibold text-xl mb-4">Automação</h2>
          <form method="post" action="index.php?route=admin/settings" class="grid md:grid-cols-3 gap-4">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <div class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="backup_auto_enabled" value="1" <?= setting($pdo,'backup_auto_enabled','0')==='1'?'checked':'' ?>> Habilitar backup automático</div>
            <label class="block"><span class="text-sm font-medium">Frequência</span><select name="backup_auto_frequency" class="mt-1 w-full rounded-xl border-slate-300"><option value="daily" <?= setting($pdo,'backup_auto_frequency','daily')==='daily'?'selected':'' ?>>Diário</option><option value="weekly" <?= setting($pdo,'backup_auto_frequency','daily')==='weekly'?'selected':'' ?>>Semanal</option></select></label>
            <label class="block"><span class="text-sm font-medium">Retenção (dias)</span><input name="backup_retention_days" value="<?= h(setting($pdo,'backup_retention_days','30')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <div class="md:col-span-3"><button class="rounded-2xl border border-slate-300 px-5 py-3 font-semibold">Salvar automação</button></div>
          </form>
        </div>
      </div>
      <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
        <h2 class="font-semibold text-xl mb-4">Histórico de backups</h2>
        <table class="min-w-full text-sm">
          <thead><tr class="text-left text-slate-500"><th class="pb-3">Data</th><th class="pb-3">Arquivo</th><th class="pb-3">Tamanho</th><th class="pb-3">Tipo</th><th class="pb-3">Ação</th></tr></thead>
          <tbody>
            <?php foreach ($backupHistory as $item): ?>
            <tr class="border-t border-slate-200">
              <td class="py-3"><?= h(date('d/m/Y H:i', strtotime($item['created_at']))) ?></td>
              <td class="py-3"><?= h($item['file_name']) ?></td>
              <td class="py-3"><?= h(number_format(((int)$item['file_size'])/1024, 1, ',', '.')) ?> KB</td>
              <td class="py-3"><?= h($item['backup_type']) ?></td>
              <td class="py-3"><?php if (!empty($item['file_path'])): ?><a class="underline text-emerald-700" href="index.php?route=admin/backups/download&id=<?= (int)$item['id'] ?>">Baixar</a><?php else: ?>—<?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
