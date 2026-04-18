<?php $parcelamento = rt2027_parcelamento_status(); ?>
<script>
    function rt2027_parcelamento_status(): array {
    $inicio = new DateTime('2026-04-01');
    $fim = new DateTime('2027-01-31');
    $hoje = new DateTime('today');

    $totalParcelas = 10;

    if ($hoje < $inicio) {
        return [
            'total' => $totalParcelas,
            'decorridas' => 0,
            'faltantes' => $totalParcelas,
            'mes_atual' => 0,
            'prazo_dia' => 10,
            'encerrado' => false,
        ];
    }

    if ($hoje > $fim) {
        return [
            'total' => $totalParcelas,
            'decorridas' => $totalParcelas,
            'faltantes' => 0,
            'mes_atual' => $totalParcelas,
            'prazo_dia' => 10,
            'encerrado' => true,
        ];
    }

    $anos = (int)$hoje->format('Y') - (int)$inicio->format('Y');
    $meses = (int)$hoje->format('n') - (int)$inicio->format('n');
    $decorridas = ($anos * 12) + $meses + 1;

    if ($decorridas < 0) $decorridas = 0;
    if ($decorridas > $totalParcelas) $decorridas = $totalParcelas;

    return [
        'total' => $totalParcelas,
        'decorridas' => $decorridas,
        'faltantes' => max(0, $totalParcelas - $decorridas),
        'mes_atual' => $decorridas,
        'prazo_dia' => 10,
        'encerrado' => false,
    ];
}
</script>