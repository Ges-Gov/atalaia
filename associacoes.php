<?php require_once "includes/header.php"; ?>

<?php
$porPagina = 6;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

$totalAssociacoes = (int)$pdo->query("SELECT COUNT(*) FROM associacoes")->fetchColumn();
$totalPaginas = max(1, ceil($totalAssociacoes / $porPagina));

$stmt = $pdo->prepare("
    SELECT *
    FROM associacoes
    ORDER BY nome ASC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $porPagina, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();

$associacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$heroImagem = !empty($associacoes[0]['imagem'])
    ? "/assets/img/" . $associacoes[0]['imagem']
    : "/assets/img/freguesia-1.jpg";

function getAssociacaoIcon($nome)
{
    $nome = mb_strtolower($nome);

    if (str_contains($nome, 'desportivo')) return '<i class="bi bi-trophy-fill"></i>';
    if (str_contains($nome, 'cultural')) return '<i class="bi bi-music-note-beamed"></i>';
    if (str_contains($nome, 'caçadores')) return '<i class="bi bi-shield-fill"></i>';
    if (str_contains($nome, 'serra')) return '<i class="bi bi-compass-fill"></i>';

    return '<i class="bi bi-people-fill"></i>';
}
?>

<style>
.associacoes-page-insane{
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 34%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 54%, #f7f4ef 100%);
    padding-bottom:70px;
}

.associacoes-hero-insane{
    position:relative;
    min-height:590px;
    overflow:hidden;
    display:flex;
    align-items:center;
    color:white;
    background:
        linear-gradient(90deg, rgba(17,21,28,.91), rgba(17,21,28,.50)),
        linear-gradient(0deg, rgba(0,0,0,.40), transparent 58%),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.associacoes-hero-insane::before{
    content:"";
    position:absolute;
    width:620px;
    height:620px;
    border-radius:50%;
    right:-220px;
    top:-260px;
    background:rgba(255,255,255,.06);
}

.associacoes-hero-insane::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:145px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}

.associacoes-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1fr auto;
    gap:38px;
    align-items:center;
    padding:90px 0 135px;
}

.assoc-kicker{
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

.associacoes-hero-insane h1{
    font-size:clamp(46px,7vw,84px);
    line-height:.98;
    margin:0 0 20px;
    letter-spacing:-2px;
}

.associacoes-hero-insane p{
    color:#dbeafe;
    font-size:21px;
    line-height:1.8;
    max-width:760px;
    margin:0;
}

.assoc-hero-stat{
    min-width:190px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.20);
    backdrop-filter:blur(16px);
    border-radius:28px;
    padding:28px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.20);
}

.assoc-hero-stat strong{
    display:block;
    color:#D4AA00;
    font-size:56px;
    line-height:1;
    margin-bottom:8px;
}

.assoc-hero-stat span{
    color:#dbeafe;
    font-weight:900;
}

.assoc-main{
    position:relative;
    z-index:5;
    margin-top:-78px;
}

.assoc-intro-panel{
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

.assoc-tabs{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.assoc-tabs span{
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

.assoc-tabs span.active{
    background:#242A30;
    color:white;
    border-color:#242A30;
}

.assoc-count{
    background:#eff6ff;
    color:#242A30;
    padding:12px 16px;
    border-radius:999px;
    font-weight:900;
}

.assoc-section-head{
    display:flex;
    align-items:end;
    justify-content:space-between;
    gap:20px;
    margin-bottom:26px;
}

.assoc-section-head h2{
    color:#11151B;
    margin:8px 0 0;
    font-size:clamp(32px,4vw,48px);
    line-height:1.1;
    letter-spacing:-1px;
}

.assoc-section-head p{
    color:#64748b;
    margin:0;
    font-weight:700;
}

.assoc-grid-insane{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:26px;
}

.assoc-card-insane{
    background:white;
    border:1px solid #eef2f7;
    border-radius:32px;
    overflow:hidden;
    box-shadow:0 18px 50px rgba(0,0,0,.09);
    transition:.32s ease;
    position:relative;
}

.assoc-card-insane:hover{
    transform:translateY(-10px);
    box-shadow:0 32px 80px rgba(0,0,0,.15);
}

.assoc-image{
    height:290px;
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg,#242A30,#11151B);
}

.assoc-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.5s ease;
}

.assoc-card-insane:hover .assoc-image img{
    transform:scale(1.08);
}

.assoc-image::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.04),rgba(17,21,28,.55));
}

