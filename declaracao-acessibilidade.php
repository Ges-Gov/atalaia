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
.legal-badge{display:inline-block;background:#dcfce7;color:#166534;border-radius:999px;padding:6px 14px;font-weight:900;font-size:14px}
.legal-note{background:#fffbeb;border:1px solid var(--ouro-fundo-2, #fde68a);border-radius:14px;padding:14px 16px;color:var(--ouro-texto, #92400e);font-weight:700;font-size:14px}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-universal-access"></i> <?= te('declaracao_acessibilidade.hero.kicker') ?></span>
        <h1><?= te('declaracao_acessibilidade.hero.titulo') ?></h1>
        <p><?= te('declaracao_acessibilidade.hero.subtitulo') ?></p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p>A <strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong> está
        <?= te('declaracao_acessibilidade.intro.texto') ?>
        <?= te('declaracao_acessibilidade.intro.sitio') ?> <strong><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?></strong>.</p>

        <h2><?= te('declaracao_acessibilidade.ponto1.titulo') ?></h2>
        <p><span class="legal-badge"><?= te('declaracao_acessibilidade.ponto1.selo') ?></span></p>
        <p><?= te('declaracao_acessibilidade.ponto1.texto1') ?> <strong><?= te('declaracao_acessibilidade.ponto1.destaque') ?></strong> <?= te('declaracao_acessibilidade.ponto1.texto2') ?>
        <?= te('declaracao_acessibilidade.ponto1.texto3') ?></p>

        <h2><?= te('declaracao_acessibilidade.ponto2.titulo') ?></h2>
        <ul>
            <li><?= te('declaracao_acessibilidade.ponto2.medida1') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida2') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida3') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida4') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida5') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto2.medida6') ?></li>
        </ul>

        <h2><?= te('declaracao_acessibilidade.ponto3.titulo') ?></h2>
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

        <h2><?= te('declaracao_acessibilidade.ponto4.titulo') ?></h2>
        <p><?= te('declaracao_acessibilidade.ponto4.texto1') ?> <strong><?= date('m/Y') ?></strong><?= te('declaracao_acessibilidade.ponto4.texto2') ?>
        <?= te('declaracao_acessibilidade.ponto4.texto3') ?></p>
        <ul>
            <li><?= te('declaracao_acessibilidade.ponto4.metodo1') ?></li>
            <li><?= te('declaracao_acessibilidade.ponto4.metodo2_texto1') ?>
            <?= te('declaracao_acessibilidade.ponto4.metodo2_texto2') ?></li>
        </ul>

        <h2><?= te('declaracao_acessibilidade.ponto5.titulo') ?></h2>
        <p><?= te('declaracao_acessibilidade.ponto5.intro') ?></p>
        <ul>
            <?php if(siteConfig('email')): ?><li><?= te('declaracao_acessibilidade.ponto5.email') ?> <?= htmlspecialchars(siteConfig('email')) ?></li><?php endif; ?>
            <?php if(siteConfig('telefone')): ?><li><?= te('declaracao_acessibilidade.ponto5.telefone') ?> <?= htmlspecialchars(siteConfig('telefone')) ?></li><?php endif; ?>
            <li><?= te('declaracao_acessibilidade.ponto5.contactos_texto') ?> <a href="/contactos.php"><?= te('declaracao_acessibilidade.ponto5.contactos_link') ?></a>.</li>
        </ul>
        <p><?= te('declaracao_acessibilidade.ponto5.prazo') ?></p>

        <h2><?= te('declaracao_acessibilidade.ponto6.titulo') ?></h2>
        <p><?= te('declaracao_acessibilidade.ponto6.texto1') ?>
        <strong><?= te('declaracao_acessibilidade.ponto6.entidade') ?></strong><?= te('declaracao_acessibilidade.ponto6.texto2') ?>
        <?= te('declaracao_acessibilidade.ponto6.texto3') ?> <a href="https://www.acessibilidade.gov.pt" target="_blank" rel="noopener">acessibilidade.gov.pt</a>.</p>

    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
