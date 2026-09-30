<?php
$adminPageTitle = "Editar Pergunta";
$adminActive = "faqs";
require_once "includes/header.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
$stmt->execute([$id]);
$faq = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faq) {
    die("Pergunta não encontrada.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pergunta = trim($_POST['pergunta'] ?? '');
    $resposta = trim($_POST['resposta'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE faqs SET pergunta = ?, resposta = ?, ordem = ?, ativo = ? WHERE id = ?");
    $stmt->execute([$pergunta, $resposta, $ordem, $ativo, $id]);

    header("Location: faqs.php");
    exit;
}
?>

<form method="POST">
    <label>Pergunta *</label>
    <input type="text" name="pergunta" value="<?= htmlspecialchars($faq['pergunta']) ?>" required>

    <label>Resposta *</label>
    <textarea name="resposta" required><?= htmlspecialchars($faq['resposta']) ?></textarea>

    <label>Ordem</label>
    <input type="number" name="ordem" value="<?= (int)$faq['ordem'] ?>">

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" <?= $faq['ativo'] ? 'checked' : '' ?> style="width:auto;margin:0;">
        Pergunta ativa
    </label>

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="faqs.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
