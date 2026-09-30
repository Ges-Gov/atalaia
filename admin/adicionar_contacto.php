<?php
$adminPageTitle = "Adicionar Contacto";
$adminActive = "contactos";
require_once "includes/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];

    $stmt = $pdo->prepare("INSERT INTO contactos_uteis (nome, telefone) VALUES (?, ?)");
    $stmt->execute([$nome, $telefone]);

    header("Location: contactos.php");
    exit;
}
?>

<form method="POST">
    <input type="text" name="nome" placeholder="Nome do contacto" required>
    <input type="text" name="telefone" placeholder="Telefone" required>

    <button class="btn">Guardar contacto</button>
    <a class="btn secondary" href="contactos.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>