.assoc-no-img{
    width:100%;
    height:100%;
    display:grid;
    place-items:center;
    color:#D4AA00;
    font-size:74px;
    font-weight:900;
}

.assoc-category-float{
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

.assoc-icon-float{
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

.assoc-body{
    padding:28px;
}

.assoc-body h3{
    color:#11151B;
    font-size:27px;
    line-height:1.2;
    margin:0 0 14px;
    letter-spacing:-.6px;
}

.assoc-location{
    display:flex;
    gap:8px;
    align-items:flex-start;
    color:#242A30;
    font-weight:900;
    margin-bottom:16px;
    font-size:14px;
}

.assoc-desc{
    color:#52606d;
    line-height:1.8;
    margin:0 0 20px;
    font-size:15.5px;
}

.assoc-divider{
    height:1px;
    background:#eef2f7;
    margin:18px 0;
}

.assoc-info{
    display:flex;
    gap:8px;
    align-items:flex-start;
    color:#475569;
    font-size:14px;
    line-height:1.6;
    margin-bottom:9px;
    word-break:break-word;
}

.assoc-info strong{
    color:#11151B;
}

.assoc-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    padding-top:18px;
    border-top:1px solid #eef2f7;
    margin-top:18px;
}

.assoc-contact-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#242A30;
    text-decoration:none;
    font-weight:900;
    transition:.25s;
}

.assoc-contact-btn:hover{
    color:#D4AA00;
}

.assoc-pagination{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
    margin-top:42px;
}

