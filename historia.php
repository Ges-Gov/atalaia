<?php
require_once __DIR__ . "/includes/db.php";

$pagina = $pdo->query("SELECT * FROM pagina_historia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$timeline = [];

if (!empty($pagina['timeline_texto'])) {
    $linhas = preg_split('/\r\n|\r|\n/', trim($pagina['timeline_texto']));

    foreach ($linhas as $linha) {
        $partes = explode('|', $linha, 2);

        $timeline[] = [
            'titulo' => trim($partes[0] ?? ''),
            'texto' => trim($partes[1] ?? '')
        ];
    }
}


/* HERÁLDICA — integrada na página História */
$heraldica = [
    'hero_kicker' => 'Heráldica',
    'titulo' => 'Brasão da Freguesia de Atalaia e Alto Estanqueiro-Jardia',
    'texto_intro' => 'Escudo de ouro, com uma buzina de vermelho cordada de azul; em campanha, charrua de vermelho com relha, podão, maço e cadeia de negro; bordadura de azul carregada de contas de rosário. Coroa mural de prata de três torres. Listel branco com a legenda em letras negras: GRANHO.',
    'imagem' => 'heraldica-granho.webp',
    'fonte_texto' => '',
    'fonte_url' => ''
];

try {
    $stmtHeraldica = $pdo->query("SELECT * FROM heraldica_pagina WHERE ativo = 1 ORDER BY id ASC LIMIT 1");
    $rowHeraldica = $stmtHeraldica->fetch(PDO::FETCH_ASSOC);
    if ($rowHeraldica) $heraldica = array_merge($heraldica, $rowHeraldica);
} catch(Exception $e) {}

$elementosHeraldica = [];
try {
    $stmtElementos = $pdo->query("SELECT * FROM heraldica_elementos WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
    $elementosHeraldica = $stmtElementos->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$heraldicaImg = !empty($heraldica['imagem'])
    ? "/uploads/heraldica/" . htmlspecialchars($heraldica['imagem'])
    : "/assets/img/heraldica-granho.webp";

/* Se houver vários brasões individuais (união de freguesias), mostra-os
   empilhados em vez do símbolo único combinado. */
$brasoesIndividuais = [];
foreach ($elementosHeraldica as $elem) {
    if (!empty($elem['imagem'])) {
        $brasoesIndividuais[] = $elem;
    }
}

$heroImagem = !empty($pagina['hero_imagem'])
    ? "/assets/img/" . $pagina['hero_imagem']
    : "/assets/img/freguesia-2.jpg";

function textoSeguro($texto) {
    return nl2br(htmlspecialchars($texto ?? ''));
}

?>
<?php require_once "includes/header.php"; ?>
<style>

.historia-page {
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 35%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 50%, #f7f4ef 100%);
}

.historia-hero {
    position: relative;
    min-height: 650px;
    overflow: hidden;
    display: flex;
    align-items: center;
    color: white;
    background:
        linear-gradient(90deg, rgba(17,21,28,.92), rgba(17,21,28,.48)),
        linear-gradient(0deg, rgba(0,0,0,.32), transparent),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.historia-hero::before {
    content: "";
    position: absolute;
    width: 620px;
    height: 620px;
    border-radius: 50%;
    right: -220px;
    top: -250px;
    background: rgba(255,255,255,.06);
}

.historia-hero::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 170px;
    background: linear-gradient(0deg,#f8fafc,transparent);
}

.historia-hero-content {
    position: relative;
    z-index: 2;
    max-width: 820px;
    padding: 100px 0 140px;
}

.historia-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(212,170,0,.14);
    border: 1px solid rgba(212,170,0,.38);
    color: #D4AA00;
    border-radius: 999px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .9px;
    margin-bottom: 18px;
}

.historia-hero h1 {
    font-size: clamp(46px, 7vw, 84px);
    line-height: .96;
    letter-spacing: -2px;
    margin: 0 0 22px;
}

.historia-hero p {
    font-size: 21px;
    line-height: 1.8;
    color: #dbeafe;
    margin: 0 0 34px;
}

.historia-actions {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.historia-btn {
    padding: 16px 26px;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 900;
    transition: .25s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.historia-btn.primary {
    background: var(--cor-secundaria);
    color: #11151B;
    box-shadow: 0 16px 40px rgba(0,0,0,.22);
}

.historia-btn.secondary {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.22);
    color: white;
    backdrop-filter: blur(10px);
}

.historia-btn:hover {
    transform: translateY(-4px);
}

.historia-main {
    position: relative;
    z-index: 4;
    margin-top: -70px;
}

.historia-intro {
    background: white;
    border-radius: 34px;
    padding: 42px;
    box-shadow: 0 24px 70px rgba(0,0,0,.10);
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 30px;
    margin-bottom: 30px;
}

.historia-intro h2,
.historia-section-title h2 {
    font-size: clamp(32px, 4vw, 48px);
    line-height: 1.1;
    color: #11151B;
    margin: 10px 0 18px;
    letter-spacing: -1px;
}

.historia-intro p,
.historia-card p {
    font-size: 17px;
    line-height: 1.9;
    color: #475569;
}

.historia-side {
    background:
        radial-gradient(120% 100% at 12% -10%, color-mix(in srgb, var(--cor-secundaria) 24%, transparent), transparent 60%),
        linear-gradient(160deg,#242A30,#11151B);
    color: white;
    border-radius: 28px;
    padding: 36px 32px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.06),
        0 24px 55px rgba(0,0,0,.30);
}

.historia-side::after {
    content: "";
    position: absolute;
    width: 240px;
    height: 240px;
    right: -90px;
    bottom: -110px;
    border-radius: 50%;
    background: radial-gradient(circle, color-mix(in srgb, var(--cor-secundaria) 40%, transparent) 0%, transparent 72%);
    pointer-events: none;
}

.historia-side-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--cor-secundaria) 30%, transparent), rgba(255,255,255,.05));
    border: 1px solid color-mix(in srgb, var(--cor-secundaria) 50%, transparent);
    display: grid;
    place-items: center;
    font-size: 26px;
    color: var(--cor-secundaria);
    margin-bottom: 20px;
    box-shadow: 0 10px 26px color-mix(in srgb, var(--cor-secundaria) 35%, transparent);
    position: relative;
    z-index: 1;
}

