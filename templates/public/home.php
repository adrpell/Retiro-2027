<link rel="stylesheet" href="assets/css/public.css">
<script src="https://kit.fontawesome.com/ce62220128.js" crossorigin="anonymous"></script>
<div class="min-h-screen p-4 lg:p-6">
  <div class="max-w-7xl mx-auto space-y-6">
    <section class="rt-hero rounded-[2rem] shadow-2xl p-8 lg:p-10">
      <div class="relative z-10 grid xl:grid-cols-[1.3fr_.95fr] gap-8 items-start">
        <div class="space-y-6">
          <span class="rt-chip bg-white/10 text-teal-50 border border-white/15"><img src="assets/images/logotipo-icnv.png" alt="Logo da ICNV Catedral" style="width: 33px; height: 20px;">&ensp;Portal oficial do retiro</span>
          <div>
          <div class="logo">
            <!-- <img src="assets/images/logotipo-icnv.png" alt="Logo da ICNV Catedral" style="width: 57px; height: 36px;">&ensp; -->
            <h1 class="text-4xl lg:text-5xl font-bold leading-tight"><?= h(setting($pdo,'event_name','Retiro 2027 - ICNV Catedral')) ?></h1>
            </div>
            <p class="text-teal-50/90 text-lg leading-8 mt-4 max-w-3xl">Faça sua inscrição, ou acesse sua conta para o Retiro 2027 da ICNV Catedral.</p>
          </div>
          <div class="grid md:grid-cols-2 gap-4 max-w-4xl">
            <div class="bg-white text-slate-900 rounded-[1.75rem] p-6 shadow-xl">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="text-2xl font-bold">Nova inscrição</div>
                  <p class="text-slate-600 mt-2">Cadastre sua participação individual ou familiar com acomodação e informações financeiras.</p>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-bold"><i class="fas fa-thin fa-address-card"></i></div>
              </div>
              <a class="mt-6 block rounded-2xl rt-btn-primary px-6 py-4 text-center font-semibold text-lg" href="<?= h(route_url('register')) ?>">Fazer inscrição</a>
            </div>
            <div class="bg-white text-slate-900 rounded-[1.75rem] p-6 shadow-xl">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="text-2xl font-bold">Já sou inscrito</div>
                  <p class="text-slate-600 mt-2">Acesse sua inscrição, acompanhe seu financeiro e envie novos comprovantes.</p>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-violet-50 text-violet-700 flex items-center justify-center text-xl font-bold"><i class="fas fa-thin fa-arrow-right-to-bracket"></i></div>
              </div>
              <a class="mt-6 block rounded-2xl rt-btn-secondary px-6 py-4 text-center font-semibold text-lg" href="<?= h(route_url('lookup')) ?>">Acessar minha inscrição</a>
            </div>
          </div>
        </div>
        <div class="bg-white/96 text-slate-900 rounded-[1.75rem] p-6 shadow-xl">
            <div class="flex items-center gap-3 mb-4"><div class="h-10 w-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">$</div><h2 class="text-2xl font-bold">Valores por idade</h2></div>
            <p class="text-teal-50/90 text-lg leading-8 mt-4 max-w-3xl" style="font-size:small";><i>* Valor referente ao Alojamento</i></p>
            <div class="grid sm:grid-cols-3 gap-3 text-center">
              <div class="rounded-2xl bg-slate-50 p-4"><div class="text-sm text-slate-500">0 a 2 anos</div><div class="rt-age-card-value font-bold text-emerald-700 mt-1;" style="font-size:1.2rem;padding: 20px 0px 20px 0px">Cortesia</div></div>
              <div class="rounded-2xl bg-slate-50 p-4"><div class="text-sm text-slate-500">3 a 9 anos</div><div class="rt-age-card-value font-bold text-amber-600 mt-1" style="font-size:1.2rem;padding: 20px 0px 20px 0px">R$ 300,00</div></div>
              <div class="rounded-2xl bg-slate-50 p-4"><div class="text-sm text-slate-500">A partir de 10 anos</div><div class="rt-age-card-value font-bold text-violet-700 mt-1" style="font-size:1.2rem;padding: 20px 0px 20px 0px">R$ 600,00</div></div>
            </div>
            <p class="text-teal-50/90 text-lg leading-8 mt-4 max-w-3xl" style="font-size:small";><i>* Valor referente ao Chalé</i></p>
            <div class="grid sm:grid-cols-3 gap-3 text-center">
              <div class="rounded-2xl bg-slate-50 p-4"><div class="text-sm text-slate-500">0 a 2 anos</div><div class="rt-age-card-value font-bold text-emerald-700 mt-1;" style="font-size:1.2rem;padding: 20px 0px 20px 0px">Cortesia</div></div>
              <div class="rounded-2xl bg-slate-50 p-4"><div class="text-sm text-slate-500">3 a 9 anos</div><div class="rt-age-card-value font-bold text-amber-600 mt-1" style="font-size:1.2rem;padding: 20px 0px 20px 0px">R$ 360,00</div></div>
              <div class="rounded-2xl bg-slate-50 p-4"><div class="text-sm text-slate-500">A partir de 10 anos</div><div class="rt-age-card-value font-bold text-violet-700 mt-1" style="font-size:1.2rem;padding: 20px 0px 20px 0px">R$ 720,00</div></div>
            </div>
          </div>
    </section>
