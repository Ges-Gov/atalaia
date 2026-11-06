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
/* 06/11/2026 (2.ª versão): menos texto, nada sobreposto — topo curto, 3 passos, formulário centrado */
.cc-hero{background:linear-gradient(135deg, var(--cor-principal), color-mix(in srgb, var(--cor-principal) 55%, #000) 80%);color:#fff;padding:90px 0 80px;text-align:center;}
.cc-kicker{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);color:#fff;padding:8px 16px;border-radius:999px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;font-size:12px;}
.cc-hero h1{font-size:clamp(36px,5vw,62px);margin:18px auto 14px;line-height:1.05;color:#fff;max-width:820px;}
.cc-hero p{max-width:620px;margin:0 auto;color:rgba(255,255,255,.9);font-size:19px;line-height:1.6;}
.cc-cta{display:inline-flex;align-items:center;gap:10px;margin-top:30px;background:var(--cor-secundaria);color:#11151B;font-weight:900;font-size:17px;padding:15px 28px;border-radius:999px;text-decoration:none;box-shadow:0 12px 30px rgba(0,0,0,.25);transition:transform .15s;}
.cc-cta:hover{transform:translateY(-2px);color:#11151B;}

.cc-passos{padding:64px 0 10px;}
.cc-passos ol{list-style:none;margin:0 auto;padding:0;display:grid;grid-template-columns:repeat(3,1fr);gap:22px;max-width:1000px;counter-reset:passo;}
.cc-passos li{background:#fff;border:1px solid #e8edf3;border-radius:22px;padding:28px 24px;text-align:center;box-shadow:0 10px 30px rgba(15,23,42,.05);}
.cc-passos li i{display:grid;place-items:center;width:62px;height:62px;margin:0 auto 16px;border-radius:50%;font-size:26px;
    background:color-mix(in srgb, var(--cor-principal) 10%, #fff);color:var(--cor-principal);}
.cc-passos strong{display:block;font-size:18px;color:#11151B;margin-bottom:6px;}
.cc-passos span{color:#64748b;font-size:15px;line-height:1.5;}

.cc-pedido{padding:44px 0 80px;scroll-margin-top:90px;}
.cc-card{max-width:720px;margin:0 auto;background:#fff;border-radius:26px;box-shadow:0 22px 50px rgba(15,23,42,.08);padding:38px 40px;border:1px solid #eef2f7;}
.cc-card h2{margin:0;font-size:28px;color:#11151B;text-align:center;}
.cc-sub{margin:8px 0 22px;text-align:center;color:#64748b;font-size:15px;line-height:1.5;}
.cc-form label{display:block;font-weight:800;font-size:14px;margin:14px 0 6px;color:#1f2933;}
.cc-duas{display:grid;grid-template-columns:1fr 1fr;gap:0 16px;}
.cc-form input[type=text],.cc-form input[type=email],.cc-form input[type=tel],.cc-form textarea{width:100%;box-sizing:border-box;border:1px solid #d6dde6;border-radius:14px;padding:12px 14px;font:inherit;font-size:16px;background:#fbfcfd;}
.cc-form textarea{min-height:96px;resize:vertical;}
.cc-form input:focus,.cc-form textarea:focus{outline:2px solid var(--cor-principal);outline-offset:1px;background:#fff;}
.cc-form .cc-ficheiro{display:flex;flex-direction:column;align-items:center;gap:8px;margin:0;font-weight:400;border:2px dashed #cbd5e1;border-radius:18px;padding:22px 16px;background:#fbfcfd;text-align:center;cursor:pointer;}
.cc-ficheiro:hover{border-color:var(--cor-principal);}
.cc-ficheiro > i{font-size:30px;color:var(--cor-principal);}
.cc-ficheiro input{max-width:100%;font:inherit;font-size:14px;}
.cc-ficheiro small{display:block;color:#64748b;font-size:13px;font-weight:400;}
.cc-consent{display:flex;gap:10px;align-items:flex-start;margin:18px 0 4px;font-size:13px !important;line-height:1.5;color:#64748b !important;font-weight:400 !important;}
.cc-consent input{margin-top:2px;flex-shrink:0;width:18px;height:18px;}
.cc-enviar{display:flex;width:100%;justify-content:center;align-items:center;gap:8px;margin-top:20px;border:0;border-radius:14px;padding:15px 26px;font-weight:900;font-size:17px;cursor:pointer;background:var(--cor-secundaria);color:#11151B;}
.cc-isco{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
.cc-alerta{padding:14px 16px;border-radius:14px;margin-bottom:12px;font-weight:700;line-height:1.5;text-align:center;}
.cc-alerta.ok{background:#dcfce7;color:#166534;}
.cc-alerta.erro{background:#fee2e2;color:#991b1b;}
.cc-alt{max-width:720px;margin:18px auto 0;text-align:center;color:#64748b;font-size:14px;line-height:1.6;}
.cc-alt a{color:var(--cor-principal);font-weight:800;}
@media(max-width:820px){.cc-passos ol{grid-template-columns:1fr;gap:12px;}.cc-passos li{display:grid;grid-template-columns:54px 1fr;text-align:left;gap:4px 14px;padding:18px;align-items:center;}
    .cc-passos li i{grid-row:span 2;width:54px;height:54px;margin:0;font-size:22px;}.cc-passos strong{margin:0;}}
@media(max-width:700px){
    .cc-hero{padding:58px 0 54px;}
    .cc-hero h1{font-size:clamp(30px,9vw,40px);}
    .cc-hero p{font-size:16px;}
    .cc-passos{padding-top:34px;}
    .cc-card{padding:26px 20px;border-radius:20px;}
    .cc-duas{grid-template-columns:1fr;}
}
</style>

<main>
<section class="cc-hero">
    <div class="container">
        <span class="cc-kicker"><i class="bi bi-shop"></i> <?= te('comercio.hero.kicker') ?></span>
        <h1><?= te('comercio.cand.titulo') ?></h1>
        <p><?= te('comercio.cand.intro') ?></p>
        <a class="cc-cta" href="#pedido">Quero aparecer aqui <i class="bi bi-arrow-down"></i></a>
    </div>
</section>

<section class="cc-passos" aria-label="Como funciona">
    <div class="container">
        <ol>
            <li><i class="bi bi-pencil-square"></i><strong>Preencha o pedido</strong><span>Nome do negócio, atividade e contactos.</span></li>
            <li><i class="bi bi-image"></i><strong>Envie uma imagem</strong><span>O logótipo ou uma foto, na horizontal.</span></li>
            <li><i class="bi bi-patch-check"></i><strong>Fica no site</strong><span>Depois de aprovado pela Junta. Gratuito.</span></li>
        </ol>
    </div>
</section>

<section class="cc-pedido" id="pedido">
    <div class="container">
        <div class="cc-card" id="candidatura">
            <h2><?= te('comercio.cand.chamada') ?></h2>
            <p class="cc-sub"><?= te('comercio.cand.texto') ?></p>

            <?php if ($enviada): ?>
                <div class="cc-alerta ok"><i class="bi bi-check-circle"></i> Obrigado! O pedido foi enviado à Junta de Freguesia. Vai receber uma confirmação por email.</div>
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

                <div class="cc-duas">
                    <div>
                        <label for="cc-nome">Nome do negócio *</label>
                        <input type="text" id="cc-nome" name="nome_estabelecimento" maxlength="200" required value="<?= htmlspecialchars($campos['nome_estabelecimento']) ?>">
                    </div>
                    <div>
                        <label for="cc-atividade">Atividade *</label>
                        <input type="text" id="cc-atividade" name="atividade" maxlength="150" required placeholder="Ex.: café, cabeleireiro" value="<?= htmlspecialchars($campos['atividade']) ?>">
                    </div>
                </div>

                <label for="cc-morada">Morada *</label>
                <input type="text" id="cc-morada" name="morada" maxlength="255" required value="<?= htmlspecialchars($campos['morada']) ?>">

                <div class="cc-duas">
                    <div>
                        <label for="cc-responsavel">Responsável *</label>
                        <input type="text" id="cc-responsavel" name="responsavel" maxlength="150" required autocomplete="name" value="<?= htmlspecialchars($campos['responsavel']) ?>">
                    </div>
                    <div>
                        <label for="cc-telefone">Telefone *</label>
                        <input type="tel" id="cc-telefone" name="telefone" maxlength="20" required autocomplete="tel" inputmode="tel" value="<?= htmlspecialchars($campos['telefone']) ?>">
                    </div>
                </div>

                <div class="cc-duas">
                    <div>
                        <label for="cc-email">Email *</label>
                        <input type="email" id="cc-email" name="email" maxlength="190" required autocomplete="email" value="<?= htmlspecialchars($campos['email']) ?>">
                    </div>
                    <div>
                        <label for="cc-website">Website ou redes sociais</label>
                        <input type="text" id="cc-website" name="website" maxlength="255" value="<?= htmlspecialchars($campos['website']) ?>">
                    </div>
                </div>

                <label for="cc-descricao">Breve descrição</label>
                <textarea id="cc-descricao" name="descricao" maxlength="1000" placeholder="O que faz, horário…"><?= htmlspecialchars($campos['descricao']) ?></textarea>

                <label for="cc-imagem">Logótipo ou imagem *</label>
                <label class="cc-ficheiro" for="cc-imagem">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <input type="file" id="cc-imagem" name="imagem" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                    <small>JPG, PNG ou WEBP · horizontal · ideal 1200 × 800 px · até <?= COMERCIO_CAND_MAX_MB ?> MB</small>
                </label>

                <label class="cc-consent">
                    <input type="checkbox" name="autorizacao" value="1" required>
                    <span>Autorizo a divulgação no site destes dados e da imagem, que tenho direito a usar. Os contactos servem só para tratar o pedido
                        (<a href="/politica-privacidade.php">política de privacidade</a>).</span>
                </label>

                <button type="submit" class="cc-enviar"><i class="bi bi-send"></i> Enviar pedido</button>
            </form>
        </div>

        <?php if ($emailJunta !== ''): ?>
            <p class="cc-alt">Prefere por email? Envie para
                <a href="mailto:<?= htmlspecialchars($emailJunta) ?>?subject=<?= rawurlencode('Divulgação – Economia Local') ?>"><?= htmlspecialchars($emailJunta) ?></a>
                com o assunto «Divulgação – Economia Local».</p>
        <?php endif; ?>
    </div>
</section>
</main>

<?php require_once __DIR__ . "/footer.php"; ?>
