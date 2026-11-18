<?php require_once "includes/header.php"; ?>
<?php require_once "includes/mail_helper.php"; ?>

<?php
$erro = '';
$errosCampo = [];   // erros junto a cada campo (acessibilidade, 18/11/2026)
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cada erro fica junto ao seu campo e diz o que fazer (checklist «Transação» da AMA, 18/11/2026)
    if ($nome === '') $errosCampo['nome'] = 'Indique o seu nome completo.';
    if ($email === '') $errosCampo['email'] = 'Indique o seu email (ex.: nome@exemplo.pt). É com ele que vai entrar.';
    elseif (!emailValido($email)) $errosCampo['email'] = 'Indique um email válido e que exista (ex.: nome@exemplo.pt).';
    if ($telefone !== '' && !telefoneValido($telefone)) $errosCampo['telefone'] = 'Indique um número de telefone válido (ex.: 912 345 678, ou com o indicativo do país se for estrangeiro).';
    if ($password === '') $errosCampo['password'] = 'Escolha uma palavra-passe com pelo menos 8 carateres.';
    elseif (mb_strlen($password) < 8) $errosCampo['password'] = 'A palavra-passe tem de ter pelo menos 8 carateres.';

    if (!$errosCampo) {
        {
            $stmt = $pdo->prepare("SELECT id FROM cidadaos WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errosCampo['email'] = 'Já existe uma conta com este email. Entre com ele, ou use «Esqueci-me da password» se não se lembrar da palavra-passe.';
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
    }
}
?>

<section class="cidadao-registo-hero">
    <div class="container">
        <div>
            <span><?= te('cidadao_registo.hero.kicker') ?></span>
            <h1><?= te('cidadao_registo.hero.titulo') ?></h1>
            <p><?= te('cidadao_registo.hero.subtitulo') ?></p>
        </div>

        <div class="cidadao-registo-icon"><i class="bi bi-pencil-square"></i></div>
    </div>
</section>

<section class="section cidadao-registo-section">
    <div class="container">

        <div class="cidadao-registo-wrapper">

            <div class="cidadao-registo-card">

                <span class="section-kicker"><?= te('cidadao_registo.formulario.kicker') ?></span>
                <h2><?= te('cidadao_registo.formulario.titulo') ?></h2>
                <p><?= te('cidadao_registo.formulario.intro') ?></p>

                <?php if ($sucesso): ?>
                    <div class="alerta-sucesso registo-alert">
                        <?= te('cidadao_registo.formulario.sucesso') ?>
                    </div>

                    <a class="btn btn-registo-full" href="/cidadao-login.php">
                        <?= te('cidadao_registo.formulario.sucesso_botao') ?>
                    </a>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alerta-erro registo-alert">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$sucesso): ?>
                    <form method="POST" class="cidadao-registo-form">
                        <?php /* etiqueta visível por cima de cada caixa, * nos obrigatórios, erros junto ao campo (18/11/2026) */ ?>
                        <?= resumoErros($errosCampo, ['nome' => 'registoNome', 'email' => 'registoEmail', 'telefone' => 'registoTelefone', 'password' => 'registoPassword']) ?>
                        <?= notaObrigatorios() ?>

                        <div class="campo">
                            <?= rotulo('registoNome', tx('cidadao_registo.formulario.ph_nome'), true) ?>
                            <div class="registo-input-group">
                                <span><i class="bi bi-person"></i></span>
                                <input type="text" id="registoNome" name="nome" autocomplete="name" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required<?= erroAttrs($errosCampo, 'nome') ?>>
                            </div>
                            <?= erroCampo($errosCampo, 'nome') ?>
                        </div>

                        <div class="campo">
                            <?= rotulo('registoEmail', tx('cidadao_registo.formulario.ph_email'), true) ?>
                            <div class="registo-input-group">
                                <span><i class="bi bi-envelope"></i></span>
                                <input type="email" id="registoEmail" name="email" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required<?= erroAttrs($errosCampo, 'email') ?>>
                            </div>
                            <?= erroCampo($errosCampo, 'email') ?>
                        </div>

                        <div class="campo">
                            <?= rotulo('registoTelefone', tx('cidadao_registo.formulario.ph_telefone'), false) ?>
                            <div class="registo-input-group">
                                <span><i class="bi bi-telephone"></i></span>
                                <input type="tel" id="registoTelefone" inputmode="tel" autocomplete="tel" maxlength="20" name="telefone" placeholder="Ex.: 912 345 678" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>"<?= erroAttrs($errosCampo, 'telefone') ?>>
                            </div>
                            <?= erroCampo($errosCampo, 'telefone') ?>
                        </div>

                        <div class="campo">
                            <?= rotulo('registoPassword', tx('cidadao_registo.formulario.ph_password'), true) ?>
                            <div class="registo-input-group password-group">
                                <span><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="registoPassword" minlength="8" autocomplete="new-password" placeholder="Pelo menos 8 carateres" required<?= erroAttrs($errosCampo, 'password') ?>>

                                <button type="button" class="toggle-password" id="toggleRegistoPassword" aria-label="Mostrar palavra-passe" aria-pressed="false" aria-controls="registoPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?= erroCampo($errosCampo, 'password') ?>
                        </div>

                        <button class="btn btn-registo-full" type="submit">
                            <?= te('cidadao_registo.formulario.botao') ?>
                        </button>

                        <div class="cidadao-registo-links">
                            <a href="/cidadao-login.php">
                                <?= te('cidadao_registo.formulario.ja_tenho') ?>
                            </a>
                        </div>

                    </form>
                <?php endif; ?>

            </div>

            <div class="cidadao-registo-side">

                <span class="section-kicker"><?= te('cidadao_registo.ajuda.kicker') ?></span>

                <h2><?= te('cidadao_registo.ajuda.titulo') ?></h2>

                <div class="registo-benefit">
                    <strong><i class="bi bi-envelope-paper"></i> <?= te('cidadao_registo.ajuda.i1_titulo') ?></strong>
                    <span><?= te('cidadao_registo.ajuda.i1_texto') ?></span>
                </div>

                <div class="registo-benefit">
                    <strong><i class="bi bi-search"></i> <?= te('cidadao_registo.ajuda.i2_titulo') ?></strong>
                    <span><?= te('cidadao_registo.ajuda.i2_texto') ?></span>
                </div>

                <div class="registo-benefit">
                    <strong><i class="bi bi-bell"></i> <?= te('cidadao_registo.ajuda.i3_titulo') ?></strong>
                    <span><?= te('cidadao_registo.ajuda.i3_texto') ?></span>
                </div>

                <div class="registo-benefit">
                    <strong><i class="bi bi-shield-lock"></i> <?= te('cidadao_registo.ajuda.i4_titulo') ?></strong>
                    <span><?= te('cidadao_registo.ajuda.i4_texto') ?></span>
                </div>

            </div>

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
            btn.innerHTML = input.type === "password" ? '<i class="bi bi-eye" aria-hidden="true"></i>' : '<i class="bi bi-eye-slash" aria-hidden="true"></i>';
            btn.setAttribute("aria-pressed", input.type === "text" ? "true" : "false");
            btn.setAttribute("aria-label", input.type === "text" ? "Esconder palavra-passe" : "Mostrar palavra-passe");
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?>