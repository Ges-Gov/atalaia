<?php require_once "includes/header.php"; ?>

<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM pontos_interesse WHERE id = ?");
$stmt->execute([$id]);
$ponto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ponto) {
    echo '<section class="section"><div class="container content-box"><h1>Local não encontrado</h1><p>O ponto de interesse solicitado não existe.</p><a href="/pontos.php" class="btn">Voltar</a></div></section>';
    require_once "includes/footer.php";
    exit;
}

$imagem = !empty($ponto['imagem'])
    ? "/assets/img/" . htmlspecialchars($ponto['imagem'])
    : "";
?>

<style>
/* O cabeçalho usa a cor da freguesia, como o resto do site. Antes tinha um
   cinzento fixo no código, igual em todas as freguesias. O tom escuro do fim
   do gradiente é derivado da própria cor, para funcionar com qualquer marca. */
.ponto-detalhe-hero{
    background:linear-gradient(135deg,
        var(--cor-principal),
        color-mix(in srgb, var(--cor-principal) 55%, #000));
    color:white;
    padding:80px 0;
}
.ponto-detalhe-hero h1{
    font-size:56px;
    margin:0 0 15px;
}
.ponto-detalhe-hero p{
    color:#dbeafe;
    font-size:18px;
}
.ponto-detalhe-card{
    background:white;
    border-radius:30px;
    padding:28px;
    box-shadow:0 20px 60px rgba(0,0,0,.10);
    margin-top:-45px;
    position:relative;
    z-index:3;
}
/* A imagem aparece inteira, sem cortes. Antes tinha object-fit:cover com
   width:100%, o que a obrigava a preencher a caixa e cortava-lhe as pontas.
   Agora a caixa é que se ajusta à imagem: nunca passa da largura do cartão
   nem dos 520px de altura, e fica centrada. */
.ponto-detalhe-img{
    display:block;
    width:auto;
    max-width:100%;
    max-height:520px;
    margin:0 auto 25px;
    border-radius:24px;
}
.ponto-detalhe-texto{
    color:#52606d;
    line-height:1.85;
    font-size:17px;
}
.ponto-detalhe-actions{
    margin-top:25px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
</style>

<section class="ponto-detalhe-hero">
    <div class="container">
        <span><i class="bi bi-geo-alt-fill"></i> Ponto de Interesse</span>
        <h1><?= htmlspecialchars($ponto['nome']) ?></h1>

        <?php if (!empty($ponto['localizacao'])): ?>
            <p><?= htmlspecialchars($ponto['localizacao']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="ponto-detalhe-card">

            <?php if ($imagem): ?>
                <img class="ponto-detalhe-img" src="<?= $imagem ?>" alt="<?= htmlspecialchars($ponto['nome']) ?>">
            <?php endif; ?>

            <div class="ponto-detalhe-texto">
                <?= nl2br(htmlspecialchars($ponto['descricao'] ?? '')) ?>
            </div>

            <?php
            // Fotografias do ponto: vivem na Galeria, num álbum criado automaticamente
            // com o nome do ponto. Não há cópias — são lidas de lá.
            require_once __DIR__ . "/includes/galeria.php";
            $fotosPonto = imagensDaOrigem($pdo, 'ponto', (int)$ponto['id']);
            ?>

            <?php if (!empty($fotosPonto)): ?>
                <h2 style="margin:28px 0 14px;">Fotografias</h2>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;">
                    <?php foreach ($fotosPonto as $f): ?>
                        <a href="<?= htmlspecialchars(imagemGaleriaUrl($f['ficheiro'])) ?>" target="_blank" rel="noopener"
                           style="display:block;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.10);">
                            <img src="<?= htmlspecialchars(imagemGaleriaUrl($f['ficheiro'])) ?>"
                                 alt="<?= htmlspecialchars($f['titulo'] ?: $ponto['nome']) ?>" loading="lazy"
                                 style="width:100%;height:145px;object-fit:cover;display:block;">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ponto-detalhe-actions">
                <a class="hero-btn" href="/pontos.php">← Voltar aos pontos</a>
                <a class="hero-btn" href="/mapa.php">Ver no mapa</a>
            </div>

        </div>
    </div>
</section>

<?php require_once "includes/footer.php"; ?>