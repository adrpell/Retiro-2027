<?php
$priceAdultChale = (float)setting($pdo, 'price_adult_chale', '600');
$priceAdultAloj = (float)setting($pdo, 'price_adult_alojamento', '600');
$priceAdultCasa = (float)setting($pdo, 'price_adult_casa', '600');
$priceChildChale = (float)setting($pdo, 'price_child_chale', '300');
$priceChildAloj = (float)setting($pdo, 'price_child_alojamento', '300');
$priceChildCasa = (float)setting($pdo, 'price_child_casa', '300');
$discountPercent = (float)setting($pdo, 'family_discount_percent', '0');
$fee = (float)setting($pdo, 'registration_fee', '0');
?>
<script>
const RT_PRICES = {
  courtesy: 0,

  child: {
    chale: <?= json_encode($priceChildChale ?? 350) ?>,
    alojamento: <?= json_encode($priceChildAloj ?? 300) ?>,
    casa: <?= json_encode($priceChildCasa ?? 300) ?>
  },

  adult: {
    chale: <?= json_encode($priceAdultChale ?? 700) ?>,
    alojamento: <?= json_encode($priceAdultAloj ?? 600) ?>,
    casa: <?= json_encode($priceAdultCasa ?? 600) ?>
  },

  familyDiscountPercent: <?= json_encode($discountPercent ?? 0) ?>,
  registrationFee: <?= json_encode($fee ?? 0) ?>
};

function normalizeAccommodation(accommodation){
  let acc = (accommodation || '').toString().toLowerCase().trim();

  if (acc === 'chalé') acc = 'chale';
  if (acc === 'dorme em casa') acc = 'casa';
  if (acc === 'casa') acc = 'casa';
  if (acc === 'alojamento masculino' || acc === 'alojamento feminino') acc = 'alojamento';

  if (!['chale', 'alojamento', 'casa'].includes(acc)) {
    acc = 'alojamento';
  }

  return acc;
}
function calcParticipantValue(age, accommodation){
  const ageNum =
    age === '' || age === null || typeof age === 'undefined'
      ? null
      : parseInt(age, 10);

  const acc = normalizeAccommodation(accommodation);

  if (ageNum !== null && !Number.isNaN(ageNum) && ageNum <= 2) {
    return Number(RT_PRICES.courtesy || 0);
  }

  if (ageNum !== null && !Number.isNaN(ageNum) && ageNum <= 9) {
    return Number((RT_PRICES.child && RT_PRICES.child[acc]) || 0);
  }

  return Number((RT_PRICES.adult && RT_PRICES.adult[acc]) || 0);
}

