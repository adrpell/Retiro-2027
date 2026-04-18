<div class="max-w-5xl mx-auto p-6">
  <div class="rt-card rounded-3xl shadow-xl p-8">
    <div class="flex items-start justify-between gap-4 mb-6">
      <div>
        <a href="<?= h(route_url()) ?>" class="text-sm text-slate-500 underline">Voltar ao portal</a>
        <h1 class="text-3xl font-bold mt-2">Quero me inscrever</h1>
        <p class="text-slate-600 mt-2">Preencha os dados do responsável e, se desejar, adicione familiares participantes. Os valores são atualizados automaticamente conforme a idade informada, mantendo a estrutura financeira atual do sistema.</p>
      </div>
    </div>
    <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="space-y-6 rt-price-form" data-role="price-form">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="grid md:grid-cols-2 gap-5">
        <div class="rt-input-wrap"><label>Nome do responsável</label><input type="text" name="responsible_name" required></div>
        <div class="rt-input-wrap"><label>Idade do responsável</label><input type="number" name="responsible_age" min="0" max="120" data-role="age" data-target="responsible"></div>
        <div class="rt-input-wrap"><label>Telefone</label><input type="text" name="responsible_phone" oninput="formatPhoneInput(this)" placeholder="(21) 99999-9999"></div>
        <div class="rt-input-wrap"><label>E-mail</label><input type="email" name="responsible_email"></div>
        <div class="rt-input-wrap md:col-span-2"><label>Restrições alimentares do responsável</label><input type="text" name="responsible_dietary_notes" placeholder="Ex.: vegetariano, sem lactose, alergia a amendoim"></div>
        <div class="rt-input-wrap"><label>Sexo do responsável</label><select name="responsible_sex"><option value="">Selecione</option><option value="M">Masculino</option><option value="F">Feminino</option></select><p class="text-xs text-slate-500 mt-2">Para alojamento, o sistema direciona automaticamente para o masculino ou feminino conforme o sexo informado.</p></div>
        <div class="rt-input-wrap"><label>Acomodação do responsável</label><select name="responsible_accommodation" data-role="accommodation" data-target="responsible"><option value="chale">Chalé</option><option value="alojamento" selected>Alojamento</option><option value="casa">Dormir em casa</option></select><p class="text-xs text-slate-500 mt-2">Valor carregado: <strong data-role="price-display" data-target="responsible"></strong></p></div>
        <div class="rt-input-wrap"><label>Forma de pagamento</label><select name="payment_method"><option value="pix">Pix</option><option value="dinheiro">Dinheiro</option><option value="cartao">Cartão</option><option value="transferencia">Transferência</option></select></div>
        <div class="rt-input-wrap"><label>Parcelas</label><input type="number" name="installments" min="1" max="<?= h((int)setting($pdo, 'max_installments', '10')) ?>" value="1"><p class="text-xs text-slate-500 mt-2">Parcelamento disponível em até 10x, de abril/26 a janeiro/27, preferencialmente até o dia <?= h(setting($pdo, 'payment_deadline_day', '10')) ?> de cada mês.</p></div>
        <div class="rt-input-wrap md:col-span-2"><label>Deseja adicionar familiares participantes?</label><select id="add_family" name="add_family" onchange="toggleFamilyFields('add_family','family_fields'); recalcularFormulario(this.form)"><option value="nao">Não</option><option value="sim">Sim</option></select></div>
      </div>
      <div id="family_fields" class="rounded-2xl bg-slate-50 p-5" style="display:none;">
        <div class="flex items-center justify-between gap-3 mb-4"><h2 class="font-semibold text-xl">Familiares participantes</h2><button type="button" class="rounded-xl bg-slate-800 text-white px-4 py-2 text-sm" onclick="addParticipantRow('register-participants')">Adicionar familiar</button></div>
        <div id="register-participants">
          <?php for ($i=0; $i<2; $i++): ?>
          <div class="participant-row grid md:grid-cols-6 gap-4 mb-4 border-t border-slate-200 pt-4">
            <div class="rt-input-wrap"><label>Nome</label><input type="text" name="participant_name[]"></div>
            <div class="rt-input-wrap"><label>Idade</label><input type="number" name="participant_age[]" min="0" max="120" data-role="age"></div>
            <div class="rt-input-wrap"><label>Sexo</label><select name="participant_sex[]"><option value="">Selecione</option><option value="M">Masculino</option><option value="F">Feminino</option></select></div>
            <div class="rt-input-wrap"><label>Acomodação</label><select name="participant_accommodation[]" data-role="accommodation"><option value="chale">Chalé</option><option value="alojamento" selected>Alojamento</option><option value="casa">Dormir em casa</option></select></div>
            <div class="rt-input-wrap"><label>Valor</label><input type="text" data-role="price-display" value="" readonly></div>
            <div class="rt-input-wrap md:col-span-6"><label>Restrições alimentares</label><input type="text" name="participant_dietary_notes[]" placeholder="Opcional"></div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="grid md:grid-cols-2 gap-5">
        <div class="rt-input-wrap"><label>Comprovante</label><input type="file" name="receipt"></div>
        <div class="rt-input-wrap"><label>Observações</label><textarea name="notes" rows="4"></textarea></div>
      </div>
      <div class="rounded-2xl bg-indigo-50 border border-indigo-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="w-full text-sm text-slate-700 space-y-1">
          <p><strong><?= h(setting($pdo, 'financial_confirmation_note', 'A inscrição só será confirmada mediante aceno financeiro.')) ?></strong></p>
          <p>Transferências devem ser feitas para <strong><?= h(setting($pdo, 'pix_beneficiary', 'Igreja Cristã Nova Vida Catedral')) ?></strong>. Envie os comprovantes para <strong><?= h(setting($pdo, 'payment_receipt_contact', 'Diácono Cláudio')) ?></strong>.</p>
          <p>PIX (CNPJ): <strong><?= h(setting($pdo, 'pix_key', '03.102.014/0001-04')) ?></strong></p>
        </div>
        <div>
          <p class="text-sm text-slate-600">Valor sugerido carregado conforme as escolhas atuais</p>
          <p class="text-2xl font-bold text-indigo-700" data-role="grand-total">R$ 0,00</p>
        </div>
        <button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold">Enviar inscrição</button>
      </div>
    </form>
  </div>
</div>
