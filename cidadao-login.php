<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/config.php";

$erro = '';
$errosCampo = [];   // erro junto ao campo (acessibilidade, 18/11/2026)

if (empty($_SESSION['anti_bot_a']) || empty($_SESSION['anti_bot_b'])) {
    $_SESSION['anti_bot_a'] = random_int(1, 9);
    $_SESSION['anti_bot_b'] = random_int(1, 9);
}

$antiBotPergunta = $_SESSION['anti_bot_a'] . " + " . $_SESSION['anti_bot_b'];
$antiBotResposta = (int)$_SESSION['anti_bot_a'] + (int)$_SESSION['anti_bot_b'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $antiBot = trim($_POST['anti_bot'] ?? '');

    if ($antiBot === '' || (int)$antiBot !== $antiBotResposta) {

        $errosCampo['anti_bot'] = $antiBot === ''
            ? 'Escreva o resultado da conta (é só para confirmar que não é um robô).'
            : 'O resultado da conta não está certo. Faça a nova conta que aparece acima da caixa e escreva o resultado.';

        $_SESSION['anti_bot_a'] = random_int(1, 9);
        $_SESSION['anti_bot_b'] = random_int(1, 9);
        $antiBotPergunta = $_SESSION['anti_bot_a'] . " + " . $_SESSION['anti_bot_b'];
        $antiBotResposta = (int)$_SESSION['anti_bot_a'] + (int)$_SESSION['anti_bot_b'];

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM cidadaos
            WHERE email = ?
            AND ativo = 1
        ");

        $stmt->execute([$email]);

        $cidadao = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cidadao && password_verify($password, $cidadao['password'])) {

            unset($_SESSION['anti_bot_a'], $_SESSION['anti_bot_b']);

            $_SESSION['cidadao_id'] = $cidadao['id'];
            $_SESSION['cidadao_nome'] = $cidadao['nome'];

            header("Location: /minha-area.php");
            exit;

        } else {

            // não diz qual dos dois está errado (segurança), mas diz o que fazer (acessibilidade, 18/11/2026)
            $erro = "O email ou a palavra-passe não estão certos. Confirme os dois, ou use «Esqueci-me da password» para definir uma nova.";

            $_SESSION['anti_bot_a'] = random_int(1, 9);
            $_SESSION['anti_bot_b'] = random_int(1, 9);
            $antiBotPergunta = $_SESSION['anti_bot_a'] . " + " . $_SESSION['anti_bot_b'];
            $antiBotResposta = (int)$_SESSION['anti_bot_a'] + (int)$_SESSION['anti_bot_b'];

        }

    }
}

require_once "includes/header.php";
?>

<section class="cidadao-login-hero">
    <div class="container">
        <div>
            <span><?= te('cidadao_login.hero.kicker') ?></span>
            <h1><?= te('cidadao_login.hero.titulo') ?></h1>
            <p>
                <?= te('cidadao_login.hero.subtitulo') ?>

            </p>
        </div>

        <div class="cidadao-login-icon"><i class="bi bi-person"></i></div>
    </div>
</section>


<style>
.login-antibot-box{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:16px;
    margin:14px 0 18px;
}
.login-antibot-label{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
    color:#11151B;
    font-weight:900;
}
.login-antibot-label > span{
    background:#242A30;
    color:white;
    padding:8px 13px;
    border-radius:999px;
    font-weight:900;
}
.login-antibot-box input{
    width:100%;
    height:52px;
    border:1px solid #dbe3ea;
    border-radius:14px;
    padding:0 14px;
    box-sizing:border-box;
    font-size:16px;
    background:white;
}
.login-antibot-box input:focus{
    outline:none;
    border-color:#242A30;
    box-shadow:0 0 0 4px rgba(36,42,50,.10);
}
</style>