function formatPhoneInput(el){
  let digits = el.value.replace(/\D/g,'').slice(0,11);
  if(digits.length >= 11){ el.value = `(${digits.slice(0,2)}) ${digits.slice(2,7)}-${digits.slice(7,11)}`; }
  else if(digits.length >= 10){ el.value = `(${digits.slice(0,2)}) ${digits.slice(2,6)}-${digits.slice(6,10)}`; }
  else { el.value = digits; }
}
function toggleFamilyFields(selectId, targetId){
  const s = document.getElementById(selectId); const t = document.getElementById(targetId);
  if(!s || !t) return; t.style.display = s.value === 'sim' ? 'block' : 'none';
}
function moneyBr(value){ return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(value||0)); }
function participantRows(form){ return Array.from(form.querySelectorAll('.participant-row')); }
function recalcRow(row){
  const ageInput = row.querySelector('[data-role="age"]');
  const accInput = row.querySelector('[data-role="accommodation"]');
  const display = row.querySelector('[data-role="price-display"]');
  if(!display) return 0;
  const value = calcParticipantValue(ageInput ? ageInput.value : '', accInput ? accInput.value : 'alojamento');
  display.value = moneyBr(value);
  if(display.tagName !== 'INPUT') display.textContent = moneyBr(value);
  row.dataset.priceValue = value;
  return value;
}
function recalculateFormulario(form){
  if(!form) return;
  let total = 0;
  const addFamilySelect = form.querySelector('#add_family');
  const includeFamily = !addFamilySelect || addFamilySelect.value === 'sim';
  const respAcc = form.querySelector('[name="responsible_accommodation"]');
  const respAge = form.querySelector('[name="responsible_age"]');
  const respDisplay = form.querySelector('[data-role="price-display"][data-target="responsible"]');
  if(respDisplay){
    const v = calcParticipantValue(respAge ? respAge.value : '', respAcc ? respAcc.value : 'alojamento');
    if(respDisplay.tagName === 'INPUT') respDisplay.value = moneyBr(v); else respDisplay.textContent = moneyBr(v);
    total += v;
  }
  participantRows(form).forEach(row => {
    const nameInput = row.querySelector('input[name="participant_name[]"], input[name="new_participant_name[]"]');
    if(!includeFamily && form.querySelector('#family_fields') && form.querySelector('#family_fields').style.display === 'none') return;
    if(nameInput && nameInput.value.trim() === '' && row.closest('#lookup-new-participants, #admin-new-participants, #register-participants') && row.querySelector('input[name="new_participant_name[]"]')) { recalcRow(row); return; }
    const del = row.querySelector('input[type="checkbox"][name^="participant_delete"]');
    const value = recalcRow(row);
    if(!(del && del.checked) && (!nameInput || nameInput.value.trim() !== '' || row.querySelector('input[name="participant_id[]"]'))) total += value;
  });
  const countRows = participantRows(form).filter(row => {
    const del = row.querySelector('input[type="checkbox"][name^="participant_delete"]');
    const nameInput = row.querySelector('input[name="participant_name[]"], input[name="new_participant_name[]"]');
    return !(del && del.checked) && nameInput && nameInput.value.trim() !== '';
  }).length + 1;
  if(countRows > 1 && RT_PRICES.familyDiscountPercent > 0){ total -= total * (RT_PRICES.familyDiscountPercent/100); }
  total += Number(RT_PRICES.registrationFee || 0);
  const grand = form.querySelector('[data-role="grand-total"]');
  if(grand) grand.textContent = moneyBr(total);
}
function bindPriceForm(form){
  form.addEventListener('input', () => recalculateFormulario(form));
  form.addEventListener('change', () => recalculateFormulario(form));
  recalculateFormulario(form);
}
function addParticipantRow(targetId, isNew = false){
  const target = document.getElementById(targetId);
  if(!target) return;
  const wrapper = document.createElement('div');
  wrapper.className = 'participant-row grid md:grid-cols-6 gap-4 mb-4 border-t border-slate-200 pt-4';
  wrapper.innerHTML = `
    <div class="rt-input-wrap"><label>Nome</label><input type="text" name="${isNew ? 'new_participant_name[]' : 'participant_name[]'}"></div>
    <div class="rt-input-wrap"><label>Idade</label><input type="number" name="${isNew ? 'new_participant_age[]' : 'participant_age[]'}" min="0" max="120" data-role="age"></div>
    <div class="rt-input-wrap"><label>Sexo</label><select name="${isNew ? 'new_participant_sex[]' : 'participant_sex[]'}"><option value="">Selecione</option><option value="M">Masculino</option><option value="F">Feminino</option></select></div>
    <div class="rt-input-wrap"><label>Acomodação</label><select name="${isNew ? 'new_participant_accommodation[]' : 'participant_accommodation[]'}" data-role="accommodation"><option value="chale">Chalé</option><option value="alojamento" selected>Alojamento</option><option value="casa">Dormir em casa</option></select></div>
    <div class="rt-input-wrap"><label>Valor</label><input type="text" data-role="price-display" readonly></div>
    <div class="rt-input-wrap"><label>Excluir</label><button type="button" class="rounded-xl bg-red-50 text-red-700 px-3 py-2 border border-red-200" onclick="this.closest('.participant-row').remove(); recalculateFormulario(this.form)">Remover</button></div>
    <div class="rt-input-wrap md:col-span-6"><label>Restrições alimentares</label><input type="text" name="${isNew ? 'new_participant_dietary_notes[]' : 'participant_dietary_notes[]'}" placeholder="Opcional"></div>`;
  target.appendChild(wrapper);
  const form = target.closest('form');
  if(form) recalculateFormulario(form);
}


