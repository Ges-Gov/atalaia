<?php require_once "includes/header.php"; ?>
<?php require_once "includes/mail_helper.php"; ?>

<?php
$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nome && $email && $password) {
        if (!emailValido($email)) {
            $erro = "Indique um email válido.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM cidadaos WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erro = "Já existe uma conta com este email.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO cidadaos (nome, email, telefone, password)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $email, $telefone, $hash]);

                $siteBaseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $assuntoEmail = "Conta criada - " . siteConfig('nome_site');

                $htmlEmail = "
                    <h2>Conta criada com sucesso</h2>
                    <p>Olá " . htmlspecialchars($nome) . ",</p>
                    <p>A sua conta no Balcão Virtual foi criada com sucesso.</p>
                    <p><strong>Email de acesso:</strong> " . htmlspecialchars($email) . "</p>
                    <p>Pode iniciar sessão através do site da freguesia.</p>
                    <p><a href='" . $siteBaseUrl . "/cidadao-login.php'>Entrar no Balcão Virtual</a></p>
                    <br>
                    <p><strong>" . htmlspecialchars(siteConfig('nome_site')) . "</strong></p>
                ";

                enviarEmailSistema($email, $assuntoEmail, $htmlEmail);

                $sucesso = true;
            }
        }
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}
?>

<section class="cidadao-registo-hero">
    <div class="container">
        <div>
            <span>Junta Virtual</span>
            <h1>Registo de Cidadão</h1>
            <p>Crie a sua conta para aceder ao Balcão Virtual e acompanhar os seus pedidos online.</p>
        </div>

        <div class="cidadao-registo-icon"><i class="bi bi-pencil-square"></i></div>
    </div>
</section>

<section class="section cidadao-registo-section">
    <div class="container">

        <div class="cidadao-registo-wrapper">

            <div class="cidadao-registo-card">

                <span class="section-kicker">Criar conta</span>
                <h2>Aceda aos serviços digitais</h2>
                <p>Registe-se para submeter pedidos, receber notificações e acompanhar processos.</p>

                <?php if ($sucesso): ?>
                    <div class="alerta-sucesso registo-alert">
                        Conta criada com sucesso. Enviámos um email de confirmação.
                    </div>

                    <a class="btn btn-registo-full" href="/cidadao-login.php">
                        Iniciar sessão
                    </a>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alerta-erro registo-alert">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$sucesso): ?>
                    <form method="POST" class="cidadao-registo-form">

                        <div class="registo-input-group">
                            <span><i class="bi bi-person"></i></span>
                            <input type="text" name="nome" placeholder="Nome completo *" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                        </div>

                        <div class="registo-input-group">
                            <span><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" placeholder="Email *" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="registo-input-group">
                            <span><i class="bi bi-telephone"></i></span>
                            <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                        </div>

                        <div class="registo-input-group password-group">
                            <span><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="registoPassword" placeholder="Password *" required>

                            <button type="button" class="toggle-password" id="toggleRegistoPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <button class="btn btn-registo-full" type="submit">
                            Criar conta
                        </button>

                        <div class="cidadao-registo-links">
                            <a href="/cidadao-login.php">
                                Já tenho conta
                            </a>
                        </div>

                    </form>
                <?php endif; ?>

            </div>

            <aside class="cidadao-registo-side">

                <span class="section-kicker">Balcão Virtual</span>

                <h2>Uma ligação direta à sua freguesia</h2>

                <div class="registo-benefit">
                    <strong><i class="bi bi-envelope-paper"></i> Pedidos online</strong>
                    <span>Submeta pedidos e ocorrências diretamente à Junta.</span>
                </div>

                <div class="registo-benefit">
                    <strong><i class="bi bi-search"></i> Acompanhamento</strong>
                    <span>Consulte estados, respostas e atualizações.</span>
                </div>

                <div class="registo-benefit">
                    <strong><i class="bi bi-bell"></i> Notificações</strong>
                    <span>Receba avisos importantes sobre os seus processos.</span>
                </div>

                <div class="registo-benefit">
                    <strong><i class="bi bi-shield-lock"></i> Segurança</strong>
                    <span>Acesso reservado e protegido para cada cidadão.</span>
                </div>

            </aside>

        </div>

    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("toggleRegistoPassword");
    const input = document.getElementById("registoPassword");

    if (btn && input) {
        btn.addEventListener("click", function () {
            input.type = input.type === "password" ? "text" : "password";
            btn.innerHTML = input.type === "password" ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?>