<section class="section cidadao-login-section">
    <div class="container">

        <div class="cidadao-login-wrapper">

            <div class="cidadao-login-card">

                <span class="section-kicker"><?= te('cidadao_login.formulario.kicker') ?></span>
                <h2><?= te('cidadao_login.formulario.titulo') ?></h2>
                <p><?= te('cidadao_login.formulario.intro') ?></p>

                <?php if ($erro): ?>
                    <div class="alerta-erro login-alert" role="alert">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="cidadao-login-form">
                    <?php /* etiqueta visível por cima de cada caixa, * nos obrigatórios, erro junto ao campo (18/11/2026) */ ?>
                    <?= resumoErros($errosCampo) ?>
                    <?= notaObrigatorios() ?>

                    <div class="campo">
                        <?= rotulo('loginEmail', tx('cidadao_login.formulario.ph_email'), true) ?>
                        <div class="login-input-group">
                            <span><i class="bi bi-envelope"></i></span>
                            <input
                                type="email"
                                name="email"
                                id="loginEmail"
                                autocomplete="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="campo">
                        <?= rotulo('loginPassword', tx('cidadao_login.formulario.ph_password'), true) ?>
                        <div class="login-input-group password-group">
                            <span><i class="bi bi-lock"></i></span>

                            <input
                                type="password"
                                name="password"
                                id="loginPassword"
                                autocomplete="current-password"
                                required
                            >

                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar palavra-passe" aria-pressed="false" aria-controls="loginPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>


                    <div class="login-antibot-box">
                        <label class="login-antibot-label" for="anti_bot">
                            <strong><?= te('cidadao_login.formulario.antibot') ?> <?= marcaObrigatorio() ?></strong>
                            <span><?= htmlspecialchars($antiBotPergunta) ?> = ?</span>
                        </label>

                        <input
                            type="number"
                            name="anti_bot"
                            id="anti_bot"
                            placeholder="<?= te('cidadao_login.formulario.ph_antibot') ?>"
                            required<?= erroAttrs($errosCampo, 'anti_bot') ?>
                        >
                        <?= erroCampo($errosCampo, 'anti_bot') ?>
                    </div>

                    <button class="hero-btn cidadao-login-entrar" type="submit">
                        <?= te('cidadao_login.formulario.botao') ?>
                    </button>

                    <div class="cidadao-login-links">
                        <a class="hero-btn hero-btn-outline" href="/cidadao-registo.php">
                            <?= te('cidadao_login.formulario.criar_conta') ?>
                        </a>

                        <a class="hero-btn hero-btn-outline" href="/recuperar-password.php">
                            <?= te('cidadao_login.formulario.esqueci') ?>
                        </a>
                    </div>

                </form>

            </div>

            <div class="cidadao-login-side">

                <span class="section-kicker"><?= te('cidadao_login.ajuda.kicker') ?></span>

                <h2><?= te('cidadao_login.ajuda.titulo') ?></h2>

                <div class="login-benefit">
                    <strong><i class="bi bi-envelope-paper"></i> <?= te('cidadao_login.ajuda.i1_titulo') ?></strong>
                    <span><?= te('cidadao_login.ajuda.i1_texto') ?></span>
                </div>

                <div class="login-benefit">
                    <strong><i class="bi bi-bell"></i> <?= te('cidadao_login.ajuda.i2_titulo') ?></strong>
                    <span><?= te('cidadao_login.ajuda.i2_texto') ?></span>
                </div>

                <div class="login-benefit">
                    <strong><i class="bi bi-file-earmark-text"></i> <?= te('cidadao_login.ajuda.i3_titulo') ?></strong>
                    <span><?= te('cidadao_login.ajuda.i3_texto') ?></span>
                </div>

                <div class="login-benefit">
                    <strong><i class="bi bi-chat-dots"></i> <?= te('cidadao_login.ajuda.i4_titulo') ?></strong>
                    <span><?= te('cidadao_login.ajuda.i4_texto') ?></span>
                </div>

            </div>

        </div>

    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("togglePassword");
    const input = document.getElementById("loginPassword");

    if (btn && input) {
        btn.addEventListener("click", function () {
            input.type = input.type === "password" ? "text" : "password";
            btn.innerHTML = input.type === "password" ? '<i class="bi bi-eye" aria-hidden="true"></i>' : '<i class="bi bi-eye-slash" aria-hidden="true"></i>';
            btn.setAttribute("aria-pressed", input.type === "text" ? "true" : "false");
            btn.setAttribute("aria-label", input.type === "text" ? "Esconder palavra-passe" : "Mostrar palavra-passe");
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?>