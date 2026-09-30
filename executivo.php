<?php require_once "includes/header.php"; ?>
<?php require_once __DIR__ . "/includes/membros.php"; ?>

<?php
$stmt = $pdo->query("
    SELECT *
    FROM executivo_membros
    WHERE ativo = 1
    ORDER BY ordem ASC, id DESC
");
$membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$presidente = null;

foreach ($membros as $m) {
    if (
        stripos($m['cargo'], 'presidente') !== false ||
        stripos($m['cargo'], 'president') !== false
    ) {
        $presidente = $m;
        break;
    }
}

if (!$presidente && !empty($membros)) {
    $presidente = $membros[0];
}

$totalMembros = count($membros);
$totalVogais = max(0, $totalMembros - (!empty($presidente) ? 1 : 0));
?>

<style>
.executivo-page-premium{
    background:radial-gradient(circle at top left,rgba(36,42,50,.08),transparent 34%),linear-gradient(180deg,#f8fafc 0%,#fff 54%,#f7f4ef 100%);
    padding-bottom:70px;
}
.executivo-hero-premium{
    position:relative;
    overflow:hidden;
    min-height:470px;
    display:flex;
    align-items:center;
    color:white;
    background:radial-gradient(circle at right top,rgba(255,255,255,.10),transparent 32%),linear-gradient(135deg,#242A30,#11151B);
}
.executivo-hero-premium::before{
    content:"";
    position:absolute;
    width:620px;
    height:620px;
    border-radius:50%;
    right:-220px;
    top:-260px;
    background:rgba(255,255,255,.055);
}
.executivo-hero-premium::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:120px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}
.executivo-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1fr auto;
    gap:40px;
    align-items:center;
    padding:80px 0 130px;
}
.executivo-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(212,170,0,.14);
    border:1px solid rgba(212,170,0,.40);
    color:#F0D060;
    border-radius:999px;
    padding:10px 16px;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.9px;
    margin-bottom:18px;
}
.executivo-hero-premium h1{
    font-size:clamp(44px,6vw,78px);
    line-height:1;
    letter-spacing:-2px;
    margin:0 0 18px;
}
.executivo-hero-premium p{
    color:#dbeafe;
    font-size:20px;
    line-height:1.8;
    max-width:760px;
    margin:0;
}
.executivo-hero-stats{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
}
.executivo-hero-stat{
    min-width:145px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    backdrop-filter:blur(12px);
    border-radius:22px;
    padding:22px;
    text-align:center;
}
.executivo-hero-stat strong{
    display:block;
    font-size:40px;
    color:#D4AA00;
    line-height:1;
    margin-bottom:7px;
}
.executivo-hero-stat span{
    color:#dbeafe;
    font-weight:800;
    font-size:13px;
}
.executivo-main{
    position:relative;
    z-index:5;
    margin-top:-78px;
}
.executivo-presidente-premium{
    background:white;
    border-radius:36px;
    box-shadow:0 28px 80px rgba(0,0,0,.14);
    border:1px solid rgba(226,232,240,.9);
    overflow:hidden;
    display:grid;
    grid-template-columns:420px 1fr;
    margin-bottom:40px;
    position:relative;
}
.executivo-presidente-premium::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:7px;
    background:linear-gradient(90deg,var(--cor-principal),var(--cor-secundaria));
    z-index:3;
}
.executivo-presidente-photo-premium{
    min-height:520px;
    background:linear-gradient(135deg,#242A30,#11151B);
    position:relative;
    overflow:hidden;
}
.executivo-presidente-photo-premium img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.5s ease;
}
.executivo-presidente-premium:hover .executivo-presidente-photo-premium img{
    transform:scale(1.04);
}
.executivo-presidente-photo-premium::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(0deg,rgba(17,21,28,.42),transparent 48%);
}
.executivo-avatar-placeholder{
    width:100%;
    height:100%;
    display:grid;
    place-items:center;
    color:#D4AA00;
    font-size:120px;
    font-weight:900;
}
.executivo-presidente-label{
    position:absolute;
    left:24px;
    bottom:24px;
    z-index:2;
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.24);
    color:white;
    backdrop-filter:blur(12px);
    border-radius:18px;
    padding:12px 15px;
    font-weight:900;
}
.executivo-presidente-info-premium{
    padding:46px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.executivo-badge-premium{
    display:inline-flex;
    align-items:center;
    gap:8px;
    width:max-content;
    background:#eff6ff;
    color:#242A30;
    padding:10px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.7px;
    margin-bottom:18px;
}
.executivo-presidente-info-premium h2{
    color:#11151B;
    font-size:clamp(34px,4vw,56px);
    line-height:1.05;
    letter-spacing:-1.5px;
    margin:0 0 12px;
}
.executivo-cargo-premium{
    color:#242A30;
    font-size:20px;
    font-weight:900;
    margin-bottom:22px;
    display:block;
}
.executivo-pelouros-premium{
    background:#f8fafc;
    border-left:5px solid #D4AA00;
    border-radius:18px;
    padding:18px;
    color:#334155;
    line-height:1.8;
    font-weight:700;
    margin:0 0 22px;
}
.executivo-bio-premium{
    color:#52606d;
    line-height:1.9;
    font-size:16px;
    margin:0 0 26px;
}
.executivo-contact-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    width:max-content;
    background:linear-gradient(135deg,#242A30,#D4AA00);
    color:white;
    text-decoration:none;
    padding:16px 24px;
    border-radius:16px;
    font-weight:900;
    box-shadow:0 14px 34px rgba(36,42,50,.24);
    transition:.25s ease;
}
.executivo-contact-btn:hover{
    transform:translateY(-4px);
}
.executivo-section-head{
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:22px;
    margin:38px 0 24px;
}
.executivo-section-head h2{
    color:#11151B;
    font-size:clamp(32px,4vw,46px);
    line-height:1.1;
    letter-spacing:-1px;
    margin:8px 0 0;
}
.executivo-section-head p{
    color:#64748b;
    margin:0;
    font-weight:700;
}
.executivo-grid-premium{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:24px;
}
.executivo-card-premium{
    background:white;
    border:1px solid #eef2f7;
    border-radius:30px;
    overflow:hidden;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
    transition:.3s ease;
    position:relative;
}
.executivo-card-premium:hover{
    transform:translateY(-8px);
    box-shadow:0 28px 70px rgba(0,0,0,.14);
}
.executivo-card-photo-premium{
    height:310px;
    background:linear-gradient(135deg,#242A30,#11151B);
    position:relative;
    overflow:hidden;
}
.executivo-card-photo-premium img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.45s ease;
}
.executivo-card-premium:hover .executivo-card-photo-premium img{
    transform:scale(1.07);
}
.executivo-card-photo-premium::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,transparent 45%,rgba(17,21,28,.40));
}
.executivo-card-placeholder{
    width:100%;
    height:100%;
    display:grid;
    place-items:center;
    font-size:86px;
    font-weight:900;
    color:#D4AA00;
}
.executivo-card-body-premium{
    padding:26px;
}
.executivo-card-body-premium h3{
    color:#11151B;
    font-size:25px;
    line-height:1.25;
    margin:0 0 14px;
}
.executivo-card-body-premium .executivo-badge-premium{
    margin-bottom:14px;
    background:#f8fafc;
}
.executivo-card-body-premium p{
    color:#52606d;
    line-height:1.8;
    margin:0 0 18px;
}
.executivo-email-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#242A30;
    text-decoration:none;
    font-weight:900;
    transition:.25s;
    word-break:break-word;
}
.executivo-email-link:hover{
    color:#D4AA00;
}
.executivo-empty-premium{
    background:white;
    border-radius:26px;
    padding:36px;
    color:#64748b;
    font-weight:800;
    text-align:center;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}
