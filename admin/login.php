<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/tema.php";

$loginLogo = '';
try {
    $loginLogo = (string) $pdo->query("SELECT logo FROM configuracoes_site LIMIT 1")->fetchColumn();
} catch (Throwable $e) {
    $loginLogo = '';
}

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if (isset($_SESSION['admin'])) {
    if ($redirect) header("Location: " . $redirect);
    elseif (($_SESSION['admin_tipo'] ?? '') === 'vogal') header("Location: ../area-vogais.php");
    elseif (($_SESSION['admin_tipo'] ?? '') === 'presidente_assembleia') header("Location: assembleia-votacoes.php");
    elseif (($_SESSION['admin_tipo'] ?? '') === 'admin_denuncias') header("Location: denuncias.php");
    else header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = md5($_POST['password'] ?? '');

    

















$stmt = $pdo->prepare("
    SELECT *
    FROM admin_utilizadores
    WHERE username = ?
    AND password = ?
    AND ativo = 1
    LIMIT 1
");

$stmt->execute([$user, $pass]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {

    $_SESSION['admin'] = $admin['username'];

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_nome'] = $admin['nome'] ?: $admin['username'];
    $_SESSION['admin_tipo'] = $admin['tipo'] ?: 'admin';

    if ($redirect) header("Location: " . $redirect);
    elseif (($admin['tipo'] ?? '') === 'vogal') header("Location: ../area-vogais.php");
    elseif (($admin['tipo'] ?? '') === 'presidente_assembleia') header("Location: assembleia-votacoes.php");
    elseif (($admin['tipo'] ?? '') === 'admin_denuncias') header("Location: denuncias.php");
    else header("Location: dashboard.php");
    exit;

} else {

    $erro = "Utilizador ou password inválidos.";

}

























}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login | AAEJ Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(212,170,0,.22), transparent 35%),
                radial-gradient(circle at bottom left, rgba(255,255,255,.10), transparent 32%),
                linear-gradient(135deg, rgba(36,42,50,.72), rgba(17,21,28,.80)),
                url("../assets/img/freguesia-1.jpg") center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1f2933;
            overflow-x: hidden;
        }

        .login-page {
            width: min(1180px, 94%);
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 34px;
            align-items: center;
            padding: 30px 0;
        }

        .login-brand {
            color: white;
            padding: 25px;
        }

        .login-kicker {
            display: inline-block;
            color: #D4AA00;
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 1px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .login-brand h1 {
            font-size: clamp(42px, 5vw, 68px);
            line-height: 1.02;
            margin: 0 0 20px;
            letter-spacing: -1.5px;
        }

        .login-brand p {
            margin: 0;
            color: #dbeafe;
            font-size: 18px;
            line-height: 1.75;
            max-width: 610px;
        }

        .login-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 34px;
        }

        .login-feature {
            background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 22px;
            padding: 18px;
            backdrop-filter: blur(10px);
            box-shadow: 0 14px 35px rgba(0,0,0,.12);
        }

        .login-feature strong {
            display: block;
            color: white;
            margin-bottom: 7px;
            font-size: 15px;
        }

        .login-feature span {
            color: #dbeafe;
            font-size: 14px;
            line-height: 1.55;
        }

        .login-card {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 34px;
            padding: 38px;
            box-shadow: 0 30px 80px rgba(0,0,0,.30);
            backdrop-filter: blur(18px);
        }

        .login-logo-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: #D4AA00;
            color: #11151B;
            display: grid;
            place-items: center;
            font-weight: 900;
            font-size: 22px;
            box-shadow: 0 14px 34px rgba(0,0,0,.18);
            flex-shrink: 0;
        }

        .login-logo--img {
            background: #fff;
            padding: 7px;
        }
        .login-logo--img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .login-logo-row h2 {
            margin: 0;
            color: white;
            font-size: 30px;
        }

        .login-logo-row small {
            display: block;
            color: #dbeafe;
            margin-top: 4px;
            font-weight: 700;
        }

        .login-card form {
            background: transparent;
            padding: 0;
            box-shadow: none;
            border-radius: 0;
        }

        .login-card label {
            display: block;
            color: white;
            font-weight: 800;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .login-card input {
            width: 100%;
            height: 54px;
            border: 1px solid rgba(255,255,255,.20);
            background: rgba(255,255,255,.13);
            color: white;
            border-radius: 18px;
            padding: 0 16px;
            margin-bottom: 18px;
            font-size: 15px;
            outline: none;
            transition: .25s ease;
        }

        .login-card input::placeholder {
            color: #dbeafe;
        }

        .login-card input:focus {
            background: rgba(255,255,255,.20);
            border-color: #D4AA00;
            box-shadow: 0 0 0 4px rgba(212,170,0,.18);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 54px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 35px;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 12px;
            background: rgba(255,255,255,.14);
            color: white;
            cursor: pointer;
            font-size: 17px;
            display: grid;
            place-items: center;
            transition: .2s ease;
        }

        .toggle-password:hover {
            background: rgba(255,255,255,.22);
        }

        .login-submit {
            width: 100%;
            height: 56px;
            border: 0;
            border-radius: 18px;
            background: #D4AA00;
            color: #11151B;
            font-weight: 900;
            font-size: 16px;
            cursor: pointer;
            transition: .25s ease;
            box-shadow: 0 16px 36px rgba(0,0,0,.20);
        }

        .login-submit:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
        }

        .erro {
            background: #fee2e2;
            color: #991b1b;
            padding: 13px 15px;
            border-radius: 15px;
            margin-bottom: 18px;
            font-weight: 900;
            border-left: 5px solid #ef4444;
        }

        .login-bottom {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .back-link {
            color: #dbeafe;
            text-decoration: none;
            font-weight: 800;
            transition: .2s ease;
        }

        .back-link:hover {
            color: #D4AA00;
        }

        .login-clock {
            color: #D4AA00;
            font-weight: 900;
            font-size: 13px;
        }

        .login-security {
            margin-top: 22px;
            background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.13);
            color: #dbeafe;
            padding: 14px;
            border-radius: 18px;
            font-size: 13px;
            line-height: 1.55;
        }

        .login-security strong {
            color: white;
        }

        @media (max-width: 950px) {
            body {
                align-items: flex-start;
            }

            .login-page {
                grid-template-columns: 1fr;
                padding: 20px 0;
            }

            .login-brand {
                display: none;
            }

            .login-card {
                padding: 28px;
                border-radius: 28px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <section class="login-brand">
        <span class="login-kicker">
            Sistema Inteligente de Gestão de Freguesia
        </span>

        <h1>AAEJ Digital</h1>

        <p>
            Plataforma moderna para gestão municipal, atendimento digital,
            Junta Virtual, notificações realtime, relatórios PDF e serviços inteligentes.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <strong><i class="bi bi-bar-chart"></i> Dashboard SaaS</strong>
                <span>Indicadores, gráficos, atividade recente e visão geral da freguesia.</span>
            </div>

            <div class="login-feature">
                <strong><i class="bi bi-bell"></i> Realtime</strong>
                <span>Notificações, alertas e comunicação digital com os cidadãos.</span>
            </div>

            <div class="login-feature">
                <strong><i class="bi bi-bank"></i> Junta Virtual</strong>
                <span>Pedidos, marcações, requerimentos e documentos online.</span>
            </div>

            <div class="login-feature">
                <strong><i class="bi bi-file-earmark-text"></i> Relatórios Premium</strong>
                <span>Exportação PDF institucional pronta para reuniões e arquivo.</span>
            </div>
        </div>
    </section>

    <section class="login-card">

        <div class="login-logo-row">
            <?php if (!empty($loginLogo)): ?>
                <div class="login-logo login-logo--img"><img src="/assets/img/<?= htmlspecialchars($loginLogo) ?>" alt="Brasão da freguesia"></div>
            <?php else: ?>
                <div class="login-logo"><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></div>
            <?php endif; ?>

            <div>
                <h2>Administração</h2>
                <small>Backoffice AAEJ Digital</small>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <label>Utilizador</label>
            <input type="text" name="username" placeholder="Introduza o utilizador" required autocomplete="username">

            <div class="password-wrap">
                <label>Password</label>
                <input type="password" name="password" id="password" placeholder="Introduza a password" required autocomplete="current-password">

                <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Mostrar ou esconder password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <button type="submit" class="login-submit">
                Entrar no painel
            </button>
        </form>

        <div class="login-security">
            <strong>Acesso reservado.</strong><br>
            Utilize as suas credenciais administrativas para gerir os conteúdos,
            pedidos, documentos e serviços digitais da freguesia.
        </div>

        <div class="login-bottom">
            <a class="back-link" href="../index.php">← Voltar ao site</a>
            <div class="login-clock" id="clock"></div>
        </div>

    </section>

</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");

    if (!input) return;

    input.type = input.type === "password" ? "text" : "password";
}

function updateClock() {
    const clock = document.getElementById("clock");

    if (!clock) return;

    const now = new Date();

    clock.innerHTML =
        now.toLocaleDateString("pt-PT") +
        " • " +
        now.toLocaleTimeString("pt-PT");
}

setInterval(updateClock, 1000);
updateClock();
</script>

</body>
</html>