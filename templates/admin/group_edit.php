<div class="rt-admin-shell">
<?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Editar inscrição</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
<main class="rt-admin-content p-6 lg:p-8">
  <div class="mb-6"><a href="index.php?route=admin/groups" class="text-sm underline text-slate-500">Voltar para inscrições</a><h1 class="text-3xl font-bold mt-2">Editar inscrição</h1></div>
  <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
  <form method="post" action="index.php?route=admin/group-save" enctype="multipart/form-data" class="rt-price-form" data-role="price-form">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="group_id" value="<?= h($group['id']) ?>">
    <div class="rt-card rounded-3xl shadow p-6 mb-6 grid md:grid-cols-2 gap-4">
      <?php $resp = null; foreach($participants as $pp){ if($pp['is_responsible']) { $resp = $pp; break; }} ?>
      <div class="rt-input-wrap"><label>Código</label><input type="text" value="<?= h($group['access_code']) ?>" readonly></div>
      <div class="rt-input-wrap"><label>Origem</label><input type="text" value="<?= h($group['source']) ?>" readonly></div>
      <div class="rt-input-wrap"><label>Responsável</label><input type="text" name="responsible_name" value="<?= h($group['responsible_name']) ?>"></div>
      <div class="rt-input-wrap"><label>Idade</label><input type="number" name="responsible_age" value="<?= h($group['responsible_age']) ?>" data-role="age" data-target="responsible"></div>
      <div class="rt-input-wrap"><label>Telefone</label><input type="text" name="responsible_phone" value="<?= h($group['responsible_phone']) ?>" oninput="formatPhoneInput(this)"></div>
      <div class="rt-input-wrap"><label>E-mail</label><input type="email" name="responsible_email" value="<?= h($group['responsible_email']) ?>"></div>
      <div class="rt-input-wrap"><label>Sexo do responsável</label><select name="responsible_sex"><option value="">Selecione</option><option value="M" <?= (($resp['sex'] ?? '')==='M')?'selected':'' ?>>Masculino</option><option value="F" <?= (($resp['sex'] ?? '')==='F')?'selected':'' ?>>Feminino</option></select></div>
      <div class="rt-input-wrap"><label>Acomodação do responsável</label><select name="responsible_accommodation" data-role="accommodation" data-target="responsible"><option value="chale" <?= (($resp['accommodation_choice'] ?? '')==='chale')?'selected':'' ?>>Chalé</option><option value="alojamento" <?= (($resp['accommodation_choice'] ?? '')==='alojamento')?'selected':'' ?>>Alojamento</option><option value="casa" <?= (($resp['accommodation_choice'] ?? '')==='casa')?'selected':'' ?>>Dormir em casa</option></select><p class="text-xs text-slate-500 mt-2">Valor carregado: <strong data-role="price-display" data-target="responsible"><?= h(money_br($resp['calculated_value'] ?? 0)) ?></strong></p></div>
      <div class="rt-input-wrap"><label>Tipo</label><select name="registration_type"><option value="individual" <?= $group['registration_type']==='individual'?'selected':'' ?>>Individual</option><option value="familia" <?= $group['registration_type']==='familia'?'selected':'' ?>>Família</option></select></div>
      <div class="rt-input-wrap"><label>Pagamento</label><select name="payment_method"><option value="pix" <?= $group['payment_method']==='pix'?'selected':'' ?>>Pix</option><option value="dinheiro" <?= $group['payment_method']==='dinheiro'?'selected':'' ?>>Dinheiro</option><option value="cartao" <?= $group['payment_method']==='cartao'?'selected':'' ?>>Cartão</option><option value="transferencia" <?= $group['payment_method']==='transferencia'?'selected':'' ?>>Transferência</option><option value="nao_definido" <?= $group['payment_method']==='nao_definido'?'selected':'' ?>>Não definido</option></select></div>
      <div class="rt-input-wrap"><label>Parcelas</label><input type="number" name="installments" value="<?= h($group['installments']) ?>"></div>
      <div class="rt-input-wrap"><label>Status</label><select name="status"><option value="intencao" <?= $group['status']==='intencao'?'selected':'' ?>>Intenção</option><option value="confirmado" <?= $group['status']==='confirmado'?'selected':'' ?>>Confirmado</option><option value="espera" <?= $group['status']==='espera'?'selected':'' ?>>Espera</option><option value="cancelado" <?= $group['status']==='cancelado'?'selected':'' ?>>Cancelado</option></select></div>
      <div class="rt-input-wrap md:col-span-2"><label>Restrições alimentares do responsável</label><input type="text" name="responsible_dietary_notes" value="<?= h($resp['dietary_notes'] ?? '') ?>" placeholder="Opcional"></div>
      <div class="rt-input-wrap"><label>Comprovante atual</label><?php if (!empty($group['receipt_file'])): ?><a class="underline text-indigo-600 block mt-2" target="_blank" href="<?= h($group['receipt_file']) ?>">Abrir comprovante</a><?php else: ?><input type="text" value="Nenhum comprovante enviado" readonly><?php endif; ?></div>
      <div class="rt-input-wrap"><label>Novo comprovante</label><input type="file" name="receipt"></div>
      <div class="rt-input-wrap md:col-span-2"><label>Observações</label><textarea name="notes" rows="4"><?= h($group['notes']) ?></textarea></div>
    </div>
    <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
      <div class="flex items-center justify-between gap-3 mb-4"><h2 class="text-xl font-semibold">Participantes vinculados</h2><button type="button" class="rounded-xl bg-slate-800 text-white px-4 py-2 text-sm" onclick="addParticipantRow('admin-new-participants', true)">Adicionar participante</button></div>
      <div class="space-y-4">
      <?php foreach($participants as $idx => $p): if($p['is_responsible']) continue; ?>
        <div class="participant-row grid md:grid-cols-6 gap-4 border-t border-slate-200 pt-4">
          <input type="hidden" name="participant_id[]" value="<?= h($p['id']) ?>">
          <div class="rt-input-wrap"><label>Nome</label><input type="text" name="participant_name[]" value="<?= h($p['full_name']) ?>"></div>
          <div class="rt-input-wrap"><label>Idade</label><input type="number" name="participant_age[]" value="<?= h($p['age']) ?>" data-role="age"></div>
          <div class="rt-input-wrap"><label>Sexo</label><select name="participant_sex[]"><option value="">Selecione</option><option value="M" <?= $p['sex']==='M'?'selected':'' ?>>Masculino</option><option value="F" <?= $p['sex']==='F'?'selected':'' ?>>Feminino</option></select></div>
          <div class="rt-input-wrap"><label>Acomodação</label><select name="participant_accommodation[]" data-role="accommodation"><option value="chale" <?= $p['accommodation_choice']==='chale'?'selected':'' ?>>Chalé</option><option value="alojamento" <?= $p['accommodation_choice']==='alojamento'?'selected':'' ?>>Alojamento</option><option value="casa" <?= $p['accommodation_choice']==='casa'?'selected':'' ?>>Dormir em casa</option></select></div>
          <div class="rt-input-wrap"><label>Valor</label><input type="text" data-role="price-display" value="<?= h(money_br($p['calculated_value'])) ?>" readonly></div>
          <div class="rt-input-wrap"><label>Excluir</label><input type="checkbox" name="participant_delete[<?= $idx ?>]" value="1"></div>
          <div class="rt-input-wrap md:col-span-6"><label>Restrições alimentares</label><input type="text" name="participant_dietary_notes[]" value="<?= h($p['dietary_notes'] ?? '') ?>" placeholder="Opcional"></div>
        </div>
      <?php endforeach; ?>
      </div>
      <div id="admin-new-participants"></div>
      <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-indigo-50 border border-indigo-100 px-5 py-4">
        <div>
          <p class="text-sm text-slate-600">Prévia do valor sugerido com as escolhas atuais</p>
          <p class="text-2xl font-bold text-indigo-700" data-role="grand-total"><?= h(money_br($group['suggested_value'])) ?></p>
        </div>
        <div class="flex flex-wrap gap-3"><button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold">Salvar inscrição</button><button type="submit" formaction="index.php?route=admin/group-delete" formnovalidate onclick="return confirm('Excluir esta inscrição e todos os vínculos associados?');" class="rounded-2xl border border-red-300 bg-red-50 px-6 py-3 font-semibold text-red-700">Excluir inscrição</button></div>
      </div>
    </div>
  </form>
</main></div>
