<?php require_once "includes/header.php"; ?>

<?php
$porPagina = 6;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

$totalNoticias = (int)$pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$totalPaginas = max(1, ceil($totalNoticias / $porPagina));

$stmt = $pdo->prepare("
    SELECT *
    FROM noticias
    ORDER BY data DESC, id DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $porPagina, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$heroImagem = !empty($noticias[0]['imagem'])
    ? "/assets/img/" . $noticias[0]['imagem']
    : "/assets/img/freguesia-1.jpg";
?>

<style>
.noticias-page-clean{
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 34%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 52%, #f7f4ef 100%);
    padding-bottom:70px;
}

.noticias-hero-clean{
    position:relative;
    overflow:hidden;
    color:white;
    min-height:360px;
    display:flex;
    align-items:center;
    background:
        linear-gradient(90deg, rgba(17,21,28,.94), rgba(17,21,28,.62), rgba(17,21,28,.78)),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.noticias-hero-clean::before{
    content:"";
    position:absolute;
    inset:0;
    background:
        radial-gradient(circle at right top, rgba(255,255,255,.11), transparent 34%),
        radial-gradient(circle at left bottom, rgba(212,170,0,.11), transparent 28%);
}

.noticias-hero-clean::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:110px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}

.noticias-hero-clean .container{
    position:relative;
    z-index:2;
}

.noticias-kicker-clean{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(212,170,0,.14);
    border:1px solid rgba(212,170,0,.42);
    color:#F0D060;
    padding:9px 15px;
    border-radius:999px;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    margin-bottom:18px;
}

.noticias-hero-clean h1{
    font-size:clamp(44px,6vw,76px);
    line-height:1.02;
    margin:0 0 18px;
    letter-spacing:-2px;
    max-width:850px;
}

.noticias-hero-clean p{
    font-size:20px;
    line-height:1.8;
    color:#dbeafe;
    max-width:760px;
    margin:0;
}

.noticias-tools{
    margin-top:-38px;
    position:relative;
    z-index:5;
}

.noticias-tools-inner{
    background:white;
    border:1px solid #eef2f7;
    box-shadow:0 18px 45px rgba(0,0,0,.10);
    border-radius:24px;
    padding:18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:18px;
    flex-wrap:wrap;
}

.news-tabs{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.news-tabs span{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    color:#334155;
    padding:11px 16px;
    border-radius:999px;
    font-weight:900;
    font-size:13px;
}

.news-tabs span.active{
    background:#242A30;
    color:white;
    border-color:#242A30;
}

.news-count{
    color:#64748b;
    font-weight:800;
    font-size:14px;
}

.noticias-list-section{
    padding:42px 0 0;
}

.noticias-clean-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:26px;
}

.noticia-clean-card{
    background:white;
    border-radius:28px;
    overflow:hidden;
    border:1px solid #eef2f7;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
    transition:.3s ease;
    position:relative;
    display:flex;
    flex-direction:column;
    min-height:100%;
}

.noticia-clean-card:hover{
    transform:translateY(-8px);
    box-shadow:0 28px 70px rgba(0,0,0,.14);
}

.noticia-clean-image{
    height:260px;
    position:relative;
    overflow:hidden;
    background:#e5e7eb;
}

.noticia-clean-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.45s ease;
}

.noticia-clean-card:hover .noticia-clean-image img{
    transform:scale(1.07);
}

.noticia-clean-image::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.05),rgba(0,0,0,.16));
}

.noticia-clean-badge{
    position:absolute;
    top:18px;
    left:18px;
    z-index:2;
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(36,42,50,.94);
    color:white;
    padding:9px 13px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.6px;
    backdrop-filter:blur(10px);
}

.noticia-clean-noimg{
    width:100%;
    height:100%;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:#D4AA00;
    font-size:54px;
    font-weight:900;
}

.noticia-clean-body{
    padding:28px;
    display:flex;
    flex-direction:column;
    flex:1;
}

.noticia-clean-date{
    display:flex;
    align-items:center;
    gap:8px;
    color:#64748b;
    font-weight:800;
    font-size:13px;
    margin-bottom:14px;
}

.noticia-clean-body h2{
    color:#11151B;
    font-size:25px;
    line-height:1.25;
    letter-spacing:-.5px;
    margin:0 0 14px;
}

.noticia-clean-body p{
    color:#52606d;
    line-height:1.8;
    margin:0 0 24px;
    font-size:15.5px;
    flex:1;
}

