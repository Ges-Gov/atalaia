<?php
/**
 * Composição da Assembleia de Freguesia (redesenhada a 20/10/2026, a pedido — «melhorar e completar»).
 * Secções: topo com o mandato · distribuição dos lugares (hemiciclo + tabela) e participação · Mesa ·
 * vogais (ou, enquanto não estiverem publicados, quantos são e por que lista) · próxima sessão ·
 * saber mais (funcionamento, competências, documentos, sessões) · ligação ao Executivo.
 * Dados: assembleia_composicao (membros), assembleia_eleicao + assembleia_eleicao_listas (migração 083),
 * assembleia_sessoes. Nunca se inventam nomes: vogais não publicados aparecem só como número por lista.
 */
require_once "includes/header.php";
require_once __DIR__ . "/includes/membros.php";

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$membros = $pdo->query("
    SELECT * FROM assembleia_composicao WHERE ativo = 1
    ORDER BY CASE WHEN grupo LIKE 'Mesa%' THEN 1 WHEN grupo = 'Vogais' THEN 2 ELSE 3 END, ordem ASC, nome ASC
")->fetchAll(PDO::FETCH_ASSOC);
$mesa = $vogais = $outros = [];
foreach ($membros as $m) {
    if (stripos($m['grupo'], 'Mesa') === 0) $mesa[] = $m;
    elseif ($m['grupo'] === 'Vogais') $vogais[] = $m;
    else $outros[] = $m;
}

$eleicao = null; $listas = [];
try {
    $eleicao = $pdo->query("SELECT * FROM assembleia_eleicao WHERE ativo = 1 ORDER BY data_eleicao DESC, id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($eleicao) {
        $st = $pdo->prepare("SELECT * FROM assembleia_eleicao_listas WHERE eleicao_id = ? ORDER BY ordem, id");
        $st->execute([$eleicao['id']]);
        $listas = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {}
$totalLugares = $eleicao ? (int)$eleicao['membros_total'] : 0;
$comLugar = array_values(array_filter($listas, fn($l) => (int)$l['mandatos'] > 0));

// O partido de cada membro liga-se à lista pela sigla; a mesma força escreve-se de mais de uma maneira
// (o membro «CDU», a lista oficial «PCP-PEV»; «PSD» / «PPD/PSD»; «CDS» / «CDS-PP»).
$chaveLista = function ($p): string {
    $k = mb_strtolower(trim((string)$p));
    return ['psd' => 'ppd/psd', 'cdu' => 'pcp-pev', 'cds' => 'cds-pp'][$k] ?? $k;
};

// Vogais por publicar: lugares de cada lista menos os membros já publicados dessa lista
$porPublicar = [];
foreach ($comLugar as $l) {
    $publicados = count(array_filter($membros, fn($m) => $chaveLista($m['partido']) === $chaveLista($l['sigla'])));
    $falta = (int)$l['mandatos'] - $publicados;
    if ($falta > 0) $porPublicar[] = [$l, $falta];
}
$nPorPublicar = array_sum(array_map(fn($x) => $x[1], $porPublicar));

$proxima = null;
try {
    $proxima = $pdo->query("SELECT * FROM assembleia_sessoes WHERE ativo = 1 AND estado = 'agendada' AND data_sessao >= CURDATE() ORDER BY data_sessao, hora_sessao LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {}

$iniciais = function (string $nome): string {
    $p = preg_split('/\s+/', trim($nome)) ?: [''];
    return mb_strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : ''));
};
$corLista = function (string $partido) use ($listas, $chaveLista): string {
    foreach ($listas as $l) if ($chaveLista($l['sigla']) === $chaveLista($partido)) return (string)$l['cor'];
    return '';
};
$pct = fn($v) => number_format((float)$v, 2, ',', ' ') . '%';

/* Hemiciclo (v2, 20/10/2026): até 15 lugares numa só fila em arco — cada lista num bloco contínuo, pela ordem da BD
   (esquerda → direita); mais lugares, várias filas. Cada lugar sabe quem o ocupa, se já estiver publicado (Mesa e
   vogais da mesma lista, por ordem) — as iniciais aparecem dentro do lugar. */
$pessoasPorLista = [];
foreach (array_merge($mesa, $vogais) as $m) if (!empty($m['partido'])) $pessoasPorLista[$chaveLista($m['partido'])][] = $m;
$maioria = intdiv($totalLugares, 2) + 1;
$umaFila = $totalLugares <= 15;
$lugares = [];
if ($totalLugares > 0 && $comLugar) {
    if ($umaFila) {
        $pos = [];
        for ($i = 0; $i < $totalLugares; $i++) $pos[] = [M_PI - ($i + .5) * M_PI / $totalLugares, 1];
    } else {
        $filas = $totalLugares <= 25 ? 3 : 4;
        $raios = []; for ($f = 0; $f < $filas; $f++) $raios[] = 1 - $f * (0.42 / max(1, $filas - 1));
        $somaR = array_sum($raios); $pos = []; $restantes = $totalLugares;
        foreach ($raios as $k => $r) {
            $n = $k === $filas - 1 ? $restantes : (int)round($totalLugares * $r / $somaR);
            $restantes -= $n;
            for ($i = 0; $i < $n; $i++) $pos[] = [M_PI - ($n === 1 ? M_PI / 2 : M_PI * $i / ($n - 1)), $r];
        }
        usort($pos, fn($a, $b) => $b[0] <=> $a[0] ?: $b[1] <=> $a[1]);
    }
    $i = 0;
    foreach ($comLugar as $l) {
        $gente = $pessoasPorLista[$chaveLista($l['sigla'])] ?? [];
        for ($k = 0; $k < (int)$l['mandatos'] && $i < count($pos); $k++, $i++) $lugares[] = [$pos[$i][0], $pos[$i][1], $l, $gente[$k] ?? null, $i + 1];
    }
}
?>
<style>
/* Composição da Assembleia (20/10/2026) — tokens da marca; cores das listas vêm da BD (validadas: daltonismo e contraste) */
.asm{ background: #f7f8f5; padding-bottom: clamp(48px, 7vw, 96px); }
.asm-hero{ position: relative; color: #fff; background: linear-gradient(135deg, color-mix(in srgb, var(--cor-principal) 82%, #000), #12161b); padding: clamp(64px, 9vw, 112px) 0 clamp(80px, 9vw, 120px); }
.asm-migalhas{ display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 18px; padding: 0; list-style: none; font-size: 14px; color: rgb(255 255 255 / .82); }
.asm-migalhas li + li::before{ content: "›"; margin-right: 6px; opacity: .7; }
.asm-migalhas a{ color: inherit; text-decoration: none; }
.asm-migalhas a:hover{ text-decoration: underline; }
.asm-kicker{ display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: color-mix(in srgb, var(--cor-secundaria) 55%, #fff); background: rgb(255 255 255 / .1); border: 1px solid rgb(255 255 255 / .22); }
.asm-hero h1{ margin: 16px 0 12px; font-size: clamp(2.1rem, 1.5rem + 2.4vw, 3.4rem); line-height: 1.08; letter-spacing: -.02em; color: #fff; }
.asm-hero p{ margin: 0; max-width: 62ch; font-size: clamp(1.05rem, 1rem + .3vw, 1.2rem); line-height: 1.6; color: rgb(255 255 255 / .9); }
.asm-chips{ display: flex; flex-wrap: wrap; gap: 10px; margin: 24px 0 0; padding: 0; list-style: none; }
.asm-chips li{ display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 999px; background: rgb(255 255 255 / .12); border: 1px solid rgb(255 255 255 / .2); font-size: 15px; font-weight: 600; color: #fff; }
.asm-chips i{ color: color-mix(in srgb, var(--cor-secundaria) 60%, #fff); }

.asm-main{ position: relative; z-index: 2; margin-top: clamp(-48px, -3vw, -24px); }
.asm-sec{ margin-top: clamp(40px, 5vw, 72px); }
.asm-cabeca{ margin-bottom: 22px; max-width: 70ch; }
.asm-kick2{ display: block; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--cor-principal); margin-bottom: 6px; }
.asm-cabeca h2{ margin: 0 0 8px; font-size: clamp(1.6rem, 1.3rem + 1.2vw, 2.25rem); line-height: 1.15; color: #11151b; }
.asm-cabeca p{ margin: 0; font-size: 17px; line-height: 1.6; color: #475569; }
.asm-caixa{ padding: clamp(22px, 3.5vw, 40px); background: #fff; border: 1px solid #e7ebe6; border-radius: 28px; box-shadow: 0 1px 2px rgb(15 23 42 / .04), 0 24px 60px -32px rgb(15 23 42 / .3); }

/* Distribuição dos lugares */
.asm-dist{ display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: clamp(24px, 4vw, 56px); align-items: center; }
.asm-hemi{ position: relative; }
.asm-hemi svg{ display: block; width: 100%; max-width: 560px; height: auto; margin-inline: auto; overflow: visible; }
.asm-anel path{ fill: none; stroke-width: 16; stroke-linecap: butt; }
.asm-maioria line{ stroke: #64748b; stroke-width: 2; stroke-dasharray: 5 5; }
.asm-lugar{ cursor: default; outline: none; }
.asm-lugar circle{ stroke: #fff; stroke-width: 3; transition: transform .2s; transform-box: fill-box; transform-origin: center; filter: drop-shadow(0 4px 6px rgb(15 23 42 / .18)); }
.asm-lugar text{ font-size: 16px; font-weight: 800; fill: #fff; pointer-events: none; letter-spacing: .02em; }
.asm-lugar:hover circle, .asm-lugar:focus circle{ transform: scale(1.1); }
.asm-lugar:focus-visible circle{ stroke: var(--cor-secundaria); stroke-width: 4; }
.asm-hemi__total{ font-size: 52px; font-weight: 800; fill: #11151b; }
.asm-hemi__legenda{ font-size: 16px; font-weight: 600; fill: #475569; }
.asm-hemi__maioria{ font-size: 14px; font-weight: 600; fill: #64748b; }
.asm-hemi__chave{ display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 18px; margin: 14px 0 0; padding: 0; list-style: none; font-size: 13.5px; color: #475569; }
.asm-hemi__chave li{ display: inline-flex; align-items: center; gap: 8px; }
.asm-chave-lugar{ width: 14px; height: 14px; border-radius: 50%; background: #94a3b8; }
.asm-chave-anel{ width: 22px; height: 6px; border-radius: 2px; background: #94a3b8; }
.asm-chave-maioria{ width: 0; height: 16px; border-left: 2px dashed #64748b; }
.asm-tip{ position: absolute; z-index: 3; pointer-events: none; padding: 8px 12px; border-radius: 10px; background: #11151b; color: #fff; font-size: 14px; font-weight: 600; white-space: nowrap; transform: translate(-50%, -130%); opacity: 0; transition: opacity .15s; }
.asm-tip.visivel{ opacity: 1; }
.asm-listas{ margin: 0; padding: 0; list-style: none; display: grid; gap: 12px; }
.asm-lista{ display: grid; grid-template-columns: 18px 1fr auto; gap: 4px 12px; align-items: center; }
.asm-lista__cor{ width: 18px; height: 18px; border-radius: 5px; }
.asm-lista strong{ font-size: 17px; color: #11151b; }
.asm-lista strong small{ display: block; font-size: 13.5px; font-weight: 500; color: #64748b; }
.asm-lista__lugares{ font-size: 17px; font-weight: 700; color: #11151b; text-align: right; }
.asm-lista__lugares small{ display: block; font-size: 13.5px; font-weight: 500; color: #64748b; }
.asm-lista__barra{ grid-column: 2 / 4; height: 8px; border-radius: 4px; background: #eef1f4; overflow: hidden; }
.asm-lista__barra span{ display: block; height: 100%; border-radius: 4px; }
.asm-lista--sem .asm-lista__cor{ opacity: .45; }
.asm-part{ display: flex; flex-wrap: wrap; gap: 14px; margin-top: 22px; padding-top: 20px; border-top: 1px solid #eef1f4; }
.asm-part div{ flex: 1 1 140px; }
.asm-part strong{ display: block; font-size: 28px; font-weight: 800; color: #11151b; line-height: 1.1; }
.asm-part span{ font-size: 14.5px; color: #475569; }
.asm-tabela{ margin-top: 18px; }
.asm-tabela summary{ cursor: pointer; font-weight: 600; color: var(--cor-principal); font-size: 15px; }
.asm-tabela table{ width: 100%; margin-top: 12px; border-collapse: collapse; font-size: 15px; }
.asm-tabela th, .asm-tabela td{ padding: 8px 10px; text-align: left; border-bottom: 1px solid #eef1f4; }
.asm-tabela td:not(:first-child), .asm-tabela th:not(:first-child){ text-align: right; }
.asm-fonte{ margin: 14px 0 0; font-size: 13.5px; color: #64748b; }
.asm-fonte a{ color: var(--cor-principal); font-weight: 600; }

/* Pessoas */
.asm-grelha{ display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 18px; }
.asm-pessoa{ position: relative; display: flex; gap: 16px; align-items: center; padding: 22px; background: #fff; border: 1px solid #e7ebe6; border-radius: 22px; box-shadow: 0 1px 2px rgb(15 23 42 / .04), 0 16px 36px -26px rgb(15 23 42 / .35); }
.asm-pessoa--destaque{ border-color: color-mix(in srgb, var(--cor-secundaria) 55%, #fff); box-shadow: 0 0 0 3px color-mix(in srgb, var(--cor-secundaria) 18%, transparent), 0 16px 36px -26px rgb(15 23 42 / .35); }
.asm-pessoa__foto, .asm-pessoa__inicial{ flex: 0 0 auto; width: 76px; height: 76px; border-radius: 50%; object-fit: cover; }
.asm-pessoa__inicial{ display: grid; place-items: center; font-size: 1.45rem; font-weight: 700; color: var(--cor-principal); background: color-mix(in srgb, var(--cor-principal) 12%, #fff); box-shadow: 0 0 0 4px #fff, 0 0 0 5px #e5e7eb; }
.asm-pessoa h3{ margin: 0 0 4px; font-size: 1.15rem; line-height: 1.25; color: #11151b; }
.asm-cargo{ display: inline-block; padding: 4px 11px; border-radius: 999px; font-size: 13px; font-weight: 700; color: var(--cor-principal); background: color-mix(in srgb, var(--cor-principal) 10%, #fff); }
.asm-partido{ display: inline-flex; align-items: center; gap: 6px; margin-left: 6px; font-size: 13.5px; font-weight: 600; color: #334155; }
.asm-partido i{ width: 10px; height: 10px; border-radius: 3px; }
.asm-pessoa p{ margin: 8px 0 0; font-size: 15px; line-height: 1.5; color: #475569; }
.asm-falta{ display: flex; gap: 16px; align-items: flex-start; padding: 22px 24px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 22px; }
.asm-falta > i{ font-size: 26px; color: var(--cor-principal); }
.asm-falta p{ margin: 0 0 12px; font-size: 16px; line-height: 1.6; color: #334155; }
.asm-falta ul{ display: flex; flex-wrap: wrap; gap: 8px; margin: 0; padding: 0; list-style: none; }
.asm-falta li{ display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px; background: #f1f4f2; font-size: 15px; font-weight: 600; color: #1f2937; }
.asm-falta li i{ width: 12px; height: 12px; border-radius: 3px; }

/* Sessão e ligações */
.asm-duo{ display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: 20px; }
.asm-sessao h2, .asm-mais h2{ margin: 0 0 8px; font-size: 1.45rem; color: #11151b; }
.asm-sessao p{ margin: 0 0 16px; font-size: 16px; line-height: 1.6; color: #475569; }
.asm-data{ display: flex; gap: 16px; align-items: center; margin-bottom: 16px; }
.asm-data__dia{ display: grid; place-items: center; width: 74px; height: 74px; border-radius: 18px; color: #fff; background: var(--cor-principal); text-align: center; line-height: 1; }
.asm-data__dia strong{ font-size: 28px; }
.asm-data__dia small{ font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
.asm-links{ display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); gap: 12px; }
.asm-link{ display: flex; gap: 12px; align-items: center; min-height: 64px; padding: 14px 16px; border-radius: 16px; text-decoration: none; color: #11151b; background: #f7f8f5; border: 1px solid #e7ebe6; font-weight: 600; transition: border-color .2s, transform .2s; }
.asm-link i{ font-size: 20px; color: var(--cor-principal); }
.asm-link:hover{ transform: translateY(-2px); border-color: color-mix(in srgb, var(--cor-principal) 35%, #fff); }
.asm-link:focus-visible, .asm-btn:focus-visible{ outline: 3px solid var(--cor-secundaria); outline-offset: 2px; }
.asm-btn{ display: inline-flex; align-items: center; gap: 8px; min-height: 48px; padding: 0 20px; border-radius: 999px; font-size: 15px; font-weight: 600; text-decoration: none; color: #fff; background: var(--cor-principal); }
.asm-btn--linha{ color: var(--cor-principal); background: #fff; border: 1.5px solid color-mix(in srgb, var(--cor-principal) 35%, #fff); }
.asm-acoes{ display: flex; flex-wrap: wrap; gap: 10px; }

@media (max-width: 56em){ .asm-dist{ grid-template-columns: 1fr; } }
@media (prefers-reduced-motion: reduce){ .asm-lugar circle, .asm-link, .asm-tip{ transition: none; } .asm-lugar:hover circle, .asm-lugar:focus circle{ transform: none; } .asm-link:hover{ transform: none; } }
</style>

<div class="asm">
    <section class="asm-hero" aria-labelledby="asmTitulo">
        <div class="container">
            <nav aria-label="Localização">
                <ol class="asm-migalhas">
                    <li><a href="/">Início</a></li>
                    <li>Assembleia de Freguesia</li>
                    <li aria-current="page">Composição</li>
                </ol>
            </nav>
            <span class="asm-kicker"><i class="bi bi-bank2" aria-hidden="true"></i> Assembleia de Freguesia</span>
            <h1 id="asmTitulo"><?= te('assembleia_composicao.hero.titulo') ?></h1>
            <p><?= te('assembleia_composicao.hero.texto') ?></p>
            <?php if ($eleicao): ?>
                <ul class="asm-chips">
                    <li><i class="bi bi-calendar3" aria-hidden="true"></i> Mandato <?= $h($eleicao['mandato']) ?></li>
                    <li><i class="bi bi-people" aria-hidden="true"></i> <?= $totalLugares ?> membros eleitos</li>
                    <?php if ($eleicao['data_eleicao']): ?><li><i class="bi bi-check2-square" aria-hidden="true"></i> Eleições de <?= $h(date('d/m/Y', strtotime($eleicao['data_eleicao']))) ?></li><?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <div class="container asm-main">

        <?php if ($eleicao && $lugares): ?>
        <section class="asm-caixa" aria-labelledby="asmDist">
            <div class="asm-cabeca">
                <span class="asm-kick2"><?= te('assembleia_composicao.distribuicao.kicker') ?></span>
                <h2 id="asmDist"><?= te('assembleia_composicao.distribuicao.titulo') ?></h2>
                <p><?= te('assembleia_composicao.distribuicao.texto') ?></p>
            </div>
            <div class="asm-dist">
                <div class="asm-hemi">
                    <?php
                    $W = 560; $Hh = 318; $cx = $W / 2; $cy = 292; $R = 236; $Ranel = 168;
                    $raio = $umaFila ? min(34, M_PI * $R / $totalLugares / 2 - 5) : 16;
                    $pt = fn($a, $r) => round($cx + cos($a) * $r, 1) . ' ' . round($cy - sin($a) * $r, 1);
                    // anel de votos: um arco por lista (proporcional à % de votos) + o resto (outras listas, brancos e nulos)
                    $arcos = []; $a0 = M_PI; $somaPct = 0;
                    foreach ($listas as $l) { $v = (float)$l['votos_pct']; if ($v <= 0) continue; $a1 = $a0 - M_PI * $v / 100; $arcos[] = [$a0, $a1, $l['cor'], $l['sigla'] . ' — ' . $pct($v) . ' dos votos']; $a0 = $a1; $somaPct += $v; }
                    if ($somaPct < 99.9) $arcos[] = [$a0, 0, '#cbd5e1', 'Outros, brancos e nulos — ' . $pct(100 - $somaPct)];
                    $aMaioria = $umaFila ? M_PI - $maioria * M_PI / $totalLugares : null;
                    $comMaioria = null; foreach ($comLugar as $l) if ((int)$l['mandatos'] >= $maioria) $comMaioria = $l;
                    ?>
                    <svg viewBox="0 0 <?= $W ?> <?= $Hh ?>" role="img" aria-labelledby="asmHemiT asmHemiD">
                        <title id="asmHemiT">Distribuição dos <?= $totalLugares ?> lugares da Assembleia</title>
                        <desc id="asmHemiD"><?= $h(implode('; ', array_map(fn($l) => $l['sigla'] . ': ' . $l['mandatos'] . ' ' . ((int)$l['mandatos'] === 1 ? 'lugar' : 'lugares') . ', ' . $pct($l['votos_pct']) . ' dos votos', $listas))) ?>. Maioria absoluta: <?= $maioria ?> lugares.</desc>
                        <g class="asm-anel" aria-hidden="true">
                            <?php foreach ($arcos as [$a, $b, $cor, $dica]): $g = .012; ?>
                                <path d="M <?= $pt($a - $g, $Ranel) ?> A <?= $Ranel ?> <?= $Ranel ?> 0 0 1 <?= $pt($b + $g, $Ranel) ?>" stroke="<?= $h($cor) ?>" data-tip="<?= $h($dica) ?>"></path>
                            <?php endforeach; ?>
                        </g>
                        <?php if ($aMaioria !== null): ?>
                            <g class="asm-maioria" aria-hidden="true">
                                <line x1="<?= round($cx + cos($aMaioria) * ($Ranel + 16), 1) ?>" y1="<?= round($cy - sin($aMaioria) * ($Ranel + 16), 1) ?>" x2="<?= round($cx + cos($aMaioria) * ($R + 44), 1) ?>" y2="<?= round($cy - sin($aMaioria) * ($R + 44), 1) ?>"></line>
                            </g>
                        <?php endif; ?>
                        <?php foreach ($lugares as [$ang, $r, $l, $pessoa, $n]):
                            $x = round($cx + cos($ang) * $R * $r, 1); $y = round($cy - sin($ang) * $R * $r, 1);
                            $dica = 'Lugar ' . $n . ' · ' . $l['sigla'] . ' — ' . ($pessoa ? $pessoa['nome'] . ' (' . $pessoa['cargo'] . ')' : 'vogal (nome por publicar)'); ?>
                            <g class="asm-lugar<?= $pessoa ? '' : ' asm-lugar--vazio' ?>" tabindex="0" role="img" aria-label="<?= $h($dica) ?>" data-tip="<?= $h($dica) ?>">
                                <circle cx="<?= $x ?>" cy="<?= $y ?>" r="<?= round($raio, 1) ?>" fill="<?= $h($l['cor']) ?>"></circle>
                                <?php if ($pessoa && $umaFila): ?><text x="<?= $x ?>" y="<?= $y ?>" dy=".35em" text-anchor="middle"><?= $h($iniciais($pessoa['nome'])) ?></text><?php endif; ?>
                            </g>
                        <?php endforeach; ?>
                        <text class="asm-hemi__total" x="<?= $cx ?>" y="<?= $cy - 40 ?>" text-anchor="middle"><?= $totalLugares ?></text>
                        <text class="asm-hemi__legenda" x="<?= $cx ?>" y="<?= $cy - 14 ?>" text-anchor="middle">lugares</text>
                        <text class="asm-hemi__maioria" x="<?= $cx ?>" y="<?= $cy + 14 ?>" text-anchor="middle">Maioria absoluta: <?= $maioria ?></text>
                    </svg>
                    <div class="asm-tip" role="status" aria-live="polite"></div>
                    <ul class="asm-hemi__chave">
                        <li><span class="asm-chave-lugar" aria-hidden="true"></span> Um círculo = um lugar<?= $umaFila ? ' (com as iniciais de quem já está publicado)' : '' ?></li>
                        <li><span class="asm-chave-anel" aria-hidden="true"></span> Anel interior = percentagem de votos</li>
                        <?php if ($aMaioria !== null): ?><li><span class="asm-chave-maioria" aria-hidden="true"></span> Linha = maioria absoluta (<?= $maioria ?> lugares)<?= $comMaioria ? ' — o ' . $h($comMaioria['sigla']) . ' tem maioria' : ' — nenhuma lista tem maioria' ?></li><?php endif; ?>
                    </ul>
                </div>
                <div>
                    <ul class="asm-listas">
                        <?php foreach ($listas as $l): ?>
                            <li class="asm-lista <?= (int)$l['mandatos'] ? '' : 'asm-lista--sem' ?>">
                                <span class="asm-lista__cor" style="background:<?= $h($l['cor']) ?>" aria-hidden="true"></span>
                                <strong><?= $h($l['sigla']) ?><small><?= $h($l['nome']) ?></small></strong>
                                <span class="asm-lista__lugares"><?= (int)$l['mandatos'] ?> <?= (int)$l['mandatos'] === 1 ? 'lugar' : 'lugares' ?><small><?= $pct($l['votos_pct']) ?> dos votos</small></span>
                                <span class="asm-lista__barra" aria-hidden="true"><span style="width:<?= min(100, (float)$l['votos_pct']) ?>%;background:<?= $h($l['cor']) ?>"></span></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($eleicao['inscritos'] && $eleicao['votantes']): ?>
                        <div class="asm-part">
                            <div><strong><?= $pct($eleicao['votantes'] / $eleicao['inscritos'] * 100) ?></strong><span>participação</span></div>
                            <div><strong><?= number_format((int)$eleicao['votantes'], 0, ',', ' ') ?></strong><span>votantes, de <?= number_format((int)$eleicao['inscritos'], 0, ',', ' ') ?> eleitores inscritos</span></div>
                        </div>
                    <?php endif; ?>
                    <details class="asm-tabela">
                        <summary>Ver os resultados em tabela</summary>
                        <table>
                            <caption class="sr-only">Resultados da eleição para a Assembleia de Freguesia, mandato <?= $h($eleicao['mandato']) ?></caption>
                            <thead><tr><th scope="col">Lista</th><th scope="col">Votos</th><th scope="col">Lugares</th></tr></thead>
                            <tbody><?php foreach ($listas as $l): ?><tr><th scope="row"><?= $h($l['sigla']) ?> — <?= $h($l['nome']) ?></th><td><?= $pct($l['votos_pct']) ?></td><td><?= (int)$l['mandatos'] ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </details>
                    <?php if ($eleicao['fonte']): ?>
                        <p class="asm-fonte">Fonte: <?= $eleicao['fonte_url'] ? '<a href="' . $h($eleicao['fonte_url']) . '" target="_blank" rel="noopener">' . $h($eleicao['fonte']) . '</a>' : $h($eleicao['fonte']) ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="asm-sec" aria-labelledby="asmMesa">
            <div class="asm-cabeca">
                <span class="asm-kick2"><?= te('assembleia_composicao.mesa.kicker') ?></span>
                <h2 id="asmMesa"><?= te('assembleia_composicao.mesa.titulo') ?></h2>
                <p><?= te('assembleia_composicao.mesa.texto') ?></p>
            </div>
            <?php if ($mesa): ?>
                <div class="asm-grelha">
                    <?php foreach ($mesa as $k => $m): $cor = $corLista((string)$m['partido']); ?>
                        <article class="asm-pessoa <?= ($k === 0 || !empty($m['destaque'])) ? 'asm-pessoa--destaque' : '' ?>">
                            <?php if (!empty($m['foto'])): ?><img class="asm-pessoa__foto" src="<?= $h(fotoMembroUrl($m['foto'])) ?>" alt="Fotografia de <?= $h($m['nome']) ?>" width="76" height="76" loading="lazy">
                            <?php else: ?><span class="asm-pessoa__inicial" aria-hidden="true"><?= $h($iniciais($m['nome'])) ?></span><?php endif; ?>
                            <div>
                                <h3><?= $h($m['nome']) ?></h3>
                                <span class="asm-cargo"><?= $h($m['cargo']) ?></span>
                                <?php if (!empty($m['partido'])): ?><span class="asm-partido"><?php if ($cor): ?><i style="background:<?= $h($cor) ?>" aria-hidden="true"></i><?php endif; ?><?= $h($m['partido']) ?></span><?php endif; ?>
                                <?php if (!empty($m['descricao'])): ?><p><?= nl2br($h($m['descricao'])) ?></p><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="asm-falta"><i class="bi bi-info-circle" aria-hidden="true"></i><p><?= te('assembleia_composicao.mesa.vazio') ?></p></div>
            <?php endif; ?>
        </section>

        <section class="asm-sec" aria-labelledby="asmVogais">
            <div class="asm-cabeca">
                <span class="asm-kick2"><?= te('assembleia_composicao.vogais.kicker') ?></span>
                <h2 id="asmVogais"><?= te('assembleia_composicao.vogais.titulo') ?></h2>
                <p><?= te('assembleia_composicao.vogais.texto') ?></p>
            </div>
            <?php if ($vogais): ?>
                <div class="asm-grelha">
                    <?php foreach ($vogais as $m): $cor = $corLista((string)$m['partido']); ?>
                        <article class="asm-pessoa">
                            <?php if (!empty($m['foto'])): ?><img class="asm-pessoa__foto" src="<?= $h(fotoMembroUrl($m['foto'])) ?>" alt="Fotografia de <?= $h($m['nome']) ?>" width="76" height="76" loading="lazy">
                            <?php else: ?><span class="asm-pessoa__inicial" aria-hidden="true"><?= $h($iniciais($m['nome'])) ?></span><?php endif; ?>
                            <div>
                                <h3><?= $h($m['nome']) ?></h3>
                                <span class="asm-cargo"><?= $h($m['cargo']) ?></span>
                                <?php if (!empty($m['partido'])): ?><span class="asm-partido"><?php if ($cor): ?><i style="background:<?= $h($cor) ?>" aria-hidden="true"></i><?php endif; ?><?= $h($m['partido']) ?></span><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($nPorPublicar > 0): ?>
                <div class="asm-falta" style="<?= $vogais ? 'margin-top:18px' : '' ?>">
                    <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                    <div>
                        <p><?= $vogais ? 'Faltam publicar' : 'Ainda não foram publicados os nomes dos' ?> <strong><?= $nPorPublicar ?> <?= $nPorPublicar === 1 ? 'vogal' : 'vogais' ?></strong> eleitos para este mandato. Pela distribuição dos lugares, são:</p>
                        <ul>
                            <?php foreach ($porPublicar as [$l, $n]): ?>
                                <li><i style="background:<?= $h($l['cor']) ?>" aria-hidden="true"></i> <?= $n ?> <?= $n === 1 ? 'vogal' : 'vogais' ?> <?= $h($l['sigla']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php elseif (!$vogais): ?>
                <div class="asm-falta"><i class="bi bi-info-circle" aria-hidden="true"></i><p><?= te('assembleia_composicao.vogais.vazio') ?></p></div>
            <?php endif; ?>
        </section>

        <?php if ($outros): ?>
        <section class="asm-sec" aria-labelledby="asmOutros">
            <div class="asm-cabeca"><h2 id="asmOutros"><?= te('assembleia_composicao.outros.titulo') ?></h2></div>
            <div class="asm-grelha">
                <?php foreach ($outros as $m): ?>
                    <article class="asm-pessoa">
                        <span class="asm-pessoa__inicial" aria-hidden="true"><?= $h($iniciais($m['nome'])) ?></span>
                        <div><h3><?= $h($m['nome']) ?></h3><span class="asm-cargo"><?= $h($m['cargo']) ?></span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <div class="asm-sec asm-duo">
            <section class="asm-caixa asm-sessao" aria-labelledby="asmSessao">
                <span class="asm-kick2"><?= te('assembleia_composicao.sessao.kicker') ?></span>
                <h2 id="asmSessao"><?= te('assembleia_composicao.sessao.titulo') ?></h2>
                <?php if ($proxima): $meses = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']; $t = strtotime($proxima['data_sessao']); ?>
                    <div class="asm-data">
                        <span class="asm-data__dia"><strong><?= date('j', $t) ?></strong><small><?= $meses[(int)date('n', $t)] ?></small></span>
                        <div><strong><?= $h($proxima['titulo']) ?></strong><br><span style="color:#475569"><?= $h($proxima['tipo']) ?> · <?= $h(substr($proxima['hora_sessao'], 0, 5)) ?><?= $proxima['local_sessao'] ? ' · ' . $h($proxima['local_sessao']) : '' ?></span></div>
                    </div>
                <?php else: ?>
                    <p><?= te('assembleia_composicao.sessao.sem') ?></p>
                <?php endif; ?>
                <p><?= te('assembleia_composicao.sessao.publicas') ?></p>
                <div class="asm-acoes">
                    <a class="asm-btn" href="/assembleia-sessoes.php"><i class="bi bi-calendar3" aria-hidden="true"></i> Sessões da Assembleia</a>
                    <a class="asm-btn asm-btn--linha" href="/assembleia-documentos.php"><i class="bi bi-folder2-open" aria-hidden="true"></i> Editais e atas</a>
                </div>
            </section>

            <section class="asm-caixa asm-mais" aria-labelledby="asmMais">
                <span class="asm-kick2"><?= te('assembleia_composicao.mais.kicker') ?></span>
                <h2 id="asmMais"><?= te('assembleia_composicao.mais.titulo') ?></h2>
                <div class="asm-links">
                    <a class="asm-link" href="/assembleia-funcionamento.php"><i class="bi bi-gear" aria-hidden="true"></i> Funcionamento</a>
                    <a class="asm-link" href="/assembleia-competencias.php"><i class="bi bi-card-checklist" aria-hidden="true"></i> Atribuições e competências</a>
                    <a class="asm-link" href="/assembleia-documentos.php"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Documentos</a>
                    <a class="asm-link" href="/executivo.php"><i class="bi bi-person-badge" aria-hidden="true"></i> Executivo da Junta</a>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
/* Hemiciclo: dica ao passar o rato / ao focar um lugar com o teclado */
(function () {
    var caixa = document.querySelector(".asm-hemi");
    if (!caixa) return;
    var tip = caixa.querySelector(".asm-tip");
    function mostrar(c) {
        var r = c.getBoundingClientRect(), b = caixa.getBoundingClientRect();
        tip.textContent = c.getAttribute("data-tip");
        // a dica fica sempre dentro do gráfico (não sai pela margem esquerda/direita)
        var meia = tip.offsetWidth / 2, x = r.left + r.width / 2 - b.left;
        tip.style.left = Math.max(meia, Math.min(b.width - meia, x)) + "px";
        tip.style.top = (r.top - b.top) + "px";
        tip.classList.add("visivel");
    }
    caixa.querySelectorAll("[data-tip]").forEach(function (c) {
        c.addEventListener("mouseenter", function () { mostrar(c); });
        c.addEventListener("focus", function () { mostrar(c); });
        c.addEventListener("mouseleave", function () { tip.classList.remove("visivel"); });
        c.addEventListener("blur", function () { tip.classList.remove("visivel"); });
    });
})();
</script>

<?php require_once "includes/footer.php"; ?>
