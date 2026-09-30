<?php
$adminPageTitle = "Editar Contacto";
$adminActive = "contactos";
require_once "includes/header.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM contactos_uteis WHERE id = ?");
$stmt->execute([$id]);
$contacto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contacto) {
    die("Contacto não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];

    $stmt = $pdo->prepare("UPDATE contactos_uteis SET nome = ?, telefone = ? WHERE id = ?");
    $stmt->execute([$nome, $telefone, $id]);

    header("Location: contactos.php");
    exit;
}
?>

<form method="POST">
    <input type="text" name="nome" value="<?= htmlspecialchars($contacto['nome']) ?>" required>
    <input type="text" name="telefone" value="<?= htmlspecialchars($contacto['telefone']) ?>" required>

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="contactos.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>