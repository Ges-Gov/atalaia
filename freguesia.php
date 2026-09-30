<?php
require_once __DIR__ . "/includes/db.php";

$pagina = $pdo->query("SELECT * FROM pagina_freguesia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$pagina) {
    $pagina = [
        'hero_kicker' => 'A Freguesia',
        'hero_titulo' => 'Atalaia e Alto Estanqueiro-Jardia',
        'hero_subtitulo' => 'Num monte sobranceiro ao estuário do Tejo, entre o Santuário da Atalaia e os campos do Alto Estanqueiro e da Jardia.',
        'hero_imagem' => '',
        'intro_titulo' => 'Uma freguesia do Montijo',
        'intro_texto' => 'A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia pertence ao concelho do Montijo, distrito de Setúbal. Tem 13,65 km² e 5379 habitantes (Censos 2021). Foi constituída pela Lei n.º 11-A/2013, de 28 de janeiro, que agregou as freguesias da Atalaia e do Alto Estanqueiro-Jardia.',
        'historia_titulo' => 'História e memória',
        'historia_texto' => 'A cerca de quatro quilómetros da sede do município, a Atalaia beneficiou desde sempre da proximidade da Estrada Real que ligava Lisboa a Badajoz, por Aldeia Galega. Já no início do século XVI, as populações locais e dos arredores vinham aqui em peregrinação.',
        'identidade_titulo' => 'Identidade',
        'identidade_texto' => 'O culto de Nossa Senhora da Atalaia, vivido por romeiros e festeiros, é o grande traço de identidade da freguesia.',
        'patrimonio_titulo' => 'Património',
        'patrimonio_texto' => 'A Igreja de Nossa Senhora da Atalaia e os seus três cruzeiros foram classificados em 2009 como Imóveis de Interesse Público. Junto à escadaria do Santuário fica o Museu Agrícola da Atalaia.',
        'localidades_titulo' => 'Localidades da freguesia',
        'localidades_texto' => "Atalaia|Sede da freguesia, junto ao Santuário de Nossa Senhora da Atalaia\nAlto Estanqueiro|Onde fica a dependência da Junta\nJardia|Lugar de tradição hortícola",
        'galeria_titulo' => 'A freguesia em imagens',
        'botao1_texto' => 'Ver pontos de interesse',
        'botao1_link' => '/pontos.php',
        'botao2_texto' => 'Explorar no mapa',
        'botao2_link' => '/mapa.php'
    ];
}

$heroImagem = !empty($pagina['hero_imagem'])
    ? "/assets/img/" . $pagina['hero_imagem']
    : "/assets/img/freguesia-1.jpg";

$localidades = [];
if (!empty($pagina['localidades_texto'])) {
    $linhas = preg_split('/\r\n|\r|\n/', trim($pagina['localidades_texto']));
    foreach ($linhas as $linha) {
        $partes = explode('|', $linha, 2);
        $localidades[] = [
            'nome' => trim($partes[0] ?? ''),
            'texto' => trim($partes[1] ?? '')
        ];
    }
}

