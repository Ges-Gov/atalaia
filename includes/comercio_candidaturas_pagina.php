<?php
/**
 * /comercio.php em modo de candidaturas (includes/comercio_candidaturas.php): apresentação da página e formulário para
 * os negócios da freguesia pedirem para ser divulgados. Incluído pelo comercio.php ANTES do cabeçalho, para poder
 * tratar o envio e redirecionar. Os textos editam-se no backoffice (Economia local).
 */
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/validacao.php";
require_once __DIR__ . "/mail_helper.php";
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$erro = '';
$campos = ['nome_estabelecimento' => '', 'atividade' => '', 'morada' => '', 'responsavel' => '', 'email' => '', 'telefone' => '', 'website' => '', 'descricao' => ''];
if (empty($_SESSION['comercio_cand_token'])) {
    $_SESSION['comercio_cand_token'] = bin2hex(random_bytes(16));
}
$enviada = !empty($_SESSION['comercio_cand_enviada']);
unset($_SESSION['comercio_cand_enviada']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $k => $v) {
        $campos[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $abertoEm = (int)($_SESSION['comercio_cand_aberto_em'] ?? 0);
    $envios = array_filter($_SESSION['comercio_cand_envios'] ?? [], fn($t) => $t > time() - 3600);

    if (!hash_equals($_SESSION['comercio_cand_token'], (string)($_POST['token'] ?? ''))) {
        $erro = 'A sessão expirou. Volte a carregar a página e tente de novo.';
    } elseif (trim((string)($_POST['site_web'] ?? '')) !== '' || ($abertoEm && time() - $abertoEm < 3)) {
        $_SESSION['comercio_cand_enviada'] = 1;   // robô: finge que correu bem e não guarda nada
        header('Location: /comercio.php#candidatura');
        exit;
    } elseif (count($envios) >= 3) {
        $erro = 'Já enviou várias candidaturas na última hora. Tente mais tarde.';
    } elseif ($campos['nome_estabelecimento'] === '' || $campos['atividade'] === '' || $campos['morada'] === '' || $campos['responsavel'] === '' || $campos['telefone'] === '') {
        $erro = 'Preencha todos os campos obrigatórios (*).';
    } elseif (!emailValido($campos['email'])) {
        $erro = 'Indique um email válido e que exista, para a Junta o poder contactar.';
    } elseif (!telefoneValido($campos['telefone'])) {
        $erro = 'Indique um número de telefone válido (ex.: 912 345 678 ou 265 000 000).';
    } elseif (mb_strlen($campos['nome_estabelecimento']) > 200 || mb_strlen($campos['atividade']) > 150 || mb_strlen($campos['morada']) > 255
        || mb_strlen($campos['responsavel']) > 150 || mb_strlen($campos['website']) > 255 || mb_strlen($campos['descricao']) > 1000) {
        $erro = 'Algum dos textos é demasiado longo.';
    } elseif (empty($_POST['autorizacao'])) {
        $erro = 'Para enviar, tem de autorizar a divulgação e confirmar que pode usar a imagem.';
    } else {
        [$imagem, $erroImg] = comercioCandGuardarImagem($_FILES['imagem'] ?? []);
        if ($imagem === null) {
            $erro = $erroImg;
        } else {
            try {
                $pdo->prepare("INSERT INTO comercio_candidaturas (nome_estabelecimento, atividade, morada, responsavel, email, telefone, website, descricao, imagem, ip)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$campos['nome_estabelecimento'], $campos['atividade'], $campos['morada'], $campos['responsavel'], $campos['email'],
                               telefoneLimpo($campos['telefone']), $campos['website'] ?: null, $campos['descricao'] ?: null, $imagem, $_SERVER['REMOTE_ADDR'] ?? null]);
                $id = (int)$pdo->lastInsertId();
                $envios[] = time();
                $_SESSION['comercio_cand_envios'] = $envios;

                // aviso à Junta, com ligação para o backoffice (se o email falhar, a candidatura fica gravada na mesma)
                try {
                    $h = fn($t) => nl2br(htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'));
                    $base = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http')
                        . '://' . preg_replace('/[^a-z0-9.\-:]/i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
                    $link = $base . '/admin/comercio-candidaturas.php#candidatura-' . $id;
                    $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));
                    if ($emailJunta) {
                        enviarEmailSistema($emailJunta, 'Nova candidatura – Economia Local – ' . $campos['nome_estabelecimento'],
                            "<h2>Nova candidatura à divulgação na Economia Local</h2>"
                            . "<p><strong>Estabelecimento:</strong> {$h($campos['nome_estabelecimento'])}<br><strong>Atividade:</strong> {$h($campos['atividade'])}<br>"
                            . "<strong>Morada:</strong> {$h($campos['morada'])}</p>"
                            . "<p><strong>Responsável:</strong> {$h($campos['responsavel'])}<br><strong>Email:</strong> {$h($campos['email'])}<br>"
                            . "<strong>Telefone:</strong> {$h($campos['telefone'])}" . ($campos['website'] !== '' ? "<br><strong>Website / redes:</strong> {$h($campos['website'])}" : '') . "</p>"
                            . ($campos['descricao'] !== '' ? "<p><strong>Descrição:</strong><br>{$h($campos['descricao'])}</p>" : '')
                            . '<p style="margin-top:22px"><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#1f3b63;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:bold">Ver a candidatura e a imagem no backoffice</a></p>',
                            $campos['email']);
                    }
                    enviarEmailSistema($campos['email'], 'Candidatura recebida – Economia Local',
                        "<p>Olá {$h($campos['responsavel'])},</p>"
                        . "<p>Recebemos o pedido de divulgação de <strong>{$h($campos['nome_estabelecimento'])}</strong> na página Economia Local do site da Junta de Freguesia.</p>"
                        . "<p>A Junta vai analisar o pedido e entrará em contacto se for preciso alguma informação.</p>"
                        . "<p>Obrigado por apoiar o comércio local.</p><p><strong>" . htmlspecialchars((string)siteConfig('nome_site')) . "</strong></p>",
                        siteConfig('email'));
                } catch (Throwable $e) {
                    error_log('comercio candidaturas: aviso por email falhou: ' . $e->getMessage());
                }

                $_SESSION['comercio_cand_enviada'] = 1;
                header('Location: /comercio.php#candidatura');
                exit;
            } catch (Throwable $e) {
                error_log('comercio candidaturas: ' . $e->getMessage());
                @unlink(COMERCIO_CAND_DIR . $imagem);
                $erro = 'Não foi possível enviar agora. Tente mais tarde.';
            }
        }
    }
}
$_SESSION['comercio_cand_aberto_em'] = time();
$emailJunta = (string)siteConfig('email', '');

