<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/config.php";

$erro = '';

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

        $erro = "Verificação anti-bot inválida. Confirme a conta apresentada.";

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

            $erro = "Email ou password inválidos.";

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
            <span>Junta Virtual</span>
            <h1>Área do Cidadão</h1>
            <p>
                Acompanhe pedidos, notificações e serviços digitais da Junta
                num espaço simples, seguro e moderno.
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
.login-antibot-label span{
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

                <span class="section-kicker">Acesso reservado</span>
                <h2>Entrar na minha conta</h2>
                <p>Use os seus dados de acesso para entrar no Balcão Virtual.</p>

                <?php if ($erro): ?>
                    <div class="alerta-erro login-alert">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="cidadao-login-form">

                    <div class="login-input-group">
                        <span><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            name="email"
                            placeholder="Email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="login-input-group password-group">
                        <span><i class="bi bi-lock"></i></span>

                        <input
                            type="password"
                            name="password"
                            id="loginPassword"
                            placeholder="Password"
                            required
                        >

                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>


                    <div class="login-antibot-box">
                        <label class="login-antibot-label" for="anti_bot">
                            <strong>Verificação anti-bot</strong>
                            <span><?= htmlspecialchars($antiBotPergunta) ?> = ?</span>
                        </label>

                        <input
                            type="number"
                            name="anti_bot"
                            id="anti_bot"
                            placeholder="Escreva o resultado"
                            required
                        >
                    </div>

                    <button class="hero-btn" type="submit" style="width: 300px;">
                        Entrar no Balcão Virtual
                    </button>

                    <div class="cidadao-login-links">
                        <button class="hero-btn"><a href="/cidadao-registo.php">
                            Criar conta
                        </a></button>

                        <button class="hero-btn"><a href="/recuperar-password.php">
                            Esqueci-me da password
                        </a></button>
                    </div>

                </form>

            </div>

            <aside class="cidadao-login-side">

                <span class="section-kicker">O que pode fazer?</span>

                <h2>Serviços digitais sempre disponíveis</h2>

                <div class="login-benefit">
                    <strong><i class="bi bi-envelope-paper"></i> Pedidos à Junta</strong>
                    <span>Submeta e acompanhe comunicações e ocorrências.</span>
                </div>

                <div class="login-benefit">
                    <strong><i class="bi bi-bell"></i> Notificações</strong>
                    <span>Receba avisos e atualizações importantes.</span>
                </div>

                <div class="login-benefit">
                    <strong><i class="bi bi-file-earmark-text"></i> Requerimentos</strong>
                    <span>Aceda a serviços digitais e documentos online.</span>
                </div>

                <div class="login-benefit">
                    <strong><i class="bi bi-chat-dots"></i> Conversação</strong>
                    <span>Comunique diretamente com a Junta sobre os seus pedidos.</span>
                </div>

            </aside>

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
            btn.innerHTML = input.type === "password" ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?>