.noticia-clean-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    width:max-content;
    color:#242A30;
    text-decoration:none;
    font-weight:900;
    transition:.25s;
}

.noticia-clean-link::after{
    content:"→";
    transition:.25s;
}

.noticia-clean-link:hover{
    color:#D4AA00;
}

.noticia-clean-link:hover::after{
    transform:translateX(5px);
}

.noticias-pagination-clean{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:42px;
    flex-wrap:wrap;
}

.noticias-pagination-clean a{
    min-width:46px;
    height:46px;
    padding:0 15px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:white;
    color:#11151B;
    text-decoration:none;
    border:1px solid #e5e7eb;
    border-radius:14px;
    font-weight:900;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
    transition:.25s;
}

.noticias-pagination-clean a:hover,
.noticias-pagination-clean a.active{
    background:#242A30;
    color:white;
    transform:translateY(-3px);
}

.noticias-empty-clean{
    background:white;
    padding:34px;
    border-radius:24px;
    text-align:center;
    color:#64748b;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

@media(max-width:1150px){
    .noticias-clean-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:760px){
    .noticias-hero-clean{
        min-height:420px;
    }

    .noticias-clean-grid{
        grid-template-columns:1fr;
    }

    .noticias-tools{
        margin-top:-25px;
    }

    .noticias-tools-inner{
        align-items:flex-start;
    }

    .noticia-clean-image{
        height:240px;
    }
}
</style>

<main class="noticias-page-clean">

    <section class="noticias-hero-clean">
        <div class="container">
            <span class="noticias-kicker-clean">Atualidade</span>
            <h1>Todas as notícias</h1>
            <p>
                Acompanhe novidades, eventos, projetos e informações importantes
                da freguesia.
            </p>
        </div>
    </section>

    <section class="noticias-tools">
        <div class="container">
            <div class="noticias-tools-inner">
                <div class="news-tabs">
                    <span class="active"><i class="bi bi-newspaper"></i> Todas as notícias</span>
                    <span><i class="bi bi-pin-angle-fill"></i> Avisos</span>
                    <span><i class="bi bi-calendar-event-fill"></i> Eventos</span>
                    <span><i class="bi bi-stars"></i> Projetos</span>
                </div>

                <div class="news-count">
                    <?= (int)$totalNoticias ?> notícia<?= (int)$totalNoticias === 1 ? '' : 's' ?> publicada<?= (int)$totalNoticias === 1 ? '' : 's' ?>
                </div>
            </div>
        </div>
    </section>

    <section class="noticias-list-section">
        <div class="container">

            <?php if (!empty($noticias)): ?>

                <div class="noticias-clean-grid">

                    <?php foreach ($noticias as $n): ?>

                        <article class="noticia-clean-card">

                            <div class="noticia-clean-image">
                                <span class="noticia-clean-badge"><?= !empty($n['categoria']) ? htmlspecialchars($n['categoria']) : 'Notícia' ?></span>

                                <?php if (!empty($n['imagem'])): ?>
                                    <img src="/assets/img/<?= htmlspecialchars($n['imagem']) ?>" alt="<?= htmlspecialchars($n['titulo']) ?>" style="object-position:<?= (int)($n['imagem_foco_x'] ?? 50) ?>% <?= (int)($n['imagem_foco_y'] ?? 50) ?>%">
                                <?php else: ?>
                                    <div class="noticia-clean-noimg"><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="noticia-clean-body">
                                <div class="noticia-clean-date">
                                    <i class="bi bi-calendar-event-fill"></i> <?= date('d/m/Y \à\s H:i', strtotime($n['data'])) ?>
                                </div>

                                <h2><?= htmlspecialchars($n['titulo']) ?></h2>

                                <p>
                                    <?= htmlspecialchars(mb_substr(strip_tags($n['descricao'] ?? ''), 0, 165)) ?>...
                                </p>

                                <a href="/noticia.php?id=<?= (int)$n['id'] ?>" class="noticia-clean-link">
                                    Ler mais
                                </a>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <?php if ($totalPaginas > 1): ?>
                    <div class="noticias-pagination-clean">

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

                <div class="noticias-empty-clean">
                    Ainda não existem notícias publicadas.
                </div>

            <?php endif; ?>

        </div>
    </section>

</main>

<?php require_once "includes/footer.php"; ?>
