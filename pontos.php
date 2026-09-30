<?php require_once "includes/header.php"; ?>

<?php
$porPagina = 6;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

$totalPontos = (int)$pdo->query("SELECT COUNT(*) FROM pontos_interesse")->fetchColumn();
$totalPaginas = max(1, ceil($totalPontos / $porPagina));

$stmt = $pdo->prepare("
    SELECT *
    FROM pontos_interesse
    ORDER BY nome ASC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $porPagina, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$heroImagem = !empty($pontos[0]['imagem'])
    ? "/assets/img/" . $pontos[0]['imagem']
    : "/assets/img/freguesia-1.jpg";
?>

<style>
.pontos-page-insane{
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 34%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 54%, #f7f4ef 100%);
    padding-bottom:70px;
}

.pontos-hero-insane{
    position:relative;
    min-height:620px;
    overflow:hidden;
    display:flex;
    align-items:center;
    color:white;
    background:
        linear-gradient(90deg, rgba(17,21,28,.90), rgba(17,21,28,.45)),
        linear-gradient(0deg, rgba(0,0,0,.42), transparent 60%),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.pontos-hero-insane::before{
    content:"";
    position:absolute;
    width:620px;
    height:620px;
    border-radius:50%;
    right:-220px;
    top:-260px;
    background:rgba(255,255,255,.06);
}

.pontos-hero-insane::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:150px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}

.pontos-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1fr auto;
    gap:38px;
    align-items:center;
    padding:95px 0 140px;
}

.pontos-kicker{
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

.pontos-hero-insane h1{
    font-size:clamp(48px,7vw,88px);
    line-height:.98;
    margin:0 0 20px;
    letter-spacing:-2px;
}

.pontos-hero-insane p{
    color:#dbeafe;
    font-size:21px;
    line-height:1.8;
    max-width:760px;
    margin:0 0 32px;
}

.pontos-hero-actions{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
}

.pontos-hero-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:16px 24px;
    border-radius:16px;
    text-decoration:none;
    font-weight:900;
    transition:.25s ease;
}

.pontos-hero-btn.primary{
    background:var(--cor-secundaria);
    color:#11151B;
    box-shadow:0 18px 45px rgba(0,0,0,.25);
}

.pontos-hero-btn.secondary{
    background:rgba(255,255,255,.14);
    color:white;
    border:1px solid rgba(255,255,255,.25);
    backdrop-filter:blur(12px);
}

.pontos-hero-btn:hover{
    transform:translateY(-4px);
}

.pontos-floating-stat{
    min-width:190px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.20);
    backdrop-filter:blur(16px);
    border-radius:28px;
    padding:28px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.20);
}

.pontos-floating-stat strong{
    display:block;
    color:#D4AA00;
    font-size:56px;
    line-height:1;
    margin-bottom:8px;
}

.pontos-floating-stat span{
    color:#dbeafe;
    font-weight:900;
}

.pontos-main{
    position:relative;
    z-index:5;
    margin-top:-78px;
}

.pontos-intro-panel{
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

.pontos-tabs{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.pontos-tabs span{
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

.pontos-tabs span.active{
    background:#242A30;
    color:white;
    border-color:#242A30;
}

.pontos-count{
    background:#eff6ff;
    color:#242A30;
    padding:12px 16px;
    border-radius:999px;
    font-weight:900;
}

.pontos-section-head{
    display:flex;
    align-items:end;
    justify-content:space-between;
    gap:20px;
    margin-bottom:26px;
}

.pontos-section-head h2{
    color:#11151B;
    margin:8px 0 0;
    font-size:clamp(32px,4vw,48px);
    line-height:1.1;
    letter-spacing:-1px;
}

.pontos-section-head p{
    color:#64748b;
    margin:0;
    font-weight:700;
}

.pontos-grid-insane{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:26px;
}

.ponto-card-insane{
    background:white;
    border:1px solid #eef2f7;
    border-radius:32px;
    overflow:hidden;
    box-shadow:0 18px 50px rgba(0,0,0,.09);
    transition:.32s ease;
    position:relative;
}

.ponto-card-insane:hover{
    transform:translateY(-10px);
    box-shadow:0 32px 80px rgba(0,0,0,.15);
}

.ponto-image-insane{
    height:290px;
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg,#242A30,#11151B);
}

.ponto-image-insane img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.5s ease;
}

.ponto-card-insane:hover .ponto-image-insane img{
    transform:scale(1.08);
}

.ponto-image-insane::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.04),rgba(17,21,28,.50));
}

.ponto-sem-img-insane{
    width:100%;
    height:100%;
    display:grid;
    place-items:center;
    color:#D4AA00;
    font-size:74px;
    font-weight:900;
}