$galeria = $pdo->query("
    SELECT *
    FROM homepage_galeria
    WHERE ativo = 1
    ORDER BY ordem ASC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

function textoSeguro($texto) {
    return nl2br(htmlspecialchars($texto ?? ''));
}
?>

<?php require_once "includes/header.php"; ?>

<style>
.freguesia-page {
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 34%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 55%, #f7f4ef 100%);
}

.freguesia-hero-premium {
    min-height: 650px;
    position: relative;
    display: flex;
    align-items: center;
    overflow: hidden;
    color: white;
    background:
        linear-gradient(90deg, rgba(17,21,28,.90), rgba(17,21,28,.44)),
        linear-gradient(0deg, rgba(0,0,0,.35), transparent),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.freguesia-hero-premium::after {
    content: "";
    position: absolute;
    width: 560px;
    height: 560px;
    border-radius: 50%;
    right: -180px;
    top: -220px;
    background: rgba(255,255,255,.055);
}

.freguesia-hero-premium::before {
    content: "";
    position: absolute;
    inset: auto 0 0 0;
    height: 160px;
    background: linear-gradient(0deg, #f8fafc, transparent);
    z-index: 1;
}

.freguesia-hero-inner {
    position: relative;
    z-index: 2;
    max-width: 820px;
    padding: 110px 0 150px;
}

.freguesia-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #D4AA00;
    background: rgba(212,170,0,.14);
    border: 1px solid rgba(212,170,0,.42);
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .9px;
    margin-bottom: 20px;
}

.freguesia-hero-inner h1 {
    font-size: clamp(46px, 7vw, 86px);
    line-height: .98;
    letter-spacing: -2px;
    margin: 0 0 22px;
}

.freguesia-hero-inner p {
    font-size: 21px;
    line-height: 1.8;
    color: #dbeafe;
    max-width: 760px;
    margin: 0 0 32px;
}

.freguesia-actions {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.freguesia-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 24px;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 900;
    transition: .25s ease;
}

.freguesia-btn.primary {
    background: var(--cor-secundaria);
    color: #11151B;
    box-shadow: 0 18px 45px rgba(0,0,0,.24);
}

.freguesia-btn.secondary {
    background: rgba(255,255,255,.14);
    color: white;
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(10px);
}

.freguesia-btn:hover {
    transform: translateY(-4px);
    filter: brightness(1.04);
}

.freguesia-main {
    position: relative;
    z-index: 5;
    margin-top: -70px;
}

.freguesia-intro-card {
    background: white;
    border-radius: 34px;
    padding: 42px;
    display: grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 34px;
    align-items: center;
    box-shadow: 0 24px 70px rgba(0,0,0,.12);
    border: 1px solid rgba(226,232,240,.9);
    margin-bottom: 28px;
}

.freguesia-intro-card h2,
.freguesia-section-title h2 {
    color: #11151B;
    font-size: clamp(32px, 4vw, 48px);
    line-height: 1.1;
    margin: 8px 0 18px;
    letter-spacing: -1px;
}

.freguesia-intro-card p,
.freguesia-text-card p {
    color: #475569;
    line-height: 1.9;
    font-size: 17px;
}

.freguesia-mini-panel {
    background: linear-gradient(135deg, #242A30, #11151B);
    color: white;
    border-radius: 28px;
    padding: 30px;
    min-height: 250px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.freguesia-mini-panel::after {
    content: "<?= htmlspecialchars(temaConfig('logo_iniciais', '')) ?>";
    position: absolute;
    right: -20px;
    bottom: -40px;
    font-size: 150px;
    font-weight: 900;
    color: rgba(255,255,255,.06);
}

.freguesia-mini-panel strong {
    display: block;
    font-size: 56px;
    color: var(--cor-secundaria);
    line-height: 1;
}

.freguesia-mini-panel span {
    color: #dbeafe;
    font-weight: 800;
}

.freguesia-content-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin: 30px 0;
}

.freguesia-text-card {
    background: white;
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 16px 45px rgba(0,0,0,.075);
    border: 1px solid #eef2f7;
    transition: .25s ease;
    position: relative;
    overflow: hidden;
}

.freguesia-text-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--cor-principal), var(--cor-secundaria));
}

.freguesia-text-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 26px 70px rgba(0,0,0,.12);
}

.freguesia-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    background: #eff6ff;
    display: grid;
    place-items: center;
    font-size: 28px;
    margin-bottom: 18px;
}

.freguesia-text-card h3 {
    color: #11151B;
    font-size: 24px;
    margin: 0 0 14px;
}

.localidades-section {
    background: linear-gradient(135deg, #242A30, #11151B);
    border-radius: 34px;
    padding: 42px;
    color: white;
    margin: 32px 0;
    box-shadow: 0 22px 60px rgba(0,0,0,.16);
}

.localidades-section .freguesia-kicker {
    background: rgba(255,255,255,.10);
    border-color: rgba(255,255,255,.18);
}

.localidades-section h2 {
    color: white;
    font-size: clamp(32px, 4vw, 46px);
    margin: 8px 0 22px;
}

.localidades-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
    margin-top: 24px;
}

