<?php require_once __DIR__ . "/includes/header.php";

// 18/11/2026 — Declaração no modelo da AMA (secções I a V). Os resultados vêm da última avaliação registada em
// acessibilidade_avaliacoes (migração 090); sem avaliação, a página diz que está em curso em vez de inventar datas.
$av = null;
try {
    $av = $pdo->query("SELECT * FROM acessibilidade_avaliacoes ORDER BY data_declaracao DESC, id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $av = null;
}
// 19/11/2026 (Selo): as avaliações manuais (checklists da AMA) desta avaliação, cada uma com a folha de evidências.
// A marcação das secções segue o gerador da AMA (classes «mr …»), que o Observatório e a candidatura ao Selo leem.
$checklistsAv = [];
if ($av) {
    try {
        $st = $pdo->prepare("SELECT * FROM acessibilidade_checklists WHERE avaliacao_id = ? ORDER BY FIELD(checklist, '10aspetos', 'conteudo', 'transacao'), id");
        $st->execute([(int) $av['id']]);
        $checklistsAv = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $checklistsAv = [];   // migração 092 ainda por correr
    }
    // registos antigos (só a checklist «10 aspetos» nas colunas manual_*)
    if (!$checklistsAv && !empty($av['manual_data']) && $av['manual_satisfeitas'] !== null && $av['manual_total']) {
        $checklistsAv[] = ['checklist' => '10aspetos', 'data_avaliacao' => $av['manual_data'], 'paginas' => $av['manual_paginas'],
            'satisfeitas' => $av['manual_satisfeitas'], 'aplicaveis' => $av['manual_total'], 'evidencia_url' => null];
    }
}
$nomesChecklists = [
    '10aspetos' => 'Checklist «10 aspetos críticos de acessibilidade funcional»',
    'conteudo' => 'Checklist «Conteúdo»',
    'transacao' => 'Checklist «Transação»',
];
$estados = ['plenamente' => 'Plenamente conforme', 'parcialmente' => 'Parcialmente conforme', 'nao' => 'Não conforme'];
$estado = ($av && isset($estados[$av['estado']])) ? $av['estado'] : null;
$dataPt = fn ($d) => $d ? date('d/m/Y', strtotime($d)) : '';
$h = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<style>
.legal-hero{background:linear-gradient(135deg,var(--cor-principal),#11151B 70%,#071c2e);color:#fff;padding:80px 0 96px}
.legal-hero .container{max-width:900px}
.legal-kicker{display:inline-flex;background:rgb(var(--ouro-rgb, 240 180 41) / .16);border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .38);color:var(--ouro-claro, #f8d77a);padding:8px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.7px;font-size:12px}
.legal-hero h1{font-size:clamp(34px,5vw,58px);margin:16px 0 10px;line-height:1.05}
.legal-hero p{color:#dbeafe;font-size:18px;margin:0}
.legal-shell{max-width:900px;margin:-46px auto 60px;position:relative;z-index:3;padding:0 16px}
.legal-card{background:#fff;border:1px solid #e5e7eb;border-radius:26px;box-shadow:0 18px 50px rgba(15,23,42,.08);padding:34px 38px}
.legal-card h2{color:#11151B;font-size:24px;margin:30px 0 10px}
.legal-card h2:first-child{margin-top:0}
.legal-card h3{color:#11151B;font-size:18px;margin:22px 0 6px}
.legal-card p,.legal-card li{color:#334155;line-height:1.75;font-size:16px}
.legal-card ul{padding-left:22px}
.legal-card a{color:var(--cor-principal);font-weight:800}
.legal-meta{color:#64748b;font-size:14px;margin-top:8px}
.legal-badge{display:inline-block;background:#dcfce7;color:#166534;border-radius:999px;padding:6px 14px;font-weight:900;font-size:14px}
.legal-badge.parcialmente{background:#fef3c7;color:#92400e}
.legal-badge.nao{background:#fee2e2;color:#991b1b}
.legal-note{background:#fffbeb;border:1px solid var(--ouro-fundo-2, #fde68a);border-radius:14px;padding:14px 16px;color:var(--ouro-texto, #92400e);font-weight:700;font-size:14px}
.legal-dados{margin:4px 0 0;padding:0;list-style:none}
.legal-dados li{padding-left:0}
.legal-dados strong{color:#11151B}
.legal-avaliacoes ol{padding-left:22px;margin:6px 0 0}
.legal-avaliacoes ol > li{margin-bottom:10px}
.legal-avaliacoes ul{margin:4px 0 0;padding-left:20px;list-style:disc}
.legal-avaliacoes ul li{line-height:1.6}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-universal-access" aria-hidden="true"></i> <?= te('declaracao_acessibilidade.hero.kicker') ?></span>
        <h1><?= te('declaracao_acessibilidade.hero.titulo') ?></h1>
        <p><?= te('declaracao_acessibilidade.hero.subtitulo') ?></p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p><span class="mr mr-e-intro">A</span> <strong><span class="mr mr-e-name"><?= $h(siteConfig('nome_site', 'Junta de Freguesia')) ?></span></strong> está
        <?= te('declaracao_acessibilidade.intro.texto') ?>
        <?= te('declaracao_acessibilidade.intro.sitio') ?> <span class="mr mr-t-type" hidden>Sítio Web</span><a href="/" class="siteurl"><strong><span class="mr mr-t-desc"><?= $h($_SERVER['HTTP_HOST'] ?? '') ?></span></strong></a>.</p>

        <!-- I. Estado de conformidade -->
        <h2><?= te('declaracao_acessibilidade.ponto1.titulo') ?></h2>
        <?php if ($estado): ?>
            <p><span class="legal-badge <?= $h($estado) ?>"><?= $h($estados[$estado]) ?></span></p>
            <p><?= te('declaracao_acessibilidade.ponto1.texto1') ?> <strong><span class="mr mr-conformance-status"><?= $h(mb_strtolower($estados[$estado])) ?></span></strong> <?= te('declaracao_acessibilidade.ponto1.texto2') ?>
            <?= $estado === 'plenamente' ? te('declaracao_acessibilidade.ponto1.texto3_pleno') : te('declaracao_acessibilidade.ponto1.texto3') ?></p>
        <?php else: ?>
            <p><span class="legal-badge parcialmente"><?= te('declaracao_acessibilidade.ponto1.selo') ?></span></p>
            <p><?= te('declaracao_acessibilidade.ponto1.texto1') ?> <strong><?= te('declaracao_acessibilidade.ponto1.destaque') ?></strong> <?= te('declaracao_acessibilidade.ponto1.texto2') ?>
            <?= te('declaracao_acessibilidade.ponto1.texto3') ?></p>
        <?php endif; ?>

        <?php if ($estado !== 'plenamente'): ?>
            <h3><?= te('declaracao_acessibilidade.ponto3.titulo') ?></h3>
            <p><?= te('declaracao_acessibilidade.ponto3.intro') ?></p>
            <ul>
                <li><?= te('declaracao_acessibilidade.ponto3.limitacao1') ?></li>
                <li><?= te('declaracao_acessibilidade.ponto3.limitacao2_texto1') ?>
                <?= te('declaracao_acessibilidade.ponto3.limitacao2_texto2') ?> <strong><?= te('declaracao_acessibilidade.ponto3.limitacao2_destaque') ?></strong> <?= te('declaracao_acessibilidade.ponto3.limitacao2_texto3') ?>
                <?= te('declaracao_acessibilidade.ponto3.limitacao2_texto4') ?></li>
                <li><?= te('declaracao_acessibilidade.ponto3.limitacao3_texto1') ?>
                <?= te('declaracao_acessibilidade.ponto3.limitacao3_texto2') ?></li>
            </ul>
            <p><?= te('declaracao_acessibilidade.ponto3.compromisso') ?></p>
        <?php endif; ?>

        <!-- II. Elaboração da presente declaração -->
        <h2><?= te('declaracao_acessibilidade.ponto4.titulo') ?></h2>
        <?php if (!$av): ?>
            <p><?= te('declaracao_acessibilidade.ponto4.sem_avaliacao') ?></p>
        <?php else: ?>
            <?php /* As datas dentro das classes «mr» ficam em AAAA-MM-DD e as listas com o texto do gerador da AMA
                     («(data). Relatório: …», «Amostra: N páginas.», «Principais resultados …»): é o que o Observatório lê. */ ?>
            <p><?= te('declaracao_acessibilidade.ponto4.atualizada') ?> <strong><span class="mr mr-date"><?= $h($av['data_declaracao']) ?></span></strong>.
            <?= te('declaracao_acessibilidade.ponto4.metodos') ?></p>

            <h3><?= te('declaracao_acessibilidade.ponto4.a_titulo') ?></h3>
            <?php if (!empty($av['auto_data'])): ?>
                <article class="mr mr-automatic-summary legal-avaliacoes"><ol><li>(<?= $h($av['auto_data']) ?>). Relatório: <a href="<?= $h($av['auto_relatorio_url'] ?? '' ?: '/declaracao-acessibilidade.php') ?>"<?= str_starts_with((string)($av['auto_relatorio_url'] ?? ''), 'https://') ? ' target="_blank" rel="noopener"' : '' ?>>Avaliação automática de <?= $h($dataPt($av['auto_data'])) ?></a><ul>
                    <li>Ferramenta utilizada: <?= $h($av['auto_ferramenta'] ?: 'AccessMonitor') ?></li>
                    <?php if ($av['auto_paginas'] !== null): ?><li>Amostra: <?= (int) $av['auto_paginas'] ?> páginas.</li><?php endif; ?>
                    <li>Principais resultados (sumário): <?php if ($av['auto_nota'] !== null): ?>média de <?= $h(number_format((float) $av['auto_nota'], 1, ',', '')) ?> na escala do AccessMonitor (1 a 10).<?php endif; ?> <?= $h($av['auto_resumo'] ?? '') ?></li>
                </ul></li></ol></article>
            <?php else: ?>
                <p><?= te('declaracao_acessibilidade.ponto4.por_registar') ?></p>
            <?php endif; ?>

            <h3><?= te('declaracao_acessibilidade.ponto4.b_titulo') ?></h3>
            <?php if ($checklistsAv): ?>
                <article class="mr mr-manual-summary legal-avaliacoes"><ol>
                <?php foreach ($checklistsAv as $c): $nomeC = $nomesChecklists[$c['checklist']] ?? $c['checklist']; ?>
                    <li>(<?= $h($c['data_avaliacao']) ?>). Relatório: <?php if (!empty($c['evidencia_url'])): ?><a href="<?= $h($c['evidencia_url']) ?>"><?= $h($nomeC) ?></a> <span class="legal-meta">(folha de evidências, Excel)</span><?php else: ?><a href="/declaracao-acessibilidade.php"><?= $h($nomeC) ?></a><?php endif; ?><ul>
                        <?php if ($c['paginas'] !== null): ?><li>Amostra: <?= (int) $c['paginas'] ?> páginas.</li><?php endif; ?>
                        <li>Principais resultados (heurísticas satisfeitas/total heurísticas aplicadas): <?= (int) $c['satisfeitas'] ?>/<?= (int) $c['aplicaveis'] ?></li>
                    </ul></li>
                <?php endforeach; ?>
                </ol></article>
            <?php else: ?>
                <p><?= te('declaracao_acessibilidade.ponto4.por_registar') ?></p>
            <?php endif; ?>

            <h3><?= te('declaracao_acessibilidade.ponto4.c_titulo') ?></h3>
            <?php if (!empty($av['usabilidade'])): ?>
                <article class="mr mr-users-summary"><p><?= nl2br($h($av['usabilidade']), false) ?></p></article>
            <?php else: ?>
                <p><?= te('declaracao_acessibilidade.ponto4.c_nao') ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- III. Contacto e solicitação de informação -->
        <h2><?= te('declaracao_acessibilidade.ponto5.titulo') ?></h2>
        <p><?= te('declaracao_acessibilidade.ponto5.intro') ?></p>
        <ul class="mr mr-contacts">
            <?php if (siteConfig('email')): ?><li><?= te('declaracao_acessibilidade.ponto5.email') ?> <?= $h(siteConfig('email')) ?></li><?php endif; ?>
            <?php if (siteConfig('telefone')): ?><li><?= te('declaracao_acessibilidade.ponto5.telefone') ?> <?= $h(siteConfig('telefone')) ?></li><?php endif; ?>
            <li><?= te('declaracao_acessibilidade.ponto5.contactos_texto') ?> <a href="/contactos.php"><?= te('declaracao_acessibilidade.ponto5.contactos_link') ?></a>.</li>
        </ul>
        <p><?= te('declaracao_acessibilidade.ponto5.prazo') ?></p>

        <!-- IV. Outras evidências -->
        <h2><?= te('declaracao_acessibilidade.ponto2.titulo') ?></h2>
        <?php if (!empty($av['selo'])): ?>
            <p>O sítio Web <?= $h($_SERVER['HTTP_HOST'] ?? '') ?> da <?= $h(siteConfig('nome_site', 'Junta de Freguesia')) ?> encontra-se certificado com o selo
            <strong><span class="mr mr-seal"><?= $h(ucfirst($av['selo'])) ?></span></strong> de usabilidade e acessibilidade.</p>
        <?php endif; ?>
        <p><?= te('declaracao_acessibilidade.ponto2.intro') ?></p>
        <ul>
            <li><?= te('declaracao_acessibilidade.ponto2.medida1') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida2') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida3') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida4') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida5') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida6') ?></li>
        </ul>

        <!-- V. Denúncia de situações de discriminação -->
        <h2><?= te('declaracao_acessibilidade.ponto7.titulo') ?></h2>
        <p><?= te('declaracao_acessibilidade.ponto7.texto') ?>
        <a href="https://www.inr.pt" target="_blank" rel="noopener"><?= te('declaracao_acessibilidade.ponto7.ligacao') ?></a>.</p>
        <p><?= te('declaracao_acessibilidade.ponto6.texto1') ?>
        <strong><?= te('declaracao_acessibilidade.ponto6.entidade') ?></strong><?= te('declaracao_acessibilidade.ponto6.texto2') ?>
        <?= te('declaracao_acessibilidade.ponto6.texto3') ?> <a href="https://www.acessibilidade.gov.pt" target="_blank" rel="noopener">acessibilidade.gov.pt</a>.</p>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
