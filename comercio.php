<?php require_once "includes/header.php"; ?>

<?php
// O "tipo" é texto livre ("Restaurante / Café", "Café / Snack-Bar"...): cada
// estabelecimento entra em todas as categorias cujas palavras aparecem no tipo.
$categoriasComercio = [
    'restaurantes' => ['Restaurantes', 'bi-egg-fried', ['restaurante', 'marisqueira', 'churrasqueira', 'tasca', 'pizzaria', 'grelhados', 'cervejaria']],
    'cafes'        => ['Cafés', 'bi-cup-hot-fill', ['café', 'cafe', 'cafetaria', 'snack']],
    'bares'        => ['Bares', 'bi-cup-straw', ['bar', 'pub', 'discoteca', 'wine bar']],
    'padarias'     => ['Padarias e Pastelarias', 'bi-basket2-fill', ['padaria', 'pastelaria', 'confeitaria']],
    'alojamento'   => ['Alojamento', 'bi-house-heart-fill', ['hotel', 'alojamento', 'turismo', 'hostel', 'residencial', 'pousada', 'guest house']],
    'mercearias'   => ['Mercearias e Supermercados', 'bi-cart-fill', ['mercearia', 'supermercado', 'minimercado', 'mini-mercado', 'talho', 'frutaria']],
    'farmacias'    => ['Farmácias', 'bi-capsule', ['farmácia', 'farmacia', 'parafarmácia']],
];
$categoriaOutros = ['Outros', 'bi-shop'];

function categoriasDoTipo($tipo, $categorias)
{
    $t = str_replace('snack-bar', 'snack', mb_strtolower((string)$tipo));
    $encontradas = [];
    foreach ($categorias as $chave => $cat) {
        foreach ($cat[2] as $palavra) {
            if (preg_match('/(^|[^\p{L}])' . preg_quote($palavra, '/') . 's?([^\p{L}]|$)/u', $t)) {
                $encontradas[] = $chave;
                break;
            }
        }
    }
    return $encontradas ?: ['outros'];
}

function comercioIcon($tipo)
{
    global $categoriasComercio, $categoriaOutros;
    $chave = categoriasDoTipo($tipo, $categoriasComercio)[0];
    $icone = $categoriasComercio[$chave][1] ?? $categoriaOutros[1];
    return '<i class="bi ' . $icone . '"></i>';
}

$todosComercio = $pdo->query("SELECT * FROM comercio_local ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
seoComercio($todosComercio);   // schema.org LocalBusiness (includes/seo.php)
$total = count($todosComercio);

$contagemCategorias = [];
foreach ($todosComercio as &$c) {
    $c['_categorias'] = categoriasDoTipo($c['tipo'] ?? '', $categoriasComercio);
    foreach ($c['_categorias'] as $k) {
        $contagemCategorias[$k] = ($contagemCategorias[$k] ?? 0) + 1;
    }
}
unset($c);

$filtrosDisponiveis = [];
foreach ($categoriasComercio as $chave => $cat) {
    if (!empty($contagemCategorias[$chave])) {
        $filtrosDisponiveis[$chave] = [$cat[0], $cat[1], $contagemCategorias[$chave]];
    }
}
if (!empty($contagemCategorias['outros'])) {
    $filtrosDisponiveis['outros'] = [$categoriaOutros[0], $categoriaOutros[1], $contagemCategorias['outros']];
}

$categoriaAtual = $_GET['categoria'] ?? '';
if (!isset($filtrosDisponiveis[$categoriaAtual])) {
    $categoriaAtual = '';
}

$filtrados = $categoriaAtual === ''
    ? $todosComercio
    : array_values(array_filter($todosComercio, fn($c) => in_array($categoriaAtual, $c['_categorias'], true)));
$totalFiltrados = count($filtrados);

$porPagina = 8;
$totalPaginas = max(1, (int)ceil($totalFiltrados / $porPagina));
$pagina = isset($_GET['pagina']) ? min($totalPaginas, max(1, (int)$_GET['pagina'])) : 1;
$comercio = array_slice($filtrados, ($pagina - 1) * $porPagina, $porPagina);

function urlComercio($categoria, $pagina = 1)
{
    $q = [];
    if ($categoria !== '') $q['categoria'] = $categoria;
    if ($pagina > 1) $q['pagina'] = $pagina;
    return '/comercio.php' . ($q ? '?' . http_build_query($q) : '') . '#comercio-lista';
}

$heroImagem = !empty($todosComercio[0]['imagem'])
    ? "/assets/img/" . $todosComercio[0]['imagem']
    : "/assets/img/freguesia-1.jpg";
?>

<style>
.comercio-page-insane{
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 34%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 54%, #f7f4ef 100%);
    padding-bottom:70px;
}

.comercio-hero-insane{
    position:relative;
    min-height:600px;
    overflow:hidden;
    display:flex;
    align-items:center;
    color:white;
    background:
        linear-gradient(90deg, rgba(17,21,28,.91), rgba(17,21,28,.48)),
        linear-gradient(0deg, rgba(0,0,0,.42), transparent 58%),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.comercio-hero-insane::before{
    content:"";
    position:absolute;
    width:620px;
    height:620px;
    border-radius:50%;
    right:-220px;
    top:-260px;
    background:rgba(255,255,255,.06);
}

.comercio-hero-insane::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:145px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}

.comercio-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1fr auto;
    gap:38px;
    align-items:center;
    padding:90px 0 135px;
}

