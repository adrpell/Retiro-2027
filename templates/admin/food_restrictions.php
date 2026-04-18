<?php $pageTitle = 'Alimentação • Restrições'; ?>
<div class="rt-admin-shell"><?php include __DIR__ . '/../partials/admin_sidebar.php'; ?><main class="rt-admin-content p-4 lg:p-8"><div class="rt-admin-topbar rounded-3xl"><div><div class="rt-admin-title">Restrições alimentares</div><div class="rt-admin-subtitle">Registre observações por participante e acompanhe o resumo consolidado para a equipe da cozinha.</div></div></div>
<?php if ($msg = flash('success')): ?><div class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3"><?= $msg ?></div><?php endif; ?>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 rt-card rounded-3xl p-5 overflow-x-auto">
    <h2 class="text-xl font-semibold mb-4">Participantes</h2>
    <form method="post" action="index.php?route=admin/food-restrictions-save">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <table class="min-w-full text-sm"><thead><tr class="text-left text-slate-500"><th class="py-2 pr-4">Participante</th><th class="py-2 pr-4">Inscrição</th><th class="py-2 pr-4">Idade</th><th class="py-2 pr-4">Restrições / observações</th></tr></thead><tbody>
      <?php foreach ($participantsRows as $row): ?>
        <tr class="border-t border-slate-200 align-top"><td class="py-3 pr-4"><div class="font-semibold"><?= h($row['full_name']) ?></div><div class="text-xs text-slate-500"><?= h($row['sex'] ?: 'Sexo não informado') ?></div></td><td class="py-3 pr-4"><?= h($row['access_code']) ?> · <?= h($row['responsible_name']) ?></td><td class="py-3 pr-4"><?= h($row['age'] !== null ? $row['age'] . ' anos' : '—') ?></td><td class="py-3 pr-4"><textarea name="dietary_notes[<?= h($row['id']) ?>]" rows="2" placeholder="Ex.: vegetariano, sem lactose, alergia a amendoim"><?= h($row['dietary_notes'] ?? '') ?></textarea></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <div class="mt-4"><button class="rt-btn-primary rounded-full px-5 py-3 font-semibold" type="submit">Salvar restrições</button></div>
    </form>
  </div>
  <div class="space-y-6">
    <div class="rt-card rounded-3xl p-5"><h2 class="text-xl font-semibold mb-4">Resumo consolidado</h2><?php if (!$restrictionSummary): ?><div class="text-slate-500">Nenhuma restrição cadastrada ainda.</div><?php else: ?><div class="space-y-3"><?php foreach ($restrictionSummary as $label => $info): ?><div class="rounded-2xl border border-slate-200 px-4 py-3"><div class="font-semibold"><?= h(ucwords(str_replace('_',' ', $label))) ?></div><div class="text-sm text-slate-500 mt-1"><?= (int)$info['count'] ?> participante(s)</div></div><?php endforeach; ?></div><?php endif; ?></div>
    <div class="rt-card rounded-3xl p-5"><h2 class="text-xl font-semibold mb-4">Sugestões</h2><div class="text-sm text-slate-600 space-y-2"><p>Use vírgulas para separar observações.</p><p>Ex.: <strong>vegetariano, sem lactose</strong>.</p><p>O resumo é usado pela equipe da cozinha junto com o cardápio.</p></div></div>
  </div>
</div></main></div>