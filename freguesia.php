<?php
require_once __DIR__ . "/includes/config.php";   // db + siteConfig()/nomeFreguesia()

$pagina = $pdo->query("SELECT * FROM pagina_freguesia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$pagina) {
    $pagina = [
        'hero_kicker' => 'A Freguesia',
        'hero_titulo' => nomeFreguesia(),
        'hero_subtitulo' => 'Conheça a freguesia ' . deFreguesia() . (freguesiaSedeConcelho() ? ', sede do concelho.' : ', no concelho ' . deConcelho() . '.'),
        'hero_imagem' => '',
        'intro_titulo' => 'Uma freguesia com identidade própria',
        'intro_texto' => '',
        'historia_titulo' => 'História e memória',
        'historia_texto' => '',
        'identidade_titulo' => 'Identidade',
        'identidade_texto' => '',
        'patrimonio_titulo' => 'Património e natureza',
        'patrimonio_texto' => '',
        'localidades_titulo' => 'Localidades da freguesia',
        'localidades_texto' => nomeFreguesia() . '|Sede da freguesia',
        'galeria_titulo' => nomeFreguesia() . ' em imagens',
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
    color: var(--ouro, #C8A02E);
    background: rgb(var(--ouro-rgb, 240 180 41) / .14);
    border: 1px solid rgb(var(--ouro-rgb, 240 180 41) / .42);
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
    color: var(--ouro-claro, #f8d77a);
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
    /* mosaico em colunas: cada foto na sua proporção, nunca cortada */
    columns: 3 260px;
    column-gap: 18px;
}

.freguesia-gallery-item {
    position: relative;
    margin: 0 0 18px;
    break-inside: avoid;
    overflow: hidden;
    border-radius: 28px;
    box-shadow: 0 18px 45px rgba(0,0,0,.12);
    background: #e5e7eb;
}


.freguesia-gallery-item img {
    width: 100%;
    height: auto;
    display: block;
    transition: .45s ease;
}

.freguesia-gallery-item:hover img {
    filter: brightness(1.06);
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
        columns: 1;
    }

    .freguesia-final-cta,
    .freguesia-section-title {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 700px) {
    .freguesia-hero-premium {
        min-height: 420px;
    }

    .freguesia-hero-inner {
        padding: 55px 0 90px;
    }

    .freguesia-hero-inner h1 {
        font-size: clamp(26px, 7vw, 38px);
        letter-spacing: -1px;
    }

    .freguesia-hero-inner p {
        font-size: 15px;
        line-height: 1.5;
        margin: 0 0 20px;
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
                <h1><?= htmlspecialchars($pagina['hero_titulo'] ?? nomeFreguesia()) ?></h1>
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
                <span class="freguesia-kicker"><?= te('freguesia.intro.kicker') ?></span>
                <h2><?= htmlspecialchars($pagina['intro_titulo'] ?? '') ?></h2>
                <p><?= textoSeguro($pagina['intro_texto'] ?? '') ?></p>
            </div>

            <aside class="freguesia-mini-panel">
                <div>
                    <strong><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></strong>
                    <span><?= te('freguesia.intro.nome') ?></span>
                </div>
                <?php
                /* Marcos: 4 datas da linha do tempo da página História (pagina_historia.timeline_texto,
                   linhas "ano — texto", editável no admin). Igual em todos os sites; sem linha do
                   tempo, mostra a frase de sempre. */
                $marcos = [];
                try {
                    $tl = (string)$pdo->query("SELECT timeline_texto FROM pagina_historia ORDER BY id LIMIT 1")->fetchColumn();
                    foreach (preg_split('/\R/', trim($tl)) as $ln) {
                        if (preg_match('/^\s*([\d\-–]{3,11})\s*[—–-]\s*(.+)$/u', $ln, $mm)) $marcos[] = [$mm[1], $mm[2]];
                    }
                } catch (Exception $e) {}
                if (count($marcos) > 4) $marcos = [$marcos[0], $marcos[1], $marcos[count($marcos) - 2], $marcos[count($marcos) - 1]];
                $curto = function ($t) { $t = preg_replace('/\s*\(.*?\)/u', '', $t); return mb_strlen($t) > 38 ? mb_substr($t, 0, mb_strrpos(mb_substr($t, 0, 38), ' ')) . '…' : $t; };
                ?>
                <?php if ($marcos): ?>
                    <ul class="mem-marcos" aria-label="Datas marcantes">
                        <?php foreach ($marcos as [$ano, $txt]): ?>
                            <li><b><?= htmlspecialchars($ano) ?></b><span><?= htmlspecialchars($curto($txt)) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="mem-marcos__mais" href="/historia.php"><?= te('freguesia.intro.link_historia') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <?php else: ?>
                    <p style="color:#dbeafe;line-height:1.7;margin:25px 0 0;"><?= te('freguesia.intro.frase') ?></p>
                <?php endif; ?>
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

        <?php require_once __DIR__ . "/includes/memoria.php"; mostrarMemoriaFreguesia($pdo); ?>

        <?php if (!empty($localidades)): ?>
            <section class="localidades-section">
                <span class="freguesia-kicker"><?= te('freguesia.territorio.kicker') ?></span>
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
                        <span class="freguesia-kicker"><?= te('freguesia.galeria.kicker') ?></span>
                        <h2><?= htmlspecialchars($pagina['galeria_titulo'] ?? nomeFreguesia() . ' em imagens') ?></h2>
                    </div>
                </div>

                <div class="freguesia-gallery-grid" data-galeria-lightbox="<?= htmlspecialchars($pagina['galeria_titulo'] ?? nomeFreguesia() . ' em imagens') ?>">
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
                <h2><?= te('freguesia.cta.titulo') ?></h2>
                <p><?= te('freguesia.cta.texto') ?></p>
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
