<?php
$adminPageTitle = "Envio da Newsletter";
$adminActive = "newsletter";
require_once "includes/header.php";

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM newsletter_envios WHERE id = ?");
$stmt->execute([$id]);
$envio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$envio) {
    die("Envio não encontrado.");
}
?>

<div class="content-box">
    <p><strong>Mês:</strong> <?= date('m/Y', strtotime($envio['mes_referencia'] . '-01')) ?></p>
    <p><strong>Destinatários:</strong> <?= (int) $envio['total_destinatarios'] ?></p>
    <p><strong>Enviado em:</strong> <?= date('d/m/Y H:i', strtotime($envio['enviado_em'])) ?></p>
    <a class="btn secondary" href="newsletter.php">Voltar</a>
</div>

<div class="content-box" style="margin-top:15px;">
    <h3>Conteúdo enviado</h3>
    <iframe style="width:100%;height:640px;border:1px solid #e5e7eb;border-radius:12px;background:#f1f5f9;" srcdoc="<?= htmlspecialchars($envio['conteudo_html'], ENT_QUOTES, 'UTF-8') ?>"></iframe>
</div>

<?php require_once "includes/footer.php"; ?>