@media(max-width:1100px){
    .executivo-presidente-premium{grid-template-columns:1fr;}
    .executivo-presidente-photo-premium{height:520px;min-height:0;}
    .executivo-grid-premium{grid-template-columns:repeat(2,minmax(0,1fr));}
    .executivo-hero-inner{grid-template-columns:1fr;}
}
@media(max-width:760px){
    .executivo-hero-inner{padding:65px 0 110px;}
    .executivo-main{margin-top:-52px;}
    .executivo-presidente-info-premium{padding:28px;}
    .executivo-presidente-photo-premium{height:420px;}
    .executivo-grid-premium{grid-template-columns:1fr;}
    .executivo-section-head{flex-direction:column;align-items:flex-start;}
    .executivo-hero-stats{width:100%;}
    .executivo-hero-stat{flex:1;}
}
</style>

<main class="executivo-page-premium">

    <section class="executivo-hero-premium">
        <div class="container">
            <div class="executivo-hero-inner">
                <div>
                    <span class="executivo-kicker">Órgãos Autárquicos</span>
                    <h1>Presidente e Executivo</h1>
                    <p>
                        Conheça os membros do executivo da Junta de Freguesia,
                        as suas áreas de intervenção e o compromisso com a comunidade.
                    </p>
                </div>

                <div class="executivo-hero-stats">
                    <div class="executivo-hero-stat">
                        <strong><?= (int)$totalMembros ?></strong>
                        <span>Membros publicados</span>
                    </div>

                    <div class="executivo-hero-stat">
                        <strong><?= (int)$totalVogais ?></strong>
                        <span>Vogais / Executivo</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container executivo-main">

        <?php if (!empty($presidente)): ?>
            <article class="executivo-presidente-premium">

                <div class="executivo-presidente-photo-premium">
                    <?php if (!empty($presidente['foto'])): ?>
                        <img src="<?= htmlspecialchars(fotoMembroUrl($presidente['foto'])) ?>" alt="<?= htmlspecialchars($presidente['nome']) ?>">
                    <?php else: ?>
                        <div class="executivo-avatar-placeholder">
                            <?= strtoupper(mb_substr($presidente['nome'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>

                    <div class="executivo-presidente-label">Presidência da Junta</div>
                </div>

                <div class="executivo-presidente-info-premium">
                    <span class="executivo-badge-premium">Presidência</span>

                    <h2><?= htmlspecialchars($presidente['nome']) ?></h2>

                    <strong class="executivo-cargo-premium">
                        <?= htmlspecialchars($presidente['cargo']) ?>
                    </strong>

                    <?php if (!empty($presidente['pelouros'])): ?>
                        <div class="executivo-pelouros-premium">
                            <?= nl2br(htmlspecialchars($presidente['pelouros'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($presidente['biografia'])): ?>
                        <p class="executivo-bio-premium">
                            <?= nl2br(htmlspecialchars($presidente['biografia'])) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($presidente['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($presidente['email']) ?>" class="executivo-contact-btn">
                            <i class="bi bi-envelope-fill"></i> Contactar Presidente
                        </a>
                    <?php endif; ?>
                </div>

            </article>
        <?php endif; ?>

        <div class="executivo-section-head">
            <div>
                <span class="executivo-contact-btn">Equipa Executiva</span>
                <h2>Membros do Executivo</h2>
            </div>

            <p>Representantes ao serviço da freguesia.</p>
        </div>

        <?php if (!empty($membros)): ?>

            <div class="executivo-grid-premium">

                <?php foreach ($membros as $m): ?>

                    <?php if (!empty($presidente) && (int)$m['id'] === (int)$presidente['id']) continue; ?>

                    <article class="executivo-card-premium">

                        <div class="executivo-card-photo-premium">
                            <?php if (!empty($m['foto'])): ?>
                                <img src="<?= htmlspecialchars(fotoMembroUrl($m['foto'])) ?>" alt="<?= htmlspecialchars($m['nome']) ?>">
                            <?php else: ?>
                                <div class="executivo-card-placeholder">
                                    <?= strtoupper(mb_substr($m['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="executivo-card-body-premium">
                            <span class="executivo-badge-premium">
                                <?= htmlspecialchars($m['cargo']) ?>
                            </span>

                            <h3><?= htmlspecialchars($m['nome']) ?></h3>

                            <?php if (!empty($m['pelouros'])): ?>
                                <p>
                                    <?= nl2br(htmlspecialchars($m['pelouros'])) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($m['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="executivo-email-link">
                                    <i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($m['email']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="executivo-empty-premium">
                Ainda não existem membros do executivo publicados.
            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once "includes/footer.php"; ?>
