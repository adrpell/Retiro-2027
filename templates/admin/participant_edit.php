<div class="rt-admin-shell">
<?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Editar participante</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
<main class="rt-admin-content p-6 lg:p-8">
  <div class="mb-6"><a href="index.php?route=admin/group-edit&id=<?= h($participant['group_id']) ?>" class="text-sm underline text-slate-500">Voltar para inscrição</a><h1 class="text-3xl font-bold mt-2">Editar participante</h1></div>
  <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
  <div class="rt-card rounded-3xl shadow p-6">
    <form method="post" action="index.php?route=admin/participant-save" class="grid md:grid-cols-2 gap-4">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="participant_id" value="<?= h($participant['id']) ?>">
      <input type="hidden" name="group_id" value="<?= h($participant['group_id']) ?>">
      <input type="hidden" name="is_responsible" value="<?= h($participant['is_responsible']) ?>">
      <div class="rt-input-wrap"><label>Código</label><input type="text" value="<?= h($participant['access_code']) ?>" readonly></div>
      <div class="rt-input-wrap"><label>Responsável da inscrição</label><input type="text" value="<?= h($participant['responsible_name']) ?>" readonly></div>
      <div class="rt-input-wrap"><label>Nome</label><input type="text" name="full_name" value="<?= h($participant['full_name']) ?>"></div>
      <div class="rt-input-wrap"><label>Idade</label><input type="number" name="age" value="<?= h($participant['age']) ?>"></div>
      <div class="rt-input-wrap"><label>Sexo</label><select name="sex"><option value="">Selecione</option><option value="M" <?= $participant['sex']==='M'?'selected':'' ?>>Masculino</option><option value="F" <?= $participant['sex']==='F'?'selected':'' ?>>Feminino</option></select></div>
      <div class="rt-input-wrap"><label>Acomodação</label><select name="accommodation_choice"><option value="chale" <?= $participant['accommodation_choice']==='chale'?'selected':'' ?>>Chalé</option><option value="alojamento" <?= $participant['accommodation_choice']==='alojamento'?'selected':'' ?>>Alojamento</option><option value="casa" <?= $participant['accommodation_choice']==='casa'?'selected':'' ?>>Dormir em casa</option></select></div>
      <div class="rt-input-wrap"><label>Faixa etária</label><input type="text" value="<?= h($participant['age_band']) ?>" readonly></div>
      <div class="rt-input-wrap"><label>Valor calculado</label><input type="text" value="<?= h(money_br($participant['calculated_value'])) ?>" readonly></div>
      <div class="rt-input-wrap md:col-span-2"><label>Restrições alimentares</label><input type="text" name="dietary_notes" value="<?= h($participant['dietary_notes'] ?? '') ?>"></div>
      <div><button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold">Salvar participante</button></div>
    </form>
  </div>
</main></div>