function setAdminTheme(mode){
  const dark = mode === 'dark';
  document.documentElement.classList.toggle('rt-dark', dark);
  document.body.classList.toggle('rt-dark', dark);
  document.querySelectorAll('#rtThemeToggleDesktop, #rtThemeToggleMobile, #rtThemeToggleDesktopInline').forEach(btn => {
    btn.textContent = dark ? '☀️' : '🌙';
    btn.setAttribute('aria-label', dark ? 'Ativar tema claro' : 'Ativar tema escuro');
  });
  try { localStorage.setItem('rt-admin-theme', dark ? 'dark' : 'light'); } catch(e) {}
}
function initAdminTheme(){
  let mode = 'light';
  try { mode = localStorage.getItem('rt-admin-theme') || 'light'; } catch(e) {}
  setAdminTheme(mode);
  document.querySelectorAll('#rtThemeToggleDesktop, #rtThemeToggleMobile, #rtThemeToggleDesktopInline').forEach(btn => {
    btn.addEventListener('click', function(){
      const next = document.body.classList.contains('rt-dark') ? 'light' : 'dark';
      setAdminTheme(next);
    });
  });
}
function initAdminMenuSections(){
  document.querySelectorAll('[data-menu-toggle]').forEach(btn => {
    btn.addEventListener('click', function(){
      const panel = btn.closest('[data-menu-section]').querySelector('[data-menu-panel]');
      const chevron = btn.querySelector('.rt-menu-chevron');
      if(!panel) return;
      const open = panel.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if(chevron) chevron.classList.toggle('is-open', open);
    });
  });
}

function closeAdminSidebar(){
  const sb = document.getElementById('rtAdminSidebar');
  const ov = document.getElementById('rtAdminSidebarOverlay');
  if(sb) sb.classList.remove('is-open');
  if(ov) ov.classList.remove('is-open');
  document.body.classList.remove('rt-admin-menu-open');
}
function openAdminSidebar(){
  const sb = document.getElementById('rtAdminSidebar');
  const ov = document.getElementById('rtAdminSidebarOverlay');
  if(sb) sb.classList.add('is-open');
  if(ov) ov.classList.add('is-open');
  document.body.classList.add('rt-admin-menu-open');
}
document.addEventListener('DOMContentLoaded', function(){
  initAdminTheme();
  initAdminMenuSections();
  const toggle = document.getElementById('rtAdminMenuToggle');
  const closeBtn = document.getElementById('rtAdminMenuClose');
  const overlay = document.getElementById('rtAdminSidebarOverlay');
  if(toggle) toggle.addEventListener('click', openAdminSidebar);
  if(closeBtn) closeBtn.addEventListener('click', closeAdminSidebar);
  if(overlay) overlay.addEventListener('click', closeAdminSidebar);
  window.addEventListener('resize', function(){ if(window.innerWidth >= 1024) closeAdminSidebar(); });
});
document.querySelectorAll('[data-role="price-form"]').forEach(bindPriceForm);
document.querySelectorAll('[data-role="prefill-name"]').forEach(el => { if (el.dataset.prefillValue) el.value = el.dataset.prefillValue; });
document.addEventListener('change', function(e){
  if (
    e.target.matches('[name*="[age]"]') ||
    e.target.matches('[name*="[accommodation]"]')
  ) {
    refreshParticipantValues();
  }
});

document.addEventListener('input', function(e){
  if (e.target.matches('[name*="[age]"]')) {
    refreshParticipantValues();
  }
});
</script>
</body>
</html>
