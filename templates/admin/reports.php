<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Relatórios</div>
        <div class="rt-admin-subtitle">Exportações, relatório executivo, impressão em PDF pelo navegador e histórico.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8">
    <div class="mb-6"><h1 class="text-3xl font-bold">Relatórios</h1><p class="text-slate-600">Defina a ordenação do relatório executivo e escolha a saída desejada.</p></div>
    <div class="grid md:grid-cols-3 gap-6 mb-6">
      <div class="rt-card rounded-3xl shadow p-6"><div class="text-slate-500 text-sm">Com comprovante</div><div class="text-3xl font-bold mt-2"><?= h($stats['with_receipt']) ?></div></div>
      <div class="rt-card rounded-3xl shadow p-6"><div class="text-slate-500 text-sm">Famílias</div><div class="text-3xl font-bold mt-2"><?= h($stats['family_groups']) ?></div></div>
      <div class="rt-card rounded-3xl shadow p-6"><div class="text-slate-500 text-sm">Individuais</div><div class="text-3xl font-bold mt-2"><?= h($stats['individual_groups']) ?></div></div>
    </div>

    <div class="rt-card rounded-3xl shadow p-6 mb-6">
      <h2 class="font-semibold text-xl mb-4">Exportações rápidas</h2>
      <div class="flex flex-wrap gap-3">
        <a href="index.php?route=admin/reports&export=groups" class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold">Exportar inscrições (CSV)</a>
        <a href="index.php?route=admin/reports&export=participants" class="rounded-2xl border border-slate-300 px-5 py-3 font-semibold">Exportar participantes (CSV)</a>
        <a href="index.php?route=admin/reports&export=financial" class="rounded-2xl border border-slate-300 px-5 py-3 font-semibold">Exportar financeiro (CSV)</a>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-6">
      <div class="rt-card rounded-3xl shadow p-6 lg:col-span-2">
        <h2 class="font-semibold text-xl mb-4">Relatório executivo</h2>
        <form method="get" action="index.php" class="grid md:grid-cols-2 gap-4">
          <input type="hidden" name="route" value="admin/reports-executive">
          <div>
            <label class="block text-sm font-semibold mb-2">Ordenar por</label>
            <select name="sort_by" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white">
              <option value="access_code">Código da inscrição</option>
              <option value="created_at">Data de criação</option>
              <option value="responsible_name">Nome do responsável</option>
              <option value="suggested_value">Valor sugerido</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">Direção</label>
            <select name="sort_dir" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white">
              <option value="asc">Mais antigo primeiro / A-Z</option>
              <option value="desc">Mais recente primeiro / Z-A</option>
            </select>
          </div>
          <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
            <button class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold" type="submit">Abrir HTML</button>
            <button class="rounded-2xl border border-slate-300 bg-white px-5 py-3 font-semibold" type="submit" name="mode" value="download_html">Exportar HTML</button>
            <button class="rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-3 font-semibold text-emerald-800" type="submit" name="mode" value="pdf">Baixar PDF (navegador)</button>
          </div>
        </form>
      </div>
      <div class="rt-card rounded-3xl shadow p-6">
        <h2 class="font-semibold text-xl mb-4">Enviar por e-mail</h2>
        <form method="post" action="index.php?route=admin/reports-executive&mode=email" class="space-y-4">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <div>
            <label class="block text-sm font-semibold mb-2">E-mail de destino</label>
            <input type="email" name="email_to" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="relatorios@exemplo.com" required>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">Ordenar por</label>
            <select name="sort_by" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white">
              <option value="access_code">Código da inscrição</option>
              <option value="created_at">Data de criação</option>
              <option value="responsible_name">Nome do responsável</option>
              <option value="suggested_value">Valor sugerido</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">Direção</label>
            <select name="sort_dir" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white">
              <option value="asc">Mais antigo primeiro / A-Z</option>
              <option value="desc">Mais recente primeiro / Z-A</option>
            </select>
          </div>
          <button class="rounded-2xl border border-slate-300 px-5 py-3 font-semibold w-full" type="submit">Enviar relatório por e-mail</button>
          <p class="text-xs text-slate-500">O sistema salva o histórico e envia o HTML anexado. O botão PDF usa a impressão do navegador para salvar em PDF.</p>
        </form>
      </div>
    </div>

    <div class="rt-card rounded-3xl shadow p-6">
      <h2 class="font-semibold text-xl mb-4">Histórico recente de relatórios</h2>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="text-left text-slate-500 border-b"><th class="py-2 pr-4">Data</th><th class="py-2 pr-4">Formato</th><th class="py-2 pr-4">Ordenação</th><th class="py-2 pr-4">Status</th><th class="py-2 pr-4">Destino</th><th class="py-2 pr-4">Arquivo</th></tr></thead>
          <tbody>
          <?php foreach (($reportHistory ?? []) as $item): ?>
            <tr class="border-b border-slate-100">
              <td class="py-3 pr-4"><?= h(date('d/m/Y H:i', strtotime($item['created_at']))) ?></td>
              <td class="py-3 pr-4"><?= h($item['output_format']) ?></td>
              <td class="py-3 pr-4"><?= h($item['sort_by']) ?> / <?= h($item['sort_dir']) ?></td>
              <td class="py-3 pr-4"><?= h($item['status']) ?></td>
              <td class="py-3 pr-4"><?= h($item['recipient_email'] ?: '—') ?></td>
              <td class="py-3 pr-4"><?php if (!empty($item['file_path'])): ?>
                  <div class="flex flex-wrap gap-3">
                    <a class="text-emerald-700 underline" href="index.php?route=admin/report-history-view&id=<?= (int)$item['id'] ?>" target="_blank">Abrir</a>
                    <a class="text-slate-700 underline" href="index.php?route=admin/report-history-download&id=<?= (int)$item['id'] ?>">Baixar HTML</a>
                  </div>
                <?php else: ?>—<?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>