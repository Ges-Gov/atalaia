<?php
/**
 * Executivo da Junta de Freguesia (redesenhado a 20/10/2026, a pedido — «melhorar e completar»).
 * Secções: topo · Presidente (retrato, mensagem, contactos) · composição do executivo (organigrama) ·
 * o que faz a Junta (Lei 75/2013, art. 16.º, por áreas) · tratar de um assunto · quem fiscaliza
 * (Mesa da Assembleia) · falar com a Junta. Tudo vem da BD (executivo_membros, homepage_config,
 * assembleia_composicao, configuracoes_site); os textos fixos são editáveis no painel (Textos do site).
 * Membros sem fotografia mostram as iniciais — nunca uma foto inventada.
 */
require_once "includes/header.php";
require_once __DIR__ . "/includes/membros.php";

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$membros = $pdo->query("SELECT * FROM executivo_membros WHERE ativo = 1 ORDER BY ordem ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

$presidente = null;
foreach ($membros as $m) {
    if (stripos($m['cargo'], 'presidente') !== false || stripos($m['cargo'], 'president') !== false) { $presidente = $m; break; }
}
if (!$presidente && $membros) $presidente = $membros[0];
$vogais = array_values(array_filter($membros, fn($m) => !$presidente || (int)$m['id'] !== (int)$presidente['id']));

// Lista/partido: se todos os membros forem da mesma lista, mostra-se uma vez no topo
$partidos = array_values(array_unique(array_filter(array_map(fn($m) => trim((string)$m['partido']), $membros))));

// Mensagem do Presidente: a mesma da página inicial (Página Inicial → Mensagem do Presidente); senão a biografia
$presMensagem = '';
try {
    $presMensagem = trim((string)$pdo->query("SELECT presidente_mensagem FROM homepage_config LIMIT 1")->fetchColumn());
} catch (Throwable $e) {}
if ($presMensagem === '' && $presidente) $presMensagem = trim((string)$presidente['biografia']);

// Quem fiscaliza: Mesa da Assembleia de Freguesia
$mesa = [];
try {
    $mesa = $pdo->query("SELECT nome, cargo FROM assembleia_composicao WHERE ativo = 1 AND grupo LIKE 'Mesa%' ORDER BY ordem, id LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$iniciais = function (string $nome): string {
    $p = preg_split('/\s+/', trim($nome)) ?: [''];
    return mb_strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : ''));
};

$telefone = siteConfig('telefone');
$email = siteConfig('email');
$morada = siteConfig('morada');
$horario = siteConfig('horario');
$telLink = preg_replace('/[^0-9+]/', '', (string)$telefone);

/* O que faz a Junta — resumo, por áreas, das competências do art. 16.º da Lei n.º 75/2013 (alíneas entre parênteses) */
$competencias = [
    ['bi-cash-coin', 'Planeamento e contas', [
        'Elabora o plano de atividades e o orçamento e executa-os depois de aprovados pela Assembleia (a, b)',
        'Presta contas à Assembleia de Freguesia e ao Tribunal de Contas (e, vv)',
        'Prepara os regulamentos da freguesia (h)',
    ]],
    ['bi-cone-striped', 'Espaço público', [
        'Conserva caminhos, arruamentos e pavimentos pedonais (ff)',
        'Mantém parques infantis, equipamentos desportivos, fontanários e abrigos de passageiros (z, bb, cc)',
        'Coloca as placas toponímicas e conserva a sinalização vertical (dd, ee)',
    ]],
    ['bi-people', 'Ação social, cultura e desporto', [
        'Promove projetos de intervenção comunitária na ação social, cultura e desporto (t)',
        'Apoia as associações e as atividades de interesse para a freguesia (o, v)',
        'Colabora com as instituições de solidariedade social (u)',
    ]],
    ['bi-file-earmark-text', 'Serviços ao cidadão', [
        'Passa atestados e lavra termos de identidade e justificação administrativa (rr, qq)',
        'Faz o recenseamento eleitoral (pp)',
        'Regista e licencia cães e gatos (nn) e licencia venda ambulante de lotarias e atividades ruidosas temporárias (n.º 3)',
    ]],
    ['bi-bank', 'Património e cemitérios', [
        'Administra e conserva o património da freguesia e mantém o seu cadastro (ii, jj)',
        'Gere e conserva os cemitérios da freguesia e concede terrenos para sepulturas (gg, hh)',
        'Gere balneários, lavadouros e sanitários públicos (aa)',
    ]],
    ['bi-shield-check', 'Cooperação e proteção civil', [
        'Colabora com a proteção civil municipal em caso de acidente grave (y)',
        'Fornece material de limpeza e expediente às escolas do 1.º ciclo e do pré-escolar (mm)',
        'Prepara com a Câmara Municipal os acordos de delegação de competências (i, j)',
    ]],
];