<?php if (($showLandingExtras ?? true) === true): ?>
    <section class="rt-hero rounded-[2rem] shadow-2xl p-8 lg:p-10" style="margin-top:30px";>
        <div class="space-y-4">
          <div class="grid md:grid-cols-2 gap-4">
            <?php $parcelamento = rt2027_parcelamento_status($pdo); ?>
            <?php include __DIR__ . '/../partials/parcelamento_card.php'; ?>
            <div class="bg-white/96 text-slate-900 rounded-[1.5rem] p-5 shadow-lg" style="background-color:#f0f0f0";>
              <div class="font-bold text-lg mb-2">Informações importantes</div>
              <p class="text-slate-600">A inscrição só será confirmada mediante aceno financeiro.</p>
            </div>
          </div>
          <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white/96 text-slate-900 rounded-[1.5rem] p-5 shadow-lg" style="background-color:#f0f0f0";>
              <div class="font-bold text-lg mb-2">Transferências</div>
              <p class="text-slate-600">Igreja Cristã Nova Vida Catedral</p>
              <p class="font-semibold mt-1">PIX (CNPJ): 03.102.014/0001-04</p>
            </div>
            <div class="bg-white/96 text-slate-900 rounded-[1.5rem] p-5 shadow-lg" style="background-color:#f0f0f0";>
              <div class="font-bold text-lg mb-2">Comprovantes</div>
              <p class="text-slate-600">Enviar comprovantes para:</p>
              <p class="font-semibold mt-1"><a href="mailto:email@exemplo.com?subject=Comprovante de Pagamento" target="_blank" rel="noopener noreferrer">Diácono Cláudio</a></p>
            </div>
          </div>
          <section class="rt-hero rounded-[2rem] shadow-2xl p-8 lg:p-10" style="margin-top:30px";>
        <div class="space-y-6">
            <div class="rt-card rounded-[1.75rem] p-6 lg:p-7">
                <h2 class="text-2xl font-bold mb-3" style="color:#767A84";>Acesso administrativo</h2>
                <p class="text-slate-600 mb-5">Entre com seu usuário de administrador para abrir o painel completo, acompanhar ocupação, pagamentos e relatórios.</p>
                <a class="inline-flex items-center rounded-2xl rt-btn-primary px-5 py-3 font-semibold" href="<?= h(route_url('admin/login', [], 'index.php')) ?>">Ir para o login do painel</a>
            </div>
        </div>
      </section>
        </div>
        </div>
    </section>

    <!-- <section class="grid xl:grid-cols-[1.3fr_.95fr] gap-6 items-start">
      <div class="rt-card rounded-[1.75rem] p-6 lg:p-7">
        <div class="flex items-center justify-between gap-4 mb-4">
          <div>
            <h2 class="text-2xl font-bold">Tudo em um só sistema</h2>
            <p class="text-slate-600 mt-2">Inscrição, acomodação, pagamentos, comprovantes e gestão administrativa com visual moderno e organizado.</p>
          </div>
          <span class="rt-chip">Painel em tempo real</span>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div class="rounded-2xl bg-slate-50 p-4">Dashboard com indicadores e gráficos</div>
          <div class="rounded-2xl bg-slate-50 p-4">Acomodação por participante</div>
          <div class="rounded-2xl bg-slate-50 p-4">Financeiro com parcelamento</div>
          <div class="rounded-2xl bg-slate-50 p-4">Upload e histórico de comprovantes</div>
          <div class="rounded-2xl bg-slate-50 p-4">Relatórios de participantes e financeiro</div>
          <div class="rounded-2xl bg-slate-50 p-4">Painel responsivo para desktop e celular</div>
        </div>
      </div>
      </section> -->
<?php endif; ?>
  </div>
</div>
