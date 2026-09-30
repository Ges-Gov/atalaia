<?php require_once "includes/header.php"; ?>

<?php
$heraldica = [
    'hero_kicker' => 'Heráldica',
    'hero_titulo' => 'Heráldica',
    'hero_subtitulo' => 'Brasão, bandeira e elementos simbólicos da Freguesia de Atalaia e Alto Estanqueiro-Jardia.',
    'titulo' => 'Brasão da Freguesia de Atalaia e Alto Estanqueiro-Jardia',
    'texto_intro' => 'Escudo de ouro, com uma buzina de vermelho cordada de azul; em campanha, charrua de vermelho com relha, podão, maço e cadeia de negro; bordadura de azul carregada de contas de rosário. Coroa mural de prata de três torres. Listel branco com a legenda em letras negras: GRANHO.',
    'imagem' => 'heraldica-granho.webp',
    'fonte_texto' => '',
    'fonte_url' => ''
];

try {
    $stmt = $pdo->query("SELECT * FROM heraldica_pagina WHERE ativo = 1 ORDER BY id ASC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $heraldica = array_merge($heraldica, $row);
} catch(Exception $e) {}

$elementos = [];
try {
    $stmt = $pdo->query("SELECT * FROM heraldica_elementos WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
    $elementos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

function hTxt($txt) {
    return nl2br(htmlspecialchars($txt ?? ''));
}

$img = !empty($heraldica['imagem']) ? "/uploads/heraldica/" . htmlspecialchars($heraldica['imagem']) : "/assets/img/heraldica-granho.webp";
?>

<style>
.heraldica-hero{
    background:
        radial-gradient(circle at 15% 18%, rgba(212,170,0,.26), transparent 30%),
        radial-gradient(circle at 90% 12%, rgba(255,255,255,.12), transparent 30%),
        linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);
    color:white;
    padding:92px 0 108px;
    position:relative;
    overflow:hidden;
}
.heraldica-hero::after{
    content:"";
    position:absolute;
    right:-250px;
    top:-280px;
    width:620px;
    height:620px;
    background:rgba(255,255,255,.07);
    border-radius:50%;
}
.heraldica-hero .container{position:relative;z-index:2;}
.heraldica-kicker{
    display:inline-flex;
    background:rgba(212,170,0,.16);
    border:1px solid rgba(212,170,0,.38);
    color:#F0D060;
    padding:9px 14px;
    border-radius:999px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    font-size:12px;
}
.heraldica-hero h1{
    font-size:clamp(42px,5vw,72px);
    margin:18px 0 14px;
    line-height:1.02;
}
.heraldica-hero p{
    max-width:880px;
    color:#dbeafe;
    font-size:20px;
    line-height:1.75;
    margin:0;
}
.heraldica-shell{margin-top:-52px;position:relative;z-index:5;}
.heraldica-main{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:32px;
    padding:30px;
    box-shadow:0 18px 50px rgba(15,23,42,.10);
    display:grid;
    grid-template-columns:360px 1fr;
    gap:32px;
    align-items:center;
}
.heraldica-img-card{
    background:linear-gradient(180deg,#fff,#f8fafc);
    border:1px solid #e5e7eb;
    border-radius:28px;
    padding:26px;
    text-align:center;
}
.heraldica-img-card img{
    max-width:100%;
    max-height:330px;
    object-fit:contain;
    filter:drop-shadow(0 20px 25px rgba(15,23,42,.16));
}
.heraldica-main h2{
    color:#11151B;
    font-size:36px;
    margin:0 0 14px;
}
.heraldica-main p{
    color:#64748b;
    line-height:1.85;
    font-size:17px;
    margin:0;
}
.heraldica-source{
    display:inline-flex;
    margin-top:18px;
    background:#eef2ff;
    color:#242A30;
    padding:10px 13px;
    border-radius:999px;
    text-decoration:none;
    font-weight:900;
}
.heraldica-section{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:32px;
    padding:30px;
    box-shadow:0 18px 50px rgba(15,23,42,.08);
    margin-top:26px;
}
.heraldica-section h2{
    color:#11151B;
    margin:0 0 20px;
    font-size:34px;
}
.heraldica-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
}
.heraldica-card{
    background:linear-gradient(180deg,#fff,#f8fafc);
    border:1px solid #e5e7eb;
    border-radius:26px;
    padding:24px;
    transition:.22s ease;
}
.heraldica-card:hover{
    transform:translateY(-4px);
    box-shadow:0 22px 50px rgba(15,23,42,.10);
}
.heraldica-icon{
    width:64px;
    height:64px;
    border-radius:20px;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:white;
    display:grid;
    place-items:center;
    font-size:30px;
    margin-bottom:15px;
}
.heraldica-card h3{
    margin:0 0 8px;
    color:#11151B;
    font-size:23px;
}
.heraldica-card p{
    color:#64748b;
    line-height:1.75;
    margin:0;
}
@media(max-width:1050px){
    .heraldica-main,.heraldica-grid{grid-template-columns:1fr;}
}
</style>

<section class="heraldica-hero">
    <div class="container">
        <span class="heraldica-kicker"><?= htmlspecialchars($heraldica['hero_kicker']) ?></span>
        <h1><?= htmlspecialchars($heraldica['hero_titulo']) ?></h1>
        <p><?= htmlspecialchars($heraldica['hero_subtitulo']) ?></p>
    </div>
</section>

<section class="section heraldica-shell">
    <div class="container">

        <div class="heraldica-main">
            <div class="heraldica-img-card">
                <img src="<?= $img ?>" alt="Brasão de Atalaia e Alto Estanqueiro-Jardia">
            </div>

            <div>
                <h2><?= htmlspecialchars($heraldica['titulo']) ?></h2>
                <p><?= hTxt($heraldica['texto_intro']) ?></p>

                <?php if(!empty($heraldica['fonte_texto'])): ?>
                    <?php if(!empty($heraldica['fonte_url'])): ?>
                        <a class="heraldica-source" href="<?= htmlspecialchars($heraldica['fonte_url']) ?>" target="_blank"><i class="bi bi-link-45deg"></i> <?= htmlspecialchars($heraldica['fonte_texto']) ?></a>
                    <?php else: ?>
                        <span class="heraldica-source">ℹ️ <?= htmlspecialchars($heraldica['fonte_texto']) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="heraldica-section">
            <h2>Elementos Heráldicos</h2>

            <div class="heraldica-grid">
                <?php foreach($elementos as $e): ?>
                    <article class="heraldica-card">
                        <div class="heraldica-icon"><i class="bi <?= htmlspecialchars($e['icone'] ?: 'bi-shield') ?>"></i></div>
                        <h3><?= htmlspecialchars($e['titulo']) ?></h3>
                        <p><?= hTxt($e['descricao']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if(empty($elementos)): ?>
                <p>Ainda não existem elementos heráldicos publicados.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>
