<?php
// Preload da primeira imagem do slider: evita o "flash" cinzento do browser
// enquanto decodifica a imagem de destaque, visível sobretudo no primeiro carregamento.
$heroPreloadImage = null;
try {
    $dbConfigPreload = require __DIR__ . "/includes/db_config.php";
    $pdoPreload = new PDO(
        "mysql:host={$dbConfigPreload['host']};dbname={$dbConfigPreload['db']};charset=utf8mb4",
        $dbConfigPreload['user'],
        $dbConfigPreload['pass']
    );
    $primeiroSlide = $pdoPreload->query("SELECT imagem FROM slides_homepage WHERE ativo = 1 ORDER BY COALESCE(ordem, 999999), id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $heroPreloadImage = $primeiroSlide['imagem'] ?? null;
} catch (Exception $e) {
    $heroPreloadImage = null;
}

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/membros.php";
require_once __DIR__ . "/includes/galeria.php";
require_once __DIR__ . "/includes/mapa.php"; $centroMapa = centroFreguesia(); ?>

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
        'hero_titulo' => 'Bem-vindo',
        'hero_subtitulo' => 'Informação, serviços, documentos e património num único espaço digital.',
        'boasvindas_titulo' => 'Bem-vindo',
        'boasvindas_texto' => 'Uma freguesia com identidade, património, comunidade e futuro digital.',
        'cta_titulo' => 'Precisa de falar com a Junta?',
        'cta_texto' => 'Aceda aos serviços digitais, faça pedidos, consulte documentos ou entre em contacto connosco.',
        'cta_botao_texto' => 'Aceder à Junta Virtual',
        'cta_botao_link' => 'minha-area.php'
    ];
}

