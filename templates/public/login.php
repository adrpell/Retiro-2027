<div class="max-w-7xl mx-auto p-4 lg:p-6">
  <div class="rt-card rounded-[2rem] shadow-xl p-6 lg:p-8">
    <div class="mb-8">
      <a href="<?= h(route_url()) ?>" class="text-sm text-slate-500 underline">Voltar ao portal</a>
      <div class="rt-chip mb-3">Acesso do inscrito</div><h1 class="text-3xl lg:text-4xl font-bold mt-2">Entrar como inscrito</h1>
      <p class="text-slate-600 mt-2">Se você cadastrou seu e-mail na inscrição, o acesso pode ser feito também por ele. Sem e-mail cadastrado, continue usando código e o início do nome do responsável.</p>
    </div>
    <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <?php if (!$group): ?>
    <form method="post" class="grid md:grid-cols-3 gap-4 mb-8 rt-admin-panel rounded-[1.5rem] p-4 border border-slate-200">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="rt-input-wrap"><label>E-mail cadastrado ou código de acesso</label><input type="text" name="login" value="<?= h($login ?? '') ?>" placeholder="email@exemplo.com ou RET001"></div>
      <div class="rt-input-wrap"><label>Nome do responsável</label><input type="text" name="responsible_name" value="<?= h($name ?? '') ?>" placeholder="Necessário apenas se não usar e-mail"></div>
      <div class="rt-input-wrap flex items-end"><button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold w-full">Acessar inscrição</button></div>
    </form>
    <?php else: ?>
      <div class="mb-8 rounded-2xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-emerald-800">Inscrição localizada com sucesso: <strong><?= h($group['access_code']) ?></strong> · <?= h($group['responsible_name']) ?></div>
    <?php endif; ?>

    <?php if ($login && ((filter_var($login, FILTER_VALIDATE_EMAIL)) || $name) && !$group): ?>
      <div class="rounded-xl bg-amber-50 text-amber-800 px-4 py-3">Nenhuma inscrição encontrada com os dados informados.</div>
    <?php endif; ?>

    <?php if ($group): ?>
      <form method="post" action="<?= h(route_url('lookup/save')) ?>" enctype="multipart/form-data" class="space-y-6 rt-price-form" data-role="price-form">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="group_id" value="<?= h($group['id']) ?>">
        <input type="hidden" name="access_code" value="<?= h($group['access_code']) ?>">
        <input type="hidden" name="lookup_login" value="<?= h($login ?? '') ?>">
        <input type="hidden" name="lookup_name" value="<?= h($name ?? '') ?>">
        <div class="grid lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <div class="rounded-[1.5rem] bg-slate-50 p-5 border border-slate-200/80">
              <h2 class="font-semibold text-xl mb-4">Dados da inscrição</h2>
              <?php $resp = null; foreach ($participants as $p) { if ($p['is_responsible']) { $resp = $p; break; } } ?>
              <div class="grid md:grid-cols-2 gap-4">
                <div class="rt-input-wrap"><label>Código</label><input type="text" value="<?= h($group['access_code']) ?>" readonly></div>
                <div class="rt-input-wrap"><label>Tipo</label><input type="text" value="<?= h(ucfirst($group['registration_type'])) ?>" readonly></div>
                <div class="rt-input-wrap"><label>Nome do responsável</label><input type="text" name="responsible_name" value="<?= h($group['responsible_name']) ?>" data-role="prefill-name" data-prefill-value="<?= h($group['responsible_name']) ?>"></div>
                <div class="rt-input-wrap"><label>Idade do responsável</label><input type="number" name="responsible_age" min="0" max="120" value="<?= h($group['responsible_age']) ?>" data-role="age" data-target="responsible"></div>
                <div class="rt-input-wrap"><label>Telefone</label><input type="text" name="responsible_phone" oninput="formatPhoneInput(this)" value="<?= h($group['responsible_phone']) ?>"></div>
                <div class="rt-input-wrap"><label>E-mail</label><input type="email" name="responsible_email" value="<?= h($group['responsible_email']) ?>"></div>
                <div class="rt-input-wrap"><label>Sexo do responsável</label><select name="responsible_sex"><option value="">Selecione</option><option value="M" <?= (($resp['sex'] ?? '')==='M')?'selected':'' ?>>Masculino</option><option value="F" <?= (($resp['sex'] ?? '')==='F')?'selected':'' ?>>Feminino</option></select></div>
                <div class="rt-input-wrap"><label>Acomodação do responsável</label><select name="responsible_accommodation" data-role="accommodation" data-target="responsible"><option value="chale" <?= (($resp['accommodation_choice'] ?? '')==='chale')?'selected':'' ?>>Chalé</option><option value="alojamento" <?= (($resp['accommodation_choice'] ?? '')==='alojamento')?'selected':'' ?>>Alojamento</option><option value="casa" <?= (($resp['accommodation_choice'] ?? '')==='casa')?'selected':'' ?>>Dormir em casa</option></select><p class="text-xs text-slate-500 mt-2">Valor carregado: <strong data-role="price-display" data-target="responsible"><?= h(money_br($resp['calculated_value'] ?? 0)) ?></strong></p></div>
                <div class="rt-input-wrap"><label>Forma de pagamento</label><select name="payment_method"><option value="pix" <?= $group['payment_method']==='pix'?'selected':'' ?>>Pix</option><option value="dinheiro" <?= $group['payment_method']==='dinheiro'?'selected':'' ?>>Dinheiro</option><option value="cartao" <?= $group['payment_method']==='cartao'?'selected':'' ?>>Cartão</option><option value="transferencia" <?= $group['payment_method']==='transferencia'?'selected':'' ?>>Transferência</option><option value="nao_definido" <?= $group['payment_method']==='nao_definido'?'selected':'' ?>>Não definido</option></select></div>
                <div class="rt-input-wrap md:col-span-2"><label>Restrições alimentares do responsável</label><input type="text" name="responsible_dietary_notes" value="<?= h($resp['dietary_notes'] ?? '') ?>" placeholder="Opcional"></div>
                <div class="rt-input-wrap"><label>Parcelas</label><input type="number" name="installments" min="1" max="<?= h((int)setting($pdo, 'max_installments', '10')) ?>" value="<?= h($group['installments']) ?>"><p class="text-xs text-slate-500 mt-2">Preferencialmente até o dia <?= h(setting($pdo, 'payment_deadline_day', '10')) ?> de cada mês.</p></div>
                <div class="rt-input-wrap md:col-span-2"><label>Observações</label><textarea name="notes" rows="4"><?= h($group['notes']) ?></textarea></div>
              </div>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-5 border border-slate-200/80">
              <div class="flex items-center justify-between gap-3 mb-4"><h2 class="font-semibold text-xl">Participantes</h2><button type="button" class="rounded-xl bg-slate-800 text-white px-4 py-2 text-sm" onclick="addParticipantRow('lookup-new-participants', true)">Adicionar familiar</button></div>
              <?php foreach ($participants as $idx => $participant): ?>
                <?php if ($participant['is_responsible']) { continue; } ?>
                <div class="participant-row grid md:grid-cols-6 gap-4 mb-4 border-t border-slate-200 pt-4">
                  <input type="hidden" name="participant_id[]" value="<?= h($participant['id']) ?>">
                  <div class="rt-input-wrap"><label>Nome</label><input type="text" name="participant_name[]" value="<?= h($participant['full_name']) ?>"></div>
                  <div class="rt-input-wrap"><label>Idade</label><input type="number" name="participant_age[]" min="0" max="120" value="<?= h($participant['age']) ?>" data-role="age"></div>
                  <div class="rt-input-wrap"><label>Sexo</label><select name="participant_sex[]"><option value="">Selecione</option><option value="M" <?= $participant['sex']==='M'?'selected':'' ?>>Masculino</option><option value="F" <?= $participant['sex']==='F'?'selected':'' ?>>Feminino</option></select></div>
                  <div class="rt-input-wrap"><label>Acomodação</label><select name="participant_accommodation[]" data-role="accommodation"><option value="chale" <?= $participant['accommodation_choice']==='chale'?'selected':'' ?>>Chalé</option><option value="alojamento" <?= $participant['accommodation_choice']==='alojamento'?'selected':'' ?>>Alojamento</option><option value="casa" <?= $participant['accommodation_choice']==='casa'?'selected':'' ?>>Dormir em casa</option></select></div>
                  <div class="rt-input-wrap"><label>Valor</label><input type="text" data-role="price-display" value="<?= h(money_br($participant['calculated_value'])) ?>" readonly></div>
                  <div class="rt-input-wrap"><label>Excluir</label><input type="checkbox" name="participant_delete[<?= $idx ?>]" value="1"></div>
                  <div class="rt-input-wrap md:col-span-6"><label>Restrições alimentares</label><input type="text" name="participant_dietary_notes[]" value="<?= h($participant['dietary_notes'] ?? '') ?>" placeholder="Opcional"></div>
                </div>
              <?php endforeach; ?>
              <div id="lookup-new-participants"></div>
            </div>
          </div>
          <div class="space-y-6">
            <div class="rounded-[1.5rem] bg-slate-50 p-5 border border-slate-200/80">
              <h2 class="font-semibold text-xl mb-3">Financeiro</h2>
              <div class="grid grid-cols-2 gap-3 text-sm">
                <div><strong>Forma:</strong><br><?= h(payment_label($group['payment_method'])) ?></div>
                <div><strong>Status:</strong><br><?= h(ucfirst($group['financial_status'])) ?></div>
                <div><strong>Valor sugerido atual:</strong><br><?= h(money_br($group['suggested_value'])) ?></div>
                <div><strong>Pago:</strong><br><?= h(money_br($group['amount_paid'])) ?></div>
                <div><strong>Pendente:</strong><br><?= h(money_br($group['amount_pending'])) ?></div>
                <div><strong>Parcelas totais:</strong><br><?= h($paymentProgress['total']) ?></div>
                <div><strong>Parcelas pagas:</strong><br><?= h($paymentProgress['paid']) ?></div>
                <div><strong>Parcelas faltantes:</strong><br><?= h($paymentProgress['remaining']) ?></div>
              </div>
              <div class="pt-3 mt-3 border-t border-slate-200"><strong>Prévia com as novas escolhas:</strong> <span class="text-indigo-700 font-semibold" data-role="grand-total"><?= h(money_br($group['suggested_value'])) ?></span></div>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-5 border border-slate-200/80">
              <h2 class="font-semibold text-xl mb-3">Comprovante da inscrição</h2>
              <?php if ($group['receipt_file']): ?><a class="underline text-indigo-600 block mb-3" target="_blank" href="<?= h($group['receipt_file']) ?>">Abrir comprovante atual</a><?php endif; ?>
              <div class="rt-input-wrap"><label>Substituir comprovante</label><input type="file" name="receipt"></div>
            </div>
            <button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold w-full">Salvar alterações</button>
            <div class="rounded-2xl bg-white border border-slate-200 p-5">
              <h2 class="font-semibold text-xl mb-3">Registrar pagamento</h2>
              <p class="text-sm text-slate-600 mb-4">Use esta área somente após acessar a inscrição. O número de parcelas pagas e faltantes é atualizado automaticamente. O parcelamento pode ir até 10x, preferencialmente até o dia <?= h(setting($pdo, 'payment_deadline_day', '10')) ?> de cada mês.</p>
              <form method="post" action="<?= h(route_url('lookup/payment')) ?>" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="group_id" value="<?= h($group['id']) ?>">
                <input type="hidden" name="access_code" value="<?= h($group['access_code']) ?>">
                <input type="hidden" name="lookup_login" value="<?= h($login ?? '') ?>">
                <input type="hidden" name="lookup_name" value="<?= h($name ?? '') ?>">
                <input type="hidden" name="payment_method" value="<?= h($group['payment_method'] === 'nao_definido' ? 'pix' : $group['payment_method']) ?>">
                <div class="rt-input-wrap"><label>Forma de pagamento desta inscrição</label><input type="text" value="<?= h(payment_label($group['payment_method'] === 'nao_definido' ? 'pix' : $group['payment_method'])) ?>" readonly></div>
                <div class="mb-4 rounded-2xl bg-indigo-50 border border-indigo-100 px-4 py-3 text-sm text-slate-700"><p><strong><?= h(setting($pdo, 'financial_confirmation_note', 'A inscrição só será confirmada mediante aceno financeiro.')) ?></strong></p><p class="mt-1">Transferências: <strong><?= h(setting($pdo, 'pix_beneficiary', 'Igreja Cristã Nova Vida Catedral')) ?></strong> · PIX (CNPJ): <strong><?= h(setting($pdo, 'pix_key', '03.102.014/0001-04')) ?></strong> · Comprovantes: <strong><?= h(setting($pdo, 'payment_receipt_contact', 'Diácono Cláudio')) ?></strong>.</p></div><div class="grid grid-cols-2 gap-4">
                  <div class="rt-input-wrap"><label>Parcela</label><input type="number" name="installment_number" min="1" max="<?= h($paymentProgress['total']) ?>" value="<?= h($paymentProgress['next']) ?>"></div>
                  <div class="rt-input-wrap"><label>Data do pagamento</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"></div>
                </div>
                <div class="rt-input-wrap"><label>Valor pago</label><input type="text" name="amount_paid" placeholder="Ex.: 650,00"></div>
                <div class="rt-input-wrap"><label>Comprovante do pagamento</label><input type="file" name="payment_receipt"></div>
                <div class="rt-input-wrap"><label>Observações do pagamento</label><textarea name="notes" rows="3" placeholder="Opcional"></textarea></div>
                <button class="rounded-2xl bg-emerald-600 text-white px-5 py-3 font-semibold w-full">Registrar pagamento</button>
              </form>
              <?php if (!empty($paymentsRows)): ?>
              <div class="mt-5 pt-4 border-t border-slate-200">
                <h3 class="font-semibold mb-3">Histórico de pagamentos</h3>
                <div class="space-y-3 text-sm">
                  <?php foreach ($paymentsRows as $payment): ?>
                    <div class="rounded-xl border border-slate-200 px-3 py-3 bg-slate-50">
                      <div class="flex items-center justify-between gap-3">
                        <div><strong><?= h($payment['installment_number']) ?>ª parcela</strong> · <?= h(money_br($payment['amount_paid'])) ?></div>
                        <div class="text-slate-500"><?= h($payment['payment_date']) ?></div>
                      </div>
                      <div class="mt-1 text-slate-600">Forma: <?= h(payment_label($payment['payment_method'])) ?></div>
                      <?php if (!empty($payment['receipt_file'])): ?><a class="underline text-indigo-600 mt-1 inline-block" target="_blank" href="<?= h($payment['receipt_file']) ?>">Abrir comprovante desta parcela</a><?php endif; ?>
                      <?php if (!empty($payment['notes'])): ?><div class="mt-1 text-slate-600"><?= h($payment['notes']) ?></div><?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
