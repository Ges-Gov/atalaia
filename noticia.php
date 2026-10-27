<?php
require_once "includes/config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT *
    FROM noticias
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$noticia = $stmt->fetch(PDO::FETCH_ASSOC);

// Meta tags Open Graph para partilhas
$siteBaseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$meta_titulo = $noticia['titulo'] ?? 'Notícia';

// resumo para a pré-visualização do Facebook/WhatsApp: numa só linha e cortado no fim de uma palavra
$meta_descricao = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($noticia['descricao'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
if (mb_strlen($meta_descricao) > 200) {
    $meta_descricao = mb_substr($meta_descricao, 0, 200);
    $meta_descricao = rtrim(mb_substr($meta_descricao, 0, mb_strrpos($meta_descricao, ' ') ?: 200), " ,;:.-–—") . '…';
}

$meta_url = $siteBaseUrl . "/noticia.php?id=" . $id;

$meta_imagem = !empty($noticia['imagem'])
    ? $siteBaseUrl . "/assets/img/" . $noticia['imagem']
    : $siteBaseUrl . "/assets/img/" . (siteConfig('logo') ?: 'freguesia-1.jpg');

require_once "includes/header.php";
 ?>

<?php
if (!$noticia) {
    echo '<section class="section"><div class="container"><div class="empty-box">Notícia não encontrada.</div></div></section>';
    require_once "includes/footer.php";
    exit;
}
seoNoticia($noticia);

$outras = $pdo->prepare("
    SELECT *
    FROM noticias
    WHERE id <> ?
    ORDER BY data DESC, id DESC
    LIMIT 3
");
$outras->execute([$id]);
$outrasNoticias = $outras->fetchAll(PDO::FETCH_ASSOC);

$galeriaNoticia = $pdo->prepare("SELECT * FROM noticias_imagens WHERE noticia_id = ? ORDER BY ordem ASC, id ASC");
$galeriaNoticia->execute([$id]);
$galeriaNoticiaFotos = $galeriaNoticia->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . "/includes/galeria.php";
$galeriaNoticiaFotos = fotosSemRepetidas($galeriaNoticiaFotos, !empty($noticia['imagem']) ? '/assets/img/' . $noticia['imagem'] : '',
    fn($f) => '/assets/img/' . $f);   // sem repetir a foto principal no carrossel

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$urlAtual = $protocolo . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$tituloPartilha = $noticia['titulo'] ?? 'Notícia';
$textoPartilha = mb_substr(strip_tags($noticia['descricao'] ?? ''), 0, 160);
$facebookShare = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($urlAtual);
$whatsappShare = 'https://wa.me/?text=' . urlencode($tituloPartilha . ' - ' . $urlAtual);
$emailShare = 'mailto:?subject=' . rawurlencode($tituloPartilha) . '&body=' . rawurlencode($tituloPartilha . "\n\n" . $urlAtual);

$todasImagens = [];
if (!empty($noticia['imagem'])) $todasImagens[] = '/assets/img/' . $noticia['imagem'];
foreach ($galeriaNoticiaFotos as $g) $todasImagens[] = '/assets/img/' . $g['ficheiro'];
$coverOffset = !empty($noticia['imagem']) ? 1 : 0;

?>

<section class="noticia-detail-hero">
    <div class="container">
        <span><?= te('noticia.hero.kicker') ?><?= !empty($noticia['categoria']) ? ' · ' . htmlspecialchars($noticia['categoria']) : '' ?></span>
        <h1><?= htmlspecialchars($noticia['titulo']) ?></h1>
        <p><?= te('noticia.hero.publicado') ?> <?= date('d/m/Y H:i', strtotime($noticia['data'])) ?></p>
    </div>
</section>

<style>
/* SHARE BUTTONS PREMIUM - NOTÍCIAS */
.noticia-share-box{
    margin:26px 0 24px;
    padding:22px;
    border-radius:22px;
    background:linear-gradient(135deg,#f8fafc,#ffffff);
    border:1px solid #e5e7eb;
    box-shadow:0 14px 34px rgba(0,0,0,.06);
}

.noticia-share-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    margin-bottom:16px;
}

.noticia-share-head strong{
    color:#11151B;
    font-size:18px;
}

.noticia-share-head span{
    color:#64748b;
    font-size:13px;
    font-weight:800;
}

.noticia-share-buttons{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.share-btn{
    border:0;
    min-height:46px;
    padding:0 16px;
    border-radius:15px;
    display:inline-flex;
    align-items:center;
    gap:9px;
    text-decoration:none;
    color:white;
    font-weight:900;
    cursor:pointer;
    transition:.25s ease;
    box-shadow:0 12px 28px rgba(0,0,0,.12);
    font-family:inherit;
    font-size:14px;
}

.share-btn:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 40px rgba(0,0,0,.18);
    filter:brightness(1.04);
}

.share-facebook{
    background:linear-gradient(135deg,#1877f2,#0d47a1);
}

.share-whatsapp{
    background:linear-gradient(135deg,#25d366,#128c7e);
}

.share-instagram{
    background:linear-gradient(135deg,#f58529,#dd2a7b,#8134af);
}

.share-copy{
    background:linear-gradient(135deg,#242A30,#11151B);
}

.share-email{
    background:linear-gradient(135deg,#64748b,#334155);
}

.share-note{
    margin-top:12px;
    color:#64748b;
    font-size:13px;
    line-height:1.6;
}

.share-toast{
    display:none;
    margin-top:12px;
    background:#dcfce7;
    color:#166534;
    padding:10px 12px;
    border-radius:12px;
    font-weight:900;
    font-size:13px;
}

.share-toast.show{
    display:block;
}

@media(max-width:700px){
    .noticia-share-head{
        flex-direction:column;
        align-items:flex-start;
    }

    .share-btn{
        width:100%;
        justify-content:center;
    }
}

/* Imagem capa da notícia — aparece inteira, sem cortes. Antes tinha
   width:100% + object-fit:cover, o que obrigava a imagem a preencher a
   caixa e cortava-a (grave em imagens altas, como cartazes/avisos com
   texto até ao fundo). Agora a caixa ajusta-se à imagem: nunca passa da
   largura do cartão nem dos 520px de altura, e fica centrada. */
.noticia-detail-img{
    display:block;
    width:auto;
    max-width:100%;
    max-height:520px;
    margin:0 auto 24px;
    border-radius:24px;
    cursor:zoom-in;
    transition:.25s;
}
.noticia-detail-img:hover{opacity:.92;}

/* LIGHTBOX */
</style>

<section class="section noticia-detail-section">
    <div class="container noticia-detail-layout">

        <article class="noticia-detail-card">

            <?php if (!empty($noticia['imagem'])): ?>
                <button type="button" class="lb-trigger" onclick="lbAbrir(todasImagens, 0)" title="Clique para ver em tamanho completo" data-carrossel data-fotos="<?= htmlspecialchars(json_encode($todasImagens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
                    <img
                        class="noticia-detail-img"
                        src="/assets/img/<?= htmlspecialchars($noticia['imagem']) ?>"
                        alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                        style="object-position:<?= (int)($noticia['imagem_foco_x'] ?? 50) ?>% <?= (int)($noticia['imagem_foco_y'] ?? 50) ?>%">
                </button>
            <?php endif; ?>

            <div class="noticia-detail-content">
                <?= nl2br(htmlspecialchars($noticia['descricao'] ?? '')) ?>
            </div>

            <?php if (!empty($galeriaNoticiaFotos)): ?>
                <style>
                .noticia-galeria{margin:30px 0 6px;}
                .noticia-galeria h3{color:#11151B;margin:0 0 16px;}
                .noticia-galeria-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;}
                .noticia-galeria-item{display:block;border-radius:16px;overflow:hidden;box-shadow:0 10px 26px rgba(0,0,0,.10);transition:.25s;cursor:zoom-in;}
                .noticia-galeria-item:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(0,0,0,.16);opacity:.9;}
                .noticia-galeria-item img{width:100%;height:170px;object-fit:cover;display:block;}
                </style>
                <div class="noticia-galeria" data-carrossel-substitui>
                    <h3><?= te('noticia.galeria.titulo') ?></h3>
                    <div class="noticia-galeria-grid">
                        <?php foreach ($galeriaNoticiaFotos as $gIdx => $g): ?>
                            <a class="noticia-galeria-item" href="#" onclick="lbAbrir(todasImagens, <?= $coverOffset + $gIdx ?>); return false;">
                                <img src="/assets/img/<?= htmlspecialchars($g['ficheiro']) ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="noticia-share-box">
                <div class="noticia-share-head">
                    <strong><?= te('noticia.partilha.titulo') ?></strong>
                    <span><?= te('noticia.partilha.texto') ?></span>
                </div>

                <div class="noticia-share-buttons">
                    <a
                        class="share-btn share-facebook"
                        href="<?= htmlspecialchars($facebookShare) ?>"
                        target="_blank"
                        rel="noopener">
                        <i class="bi bi-facebook"></i>
                        Facebook
                    </a>

                    <a
                        class="share-btn share-whatsapp"
                        href="<?= htmlspecialchars($whatsappShare) ?>"
                        target="_blank"
                        rel="noopener">
                        <i class="bi bi-whatsapp"></i>
                        WhatsApp
                    </a>

                    <button
                        type="button"
                        class="share-btn share-instagram"
                        onclick="copiarLinkNoticia()">
                        <i class="bi bi-instagram"></i>
                        Instagram
                    </button>

                    <button 
                        type="button"
                        class="share-btn share-copy"
                        onclick="copiarLinkNoticia()">
                        <span><i class="bi bi-link-45deg"></i></span>
                        <?= te('noticia.partilha.copiar') ?>
                    </button>

                    <a 
                        class="share-btn share-email" 
                        href="<?= htmlspecialchars($emailShare) ?>">
                        <span><i class="bi bi-envelope-fill"></i></span>
                        Email
                    </a>
                </div>

                <div class="share-note">
                    <?= te('noticia.partilha.nota_instagram') ?>
                </div>

                <div class="share-toast" id="shareToast">
                    <?= te('noticia.partilha.copiado') ?>
                </div>
            </div>

            <a href="/noticias.php" class="noticia-btn">
                <?= te('noticia.navegacao.voltar') ?>
            </a>

        </article>

        <aside class="noticia-side-card">
            <h3><?= te('noticia.sidebar.titulo') ?></h3>

            <?php if (!empty($outrasNoticias)): ?>
                <?php foreach ($outrasNoticias as $n): ?>
                    <a href="/noticia.php?id=<?= (int)$n['id'] ?>" class="noticia-side-item">
                        <strong><?= htmlspecialchars($n['titulo']) ?></strong>
                        <small><?= date('d/m/Y H:i', strtotime($n['data'])) ?></small>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?= te('noticia.sidebar.vazio') ?></p>
            <?php endif; ?>
        </aside>

    </div>
</section>

<script>
var todasImagens = <?= json_encode($todasImagens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function copiarLinkNoticia() {
    const link = <?= json_encode($urlAtual, JSON_UNESCAPED_UNICODE) ?>;
    const toast = document.getElementById('shareToast');

    if (navigator.share && window.innerWidth <= 900) {
        navigator.share({
            title: <?= json_encode($tituloPartilha, JSON_UNESCAPED_UNICODE) ?>,
            text: <?= json_encode($textoPartilha, JSON_UNESCAPED_UNICODE) ?>,
            url: link
        }).catch(function(){});
        return;
    }

    if (navigator.clipboard) {
        navigator.clipboard.writeText(link).then(function () {
            if (toast) {
                toast.classList.add('show');
                setTimeout(function () {
                    toast.classList.remove('show');
                }, 2500);
            }
        });
    } else {
        const input = document.createElement('input');
        input.value = link;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);

        if (toast) {
            toast.classList.add('show');
            setTimeout(function () {
                toast.classList.remove('show');
            }, 2500);
        }
    }
}
</script>

<?php require_once "includes/footer.php"; ?>