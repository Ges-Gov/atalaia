<?php require_once "includes/header.php"; ?>

<?php
$token = $_GET['token'] ?? '';
$erro = '';
$errosCampo = [];   // erros junto a cada campo (acessibilidade, 18/11/2026)
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
        $errosCampo['password'] = 'A nova palavra-passe tem de ter pelo menos 8 carateres.';
    } elseif ($password !== $confirmar) {
        $errosCampo['confirmar'] = 'As duas palavras-passe não são iguais. Escreva outra vez a mesma nas duas caixas.';
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
                <?php /* etiqueta visível, * nos obrigatórios, erros junto ao campo (acessibilidade, 18/11/2026) */ ?>
                <?= resumoErros($errosCampo, ['password' => 'reporPassword', 'confirmar' => 'reporConfirmar']) ?>
                <?= notaObrigatorios() ?>
                <div class="campo">
                    <?= rotulo('reporPassword', tx('repor_password.formulario.ph_nova'), true) ?>
                    <input type="password" id="reporPassword" name="password" minlength="8" autocomplete="new-password" placeholder="Pelo menos 8 carateres" required<?= erroAttrs($errosCampo, 'password') ?>>
                    <?= erroCampo($errosCampo, 'password') ?>
                </div>
                <div class="campo">
                    <?= rotulo('reporConfirmar', tx('repor_password.formulario.ph_confirmar'), true) ?>
                    <input type="password" id="reporConfirmar" name="confirmar" autocomplete="new-password" required<?= erroAttrs($errosCampo, 'confirmar') ?>>
                    <?= erroCampo($errosCampo, 'confirmar') ?>
                </div>

                <button class="btn" type="submit"><?= te('repor_password.formulario.botao') ?></button>
            </form>
        <?php endif; ?>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>