.comercio-kicker{
    display:inline-flex;
    align-items:center;
    gap:9px;
    background:rgb(var(--ouro-rgb, 240 180 41) / .15);
    border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .42);
    color:var(--ouro-claro, #f8d77a);
    border-radius:999px;
    padding:10px 16px;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.9px;
    margin-bottom:18px;
}

.comercio-hero-insane h1{
    font-size:clamp(46px,7vw,84px);
    line-height:.98;
    margin:0 0 20px;
    letter-spacing:-2px;
}

.comercio-hero-insane p{
    color:#dbeafe;
    font-size:21px;
    line-height:1.8;
    max-width:760px;
    margin:0;
}

.comercio-hero-stat{
    min-width:190px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.20);
    backdrop-filter:blur(16px);
    border-radius:28px;
    padding:28px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.20);
}

.comercio-hero-stat strong{
    display:block;
    color:var(--ouro, #C8A02E);
    font-size:56px;
    line-height:1;
    margin-bottom:8px;
}

.comercio-hero-stat span{
    color:#dbeafe;
    font-weight:900;
}

.comercio-main{
    position:relative;
    z-index:5;
    margin-top:-78px;
}

.comercio-intro-panel{
    background:white;
    border:1px solid rgba(226,232,240,.95);
    box-shadow:0 24px 70px rgba(0,0,0,.12);
    border-radius:34px;
    padding:26px;
    margin-bottom:34px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:22px;
    flex-wrap:wrap;
}

.comercio-tabs{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.comercio-tabs span{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    color:#334155;
    padding:12px 16px;
    border-radius:999px;
    font-weight:900;
    font-size:13px;
}

.comercio-tabs span.active{
    background:#242A30;
    color:white;
    border-color:#242A30;
}

.comercio-count{
    background:#eff6ff;
    color:#242A30;
    padding:12px 16px;
    border-radius:999px;
    font-weight:900;
}

.comercio-section-head{
    display:flex;
    align-items:end;
    justify-content:space-between;
    gap:20px;
    margin-bottom:26px;
}

.comercio-section-head h2{
    color:#11151B;
    margin:8px 0 0;
    font-size:clamp(32px,4vw,48px);
    line-height:1.1;
    letter-spacing:-1px;
}

.comercio-section-head p{
    color:#64748b;
    margin:0;
    font-weight:700;
}

.comercio-grid-insane{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:24px;
}

.comercio-card-insane{
    background:white;
    border:1px solid #eef2f7;
    border-radius:32px;
    overflow:hidden;
    box-shadow:0 18px 50px rgba(0,0,0,.09);
    transition:.32s ease;
    position:relative;
}

.comercio-card-insane:hover{
    transform:translateY(-10px);
    box-shadow:0 32px 80px rgba(0,0,0,.15);
}

.comercio-image{
    height:250px;
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg,#242A30,#11151B);
}

.comercio-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.5s ease;
}

.comercio-card-insane:hover .comercio-image img{
    transform:scale(1.08);
}

.comercio-image::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.04),rgba(17,21,28,.55));
}

