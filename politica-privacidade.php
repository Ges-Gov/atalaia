<?php require_once __DIR__ . "/includes/header.php"; ?>
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
.legal-card p,.legal-card li{color:#334155;line-height:1.75;font-size:16px}
.legal-card ul{padding-left:22px}
.legal-card a{color:var(--cor-principal);font-weight:800}
.legal-meta{color:#64748b;font-size:14px;margin-top:8px}
.legal-note{background:#fffbeb;border:1px solid var(--ouro-fundo-2, #fde68a);border-radius:14px;padding:14px 16px;color:var(--ouro-texto, #92400e);font-weight:700;font-size:14px}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-shield-lock"></i> <?= te('politica_privacidade.hero.kicker') ?></span>
        <h1><?= te('politica_privacidade.hero.titulo') ?></h1>
        <p><?= te('politica_privacidade.hero.subtitulo') ?></p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p class="legal-meta"><?= te('politica_privacidade.hero.atualizacao') ?> <?= date('m/Y') ?></p>

        <h2><?= te('politica_privacidade.ponto1.titulo') ?></h2>
        <p>A <strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong> é a
        <?= te('politica_privacidade.ponto1.texto') ?></p>
        <ul>
            <?php if(siteConfig('morada')): ?><li><?= te('politica_privacidade.ponto1.morada') ?> <?= htmlspecialchars(siteConfig('morada')) ?></li><?php endif; ?>
            <?php if(siteConfig('telefone')): ?><li><?= te('politica_privacidade.ponto1.telefone') ?> <?= htmlspecialchars(siteConfig('telefone')) ?></li><?php endif; ?>
            <?php if(siteConfig('email')): ?><li><?= te('politica_privacidade.ponto1.email') ?> <?= htmlspecialchars(siteConfig('email')) ?></li><?php endif; ?>
        </ul>
        <p><?= te('politica_privacidade.ponto1.epd_texto1') ?> <strong><?= te('politica_privacidade.ponto1.epd_destaque') ?></strong> <?= te('politica_privacidade.ponto1.epd_texto2') ?>
        <a href="/protecao-dados.php"><?= te('politica_privacidade.ponto1.epd_link') ?></a>.</p>

        <h2><?= te('politica_privacidade.ponto2.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto2.intro') ?></p>
        <ul>
            <li><strong><?= te('politica_privacidade.ponto2.form_titulo') ?></strong> <?= te('politica_privacidade.ponto2.form_texto1') ?>
            <?= te('politica_privacidade.ponto2.form_texto2') ?>
            <?= te('politica_privacidade.ponto2.form_texto3') ?></li>
            <li><strong><?= te('politica_privacidade.ponto2.nav_titulo') ?></strong><?= te('politica_privacidade.ponto2.nav_texto') ?>
            (ver <a href="/politica-cookies.php"><?= te('politica_privacidade.ponto2.nav_link') ?></a>).</li>
            <li><strong><?= te('politica_privacidade.ponto2.assist_titulo') ?></strong><?= te('politica_privacidade.ponto2.assist_texto1') ?>
            <?= te('politica_privacidade.ponto2.assist_texto2') ?></li>
        </ul>

        <h2><?= te('politica_privacidade.ponto3.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto3.texto1') ?> <strong><?= te('politica_privacidade.ponto3.destaque1') ?></strong>
        e no <strong><?= te('politica_privacidade.ponto3.destaque2') ?></strong> <?= te('politica_privacidade.ponto3.texto2') ?>
        <?= te('politica_privacidade.ponto3.texto3') ?> <strong>consentimento</strong> <?= te('politica_privacidade.ponto3.texto4') ?></p>

        <h2><?= te('politica_privacidade.ponto4.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto4.texto1') ?>
        <?= te('politica_privacidade.ponto4.texto2') ?></p>

        <h2><?= te('politica_privacidade.ponto5.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto5.texto1') ?>
        <?= te('politica_privacidade.ponto5.texto2') ?></p>

        <h3><?= te('politica_privacidade.ponto5_1.titulo') ?></h3>
        <p><?= te('politica_privacidade.ponto5_1.p1_texto1') ?> <strong><?= te('politica_privacidade.ponto5_1.p1_destaque') ?></strong> <?= te('politica_privacidade.ponto5_1.p1_texto2') ?>
        <?= te('politica_privacidade.ponto5_1.p1_texto3') ?>

        <strong><?= te('politica_privacidade.ponto5_1.p1_destaque2') ?></strong>.</p>
        <p><?= te('politica_privacidade.ponto5_1.p2_texto1') ?>
        (<strong><?= te('politica_privacidade.ponto5_1.p2_servico') ?></strong><?= te('politica_privacidade.ponto5_1.p2_texto2') ?>
        <?= te('politica_privacidade.ponto5_1.p2_texto3') ?> <strong><?= te('politica_privacidade.ponto5_1.p2_destaque') ?></strong>
        <?= te('politica_privacidade.ponto5_1.p2_texto4') ?>

        <em>EU-U.S. Data Privacy Framework</em><?= te('politica_privacidade.ponto5_1.p2_texto5') ?>
        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener"><?= te('politica_privacidade.ponto5_1.p2_link') ?></a>.</p>
        <p><strong><?= te('politica_privacidade.ponto5_1.p3_destaque') ?></strong> <?= te('politica_privacidade.ponto5_1.p3_texto1') ?>
        <?= te('politica_privacidade.ponto5_1.p3_texto2') ?>

        (cookie <code>PHPSESSID</code>, ver <a href="/politica-cookies.php">Política de Cookies</a>), um
        <?= te('politica_privacidade.ponto5_1.p3_texto3') ?>
        <?= te('politica_privacidade.ponto5_1.p3_texto4') ?></p>
        <p><?= te('politica_privacidade.ponto5_1.p4_texto1') ?> <strong>consentimento</strong><?= te('politica_privacidade.ponto5_1.p4_texto2') ?>
        <?= te('politica_privacidade.ponto5_1.p4_texto3') ?> <strong><?= te('politica_privacidade.ponto5_1.p4_destaque') ?>
        terceiros</strong> <?= te('politica_privacidade.ponto5_1.p4_texto4') ?>
        <?= te('politica_privacidade.ponto5_1.p4_texto5') ?>
        <?= te('politica_privacidade.ponto5_1.p4_texto6') ?><a href="/requerimentos.php"><?= te('politica_privacidade.ponto5_1.p4_link1') ?></a>, <a href="/canal-denuncias.php">Canal
        de Denúncias</a>, <a href="/protecao-dados.php"><?= te('politica_privacidade.ponto5_1.p4_link3') ?></a>).</p>

        <h2><?= te('politica_privacidade.ponto6.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto6.texto1') ?> <strong><?= te('politica_privacidade.ponto6.direitos1') ?>
        <?= te('politica_privacidade.ponto6.direitos2') ?></strong><?= te('politica_privacidade.ponto6.texto2') ?>
        <a href="/protecao-dados.php"><?= te('politica_privacidade.ponto6.link') ?></a>.</p>
        <p><?= te('politica_privacidade.ponto6.cnpd_texto') ?>
        <strong><?= te('politica_privacidade.ponto6.cnpd_nome') ?></strong> — <a href="https://www.cnpd.pt" target="_blank" rel="noopener">www.cnpd.pt</a>.</p>

        <h2><?= te('politica_privacidade.ponto7.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto7.texto1') ?>
        <?= te('politica_privacidade.ponto7.texto2') ?></p>

        <h2><?= te('politica_privacidade.ponto8.titulo') ?></h2>
        <p><?= te('politica_privacidade.ponto8.texto') ?></p>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