.localidade-card {
    background: rgba(255,255,255,.11);
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 22px;
    padding: 22px;
    backdrop-filter: blur(10px);
    transition: .25s;
}

.localidade-card:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,.16);
}

.localidade-card h3 {
    margin: 0 0 10px;
    color: #F0D060;
    font-size: 22px;
}

.localidade-card p {
    margin: 0;
    color: #dbeafe;
    line-height: 1.7;
}

.freguesia-gallery {
    margin: 45px 0;
}

.freguesia-section-title {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: end;
    margin-bottom: 24px;
}

.freguesia-gallery-grid {
    display: grid;
    grid-template-columns: 1.3fr 1fr 1fr;
    grid-auto-rows: 240px;
    gap: 18px;
}

.freguesia-gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    box-shadow: 0 18px 45px rgba(0,0,0,.12);
    background: #e5e7eb;
}

.freguesia-gallery-item:first-child {
    grid-row: span 2;
}

.freguesia-gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: .45s ease;
}

.freguesia-gallery-item:hover img {
    transform: scale(1.07);
}

.freguesia-gallery-item span {
    position: absolute;
    left: 16px;
    bottom: 16px;
    right: 16px;
    background: rgba(17,21,28,.78);
    color: white;
    border-radius: 16px;
    padding: 12px 14px;
    font-weight: 900;
    backdrop-filter: blur(10px);
}

.freguesia-final-cta {
    background:
        linear-gradient(90deg, rgba(17,21,28,.94), rgba(36,42,50,.78)),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
    border-radius: 34px;
    padding: 54px;
    color: white;
    display: flex;
    justify-content: space-between;
    gap: 28px;
    align-items: center;
    margin: 36px 0 20px;
}

.freguesia-final-cta h2 {
    margin: 0 0 12px;
    font-size: clamp(30px, 4vw, 44px);
}

.freguesia-final-cta p {
    margin: 0;
    color: #dbeafe;
    line-height: 1.7;
}

