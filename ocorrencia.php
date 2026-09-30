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
            <h2>Detalhes</h2>

            <p><strong>Estado:</strong> <?= htmlspecialchars(str_replace('_',' ',$ocorrencia['estado'])) ?></p>

            <p><strong>Localização:</strong><br>
                <?= htmlspecialchars($ocorrencia['localizacao']) ?>
            </p>

            <p><strong>Descrição:</strong></p>
            <p><?= nl2br(htmlspecialchars($ocorrencia['mensagem'])) ?></p>

            <p><strong>Código:</strong> <?= htmlspecialchars($ocorrencia['codigo']) ?></p>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>