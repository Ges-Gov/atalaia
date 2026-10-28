<?php
// Formulário de sugestões (migração 035). As sugestões ficam na BD e leem-se no backoffice
// (Junta Virtual › Sugestões); a Junta recebe também um aviso por email, se o email estiver configurado.
require_once "includes/db.php";
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$erro = '';
$enviado = false;
$campos = ['nome' => '', 'email' => '', 'assunto' => '', 'mensagem' => ''];

if (empty($_SESSION['sugestoes_token'])) {
    $_SESSION['sugestoes_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $k => $v) {
        $campos[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $abertoEm = (int)($_SESSION['sugestoes_aberto_em'] ?? 0);
    $envios = array_filter($_SESSION['sugestoes_envios'] ?? [], fn($t) => $t > time() - 3600);

    if (!hash_equals($_SESSION['sugestoes_token'], (string)($_POST['token'] ?? ''))) {
        $erro = 'A sessão expirou. Volte a carregar a página e tente de novo.';
    } elseif (trim((string)($_POST['website'] ?? '')) !== '' || ($abertoEm && time() - $abertoEm < 3)) {
        $enviado = true;                       // robô: finge que correu bem e não guarda nada
    } elseif (count($envios) >= 5) {
        $erro = 'Já enviou várias sugestões na última hora. Tente mais tarde.';
    } elseif ($campos['nome'] === '' || $campos['assunto'] === '' || $campos['mensagem'] === '') {
        $erro = 'Preencha o nome, o assunto e a mensagem.';
    } elseif (!filter_var($campos['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'Indique um email válido, para a Junta lhe poder responder.';
    } elseif (empty($_POST['consentimento'])) {
        $erro = 'Para enviar, tem de aceitar o tratamento dos dados indicados.';
    } elseif (mb_strlen($campos['mensagem']) > 5000 || mb_strlen($campos['assunto']) > 200 || mb_strlen($campos['nome']) > 150) {
        $erro = 'O texto é demasiado longo.';
    } else {
        try {
            $pdo->prepare("INSERT INTO sugestoes (nome, email, assunto, mensagem, ip) VALUES (?, ?, ?, ?, ?)")
                ->execute([$campos['nome'], $campos['email'], $campos['assunto'], $campos['mensagem'], $_SERVER['REMOTE_ADDR'] ?? null]);
            $envios[] = time();
            $_SESSION['sugestoes_envios'] = $envios;
            $enviado = true;

            // Aviso por email à Junta (opcional: se o email não estiver configurado, a sugestão fica na BD na mesma)
            try {
                require_once __DIR__ . "/includes/config.php";
                require_once __DIR__ . "/includes/mail_helper.php";
                $para = siteConfig('email', '');
                if ($para !== '' && function_exists('enviarEmailSistema')) {
                    $h = fn($t) => nl2br(htmlspecialchars($t, ENT_QUOTES, 'UTF-8'));
                    enviarEmailSistema($para, 'Nova sugestão no site: ' . $campos['assunto'],
                        "<p><strong>Nome:</strong> {$h($campos['nome'])}<br><strong>Email:</strong> {$h($campos['email'])}</p>"
                        . "<p><strong>Assunto:</strong> {$h($campos['assunto'])}</p><p>{$h($campos['mensagem'])}</p>"
                        . "<p>Também disponível no backoffice, em Junta Virtual › Sugestões.</p>", $campos['email']);
                }
            } catch (Throwable $e) {
                error_log('sugestoes: aviso por email falhou: ' . $e->getMessage());
            }
            $campos = ['nome' => '', 'email' => '', 'assunto' => '', 'mensagem' => ''];
        } catch (Throwable $e) {
            error_log('sugestoes: ' . $e->getMessage());
            $erro = 'Não foi possível enviar agora. Tente mais tarde.';
        }
    }
}
$_SESSION['sugestoes_aberto_em'] = time();

require_once "includes/header.php";
?>

<style>
.sugestoes-hero{
    background:linear-gradient(135deg, var(--cor-principal), color-mix(in srgb, var(--cor-principal) 55%, #000) 75%);
    color:#fff;
    padding:84px 0 104px;
}
.sugestoes-hero .container{position:relative;z-index:2;}
.sugestoes-kicker{
    display:inline-flex;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.32);color:#fff;
    padding:8px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px;
}
.sugestoes-hero h1{font-size:clamp(36px,5vw,64px);margin:16px 0 12px;line-height:1.04;color:#fff;}
.sugestoes-hero p{max-width:760px;color:rgba(255,255,255,.92);font-size:19px;line-height:1.7;margin:0;}
.sugestoes-shell{margin-top:-56px;position:relative;z-index:5;padding-bottom:70px;}
.sugestoes-card{
    max-width:760px;margin:0 auto;background:#fff;border-radius:26px;box-shadow:0 22px 50px rgba(15,23,42,.09);padding:38px 42px;
}
.sugestoes-card label{display:block;font-weight:800;font-size:14px;margin:16px 0 6px;color:#1f2933;}
.sugestoes-card input[type=text],.sugestoes-card input[type=email],.sugestoes-card textarea{
    width:100%;box-sizing:border-box;border:1px solid #d6dde6;border-radius:14px;padding:13px 15px;font:inherit;font-size:16px;background:#fbfcfd;
}
.sugestoes-card textarea{min-height:170px;resize:vertical;}
.sugestoes-card input:focus,.sugestoes-card textarea:focus{outline:2px solid var(--cor-principal);outline-offset:1px;background:#fff;}
.sugestoes-consent{display:flex;gap:10px;align-items:flex-start;margin:18px 0 6px;font-size:14px;line-height:1.5;color:#475569;}
.sugestoes-consent input{margin-top:3px;flex-shrink:0;width:18px;height:18px;}
.sugestoes-card button{
    margin-top:18px;border:0;border-radius:14px;padding:14px 26px;font-weight:900;font-size:16px;cursor:pointer;
    background:var(--cor-secundaria);color:#11151B;
}
.sugestoes-isco{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
.sugestoes-alerta{padding:14px 16px;border-radius:14px;margin-bottom:6px;font-weight:700;}
.sugestoes-alerta.ok{background:#dcfce7;color:#166534;}
.sugestoes-alerta.erro{background:#fee2e2;color:#991b1b;}
@media(max-width:700px){
    .sugestoes-hero{padding:56px 0 86px;}
    .sugestoes-hero h1{font-size:clamp(28px,8vw,38px);}
    .sugestoes-hero p{font-size:15px;}
    .sugestoes-card{padding:24px 20px;border-radius:20px;}
}
</style>

<section class="sugestoes-hero">
    <div class="container">
        <span class="sugestoes-kicker">Junta de Freguesia</span>
        <h1>Sugestões</h1>
        <p>Tem uma ideia ou uma sugestão para a freguesia? Escreva-nos — todas as mensagens são lidas pela Junta.</p>
    </div>
</section>

<section class="sugestoes-shell">
    <div class="container">
        <div class="sugestoes-card">
            <?php if ($enviado): ?>
                <div class="sugestoes-alerta ok">Obrigado! A sua sugestão foi enviada à Junta de Freguesia.</div>
            <?php elseif ($erro): ?>
                <div class="sugestoes-alerta erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="post" action="/sugestoes.php" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['sugestoes_token']) ?>">
                <div class="sugestoes-isco" aria-hidden="true">
                    <label for="sug-website">Não preencher</label>
                    <input type="text" id="sug-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <label for="sug-nome">Nome *</label>
                <input type="text" id="sug-nome" name="nome" maxlength="150" required value="<?= htmlspecialchars($campos['nome']) ?>">

                <label for="sug-email">Email *</label>
                <input type="email" id="sug-email" name="email" maxlength="190" required value="<?= htmlspecialchars($campos['email']) ?>">

                <label for="sug-assunto">Assunto *</label>
                <input type="text" id="sug-assunto" name="assunto" maxlength="200" required value="<?= htmlspecialchars($campos['assunto']) ?>">

                <label for="sug-mensagem">Mensagem *</label>
                <textarea id="sug-mensagem" name="mensagem" maxlength="5000" required><?= htmlspecialchars($campos['mensagem']) ?></textarea>

                <label class="sugestoes-consent">
                    <input type="checkbox" name="consentimento" value="1" required>
                    <span>Aceito que a Junta de Freguesia trate os dados indicados para responder a esta sugestão,
                        nos termos da <a href="/politica-privacidade.php">política de privacidade</a>.</span>
                </label>

                <button type="submit">Enviar sugestão</button>
            </form>
        </div>
    </div>
</section>

<?php require_once "includes/footer.php"; ?>
