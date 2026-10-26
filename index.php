<?php require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/membros.php";
require_once __DIR__ . "/includes/galeria.php";
require_once __DIR__ . "/includes/calendario.php";
require_once __DIR__ . "/includes/mapa.php";
require_once __DIR__ . "/includes/video.php";
require_once __DIR__ . "/includes/cartoes.php"; $centroMapa = centroFreguesia(); ?>

<?php
$homepageDestaque = null;

try {
    $stmtDestaque = $pdo->query("SELECT * FROM homepage_destaque WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
    $homepageDestaque = $stmtDestaque->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $homepageDestaque = null;
}

$tipoDestaqueHomepage = $homepageDestaque['tipo'] ?? 'slider';

function youtubeEmbedHome($url) {
    if (empty($url)) {
        return '';
    }

    if (strpos($url, 'youtube-nocookie.com/embed/') !== false) {
        return $url;
    }

    if (strpos($url, 'youtube.com/embed/') !== false) {
        return str_replace('youtube.com/embed/', 'youtube-nocookie.com/embed/', $url);
    }

    if (preg_match('/youtu\.be\/([^?&]+)/', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&mute=1&loop=1&playlist=' . $m[1] . '&controls=0&showinfo=0&rel=0';
    }

    if (preg_match('/v=([^?&]+)/', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&mute=1&loop=1&playlist=' . $m[1] . '&controls=0&showinfo=0&rel=0';
    }

    return $url;
}

try {
    $slides = $pdo->query("SELECT * FROM slides_homepage WHERE ativo = 1 ORDER BY COALESCE(ordem, 999999), id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {   // migração 038 ainda não corrida
    $slides = $pdo->query("SELECT * FROM slides_homepage WHERE ativo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Link do botão de um slide, validado: links externos (http/https) passam; links internos
 * só passam se a página existir no site (ex.: "freguesia.php", "/ponto.php?id=22"). Qualquer
 * outra coisa ("escola", vazio, página inexistente) devolve '' — o slide mostra então o botão
 * por omissão em vez de levar a um erro 404.
 */
function linkSlideValido(string $link): string
{
    $link = trim($link);
    if ($link === '') return '';
    if (preg_match('#^https?://#i', $link)) return filter_var($link, FILTER_VALIDATE_URL) ? $link : '';
    $caminho = parse_url('/' . ltrim($link, '/'), PHP_URL_PATH) ?: '';
    if (!preg_match('#^/[\w\-/]+\.php$#', $caminho) || !is_file(__DIR__ . $caminho)) return '';
    return '/' . ltrim($link, '/');
}

$home = $pdo->query("SELECT * FROM homepage_config ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$home) {
    $home = [
        'mostrar_hero' => 1,
        'mostrar_boasvindas' => 1,
        'mostrar_pontos' => 1,
        'mostrar_mapa' => 1,
        'mostrar_noticias_eventos' => 1,
        'mostrar_servicos' => 1,
        'mostrar_cta_final' => 1,
        'mostrar_apoios' => 1,
        'hero_titulo' => 'Bem-vindo a ' . nomeFreguesia(),
        'hero_subtitulo' => 'Informação, serviços, documentos e património num único espaço digital.',
        'boasvindas_titulo' => 'Bem-vindo a ' . nomeFreguesia(),
        'boasvindas_texto' => 'Uma freguesia com identidade, património, comunidade e futuro digital.',
        'cta_titulo' => 'Precisa de falar com a Junta?',
        'cta_texto' => 'Aceda aos serviços digitais, faça pedidos, consulte documentos ou entre em contacto connosco.',
        'cta_botao_texto' => 'Aceder à Junta Virtual',
        'cta_botao_link' => 'minha-area.php'
    ];
}

// Pontos em destaque primeiro (o 1.º ocupa o quadro grande — migração 039), depois os mais recentes
try {
    $pontos = $pdo->query("SELECT * FROM pontos_interesse ORDER BY destaque DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {   // migração 039 ainda não corrida
    $pontos = $pdo->query("SELECT * FROM pontos_interesse ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// Notícias para a grelha editorial: as 24 mais recentes vêm todas para a página e os filtros
// por categoria (app.js) escolhem quais mostrar, sem recarregar. Sem JS mostram-se as 4 primeiras.
$noticias = $pdo->query("
    SELECT id, titulo, descricao, imagem, data, categoria, imagem_foco_x, imagem_foco_y
    FROM noticias
    ORDER BY destaque DESC, data DESC   -- a notícia em destaque (admin) vai para o cartão grande
    LIMIT 24
")->fetchAll(PDO::FETCH_ASSOC);

// Categorias para os filtros: só as que têm notícias recentes, das mais usadas para as menos
$categoriasNoticias = [];
foreach ($noticias as $n) {
    $c = trim((string)($n['categoria'] ?? ''));
    if ($c !== '') $categoriasNoticias[$c] = ($categoriasNoticias[$c] ?? 0) + 1;
}
arsort($categoriasNoticias);
$categoriasNoticias = array_slice(array_keys($categoriasNoticias), 0, 5);

// Lista da agenda: como a página Agenda, mostra o mês corrente desde o dia 1 (os já
// realizados ficam marcados) e os seguintes. 13/10/2026: antes só mostrava os futuros.
$eventos = $pdo->query("
    SELECT * FROM eventos
    WHERE data_evento >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ORDER BY data_evento ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$eventoSemana = $pdo->query("
    SELECT * FROM eventos
    WHERE data_evento >= CURDATE()
    ORDER BY data_evento ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Galeria da homepage: mostra os ÁLBUNS (ver migrations/005_galeria_albuns.sql).
// Antes lia da homepage_galeria — uma lista plana de imagens soltas — e por isso a
// secção não aparecia nos sites que já usam álbuns: essa tabela estava vazia.
// Agora os álbuns dos pontos de interesse e dos eventos, que são criados
// automaticamente, aparecem aqui sem ser preciso fazer nada.
$galeriaHome = $pdo->query("
    SELECT a.*, COUNT(gi.id) AS n_imagens
    FROM galeria_albuns a
    JOIN galeria_imagens gi ON gi.album_id = a.id AND gi.ativo = 1
    WHERE a.ativo = 1
    GROUP BY a.id
    ORDER BY a.ordem ASC, a.criado_em DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$presidenteHome = $pdo->query("
    SELECT *
    FROM executivo_membros
    WHERE ativo = 1
    AND cargo LIKE '%Presidente%'
    ORDER BY ordem ASC, id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
// sem nenhum «Presidente» no executivo (ex.: o site de demonstração, com um Diretor Executivo), usa a pessoa
// cujo nome está na mensagem da página inicial — a secção deixa de desaparecer sem aviso
if (!$presidenteHome && trim((string)($home['presidente_nome'] ?? '')) !== '') {
    $st = $pdo->prepare("SELECT * FROM executivo_membros WHERE ativo = 1 AND nome = ? ORDER BY ordem ASC, id DESC LIMIT 1");
    $st->execute([trim((string)$home['presidente_nome'])]);
    $presidenteHome = $st->fetch(PDO::FETCH_ASSOC);
}

$associacoesCount = $pdo->query("SELECT COUNT(*) FROM associacoes")->fetchColumn();
$pontosCount = $pdo->query("SELECT COUNT(*) FROM pontos_interesse")->fetchColumn();
$comercioCount = $pdo->query("SELECT COUNT(*) FROM comercio_local")->fetchColumn();
$documentosCount = $pdo->query("SELECT COUNT(*) FROM documentos WHERE ativo = 1")->fetchColumn();
?>

<style>
/* HOMEPAGE DESTAQUE VÍDEO */
.home-video-hero{
    position:relative;
    min-height:90vh;
    background:#11151B;
    overflow:hidden;
    display:flex;
    align-items:flex-end;
}
.home-video-hero video,
.home-video-hero iframe{
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    object-fit:cover;
    border:0;
    z-index:1;
}
.home-video-hero::after{
    content:"";
    position:absolute;
    inset:0;
    background:
        linear-gradient(90deg, rgba(17,21,28,.84), rgba(17,21,28,.28)),
        linear-gradient(0deg, rgba(0,0,0,.42), transparent 56%);
    z-index:2;
}
.home-video-content{
    position:relative;
    z-index:3;
    width:100%;
    padding:0 7% 120px;
    color:white;
    max-width:980px;
}
.home-video-content span{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgb(var(--ouro-rgb, 240 180 41) / .17);
    border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .42);
    color:var(--ouro-claro, #f8d77a);
    padding:9px 15px;
    border-radius:999px;
    font-weight:900;
    margin-bottom:18px;
    letter-spacing:.4px;
}
.home-video-content h1{
    font-size:clamp(40px,6vw,74px);
    line-height:1.02;
    margin:0 0 18px;
    letter-spacing:-1.5px;
}
.home-video-content p{
    font-size:21px;
    line-height:1.7;
    color:#dbeafe;
    max-width:720px;
    margin:0 0 30px;
}
.home-video-actions{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
}
.home-video-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:var(--cor-secundaria);
    color:#11151B;
    text-decoration:none;
    padding:12px 18px;
    border-radius:16px;
    font-weight:900;
    box-shadow:0 18px 45px rgba(0,0,0,.25);
    transition:.25s ease;
    font-size:15px;
}
.home-video-btn.secondary{
    background:rgba(255,255,255,.14);
    color:white;
    border:1px solid rgba(255,255,255,.28);
    backdrop-filter:blur(12px);
}
.home-video-btn:hover{
    transform:translateY(-4px);
    filter:brightness(1.05);
}
.home-video-muted{
    position:absolute;
    right:32px;
    top:32px;
    z-index:4;
    background:rgba(255,255,255,.14);
    color:white;
    border:1px solid rgba(255,255,255,.28);
    backdrop-filter:blur(12px);
    padding:10px 14px;
    border-radius:999px;
    font-weight:800;
    font-size:13px;
}
@media(max-width:850px){
    .home-video-hero{
        min-height:78vh;
    }
    .home-video-content{
        padding:0 24px 92px;
    }
    .home-video-content p{
        font-size:17px;
    }
    .home-video-muted{
        display:none;
    }
    .home-video-actions{
        flex-direction:column;
    }
    .home-video-btn{
        justify-content:center;
    }

.home-video-actions{
    align-items:center;
}

.home-video-btn{
    width:220px;
    justify-content:center;
}

}
</style>

<?php if (!empty($home['mostrar_hero'])): ?>

<?php if ($tipoDestaqueHomepage === 'video' && !empty($homepageDestaque)): ?>
    <?php
        $videoFicheiro = $homepageDestaque['video_ficheiro'] ?? '';
        $videoUrl = $homepageDestaque['video_url'] ?? '';
        $youtubeEmbed = youtubeEmbedHome($videoUrl);
        $tituloVideo = $homepageDestaque['titulo'] ?: ($home['hero_titulo'] ?? nomeFreguesia());
        $subtituloVideo = $homepageDestaque['subtitulo'] ?: ($home['hero_subtitulo'] ?? '');
        $botaoTexto = $homepageDestaque['botao_texto'] ?: 'Saber mais';
        $botaoLink = $homepageDestaque['botao_link'] ?: '/freguesia.php';
    ?>

    <section class="home-video-hero">
        <?php if (!empty($videoFicheiro)): ?>
            <div class="home-video-fallback" style="position:absolute;inset:0;z-index:1;background:var(--cor-principal) url('/assets/img/freguesia-1.jpg') center/cover;"></div>
            <video autoplay muted loop playsinline preload="auto" poster="/assets/img/freguesia-1.jpg" style="z-index:2;">
                <source src="/<?= htmlspecialchars($videoFicheiro) ?>" type="video/mp4">
            </video>
        <?php elseif (!empty($youtubeEmbed)): ?>
            <div class="home-video-fallback" style="position:absolute;inset:0;z-index:1;background:var(--cor-principal);"></div>
            <iframe
                data-cookie-src="<?= htmlspecialchars($youtubeEmbed) ?>"
                allow="autoplay; encrypted-media"
                allowfullscreen
                title="<?= htmlspecialchars($tituloVideo) ?>"
            ></iframe>
        <?php else: ?>
            <div class="hero-slide active" style="background:var(--cor-principal);"></div>
        <?php endif; ?>

        <div class="home-video-content">
            <span><?= te('inicio.topo.video_etiqueta') ?></span>
            <h1><?= htmlspecialchars($tituloVideo) ?></h1>
            <p><?= htmlspecialchars($subtituloVideo) ?></p>

            <div class="home-video-actions">
                <a href="<?= htmlspecialchars($botaoLink) ?>" class="home-video-btn">
                    <?= htmlspecialchars($botaoTexto) ?>
                </a>

                <a href="/mapa.php" class="home-video-btn secondary">
                    <?= te('inicio.topo.video_botao2') ?>
                </a>
            </div>

            <?php if (!empty($youtubeEmbed)): ?>
            <button type="button" class="rm-needs-consent home-video-consent" style="display:none;margin-top:16px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer;" onclick="window.rmAcceptCookies && window.rmAcceptCookies()">
                <i class="bi bi-play-circle"></i> <?= te('inicio.topo.video_cookies') ?>
            </button>
            <?php endif; ?>
        </div>

        <div class="home-video-muted"><?= te('inicio.topo.video_sem_som') ?></div>
    </section>

<?php else: ?>

<section class="hero-slider" id="heroSlider">
    <?php if (!empty($slides)): ?>
        <?php foreach ($slides as $i => $slide):
            /* Slide de vídeo (12/10/2026): YouTube sem som por trás do texto; «Ver com som» abre o leitor
               completo (com escolha de qualidade). Só carrega depois de aceites os cookies. */
            $ytId = ($slide['tipo'] ?? 'imagem') === 'video' ? youtubeId($slide['video_url'] ?? '') : '';
            $fundoSlide = !empty($slide['imagem']) ? '/assets/img/' . $slide['imagem'] : ($ytId ? youtubeMiniatura($ytId) : '');
        ?>
            <div
                class="hero-slide <?= $i === 0 ? 'active' : '' ?><?= $ytId ? ' hero-slide--video' . (($slide['video_ajuste'] ?? '') === 'ajustar' ? ' hero-slide--ajustar' : '') : '' ?>"
                style="background-image: url('<?= htmlspecialchars($fundoSlide) ?>');<?= $ytId ? ' --video-formato: ' . max(1, (float)($slide['video_formato'] ?? 1.7778)) . ';' : '' ?>"
                <?php if ($ytId): ?>data-video-leitor="<?= htmlspecialchars(youtubeEmbedLeitor($ytId)) ?>"<?php endif; ?>
            >
                <?php if ($ytId): ?>
                    <div class="hero-video" aria-hidden="true">
                        <iframe data-cookie-src="<?= htmlspecialchars(youtubeEmbedFundo($ytId)) ?>" title="<?= htmlspecialchars($slide['titulo']) ?>" allow="autoplay; encrypted-media; picture-in-picture" tabindex="-1"></iframe>
                    </div>
                <?php endif; ?>
                <div class="hero-overlay">
                    <span><?= te('inicio.topo.etiqueta') ?></span>
                    <?php if ($i === 0): ?>
                        <h1><?= htmlspecialchars($slide['titulo'] ?? '') ?></h1>
                    <?php else: ?>
                        <h2><?= htmlspecialchars($slide['titulo'] ?? '') ?></h2>
                    <?php endif; ?>
                    <p><?= htmlspecialchars($slide['subtitulo'] ?? '') ?></p>

                    <div class="hero-actions">
                        <?php $linkSlide = linkSlideValido($slide['link_destino'] ?? ''); ?>
                        <?php if ($linkSlide !== ''): ?>
                            <a href="<?= htmlspecialchars($linkSlide) ?>" class="hero-btn"><?= te('inicio.topo.botao_slide') ?></a>
                        <?php else: ?>
                            <a href="/freguesia.php" class="hero-btn"><?= te('inicio.topo.botao_sem_link') ?></a>
                        <?php endif; ?>

                        <?php if ($ytId): ?>
                            <button type="button" class="hero-btn hero-btn-outline hero-btn-video" data-video-abrir><i class="bi bi-play-fill" aria-hidden="true"></i> <?= te('inicio.topo.video_ver') ?></button>
                        <?php else: ?>
                            <a href="/mapa.php" class="hero-btn hero-btn-outline"><?= te('inicio.topo.botao_mapa') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <button class="slider-arrow prev" id="prevSlide" aria-label="Slide anterior">‹</button>
        <button class="slider-arrow next" id="nextSlide" aria-label="Slide seguinte">›</button>

        <?php if (array_filter($slides, fn($s) => ($s['tipo'] ?? '') === 'video')): ?>
        <dialog class="hero-video-modal" id="heroVideoModal" aria-labelledby="heroVideoTitulo">
            <div class="hero-video-modal__topo">
                <h2 id="heroVideoTitulo" class="hero-video-modal__titulo"></h2>
                <button type="button" class="hero-video-modal__fechar" data-video-fechar aria-label="Fechar o vídeo"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="hero-video-modal__quadro"></div>
            <div class="hero-video-modal__consentir" hidden>
                <p><?= te('inicio.topo.video_consentir') ?></p>
                <button type="button" class="hero-btn" data-video-aceitar><?= te('inicio.topo.video_cookies') ?></button>
            </div>
            <p class="hero-video-modal__nota"><i class="bi bi-gear" aria-hidden="true"></i> <?= te('inicio.topo.video_qualidade') ?></p>
        </dialog>
        <?php endif; ?>

        <div class="slider-dots" id="sliderDots">
            <?php foreach ($slides as $i => $slide): ?>
                <button class="<?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="Ir para o slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="hero-slide active" style="background:var(--cor-principal);">
            <div class="hero-overlay">
                <span><?= te('inicio.topo.etiqueta') ?></span>
                <h1><?= htmlspecialchars($home['hero_titulo']) ?></h1>
                <p><?= htmlspecialchars($home['hero_subtitulo']) ?></p>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php endif; ?>

<?php endif; ?>

<?php /* Acesso rápido (fase 1 do portal, 09/10/2026): barra de pesquisa "Spotlight" + 6 atalhos
   em vidro sobrepostos à base do hero (5 desde 12/10/2026: saiu o Canal de Denúncias, a pedido). Os atalhos levam só a serviços que existem e estão
   públicos — o Balcão Virtual (atestados, marcações) está oculto e por isso não entra. */ ?>
<section class="quick-access-section" data-spy="nao" aria-label="Acesso rápido">
    <div class="container">
        <button type="button" class="hero-pesquisa js-spotlight" aria-haspopup="dialog" aria-expanded="false" aria-keyshortcuts="Control+K">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="hero-pesquisa__texto"><?= te('inicio.pesquisa.texto') ?></span>
            <span class="hero-pesquisa__exemplos" aria-hidden="true"><?= te('inicio.pesquisa.exemplos') ?></span>
            <kbd class="hero-pesquisa__atalho" data-atalho-pesquisa aria-hidden="true">Ctrl K</kbd>
        </button>

        <?php /* Atalhos (12/10/2026): editáveis em Página Inicial › Cartões e atalhos (cartoes_site) */ ?>
        <div class="quick-access-grid quick-access-grid--6 qa-entrada" id="qaGrelha" style="--n: <?= max(1, count($cartoesAtalhos = cartoesSite('atalhos'))) ?>">
            <?php foreach ($cartoesAtalhos as $iAtalho => $c):
                /* ícone de traço animado (includes/cartoes.php); uma ilustração posta no admin tem prioridade */
                $svgAtalho = empty($c['imagem']) ? atalhoIconeSvg((string)$c['icone']) : null; ?>
            <a href="<?= htmlspecialchars($c['link'] ?: '#') ?>" class="quick-card<?= !empty($c['imagem']) ? ' quick-card--ilustrado' : ($svgAtalho ? ' quick-card--svg' : '') ?>" style="--i: <?= (int)$iAtalho ?>">
                <?php if (!empty($c['imagem'])): ?>
                <img class="quick-card__ilustracao" src="/assets/img/<?= htmlspecialchars($c['imagem']) ?>" alt="" width="256" height="256" decoding="async">
                <?php elseif ($svgAtalho): ?>
                <span class="qa-ico"><?= $svgAtalho ?></span>
                <?php else: ?>
                <span><i class="bi <?= htmlspecialchars($c['icone'] ?: 'bi-arrow-right') ?>" aria-hidden="true"></i></span>
                <?php endif; ?>
                <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                <?php if (!empty($c['texto'])): ?><small><?= htmlspecialchars($c['texto']) ?></small><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<script>/* a entrada dos ícones dos atalhos corre uma vez */setTimeout(function(){var g=document.getElementById("qaGrelha");if(g)g.classList.remove("qa-entrada");},2600);</script>

<?php /* «Hoje na freguesia» (15/10/2026): tempo, atendimento, agenda, farmácia de serviço e pedidos resolvidos —
         muda todos os dias. A farmácia de serviço, que tinha bloco próprio aqui, passou para dentro dele. */
require_once __DIR__ . "/includes/hoje.php";
blocoHoje($pdo); ?>

<?php if (!empty($home['mostrar_noticias_eventos']) && !empty($noticias)): ?>
<?php /* Notícias em grelha editorial: destaque grande + 3 laterais, filtros sem recarregar (app.js) */ ?>
<section class="section editorial" data-editorial data-spy="Notícias">
    <div class="container">
        <div class="section-heading editorial__cabecalho">
            <div>
                <span class="section-kicker"><?= te('inicio.noticias.kicker') ?></span>
                <h2><?= te('inicio.noticias.titulo') ?></h2>
            </div>
            <a href="/noticias.php" class="link-premium"><?= te('inicio.noticias.link') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if (count($categoriasNoticias) > 1): ?>
        <div class="editorial__filtros" role="group" aria-label="Filtrar notícias por categoria">
            <button type="button" class="editorial__filtro" data-filtro-noticias="*" aria-pressed="true">Todas</button>
            <?php foreach ($categoriasNoticias as $cat): ?>
                <button type="button" class="editorial__filtro" data-filtro-noticias="<?= htmlspecialchars($cat) ?>" aria-pressed="false"><?= htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="sr-only" aria-live="polite" data-editorial-estado></p>

        <div class="editorial__grelha">
            <?php foreach ($noticias as $i => $n): ?>
                <?php $catN = trim((string)($n['categoria'] ?? '')) ?: 'Notícia'; ?>
                <article class="editorial__item<?= $i === 0 ? ' editorial__item--destaque' : '' ?>" data-categoria="<?= htmlspecialchars($catN) ?>"<?= $i >= 4 ? ' hidden' : '' ?>>
                    <a class="editorial__link" href="/noticia.php?id=<?= (int)$n['id'] ?>">
                        <div class="editorial__media">
                            <?php if (!empty($n['imagem'])): ?>
                                <img src="<?= htmlspecialchars(miniaturaImagem('/assets/img/' . $n['imagem'], 900)) ?>" alt="" loading="lazy"
                                     style="object-position:<?= (int)($n['imagem_foco_x'] ?? 50) ?>% <?= (int)($n['imagem_foco_y'] ?? 50) ?>%">
                            <?php else: ?>
                                <div class="editorial__sem-img" aria-hidden="true"><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="editorial__texto">
                            <span class="editorial__categoria"><?= htmlspecialchars($catN) ?></span>
                            <h3><?= htmlspecialchars($n['titulo']) ?></h3>
                            <time datetime="<?= date('Y-m-d', strtotime($n['data'])) ?>"><i class="bi bi-calendar3" aria-hidden="true"></i> <?= date('d/m/Y', strtotime($n['data'])) ?></time>
                            <p><?= htmlspecialchars(mb_substr(trim(strip_tags($n['descricao'] ?? '')), 0, 170)) ?>…</p>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
            <p class="editorial__vazio" data-editorial-vazio hidden><?= te('inicio.noticias.vazio') ?> <a href="/noticias.php">Ver todas as notícias</a></p>
        </div>
    </div>
</section>
<?php endif; ?>

<?php /* Mensagem institucional (boas-vindas) movida para baixo das notícias/eventos */ ?>

<?php /* Mensagem do Presidente movida para depois das Notícias e Eventos */ ?>

<?php if (!empty($home['mostrar_noticias_eventos'])): ?>
<section class="section home-news-events-premium" data-spy="Agenda">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="section-kicker"><?= te('inicio.agenda.kicker') ?></span>
                <h2><?= te('inicio.agenda.titulo') ?></h2>
            </div>

            <div class="news-events-actions">
                <a href="/eventos.php" class="link-premium"><?= te('inicio.agenda.link') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="premium-news-events-grid">

            <?php
            /* «Evento da semana» (15/10/2026, a pedido): painel claro com etiquetas de contorno e título
               grande na cor da marca; a fotografia do evento ocupa o fundo do cartão, separada por uma
               onda, com a marca da Junta em baixo à esquerda. Sem foto → fotografia da freguesia. */
            require_once __DIR__ . "/includes/calendario.php";
            $fotoEvento = !empty($eventoSemana['imagem']) ? $eventoSemana['imagem'] : 'freguesia-1.jpg';
            // sem foto do evento nem freguesia-1.jpg (nem todos os sites a têm): a do 1.º slide com imagem
            if (empty($eventoSemana['imagem']) && !is_file(__DIR__ . '/assets/img/freguesia-1.jpg')) {
                $fotoEvento = '';
                foreach ($slides ?? [] as $s) if (!empty($s['imagem']) && is_file(__DIR__ . '/assets/img/' . $s['imagem'])) { $fotoEvento = $s['imagem']; break; }
            }
            $focoEvento = !empty($eventoSemana['imagem']) ? (int)($eventoSemana['imagem_foco_x'] ?? 50) . '% ' . (int)($eventoSemana['imagem_foco_y'] ?? 50) . '%' : '50% 50%';
            $logoJunta = siteConfig('logo', '');
            ?>
            <article class="evento-destaque">
                <div class="evento-destaque__topo">
                    <div class="evento-destaque__etiquetas">
                        <span class="evento-destaque__etiqueta evento-destaque__etiqueta--icone" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                        <span class="evento-destaque__etiqueta"><?= te('inicio.agenda.destaque') ?></span>
                    </div>

                    <?php if (!empty($eventoSemana)): ?>
                    <h3 class="evento-destaque__titulo"><a href="/evento.php?id=<?= (int)$eventoSemana['id'] ?>"><?= htmlspecialchars($eventoSemana['titulo']) ?></a></h3>
                    <div class="evento-destaque__meta">
                        <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= date(eventoDiaInteiro($eventoSemana) ? 'd/m/Y' : 'd/m/Y H:i', strtotime($eventoSemana['data_evento'])) ?></span>
                        <span class="evento-countdown" data-evento="<?= htmlspecialchars($eventoSemana['data_evento']) ?>">A calcular...</span>
                    </div>
                    <?php if (trim((string)($eventoSemana['descricao'] ?? '')) !== ''): ?>
                    <p class="evento-destaque__texto"><?= htmlspecialchars(mb_substr($eventoSemana['descricao'], 0, 160)) ?>...</p>
                    <?php endif; ?>
                    <?= botoesCalendario($eventoSemana) ?>
                    <?php else: ?>
                    <h3 class="evento-destaque__titulo"><?= te('inicio.agenda.vazio_titulo') ?></h3>
                    <p class="evento-destaque__texto"><?= te('inicio.agenda.vazio_texto') ?></p>
                    <?php endif; ?>
                </div>

                <div class="evento-destaque__foto">
                    <?php if ($fotoEvento !== ''): ?>
                    <img src="<?= htmlspecialchars(miniaturaImagem('/assets/img/' . $fotoEvento, 1100)) ?>" alt="<?= htmlspecialchars($eventoSemana['titulo'] ?? '') ?>" style="object-position:<?= $focoEvento ?>" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <svg class="evento-destaque__onda" viewBox="0 0 600 70" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0H600V6C520 44 380 70 220 62 130 58 60 48 0 40Z"/></svg>
                    <div class="evento-destaque__rodape">
                        <div class="evento-destaque__marca">
                            <?php if ($logoJunta !== ''): ?><img src="/assets/img/<?= htmlspecialchars($logoJunta) ?>" alt="" width="44" height="44"><?php endif; ?>
                            <span><strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong><?php if (nomeConcelho() !== ''): ?><small><?= htmlspecialchars(nomeConcelho()) ?></small><?php endif; ?></span>
                        </div>
                        <?php if (!empty($eventoSemana)): ?>
                        <a href="/evento.php?id=<?= (int)$eventoSemana['id'] ?>" class="evento-destaque__botao"><?= te('inicio.agenda.botao_evento') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <?php else: ?>
                        <a href="/eventos.php" class="evento-destaque__botao"><?= te('inicio.agenda.vazio_botao') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <div class="news-events-lists">

                <?php /* As "Últimas Notícias" passaram para a secção editorial, logo acima */ ?>
                <div class="premium-list-block">
                    <div class="premium-list-head">
                        <span><i class="bi bi-calendar-event-fill"></i></span>
                        <h3><?= te('inicio.agenda.proximos') ?></h3>
                    </div>

                    <?php if (!empty($eventos)): ?>
                        <div class="premium-horizontal-list">
                            <?php foreach ($eventos as $e): ?>
                                <?php $realizado = strtotime($e['data_evento']) < strtotime('today'); ?>
                                <a href="/evento.php?id=<?= (int)$e['id'] ?>" class="premium-event-card<?= $realizado ? ' is-realizado' : '' ?>">
                                    <div class="premium-event-date">
                                        <strong><?= date('d', strtotime($e['data_evento'])) ?></strong>
                                        <span><?= ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'][(int)date('n', strtotime($e['data_evento'])) - 1] ?><?= date('Y', strtotime($e['data_evento'])) !== date('Y') ? ' ' . date('Y', strtotime($e['data_evento'])) : '' ?></span>
                                    </div>

                                    <div>
                                        <span class="mini-badge evento">Evento</span><?php if ($realizado): ?> <span class="cal-realizado"><i class="bi bi-check2" aria-hidden="true"></i> Realizado</span><?php elseif ($faltamE = eventoFaltam($e)): ?> <span class="cal-faltam"><?= $faltamE ?></span><?php endif; ?>
                                        <h4><?= htmlspecialchars($e['titulo']) ?></h4>
                                        <small><?= date(eventoDiaInteiro($e) ? 'd/m/Y' : 'd/m/Y H:i', strtotime($e['data_evento'])) ?></small>
                                        <p><?= htmlspecialchars(mb_substr($e['descricao'] ?? '', 0, 90)) ?>...</p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-box"><?= te('inicio.agenda.sem_publicados') ?></div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>
</section>
<?php endif; /* fim da Agenda — até 15/10/2026 este if só fechava depois do mapa e escondia 7 secções */ ?>

<?php if (!empty($home['mostrar_proximidade'])): ?>
<?php
/*
 * Secção "Proximidade" (10/2026): ponte entre a Agenda e a Mensagem do Presidente.
 * 3 blocos: números REAIS (nunca inventados), guia "O que precisa de fazer hoje?" em
 * separadores, e "Voz do Freguês". As métricas de ocorrências (% resolvidas, tempo médio
 * de resposta) só aparecem quando houver registos suficientes para serem significativas;
 * até lá mostra-se a atividade da Junta neste ano.
 */
$proxMetricas = [];
try {
    $oc = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(estado = 'resolvido') AS resolvidas,
               AVG(CASE WHEN estado = 'resolvido' AND atualizado_em IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, criado_em, atualizado_em) END) AS horas
        FROM pedidos_junta
    ")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $oc = ['total' => 0]; }

if ((int)($oc['total'] ?? 0) >= 20) {
    $proxMetricas[] = ['valor' => (int)$oc['total'], 'sufixo' => '', 'rotulo' => 'Ocorrências recebidas', 'icone' => 'bi-chat-square-text-fill'];
    $proxMetricas[] = ['valor' => (int)round(100 * $oc['resolvidas'] / $oc['total']), 'sufixo' => '%', 'rotulo' => 'Ocorrências resolvidas', 'icone' => 'bi-check2-circle'];
    if ($oc['horas'] !== null) {
        $proxMetricas[] = ['valor' => (int)round($oc['horas']), 'sufixo' => 'h', 'rotulo' => 'Tempo médio até à resolução', 'icone' => 'bi-stopwatch-fill'];
    }
} else {
    $anoAtual = date('Y');
    $proxMetricas[] = ['valor' => (int)$pdo->query("SELECT COUNT(*) FROM noticias WHERE YEAR(data) = YEAR(CURDATE())")->fetchColumn(), 'sufixo' => '', 'rotulo' => "Notícias publicadas em $anoAtual", 'icone' => 'bi-newspaper'];
    $proxMetricas[] = ['valor' => (int)$pdo->query("SELECT COUNT(*) FROM eventos WHERE YEAR(data_evento) = YEAR(CURDATE())")->fetchColumn(), 'sufixo' => '', 'rotulo' => "Eventos na agenda de $anoAtual", 'icone' => 'bi-calendar-event-fill'];
    try {
        $nDocs = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM documentos WHERE ativo = 1) + (SELECT COUNT(*) FROM assembleia_documentos WHERE ativo = 1)")->fetchColumn();
    } catch (Exception $e) { $nDocs = 0; }
    $proxMetricas[] = ['valor' => $nDocs, 'sufixo' => '', 'rotulo' => 'Documentos públicos disponíveis', 'icone' => 'bi-file-earmark-text-fill'];
}
$proxEmail = siteConfig('email', '');
?>
<section class="section proximidade" data-spy="Proximidade" aria-labelledby="proximidadeTitulo">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker"><?= te('inicio.proximidade.kicker') ?></span>
                <h2 id="proximidadeTitulo"><?= htmlspecialchars(siteConfig('slogan', 'Perto de si')) ?></h2>
            </div>
        </div>

        <div class="proximidade__grelha">
            <!-- 1. Números reais -->
            <div class="prox-bloco prox-bloco--numeros">
                <h3 class="prox-bloco__titulo"><i class="bi bi-activity" aria-hidden="true"></i> <?= te('inicio.proximidade.numeros') ?></h3>
                <ul class="prox-numeros">
                    <?php foreach ($proxMetricas as $mt): ?>
                        <li>
                            <span class="prox-numeros__icone" aria-hidden="true"><i class="bi <?= $mt['icone'] ?>"></i></span>
                            <span class="prox-numeros__texto">
                                <strong aria-label="<?= $mt['valor'] . $mt['sufixo'] ?>"><span data-contar="<?= $mt['valor'] ?>" aria-hidden="true"><?= $mt['valor'] ?></span><?= $mt['sufixo'] ?></strong>
                                <span><?= htmlspecialchars($mt['rotulo']) ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 2. O que precisa de fazer hoje? (separadores acessíveis — app.js [data-separadores]) -->
            <div class="prox-bloco prox-bloco--guia" data-separadores>
                <h3 class="prox-bloco__titulo"><i class="bi bi-signpost-split-fill" aria-hidden="true"></i> <?= te('inicio.proximidade.guias_titulo') ?></h3>
                <div class="prox-tabs" role="tablist" aria-label="Guias rápidos">
                    <button type="button" role="tab" id="proxTab1" aria-controls="proxPainel1" aria-selected="true"><?= te('inicio.proximidade.tab1') ?></button>
                    <button type="button" role="tab" id="proxTab2" aria-controls="proxPainel2" aria-selected="false" tabindex="-1"><?= te('inicio.proximidade.tab2') ?></button>
                    <button type="button" role="tab" id="proxTab3" aria-controls="proxPainel3" aria-selected="false" tabindex="-1"><?= te('inicio.proximidade.tab3') ?></button>
                </div>

                <div class="prox-painel" role="tabpanel" id="proxPainel1" aria-labelledby="proxTab1">
                    <ol class="prox-passos">
                        <li><b>1</b> <?= te('inicio.proximidade.passo1') ?></li>
                        <li><b>2</b> <?= te('inicio.proximidade.passo2') ?></li>
                        <li><b>3</b> Guarde o código <?= htmlspecialchars(prefixoPedidos()) ?>-… para acompanhar</li>
                    </ol>
                    <a href="/pedidos.php" class="prox-acao"><?= te('inicio.proximidade.acao1') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>

                <div class="prox-painel" role="tabpanel" id="proxPainel2" aria-labelledby="proxTab2" hidden>
                    <ul class="prox-info">
                        <?php if (siteConfig('horario', '') !== ''): ?>
                            <li><i class="bi bi-clock-fill" aria-hidden="true"></i> <?= htmlspecialchars(siteConfig('horario')) ?></li>
                        <?php endif; ?>
                        <?php if (siteConfig('morada', '') !== ''): ?>
                            <li><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> <?= htmlspecialchars(siteConfig('morada')) ?></li>
                        <?php endif; ?>
                        <?php if (siteConfig('telefone', '') !== ''): ?>
                            <li><i class="bi bi-telephone-fill" aria-hidden="true"></i> <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', siteConfig('telefone'))) ?>"><?= htmlspecialchars(siteConfig('telefone')) ?></a></li>
                        <?php endif; ?>
                    </ul>
                    <a href="/contactos.php" class="prox-acao"><?= te('inicio.proximidade.acao2') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>

                <div class="prox-painel" role="tabpanel" id="proxPainel3" aria-labelledby="proxTab3" hidden>
                    <form class="prox-form" method="post" action="/consultar-pedido.php">
                        <label for="proxCodigo"><?= te('inicio.proximidade.codigo') ?></label>
                        <input id="proxCodigo" name="codigo" type="text" required autocomplete="off" placeholder="<?= htmlspecialchars(prefixoPedidos()) ?>-…">
                        <label for="proxEmail"><?= te('inicio.proximidade.email') ?></label>
                        <input id="proxEmail" name="email" type="email" required autocomplete="email" placeholder="o.seu@email.pt">
                        <button type="submit" class="prox-acao"><?= te('inicio.proximidade.acao3') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                    </form>
                </div>
            </div>

            <!-- 3. Voz do Freguês -->
            <div class="prox-bloco prox-bloco--voz">
                <span class="prox-voz__icone" aria-hidden="true"><i class="bi bi-chat-heart-fill"></i></span>
                <h3 class="prox-bloco__titulo"><?= te('inicio.proximidade.voz_titulo') ?></h3>
                <p><?= te('inicio.proximidade.voz_texto') ?></p>
                <div class="prox-voz__acoes">
                    <?php if ($proxEmail !== ''): ?>
                        <a class="prox-acao" href="mailto:<?= htmlspecialchars($proxEmail) ?>?subject=<?= rawurlencode('Sugestão para a Junta de Freguesia') ?>">
                            <i class="bi bi-envelope-fill" aria-hidden="true"></i> <?= te('inicio.proximidade.voz_botao1') ?>
                        </a>
                        <span class="prox-voz__email"><?= htmlspecialchars($proxEmail) ?></span>
                    <?php endif; ?>
                    <a class="prox-acao prox-acao--secundaria" href="/pedidos.php">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> <?= te('inicio.proximidade.voz_botao2') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; /* Proximidade */ ?>

<?php
/* Cartões imersivos "Apoio à população": editáveis em Página Inicial › Cartões e atalhos (12/10/2026) */
require_once __DIR__ . "/includes/cartoes-imersivos.php";
$imersivoDe = fn(array $c) => ['titulo' => $c['titulo'], 'texto' => $c['texto'], 'url' => $c['link'], 'imagem' => $c['imagem'],
    'icone' => $c['icone'] ?: 'bi-arrow-up-right', 'etiqueta' => $c['etiqueta'], 'acao' => $c['acao'] ?: tx('inicio.apoios.acao'),
    'externo' => (bool)preg_match('~^https?://~i', (string)$c['link'])];
if (!empty($home['mostrar_apoios'])) secaoImersiva('apoios', tx('inicio.apoios.kicker'), tx('inicio.apoios.titulo'), tx('inicio.apoios.texto'), array_map($imersivoDe, cartoesSite('apoios')), 'Apoios');
?>

<?php
/*
 * Mensagem do Presidente (homepage).
 *
 * A mensagem é editável no backoffice em "Página Inicial" (homepage_config).
 * Antes não havia campo nenhum: o texto vinha da BIOGRAFIA do presidente, na
 * secção Executivo — não era nada óbvio onde se alterava, e mudar a biografia
 * mudava também a homepage.
 *
 * Ordem de preferência (fallback para não perder o que os sites já mostram):
 *   1. homepage_config.presidente_mensagem   (o campo próprio, novo)
 *   2. executivo_membros.biografia           (o que era usado até aqui)
 *   3. texto genérico
 */
$presMensagem = trim((string)($home['presidente_mensagem'] ?? '')) ?: trim((string)($presidenteHome['biografia'] ?? ''));
$presTitulo   = trim((string)($home['presidente_titulo'] ?? '')) ?: 'Mensagem do Presidente';
$presNome     = trim((string)($home['presidente_nome'] ?? '')) ?: (string)($presidenteHome['nome'] ?? '');
$presCargo    = trim((string)($home['presidente_cargo'] ?? '')) ?: (string)($presidenteHome['cargo'] ?? '');
$presFoto     = trim((string)($home['presidente_foto'] ?? '')) ?: (string)($presidenteHome['foto'] ?? '');

$mostrarPresidente = !empty($presidenteHome) && !empty($home['mostrar_mensagem_presidente'] ?? 1);
?>

<?php if ($mostrarPresidente):
    /* Mensagem do Presidente — versão limpa (12/10/2026, a pedido: «menos efeitos, estilo Apple»):
       cartão de vidro sobre um fundo suave e desfocado, retrato simples, a tipografia faz o trabalho,
       um botão principal e uma ligação discreta. Só uma entrada suave ao aparecer (reveal genérico). */
    $presEmail = trim((string)($presidenteHome['email'] ?? ''));
    $presTexto = $presMensagem !== '' ? mb_substr(preg_replace('/\s+/u', ' ', $presMensagem), 0, 520) . (mb_strlen($presMensagem) > 520 ? '…' : '') : '';
?>
<section class="section pres" data-spy="Presidente" aria-labelledby="presTitulo">
    <div class="container">
        <div class="pres__cartao">
            <figure class="pres__retrato">
                <span class="pres__moldura">
                <?php if ($presFoto !== ''): ?>
                    <img src="<?= htmlspecialchars(fotoMembroUrl($presFoto)) ?>" alt="<?= htmlspecialchars($presNome) ?>" loading="lazy" decoding="async">
                <?php else: ?>
                    <span class="pres__inicial" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($presNome, 0, 1))) ?></span>
                <?php endif; ?>
                </span>
            </figure>

            <div class="pres__conteudo">
                <span class="section-kicker pres__kicker"><?= te('inicio.presidente.kicker') ?></span>
                <h2 id="presTitulo" class="pres__titulo"><?= htmlspecialchars($presTitulo) ?></h2>
                <p class="pres__texto"><?= $presTexto !== '' ? htmlspecialchars($presTexto) : te('inicio.presidente.texto_omissao') ?></p>

                <p class="pres__assinatura">
                    <strong><?= htmlspecialchars($presNome) ?></strong>
                    <span><?= htmlspecialchars($presCargo) ?></span>
                </p>

                <div class="pres__acoes">
                    <a href="/executivo.php" class="pres__botao"><?= te('inicio.presidente.botao') ?></a>
                    <a href="<?= $presEmail !== '' ? 'mailto:' . htmlspecialchars($presEmail) : '/contactos.php' ?>" class="pres__ligacao">
                        <?= te('inicio.presidente.botao2') ?> <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>

            </div>

            <?php
            /* «Quem representa a freguesia» (16/10/2026, a pedido — o lado direito estava vazio): o resto do Executivo
               e a Mesa da Assembleia, lidos das tabelas que o admin já edita. Sem fotografia → iniciais. */
            $equipaExec = []; $mesaAssembleia = [];
            try {
                $equipaExec = $pdo->query("SELECT nome, cargo, foto FROM executivo_membros WHERE ativo = 1 AND cargo NOT LIKE '%Presidente%' ORDER BY ordem, id LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
                $mesaAssembleia = $pdo->query("SELECT nome, cargo, foto FROM assembleia_composicao WHERE ativo = 1 AND grupo LIKE 'Mesa%' ORDER BY ordem, id LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}
            $nomeCurto = function (string $n): string { $p = preg_split('/\s+/u', trim($n)); return count($p) > 2 ? $p[0] . ' ' . end($p) : trim($n); };
            $iniciais = function (string $n): string { $p = preg_split('/\s+/u', trim($n)); return mb_strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : '')); };
            $pessoa = function (array $m) use ($nomeCurto, $iniciais) { ?>
                <li class="pres-equipa__pessoa">
                    <?php $f = fotoMembroUrl($m['foto'] ?? ''); if ($f !== ''): ?>
                        <img class="pres-equipa__avatar" src="<?= htmlspecialchars($f) ?>" alt="" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span class="pres-equipa__avatar" aria-hidden="true"><?= htmlspecialchars($iniciais($m['nome'])) ?></span>
                    <?php endif; ?>
                    <span class="pres-equipa__txt"><strong title="<?= htmlspecialchars($m['nome']) ?>"><?= htmlspecialchars($nomeCurto($m['nome'])) ?></strong><small><?= htmlspecialchars($m['cargo']) ?></small></span>
                </li>
            <?php };
            if ($equipaExec || $mesaAssembleia): ?>
            <aside class="pres-equipa" aria-labelledby="presEquipaT">
                <h3 class="section-kicker pres-equipa__titulo" id="presEquipaT">Quem representa a freguesia</h3>
                <?php if ($equipaExec): ?>
                <a class="pres-equipa__grupo" href="/executivo.php">
                    <span class="pres-equipa__rotulo">Executivo <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    <ul><?php foreach ($equipaExec as $m) $pessoa($m); ?></ul>
                </a>
                <?php endif; ?>
                <?php if ($mesaAssembleia): ?>
                <a class="pres-equipa__grupo" href="/assembleia-composicao.php">
                    <span class="pres-equipa__rotulo">Assembleia de Freguesia <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    <ul><?php foreach ($mesaAssembleia as $m) $pessoa($m); ?></ul>
                </a>
                <?php endif; ?>
            </aside>
            <?php endif; ?>

            <?php /* 16/10/2026: as ligações da freguesia passam a uma faixa a toda a largura do cartão (4 colunas iguais) */ ?>
            <?php /* 15/10/2026: com o «Bem-vindo» desligado, as ligações dele (Página Inicial › Cartões ›
                     Destaques do Bem-vindo) passam para aqui — a freguesia continua a um clique */
            if (empty($home['mostrar_boasvindas'])): $ligacoesFreg = cartoesSite('boasvindas'); ?>
            <nav class="pres__freguesia" aria-label="<?= te('inicio.boasvindas.botao') ?>">
                <?php foreach ($ligacoesFreg as $c): ?>
                <a href="<?= htmlspecialchars($c['link'] ?: '#') ?>"><i class="bi <?= htmlspecialchars($c['icone'] ?: 'bi-arrow-right') ?>" aria-hidden="true"></i> <?= htmlspecialchars($c['titulo']) ?></a>
                <?php endforeach; ?>
                <a href="/freguesia.php" class="pres__freguesia-mais"><?= te('inicio.boasvindas.botao') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($home['mostrar_boasvindas'])): ?>
<section class="section home-welcome" data-spy="Bem-vindo">
    <div class="container welcome-grid">
        <div class="welcome-text">
            <span class="section-kicker"><?= te('inicio.boasvindas.kicker') ?></span>
            <h2><?= htmlspecialchars($home['boasvindas_titulo']) ?></h2>
            <?php foreach (preg_split('/\n{2,}/', trim($home['boasvindas_texto'])) as $paragrafoBoasVindas): ?>
                <?php if (trim($paragrafoBoasVindas) !== ''): ?>
                    <p><?= htmlspecialchars(trim($paragrafoBoasVindas)) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="welcome-highlights">
                <?php foreach (cartoesSite('boasvindas') as $c): ?>
                <a href="<?= htmlspecialchars($c['link'] ?: '#') ?>" class="welcome-highlight-item">
                    <i class="bi <?= htmlspecialchars($c['icone'] ?: 'bi-arrow-right') ?>"></i>
                    <span><?= htmlspecialchars($c['titulo']) ?></span>
                    <i class="bi bi-chevron-right welcome-highlight-arrow"></i>
                </a>
                <?php endforeach; ?>
            </div>

            <a href="/freguesia.php" class="link-premium"><?= te('inicio.boasvindas.botao') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <div class="welcome-panel">
            <div class="welcome-panel-icon"><i class="bi bi-broadcast"></i></div>
            <h3><?= te('inicio.boasvindas.painel_titulo') ?></h3>
            <p>
                <?= te('inicio.boasvindas.painel_texto') ?>

            </p>

            <?php /* Números com contagem animada (app.js, [data-contar]): o valor final está sempre
                     no HTML — sem JS ou com "reduzir movimento" aparece logo. */ ?>
            <div class="stats-grid">
                <?php foreach ([
                    ['/pontos.php', $pontosCount, 'Pontos de interesse', 'bi-geo-alt-fill'],
                    ['/associacoes.php', $associacoesCount, 'Associações', 'bi-people-fill'],
                    ['/comercio.php', $comercioCount, 'Comércio local', 'bi-shop'],
                    ['/freguesia-documentos.php', $documentosCount, 'Documentos', 'bi-file-earmark-text-fill'],
                ] as [$urlStat, $nStat, $rotStat, $icoStat]): ?>
                    <a href="<?= $urlStat ?>" class="stat-tile" aria-label="<?= (int)$nStat ?> <?= $rotStat ?>">
                        <span class="stat-tile__icone" aria-hidden="true"><i class="bi <?= $icoStat ?>"></i></span>
                        <strong data-contar="<?= (int)$nStat ?>"><?= (int)$nStat ?></strong>
                        <span class="stat-tile__rotulo"><?= $rotStat ?></span>
                        <i class="bi bi-arrow-up-right stat-tile__seta" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($home['mostrar_pontos'])): ?>
<section class="section home-featured" data-spy="Património">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker"><?= te('inicio.pontos.kicker') ?></span>
                <h2><?= te('inicio.pontos.titulo') ?></h2>
            </div>
            <div class="home-featured__acoes">
                <?php /* 15/10/2026: o mapa e a galeria deixaram de ter secção própria na página inicial */ ?>
                <?php if (empty($home['mostrar_mapa'])): ?><a href="/mapa.php" class="home-featured__ligacao"><i class="bi bi-map" aria-hidden="true"></i> <?= te('inicio.pontos.mapa') ?></a><?php endif; ?>
                <?php if (empty($home['mostrar_galeria'])): ?><a href="/galeria.php" class="home-featured__ligacao"><i class="bi bi-images" aria-hidden="true"></i> <?= te('inicio.pontos.galeria') ?></a><?php endif; ?>
                <a href="/pontos.php" class="link-premium"><?= te('inicio.pontos.link') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>

        <?php /* Vitrine "bento" (10/2026): a fotografia é o cartão inteiro e o texto fica por cima,
                 num véu escuro. O quadro grande (12/10/2026, a pedido) é uma APRESENTAÇÃO: roda pelos
                 pontos de interesse com fotografia que NÃO estão nos 5 cartões pequenos (nunca repete
                 um que já esteja à vista); os 5 pequenos ficam fixos. Pára com o rato/foco, fora do
                 ecrã, com o botão de pausa e com «reduzir movimento» (app.js, [data-pontos-rotativo]). */
        $fixos = array_slice($pontos, 1, 5);
        $idsFixos = array_column($fixos, 'id');
        $rodam = array_values(array_filter($pontos, fn($p) => !in_array($p['id'], $idsFixos) && !empty($p['imagem'])));
        if (!$rodam && $pontos) $rodam = [$pontos[0]];
        $tilePonto = function (array $p, bool $grande, string $extra = '') use ($pdo) {
            // Fotografias do ponto (vivem na Galeria, no álbum do ponto) + a foto principal.
            // Vão para data-lightbox: o clique abre-as num lightbox (app.js); sem JS o link
            // leva à página do ponto como antes.
            $imgsPonto = imagensDaOrigem($pdo, 'ponto', (int)$p['id']);
            $nFotosPonto = count($imgsPonto);
            $fotosLb = [];
            if (!empty($p['imagem'])) $fotosLb[] = '/assets/img/' . $p['imagem'];
            foreach ($imgsPonto as $img) {
                $u = imagemGaleriaUrl($img['ficheiro']);
                if ($u !== '' && !in_array($u, $fotosLb, true)) $fotosLb[] = $u;
            }
            $descPonto = trim(preg_replace('/\s+/', ' ', strip_tags((string)($p['descricao'] ?? ''))));
            ?>
                <a class="ponto-tile<?= $grande ? ' ponto-tile--grande' : '' ?>" href="/ponto.php?id=<?= (int)$p['id'] ?>" <?= $extra ?>
                   <?php if ($fotosLb): ?>data-lightbox='<?= htmlspecialchars(json_encode(['titulo' => $p['nome'], 'fotos' => $fotosLb, 'link' => '/ponto.php?id=' . (int)$p['id'], 'linkTexto' => 'Ver página do local'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>'
                   aria-haspopup="dialog"<?php endif; ?>>
                    <?php if (!empty($p['imagem'])): ?>
                        <img src="<?= htmlspecialchars(miniaturaImagem('/assets/img/' . $p['imagem'], 900)) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="ponto-tile__sem-img" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                    <?php endif; ?>

                    <span class="ponto-tile__info">
                        <span class="ponto-tile__meta">
                            <?php if (!empty($p['localizacao'])): ?>
                                <span><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> <?= htmlspecialchars($p['localizacao']) ?></span>
                            <?php endif; ?>
                            <?php if ($nFotosPonto > 0): ?>
                                <span><i class="bi bi-images" aria-hidden="true"></i> <?= $nFotosPonto ?> foto<?= $nFotosPonto === 1 ? '' : 's' ?></span>
                            <?php endif; ?>
                        </span>
                        <h3 class="ponto-tile__nome"><?= htmlspecialchars($p['nome']) ?></h3>
                        <?php if ($grande && $descPonto !== ''): ?>
                            <span class="ponto-tile__desc"><?= htmlspecialchars(mb_substr($descPonto, 0, 160)) ?><?= mb_strlen($descPonto) > 160 ? '…' : '' ?></span>
                        <?php endif; ?>
                        <span class="ponto-tile__ver" aria-hidden="true"><?= $fotosLb ? 'Ver fotografias <i class="bi bi-arrows-angle-expand"></i>' : 'Ver local <i class="bi bi-arrow-right"></i>' ?></span>
                    </span>
                </a>
            <?php
        };
        ?>
        <div class="pontos-vitrine">
            <?php if ($rodam): ?>
            <div class="ponto-rotativo" data-pontos-rotativo role="group" aria-roledescription="apresentação" aria-label="<?= te('inicio.pontos.rotativo') ?>">
                <?php foreach ($rodam as $k => $p) $tilePonto($p, true, $k === 0 ? 'data-face="0"' : 'data-face="' . $k . '" inert aria-hidden="true"'); ?>
                <?php if (count($rodam) > 1): ?>
                    <?php /* setas (16/10/2026, a pedido) */ ?>
                    <button type="button" class="ponto-rotativo__seta ponto-rotativo__seta--ant" data-rotativo-ant data-sem-arrasto aria-label="Local anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" class="ponto-rotativo__seta ponto-rotativo__seta--seg" data-rotativo-seg data-sem-arrasto aria-label="Local seguinte"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                    <div class="ponto-rotativo__controlos">
                        <button type="button" class="ponto-rotativo__pausa" data-rotativo-pausa aria-label="Pausar a apresentação"><i class="bi bi-pause-fill" aria-hidden="true"></i></button>
                        <div class="ponto-rotativo__pontos" role="group" aria-label="Escolher local">
                            <?php foreach ($rodam as $k => $p): ?>
                                <button type="button" data-rotativo-ir="<?= $k ?>" aria-label="<?= htmlspecialchars($p['nome']) ?>"<?= $k === 0 ? ' aria-current="true"' : '' ?>><span></span></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php foreach ($fixos as $p) $tilePonto($p, false); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
/* Cartões imersivos "Descobrir": editáveis em Página Inicial › Cartões e atalhos (12/10/2026) */
if (!empty($home['mostrar_descobrir'])) secaoImersiva('descobrir', tx('inicio.descobrir.kicker'), tx('inicio.descobrir.titulo'), tx('inicio.descobrir.texto'), array_map($imersivoDe, cartoesSite('descobrir')), 'Descobrir');
?>

<?php if (!empty($home['mostrar_mapa'])): ?>
<section class="home-map-cta" data-spy="nao">
    <div class="container map-cta-inner">
        <div class="map-cta-text">
            <span class="section-kicker"><?= te('inicio.mapa.kicker') ?></span>
            <h2><?= te('inicio.mapa.titulo') ?></h2>
            <p>
                <?= te('inicio.mapa.texto') ?>

            </p>
        </div>

        <a href="/mapa.php" class="hero-btn"><?= te('inicio.mapa.botao') ?></a>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const countdown = document.querySelector(".evento-countdown");

    if (!countdown) return;

    const dataEvento = new Date(countdown.dataset.evento).getTime();

    function atualizarCountdown() {
        const agora = new Date().getTime();
        const distancia = dataEvento - agora;

        if (distancia <= 0) {
            countdown.innerHTML = "O evento já começou";
            return;
        }

        const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((distancia / (1000 * 60 * 60)) % 24);
        const minutos = Math.floor((distancia / (1000 * 60)) % 60);

        countdown.innerHTML = "Faltam " + dias + " dias, " + horas + "h " + minutos + "min";
    }

    atualizarCountdown();
    setInterval(atualizarCountdown, 60000);
});
</script>

<?php if (!empty($home['mostrar_servicos'])): ?>
<section class="section home-services" data-spy="Serviços">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker"><?= te('inicio.servicos.kicker') ?></span>
                <h2><?= te('inicio.servicos.titulo') ?></h2>
            </div>
        </div>

        <div class="services-grid">
            <?php foreach (cartoesSite('servicos') as $c): ?>
            <a href="<?= htmlspecialchars($c['link'] ?: '#') ?>" class="service-card">
                <span><i class="bi <?= htmlspecialchars($c['icone'] ?: 'bi-arrow-right') ?>"></i></span>
                <h3><?= htmlspecialchars($c['titulo']) ?></h3>
                <?php if (!empty($c['texto'])): ?><p><?= htmlspecialchars($c['texto']) ?></p><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$fotosGaleria = []; $nFotos = 0;
if (!empty($home['mostrar_galeria']) && !empty($galeriaHome)) {
        /* Apresentação da galeria (10/2026, substitui o mosaico de álbuns): SÓ fotos carregadas
           no separador "Galeria de imagens" do admin (álbuns manuais), sem repetidas (muitas são
           cópias com outro nome — compara-se o tamanho e, se igual, o md5). Troca a cada 4 s
           (app.js [data-gshow]); miniaturas em baixo; clicar na foto abre o lightbox. */
        $fotosGaleria = [];
        $vistas = [];
        foreach ($pdo->query("
            SELECT gi.ficheiro, gi.titulo, a.nome AS album
            FROM galeria_imagens gi
            JOIN galeria_albuns a ON a.id = gi.album_id
            WHERE a.ativo = 1 AND gi.ativo = 1 AND a.na_apresentacao = 1   -- escolhido no admin (Galeria de fotos)
            ORDER BY a.ordem ASC, a.criado_em DESC, gi.ordem ASC, gi.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC) as $img) {
            $src = imagemGaleriaUrl($img['ficheiro']);
            $c = __DIR__ . $src;
            if ($src === '' || !is_file($c)) continue;
            $t = filesize($c);
            foreach ($vistas[$t] ?? [] as $outro) {
                if (md5_file($outro) === md5_file($c)) continue 2;
            }
            $vistas[$t][] = $c;
            // títulos que são só o nome do ficheiro da câmara (DSC02199, IMG_1234…) dão lugar ao álbum
            $legenda = trim((string)$img['titulo']);
            if ($legenda === '' || preg_match('/^((dsc|img|dji|pxl|mvimg)[\s_\-]*\d[\w\-]*|[\d_\-\s]+)$/i', $legenda)) $legenda = $img['album'];
            $fotosGaleria[] = ['src' => $src, 'legenda' => $legenda];
        }
        $nFotos = count($fotosGaleria);
}
/* sem fotos escolhidas para a apresentação, a secção não aparece (antes ficava só o título e o botão) */
?>
<?php if (!empty($home['mostrar_galeria']) && !empty($galeriaHome) && $nFotos): ?>
<section class="section home-gallery-section" data-spy="Galeria">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker">
                    <?= htmlspecialchars($home['galeria_kicker'] ?? nomeFreguesia() . ' em imagens') ?>
                </span>
                <h2>
                    <?= htmlspecialchars($home['galeria_titulo'] ?? 'Uma freguesia com identidade') ?>
                </h2>
            </div>
        </div>

        <?php if ($nFotos): ?>
        <div class="gshow" data-gshow="auto">
            <div class="gshow__palco">
                <?php foreach ($fotosGaleria as $k => $f): ?>
                    <?php /* só a primeira traz a foto; as outras recebem-na quando chega a vez delas (app.js) — empilhadas no
                         palco, o «loading=lazy» não chegava e descarregavam-se todas (~20 MB) */ ?>
                    <img <?= $k === 0 ? 'src' : 'data-src' ?>="<?= htmlspecialchars(miniaturaImagem($f['src'], 1600)) ?>" alt="<?= htmlspecialchars($f['legenda']) ?>" decoding="async"
                         <?= $k === 0 ? 'class="ativa"' : 'loading="lazy"' ?> data-legenda="<?= htmlspecialchars($f['legenda']) ?>">
                <?php endforeach; ?>
                <?php /* Clicar abre o lightbox na foto que está à vista. Sem JS, leva à galeria. */ ?>
                <a class="gshow__abrir" href="/galeria.php" aria-haspopup="dialog" data-lightbox-inicio="0"
                   aria-label="Ampliar fotografia"
                   data-lightbox='<?= htmlspecialchars(json_encode(['titulo' => nomeFreguesia() . ' em imagens', 'fotos' => array_column($fotosGaleria, 'src'), 'link' => '/galeria.php', 'linkTexto' => 'Ver toda a galeria'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>'></a>
                <div class="gshow__info" aria-hidden="true">
                    <strong class="gshow__legenda" data-gshow-legenda><?= htmlspecialchars($fotosGaleria[0]['legenda']) ?></strong>
                    <span class="gshow__ver"><i class="bi bi-arrows-angle-expand"></i> <?= te('inicio.galeria.ampliar') ?></span>
                </div>
                <?php if ($nFotos > 1): ?>
                    <button type="button" class="gshow__seta gshow__seta--ant" data-gshow-ant aria-label="Fotografia anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" class="gshow__seta gshow__seta--seg" data-gshow-seg aria-label="Fotografia seguinte"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                    <div class="gshow__topo">
                        <span class="gshow__contador" data-gshow-contador>1 / <?= $nFotos ?></span>
                        <button type="button" class="gshow__pausa" data-gshow-pausa aria-label="Pausar apresentação de fotografias"><i class="bi bi-pause-fill" aria-hidden="true"></i></button>
                    </div>
                    <span class="gshow__progresso" aria-hidden="true"><span></span></span>
                <?php endif; ?>
                <p class="sr-only" aria-live="polite" data-gshow-anuncio></p>
            </div>
            <?php if ($nFotos > 1): ?>
            <div class="gshow__miniaturas" role="group" aria-label="Escolher fotografia">
                <?php foreach ($fotosGaleria as $k => $f): ?>
                    <button type="button" class="gshow__mini<?= $k === 0 ? ' ativa' : '' ?>" data-gshow-ir="<?= $k ?>"
                            aria-label="Fotografia <?= $k + 1 ?> de <?= $nFotos ?>: <?= htmlspecialchars($f['legenda']) ?>"<?= $k === 0 ? ' aria-current="true"' : '' ?>>
                        <img src="<?= htmlspecialchars(miniaturaImagem($f['src'])) ?>" alt="" loading="lazy" decoding="async">
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:24px;">
            <a href="/galeria.php" class="hero-btn"><?= te('inicio.galeria.botao') ?></a>
        </div>
    </div>
</section>

<?php endif; ?>


<?php if (!empty($home['mostrar_transparencia'])): ?>
<?php
/* Mosaico de transparência (10/2026): destaca os documentos de governação mais recentes.
   O orçamento e a prestação de contas são encontrados pelo TÍTULO (as categorias na BD não
   são consistentes entre sites); contagens reais das tabelas documentos e assembleia_documentos. */
$transp = ['orcamento' => null, 'contas' => null, 'nFreg' => 0, 'nAssemb' => 0, 'nReg' => 0, 'ultimaAssemb' => null];
try {
    $docsT = $pdo->query("SELECT id, titulo, categoria, ficheiro, data_documento FROM documentos WHERE ativo = 1 ORDER BY data_documento DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $transp['nFreg'] = count($docsT);
    foreach ($docsT as $d) {
        $t = mb_strtolower($d['titulo'] . ' ' . $d['categoria']);
        if (!$transp['orcamento'] && str_contains($t, 'orçamento')) $transp['orcamento'] = $d;
        if (!$transp['contas'] && str_contains($t, 'prestação de contas')) $transp['contas'] = $d;
        if (str_contains($t, 'regulamento') || str_contains($t, 'norma')) $transp['nReg']++;
    }
    $transp['nAssemb'] = (int)$pdo->query("SELECT COUNT(*) FROM assembleia_documentos WHERE ativo = 1")->fetchColumn();
    $transp['ultimaAssemb'] = $pdo->query("SELECT titulo, data_documento FROM assembleia_documentos WHERE ativo = 1 ORDER BY data_documento DESC, id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Exception $e) {}
$anoDoc = fn($d) => $d && !empty($d['data_documento']) ? date('Y', strtotime($d['data_documento'])) : '';
?>
<section class="section alt home-docs transp" data-spy="Transparência" aria-labelledby="transpTitulo">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker"><?= te('inicio.transparencia.kicker') ?></span>
                <h2 id="transpTitulo"><?= te('inicio.transparencia.titulo') ?></h2>
            </div>
            <a href="/transparencia.php" class="link-premium"><?= te('inicio.transparencia.link') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <div class="transp-bento">
            <a class="transp-cel transp-cel--grande" href="<?= $transp['orcamento'] ? '/assets/docs/' . htmlspecialchars(rawurlencode($transp['orcamento']['ficheiro'])) : '/freguesia-documentos.php' ?>"<?= $transp['orcamento'] ? ' target="_blank" rel="noopener"' : '' ?>>
                <span class="transp-ico" aria-hidden="true"><i class="bi bi-pie-chart-fill"></i></span>
                <span class="transp-etq">Orçamento<?= $anoDoc($transp['orcamento']) ? ' ' . $anoDoc($transp['orcamento']) : '' ?></span>
                <strong><?= htmlspecialchars($transp['orcamento']['titulo'] ?? 'Orçamento e Grandes Opções do Plano') ?></strong>
                <span class="transp-txt"><?= te('inicio.transparencia.orcamento_texto') ?></span>
                <span class="transp-acao"><?= $transp['orcamento'] ? 'Abrir PDF' : 'Ver documentos' ?> <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
            </a>

            <a class="transp-cel" href="<?= $transp['contas'] ? '/assets/docs/' . htmlspecialchars(rawurlencode($transp['contas']['ficheiro'])) : '/freguesia-documentos.php' ?>"<?= $transp['contas'] ? ' target="_blank" rel="noopener"' : '' ?>>
                <span class="transp-ico" aria-hidden="true"><i class="bi bi-calculator-fill"></i></span>
                <span class="transp-etq">Prestação de contas<?= $anoDoc($transp['contas']) ? ' ' . $anoDoc($transp['contas']) : '' ?></span>
                <strong><?= htmlspecialchars($transp['contas']['titulo'] ?? 'Prestação de contas') ?></strong>
                <span class="transp-acao"><?= $transp['contas'] ? 'Abrir PDF' : 'Ver documentos' ?> <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
            </a>

            <a class="transp-cel" href="/assembleia-documentos.php">
                <span class="transp-ico" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
                <span class="transp-etq">Assembleia de Freguesia</span>
                <strong><span data-contar="<?= (int)$transp['nAssemb'] ?>"><?= (int)$transp['nAssemb'] ?></span> documento<?= $transp['nAssemb'] == 1 ? '' : 's' ?></strong>
                <?php if ($transp['ultimaAssemb']): ?><span class="transp-txt">Mais recente: <?= htmlspecialchars(mb_strimwidth($transp['ultimaAssemb']['titulo'], 0, 60, '…')) ?></span><?php endif; ?>
                <span class="transp-acao"><?= te('inicio.transparencia.assembleia_texto') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
            </a>

            <a class="transp-cel transp-cel--larga" href="/freguesia-documentos.php">
                <span class="transp-ico" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                <span class="transp-etq"><?= te('inicio.transparencia.arquivo') ?></span>
                <strong><span data-contar="<?= (int)$transp['nFreg'] ?>"><?= (int)$transp['nFreg'] ?></span> documentos públicos<?= $transp['nReg'] ? ' · ' . (int)$transp['nReg'] . ' regulamentos' : '' ?></strong>
                <span class="transp-acao"><?= te('inicio.transparencia.pesquisar') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($home['mostrar_cta_final'])): ?>
<section class="home-final-cta" data-spy="nao">
    <div class="container">
        <h2><?= htmlspecialchars($home['cta_titulo']) ?></h2>
        <?php foreach (preg_split('/\n{2,}/', trim($home['cta_texto'])) as $paragrafoCta): ?>
            <?php if (trim($paragrafoCta) !== ''): ?>
                <p><?= htmlspecialchars(trim($paragrafoCta)) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
        <a href="/<?= htmlspecialchars($home['cta_botao_link']) ?>" class="hero-btn">
            <?= htmlspecialchars($home['cta_botao_texto']) ?>
        </a>
    </div>
</section>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>