.ponto-category-float{
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

.ponto-pin-float{
    position:absolute;
    right:18px;
    bottom:18px;
    z-index:2;
    width:52px;
    height:52px;
    border-radius:18px;
    display:grid;
    place-items:center;
    background:var(--cor-secundaria);
    color:#11151B;
    font-size:24px;
    box-shadow:0 14px 34px rgba(0,0,0,.28);
}

.ponto-body-insane{
    padding:28px;
}

.ponto-body-insane h3{
    color:#11151B;
    font-size:27px;
    line-height:1.2;
    margin:0 0 12px;
    letter-spacing:-.6px;
}

.ponto-location{
    display:flex;
    gap:8px;
    align-items:flex-start;
    color:#242A30;
    font-weight:900;
    margin-bottom:16px;
    font-size:14px;
}

.ponto-body-insane p{
    color:#52606d;
    line-height:1.8;
    margin:0 0 22px;
    font-size:15.5px;
}

.ponto-actions-insane{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    padding-top:18px;
    border-top:1px solid #eef2f7;
}

.ponto-link-insane{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#242A30;
    text-decoration:none;
    font-weight:900;
    transition:.25s;
}

.ponto-link-insane::after{
    content:"→";
    transition:.25s;
}

.ponto-link-insane:hover{
    color:#D4AA00;
}

.ponto-link-insane:hover::after{
    transform:translateX(5px);
}

.ponto-mini-tag{
    color:#94a3b8;
    font-size:13px;
    font-weight:800;
}

.pontos-pagination{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
    margin-top:42px;
}

.pontos-pagination a{
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

.pontos-pagination a.active,
.pontos-pagination a:hover{
    background:#242A30;
    color:white;
    transform:translateY(-3px);
}

.pontos-empty{
    background:white;
    border-radius:28px;
    padding:38px;
    text-align:center;
    color:#64748b;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

@media(max-width:1120px){
    .pontos-grid-insane{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .pontos-hero-inner{
        grid-template-columns:1fr;
    }

    .pontos-floating-stat{
        width:max-content;
    }
}

@media(max-width:760px){
    .pontos-hero-insane{
        min-height:580px;
    }

    .pontos-hero-inner{
        padding:70px 0 120px;
    }

    .pontos-main{
        margin-top:-52px;
    }

    .pontos-grid-insane{
        grid-template-columns:1fr;
    }

    .pontos-section-head{
        flex-direction:column;
        align-items:flex-start;
    }

    .pontos-floating-stat{
        width:100%;
    }

    .pontos-hero-actions{
        flex-direction:column;
        align-items:center;
    }

    .pontos-hero-btn{
        justify-content:center;
         width:220px;
    }












.pontos-floating-stat{
    width:240px;
    min-width:0;
    padding:20px 18px;
    margin:8px auto 0;
    border-radius:22px;
}

.pontos-floating-stat strong{
    font-size:42px;
}

.pontos-floating-stat span{
    font-size:15px;
}









    
}
</style>

<main class="pontos-page-insane">

    <section class="pontos-hero-insane">
        <div class="container">
            <div class="pontos-hero-inner">
                <div>
                    <span class="pontos-kicker">Património e Cultura</span>
                    <h1>Pontos de Interesse</h1>
                    <p>
                        Descubra o património, os espaços naturais, a cultura
                        e os locais mais marcantes da freguesia.
                    </p>

                    <div class="pontos-hero-actions">
                        <a href="#lista-pontos" class="pontos-hero-btn primary">Explorar locais</a>
                        <a href="/mapa.php" class="pontos-hero-btn secondary">Ver no mapa</a>
                    </div>
                </div>

                <aside class="pontos-floating-stat">
                    <strong><?= (int)$totalPontos ?></strong>
                    <span>locais para descobrir</span>
                </aside>
            </div>
        </div>
    </section>

    <section class="container pontos-main" id="lista-pontos">

        <div class="pontos-intro-panel">
            <div class="pontos-tabs">
                <span class="active"><i class="bi bi-geo-alt-fill"></i> Todos</span>
                <span><i class="bi bi-bank2"></i> Património</span>
                <span><i class="bi bi-tree-fill"></i> Natureza</span>
                <span><i class="bi bi-stars"></i> Cultura</span>
            </div>

            <div class="pontos-count">
                <?= (int)$totalPontos ?> ponto<?= (int)$totalPontos === 1 ? '' : 's' ?> publicado<?= (int)$totalPontos === 1 ? '' : 's' ?>
            </div>
        </div>

        <div class="pontos-section-head">
            <div>
                <span class="pontos-kicker">Explorar</span>
                <h2>Todos os pontos de interesse</h2>
            </div>

            <p>Uma seleção de locais com identidade, história e valor comunitário.</p>
        </div>

        <?php if (!empty($pontos)): ?>

            <div class="pontos-grid-insane">

                <?php foreach ($pontos as $p): ?>

                    <article class="ponto-card-insane">

                        <div class="ponto-image-insane">
                            <span class="ponto-category-float">Local</span>

                            <?php if (!empty($p['imagem'])): ?>
                                <img src="/assets/img/<?= htmlspecialchars($p['imagem']) ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                            <?php else: ?>
                                <div class="ponto-sem-img-insane">⌂</div>
                            <?php endif; ?>

                            <div class="ponto-pin-float"><i class="bi bi-geo-alt-fill"></i></div>
                        </div>

                        <div class="ponto-body-insane">

                            <h3><?= htmlspecialchars($p['nome']) ?></h3>

                            <?php if (!empty($p['localizacao'])): ?>
                                <div class="ponto-location">
                                    <span><i class="bi bi-geo-alt-fill"></i></span>
                                    <span><?= htmlspecialchars($p['localizacao']) ?></span>
                                </div>
                            <?php endif; ?>

                            <p>
                                <?= htmlspecialchars(mb_substr(strip_tags($p['descricao'] ?? ''), 0, 150)) ?><?= mb_strlen(strip_tags($p['descricao'] ?? '')) > 150 ? '...' : '' ?>
                            </p>

                            <div class="ponto-actions-insane">
                               



<a href="/ponto.php?id=<?= $p['id'] ?>" class="ponto-link-insane">
    Ver local
</a>

                               
                                <span class="ponto-mini-tag">AAEJ</span>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <?php if ($totalPaginas > 1): ?>
                <div class="pontos-pagination">

                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?= $pagina - 1 ?>">‹</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>" class="<?= $i === $pagina ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?>">›</a>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        <?php else: ?>

            <div class="pontos-empty">
                Ainda não existem pontos de interesse publicados.
            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once "includes/footer.php"; ?>
