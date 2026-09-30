<?php require_once "includes/header.php"; ?>

<?php
require_once __DIR__ . "/includes/galeria.php";

// A galeria passou a estar organizada em ÁLBUNS (ver migrations/005_galeria_albuns.sql).
// Sem ?album=  -> mostra a lista de álbuns.
// Com  ?album=N -> mostra as imagens desse álbum.
// Os álbuns dos eventos e dos pontos de interesse são criados automaticamente:
// aparecem aqui sem ser preciso fazer nada.
$albumId = isset($_GET['album']) ? (int)$_GET['album'] : 0;
$album = null;
$imagens = [];
$albuns = [];

if ($albumId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM galeria_albuns WHERE id = ? AND ativo = 1");
    $stmt->execute([$albumId]);
    $album = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($album) {
        $imagens = imagensDoAlbum($pdo, $albumId);
    }
}

if (!$album) {
    $albumId = 0;
    $albuns = $pdo->query("
        SELECT a.*, COUNT(gi.id) AS n_imagens
        FROM galeria_albuns a
        JOIN galeria_imagens gi ON gi.album_id = a.id AND gi.ativo = 1
        WHERE a.ativo = 1
        GROUP BY a.id
        ORDER BY a.ordem ASC, a.criado_em DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$todasImagens = array_map(fn($img) => imagemGaleriaUrl($img['ficheiro']), $imagens);
?>

<style>
.galeria-publica-hero{
    background:linear-gradient(135deg,var(--cor-principal),#11151B);
    color:white;
    padding:80px 0 60px;
    text-align:center;
}
.galeria-publica-hero span{
    color:#D4AA00;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    font-size:13px;
}
.galeria-publica-hero h1{
    font-size:clamp(34px,5vw,56px);
    margin:14px 0 16px;
    letter-spacing:-1px;
}
.galeria-publica-hero p{
    color:#dbeafe;
    font-size:18px;
    max-width:600px;
    margin:0 auto;
    line-height:1.8;
}

.galeria-publica-section{padding:54px 0 70px;}

.galeria-publica-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:18px;
}

.galeria-publica-item{
    display:block;
    width:100%;
    padding:0;
    border:0;
    font:inherit;
    text-align:left;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 12px 32px rgba(0,0,0,.10);
    cursor:zoom-in;
    position:relative;
    background:#e5e7eb;
    transition:.3s ease;
}
.galeria-publica-item:hover{
    transform:translateY(-6px);
    box-shadow:0 22px 55px rgba(0,0,0,.17);
}
.galeria-publica-item img{
    width:100%;
    height:220px;
    object-fit:cover;
    display:block;
    transition:.4s ease;
}
.galeria-publica-item:hover img{transform:scale(1.06);}
.galeria-publica-legenda{
    position:absolute;
    bottom:0;
    left:0;
    right:0;
    background:linear-gradient(0deg,rgba(0,0,0,.75),transparent);
    color:white;
    padding:28px 16px 14px;
    font-weight:800;
    font-size:14px;
    opacity:0;
    transition:.3s;
}
.galeria-publica-item:hover .galeria-publica-legenda{opacity:1;}
.galeria-publica-zoom{
    position:absolute;
    top:12px;
    right:12px;
    background:rgba(0,0,0,.5);
    color:white;
    border-radius:50%;
    width:36px;
    height:36px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    opacity:0;
    transition:.3s;
    backdrop-filter:blur(6px);
}
.galeria-publica-item:hover .galeria-publica-zoom{opacity:1;}

.galeria-vazia{
    background:white;
    border-radius:24px;
    padding:48px;
    text-align:center;
    color:#64748b;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

/* LIGHTBOX */
.lb-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);align-items:center;justify-content:center;flex-direction:column;padding:60px 24px 24px;box-sizing:border-box;cursor:zoom-out;backdrop-filter:blur(4px);}
.lb-overlay.open{display:flex;}
.lb-img{max-width:92vw;max-height:80vh;object-fit:contain;border-radius:16px;box-shadow:0 30px 80px rgba(0,0,0,.5);cursor:default;}
.lb-close{position:fixed;top:18px;right:22px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:white;width:46px;height:46px;border-radius:50%;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;backdrop-filter:blur(8px);padding:0;}
.lb-close:hover{background:rgba(255,255,255,.28);}
.lb-bar{margin-top:18px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;justify-content:center;}
.lb-download{display:inline-flex;align-items:center;gap:8px;background:white;color:#1C3F6E;padding:12px 20px;border-radius:14px;text-decoration:none;font-weight:900;font-size:14px;transition:.2s;}
.lb-download:hover{background:#f1f5f9;transform:translateY(-2px);}
.lb-counter{color:rgba(255,255,255,.7);font-weight:800;font-size:14px;min-width:50px;text-align:center;}
.lb-nav{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:white;width:46px;height:46px;border-radius:50%;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;backdrop-filter:blur(8px);position:fixed;top:50%;transform:translateY(-50%);padding:0;}
.lb-nav:hover{background:rgba(255,255,255,.28);}
#lbPrev{left:16px;}
#lbNext{right:16px;}

@media(max-width:700px){
    .galeria-publica-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
    .galeria-publica-item img{height:150px;}
    .galeria-publica-legenda,.galeria-publica-zoom{opacity:1;}
}
</style>

<?php $nomeFreguesia = siteConfig('nome_site', 'a freguesia'); ?>

<section class="galeria-publica-hero">
    <div class="container">
        <span><i class="bi bi-images"></i> Fotografia</span>
        <h1><?= $album ? htmlspecialchars($album['nome']) : 'Galeria de Imagens' ?></h1>
        <p>
            <?php if ($album && !empty($album['descricao'])): ?>
                <?= htmlspecialchars($album['descricao']) ?>
            <?php else: ?>
                Fotografias da <?= htmlspecialchars($nomeFreguesia) ?> — o seu território, as suas gentes e os seus momentos.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="galeria-publica-section">
    <div class="container">

        <?php if ($album): ?>
            <p style="margin:0 0 22px;">
                <a href="/galeria.php" style="font-weight:900;text-decoration:none;color:var(--cor-principal,#242A30);">
                    <i class="bi bi-arrow-left"></i> Todos os álbuns
                </a>
            </p>
        <?php endif; ?>

        <?php if ($album && !empty($imagens)): ?>
            <div class="galeria-publica-grid">
                <?php foreach ($imagens as $i => $img): ?>
                    <button type="button" class="galeria-publica-item" onclick="lbAbrir(todasImagens, <?= $i ?>)">
                        <img src="<?= htmlspecialchars(imagemGaleriaUrl($img['ficheiro'])) ?>"
                             alt="<?= htmlspecialchars($img['titulo'] ?: $nomeFreguesia) ?>"
                             loading="lazy">
                        <?php if (!empty($img['titulo'])): ?>
                            <span class="galeria-publica-legenda"><?= htmlspecialchars($img['titulo']) ?></span>
                        <?php endif; ?>
                        <span class="galeria-publica-zoom"><i class="bi bi-zoom-in"></i></span>
                    </button>
                <?php endforeach; ?>
            </div>

        <?php elseif (!$album && !empty($albuns)): ?>
            <div class="galeria-publica-grid">
                <?php foreach ($albuns as $a): ?>
                    <a class="galeria-publica-item" href="/galeria.php?album=<?= (int)$a['id'] ?>" style="display:block;">
                        <?php if (!empty($a['capa'])): ?>
                            <img src="<?= htmlspecialchars(imagemGaleriaUrl($a['capa'])) ?>"
                                 alt="<?= htmlspecialchars($a['nome']) ?>" loading="lazy">
                        <?php endif; ?>
                        <span class="galeria-publica-legenda">
                            <?= htmlspecialchars($a['nome']) ?>
                            <small style="display:block;opacity:.8;font-weight:700;">
                                <?= (int)$a['n_imagens'] ?> fotografia<?= (int)$a['n_imagens'] === 1 ? '' : 's' ?>
                            </small>
                        </span>
                        <span class="galeria-publica-zoom"><i class="bi bi-collection"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="galeria-vazia">
                <i class="bi bi-images" style="font-size:42px;display:block;margin-bottom:14px;opacity:.4;"></i>
                <?= $album ? 'Este álbum ainda não tem fotografias.' : 'Ainda não existem imagens na galeria.' ?><br>
                <small style="font-weight:700;opacity:.7;">As imagens são adicionadas pela equipa da Junta de Freguesia.</small>
            </div>
        <?php endif; ?>

    </div>
</section>

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
var todasImagens = <?= json_encode($todasImagens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
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

<?php require_once "includes/footer.php"; ?>