$atalhos = [
    ['bi-calendar-check', 'Marcar atendimento', 'Marque uma reunião ou atendimento com a Junta.', '/marcacoes.php'],
    ['bi-house-check', 'Pedir um atestado', 'Atestado de residência, emitido pelos serviços.', '/atestado-residencia.php'],
    ['bi-file-earmark-check', 'Fazer um requerimento', 'Pedidos e documentos, sem sair de casa.', '/requerimentos.php'],
    ['bi-megaphone', 'Comunicar uma ocorrência', 'Buracos, iluminação, limpeza, espaços verdes…', '/pedidos.php'],
];
?>
<style>
/* Executivo (20/10/2026) — só tokens da marca (var(--cor-principal) / --cor-secundaria); o modo escuro vem do gerador */
.exe{ background: #f7f8f5; padding-bottom: clamp(48px, 7vw, 96px); }
.exe-hero{ position: relative; color: #fff; background: linear-gradient(135deg, color-mix(in srgb, var(--cor-principal) 82%, #000), #12161b); padding: clamp(64px, 9vw, 112px) 0 clamp(96px, 11vw, 140px); }
.exe-hero .container{ position: relative; z-index: 1; }
.exe-migalhas{ display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 18px; padding: 0; list-style: none; font-size: 14px; color: rgb(255 255 255 / .82); }
.exe-migalhas li + li::before{ content: "›"; margin-right: 6px; opacity: .7; }
.exe-migalhas a{ color: inherit; text-decoration: none; }
.exe-migalhas a:hover{ text-decoration: underline; }
.exe-kicker{ display: inline-block; padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: color-mix(in srgb, var(--cor-secundaria) 55%, #fff); background: rgb(255 255 255 / .1); border: 1px solid rgb(255 255 255 / .22); }
.exe-hero h1{ margin: 16px 0 12px; font-size: clamp(2.1rem, 1.5rem + 2.4vw, 3.4rem); line-height: 1.08; letter-spacing: -.02em; color: #fff; }
.exe-hero p{ margin: 0; max-width: 62ch; font-size: clamp(1.05rem, 1rem + .3vw, 1.2rem); line-height: 1.6; color: rgb(255 255 255 / .9); }
.exe-chips{ display: flex; flex-wrap: wrap; gap: 10px; margin: 24px 0 0; padding: 0; list-style: none; }
.exe-chips li{ display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 999px; background: rgb(255 255 255 / .12); border: 1px solid rgb(255 255 255 / .2); font-size: 15px; font-weight: 600; color: #fff; }
.exe-chips i{ color: color-mix(in srgb, var(--cor-secundaria) 60%, #fff); }

.exe-main{ position: relative; z-index: 2; margin-top: clamp(-48px, -3vw, -24px); }
.exe-sec{ margin-top: clamp(48px, 6vw, 80px); }
.exe-sec__cabeca{ margin-bottom: 24px; max-width: 70ch; }
.exe-sec__kicker{ display: block; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--cor-principal); margin-bottom: 6px; }
.exe-sec__cabeca h2{ margin: 0 0 8px; font-size: clamp(1.6rem, 1.3rem + 1.2vw, 2.25rem); line-height: 1.15; color: #11151b; }
.exe-sec__cabeca p{ margin: 0; font-size: 17px; line-height: 1.6; color: #475569; }

/* Presidente */
.exe-pres{ display: grid; grid-template-columns: minmax(220px, 300px) 1fr; gap: clamp(24px, 4vw, 56px); align-items: center; padding: clamp(24px, 4vw, 48px); background: #fff; border: 1px solid #e7ebe6; border-radius: 28px; box-shadow: 0 1px 2px rgb(15 23 42 / .04), 0 24px 60px -30px rgb(15 23 42 / .3); }
.exe-pres__retrato{ position: relative; margin: 0; width: 100%; max-width: 300px; justify-self: center; aspect-ratio: 1; padding: 10px; border-radius: 50%; background: #fff; box-shadow: 0 0 0 1px #e5e7eb, 0 20px 44px -18px rgb(15 23 42 / .45); }
.exe-pres__retrato::before{ content: ""; position: absolute; inset: -14px; border-radius: 50%; z-index: -1; background: radial-gradient(circle, color-mix(in srgb, var(--cor-secundaria) 30%, transparent), transparent 70%); }
.exe-pres__retrato img, .exe-pres__inicial{ display: block; width: 100%; height: 100%; border-radius: 50%; object-fit: cover; object-position: 50% 30%; }
.exe-pres__inicial, .exe-membro__inicial{ display: grid; place-items: center; font-weight: 700; color: var(--cor-principal); background: color-mix(in srgb, var(--cor-principal) 12%, #fff); }
.exe-pres__inicial{ font-size: 4rem; }
.exe-etiqueta{ display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; color: var(--cor-principal); background: color-mix(in srgb, var(--cor-principal) 10%, #fff); }
.exe-pres h2{ margin: 14px 0 4px; font-size: clamp(1.7rem, 1.3rem + 1.6vw, 2.6rem); line-height: 1.12; letter-spacing: -.02em; color: #11151b; }
.exe-pres__cargo{ display: block; font-size: 17px; font-weight: 600; color: #334155; }
.exe-partido{ display: inline-block; margin-top: 10px; padding: 3px 10px; border-radius: 6px; font-size: 13px; font-weight: 700; letter-spacing: .03em; color: #334155; background: #eef1f4; }
.exe-pres blockquote{ position: relative; margin: 22px 0 0; padding: 4px 0 4px 22px; border-left: 3px solid var(--cor-secundaria); font-size: clamp(1.05rem, 1rem + .25vw, 1.15rem); line-height: 1.7; color: #1f2937; }
.exe-pres blockquote footer{ margin-top: 10px; font-size: 15px; font-style: normal; font-weight: 600; color: #475569; }
.exe-pres__pelouros{ margin: 18px 0 0; font-size: 16px; line-height: 1.6; color: #334155; }
.exe-acoes{ display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
.exe-btn{ display: inline-flex; align-items: center; gap: 8px; min-height: 48px; padding: 0 20px; border-radius: 999px; font-size: 15px; font-weight: 600; text-decoration: none; color: var(--cor-principal); background: #fff; border: 1.5px solid color-mix(in srgb, var(--cor-principal) 35%, #fff); transition: background-color .2s, transform .2s; }
.exe-btn--principal{ color: #fff; background: var(--cor-principal); border-color: var(--cor-principal); }
.exe-btn:hover{ transform: translateY(-1px); background: color-mix(in srgb, var(--cor-principal) 8%, #fff); }
.exe-btn--principal:hover{ background: color-mix(in srgb, var(--cor-principal) 88%, #000); }
.exe-btn:focus-visible{ outline: 3px solid var(--cor-secundaria); outline-offset: 2px; }

/* Organigrama */
.exe-org{ display: grid; justify-items: center; }
.exe-org__topo{ display: flex; align-items: center; gap: 14px; padding: 14px 22px 14px 14px; background: #fff; border: 1.5px solid color-mix(in srgb, var(--cor-principal) 30%, #fff); border-radius: 18px; box-shadow: 0 10px 28px -18px rgb(15 23 42 / .4); }
.exe-org__topo img, .exe-org__topo .exe-membro__inicial{ width: 56px; height: 56px; border-radius: 50%; object-fit: cover; font-size: 1.2rem; }
.exe-org__topo strong{ display: block; font-size: 16px; color: #11151b; }
.exe-org__topo span{ font-size: 14px; color: #475569; }
.exe-org__ligacao{ width: 2px; height: 28px; background: color-mix(in srgb, var(--cor-principal) 35%, #fff); }
.exe-org__ramo{ position: relative; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 360px)); justify-content: center; gap: 24px; width: 100%; padding-top: 28px; }
.exe-org__ramo::before{ content: ""; position: absolute; top: 0; left: 25%; right: 25%; height: 2px; background: color-mix(in srgb, var(--cor-principal) 35%, #fff); }
.exe-membro{ position: relative; display: flex; gap: 18px; align-items: flex-start; padding: 22px; background: #fff; border: 1px solid #e7ebe6; border-radius: 22px; box-shadow: 0 1px 2px rgb(15 23 42 / .04), 0 16px 36px -26px rgb(15 23 42 / .35); }
.exe-org__ramo .exe-membro::before{ content: ""; position: absolute; top: -28px; left: 50%; width: 2px; height: 28px; background: color-mix(in srgb, var(--cor-principal) 35%, #fff); }
.exe-membro__foto, .exe-membro .exe-membro__inicial{ flex: 0 0 auto; width: 84px; height: 84px; border-radius: 50%; object-fit: cover; font-size: 1.6rem; box-shadow: 0 0 0 4px #fff, 0 0 0 5px #e5e7eb; }
.exe-membro h3{ margin: 8px 0 2px; font-size: 1.2rem; line-height: 1.25; color: #11151b; }
.exe-membro__papel{ font-size: 15px; color: #475569; }
.exe-membro p{ margin: 10px 0 0; font-size: 15px; line-height: 1.55; color: #334155; }
.exe-membro a{ display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 15px; font-weight: 600; color: var(--cor-principal); word-break: break-word; }

/* Competências */
.exe-comp{ display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 330px), 1fr)); gap: 18px; }
.exe-comp__item{ padding: 22px 24px; background: #fff; border: 1px solid #e7ebe6; border-radius: 20px; }
.exe-comp__item h3{ display: flex; align-items: center; gap: 12px; margin: 0 0 12px; font-size: 1.1rem; color: #11151b; }
.exe-comp__item h3 i{ display: grid; place-items: center; width: 42px; height: 42px; border-radius: 12px; font-size: 20px; color: var(--cor-principal); background: color-mix(in srgb, var(--cor-principal) 10%, #fff); }
.exe-comp__item ul{ margin: 0; padding-left: 18px; }
.exe-comp__item li{ margin: 0 0 8px; font-size: 15.5px; line-height: 1.55; color: #334155; }
.exe-comp__item li::marker{ color: var(--cor-secundaria); }
.exe-fonte{ margin: 16px 0 0; font-size: 14px; color: #64748b; }
.exe-fonte a{ color: var(--cor-principal); font-weight: 600; }

/* Tratar de um assunto */
.exe-atalhos{ display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 16px; }
.exe-atalho{ display: flex; flex-direction: column; gap: 8px; min-height: 150px; padding: 22px; border-radius: 20px; text-decoration: none; color: #11151b; background: #fff; border: 1px solid #e7ebe6; transition: transform .2s, box-shadow .2s, border-color .2s; }
.exe-atalho i{ font-size: 26px; color: var(--cor-principal); }
.exe-atalho strong{ font-size: 1.1rem; }
.exe-atalho span{ font-size: 15px; line-height: 1.5; color: #475569; }
.exe-atalho:hover{ transform: translateY(-3px); border-color: color-mix(in srgb, var(--cor-principal) 35%, #fff); box-shadow: 0 18px 36px -24px rgb(15 23 42 / .45); }
.exe-atalho:focus-visible{ outline: 3px solid var(--cor-secundaria); outline-offset: 3px; }

/* Assembleia + contactos */
.exe-duo{ display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 380px), 1fr)); gap: 20px; }
.exe-caixa{ padding: clamp(22px, 3vw, 32px); background: #fff; border: 1px solid #e7ebe6; border-radius: 24px; }
.exe-caixa h2{ margin: 0 0 8px; font-size: 1.45rem; color: #11151b; }
.exe-caixa > p{ margin: 0 0 16px; font-size: 16px; line-height: 1.6; color: #475569; }
.exe-mesa{ margin: 0 0 18px; padding: 0; list-style: none; }
.exe-mesa li{ display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid #eef1f4; }
.exe-mesa li:first-child{ border-top: 0; }
.exe-mesa .exe-membro__inicial{ width: 44px; height: 44px; border-radius: 50%; font-size: 1rem; flex: 0 0 auto; }
.exe-mesa strong{ display: block; font-size: 15.5px; color: #11151b; }
.exe-mesa span{ font-size: 14px; color: #475569; }
.exe-contactos{ margin: 0 0 18px; padding: 0; list-style: none; display: grid; gap: 12px; }
.exe-contactos li{ display: flex; gap: 12px; align-items: flex-start; font-size: 16px; line-height: 1.5; color: #1f2937; }
.exe-contactos i{ flex: 0 0 auto; display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px; color: var(--cor-principal); background: color-mix(in srgb, var(--cor-principal) 10%, #fff); }
.exe-contactos a{ color: inherit; font-weight: 600; }
.exe-vazio{ padding: 28px; text-align: center; background: #fff; border: 1px dashed #cbd5e1; border-radius: 20px; color: #475569; }

@media (max-width: 56em){
    .exe-pres{ grid-template-columns: 1fr; text-align: center; }
    .exe-pres__retrato{ max-width: 220px; }
    .exe-pres blockquote{ text-align: left; }
    .exe-acoes{ justify-content: center; }
    .exe-org__ramo::before, .exe-org__ramo .exe-membro::before{ display: none; }
    .exe-org__ramo{ padding-top: 16px; }
}
@media (prefers-reduced-motion: reduce){
    .exe-btn, .exe-atalho{ transition: none; }
    .exe-btn:hover, .exe-atalho:hover{ transform: none; }
}
</style>

<div class="exe">

    <section class="exe-hero" aria-labelledby="exeTitulo">
        <div class="container">
            <nav aria-label="Localização">
                <ol class="exe-migalhas">
                    <li><a href="/">Início</a></li>
                    <li>A Freguesia</li>
                    <li aria-current="page">Executivo</li>
                </ol>
            </nav>
            <span class="exe-kicker"><?= te('executivo.hero.kicker') ?></span>
            <h1 id="exeTitulo"><?= te('executivo.hero.titulo') ?></h1>
            <p><?= te('executivo.hero.texto') ?></p>
            <ul class="exe-chips">
                <?php if (tx('executivo.hero.mandato') !== ''): ?><li><i class="bi bi-calendar3" aria-hidden="true"></i> <?= te('executivo.hero.mandato') ?></li><?php endif; ?>
                <li><i class="bi bi-people" aria-hidden="true"></i> <?= count($membros) ?> <?= count($membros) === 1 ? 'membro' : 'membros' ?> do executivo</li>
                <?php if (count($partidos) === 1): ?><li><i class="bi bi-check2-square" aria-hidden="true"></i> Eleitos pela lista <?= $h($partidos[0]) ?></li><?php endif; ?>
            </ul>
        </div>
    </section>

    <div class="container exe-main">

        <?php if ($presidente): ?>
        <article class="exe-pres" aria-labelledby="exePresNome">
            <figure class="exe-pres__retrato">
                <?php if (!empty($presidente['foto'])): ?>
                    <img src="<?= $h(fotoMembroUrl($presidente['foto'])) ?>" alt="Fotografia de <?= $h($presidente['nome']) ?>" width="300" height="300">
                <?php else: ?>
                    <span class="exe-pres__inicial" aria-hidden="true"><?= $h($iniciais($presidente['nome'])) ?></span>
                <?php endif; ?>
            </figure>
            <div>
                <span class="exe-etiqueta"><i class="bi bi-award" aria-hidden="true"></i> <?= te('executivo.presidente.distintivo') ?></span>
                <h2 id="exePresNome"><?= $h($presidente['nome']) ?></h2>
                <span class="exe-pres__cargo"><?= $h($presidente['cargo']) ?></span>
                <?php if (!empty($presidente['partido']) && count($partidos) !== 1): ?><span class="exe-partido"><?= $h($presidente['partido']) ?></span><?php endif; ?>

                <?php if ($presMensagem !== ''): ?>
                    <blockquote>
                        <?= nl2br($h($presMensagem)) ?>
                        <footer>— <?= $h($presidente['nome']) ?>, <?= $h($presidente['cargo']) ?></footer>
                    </blockquote>
                <?php endif; ?>

                <?php if (!empty($presidente['pelouros'])): ?>
                    <p class="exe-pres__pelouros"><strong>Pelouros:</strong> <?= nl2br($h($presidente['pelouros'])) ?></p>
                <?php endif; ?>

                <div class="exe-acoes">
                    <a class="exe-btn exe-btn--principal" href="/marcacoes.php"><i class="bi bi-calendar-check" aria-hidden="true"></i> <?= te('executivo.presidente.botao_marcar') ?></a>
                    <?php if (!empty($presidente['email'])): ?>
                        <a class="exe-btn" href="mailto:<?= $h($presidente['email']) ?>"><i class="bi bi-envelope" aria-hidden="true"></i> <?= te('executivo.presidente.botao') ?></a>
                    <?php elseif ($email): ?>
                        <a class="exe-btn" href="mailto:<?= $h($email) ?>"><i class="bi bi-envelope" aria-hidden="true"></i> <?= te('executivo.presidente.botao') ?></a>
                    <?php endif; ?>
                    <?php if ($telLink): ?><a class="exe-btn" href="tel:<?= $h($telLink) ?>"><i class="bi bi-telephone" aria-hidden="true"></i> <?= $h($telefone) ?></a><?php endif; ?>
                </div>
            </div>
        </article>
        <?php endif; ?>

        <section class="exe-sec" aria-labelledby="exeComposicao">
            <div class="exe-sec__cabeca">
                <span class="exe-sec__kicker"><?= te('executivo.membros.kicker') ?></span>
                <h2 id="exeComposicao"><?= te('executivo.membros.titulo') ?></h2>
                <p><?= te('executivo.membros.texto') ?></p>
            </div>

            <?php if ($membros): ?>
            <div class="exe-org">
                <?php if ($presidente): ?>
                    <div class="exe-org__topo">
                        <?php if (!empty($presidente['foto'])): ?><img src="<?= $h(fotoMembroUrl($presidente['foto'])) ?>" alt="" width="56" height="56" loading="lazy">
                        <?php else: ?><span class="exe-membro__inicial" aria-hidden="true"><?= $h($iniciais($presidente['nome'])) ?></span><?php endif; ?>
                        <div><strong><?= $h($presidente['nome']) ?></strong><span><?= $h($presidente['cargo']) ?></span></div>
                    </div>
                    <?php if ($vogais): ?><div class="exe-org__ligacao" aria-hidden="true"></div><?php endif; ?>
                <?php endif; ?>

                <?php if ($vogais): ?>
                <div class="exe-org__ramo">
                    <?php foreach ($vogais as $m): ?>
                        <article class="exe-membro">
                            <?php if (!empty($m['foto'])): ?>
                                <img class="exe-membro__foto" src="<?= $h(fotoMembroUrl($m['foto'])) ?>" alt="Fotografia de <?= $h($m['nome']) ?>" width="84" height="84" loading="lazy">
                            <?php else: ?>
                                <span class="exe-membro__inicial" aria-hidden="true"><?= $h($iniciais($m['nome'])) ?></span>
                            <?php endif; ?>
                            <div>
                                <span class="exe-etiqueta"><?= $h($m['cargo']) ?></span>
                                <h3><?= $h($m['nome']) ?></h3>
                                <span class="exe-membro__papel"><?= te('executivo.membros.papel_vogal') ?><?= (!empty($m['partido']) && count($partidos) !== 1) ? ' · ' . $h($m['partido']) : '' ?></span>
                                <?php if (!empty($m['pelouros'])): ?><p><strong>Pelouros:</strong> <?= nl2br($h($m['pelouros'])) ?></p><?php endif; ?>
                                <?php if (!empty($m['biografia'])): ?><p><?= nl2br($h($m['biografia'])) ?></p><?php endif; ?>
                                <?php if (!empty($m['email'])): ?><a href="mailto:<?= $h($m['email']) ?>"><i class="bi bi-envelope" aria-hidden="true"></i> <?= $h($m['email']) ?></a><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <div class="exe-vazio"><?= te('executivo.vazio.texto') ?></div>
            <?php endif; ?>
        </section>

        <section class="exe-sec" aria-labelledby="exeCompetencias">
            <div class="exe-sec__cabeca">
                <span class="exe-sec__kicker"><?= te('executivo.competencias.kicker') ?></span>
                <h2 id="exeCompetencias"><?= te('executivo.competencias.titulo') ?></h2>
                <p><?= te('executivo.competencias.texto') ?></p>
            </div>
            <div class="exe-comp">
                <?php foreach ($competencias as [$icone, $titulo, $itens]): ?>
                    <div class="exe-comp__item">
                        <h3><i class="bi <?= $icone ?>" aria-hidden="true"></i> <?= $h($titulo) ?></h3>
                        <ul><?php foreach ($itens as $it): ?><li><?= $h($it) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="exe-fonte"><?= te('executivo.competencias.fonte') ?></p>
        </section>

        <section class="exe-sec" aria-labelledby="exeAtalhos">
            <div class="exe-sec__cabeca">
                <span class="exe-sec__kicker"><?= te('executivo.atalhos.kicker') ?></span>
                <h2 id="exeAtalhos"><?= te('executivo.atalhos.titulo') ?></h2>
            </div>
            <div class="exe-atalhos">
                <?php foreach ($atalhos as [$icone, $titulo, $texto, $url]): ?>
                    <a class="exe-atalho" href="<?= $h($url) ?>">
                        <i class="bi <?= $icone ?>" aria-hidden="true"></i>
                        <strong><?= $h($titulo) ?></strong>
                        <span><?= $h($texto) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="exe-sec exe-duo">
            <section class="exe-caixa" aria-labelledby="exeAssembleia">
                <span class="exe-sec__kicker"><?= te('executivo.assembleia.kicker') ?></span>
                <h2 id="exeAssembleia"><?= te('executivo.assembleia.titulo') ?></h2>
                <p><?= te('executivo.assembleia.texto') ?></p>
                <?php if ($mesa): ?>
                    <ul class="exe-mesa">
                        <?php foreach ($mesa as $mm): ?>
                            <li><span class="exe-membro__inicial" aria-hidden="true"><?= $h($iniciais($mm['nome'])) ?></span>
                                <div><strong><?= $h($mm['nome']) ?></strong><span><?= $h($mm['cargo']) ?></span></div></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="exe-acoes" style="margin-top:0">
                    <a class="exe-btn" href="/assembleia-composicao.php"><i class="bi bi-people" aria-hidden="true"></i> Composição da Assembleia</a>
                    <a class="exe-btn" href="/assembleia-sessoes.php"><i class="bi bi-calendar3" aria-hidden="true"></i> Sessões</a>
                </div>
            </section>

            <section class="exe-caixa" aria-labelledby="exeContactos">
                <span class="exe-sec__kicker"><?= te('executivo.contactos.kicker') ?></span>
                <h2 id="exeContactos"><?= te('executivo.contactos.titulo') ?></h2>
                <ul class="exe-contactos">
                    <?php if ($telefone): ?><li><i class="bi bi-telephone" aria-hidden="true"></i><div><a href="tel:<?= $h($telLink) ?>"><?= $h($telefone) ?></a><br><small>Chamada para a rede fixa nacional</small></div></li><?php endif; ?>
                    <?php if ($email): ?><li><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:<?= $h($email) ?>"><?= $h($email) ?></a></li><?php endif; ?>
                    <?php if ($morada): ?><li><i class="bi bi-geo-alt" aria-hidden="true"></i><span><?= $h($morada) ?></span></li><?php endif; ?>
                    <?php if ($horario): ?><li><i class="bi bi-clock" aria-hidden="true"></i><span><?= $h($horario) ?></span></li><?php endif; ?>
                </ul>
                <div class="exe-acoes" style="margin-top:0">
                    <a class="exe-btn exe-btn--principal" href="/contactos.php"><i class="bi bi-chat-dots" aria-hidden="true"></i> Página de contactos</a>
                </div>
            </section>
        </div>

    </div>
</div>

<?php require_once "includes/footer.php"; ?>