.assoc-pagination a{
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

.assoc-pagination a.active,
.assoc-pagination a:hover{
    background:#242A30;
    color:white;
    transform:translateY(-3px);
}

.assoc-empty{
    background:white;
    border-radius:28px;
    padding:38px;
    text-align:center;
    color:#64748b;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

@media(max-width:1120px){
    .assoc-grid-insane{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .associacoes-hero-inner{
        grid-template-columns:1fr;
    }

    .assoc-hero-stat{
        width:max-content;
    }
}

@media(max-width:760px){
    .associacoes-hero-insane{
        min-height:560px;
    }

    .associacoes-hero-inner{
        padding:70px 0 120px;
    }

    .assoc-main{
        margin-top:-52px;
    }

    .assoc-grid-insane{
        grid-template-columns:1fr;
    }

    .assoc-section-head{
        flex-direction:column;
        align-items:flex-start;
    }

    














.assoc-hero-stat{
    width:240px;
    min-width:0;
    padding:20px 18px;
    margin:10px auto 0;
    border-radius:22px;
}

.assoc-hero-stat strong{
    font-size:42px;
}

.assoc-hero-stat span{
    font-size:15px;
}













    
}
</style>

<main class="associacoes-page-insane">

    <section class="associacoes-hero-insane">
        <div class="container">
            <div class="associacoes-hero-inner">
                <div>
                    <span class="assoc-kicker">Comunidade e Movimento Associativo</span>
                    <h1>Associações da Freguesia</h1>
                    <p>
                        Conheça as associações, coletividades e grupos que dinamizam
                        a vida cultural, social, desportiva e comunitária da freguesia.
                    </p>
                </div>

                <aside class="assoc-hero-stat">
                    <strong><?= (int)$totalAssociacoes ?></strong>
                    <span>associações publicadas</span>
                </aside>
            </div>
        </div>
    </section>

    <section class="container assoc-main">

        <div class="assoc-intro-panel">
            <div class="assoc-tabs">
                <span class="active"><i class="bi bi-people-fill"></i> Todas</span>
                <span><i class="bi bi-music-note-beamed"></i> Cultura</span>
                <span><i class="bi bi-trophy-fill"></i> Desporto</span>
                <span><i class="bi bi-people-fill"></i> Comunidade</span>
            </div>

            <div class="assoc-count">
                <?= (int)$totalAssociacoes ?> associação<?= (int)$totalAssociacoes === 1 ? '' : 'ões' ?> registada<?= (int)$totalAssociacoes === 1 ? '' : 's' ?>
            </div>
        </div>

        <div class="assoc-section-head">
            <div>
                <span class="hero-btn">Movimento associativo</span>
                <h2>Juntos fazemos mais</h2>
            </div>

            <p>Entidades que dão vida à freguesia.</p>
        </div>

        <?php if (!empty($associacoes)): ?>

            <div class="assoc-grid-insane">

                <?php foreach ($associacoes as $a): ?>

                    <article class="assoc-card-insane">

                        <div class="assoc-image">
                            <span class="assoc-category-float">Associação</span>

                            <?php if (!empty($a['imagem'])): ?>
                                <img src="/assets/img/<?= htmlspecialchars($a['imagem']) ?>" alt="<?= htmlspecialchars($a['nome']) ?>">
                            <?php else: ?>
                                <div class="assoc-no-img"><?= getAssociacaoIcon($a['nome']) ?></div>
                            <?php endif; ?>

                            <div class="assoc-icon-float">
                                <?= getAssociacaoIcon($a['nome']) ?>
                            </div>
                        </div>

                        <div class="assoc-body">

                            <h3><?= htmlspecialchars($a['nome']) ?></h3>

                            <?php if (!empty($a['morada'])): ?>
                                <div class="assoc-location">
                                    <span><i class="bi bi-geo-alt-fill"></i></span>
                                    <span><?= htmlspecialchars($a['morada']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($a['descricao'])): ?>
                                <p class="assoc-desc">
                                    <?= htmlspecialchars(mb_substr(strip_tags($a['descricao']), 0, 145)) ?><?= mb_strlen(strip_tags($a['descricao'])) > 145 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>

                            <div class="assoc-divider"></div>

                            <?php if (!empty($a['email'])): ?>
                                <div class="assoc-info">
                                    <strong>Email:</strong>
                                    <span><?= htmlspecialchars($a['email']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($a['telefone'])): ?>
                                <div class="assoc-info">
                                    <strong>Telefone:</strong>
                                    <span><?= htmlspecialchars($a['telefone']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($a['website'])): ?>
                                <div class="assoc-info">
                                    <strong>Website:</strong>
                                    <span><a href="<?= htmlspecialchars($a['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($a['website']) ?></a></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($a['outros_contactos'])): ?>
                                <div class="assoc-info">
                                    <strong>Outros contactos:</strong>
                                    <span><?= nl2br(htmlspecialchars($a['outros_contactos'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="assoc-actions">
                                <?php if (!empty($a['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($a['email']) ?>" class="assoc-contact-btn">
                                        <i class="bi bi-envelope-fill"></i> Contactar
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($a['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($a['facebook']) ?>" target="_blank" rel="noopener" class="assoc-contact-btn">
                                        <i class="bi bi-facebook"></i> Facebook
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($a['instagram'])): ?>
                                    <a href="<?= htmlspecialchars($a['instagram']) ?>" target="_blank" rel="noopener" class="assoc-contact-btn">
                                        <i class="bi bi-instagram"></i> Instagram
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($a['latitude']) && !empty($a['longitude'])): ?>
                                    <a href="https://www.google.com/maps?q=<?= htmlspecialchars($a['latitude']) ?>,<?= htmlspecialchars($a['longitude']) ?>" target="_blank" rel="noopener" class="assoc-contact-btn">
                                        <i class="bi bi-geo-alt-fill"></i> Ver no mapa
                                    </a>
                                <?php elseif (!empty($a['morada'])): ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($a['nome'] . ' ' . $a['morada']) ?>" target="_blank" rel="noopener" class="assoc-contact-btn">
                                        <i class="bi bi-geo-alt-fill"></i> Ver no mapa
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <?php if ($totalPaginas > 1): ?>

                <div class="assoc-pagination">

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

            <div class="assoc-empty">
                Ainda não existem associações publicadas.
            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once "includes/footer.php"; ?>
