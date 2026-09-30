<?php
$adminPageTitle = "Adicionar Pergunta";
$adminActive = "faqs";
require_once "includes/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pergunta = trim($_POST['pergunta'] ?? '');
    $resposta = trim($_POST['resposta'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO faqs (pergunta, resposta, ordem, ativo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pergunta, $resposta, $ordem, $ativo]);

    header("Location: faqs.php");
    exit;
}
?>

<form method="POST">
    <label>Pergunta *</label>
    <input type="text" name="pergunta" placeholder="Ex: Como posso contactar a Junta?" required>

    <label>Resposta *</label>
    <textarea name="resposta" placeholder="Resposta à pergunta" required></textarea>

    <label>Ordem</label>
    <input type="number" name="ordem" value="0">

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" checked style="width:auto;margin:0;">
        Pergunta ativa
    </label>

    <button class="btn">Guardar pergunta</button>
    <a class="btn secondary" href="faqs.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
