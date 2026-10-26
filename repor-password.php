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
$stmt->execute([$token !== '' ? hash('sha256', (string)$token) : '-']);   // na BD só fica o hash do token
$cidadao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cidadao) {
    $erro = "Link inválido ou expirado.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cidadao) {
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (mb_strlen($password) < 8) {
        $erro = "A password deve ter pelo menos 8 caracteres.";
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
        <h1><?= te('repor_password.hero.titulo') ?></h1>
        <p><?= te('repor_password.hero.subtitulo') ?></p>
    </div>
</section>

<section class="section">
    <div class="container content-box">

        <?php if ($erro): ?>
            <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta-sucesso">
                <?= te('repor_password.formulario.sucesso') ?>
            </div>
            <a class="btn" href="/cidadao-login.php"><?= te('repor_password.formulario.sucesso_botao') ?></a>
        <?php elseif ($cidadao): ?>
            <form method="POST" class="form-publico">
                <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="<?= te('repor_password.formulario.ph_nova') ?>" required>
                <input type="password" name="confirmar" placeholder="<?= te('repor_password.formulario.ph_confirmar') ?>" required>

                <button class="btn" type="submit"><?= te('repor_password.formulario.botao') ?></button>
            </form>
        <?php endif; ?>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>