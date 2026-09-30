<?php
$adminPageTitle = "Editar Texto";
$adminActive = "homepage";
require_once "includes/header.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM seccoes_homepage WHERE id = ?");
$stmt->execute([$id]);
$secao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$secao) {
    die("Secção não encontrada.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $conteudo = $_POST['conteudo'];

    $stmt = $pdo->prepare("UPDATE seccoes_homepage SET titulo = ?, conteudo = ? WHERE id = ?");
    $stmt->execute([$titulo, $conteudo, $id]);

    header("Location: homepage.php");
    exit;
}
?>

<form method="POST">
    <input type="text" name="titulo" value="<?= htmlspecialchars($secao['titulo']) ?>" required>
    <textarea name="conteudo" required><?= htmlspecialchars($secao['conteudo']) ?></textarea>
    <button class="btn">Guardar alterações</button>
</form>

<?php require_once "includes/footer.php"; ?>