.historia-side-kicker {
    display: block;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--cor-secundaria);
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.historia-side-name {
    color: #f8fafc;
    font-weight: 800;
    font-size: 19px;
    line-height: 1.35;
    display: block;
    position: relative;
    z-index: 1;
}

.historia-side p {
    position: relative;
    z-index: 1;
}

.historia-grid {
    display: grid;
    grid-template-columns: repeat(2,1fr);
    gap: 24px;
    margin-bottom: 35px;
}

.historia-card {
    background: white;
    border-radius: 28px;
    padding: 32px;
    box-shadow: 0 16px 45px rgba(0,0,0,.08);
    border: 1px solid #eef2f7;
    transition: .25s;
    position: relative;
    overflow: hidden;
}

.historia-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg,var(--cor-principal),var(--cor-secundaria));
}

.historia-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 24px 65px rgba(0,0,0,.12);
}

.historia-icon {
    width: 62px;
    height: 62px;
    border-radius: 18px;
    background: #eff6ff;
    display: grid;
    place-items: center;
    font-size: 30px;
    margin-bottom: 16px;
}

.historia-card h3 {
    color: #11151B;
    font-size: 28px;
    margin: 0 0 16px;
}

.timeline-section {
    background: linear-gradient(135deg,#242A30,#11151B);
    color: white;
    border-radius: 34px;
    padding: 42px;
    margin-bottom: 36px;
    box-shadow: 0 22px 60px rgba(0,0,0,.16);
}

.timeline-section h2 {
    color: white;
    font-size: clamp(34px,4vw,48px);
    margin: 12px 0 34px;
}

.timeline-wrapper {
    position: relative;
    padding-left: 34px;
}

.timeline-wrapper::before {
    content: "";
    position: absolute;
    left: 7px;
    top: 0;
    bottom: 0;
    width: 4px;
    border-radius: 999px;
    background: rgba(255,255,255,.18);
}

.timeline-item {
    position: relative;
    margin-bottom: 28px;
    padding: 24px;
    border-radius: 24px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
    backdrop-filter: blur(10px);
}

.timeline-item::before {
    content: "";
    position: absolute;
    left: -33px;
    top: 28px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--cor-secundaria);
    box-shadow: 0 0 0 5px rgba(212,170,0,.18);
}

