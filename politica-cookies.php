<?php
require_once __DIR__ . "/includes/header.php";
$clarityAtivo = function_exists('temaConfig') ? (temaConfig('clarity_project_id', '') !== '') : false;
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
.legal-card p,.legal-card li{color:#334155;line-height:1.75;font-size:16px}
.legal-card ul{padding-left:22px}
.legal-card a{color:var(--cor-principal);font-weight:800}
.legal-meta{color:#64748b;font-size:14px;margin-top:8px}
.legal-table{width:100%;border-collapse:collapse;margin:12px 0;font-size:15px}
.legal-table caption{text-align:left;font-weight:800;color:#52606d;font-size:13px;margin-bottom:6px;caption-side:top}
.legal-table th,.legal-table td{border:1px solid #e5e7eb;padding:10px 12px;text-align:left;vertical-align:top}
.legal-table th{background:#f8fafc;color:#11151B}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-cookie"></i> <?= te('politica_cookies.hero.kicker') ?></span>
        <h1><?= te('politica_cookies.hero.titulo') ?></h1>
        <p><?= te('politica_cookies.hero.subtitulo') ?></p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p class="legal-meta"><?= te('politica_cookies.hero.atualizacao') ?> <?= date('m/Y') ?></p>

        <h2><?= te('politica_cookies.ponto1.titulo') ?></h2>
        <p><?= te('politica_cookies.ponto1.texto1') ?>
        <?= te('politica_cookies.ponto1.texto2') ?></p>

        <h2><?= te('politica_cookies.ponto2.titulo') ?></h2>
        <p><?= te('politica_cookies.ponto2.texto') ?> <strong><?= te('politica_cookies.ponto2.destaque') ?></strong>.</p>
        <table class="legal-table">
            <caption><?= te('politica_cookies.ponto2.tabela_legenda') ?></caption>
            <tr><th scope="col"><?= te('politica_cookies.ponto2.col_cookie') ?></th><th scope="col"><?= te('politica_cookies.ponto2.col_finalidade') ?></th><th scope="col"><?= te('politica_cookies.ponto2.col_duracao') ?></th></tr>
            <tr><td>PHPSESSID</td><td><?= te('politica_cookies.ponto2.sessao_finalidade') ?></td><td><?= te('politica_cookies.ponto2.sessao_duracao') ?></td></tr>
            <tr><td>rm_cookie_consent</td><td><?= te('politica_cookies.ponto2.consent_finalidade') ?></td><td><?= te('politica_cookies.ponto2.consent_duracao') ?></td></tr>
        </table>

        <h2><?= te('politica_cookies.ponto3.titulo') ?></h2>
        <p><?= te('politica_cookies.ponto3.texto1') ?> <strong><?= te('politica_cookies.ponto3.destaque') ?></strong> <?= te('politica_cookies.ponto3.texto2') ?></p>
        <table class="legal-table">
            <caption><?= te('politica_cookies.ponto3.tabela_legenda') ?></caption>
            <tr><th scope="col"><?= te('politica_cookies.ponto3.col_origem') ?></th><th scope="col"><?= te('politica_cookies.ponto3.col_finalidade') ?></th><th scope="col"><?= te('politica_cookies.ponto3.col_info') ?></th></tr>
            <tr>
                <td>YouTube (Google)</td>
                <td>Reprodução do vídeo em destaque na página inicial. Utilizamos o modo de privacidade
                melhorada (<em>youtube-nocookie.com</em>); ainda assim, ao reproduzir, o YouTube pode
                definir cookies.</td>
                <td><a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacidade Google</a></td>
            </tr>
            <?php if ($clarityAtivo): ?>
            <tr>
                <td><?= te('politica_cookies.ponto3.clarity_origem') ?></td>
                <td><?= te('politica_cookies.ponto3.clarity_texto1') ?>
                <?= te('politica_cookies.ponto3.clarity_texto2') ?>
                <?= te('politica_cookies.ponto3.clarity_texto3') ?></td>
                <td><a href="https://privacy.microsoft.com/pt-pt/privacystatement" target="_blank" rel="noopener"><?= te('politica_cookies.ponto3.clarity_link') ?></a></td>
            </tr>
            <?php endif; ?>
        </table>
        <p><?= te('politica_cookies.externos.texto1') ?> <strong><?= te('politica_cookies.externos.destaque') ?></strong>, mas
        <?= te('politica_cookies.externos.texto2') ?></p>
        <ul>
            <li><strong><?= te('politica_cookies.externos.mapa_titulo') ?></strong>: usamos <em>OpenStreetMap</em> e a biblioteca <em>Leaflet</em> (mapa aberto,
            <?= te('politica_cookies.externos.mapa_texto') ?></li>
            <li><strong><?= te('politica_cookies.externos.icones_titulo') ?></strong><?= te('politica_cookies.externos.icones_texto') ?></li>
            <li><strong><?= te('politica_cookies.externos.assist_titulo') ?></strong> <?= te('politica_cookies.externos.assist_texto1') ?>
            <?= te('politica_cookies.externos.assist_texto2') ?>

            <a href="/politica-privacidade.php"><?= te('politica_cookies.externos.assist_link') ?></a>, ponto 5.1.</li>
        </ul>

        <h2><?= te('politica_cookies.ponto4.titulo') ?></h2>
        <p><?= te('politica_cookies.ponto4.texto1') ?> <strong>"Aceitar todos"</strong> ou escolher
        <strong>"Só essenciais"</strong><?= te('politica_cookies.ponto4.texto2') ?>
        <?= te('politica_cookies.ponto4.texto3') ?></p>

        <h2><?= te('politica_cookies.ponto5.titulo') ?></h2>
        <p><?= te('politica_cookies.ponto5.texto') ?> <a href="/politica-privacidade.php"><?= te('politica_cookies.ponto5.link') ?></a>.</p>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