$pontos = $pdo->query("
    SELECT * FROM pontos_interesse 
    ORDER BY id DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$noticias = $pdo->query("
    SELECT * FROM noticias 
    ORDER BY data DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$eventos = $pdo->query("
    SELECT * FROM eventos
    WHERE data_evento >= CURDATE()
    ORDER BY data_evento ASC
    LIMIT 3
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














$ocorrenciasHome = $pdo->query("
    SELECT id, codigo, categoria, assunto, mensagem, localizacao, estado, latitude, longitude, criado_em
    FROM pedidos_junta
    WHERE publico = 1
    AND latitude IS NOT NULL
    AND longitude IS NOT NULL
    AND latitude != ''
    AND longitude != ''
    ORDER BY criado_em DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);













$presidenteHome = $pdo->query("
    SELECT *
    FROM executivo_membros
    WHERE ativo = 1
    AND cargo LIKE '%Presidente%'
    ORDER BY ordem ASC, id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);











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
    background:rgba(212,170,0,.17);
    border:1px solid rgba(212,170,0,.42);
    color:#F0D060;
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
        $tituloVideo = $homepageDestaque['titulo'] ?: ($home['hero_titulo'] ?? 'Atalaia e Alto Estanqueiro-Jardia');
        $subtituloVideo = $homepageDestaque['subtitulo'] ?: ($home['hero_subtitulo'] ?? '');
        $botaoTexto = $homepageDestaque['botao_texto'] ?: 'Saber mais';
        $botaoLink = $homepageDestaque['botao_link'] ?: '/freguesia.php';
    ?>

    <section class="home-video-hero">
        <?php if (!empty($videoFicheiro)): ?>
            <div class="home-video-fallback" style="position:absolute;inset:0;z-index:1;background:var(--cor-principal) url('/assets/img/granho-1782232182.jpg') center/cover;"></div>
            <video autoplay muted loop playsinline preload="auto" poster="/assets/img/granho-1782232182.jpg" style="z-index:2;">
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
            <span>▶ Destaque em vídeo</span>
            <h1><?= htmlspecialchars($tituloVideo) ?></h1>
            <p><?= htmlspecialchars($subtituloVideo) ?></p>

            <div class="home-video-actions">
                <a href="<?= htmlspecialchars($botaoLink) ?>" class="home-video-btn">
                    <?= htmlspecialchars($botaoTexto) ?>
                </a>

                <a href="/mapa.php" class="home-video-btn secondary">
                    Explorar freguesia
                </a>
            </div>

            <?php if (!empty($youtubeEmbed)): ?>
            <button type="button" class="rm-needs-consent home-video-consent" style="display:none;margin-top:16px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer;" onclick="window.rmAcceptCookies && window.rmAcceptCookies()">
                <i class="bi bi-play-circle"></i> Permitir vídeo (aceitar cookies do YouTube)
            </button>
            <?php endif; ?>
        </div>

        <div class="home-video-muted">Vídeo automático sem som</div>
    </section>

<?php else: ?>

<section class="hero-slider" id="heroSlider">
    <?php if (!empty($slides)): ?>
        <?php foreach ($slides as $i => $slide): ?>
            <div
                class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
                style="background-image: url('/assets/img/<?= htmlspecialchars($slide['imagem']) ?>');"
            >
                <div class="hero-overlay">
                    <span>Atalaia e Alto Estanqueiro-Jardia · Setúbal</span>
                    <?php if ($i === 0): ?>
                        <h1><?= htmlspecialchars($slide['titulo']) ?></h1>
                    <?php else: ?>
                        <h2><?= htmlspecialchars($slide['titulo']) ?></h2>
                    <?php endif; ?>
                    <p><?= htmlspecialchars($slide['subtitulo']) ?></p>

                    <div class="hero-actions">
                        <?php if (!empty($slide['link_destino'])): ?>
                            <a href="<?= htmlspecialchars($slide['link_destino']) ?>" class="hero-btn">Ver artigo</a>
                        <?php else: ?>
                            <a href="/freguesia.php" class="hero-btn">Conhecer a freguesia</a>
                        <?php endif; ?>

                        <a href="/mapa.php" class="hero-btn hero-btn-outline">Explorar mapa</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <button class="slider-arrow prev" id="prevSlide" aria-label="Slide anterior">‹</button>
        <button class="slider-arrow next" id="nextSlide" aria-label="Slide seguinte">›</button>

        <div class="slider-dots" id="sliderDots">
            <?php foreach ($slides as $i => $slide): ?>
                <button class="<?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="Ir para o slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="hero-slide active" style="background:var(--cor-principal);">
            <div class="hero-overlay">
                <span>Atalaia e Alto Estanqueiro-Jardia · Setúbal</span>
                <h1><?= htmlspecialchars($home['hero_titulo']) ?></h1>
                <p><?= htmlspecialchars($home['hero_subtitulo']) ?></p>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php endif; ?>

<?php endif; ?>

<section class="quick-access-section">
    <div class="container">
        <div class="quick-access-grid">
            <a href="/freguesia.php" class="quick-card">
                <span><i class="bi bi-bank2"></i></span>
                <strong>A Freguesia</strong>
                <small>História, identidade e território</small>
            </a>

            <a href="/pontos.php" class="quick-card">
                <span><i class="bi bi-geo-alt-fill"></i></span>
                <strong>Pontos de Interesse</strong>
                <small>Património e locais a visitar</small>
            </a>

            <a href="/assembleia-documentos.php" class="quick-card">
                <span><i class="bi bi-file-earmark-text-fill"></i></span>
                <strong>Documentos</strong>
                <small>Atas, editais e informação oficial</small>
            </a>

            <a href="/contactos.php" class="quick-card">
                <span><i class="bi bi-telephone-fill"></i></span>
                <strong>Contactos</strong>
                <small>Fale com a Junta de Freguesia</small>
            </a>
        </div>
    </div>
</section>

<?php /* Mensagem institucional (boas-vindas) movida para baixo das notícias/eventos */ ?>















<?php /* Mensagem do Presidente movida para depois das Notícias e Eventos */ ?>
























<?php if (!empty($home['mostrar_noticias_eventos'])): ?>
<section class="section home-news-events-premium">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="section-kicker">Atualidade & Agenda</span>
                <h2>Notícias e Eventos</h2>
            </div>

            <div class="news-events-actions">
                <a href="/noticias.php" class="link-premium">Ver notícias</a>
                <a href="/eventos.php" class="link-premium">Ver eventos</a>
            </div>
        </div>

        <div class="premium-news-events-grid">

            <div class="evento-semana-card">
                <span class="evento-badge">Evento da Semana</span>

                <?php if (!empty($eventoSemana)): ?>





<?php if (!empty($eventoSemana['imagem'])): ?>
    <div class="evento-semana-img">
        <img src="/assets/img/<?= htmlspecialchars($eventoSemana['imagem']) ?>" alt="<?= htmlspecialchars($eventoSemana['titulo']) ?>" style="object-position:<?= (int)($eventoSemana['imagem_foco_x'] ?? 50) ?>% <?= (int)($eventoSemana['imagem_foco_y'] ?? 50) ?>%">
    </div>
<?php endif; ?>




























                    
                    <h3><?= htmlspecialchars($eventoSemana['titulo']) ?></h3>

                    <div class="evento-data-premium">
                        <i class="bi bi-calendar-event-fill"></i> <?= date('d/m/Y H:i', strtotime($eventoSemana['data_evento'])) ?>
                    </div>

                    <p><?= htmlspecialchars(mb_substr($eventoSemana['descricao'] ?? '', 0, 160)) ?>...</p>

                    <div 
                        class="evento-countdown" 
                        data-evento="<?= htmlspecialchars($eventoSemana['data_evento']) ?>"
                    >
                        A calcular...
                    </div>

                    <a href="/evento.php?id=<?= (int)$eventoSemana['id'] ?>" class="hero-btn">Ver evento</a>
                <?php else: ?>
                    <h3>Sem eventos agendados</h3>
                    <p>Quando existir um novo evento, ele aparece automaticamente aqui.</p>
                    <a href="/eventos.php" class="hero-btn">Ver agenda</a>
                <?php endif; ?>
            </div>




























            <div class="news-events-lists">

                <div class="premium-list-block">
                    <div class="premium-list-head">
                        <span><i class="bi bi-newspaper"></i></span>
                        <h3>Últimas Notícias</h3>
                    </div>

                    <?php if (!empty($noticias)): ?>
                        <div class="premium-horizontal-list">
                            <?php foreach ($noticias as $n): ?>
                                <a href="/noticia.php?id=<?= (int)$n['id'] ?>" class="premium-news-card">
                                    <?php if (!empty($n['imagem'])): ?>
                                        <img src="/assets/img/<?= htmlspecialchars($n['imagem']) ?>" alt="<?= htmlspecialchars($n['titulo']) ?>" style="object-position:<?= (int)($n['imagem_foco_x'] ?? 50) ?>% <?= (int)($n['imagem_foco_y'] ?? 50) ?>%">
                                    <?php else: ?>
                                        <div class="premium-no-img"><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></div>
                                    <?php endif; ?>

                                    <div>
                                        <span class="mini-badge">Notícia</span>
                                        <h4><?= htmlspecialchars($n['titulo']) ?></h4>
                                        <small>Publicado em <?= date('d/m/Y H:i', strtotime($n['data'])) ?></small>
                                        <p><?= htmlspecialchars(mb_substr($n['descricao'] ?? '', 0, 90)) ?>...</p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-box">Ainda não existem notícias publicadas.</div>
                    <?php endif; ?>
                </div>

                <div class="premium-list-block">
                    <div class="premium-list-head">
                        <span><i class="bi bi-calendar-event-fill"></i></span>
                        <h3>Próximos Eventos</h3>
                    </div>

                    <?php if (!empty($eventos)): ?>
                        <div class="premium-horizontal-list">
                            <?php foreach ($eventos as $e): ?>
                                <a href="/evento.php?id=<?= (int)$e['id'] ?>" class="premium-event-card">
                                    <div class="premium-event-date">
                                        <strong><?= date('d', strtotime($e['data_evento'])) ?></strong>
                                        <span><?= date('m/Y', strtotime($e['data_evento'])) ?></span>
                                    </div>

                                    <div>
                                        <span class="mini-badge evento">Evento</span>
                                        <h4><?= htmlspecialchars($e['titulo']) ?></h4>
                                        <small><?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></small>
                                        <p><?= htmlspecialchars(mb_substr($e['descricao'] ?? '', 0, 90)) ?>...</p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-box">Ainda não existem eventos publicados.</div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>
</section>




















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

<?php if ($mostrarPresidente): ?>
<section class="section presidente-home-section">
    <div class="container">

        <div class="presidente-home-card">

            <div class="presidente-home-photo">
                <?php if ($presFoto !== ''): ?>
                    <img src="<?= htmlspecialchars(fotoMembroUrl($presFoto)) ?>" alt="<?= htmlspecialchars($presNome) ?>">
                <?php else: ?>
                    <span><?= strtoupper(mb_substr($presNome, 0, 1)) ?></span>
                <?php endif; ?>
            </div>

            <div class="presidente-home-content">
                <span class="section-kicker">Mensagem institucional</span>

                <h2><?= htmlspecialchars($presTitulo) ?></h2>

                <?php if ($presMensagem !== ''): ?>
                    <p><?= htmlspecialchars(mb_substr(preg_replace('/\s*
+\s*/', ' ', $presMensagem), 0, 450)) ?><?= mb_strlen($presMensagem) > 450 ? '…' : '' ?></p>
                <?php else: ?>
                    <p>
                        Uma mensagem de proximidade, transparência e compromisso
                        com todos os cidadãos da freguesia.
                    </p>
                <?php endif; ?>

                <div class="presidente-home-sign">
                    <strong><?= htmlspecialchars($presNome) ?></strong>
                    <small><?= htmlspecialchars($presCargo) ?></small>
                </div>

                <a href="/executivo.php" class="hero-btn">
                    Conhecer Executivo
                </a>
            </div>

        </div>

    </div>
</section>
<?php endif; ?>

<?php if (!empty($home['mostrar_boasvindas'])): ?>
<section class="section home-welcome">
    <div class="container welcome-grid">
        <div class="welcome-text">
            <span class="section-kicker">Mensagem institucional</span>
            <h2><?= htmlspecialchars($home['boasvindas_titulo']) ?></h2>
            <?php foreach (preg_split('/\n{2,}/', trim($home['boasvindas_texto'])) as $paragrafoBoasVindas): ?>
                <?php if (trim($paragrafoBoasVindas) !== ''): ?>
                    <p><?= htmlspecialchars(trim($paragrafoBoasVindas)) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="welcome-highlights">
                <a href="/historia.php" class="welcome-highlight-item">
                    <i class="bi bi-clock-history"></i>
                    <span>História e heráldica da freguesia</span>
                    <i class="bi bi-chevron-right welcome-highlight-arrow"></i>
                </a>
                <a href="/associacoes.php" class="welcome-highlight-item">
                    <i class="bi bi-people-fill"></i>
                    <span>Comunidade e associativismo</span>
                    <i class="bi bi-chevron-right welcome-highlight-arrow"></i>
                </a>
                <a href="/pedidos.php" class="welcome-highlight-item">
                    <i class="bi bi-laptop"></i>
                    <span>Serviços ao alcance de um clique</span>
                    <i class="bi bi-chevron-right welcome-highlight-arrow"></i>
                </a>
            </div>

            <a href="/freguesia.php" class="link-premium">Saber mais</a>
        </div>

        <div class="welcome-panel">
            <div class="welcome-panel-icon"><i class="bi bi-broadcast"></i></div>
            <h3>AAEJ Digital</h3>
            <p>
                Um espaço moderno para informar, aproximar e valorizar a freguesia,
                a sua população, o seu património e a sua identidade.
            </p>

            <div class="stats-grid">
                <a href="/pontos.php">
                    <strong><?= (int)$pontosCount ?></strong>
                    <span>Pontos</span>
                </a>
                <a href="/associacoes.php">
                    <strong><?= (int)$associacoesCount ?></strong>
                    <span>Associações</span>
                </a>
                <a href="/comercio.php">
                    <strong><?= (int)$comercioCount ?></strong>
                    <span>Comércio</span>
                </a>
                <a href="/freguesia-documentos.php">
                    <strong><?= (int)$documentosCount ?></strong>
                    <span>Documentos</span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($home['mostrar_pontos'])): ?>
<section class="section alt home-featured">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker">Património e território</span>
                <h2>Pontos de Interesse</h2>
            </div>
            <a href="/pontos.php" class="hero-btn">Ver todos</a>
        </div>

        <div class="grid">
            <?php foreach ($pontos as $p): ?>
                <?php
                // Nº de fotografias do ponto (vivem na Galeria, no álbum do ponto).
                $nFotosPonto = count(imagensDaOrigem($pdo, 'ponto', (int)$p['id']));
                ?>

                <!-- O cartão é um LINK para a página do ponto. Antes era um <article>:
                     clicar não fazia nada e só o botão "Ver todos" levava a algum lado. -->
                <a class="card home-card" href="/ponto.php?id=<?= (int)$p['id'] ?>">
                    <?php if (!empty($p['imagem'])): ?>
                        <img src="/assets/img/<?= htmlspecialchars($p['imagem']) ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                    <?php else: ?>
                        <div class="sem-imagem">Sem imagem</div>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($p['nome']) ?></h3>

                    <?php if (!empty($p['localizacao'])): ?>
                        <small><?= htmlspecialchars($p['localizacao']) ?></small>
                    <?php endif; ?>

                    <p><?= htmlspecialchars(mb_substr($p['descricao'] ?? '', 0, 120)) ?>...</p>

                    <?php if ($nFotosPonto > 0): ?>
                        <span class="home-card-fotos">
                            <i class="bi bi-images"></i>
                            <?= $nFotosPonto ?> fotografia<?= $nFotosPonto === 1 ? '' : 's' ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($home['mostrar_mapa'])): ?>
<section class="home-map-cta">
    <div class="container map-cta-inner">
        <div class="map-cta-text">
            <span class="section-kicker">Explore a freguesia</span>
            <h2>Descubra a freguesia no mapa interativo</h2>
            <p>
                Consulte os pontos de interesse, veja fotografias, leia descrições e obtenha
                indicações para chegar a cada local.
            </p>
        </div>

        <a href="/mapa.php" class="hero-btn">Abrir mapa</a>
    </div>
</section>
<?php endif; ?>































<section class="section junta-bolso-section">
    <div class="container">

        <div class="junta-bolso-card">

            <div class="junta-bolso-content">

                <span class="section-kicker">Junta Virtual</span>

                <h2>A Junta no bolso dos cidadãos</h2>

                <p>
                    Aceda aos serviços digitais da Junta de Freguesia sem sair de casa.
                    Faça pedidos, acompanhe processos, receba notificações e interaja
                    com a Junta através da plataforma online.
                </p>

                <div class="junta-bolso-actions">

                    <!-- Balcão Virtual temporariamente oculto:
                    <a href="/minha-area.php" class="hero-btn">
                        Entrar na Área do Cidadão
                    </a>
                    -->

                    <a href="/pedidos.php" class="hero-btn">
                        Fazer Pedido
                    </a>

                    <a href="/requerimentos.php" class="hero-btn">
                        Requerimentos
                    </a>

                </div>

            </div>




















            <div class="junta-bolso-stats">

                <a class="junta-stat-card" href="/executivo.php">
    <strong>
        <?= (int)$pdo->query("SELECT COUNT(*) FROM executivo_membros")->fetchColumn(); ?>
    </strong>
    <span>Membros do executivo</span>
</a>

                <a class="junta-stat-card" href="/eventos.php">
                    <strong>
                        <?= (int)$pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn(); ?>
                    </strong>
                    <span>Eventos publicados</span>
                </a>

                <a class="junta-stat-card" href="/freguesia-documentos.php">
                    <strong>
                        <?= (int)$pdo->query("SELECT COUNT(*) FROM documentos")->fetchColumn(); ?>
                    </strong>
                    <span>Documentos disponíveis</span>
                </a>

                <a class="junta-stat-card" href="/pontos.php">
                    <strong>
                        <?= (int)$pdo->query("SELECT COUNT(*) FROM pontos_interesse")->fetchColumn(); ?>
                    </strong>
                    <span>Pontos de interesse</span>
                </a>

            </div>

        </div>

    </div>
</section>





















































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
<?php endif; ?>
























<?php if (!empty($home['mostrar_servicos'])): ?>
<section class="section home-services">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker">Acesso rápido</span>
                <h2>Serviços e informação útil</h2>
            </div>
        </div>

        <div class="services-grid">
            <a href="/contactos.php" class="service-card reveal">
                <span><i class="bi bi-telephone-fill"></i></span>
                <h3>Contactos Úteis</h3>
                <p>Encontre contactos importantes da freguesia e do concelho.</p>
            </a>

            <a href="/freguesia-documentos.php" class="service-card reveal">
                <span><i class="bi bi-file-earmark-text-fill"></i></span>
                <h3>Documentos</h3>
                <p>Consulte documentos oficiais da Junta de Freguesia.</p>
            </a>

            <a href="/mapa.php" class="service-card reveal">
                <span><i class="bi bi-map-fill"></i></span>
                <h3>Mapa Interativo</h3>
                <p>Explore património, locais e pontos de interesse.</p>
            </a>

            <a href="/comercio.php" class="service-card reveal">
                <span><i class="bi bi-shop"></i></span>
                <h3>Economia Local</h3>
                <p>Conheça os estabelecimentos da freguesia.</p>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>























<?php if (!empty($home['mostrar_galeria']) && !empty($galeriaHome)): ?>
<section class="section home-gallery-section">
    <div class="container">
        <div class="section-heading">
            <div>
                <span class="section-kicker">
                    <?= htmlspecialchars($home['galeria_kicker'] ?? 'A freguesia em imagens') ?>
                </span>
                <h2>
                    <?= htmlspecialchars($home['galeria_titulo'] ?? 'Uma freguesia com identidade') ?>
                </h2>
            </div>
        </div>

        <div class="home-gallery reveal">
            <?php foreach ($galeriaHome as $alb): ?>
                <a class="home-album" href="/galeria.php?album=<?= (int)$alb['id'] ?>">
                    <?php if (!empty($alb['capa'])): ?>
                        <img src="<?= htmlspecialchars(imagemGaleriaUrl($alb['capa'])) ?>"
                             alt="<?= htmlspecialchars($alb['nome']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="sem-imagem">Sem imagem</div>
                    <?php endif; ?>

                    <span class="home-album-info">
                        <strong><?= htmlspecialchars($alb['nome']) ?></strong>
                        <small>
                            <?= (int)$alb['n_imagens'] ?> fotografia<?= (int)$alb['n_imagens'] === 1 ? '' : 's' ?>
                        </small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:24px;">
            <a href="/galeria.php" class="hero-btn">Ver toda a galeria</a>
        </div>
    </div>
</section>

<style>
.lb-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);align-items:center;justify-content:center;flex-direction:column;padding:60px 24px 24px;box-sizing:border-box;cursor:zoom-out;backdrop-filter:blur(4px);}
.lb-overlay.open{display:flex;}
.lb-img{max-width:92vw;max-height:80vh;object-fit:contain;border-radius:16px;box-shadow:0 30px 80px rgba(0,0,0,.5);cursor:default;}
.lb-close{position:fixed;top:18px;right:22px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:white;width:46px;height:46px;border-radius:50%;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;backdrop-filter:blur(8px);padding:0;}
.lb-close:hover{background:rgba(255,255,255,.28);}
.lb-bar{margin-top:18px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;justify-content:center;}
.lb-download{display:inline-flex;align-items:center;gap:8px;background:white;color:#11151B;padding:12px 20px;border-radius:14px;text-decoration:none;font-weight:900;font-size:14px;transition:.2s;}
.lb-download:hover{background:#f1f5f9;transform:translateY(-2px);}
.lb-counter{color:rgba(255,255,255,.7);font-weight:800;font-size:14px;min-width:50px;text-align:center;}
.lb-nav{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:white;width:46px;height:46px;border-radius:50%;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;backdrop-filter:blur(8px);position:fixed;top:50%;transform:translateY(-50%);padding:0;}
.lb-nav:hover{background:rgba(255,255,255,.28);}
#lbPrev{left:16px;}
#lbNext{right:16px;}
</style>

<div class="lb-overlay" id="lbOverlay" onclick="lbClose(event)" role="dialog" aria-modal="true" aria-label="Imagem ampliada">
    <button class="lb-close" onclick="lbFechar()" aria-label="Fechar">&#x2715;</button>
    <button class="lb-nav" id="lbPrev" onclick="lbMover(-1,event)" aria-label="Imagem anterior">&#8249;</button>
    <button class="lb-nav" id="lbNext" onclick="lbMover(1,event)" aria-label="Imagem seguinte">&#8250;</button>
    <img class="lb-img" id="lbImg" src="" alt="">
    <div class="lb-bar">
        <a class="lb-download" id="lbDownload" href="" download><i class="bi bi-download"></i> Descarregar</a>
        <span class="lb-counter" id="lbCounter"></span>
    </div>
</div>

<script>
var homeGaleriaImagens = <?= json_encode(array_map(fn($img) => '/assets/img/' . $img['imagem'], $galeriaHome), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var lbImagens = [];
var lbIdx = 0;
var lbUltimoFoco = null;

function lbMostrar() {
    document.getElementById('lbImg').src = lbImagens[lbIdx];
    document.getElementById('lbDownload').href = lbImagens[lbIdx];
    var multi = lbImagens.length > 1;
    document.getElementById('lbCounter').textContent = multi ? (lbIdx+1)+' / '+lbImagens.length : '';
    document.getElementById('lbPrev').style.display = multi ? 'flex' : 'none';
    document.getElementById('lbNext').style.display = multi ? 'flex' : 'none';
}
function lbAbrir(imgs, idx) {
    lbUltimoFoco = document.activeElement;
    lbImagens = imgs; lbIdx = idx;
    lbMostrar();
    document.getElementById('lbOverlay').classList.add('open');
    document.addEventListener('keydown', lbTecla);
    document.querySelector('.lb-close').focus();
}
function lbMover(dir, e) {
    if (e) e.stopPropagation();
    lbIdx = (lbIdx + dir + lbImagens.length) % lbImagens.length;
    lbMostrar();
}
function lbFechar() {
    document.getElementById('lbOverlay').classList.remove('open');
    document.getElementById('lbImg').src = '';
    document.removeEventListener('keydown', lbTecla);
    if (lbUltimoFoco) { lbUltimoFoco.focus(); lbUltimoFoco = null; }
}
function lbClose(e) { if (e.target.id === 'lbOverlay') lbFechar(); }
function lbTecla(e) {
    if (e.key === 'Escape') lbFechar();
    else if (e.key === 'ArrowLeft') lbMover(-1);
    else if (e.key === 'ArrowRight') lbMover(1);
    else if (e.key === 'Tab') lbTrapFocus(e);
}
function lbTrapFocus(e) {
    var focaveis = Array.prototype.filter.call(
        document.getElementById('lbOverlay').querySelectorAll('button, a[href]'),
        function (el) { return el.offsetParent !== null; }
    );
    if (!focaveis.length) return;
    var primeiro = focaveis[0];
    var ultimo = focaveis[focaveis.length - 1];
    if (e.shiftKey && document.activeElement === primeiro) {
        e.preventDefault(); ultimo.focus();
    } else if (!e.shiftKey && document.activeElement === ultimo) {
        e.preventDefault(); primeiro.focus();
    }
}
</script>
<?php endif; ?>



















<section class="section freguesia-stats-section">
    <div class="container">

        <div class="section-heading center">
            <div>
                <span class="section-kicker">Freguesia em números</span>
                <h2>Uma plataforma viva e dinâmica</h2>
            </div>
        </div>

        <div class="freguesia-stats-grid">

            <a class="freguesia-stat-card" href="/pontos.php">
                <div class="freguesia-stat-icon"><i class="bi bi-geo-alt-fill"></i></div>

                <strong>
                    <?= (int)$pdo->query("SELECT COUNT(*) FROM pontos_interesse")->fetchColumn(); ?>
                </strong>

                <span>Pontos de Interesse</span>
            </a>

            <a class="freguesia-stat-card" href="/freguesia-documentos.php">
                <div class="freguesia-stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>

                <strong>
                    <?= (int)$pdo->query("SELECT COUNT(*) FROM documentos")->fetchColumn(); ?>
                </strong>

                <span>Documentos Públicos</span>
            </a>

            <a class="freguesia-stat-card" href="/eventos.php">
                <div class="freguesia-stat-icon"><i class="bi bi-calendar-event-fill"></i></div>

                <strong>
                    <?= (int)$pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn(); ?>
                </strong>

                <span>Eventos Publicados</span>
            </a>

            <a class="freguesia-stat-card" href="/executivo.php">
                <div class="freguesia-stat-icon"><i class="bi bi-people-fill"></i></div>

                <strong>
                    <?= (int)$pdo->query("SELECT COUNT(*) FROM executivo_membros WHERE ativo = 1")->fetchColumn(); ?>
                </strong>

                <span>Membros do Executivo</span>
            </a>

            <a class="freguesia-stat-card" href="/comercio.php">
                <div class="freguesia-stat-icon"><i class="bi bi-shop"></i></div>

                <strong>
                    <?= (int)$pdo->query("SELECT COUNT(*) FROM comercio_local")->fetchColumn(); ?>
                </strong>

                <span>Economia Local</span>
            </a>

            <a class="freguesia-stat-card" href="/associacoes.php">
                <div class="freguesia-stat-icon"><i class="bi bi-bank2"></i></div>

                <strong>
                    <?= (int)$pdo->query("SELECT COUNT(*) FROM associacoes")->fetchColumn(); ?>
                </strong>

                <span>Associações</span>
            </a>

        </div>

    </div>
</section>















<section class="section alt home-docs">
    <div class="container docs-cta">
        <div>
            <span class="section-kicker">Transparência</span>
            <h2>Documentos oficiais</h2>
            <p>
                Consulte atas, editais, convocatórias, regulamentos e outros documentos
                disponibilizados pela freguesia e pela Assembleia de Freguesia.
            </p>
        </div>

        <div class="docs-buttons">
            <a href="/freguesia-documentos.php" class="link-premium">Documentos da Freguesia</a>
            <a href="/assembleia-documentos.php" class="link-premium">Documentos da Assembleia</a>
        </div>
    </div>
</section>

<?php if (!empty($home['mostrar_cta_final'])): ?>
<section class="home-final-cta">
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
















<?php if (!empty($ocorrenciasHome)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

<section class="section home-ocorrencias-section">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="section-kicker">Junta Virtual</span>
                <h2>Ocorrências recentes</h2>
            </div>

            <a href="/ocorrencias-mapa.php" class="link-premium">
                Ver mapa completo →
            </a>
        </div>

        <div class="home-ocorrencias-card">

            <aside class="home-ocorrencias-list">
                <div class="home-ocorrencias-head">
                    <strong>Últimas ocorrências</strong>
                    <span><?= count($ocorrenciasHome) ?></span>
                </div>

                <?php foreach ($ocorrenciasHome as $o): ?>
                    <button 
                        class="home-ocorrencia-item"
                        data-lat="<?= htmlspecialchars($o['latitude']) ?>"
                        data-lng="<?= htmlspecialchars($o['longitude']) ?>"
                    >
                        <span class="home-ocorrencia-dot"></span>

                        <div>
                            <strong><?= htmlspecialchars($o['assunto']) ?></strong>
                            <small><?= htmlspecialchars($o['localizacao'] ?? '-') ?></small>
                        </div>

                        <em><?= date('d/m/Y', strtotime($o['criado_em'])) ?></em>
                    </button>
                <?php endforeach; ?>
            </aside>

            <div id="homeOcorrenciasMapa"></div>

        </div>

    </div>
</section>

<script>
const ocorrenciasHome = <?= json_encode($ocorrenciasHome, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const mapaHome = L.map('homeOcorrenciasMapa', {
        scrollWheelZoom: false
    }).setView([<?= $centroMapa["lat"] ?>, <?= $centroMapa["lng"] ?>], <?= $centroMapa["zoom"] ?>);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapaHome);

    // Limite da freguesia: vem de assets/geo/freguesia.geojson (um ficheiro por site).
    // Antes estava aqui embutido, com centenas de coordenadas — era isso que obrigava
    // este ficheiro a ser diferente em cada freguesia.
    const limiteFreguesia = <?= geojsonFreguesiaJs() ?>;

    const limiteLayer = L.geoJSON(limiteFreguesia, {
        style: {
            color: '#ff3b30',
            weight: 3,
            opacity: 1,
            dashArray: '7,5',
            fillColor: '#ff3b30',
            fillOpacity: 0.05
        }
    }).addTo(mapaHome);

    const markers = [];

    ocorrenciasHome.forEach(function(o, index){
        const lat = parseFloat(o.latitude);
        const lng = parseFloat(o.longitude);

        if(!lat || !lng) return;

        const marker = L.circleMarker([lat, lng], {
            radius: 9,
            color: '#f59f00',
            fillColor: '#f59f00',
            fillOpacity: 0.9,
            weight: 3
        }).addTo(mapaHome);

        marker.bindPopup(`
            <strong>${o.assunto ?? 'Ocorrência'}</strong><br>
            <small>${o.localizacao ?? ''}</small>
        `);

        markers[index] = marker;
    });

    mapaHome.fitBounds(limiteLayer.getBounds(), {
        padding: [25, 25]
    });

    limiteLayer.bringToFront();

    document.querySelectorAll('.home-ocorrencia-item').forEach(function(btn, index){
        btn.addEventListener('click', function(){
            const lat = parseFloat(btn.dataset.lat);
            const lng = parseFloat(btn.dataset.lng);

            if(!lat || !lng) return;

            mapaHome.setView([lat, lng], 15);

            if(markers[index]){
                markers[index].openPopup();
            }
        });
    });

    setTimeout(function(){
        mapaHome.invalidateSize();
    }, 400);

});
</script>
<?php endif; ?>




















<?php require_once "includes/footer.php"; ?>