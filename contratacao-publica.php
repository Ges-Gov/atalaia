<?php
require_once __DIR__ . "/includes/db.php";

$cfg = [
    'hero_kicker' => 'Transparência e Gestão Pública',
    'hero_titulo' => 'Contratação Pública',
    'texto' => '',
    'botao_texto' => 'Ver no Portal BASE',
    'botao_link' => '',
];
try {
    $row = $pdo->query("SELECT * FROM contratacao_publica_pagina ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row) $cfg = array_merge($cfg, $row);
} catch (Exception $e) {}

require_once __DIR__ . "/includes/header.php";

$baseGovUrl = !empty($cfg['botao_link'])
    ? $cfg['botao_link']
    : "https://www.base.gov.pt/Base4/pt/pesquisa/?type=contratos&adjudicante=" . urlencode(siteConfig('nome_site', 'Junta de Freguesia'));
?>

<style>
.cp-info-page{background:linear-gradient(180deg,#f8fafc 0%,#fff 52%,#f7f4ef 100%);}
.cp-info-hero{position:relative;min-height:480px;display:flex;align-items:center;color:white;overflow:hidden;background:linear-gradient(90deg,rgba(17,21,28,.93),rgba(17,21,28,.50)),linear-gradient(0deg,rgba(0,0,0,.28),transparent),url('/assets/img/freguesia-2.jpg') center/cover no-repeat;}
.cp-info-hero:after{content:"";position:absolute;left:0;right:0;bottom:0;height:120px;background:linear-gradient(0deg,#f8fafc,transparent);}
.cp-info-inner{position:relative;z-index:2;max-width:820px;padding:90px 0 110px;}
.cp-info-kicker{display:inline-flex;align-items:center;gap:8px;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.38);color:#F0D060;border-radius:999px;padding:10px 16px;font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.9px;margin-bottom:18px;}
.cp-info-hero h1{font-size:clamp(38px,5.5vw,64px);line-height:1.05;margin:0 0 20px;letter-spacing:-1.2px;}
.cp-info-hero p{font-size:19px;line-height:1.75;color:#dbeafe;margin:0 0 26px;max-width:720px;}
.cp-info-btn{display:inline-flex;align-items:center;gap:10px;background:var(--cor-secundaria);color:#11151B!important;text-decoration:none;border-radius:16px;padding:16px 26px;font-weight:900;box-shadow:0 18px 45px rgba(0,0,0,.25);transition:.25s ease;}
.cp-info-btn:hover{transform:translateY(-3px);filter:brightness(1.05);}
@media(max-width:760px){.cp-info-hero{min-height:420px}.cp-info-inner{padding:70px 0 90px}.cp-info-hero p{font-size:16px}}
</style>

<main class="cp-info-page">
    <section class="cp-info-hero">
        <div class="container">
            <div class="cp-info-inner">
                <span class="cp-info-kicker"><i class="bi bi-briefcase"></i> <?= htmlspecialchars($cfg['hero_kicker']) ?></span>
                <h1><?= htmlspecialchars($cfg['hero_titulo']) ?></h1>
                <?php if (!empty($cfg['texto'])): ?>
                    <p><?= nl2br(htmlspecialchars($cfg['texto'])) ?></p>
                <?php endif; ?>
                <a class="cp-info-btn" href="<?= htmlspecialchars($baseGovUrl) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right"></i> <?= htmlspecialchars($cfg['botao_texto']) ?>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
