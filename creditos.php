<?php require_once __DIR__ . "/includes/header.php"; ?>
<?php
/* Créditos das imagens de terceiros (02/11/2026): autor/titular, licença e fonte de cada imagem.
   Os dados vêm de includes/creditos.php (tabela creditos_imagens + créditos já existentes noutros sítios). */
require_once __DIR__ . "/includes/creditos.php";
$creditos = creditosImagens($pdo);
$emailJunta = siteConfig('email');
// Os sites do modelo antigo não têm textos editáveis (te()): aí ficam estes, iguais aos de includes/textos-registo.php.
$textosCreditos = [
    'creditos.hero.kicker' => 'Créditos',
    'creditos.hero.titulo' => 'Créditos das imagens',
    'creditos.hero.subtitulo' => 'Autoria e licença das fotografias e imagens de terceiros usadas neste site.',
    'creditos.intro.texto' => 'Respeitamos os direitos de autor de quem fotografa. As imagens de terceiros publicadas neste site são usadas com autorização ou ao abrigo da respetiva licença, e o seu autor ou titular fica identificado abaixo.',
    'creditos.contacto.texto' => 'Se é autor ou titular de uma imagem deste site e ela não está corretamente identificada, ou se não autorizou a sua utilização, contacte-nos e corrigimos ou retiramos a imagem:',
    'creditos.vazio.texto' => 'Ainda não há imagens de terceiros registadas nesta página.',
];
$t = fn(string $chave): string => function_exists('te') ? te($chave) : htmlspecialchars($textosCreditos[$chave]);
?>
<style>
.legal-hero{background:linear-gradient(135deg,var(--cor-principal),#11151B 70%,#071c2e);color:#fff;padding:80px 0 96px}
.legal-hero .container{max-width:1100px}
.legal-kicker{display:inline-flex;gap:6px;background:rgb(var(--ouro-rgb, 240 180 41) / .16);border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .38);color:var(--ouro-claro, #f8d77a);padding:8px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.7px;font-size:12px}
.legal-hero h1{font-size:clamp(34px,5vw,58px);margin:16px 0 10px;line-height:1.05}
.legal-hero p{color:#dbeafe;font-size:18px;margin:0}
.legal-shell{max-width:1100px;margin:-46px auto 60px;position:relative;z-index:3;padding:0 16px}
.legal-card{background:#fff;border:1px solid #e5e7eb;border-radius:26px;box-shadow:0 18px 50px rgba(15,23,42,.08);padding:34px 38px}
.legal-card p{color:#334155;line-height:1.75;font-size:16px}
.legal-card a{color:var(--cor-principal);font-weight:800}
.legal-note{background:#fffbeb;border:1px solid var(--ouro-fundo-2, #fde68a);border-radius:14px;padding:14px 16px;color:var(--ouro-texto, #92400e);font-weight:700;font-size:14px}
.creditos-grelha{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:18px;margin-top:24px}
.credito{border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;background:#f8fafc;display:flex;flex-direction:column}
.credito img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;background:#e5e7eb}
.credito div{padding:12px 14px 14px;display:flex;flex-direction:column;gap:4px;font-size:14px;line-height:1.45}
.credito .credito-onde{color:#64748b;font-weight:700;font-size:12px}
.credito .credito-titular{color:#11151B;font-weight:900}
.credito .credito-licenca{color:#334155}
.credito a{font-size:13px}
@media(max-width:600px){.legal-card{padding:24px 18px}}
:root[data-tema="escuro"] .legal-card{background:var(--escuro-superficie);border-color:var(--escuro-borda)}
:root[data-tema="escuro"] .legal-card p,:root[data-tema="escuro"] .credito .credito-licenca{color:var(--escuro-texto-2)}
:root[data-tema="escuro"] .credito{background:var(--escuro-superficie-2);border-color:var(--escuro-borda)}
:root[data-tema="escuro"] .credito .credito-titular{color:var(--escuro-texto)}
:root[data-tema="escuro"] .credito .credito-onde{color:var(--escuro-texto-2)}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-camera"></i> <?= $t('creditos.hero.kicker') ?></span>
        <h1><?= $t('creditos.hero.titulo') ?></h1>
        <p><?= $t('creditos.hero.subtitulo') ?></p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p><?= $t('creditos.intro.texto') ?></p>
        <p class="legal-note"><?= $t('creditos.contacto.texto') ?>
            <?php if ($emailJunta): ?><a href="mailto:<?= htmlspecialchars($emailJunta) ?>"><?= htmlspecialchars($emailJunta) ?></a>.<?php endif; ?></p>

        <?php if (!$creditos): ?>
            <p><?= $t('creditos.vazio.texto') ?></p>
        <?php else: ?>
            <div class="creditos-grelha">
                <?php foreach ($creditos as $c): ?>
                    <figure class="credito" style="margin:0">
                        <img src="<?= htmlspecialchars(miniaturaImagem($c['src'], 480)) ?>" alt="" loading="lazy">
                        <div>
                            <?php if ($c['onde'] !== ''): ?><span class="credito-onde"><?= htmlspecialchars($c['onde']) ?></span><?php endif; ?>
                            <span class="credito-titular"><i class="bi bi-camera" aria-hidden="true"></i> <?= htmlspecialchars($c['titular']) ?></span>
                            <?php if ($c['licenca'] !== '' || $c['fonte'] !== ''): ?>
                                <span class="credito-licenca"><?= htmlspecialchars(implode(' · ', array_filter([$c['fonte'], $c['licenca']]))) ?></span>
                            <?php endif; ?>
                            <?php if ($c['nota'] !== ''): ?><span class="credito-licenca"><?= htmlspecialchars($c['nota']) ?></span><?php endif; ?>
                            <?php if (preg_match('#^https?://#i', $c['fonte_url'])): ?><a href="<?= htmlspecialchars($c['fonte_url']) ?>" target="_blank" rel="noopener">Ver a imagem original <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a><?php endif; ?>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