@media (max-width: 1000px) {
    .freguesia-intro-card,
    .freguesia-content-grid {
        grid-template-columns: 1fr;
    }

    .freguesia-gallery-grid {
        grid-template-columns: 1fr;
        grid-auto-rows: 260px;
    }

    .freguesia-gallery-item:first-child {
        grid-row: auto;
    }

    .freguesia-final-cta,
    .freguesia-section-title {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 700px) {
    .freguesia-hero-premium {
        min-height: 560px;
    }

    .freguesia-hero-inner {
        padding: 80px 0 120px;
    }

    .freguesia-intro-card,
    .localidades-section,
    .freguesia-final-cta {
        padding: 26px;
        border-radius: 26px;
    }

    .freguesia-main {
        margin-top: -45px;
    }

    .freguesia-actions {
        flex-direction: column;
    }

    .freguesia-btn {
        justify-content: center;
    }







.freguesia-actions{
        align-items:center;
    }

    .freguesia-btn{
        width:220px;
        justify-content:center;
    }






}
</style>

<main class="freguesia-page">

    <section class="freguesia-hero-premium">
        <div class="container">
            <div class="freguesia-hero-inner">
                <span class="freguesia-kicker"><?= htmlspecialchars($pagina['hero_kicker'] ?? 'A Freguesia') ?></span>
                <h1><?= htmlspecialchars($pagina['hero_titulo'] ?? 'Atalaia e Alto Estanqueiro-Jardia') ?></h1>
                <p><?= textoSeguro($pagina['hero_subtitulo'] ?? '') ?></p>

                <div class="freguesia-actions">
                    <?php if (!empty($pagina['botao1_texto']) && !empty($pagina['botao1_link'])): ?>
                        <a href="<?= htmlspecialchars($pagina['botao1_link']) ?>" class="freguesia-btn primary">
                            <?= htmlspecialchars($pagina['botao1_texto']) ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($pagina['botao2_texto']) && !empty($pagina['botao2_link'])): ?>
                        <a href="<?= htmlspecialchars($pagina['botao2_link']) ?>" class="freguesia-btn secondary">
                            <?= htmlspecialchars($pagina['botao2_texto']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="container freguesia-main">

        <div class="freguesia-intro-card">
            <div>
                <span class="freguesia-kicker">Identidade local</span>
                <h2><?= htmlspecialchars($pagina['intro_titulo'] ?? '') ?></h2>
                <p><?= textoSeguro($pagina['intro_texto'] ?? '') ?></p>
            </div>

            <aside class="freguesia-mini-panel">
                <div>
                    <strong><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></strong>
                    <span>Freguesia de Atalaia e Alto Estanqueiro-Jardia · Setúbal</span>
                </div>
                <p style="color:#dbeafe;line-height:1.7;margin:25px 0 0;">
                    Um território onde a história, a paisagem e a comunidade se encontram.
                </p>
            </aside>
        </div>

        <div class="freguesia-content-grid">
            <article class="freguesia-text-card">
                <div class="freguesia-icon"><i class="bi bi-bank2"></i></div>
                <h3><?= htmlspecialchars($pagina['historia_titulo'] ?? '') ?></h3>
                <p><?= textoSeguro($pagina['historia_texto'] ?? '') ?></p>
            </article>

            <article class="freguesia-text-card">
                <div class="freguesia-icon"><i class="bi bi-flower1"></i></div>
                <h3><?= htmlspecialchars($pagina['identidade_titulo'] ?? '') ?></h3>
                <p><?= textoSeguro($pagina['identidade_texto'] ?? '') ?></p>
            </article>

            <article class="freguesia-text-card">
                <div class="freguesia-icon"><i class="bi bi-compass-fill"></i></div>
                <h3><?= htmlspecialchars($pagina['patrimonio_titulo'] ?? '') ?></h3>
                <p><?= textoSeguro($pagina['patrimonio_texto'] ?? '') ?></p>
            </article>
        </div>

        <?php if (!empty($localidades)): ?>
            <section class="localidades-section">
                <span class="freguesia-kicker">Território</span>
                <h2><?= htmlspecialchars($pagina['localidades_titulo'] ?? 'Localidades') ?></h2>

                <div class="localidades-grid">
                    <?php foreach ($localidades as $loc): ?>
                        <?php if (!empty($loc['nome'])): ?>
                            <article class="localidade-card">
                                <h3><?= htmlspecialchars($loc['nome']) ?></h3>
                                <p><?= htmlspecialchars($loc['texto']) ?></p>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($galeria)): ?>
            <section class="freguesia-gallery">
                <div class="freguesia-section-title">
                    <div>
                        <span class="freguesia-kicker">Galeria</span>
                        <h2><?= htmlspecialchars($pagina['galeria_titulo'] ?? 'A freguesia em imagens') ?></h2>
                    </div>
                </div>

                <div class="freguesia-gallery-grid">
                    <?php foreach ($galeria as $img): ?>
                        <article class="freguesia-gallery-item">
                            <img src="/assets/img/<?= htmlspecialchars($img['imagem']) ?>" alt="<?= htmlspecialchars($img['titulo']) ?>">
                            <span><?= htmlspecialchars($img['titulo']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="freguesia-final-cta">
            <div>
                <h2>Descubra a freguesia</h2>
                <p>Explore locais, serviços, património e informação útil da freguesia num só lugar.</p>
            </div>

            <div class="freguesia-actions">
                <?php if (!empty($pagina['botao1_texto']) && !empty($pagina['botao1_link'])): ?>
                    <a href="<?= htmlspecialchars($pagina['botao1_link']) ?>" class="freguesia-btn primary">
                        <?= htmlspecialchars($pagina['botao1_texto']) ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($pagina['botao2_texto']) && !empty($pagina['botao2_link'])): ?>
                    <a href="<?= htmlspecialchars($pagina['botao2_link']) ?>" class="freguesia-btn secondary">
                        <?= htmlspecialchars($pagina['botao2_texto']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>

    </section>

</main>

<?php require_once "includes/footer.php"; ?>
