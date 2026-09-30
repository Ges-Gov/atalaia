<?php require_once "includes/header.php"; ?>

<?php
$token = $_GET['token'] ?? '';
$erro = '';
$sucesso = false;

$stmt = $pdo->prepare("
    SELECT * FROM cidadaos
    WHERE reset_token = ?
    AND reset_expira >= NOW()
    AND ativo = 1
");
$stmt->execute([$token]);
$cidadao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cidadao) {
    $erro = "Link inválido ou expirado.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cidadao) {
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (strlen($password) < 6) {
        $erro = "A password deve ter pelo menos 6 caracteres.";
    } elseif ($password !== $confirmar) {
        $erro = "As passwords não coincidem.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE cidadaos
            SET password = ?, reset_token = NULL, reset_expira = NULL
            WHERE id = ?
        ");
        $stmt->execute([$hash, $cidadao['id']]);

        $sucesso = true;
    }
}
?>

<section class="page-hero">
    <div class="container">
        <h1>Definir Nova Password</h1>
        <p>Escolha uma nova password para aceder ao Balcão Virtual.</p>
    </div>
</section>

<section class="section">
    <div class="container content-box">

        <?php if ($erro): ?>
            <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta-sucesso">
                Password alterada com sucesso.
            </div>
            <a class="btn" href="/cidadao-login.php">Iniciar sessão</a>
        <?php elseif ($cidadao): ?>
            <form method="POST" class="form-publico">
                <input type="password" name="password" placeholder="Nova password" required>
                <input type="password" name="confirmar" placeholder="Confirmar password" required>

                <button class="btn" type="submit">Guardar nova password</button>
            </form>
        <?php endif; ?>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>