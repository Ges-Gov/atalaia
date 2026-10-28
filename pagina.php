<?php
// Páginas de conteúdo livre (migração 034): /pagina.php?p=<slug>
// O conteúdo é HTML escrito pela GesGov (não vem de visitantes); o título e o subtítulo são escapados.
require_once "includes/db.php";

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['p'] ?? ''));
$pagina = null;
if ($slug !== '') {
    try {
        $st = $pdo->prepare("SELECT * FROM paginas WHERE slug = ? AND ativo = 1 LIMIT 1");
        $st->execute([$slug]);
        $pagina = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        $pagina = null;
    }
}
if (!$pagina) {
    http_response_code(404);
}
// Fundo do separador próprio desta página (migração 035), lido pelo includes/header.php
if ($pagina && !empty($pagina['fundo'])) {
    $fundoSeparadorPagina = '/uploads/separadores/' . rawurlencode($pagina['fundo']);
}

require_once "includes/header.php";
?>

<style>
.pagina-hero{
    background:
        radial-gradient(circle at 12% 20%, color-mix(in srgb, var(--cor-secundaria) 28%, transparent), transparent 32%),
        radial-gradient(circle at 92% 10%, rgba(255,255,255,.14), transparent 30%),
        linear-gradient(135deg, var(--cor-principal), color-mix(in srgb, var(--cor-principal) 55%, #000) 75%);
    color:#fff;
    padding:84px 0 104px;
    position:relative;
    overflow:hidden;
}
.pagina-hero .container{position:relative;z-index:2;}
.pagina-kicker{
    display:inline-flex;
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.32);
    color:#fff;
    padding:8px 14px;
    border-radius:999px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    font-size:12px;
}
.pagina-hero h1{
    font-size:clamp(36px,5vw,64px);
    margin:16px 0 12px;
    line-height:1.04;
}
.pagina-hero p{
    max-width:780px;
    color:rgba(255,255,255,.9);
    font-size:19px;
    line-height:1.7;
    margin:0;
}
.pagina-shell{margin-top:-56px;position:relative;z-index:5;padding-bottom:70px;}
.pagina-card{
    max-width:980px;
    margin:0 auto;
    background:#fff;
    border-radius:26px;
    box-shadow:0 22px 50px rgba(15,23,42,.09);
    padding:42px 46px;
    color:#1f2933;
    line-height:1.75;
    font-size:17px;
}
.pagina-card h2{
    color:var(--cor-principal);
    font-size:26px;
    margin:34px 0 12px;
    line-height:1.2;
}
.pagina-card h2:first-child{margin-top:0;}
.pagina-card h3{font-size:20px;margin:24px 0 8px;}
.pagina-card ul{padding-left:22px;}
.pagina-card li{margin:4px 0;}
.pagina-card a{color:var(--cor-principal);font-weight:700;}
.pagina-card img.pagina-capa{width:100%;border-radius:18px;margin:0 0 26px;display:block;}
.pagina-card .pagina-indice{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 26px;padding:0;list-style:none;}
.pagina-card .pagina-indice a{
    display:inline-block;padding:9px 16px;border-radius:999px;text-decoration:none;
    background:color-mix(in srgb, var(--cor-principal) 9%, #fff);border:1px solid color-mix(in srgb, var(--cor-principal) 25%, #fff);
}
.pagina-card .pagina-ficheiros a{
    display:inline-flex;align-items:center;gap:8px;padding:10px 16px;margin:4px 8px 4px 0;border-radius:12px;text-decoration:none;
    background:var(--cor-secundaria);color:#11151B;
}
.pagina-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;margin:18px 0;}
.pagina-cards article{
    border:1px solid #e5e7eb;border-radius:20px;overflow:hidden;background:#fff;display:flex;flex-direction:column;
}
.pagina-cards article img{width:100%;aspect-ratio:16/10;object-fit:cover;display:block;}
.pagina-cards article div{padding:16px 18px;font-size:15px;line-height:1.55;}
.pagina-cards article strong{display:block;font-size:17px;color:#11151B;margin-bottom:2px;}
.pagina-cards article small{display:block;color:var(--cor-principal);font-weight:800;margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px;font-size:11px;}
@media(max-width:700px){
    .pagina-hero{padding:56px 0 86px;}
    .pagina-hero h1{font-size:clamp(28px,8vw,38px);}
    .pagina-hero p{font-size:15px;line-height:1.55;}
    .pagina-card{padding:26px 20px;font-size:16px;border-radius:20px;}
    .pagina-card h2{font-size:21px;}
}
</style>

<?php if (!$pagina): ?>
<section class="pagina-hero">
    <div class="container">
        <span class="pagina-kicker">Página não encontrada</span>
        <h1>Esta página não existe</h1>
        <p>Pode ter mudado de endereço. Volte à <a href="/index.php" style="color:#fff;text-decoration:underline">página inicial</a>.</p>
    </div>
</section>
<?php else: ?>
<section class="pagina-hero">
    <div class="container">
        <?php if (!empty($pagina['kicker'])): ?>
            <span class="pagina-kicker"><?= htmlspecialchars($pagina['kicker']) ?></span>
        <?php endif; ?>
        <h1><?= htmlspecialchars($pagina['titulo']) ?></h1>
        <?php if (!empty($pagina['subtitulo'])): ?>
            <p><?= htmlspecialchars($pagina['subtitulo']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="pagina-shell">
    <div class="container">
        <article class="pagina-card">
            <?php if (!empty($pagina['imagem'])): ?>
                <img class="pagina-capa" src="/assets/img/<?= htmlspecialchars($pagina['imagem']) ?>" alt="">
            <?php endif; ?>
            <?= $pagina['conteudo'] /* HTML de confiança, escrito pela GesGov */ ?>
        </article>
    </div>
</section>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>
