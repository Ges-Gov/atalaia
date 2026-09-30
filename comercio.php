<?php require_once "includes/header.php"; ?>

<?php
$porPagina = 8;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

$total = (int)$pdo->query("SELECT COUNT(*) FROM comercio_local")->fetchColumn();
$totalPaginas = max(1, ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT *
    FROM comercio_local
    ORDER BY nome ASC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $porPagina, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();

$comercio = $stmt->fetchAll(PDO::FETCH_ASSOC);

$heroImagem = !empty($comercio[0]['imagem'])
    ? "/assets/img/" . $comercio[0]['imagem']
    : "/assets/img/freguesia-1.jpg";

function comercioIcon($tipo)
{
    $tipo = mb_strtolower($tipo);

    if (str_contains($tipo, 'padaria')) return '<i class="bi bi-basket2-fill"></i>';
    if (str_contains($tipo, 'café')) return '<i class="bi bi-cup-hot-fill"></i>';
    if (str_contains($tipo, 'restaurante')) return '<i class="bi bi-egg-fried"></i>';
    if (str_contains($tipo, 'mercearia')) return '<i class="bi bi-bag-fill"></i>';
    if (str_contains($tipo, 'pastelaria')) return '<i class="bi bi-cake2-fill"></i>';
    if (str_contains($tipo, 'mini')) return '<i class="bi bi-cart-fill"></i>';
    if (str_contains($tipo, 'turismo') || str_contains($tipo, 'rural')) return '<i class="bi bi-tree-fill"></i>';

    return '<i class="bi bi-shop"></i>';
}
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
    background:rgba(212,170,0,.15);
    border:1px solid rgba(212,170,0,.42);
    color:#F0D060;
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
    color:#D4AA00;
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
    color:#D4AA00;
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
    color:#D4AA00;
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
        min-height:560px;
    }

    .comercio-hero-inner{
        padding:70px 0 120px;
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












.filtro-comercio{
    cursor:pointer;
    user-select:none;
    transition:.2s ease;
}

.filtro-comercio:hover{
    transform:translateY(-2px);
    background:#eef2ff;
    color:#242A30;
}



</style>

<main class="comercio-page-insane">

    <section class="comercio-hero-insane">
        <div class="container">
            <div class="comercio-hero-inner">
                <div>
                    <span class="comercio-kicker">Economia Local</span>
                    <h1>Economia Local</h1>
                    <p>
                        Descubra estabelecimentos, serviços, cafés, restaurantes
                        e negócios locais. Apoie quem está perto de si.
                    </p>
                </div>

                <aside class="comercio-hero-stat">
                    <strong><?= (int)$total ?></strong>
                    <span>estabelecimentos publicados</span>
                </aside>
            </div>
        </div>
    </section>

    <section class="container comercio-main">

        <div class="comercio-intro-panel">
            








<div class="comercio-tabs">

    <span class="active filtro-comercio" data-filtro="todos">
        <i class="bi bi-shop"></i> Todos
    </span>

    <span class="filtro-comercio" data-filtro="restaurante">
        <i class="bi bi-egg-fried"></i> Restaurantes
    </span>

    <span class="filtro-comercio" data-filtro="café">
        <i class="bi bi-cup-hot-fill"></i> Cafés
    </span>

    <span class="filtro-comercio" data-filtro="comércio">
        <i class="bi bi-cart-fill"></i> Comércio
    </span>

    <span class="filtro-comercio" data-filtro="turismo">
        <i class="bi bi-tree-fill"></i> Turismo Rural
    </span>

</div>










            <div class="comercio-count">
                <?= (int)$total ?> estabelecimento<?= (int)$total === 1 ? '' : 's' ?> registado<?= (int)$total === 1 ? '' : 's' ?>
            </div>
        </div>

        <div class="comercio-section-head">
            <div>
                <span class="comercio-kicker">Economia local</span>
                <h2>O nosso comércio</h2>
            </div>

            <p>Negócios e serviços que dão vida à freguesia.</p>
        </div>

        <?php if (!empty($comercio)): ?>

            <div class="comercio-grid-insane">

                <?php foreach ($comercio as $c): ?>

                    <article
    class="comercio-card-insane"
    data-tipo="<?= mb_strtolower(trim($c['tipo'])) ?>">

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
                                    <strong><i class="bi bi-geo-alt-fill"></i> Morada:</strong>
                                    <span><?= nl2br(htmlspecialchars($c['morada'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['telefone'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-telephone-fill"></i> Telefone:</strong>
                                    <span><?= htmlspecialchars($c['telefone']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['email'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-envelope-fill"></i> Email:</strong>
                                    <span><?= htmlspecialchars($c['email']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['website'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-globe"></i> Website:</strong>
                                    <span><a href="<?= htmlspecialchars($c['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($c['website']) ?></a></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($c['outros_contactos'])): ?>
                                <div class="comercio-info">
                                    <strong><i class="bi bi-info-circle-fill"></i> Outros contactos:</strong>
                                    <span><?= nl2br(htmlspecialchars($c['outros_contactos'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="comercio-actions">
                                <?php if (!empty($c['telefone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($c['telefone']) ?>" class="comercio-contact-btn">
                                        <i class="bi bi-telephone-fill"></i> Ligar
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($c['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="comercio-contact-btn">
                                        <i class="bi bi-envelope-fill"></i> Contactar
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
                                        <i class="bi bi-geo-alt-fill"></i> Ver no mapa
                                    </a>
                                <?php elseif (!empty($c['morada'])): ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($c['nome'] . ' ' . $c['morada']) ?>" target="_blank" rel="noopener" class="comercio-contact-btn">
                                        <i class="bi bi-geo-alt-fill"></i> Ver no mapa
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
                        <a href="?pagina=<?= $pagina - 1 ?>">‹</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                        <a href="?pagina=<?= $i ?>"
                           class="<?= $i === $pagina ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?>">›</a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="comercio-empty">
                Ainda não existem estabelecimentos publicados.
            </div>

        <?php endif; ?>

    </section>

</main>















<script>

document.addEventListener('DOMContentLoaded', function(){

    const filtros = document.querySelectorAll('.filtro-comercio');
    const cards = document.querySelectorAll('.comercio-card-insane');

    filtros.forEach(btn => {

        btn.addEventListener('click', function(){

            filtros.forEach(f =>
                f.classList.remove('active')
            );

            this.classList.add('active');

            const filtro =
                this.dataset.filtro.toLowerCase();

            cards.forEach(card => {

                const tipo =
                    card.dataset.tipo.toLowerCase();

                if(filtro === 'todos'){
                    card.style.display = '';
                }
                else{
                    card.style.display =
                        tipo.includes(filtro)
                        ? ''
                        : 'none';
                }

            });

        });

    });

});

</script>













<?php require_once "includes/footer.php"; ?>