.timeline-item h3 {
    margin: 0 0 10px;
    color: #F0D060;
    font-size: 24px;
}

.timeline-item p {
    margin: 0;
    color: #dbeafe;
    line-height: 1.8;
}

.historia-final {
    background:
        linear-gradient(90deg, rgba(17,21,28,.92), rgba(36,42,50,.74)),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
    border-radius: 34px;
    padding: 54px;
    color: white;
    display: flex;
    justify-content: space-between;
    gap: 30px;
    align-items: center;
    margin-bottom: 20px;
}

.historia-final h2 {
    margin: 0 0 12px;
    font-size: clamp(32px,4vw,46px);
}

.historia-final p {
    margin: 0;
    color: #dbeafe;
    line-height: 1.8;
}


/* HERÁLDICA INTEGRADA */
.historia-heraldica-divider{margin:46px 0 28px;display:flex;justify-content:center;text-align:center}
.historia-heraldica-divider span{display:inline-flex;gap:10px;background:linear-gradient(135deg,#242A30,#11151B);color:white;padding:14px 20px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:13px;box-shadow:0 18px 45px rgba(15,23,42,.16)}
.historia-heraldica{background:white;border:1px solid #e5e7eb;border-radius:34px;padding:34px;box-shadow:0 22px 60px rgba(15,23,42,.10);margin-bottom:36px}
.historia-heraldica-main{display:grid;grid-template-columns:340px 1fr;gap:32px;align-items:center}
.historia-heraldica-img{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:28px;padding:26px;text-align:center}
.historia-heraldica-img img{max-width:100%;max-height:330px;object-fit:contain;filter:drop-shadow(0 20px 25px rgba(15,23,42,.16))}
.historia-heraldica-content h2{color:#11151B;font-size:clamp(30px,4vw,44px);line-height:1.1;margin:0 0 16px;letter-spacing:-1px}
.historia-heraldica-content p{color:#64748b;line-height:1.85;font-size:17px;margin:0}
.historia-heraldica-source{display:inline-flex;margin-top:18px;background:#eef2ff;color:#242A30;padding:10px 13px;border-radius:999px;text-decoration:none;font-weight:900}
.historia-heraldica-elementos{margin-top:30px;padding-top:28px;border-top:1px solid #eef2f7}
.historia-heraldica-elementos h2{color:#11151B;margin:0 0 20px;font-size:34px}
.historia-heraldica-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.historia-heraldica-card{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:26px;padding:24px;transition:.22s ease}
.historia-heraldica-card:hover{transform:translateY(-4px);box-shadow:0 22px 50px rgba(15,23,42,.10)}
.historia-heraldica-icon{width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#242A30,#11151B);color:white;display:grid;place-items:center;font-size:30px;margin-bottom:15px}
.historia-heraldica-card-img{width:100%;height:190px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;background:linear-gradient(180deg,#fff,#f1f5f9);border-radius:18px;border:1px solid #eef2f7}
.historia-heraldica-card-img img{max-width:78%;max-height:82%;object-fit:contain;filter:drop-shadow(0 10px 18px rgba(15,23,42,.14))}
.historia-heraldica-img.multi{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px}
.historia-heraldica-img.multi img{max-width:140px;max-height:140px;object-fit:contain;filter:drop-shadow(0 10px 16px rgba(15,23,42,.14))}
.historia-heraldica-card h3{margin:0 0 8px;color:#11151B;font-size:23px}
.historia-heraldica-card p{color:#64748b;line-height:1.75;margin:0}

@media(max-width:1000px) {

    .historia-intro,
    .historia-grid {
        grid-template-columns: 1fr;
    }

    .historia-final {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media(max-width:700px) {

    .historia-main {
        margin-top: -45px;
    }

    .historia-intro,
    .timeline-section,
    .historia-final {
        padding: 26px;
        border-radius: 26px;
    }

    .historia-actions {
        flex-direction: column;
    }

    






.historia-actions{
        align-items:center;
    }

    .historia-btn{
        width:220px;
        justify-content:center;
    }











    .timeline-wrapper {
        padding-left: 26px;
    }

    .timeline-item::before {
        left: -25px;
    }
}


@media(max-width:1000px){
    .historia-heraldica-main,
    .historia-heraldica-grid{grid-template-columns:1fr}
}
@media(max-width:700px){
    .historia-heraldica{padding:26px;border-radius:26px}
    .historia-heraldica-divider{margin:34px 0 22px}
    .historia-heraldica-img img{max-height:250px}
}

</style>

<main class="historia-page">

<section class="historia-hero">
    <div class="container">
        <div class="historia-hero-content">

            <span class="historia-kicker">
                <?= htmlspecialchars($pagina['hero_kicker'] ?? 'História e Heráldica') ?>
            </span>

            <h1><?= htmlspecialchars($pagina['hero_titulo'] ?? 'História e Heráldica') ?></h1>

            <p><?= textoSeguro($pagina['hero_subtitulo'] ?? '') ?></p>

            <div class="historia-actions">

                <?php if (!empty($pagina['botao1_texto']) && !empty($pagina['botao1_link'])): ?>
                    <a href="<?= htmlspecialchars($pagina['botao1_link']) ?>" class="historia-btn primary">
                        <?= htmlspecialchars($pagina['botao1_texto']) ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($pagina['botao2_texto']) && !empty($pagina['botao2_link'])): ?>
                    <a href="<?= htmlspecialchars($pagina['botao2_link']) ?>" class="historia-btn secondary">
                        <?= htmlspecialchars($pagina['botao2_texto']) ?>
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </div>
</section>

<section class="container historia-main">

    <section class="historia-intro">

        <div>
            <span class="historia-kicker">Memória local</span>

            <h2><?= htmlspecialchars($pagina['intro_titulo'] ?? '') ?></h2>

            <p><?= textoSeguro($pagina['intro_texto'] ?? '') ?></p>
        </div>

        <aside class="historia-side">

            <div>
                <div class="historia-side-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <span class="historia-side-kicker">Identidade da freguesia</span>
                <span class="historia-side-name">Atalaia e Alto Estanqueiro-Jardia · Setúbal</span>
            </div>

            <p style="color:#dbeafe;line-height:1.8;margin-top:24px;">
                Uma freguesia marcada pela tradição,
                património, agricultura e identidade ribatejana.
            </p>

        </aside>

    </section>

    <section class="historia-grid">

        <article class="historia-card">

            <div class="historia-icon"><i class="bi bi-bank2"></i></div>

            <h3><?= htmlspecialchars($pagina['bloco1_titulo'] ?? '') ?></h3>

            <p><?= textoSeguro($pagina['bloco1_texto'] ?? '') ?></p>

        </article>

        <article class="historia-card">

            <div class="historia-icon"><i class="bi bi-flower1"></i></div>

            <h3><?= htmlspecialchars($pagina['bloco2_titulo'] ?? '') ?></h3>

            <p><?= textoSeguro($pagina['bloco2_texto'] ?? '') ?></p>

        </article>

    </section>

    <?php if (!empty($timeline)): ?>

    <section class="timeline-section">

        <span class="historia-kicker">Linha temporal</span>

        <h2><?= htmlspecialchars($pagina['timeline_titulo'] ?? 'Linha do tempo') ?></h2>

        <div class="timeline-wrapper">

            <?php foreach ($timeline as $item): ?>

                <article class="timeline-item">

                    <h3><?= htmlspecialchars($item['titulo']) ?></h3>

                    <p><?= htmlspecialchars($item['texto']) ?></p>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

    <?php endif; ?>























<div class="historia-heraldica-divider" id="heraldica">
    <span><i class="bi bi-shield-fill"></i> Heráldica da Freguesia</span>
</div>

<section class="historia-heraldica">
    <div class="historia-heraldica-main">
        <div class="historia-heraldica-img<?= !empty($brasoesIndividuais) ? ' multi' : '' ?>">
            <?php if (!empty($brasoesIndividuais)): ?>
                <?php foreach ($brasoesIndividuais as $brasao): ?>
                    <img src="/assets/img/heraldica/<?= htmlspecialchars($brasao['imagem']) ?>" alt="<?= htmlspecialchars($brasao['titulo']) ?>">
                <?php endforeach; ?>
            <?php else: ?>
                <img src="<?= $heraldicaImg ?>" alt="Brasão de Atalaia e Alto Estanqueiro-Jardia">
            <?php endif; ?>
        </div>

        <div class="historia-heraldica-content">
            <span class="historia-kicker">
                <?= htmlspecialchars($heraldica['hero_kicker'] ?? 'Heráldica') ?>
            </span>

            <h2><?= htmlspecialchars($heraldica['titulo'] ?? 'Brasão da Freguesia de Atalaia e Alto Estanqueiro-Jardia') ?></h2>

            <p><?= textoSeguro($heraldica['texto_intro'] ?? '') ?></p>
        </div>
    </div>

    <div class="historia-heraldica-elementos">
        <h2>Elementos Heráldicos</h2>

        <?php if(!empty($elementosHeraldica)): ?>
            <div class="historia-heraldica-grid">
                <?php foreach($elementosHeraldica as $e): ?>
                    <article class="historia-heraldica-card">
                        <?php if (!empty($e['imagem'])): ?>
                            <div class="historia-heraldica-card-img">
                                <img src="/assets/img/heraldica/<?= htmlspecialchars($e['imagem']) ?>" alt="<?= htmlspecialchars($e['titulo']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="historia-heraldica-icon">
                                <i class="bi <?= htmlspecialchars($e['icone'] ?: 'bi-shield') ?>"></i>
                            </div>
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($e['titulo']) ?></h3>
                        <p><?= textoSeguro($e['descricao']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Ainda não existem elementos heráldicos publicados.</p>
        <?php endif; ?>
    </div>
</section>






















    

    <section class="historia-final">

        <div>

            <h2>Conheça a história e a heráldica da freguesia</h2>

            <p>
                Descubra as origens, a memória e a identidade
                que moldaram a freguesia ao longo do tempo.
            </p>

        </div>

        <div class="historia-actions">

            <?php if (!empty($pagina['botao1_texto']) && !empty($pagina['botao1_link'])): ?>
                <a href="<?= htmlspecialchars($pagina['botao1_link']) ?>" class="historia-btn primary">
                    <?= htmlspecialchars($pagina['botao1_texto']) ?>
                </a>
            <?php endif; ?>

            <?php if (!empty($pagina['botao2_texto']) && !empty($pagina['botao2_link'])): ?>
                <a href="<?= htmlspecialchars($pagina['botao2_link']) ?>" class="historia-btn secondary">
                    <?= htmlspecialchars($pagina['botao2_texto']) ?>
                </a>
            <?php endif; ?>

        </div>

    </section>

</section>

</main>

<?php require_once "includes/footer.php"; ?>
