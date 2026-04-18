<div class="bg-white/96 text-slate-900 rounded-[1.5rem] p-5 shadow-lg rt-info-muted-card card-parcelamento-home">
  <div class="font-bold text-lg mb-2">Parcelamento</div>
  <p class="text-slate-600"><strong>Período:</strong> <?= h($parcelamento['periodo']) ?></p>
  <p class="text-slate-600 mt-2"><strong>Parcelas máximas:</strong> <?= (int)$parcelamento['total'] ?>x · <strong>Restantes:</strong> <?= (int)$parcelamento['faltantes'] ?></p>
  <p class="text-slate-600 mt-2">Preferencialmente até o dia <?= (int)$parcelamento['prazo_dia'] ?> de cada mês.</p>
  <div class="rt-progress-wrap mt-4" aria-label="Progresso do parcelamento">
    <div class="rt-progress-track"><div class="rt-progress-fill" style="width: <?= (int)$parcelamento['progresso'] ?>%"></div></div>
    <div class="rt-progress-meta"><span><?= (int)$parcelamento['decorridas'] ?> / <?= (int)$parcelamento['total'] ?> janelas</span><span><?= (int)$parcelamento['progresso'] ?>%</span></div>
  </div>
  <div class="parcelamento-status <?= $parcelamento['encerrado'] ? 'status-encerrado' : ($parcelamento['nao_iniciado'] ? 'status-previo' : 'status-ativo') ?>"><?= h($parcelamento['mensagem']) ?></div>
</div>