require_once __DIR__ . "/header.php";
?>

<style>
.cc-hero{background:linear-gradient(135deg, var(--cor-principal), color-mix(in srgb, var(--cor-principal) 55%, #000) 75%);color:#fff;padding:84px 0 110px;}
.cc-kicker{display:inline-flex;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.32);color:#fff;padding:8px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px;}
.cc-hero h1{font-size:clamp(34px,4.6vw,58px);margin:16px 0 14px;line-height:1.06;color:#fff;max-width:900px;}
.cc-hero p{max-width:780px;color:rgba(255,255,255,.92);font-size:18px;line-height:1.7;margin:0;}
.cc-shell{margin-top:-60px;position:relative;z-index:5;padding-bottom:70px;}
.cc-grelha{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:26px;align-items:start;}
.cc-card{background:#fff;border-radius:26px;box-shadow:0 22px 50px rgba(15,23,42,.09);padding:34px 36px;}
.cc-card h2{margin:0 0 12px;font-size:24px;line-height:1.25;color:#11151B;}
.cc-card > p{color:#475569;line-height:1.7;margin:0 0 14px;}
.cc-lista{list-style:none;margin:18px 0 0;padding:0;display:grid;gap:12px;}
.cc-lista li{display:grid;grid-template-columns:38px 1fr;gap:12px;align-items:start;color:#1f2933;line-height:1.5;font-size:15px;}
.cc-lista li i{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:color-mix(in srgb, var(--cor-principal) 10%, #fff);color:var(--cor-principal);font-size:18px;}
.cc-lista strong{display:block;color:#11151B;}
.cc-alt{margin-top:22px;padding:16px 18px;border-radius:16px;background:#f8fafc;border:1px solid #e5e7eb;font-size:14px;color:#475569;line-height:1.6;}
.cc-alt a{color:var(--cor-principal);font-weight:800;word-break:break-all;}
.cc-form label{display:block;font-weight:800;font-size:14px;margin:14px 0 6px;color:#1f2933;}
.cc-form .cc-duas{display:grid;grid-template-columns:1fr 1fr;gap:0 16px;}
.cc-form input[type=text],.cc-form input[type=email],.cc-form input[type=tel],.cc-form textarea{width:100%;box-sizing:border-box;border:1px solid #d6dde6;border-radius:14px;padding:12px 14px;font:inherit;font-size:16px;background:#fbfcfd;}
.cc-form textarea{min-height:110px;resize:vertical;}
.cc-form input:focus,.cc-form textarea:focus{outline:2px solid var(--cor-principal);outline-offset:1px;background:#fff;}
.cc-ficheiro{border:2px dashed #cbd5e1;border-radius:16px;padding:16px;background:#fbfcfd;}
.cc-ficheiro input{width:100%;font:inherit;font-size:15px;}
.cc-ficheiro small{display:block;margin-top:8px;color:#64748b;font-size:13px;line-height:1.5;}
.cc-consent{display:flex;gap:10px;align-items:flex-start;margin:18px 0 6px;font-size:14px;line-height:1.5;color:#475569;font-weight:400 !important;}
.cc-consent input{margin-top:3px;flex-shrink:0;width:18px;height:18px;}
.cc-form button{margin-top:16px;border:0;border-radius:14px;padding:14px 26px;font-weight:900;font-size:16px;cursor:pointer;background:var(--cor-secundaria);color:#11151B;display:inline-flex;gap:8px;align-items:center;}
.cc-isco{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
.cc-alerta{padding:14px 16px;border-radius:14px;margin-bottom:10px;font-weight:700;line-height:1.5;}
.cc-alerta.ok{background:#dcfce7;color:#166534;}
.cc-alerta.erro{background:#fee2e2;color:#991b1b;}
.cc-fecho{text-align:center;margin:34px auto 0;max-width:720px;font-size:19px;font-weight:800;color:var(--cor-principal);line-height:1.5;}
@media(max-width:960px){.cc-grelha{grid-template-columns:1fr;}}
@media(max-width:700px){
    .cc-hero{padding:56px 0 86px;}
    .cc-hero h1{font-size:clamp(28px,8vw,36px);}
    .cc-hero p{font-size:15px;}
    .cc-card{padding:24px 20px;border-radius:20px;}
    .cc-form .cc-duas{grid-template-columns:1fr;}
}
</style>

<main>
<section class="cc-hero">
    <div class="container">
        <span class="cc-kicker"><?= te('comercio.hero.kicker') ?></span>
        <h1><?= te('comercio.cand.titulo') ?></h1>
        <p><?= te('comercio.cand.intro') ?></p>
    </div>
</section>

<section class="cc-shell" id="candidatura">
    <div class="container">
        <div class="cc-grelha">
            <div class="cc-card">
                <h2><?= te('comercio.cand.chamada') ?></h2>
                <p><?= te('comercio.cand.texto') ?></p>
                <ul class="cc-lista">
                    <li><i class="bi bi-gift"></i><span><strong>Gratuito</strong>Para estabelecimentos e empresas com sede ou atividade na freguesia.</span></li>
                    <li><i class="bi bi-image"></i><span><strong>Logótipo ou imagem do estabelecimento</strong>JPG, PNG ou WEBP, na horizontal, até <?= COMERCIO_CAND_MAX_MB ?> MB.</span></li>
                    <li><i class="bi bi-aspect-ratio"></i><span><strong>Tamanho recomendado: 1200 × 800 píxeis</strong>No mínimo 800 × 533. Uma imagem nítida, sem texto muito pequeno.</span></li>
                    <li><i class="bi bi-check2-circle"></i><span><strong>Análise pela Junta</strong>Cada pedido é visto pela Junta de Freguesia antes de ser publicado.</span></li>
                </ul>
                <?php if ($emailJunta !== ''): ?>
                    <div class="cc-alt">
                        Prefere enviar por email? Mande os mesmos dados e a imagem para
                        <a href="mailto:<?= htmlspecialchars($emailJunta) ?>?subject=<?= rawurlencode('Divulgação – Economia Local') ?>"><?= htmlspecialchars($emailJunta) ?></a>,
                        com o assunto «Divulgação – Economia Local».
                    </div>
                <?php endif; ?>
            </div>

            <div class="cc-card">
                <h2>Pedido de divulgação</h2>
                <?php if ($enviada): ?>
                    <div class="cc-alerta ok">Obrigado! O seu pedido foi enviado à Junta de Freguesia. Vai receber uma confirmação por email.</div>
                <?php elseif ($erro): ?>
                    <div class="cc-alerta erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="post" action="/comercio.php#candidatura" enctype="multipart/form-data" class="cc-form" novalidate>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['comercio_cand_token']) ?>">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= COMERCIO_CAND_MAX_MB * 1024 * 1024 ?>">
                    <div class="cc-isco" aria-hidden="true">
                        <label for="cc-site-web">Não preencher</label>
                        <input type="text" id="cc-site-web" name="site_web" tabindex="-1" autocomplete="off">
                    </div>

                    <label for="cc-nome">Nome do estabelecimento ou empresa *</label>
                    <input type="text" id="cc-nome" name="nome_estabelecimento" maxlength="200" required value="<?= htmlspecialchars($campos['nome_estabelecimento']) ?>">

                    <div class="cc-duas">
                        <div>
                            <label for="cc-atividade">Atividade *</label>
                            <input type="text" id="cc-atividade" name="atividade" maxlength="150" required placeholder="Ex.: café, cabeleireiro, oficina" value="<?= htmlspecialchars($campos['atividade']) ?>">
                        </div>
                        <div>
                            <label for="cc-morada">Morada / localidade *</label>
                            <input type="text" id="cc-morada" name="morada" maxlength="255" required value="<?= htmlspecialchars($campos['morada']) ?>">
                        </div>
                    </div>

                    <div class="cc-duas">
                        <div>
                            <label for="cc-responsavel">Nome do responsável *</label>
                            <input type="text" id="cc-responsavel" name="responsavel" maxlength="150" required autocomplete="name" value="<?= htmlspecialchars($campos['responsavel']) ?>">
                        </div>
                        <div>
                            <label for="cc-telefone">Telefone *</label>
                            <input type="tel" id="cc-telefone" name="telefone" maxlength="20" required autocomplete="tel" inputmode="tel" value="<?= htmlspecialchars($campos['telefone']) ?>">
                        </div>
                    </div>

                    <label for="cc-email">Email *</label>
                    <input type="email" id="cc-email" name="email" maxlength="190" required autocomplete="email" value="<?= htmlspecialchars($campos['email']) ?>">

                    <label for="cc-website">Website ou redes sociais</label>
                    <input type="text" id="cc-website" name="website" maxlength="255" placeholder="Ex.: www.… ou página do Facebook / Instagram" value="<?= htmlspecialchars($campos['website']) ?>">

                    <label for="cc-descricao">Breve descrição</label>
                    <textarea id="cc-descricao" name="descricao" maxlength="1000" placeholder="O que faz, horário, o que o distingue…"><?= htmlspecialchars($campos['descricao']) ?></textarea>

                    <label for="cc-imagem">Logótipo ou imagem do estabelecimento *</label>
                    <div class="cc-ficheiro">
                        <input type="file" id="cc-imagem" name="imagem" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <small>JPG, PNG ou WEBP · na horizontal · recomendado 1200 × 800 píxeis · até <?= COMERCIO_CAND_MAX_MB ?> MB</small>
                    </div>

                    <label class="cc-consent">
                        <input type="checkbox" name="autorizacao" value="1" required>
                        <span>Autorizo a Junta de Freguesia a divulgar no site os dados do estabelecimento e a imagem enviada, e confirmo que tenho direito a usar essa imagem.
                            Os meus dados de contacto servem só para tratar este pedido, nos termos da <a href="/politica-privacidade.php">política de privacidade</a>.</span>
                    </label>

                    <button type="submit"><i class="bi bi-send"></i> Enviar pedido</button>
                </form>
            </div>
        </div>

        <p class="cc-fecho"><?= te('comercio.cand.fecho') ?></p>
    </div>
</section>
</main>

<?php require_once __DIR__ . "/footer.php"; ?>