.comercio-no-img{
    width:100%;
    height:100%;
    display:grid;
    place-items:center;
    color:var(--ouro, #C8A02E);
    font-size:74px;
    font-weight:900;
}

.comercio-type-float{
    position:absolute;
    left:18px;
    top:18px;
    z-index:2;
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(255,255,255,.16);
    color:white;
    border:1px solid rgba(255,255,255,.25);
    backdrop-filter:blur(12px);
    padding:9px 13px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
}

.comercio-icon-float{
    position:absolute;
    right:18px;
    bottom:18px;
    z-index:2;
    width:56px;
    height:56px;
    border-radius:20px;
    display:grid;
    place-items:center;
    background:var(--cor-secundaria);
    color:#11151B;
    font-size:26px;
    box-shadow:0 14px 34px rgba(0,0,0,.28);
}

.comercio-body{
    padding:26px;
}

.comercio-body h3{
    color:#11151B;
    font-size:24px;
    line-height:1.2;
    margin:0 0 14px;
    letter-spacing:-.6px;
}

.comercio-info{
    display:flex;
    gap:8px;
    align-items:flex-start;
    color:#475569;
    font-size:14px;
    line-height:1.6;
    margin-bottom:9px;
    word-break:break-word;
}

.comercio-info strong{
    color:#11151B;
}
/* 15/10/2026: rótulo por cima do valor — lado a lado, o rótulo encolhia e partia («Morad / a:») */
.comercio-info{ flex-direction:column; gap:2px; }
.comercio-info strong{ display:inline-flex; align-items:center; gap:6px; white-space:nowrap; font-size:11.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#64748b; }
.comercio-info strong .bi{ color:var(--cor-principal); font-size:12px; }
.comercio-info span{ color:#1e293b; overflow-wrap:anywhere; }
:root[data-tema="escuro"] .comercio-info strong{ color:#94a3b8; } :root[data-tema="escuro"] .comercio-info span{ color:#e2e8f0; }

.comercio-divider{
    height:1px;
    background:#eef2f7;
    margin:18px 0;
}

.comercio-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    padding-top:18px;
    border-top:1px solid #eef2f7;
    margin-top:18px;
}

.comercio-contact-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#242A30;
    text-decoration:none;
    font-weight:900;
    transition:.25s;
}

.comercio-contact-btn:hover{
    color:var(--ouro, #C8A02E);
}

.comercio-pagination{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
    margin-top:42px;
}

.comercio-pagination a{
    min-width:48px;
    height:48px;
    padding:0 14px;
    border-radius:15px;
    background:white;
    color:#11151B;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid #e5e7eb;
    text-decoration:none;
    font-weight:900;
    box-shadow:0 10px 24px rgba(0,0,0,.06);
    transition:.25s;
}

.comercio-pagination a.active,
.comercio-pagination a:hover{
    background:#242A30;
    color:white;
    transform:translateY(-3px);
}

.comercio-empty{
    background:white;
    border-radius:28px;
    padding:38px;
    text-align:center;
    color:#64748b;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

@media(max-width:1300px){
    .comercio-grid-insane{
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
}

@media(max-width:1050px){
    .comercio-grid-insane{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .comercio-hero-inner{
        grid-template-columns:1fr;
    }

    .comercio-hero-stat{
        width:max-content;
    }
}

@media(max-width:760px){
    .comercio-hero-insane{
        min-height:400px;
    }

    .comercio-hero-inner{
        padding:50px 0 90px;
    }

    .comercio-hero-insane h1{
        font-size:clamp(28px,8vw,40px);
        letter-spacing:-1px;
    }

    .comercio-hero-insane p{
        font-size:15px;
        line-height:1.5;
        margin:0 0 20px;
    }

    .comercio-main{
        margin-top:-52px;
    }

    .comercio-grid-insane{
        grid-template-columns:1fr;
    }

    .comercio-section-head{
        flex-direction:column;
        align-items:flex-start;
    }

    




.comercio-hero-stat{
    width:240px;
    min-width:0;
    padding:20px 18px;
    margin:10px auto 0;
    border-radius:22px;
}

.comercio-hero-stat strong{
    font-size:42px;
}

.comercio-hero-stat span{
    font-size:15px;
}









    
}












.comercio-tabs a.filtro-comercio{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    color:#334155;
    padding:10px 15px;
    border-radius:999px;
    font-weight:800;
    font-size:13px;
    text-decoration:none;
    transition:.2s ease;
}

.comercio-tabs a.filtro-comercio small{
    background:#e2e8f0;
    color:#475569;
    border-radius:999px;
    padding:2px 8px;
    font-size:11px;
    font-weight:900;
}

.comercio-tabs a.filtro-comercio:hover{
    border-color:var(--cor-principal);
    color:var(--cor-principal);
}

.comercio-tabs a.filtro-comercio.active{
    background:var(--cor-principal);
    border-color:var(--cor-principal);
    color:#fff;
}

.comercio-tabs a.filtro-comercio.active small{
    background:rgba(255,255,255,.22);
    color:#fff;
}

@media(max-width:760px){
    .comercio-tabs{
        flex-wrap:nowrap;
        overflow-x:auto;
        gap:8px;
        padding-bottom:6px;
        margin:0 -4px;
        scrollbar-width:none;
        -webkit-overflow-scrolling:touch;
    }
    .comercio-tabs::-webkit-scrollbar{ display:none; }
    .comercio-tabs a.filtro-comercio{ flex:0 0 auto; padding:8px 12px; }
    .comercio-count{ font-size:14px; padding:9px 14px; }
}



</style>

<main class="comercio-page-insane">

    <section class="comercio-hero-insane">
        <div class="container">
            <div class="comercio-hero-inner">
                <div>
                    <span class="comercio-kicker"><?= te('comercio.hero.kicker') ?></span>
                    <h1><?= te('comercio.hero.titulo') ?></h1>
                    <p>
                        <?= te('comercio.hero.texto') ?>

                    </p>
                </div>

                <aside class="comercio-hero-stat">
                    <strong><?= (int)$total ?></strong>
                    <span><?= te('comercio.hero.contagem') ?></span>
                </aside>
            </div>
        </div>
    </section>

    <section class="container comercio-main" data-zona-ajax="comercio">

        <div class="comercio-intro-panel">
            








<?php if (count($filtrosDisponiveis) > 1): ?>
<nav class="comercio-tabs" aria-label="Filtrar por tipo de estabelecimento">
    <a href="<?= urlComercio('') ?>" class="filtro-comercio<?= $categoriaAtual === '' ? ' active' : '' ?>">
        <i class="bi bi-grid-fill"></i> <?= te('comercio.filtros.todos') ?> <small><?= (int)$total ?></small>
    </a>
    <?php foreach ($filtrosDisponiveis as $chave => $f): ?>
        <a href="<?= urlComercio($chave) ?>" class="filtro-comercio<?= $categoriaAtual === $chave ? ' active' : '' ?>">
            <i class="bi <?= $f[1] ?>"></i> <?= htmlspecialchars($f[0]) ?> <small><?= (int)$f[2] ?></small>
        </a>
    <?php endforeach; ?>
</nav>
<script>
(function () {
    var nav = document.querySelector('.comercio-tabs');
    var ativo = nav && nav.querySelector('.filtro-comercio.active');
    if (ativo && nav.scrollWidth > nav.clientWidth) {
        nav.scrollLeft = ativo.offsetLeft - nav.offsetLeft - 16;
    }
})();
</script>
<?php endif; ?>










            <div class="comercio-count">
                <?= (int)$totalFiltrados ?> estabelecimento<?= (int)$totalFiltrados === 1 ? '' : 's' ?><?= $categoriaAtual !== '' ? ' em ' . htmlspecialchars(mb_strtolower($filtrosDisponiveis[$categoriaAtual][0])) : ' registado' . ((int)$totalFiltrados === 1 ? '' : 's') ?>
            </div>
        </div>

        <div class="comercio-section-head" id="comercio-lista">
            <div>
                <span class="comercio-kicker"><?= te('comercio.lista.kicker') ?></span>
                <h2><?= te('comercio.lista.titulo') ?></h2>
            </div>

            <p><?= te('comercio.lista.texto') ?></p>
        </div>

        <?php if (!empty($comercio)): ?>

            <div class="comercio-grid-insane">

                <?php foreach ($comercio as $c): ?>

                    <article class="comercio-card-insane">

                        <div class="comercio-image">
                            <span class="comercio-type-float">
                                <?= htmlspecialchars($c['tipo'] ?: 'Comércio') ?>
                            </span>

                            <?php if (!empty($c['imagem'])): ?>
                                <img src="/assets/img/<?= htmlspecialchars($c['imagem']) ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
                            <?php else: ?>
                                <div class="comercio-no-img"><?= comercioIcon($c['tipo'] ?? '') ?></div>
                            <?php endif; ?>

                            <div class="comercio-icon-float">
                                <?= comercioIcon($c['tipo'] ?? '') ?>
                            </div>
                        </div>

                        <div class="comercio-body">

                            <h3><?= htmlspecialchars($c['nome']) ?></h3>

                            <div class="comercio-divider"></div>

                            <?php if (!empty($c['morada'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-geo-alt-fill"></i> <?= te('comercio.lista.morada') ?></strong>
                                    <span><?= nl2br(htmlspecialchars($c['morada'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['telefone'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-telephone-fill"></i> <?= te('comercio.lista.telefone') ?></strong>
                                    <span><?= htmlspecialchars($c['telefone']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['email'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-envelope-fill"></i> <?= te('comercio.lista.email') ?></strong>
                                    <span><?= htmlspecialchars($c['email']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['website'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-globe"></i> <?= te('comercio.lista.website') ?></strong>
                                    <span><a href="<?= htmlspecialchars($c['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($c['website']) ?></a></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['outros_contactos'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-info-circle-fill"></i> <?= te('comercio.lista.outros') ?></strong>
                                    <span><?= nl2br(htmlspecialchars($c['outros_contactos'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="comercio-actions">
                                <?php if (!empty($c['telefone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($c['telefone']) ?>" class="comercio-contact-btn">
                                        <i class="bi bi-telephone-fill"></i> <?= te('comercio.lista.ligar') ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($c['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="comercio-contact-btn">
                                        <i class="bi bi-envelope-fill"></i> <?= te('comercio.lista.contactar') ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($c['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($c['facebook']) ?>" target="_blank" rel="noopener" class="comercio-contact-btn">
                                        <i class="bi bi-facebook"></i> Facebook
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($c['instagram'])): ?>
                                    <a href="<?= htmlspecialchars($c['instagram']) ?>" target="_blank" rel="noopener" class="comercio-contact-btn">
                                        <i class="bi bi-instagram"></i> Instagram
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($c['latitude']) && !empty($c['longitude'])): ?>
                                    <a href="https://www.google.com/maps?q=<?= htmlspecialchars($c['latitude']) ?>,<?= htmlspecialchars($c['longitude']) ?>" target="_blank" rel="noopener" class="comercio-contact-btn">
                                        <i class="bi bi-geo-alt-fill"></i> <?= te('comercio.lista.mapa') ?>
                                    </a>
                                <?php elseif (!empty($c['morada'])): ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($c['nome'] . ' ' . $c['morada']) ?>" target="_blank" rel="noopener" class="comercio-contact-btn">
                                        <i class="bi bi-geo-alt-fill"></i> <?= te('comercio.lista.mapa') ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <?php if ($totalPaginas > 1): ?>

                <div class="comercio-pagination">

                    <?php if ($pagina > 1): ?>
                        <a href="<?= urlComercio($categoriaAtual, $pagina - 1) ?>">‹</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                        <a href="<?= urlComercio($categoriaAtual, $i) ?>"
                           class="<?= $i === $pagina ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="<?= urlComercio($categoriaAtual, $pagina + 1) ?>">›</a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="comercio-empty">
                <?= te('comercio.vazio.texto') ?>
            </div>

        <?php endif; ?>

    </section>

</main>





























<?php require_once "includes/footer.php"; ?>
