<?php require_once "includes/header.php"; ?>
<?php require_once "includes/mail_helper.php"; ?>

<?php
$sucesso = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM cidadaos WHERE email = ? AND ativo = 1");
    $stmt->execute([$email]);
    $cidadao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cidadao) {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("
            UPDATE cidadaos 
            SET reset_token = ?, reset_expira = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expira, $cidadao['id']]);

        $siteBaseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $link = $siteBaseUrl . "/repor-password.php?token=" . $token;

        $html = "
            <h2>Recuperação de password</h2>
            <p>Olá " . htmlspecialchars($cidadao['nome']) . ",</p>
            <p>Recebemos um pedido para recuperar a sua password.</p>
            <p>Clique no link abaixo para definir uma nova password:</p>
            <p><a href='{$link}'>Repor password</a></p>
            <p>Este link expira dentro de 1 hora.</p>
            <br>
            <p><strong>" . siteConfig('nome_site') . "</strong></p>
        ";

        enviarEmailSistema($email, "Recuperar password", $html);
    }

    $sucesso = true;
}
?>

<section class="recuperar-hero-premium">
    <div class="container">
        <div>
            <span><?= te('recuperar_password.hero.kicker') ?></span>
            <h1><?= te('recuperar_password.hero.titulo') ?></h1>
            <p><?= te('recuperar_password.hero.subtitulo') ?></p>
        </div>

        <div class="recuperar-hero-icon"><i class="bi bi-shield-lock"></i></div>
    </div>
</section>

<section class="section recuperar-section">
    <div class="container">

        <div class="recuperar-wrapper">

            <div class="recuperar-card">
                <span class="section-kicker"><?= te('recuperar_password.formulario.kicker') ?></span>
                <h2><?= te('recuperar_password.formulario.titulo') ?></h2>
                <p><?= te('recuperar_password.formulario.intro') ?></p>

                <?php if ($sucesso): ?>
                    <div class="alerta-sucesso recuperar-alert">
                        <?= te('recuperar_password.formulario.enviado') ?>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alerta-erro recuperar-alert">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="recuperar-form">
                    <div class="recuperar-input-group">
                        <span><i class="bi bi-envelope"></i></span>
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="<?= te('recuperar_password.formulario.ph_email') ?>"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <button class="btn recuperar-btn" type="submit">
                        <?= te('recuperar_password.formulario.botao') ?>
                    </button>

                    <div class="recuperar-links">
                        <a href="/cidadao-login.php"><?= te('recuperar_password.formulario.voltar') ?></a>
                        <a href="/cidadao-registo.php"><?= te('recuperar_password.formulario.criar_conta') ?></a>
                    </div>
                </form>
            </div>

            <aside class="recuperar-side">
                <span class="section-kicker"><?= te('recuperar_password.passos.kicker') ?></span>
                <h2><?= te('recuperar_password.passos.titulo') ?></h2>

                <div class="recuperar-step">
                    <strong>1</strong>
                    <span><?= te('recuperar_password.passos.p1') ?></span>
                </div>

                <div class="recuperar-step">
                    <strong>2</strong>
                    <span><?= te('recuperar_password.passos.p2') ?></span>
                </div>

                <div class="recuperar-step">
                    <strong>3</strong>
                    <span><?= te('recuperar_password.passos.p3') ?></span>
                </div>

                <div class="recuperar-note">
                    <?= te('recuperar_password.passos.nota') ?>
                </div>
            </aside>

        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>