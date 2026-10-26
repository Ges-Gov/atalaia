<?php require_once "includes/header.php"; ?>

<?php
$codigo = $_GET['codigo'] ?? '';

$stmt = $pdo->prepare("
    SELECT * FROM pedidos_junta 
    WHERE codigo = ? AND publico = 1
");
$stmt->execute([$codigo]);

$ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ocorrencia) {
    echo "<div class='container'><h2>Ocorrência não encontrada</h2></div>";
    require_once "includes/footer.php";
    exit;
}
?>

<section class="page-hero">
    <div class="container">
        <h1><?= htmlspecialchars($ocorrencia['assunto']) ?></h1>
        <p><?= htmlspecialchars($ocorrencia['categoria']) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="content-box">
            <h2><?= te('ocorrencia.conteudo.titulo') ?></h2>

            <p><strong><?= te('ocorrencia.conteudo.estado') ?></strong> <?= htmlspecialchars(str_replace('_',' ',$ocorrencia['estado'])) ?></p>

            <p><strong><?= te('ocorrencia.conteudo.localizacao') ?></strong><br>
                <?= htmlspecialchars($ocorrencia['localizacao']) ?>
            </p>

            <p><strong><?= te('ocorrencia.conteudo.descricao') ?></strong></p>
            <p><?= nl2br(htmlspecialchars($ocorrencia['mensagem'])) ?></p>

            <p><strong><?= te('ocorrencia.conteudo.codigo') ?></strong> <?= htmlspecialchars($ocorrencia['codigo']) ?></p>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>