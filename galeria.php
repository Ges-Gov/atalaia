<?php require_once "includes/header.php"; ?>

<?php
require_once __DIR__ . "/includes/galeria.php";

// A galeria passou a estar organizada em ÁLBUNS (ver migrations/005_galeria_albuns.sql).
// Sem ?album=  -> mostra a lista de álbuns.
// Com  ?album=N -> mostra as imagens desse álbum.
// Com  ?vista=todas -> todas as fotografias de todos os álbuns, soltas, numa só galeria (20/10/2026, a pedido).
// Os álbuns dos eventos e dos pontos de interesse são criados automaticamente:
// aparecem aqui sem ser preciso fazer nada.
$albumId = isset($_GET['album']) ? (int)$_GET['album'] : 0;
$vistaTodas = !$albumId && ($_GET['vista'] ?? '') === 'todas';
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

if ($vistaTodas) {
    // Todas as fotografias ativas, de todos os álbuns ativos, pela ordem dos álbuns e das fotos
    $imagens = $pdo->query("
        SELECT gi.*, a.nome AS album_nome, a.id AS album_ref
        FROM galeria_imagens gi
        JOIN galeria_albuns a ON a.id = gi.album_id AND a.ativo = 1
        WHERE gi.ativo = 1
        ORDER BY a.ordem ASC, a.criado_em DESC, gi.ordem ASC, gi.id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Totais para o seletor «Álbuns / Todas as fotografias»
$totalFotos = (int)$pdo->query("SELECT COUNT(*) FROM galeria_imagens gi JOIN galeria_albuns a ON a.id = gi.album_id AND a.ativo = 1 WHERE gi.ativo = 1")->fetchColumn();

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

// Títulos que são só o nome do ficheiro da câmara («DSC02232», «DJI_20261001…», «IMG_1234») não dizem nada: escondem-se
foreach ($imagens as &$imgT) { if (preg_match('/^[A-Za-z_]{0,6}[\d_-]{3,}[\w-]*$/', trim((string)$imgT['titulo']))) $imgT['titulo'] = '';
    elseif (preg_match('/^[a-z0-9]+(-[a-z0-9]+)+$/', (string)$imgT['titulo'])) $imgT['titulo'] = mb_strtoupper(mb_substr(str_replace('-', ' ', $imgT['titulo']), 0, 1)) . mb_substr(str_replace('-', ' ', $imgT['titulo']), 1); }
unset($imgT);

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
    color:var(--ouro, #C8A02E);
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

/* Grelha de fotografias e de álbuns (10/2026): mosaico em colunas — cada foto na SUA proporção,
   nunca cortada; clicar numa foto abre o lightbox (app.js, zoom de 40%). */
.galeria-publica-grid{
    columns:4 250px;
    column-gap:14px;
}
.galeria-publica-item{
    position:relative;
    isolation:isolate;
    display:block;
    width:100%;
    padding:0;
    border:0;
    font:inherit;
    text-align:left;
    color:#fff;
    text-decoration:none;
    margin:0 0 14px;
    break-inside:avoid;
    border-radius:18px;
    overflow:hidden;
    background:#1b2026;
    box-shadow:0 1px 2px rgba(15,23,42,.06), 0 10px 26px -14px rgba(15,23,42,.35);
    cursor:zoom-in;
    transition:transform var(--dur-3,.3s) var(--ease-spring,ease), box-shadow var(--dur-3,.3s) var(--ease-out,ease);
}
a.galeria-publica-item{cursor:pointer;}
.galeria-publica-item:not(:has(img)){min-height:180px;}
.galeria-publica-item img{
    width:100%;
    height:auto;
    display:block;
    position:relative;
    z-index:-1;
    transition:filter .5s var(--ease-out,ease);
}
.galeria-publica-legenda{
    position:absolute;
    left:0; right:0; bottom:0;
    padding:44px 18px 16px;
    background:linear-gradient(0deg,rgba(8,10,12,.82),rgba(8,10,12,0));
    font-weight:600;
    font-size:14.5px;
    line-height:1.35;
    opacity:0;
    transform:translateY(6px);
    transition:opacity var(--dur-3,.3s) var(--ease-out,ease), transform var(--dur-3,.3s) var(--ease-out,ease);
}
.galeria-publica-legenda small{display:block;margin-top:3px;font-size:12.5px;font-weight:500;opacity:.85;}
/* nos álbuns o nome está sempre à vista */
a.galeria-publica-item .galeria-publica-legenda{opacity:1;transform:none;font-size:16px;font-weight:700;}
.galeria-publica-zoom{
    position:absolute;
    top:12px; right:12px;
    width:40px; height:40px;
    display:grid; place-items:center;
    border-radius:50%;
    background:rgba(8,10,12,.4);
    border:1px solid rgba(255,255,255,.22);
    -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px);
    font-size:16px;
    opacity:0;
    transform:scale(.9);
    transition:opacity var(--dur-3,.3s) var(--ease-out,ease), transform var(--dur-3,.3s) var(--ease-out,ease);
}
@media (hover:hover){
    .galeria-publica-item:hover{transform:translateY(-4px);box-shadow:0 2px 4px rgba(15,23,42,.06), 0 24px 44px -18px rgba(15,23,42,.45);}
    .galeria-publica-item:hover img{filter:brightness(1.06);}
    .galeria-publica-item:hover .galeria-publica-legenda,
    .galeria-publica-item:hover .galeria-publica-zoom{opacity:1;transform:none;}
}
.galeria-publica-item:focus-visible{outline:3px solid var(--cor-secundaria);outline-offset:3px;}
.galeria-publica-item:focus-visible .galeria-publica-legenda,
.galeria-publica-item:focus-visible .galeria-publica-zoom{opacity:1;transform:none;}
@media (prefers-reduced-motion:reduce){ .galeria-publica-item, .galeria-publica-item img{transition:none;} .galeria-publica-item:hover{transform:none;} }

.galeria-vazia{
    background:white;
    border-radius:24px;
    padding:48px;
    text-align:center;
    color:#64748b;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

/* Seletor de vista: Álbuns | Todas as fotografias */
.galeria-vistas{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin:0 0 24px; }
.galeria-vistas__grupo{ display:inline-flex; padding:5px; gap:4px; border-radius:999px; background:#fff; box-shadow:0 0 0 1px #e5e7eb, 0 8px 20px -14px rgba(15,23,42,.35); }
.galeria-vistas__grupo a{ display:inline-flex; align-items:center; gap:8px; min-height:44px; padding:0 18px; border-radius:999px; font-size:15px; font-weight:600; text-decoration:none; color:#334155; transition:background-color .2s, color .2s; }
.galeria-vistas__grupo a:hover{ background:color-mix(in srgb, var(--cor-principal) 8%, #fff); }
.galeria-vistas__grupo a[aria-current="page"]{ background:var(--cor-principal); color:#fff; }
.galeria-vistas__grupo a:focus-visible{ outline:3px solid var(--cor-secundaria); outline-offset:2px; }
.galeria-vistas__conta{ font-size:15px; color:#475569; }
.galeria-vistas__grupo a{ white-space:nowrap; }
@media (max-width:40em){ .galeria-vistas__grupo{ width:100%; } .galeria-vistas__grupo a{ flex:1; justify-content:center; padding:0 10px; font-size:14px; } }
.galeria-publica-legenda .galeria-album-nome{ display:block; margin-top:2px; font-size:12px; font-weight:500; opacity:.85; }
@media (prefers-reduced-motion:reduce){ .galeria-vistas__grupo a{ transition:none; } }

/* LIGHTBOX */

@media(max-width:700px){
    .galeria-publica-grid{columns:2;column-gap:10px;}
    .galeria-publica-item{margin-bottom:10px;}
    .galeria-publica-item{border-radius:14px;}
    .galeria-publica-legenda{opacity:1;transform:none;padding:30px 12px 10px;font-size:13px;}
    .galeria-publica-zoom{display:none;}
}
</style>

<?php $nomeFreguesia = siteConfig('nome_site', 'a freguesia'); ?>

<section class="galeria-publica-hero">
    <div class="container">
        <span><i class="bi bi-images"></i> <?= te('galeria.hero.kicker') ?></span>
        <h1><?= $album ? htmlspecialchars($album['nome']) : ($vistaTodas ? te('galeria.vistas.titulo_todas') : 'Galeria de Imagens') ?></h1>
        <p>
            <?php if ($album && !empty($album['descricao'])): ?>
                <?= htmlspecialchars($album['descricao']) ?>
            <?php else: ?>
                <?= te('galeria.hero.texto_inicio') ?> <?= htmlspecialchars($nomeFreguesia) ?> <?= te('galeria.hero.texto_fim') ?>
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="galeria-publica-section">
    <div class="container">

        <?php if (!$album && $totalFotos > 0): ?>
            <div class="galeria-vistas">
                <nav class="galeria-vistas__grupo" aria-label="<?= te('galeria.vistas.rotulo') ?>">
                    <a href="/galeria.php"<?= $vistaTodas ? '' : ' aria-current="page"' ?>><i class="bi bi-folder2" aria-hidden="true"></i> <?= te('galeria.vistas.albuns') ?></a>
                    <a href="/galeria.php?vista=todas"<?= $vistaTodas ? ' aria-current="page"' : '' ?>><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> <?= te('galeria.vistas.todas') ?></a>
                </nav>
                <span class="galeria-vistas__conta"><?= $vistaTodas
                    ? $totalFotos . ' fotografia' . ($totalFotos === 1 ? '' : 's') . ' · clique numa para ver em grande e passar de uma em uma'
                    : count($albuns) . ' álbu' . (count($albuns) === 1 ? 'm' : 'ns') . ' · ' . $totalFotos . ' fotografia' . ($totalFotos === 1 ? '' : 's') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($album): ?>
            <p style="margin:0 0 22px;">
                <a href="/galeria.php" style="font-weight:900;text-decoration:none;color:var(--cor-principal,#242A30);">
                    <i class="bi bi-arrow-left"></i> <?= te('galeria.albuns.voltar') ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if (($album || $vistaTodas) && !empty($imagens)): ?>
            <div class="galeria-publica-grid">
                <?php foreach ($imagens as $i => $img): ?>
                    <button type="button" class="galeria-publica-item" onclick="lbAbrir(todasImagens, <?= $i ?>)">
                        <img src="<?= htmlspecialchars(imagemGaleriaUrl($img['ficheiro'])) ?>"
                             alt="<?= htmlspecialchars($img['titulo'] ?: $nomeFreguesia) ?>"
                             loading="lazy">
                        <?php if (!empty($img['titulo']) || $vistaTodas): ?>
                            <span class="galeria-publica-legenda"><?= htmlspecialchars($img['titulo'] ?: '') ?>
                                <?php if ($vistaTodas): ?><span class="galeria-album-nome"><i class="bi bi-folder2" aria-hidden="true"></i> <?= htmlspecialchars($img['album_nome']) ?></span><?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <span class="galeria-publica-zoom" aria-hidden="true"><i class="bi bi-zoom-in"></i></span>
                    </button>
                <?php endforeach; ?>
            </div>

        <?php elseif (!$album && !$vistaTodas && !empty($albuns)): ?>
            <div class="galeria-publica-grid">
                <?php foreach ($albuns as $a): ?>
                    <a class="galeria-publica-item" href="/galeria.php?album=<?= (int)$a['id'] ?>">
                        <?php if (!empty($a['capa'])): ?>
                            <img src="<?= htmlspecialchars(imagemGaleriaUrl($a['capa'])) ?>"
                                 alt="<?= htmlspecialchars($a['nome']) ?>" loading="lazy">
                        <?php endif; ?>
                        <span class="galeria-publica-legenda">
                            <?= htmlspecialchars($a['nome']) ?>
                            <small>
                                <?= (int)$a['n_imagens'] ?> fotografia<?= (int)$a['n_imagens'] === 1 ? '' : 's' ?>
                            </small>
                        </span>
                        <span class="galeria-publica-zoom" aria-hidden="true"><i class="bi bi-collection"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="galeria-vazia">
                <i class="bi bi-images" style="font-size:42px;display:block;margin-bottom:14px;opacity:.4;"></i>
                <?= $album ? 'Este álbum ainda não tem fotografias.' : 'Ainda não existem imagens na galeria.' ?><br>
                <small style="font-weight:700;opacity:.7;"><?= te('galeria.albuns.nota') ?></small>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
var todasImagens = <?= json_encode($todasImagens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

</script>

<?php require_once "includes/footer.php"; ?>
