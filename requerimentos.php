<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php require_once "includes/header.php"; ?>
<?php require_once "includes/mail_helper.php"; ?>

<?php
$sucesso = false;
$erro = '';
$codigoReq = '';
$cidadaoLogado = null;

if (isset($_SESSION['cidadao_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM cidadaos WHERE id = ? AND ativo = 1");
    $stmt->execute([$_SESSION['cidadao_id']]);
    $cidadaoLogado = $stmt->fetch(PDO::FETCH_ASSOC);
}

$modelos = $pdo->query("
    SELECT * FROM modelos_requerimentos
    WHERE ativo = 1
    ORDER BY tipo ASC, titulo ASC
")->fetchAll(PDO::FETCH_ASSOC);

$nomeForm = $cidadaoLogado['nome'] ?? '';
$emailForm = $cidadaoLogado['email'] ?? '';
$telefoneForm = $cidadaoLogado['telefone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cidadaoId = $cidadaoLogado['id'] ?? null;

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($cidadaoLogado) {
        $nome = $cidadaoLogado['nome'];
        $email = $cidadaoLogado['email'];
        $telefone = $cidadaoLogado['telefone'] ?? $telefone;
    }

    // Consentimento RGPD: mesmo o formulário simples recolhe dados pessoais
    // (nome, email, telefone), por isso o consentimento é obrigatório.
    $consentimento = isset($_POST['consentimento']);

    $siteWeb = trim($_POST['site_web'] ?? ''); // honeypot: campo invisível, só bots o preenchem

    if ($siteWeb !== '') {
        // Bot apanhado no honeypot: finge sucesso, não processa nem envia nada.
        $sucesso = true;
    } elseif (!$nome || !$email || !$tipo || !$assunto) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!emailValido($email)) {
        $erro = "Indique um email válido.";
    } elseif (!$consentimento) {
        $erro = "Tem de aceitar o tratamento dos seus dados pessoais para submeter o requerimento.";
    } elseif (submissaoRecente('requerimento')) {
        $erro = "Já recebemos um pedido seu há pouco. Aguarde uns instantes antes de submeter outro.";
    } else {
        do {
            $codigoReq = "REQ-" . date('Y') . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmtCheck = $pdo->prepare("SELECT id FROM requerimentos WHERE codigo = ?");
            $stmtCheck->execute([$codigoReq]);
        } while ($stmtCheck->fetch());

        $stmt = $pdo->prepare("
            INSERT INTO requerimentos
            (cidadao_id, codigo, nome, email, telefone, tipo, assunto, mensagem)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $cidadaoId,
            $codigoReq,
            $nome,
            $email,
            $telefone,
            $tipo,
            $assunto,
            $mensagem
        ]);

        $reqId = $pdo->lastInsertId();

        if (!empty($_FILES['ficheiros']['name'][0])) {
            $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
            $pasta = "assets/docs/requerimentos/";

            if (!is_dir($pasta)) {
                mkdir($pasta, 0777, true);
            }

            foreach ($_FILES['ficheiros']['tmp_name'] as $key => $tmp) {
                if (empty($_FILES['ficheiros']['name'][$key])) {
                    continue;
                }

                $nomeOriginal = $_FILES['ficheiros']['name'][$key];
                $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

                if (!in_array($ext, $permitidas)) {
                    continue;
                }

                $novoNome = "req_" . $reqId . "_" . time() . "_" . $key . "." . $ext;

                if (move_uploaded_file($tmp, $pasta . $novoNome)) {
                    $stmtFile = $pdo->prepare("
                        INSERT INTO requerimentos_ficheiros
                        (requerimento_id, ficheiro, nome_original)
                        VALUES (?, ?, ?)
                    ");
                    $stmtFile->execute([$reqId, $novoNome, $nomeOriginal]);
                }
            }
        }

        $sucesso = true;

        $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));

        if (!empty($emailJunta)) {
            $htmlJunta = "
                <h2>Novo requerimento online</h2>
                <p><strong>Código:</strong> " . htmlspecialchars($codigoReq) . "</p>
                <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Telefone:</strong> " . htmlspecialchars($telefone) . "</p>
                <p><strong>Tipo:</strong> " . htmlspecialchars($tipo) . "</p>
                <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
                <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
            ";

            enviarEmailSistema($emailJunta, "Novo requerimento online", $htmlJunta);
        }

        $htmlCidadao = "
            <h2>Requerimento recebido</h2>
            <p>Olá " . htmlspecialchars($nome) . ",</p>
            <p>Recebemos o seu requerimento online.</p>
            <p><strong>Código:</strong> " . htmlspecialchars($codigoReq) . "</p>
            <p><strong>Tipo:</strong> " . htmlspecialchars($tipo) . "</p>
            <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
            <p>A Junta irá analisar o seu pedido.</p>
            <br>
            <p><strong>" . htmlspecialchars(siteConfig('nome_site')) . "</strong></p>
        ";

        enviarEmailSistema($email, "Requerimento recebido", $htmlCidadao);
    }
}
?>

<style>
/* =========================================================
   REQUERIMENTOS ONLINE — PREMIUM UI
   Apenas visual/UX. Mantém nomes dos campos, POST, uploads e lógica PHP.
========================================================= */
.req-page-premium{
    background:
        radial-gradient(circle at top left, rgba(212,170,0,.16), transparent 34%),
        radial-gradient(circle at top right, rgba(36,42,50,.14), transparent 30%),
        #f7f4ef;
}

.req-hero-premium{
    position:relative;
    overflow:hidden;
    padding:92px 0 78px;
    background:
        linear-gradient(135deg, rgba(36,42,50,.97), rgba(17,21,28,.96)),
        url('/assets/img/freguesia-1.jpg') center/cover no-repeat;
    color:white;
}

.req-hero-premium::before{
    content:"";
    position:absolute;
    inset:0;
    background:
        radial-gradient(circle at 85% 15%, rgba(212,170,0,.25), transparent 26%),
        linear-gradient(90deg, rgba(0,0,0,.18), transparent);
    pointer-events:none;
}

.req-hero-premium::after{
    content:"";
    position:absolute;
    width:560px;
    height:560px;
    border-radius:50%;
    right:-220px;
    bottom:-300px;
    background:rgba(255,255,255,.055);
}

.req-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1.25fr .75fr;
    gap:40px;
    align-items:center;
}

.req-kicker{
    display:inline-flex;
    align-items:center;
    gap:9px;
    background:rgba(212,170,0,.14);
    border:1px solid rgba(212,170,0,.38);
    color:#ffe8a3;
    border-radius:999px;
    padding:9px 14px;
    text-transform:uppercase;
    letter-spacing:.9px;
    font-size:13px;
    font-weight:900;
}

.req-hero-premium h1{
    font-size:clamp(42px,6vw,74px);
    line-height:1.02;
    margin:20px 0 18px;
    letter-spacing:-1.4px;
}

.req-hero-premium p{
    max-width:760px;
    margin:0;
    color:#dbeafe;
    font-size:20px;
    line-height:1.8;
}

.req-hero-actions{
    display:flex;
    gap:13px;
    flex-wrap:wrap;
    margin-top:30px;
}

.req-btn-light{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    min-height:48px;
    padding:0 18px;
    border-radius:15px;
    background:white;
    color:#11151B;
    text-decoration:none;
    font-weight:900;
    box-shadow:0 16px 35px rgba(0,0,0,.18);
    transition:.25s ease;
}

.req-btn-light:hover,
.req-btn-outline:hover{
    transform:translateY(-3px);
}

.req-btn-outline{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:48px;
    padding:0 18px;
    border-radius:15px;
    border:1px solid rgba(255,255,255,.32);
    color:white;
    text-decoration:none;
    font-weight:900;
    background:rgba(255,255,255,.09);
    backdrop-filter:blur(10px);
    transition:.25s ease;
}

.req-hero-panel{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    border-radius:30px;
    padding:26px;
    box-shadow:0 24px 70px rgba(0,0,0,.20);
    backdrop-filter:blur(14px);
}

.req-hero-panel strong{
    display:block;
    font-size:22px;
    margin-bottom:14px;
}

.req-hero-mini-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.req-hero-mini-grid div{
    background:rgba(255,255,255,.11);
    border:1px solid rgba(255,255,255,.12);
    border-radius:18px;
    padding:16px;
}

.req-hero-mini-grid span{
    display:block;
    font-size:26px;
    margin-bottom:8px;
}

.req-hero-mini-grid small{
    color:#dbeafe;
    font-weight:800;
}

.req-section-premium{
    padding:62px 0 80px;
}

.req-layout{
    display:grid;
    grid-template-columns:minmax(0,1.25fr) 390px;
    gap:34px;
    align-items:start;
}

.req-card{
    background:rgba(255,255,255,.94);
    border:1px solid rgba(226,232,240,.95);
    border-radius:30px;
    box-shadow:0 24px 70px rgba(15,23,42,.10);
    padding:32px;
    position:relative;
    overflow:hidden;
}

.req-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:5px;
    background:linear-gradient(90deg,var(--cor-principal),var(--cor-secundaria));
}

.req-card + .req-card{
    margin-top:26px;
}

.req-card-head{
    display:flex;
    justify-content:space-between;
    gap:20px;
    align-items:flex-start;
    margin-bottom:22px;
}

.req-card-head h2{
    margin:8px 0 8px;
    color:#11151B;
    font-size:32px;
    letter-spacing:-.5px;
}

.req-card-head p{
    margin:0;
    color:#64748b;
    line-height:1.7;
}

.req-icon-bubble{
    width:58px;
    height:58px;
    border-radius:20px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,var(--cor-principal),#11151B);
    color:white;
    font-size:28px;
    box-shadow:0 15px 36px rgba(36,42,50,.24);
    flex-shrink:0;
}

.req-modelos-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:16px;
}

.req-modelo-card{
    position:relative;
    display:flex;
    gap:15px;
    background:linear-gradient(180deg,#ffffff,#f8fafc);
    border:1px solid #e5e7eb;
    border-radius:22px;
    padding:18px;
    box-shadow:0 14px 34px rgba(15,23,42,.07);
    transition:.25s ease;
    overflow:hidden;
}

.req-modelo-card:hover{
    transform:translateY(-5px);
    box-shadow:0 24px 54px rgba(15,23,42,.12);
    border-color:rgba(212,170,0,.55);
}

.req-modelo-icon{
    width:52px;
    height:52px;
    border-radius:17px;
    display:grid;
    place-items:center;
    background:#fff3bf;
    color:#8a5a00;
    font-size:25px;
    flex-shrink:0;
}

.req-modelo-content strong{
    display:block;
    color:#11151B;
    font-size:16px;
    line-height:1.35;
    margin-bottom:5px;
}

.req-modelo-content small{
    display:inline-flex;
    background:#eaf2fb;
    color:var(--cor-principal);
    border-radius:999px;
    padding:5px 9px;
    font-weight:900;
    font-size:12px;
}

.req-modelo-content p{
    color:#64748b;
    line-height:1.55;
    margin:10px 0 12px;
    font-size:14px;
}

.req-modelo-content .btn{
    min-height:38px;
    padding:8px 12px;
    border-radius:12px;
    font-size:13px;
}

.req-alert{
    border-radius:18px;
    padding:16px 18px;
    margin-bottom:18px;
    font-weight:800;
    line-height:1.6;
}

.req-alert.success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}

.req-alert.error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}

.req-form-premium.form-publico{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px 22px !important;
    column-gap:22px !important;
}

.req-form-premium input,
.req-form-premium select,
.req-form-premium textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid #dbe4ee;
    border-radius:16px;
    background:#f8fafc;
    color:#11151B;
    font-size:15px;
    font-weight:700;
    outline:none;
    transition:.22s ease;
}

.req-form-premium input,
.req-form-premium select{
    min-height:52px;
    padding:0 16px;
}

.req-form-premium textarea{
    grid-column:span 2;
    min-height:128px;
    padding:15px 16px;
    resize:vertical;
}

.req-form-premium input:focus,
.req-form-premium select:focus,
.req-form-premium textarea:focus{
    background:white;
    border-color:var(--cor-principal);
    box-shadow:0 0 0 4px rgba(36,42,50,.11);
}

.req-form-premium input[readonly]{
    background:#eef2f7;
    color:#64748b;
    cursor:not-allowed;
}

.req-form-premium > label{
    grid-column:span 2;
    color:#11151B;
    font-weight:900;
    margin-top:2px;
}

.req-upload-zone.upload-pro{
    grid-column:span 2;
}

.req-upload-zone.upload-pro label{
    min-height:118px;
    border-radius:22px;
    border:2px dashed #cbd5e1;
    background:
        linear-gradient(180deg,rgba(248,250,252,.98),rgba(255,255,255,.98));
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.7);
}

.req-upload-zone.upload-pro label:hover{
    transform:translateY(-2px);
    border-color:var(--cor-principal);
}

.req-upload-zone.upload-pro label span{
    width:48px;
    height:48px;
    border-radius:17px;
    background:#eaf2fb;
    display:grid;
    place-items:center;
}

.req-consent{
    grid-column:span 2;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:16px 18px;
    margin-top:4px;
}

.req-consent-check{
    display:flex;
    gap:10px;
    align-items:flex-start;
    font-weight:800;
    color:#11151B;
    line-height:1.5;
    cursor:pointer;
.req-consent-check input{
    width:18px;
    height:18px;
    min-height:0;
    margin-top:2px;
    padding:0;
    flex-shrink:0;
    accent-color:var(--cor-secundaria);
.req-consent-text{
    margin:12px 0 0;
    color:#64748b;
    font-size:13px;
    line-height:1.7;

.req-file-note{
    grid-column:span 2;
    margin-top:-4px;
    color:#64748b;
    font-size:13px;
    font-weight:700;
}

.req-submit-row{
    grid-column:span 2;
    display:flex;
    align-items:center;
    gap:14px;
    flex-wrap:wrap;
    margin-top:6px;
}

.req-submit-row .btn{
    border:0;
    min-height:52px;
    padding:0 22px;
    border-radius:16px;
    box-shadow:0 14px 32px rgba(36,42,50,.20);
}

.req-submit-row small{
    color:#64748b;
    font-weight:800;
}

.req-side{
    position:sticky;
    top:118px;
    display:grid;
    gap:20px;
}

.req-side-card{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:28px;
    padding:26px;
    box-shadow:0 20px 55px rgba(15,23,42,.10);
    overflow:hidden;
}

.req-side-card.dark{
    background:linear-gradient(135deg,var(--cor-principal),#11151B);
    color:white;
    border:0;
}

.req-side-card.dark h2,
.req-side-card.dark p{
    color:white;
}

.req-side-card h2{
    margin:0 0 18px;
    color:#11151B;
    font-size:25px;
}

.req-flow{
    position:relative;
    display:grid;
    gap:15px;
}

.req-flow::before{
    content:"";
    position:absolute;
    left:18px;
    top:20px;
    bottom:20px;
    width:2px;
    background:#e5e7eb;
}

.req-step-pro{
    position:relative;
    display:grid;
    grid-template-columns:38px 1fr;
    gap:13px;
    align-items:flex-start;
}

.req-step-pro strong{
    position:relative;
    z-index:2;
    width:38px;
    height:38px;
    border-radius:14px;
    background:linear-gradient(135deg,var(--cor-principal),#11151B);
    color:white;
    display:grid;
    place-items:center;
    box-shadow:0 10px 22px rgba(36,42,50,.18);
}

.req-step-pro p{
    margin:0;
    color:#475569;
    line-height:1.55;
    font-weight:700;
}

.req-help-list{
    display:grid;
    gap:12px;
    margin-top:16px;
}

.req-help-list div{
    display:flex;
    gap:10px;
    align-items:center;
    padding:13px;
    border-radius:16px;
    background:rgba(255,255,255,.10);
    border:1px solid rgba(255,255,255,.12);
    color:#dbeafe;
    font-weight:800;
}

.req-help-list span{
    font-size:22px;
}

@media(max-width:1050px){
    .req-hero-inner,
    .req-layout{
        grid-template-columns:1fr;
    }

    .req-side{
        position:static;
    }
}

@media(max-width:760px){
    .req-hero-premium{
        padding:68px 0 54px;
    }

    .req-hero-panel{
        padding:20px;
    }

    .req-hero-mini-grid,
    .req-form-premium.form-publico{
        grid-template-columns:1fr;
    }

    .req-form-premium textarea,
    .req-form-premium > label,
    .req-upload-zone.upload-pro,
    .req-file-note,
    .req-submit-row{
        grid-column:span 1;
    }

    .req-card{
        padding:24px 20px;
        border-radius:24px;
    }

    .req-card-head{
        flex-direction:column-reverse;
    }

    .req-card-head h2{
        font-size:27px;
    }

    .req-modelos-grid{
        grid-template-columns:1fr;
    }
}
</style>

<main class="req-page-premium">
    <section class="req-hero-premium">
        <div class="container req-hero-inner">
            <div>
                <span class="req-kicker"><i class="bi bi-envelope-paper"></i> Balcão Virtual</span>
                <h1>Requerimentos Online</h1>
                <p>Submeta pedidos de documentos, declarações, certidões e outros requerimentos sem deslocações desnecessárias, com acompanhamento pela Junta de Freguesia.</p>

                <div class="req-hero-actions">
                    <a href="#novo-requerimento" class="req-btn-light">Submeter requerimento</a>
                    <?php if (!empty($modelos)): ?>
                        <a href="#modelos-requerimentos" class="req-btn-outline">Ver modelos disponíveis</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="req-hero-panel">
                <strong>Serviços digitais disponíveis</strong>
                <div class="req-hero-mini-grid">
                    <div><span><i class="bi bi-house-door"></i></span><small>Atestados de residência</small></div>
                    <div><span><i class="bi bi-file-earmark-text"></i></span><small>Declarações e certidões</small></div>
                    <div><span><i class="bi bi-paperclip"></i></span><small>Anexos digitais</small></div>
                    <div><span><i class="bi bi-envelope"></i></span><small>Resposta por email</small></div>
                </div>
            </div>
        </div>
    </section>

    <section class="req-section-premium">
        <div class="container req-layout">

            <div>
                <?php if (!empty($modelos)): ?>
                    <div class="req-card" id="modelos-requerimentos">
                        <div class="req-card-head">
                            <div>
                                <span class="section-kicker">Downloads</span>
                                <h2>Modelos de requerimentos</h2>
                                <p>Descarregue o modelo adequado antes de submeter o seu pedido, sempre que seja necessário anexar documentação formal.</p>
                            </div>
                            <div class="req-icon-bubble"><i class="bi bi-folder"></i></div>
                        </div>

                        <div class="req-modelos-grid">
                            <?php foreach ($modelos as $m): ?>
                                <div class="req-modelo-card">
                                    <div class="req-modelo-icon"><i class="bi bi-file-earmark-text"></i></div>

                                    <div class="req-modelo-content">
                                        <strong><?= htmlspecialchars($m['titulo']) ?></strong>
                                        <small><?= htmlspecialchars($m['tipo']) ?></small>

                                        <?php if (!empty($m['descricao'])): ?>
                                            <p><?= htmlspecialchars($m['descricao']) ?></p>
                                        <?php endif; ?>

                                        <a 
                                            href="assets/docs/modelos/<?= htmlspecialchars($m['ficheiro']) ?>" 
                                            target="_blank"
                                            class="btn secondary"
                                        >
                                            Descarregar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="req-card" id="novo-requerimento">
                    <div class="req-card-head">
                        <div>
                            <span class="section-kicker">Pedido Online</span>
                            <h2>Novo requerimento</h2>
                            <p>Preencha os dados, escolha o tipo de requerimento e anexe documentos sempre que necessário.</p>
                        </div>
                        <div class="req-icon-bubble"><i class="bi bi-pencil-square"></i></div>
                    </div>

                    <?php if ($cidadaoLogado): ?>
                        <div class="req-alert success">
                            Está autenticado como <strong><?= htmlspecialchars($cidadaoLogado['nome']) ?></strong>.
                            Este requerimento ficará associado à sua área pessoal.
                        </div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                        <div class="req-alert success">
                            Requerimento submetido com sucesso.<br>
                            <strong>Código:</strong> <?= htmlspecialchars($codigoReq) ?><br>
                            Guarde este código para acompanhamento.
                        </div>
                    <?php endif; ?>

                    <?php if ($erro): ?>
                        <div class="req-alert error"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="form-publico req-form-premium">
                        <input type="text" name="site_web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">

                        <input type="text" name="nome" placeholder="Nome completo *" value="<?= htmlspecialchars($nomeForm) ?>" <?= $cidadaoLogado ? 'readonly' : '' ?> required>

                        <input type="email" name="email" placeholder="Email *" value="<?= htmlspecialchars($emailForm) ?>" <?= $cidadaoLogado ? 'readonly' : '' ?> required>

                        <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($telefoneForm) ?>">

                        <select name="tipo" required>
                            <option value="">Tipo de requerimento *</option>
                            <option value="Atestado de residência">Atestado de residência</option>
                            <option value="Declaração / comprovativo">Declaração / comprovativo</option>
                            <option value="Pedido de certidão">Pedido de certidão</option>
                            <option value="Licença / autorização">Licença / autorização</option>
                            <option value="Apoio social">Apoio social</option>
                            <option value="Outro requerimento">Outro requerimento</option>
                        </select>

                        <input type="text" name="assunto" placeholder="Assunto *" required>

                        <textarea name="mensagem" placeholder="Descreva o seu pedido"></textarea>

                        <label>Anexar documentos</label>

                        <div class="upload-pro req-upload-zone">
                            <input type="file" name="ficheiros[]" id="ficheirosReq" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            <label for="ficheirosReq">
                                <span><i class="bi bi-paperclip"></i></span>
                                <strong>Escolher ficheiros</strong>
                                <small id="ficheirosReqTexto">PDF, imagens ou documentos Word</small>
                            </label>
                        </div>

                        <div class="req-file-note">Pode anexar vários ficheiros. Formatos aceites: PDF, JPG, PNG, WEBP, DOC e DOCX.</div>

                        <div class="req-consent">
                            <label class="req-consent-check">
                                <input type="checkbox" name="consentimento" value="1" required>
                                <span>Li e aceito a política de privacidade e o tratamento dos meus dados pessoais para efeitos deste requerimento. *</span>
                            </label>
                            <p class="req-consent-text">
                                A <?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?> compromete-se a garantir a segurança e confidencialidade dos seus dados pessoais. No cumprimento do Regulamento Geral de Proteção de Dados (UE) 2016/679, os dados fornecidos neste formulário são utilizados única e exclusivamente para responder e dar seguimento ao requerimento submetido. Os dados são registados e conservados nos sistemas da Junta de Freguesia apenas durante o período necessário ao tratamento do pedido e ao cumprimento das obrigações legais aplicáveis, não sendo transmitidos a terceiros para fins distintos. Pode solicitar o acesso, a atualização ou a eliminação dos seus dados a qualquer momento, contactando diretamente a Junta de Freguesia.
                            </p>
                        </div>


                        <div class="req-submit-row">
                            <button class="btn" type="submit">Submeter requerimento</button>
                            <small>Receberá confirmação por email após a submissão.</small>
                        </div>
                    </form>
                </div>
            </div>

            <aside class="req-side">
                <div class="req-side-card">
                    <h2>Como funciona?</h2>

                    <div class="req-flow">
                        <div class="req-step-pro">
                            <strong>1</strong>
                            <p>Descarrega o modelo adequado, se necessário.</p>
                        </div>

                        <div class="req-step-pro">
                            <strong>2</strong>
                            <p>Escolhe o tipo de requerimento e descreve o pedido.</p>
                        </div>

                        <div class="req-step-pro">
                            <strong>3</strong>
                            <p>Anexa documentos se forem necessários.</p>
                        </div>

                        <div class="req-step-pro">
                            <strong>4</strong>
                            <p>A Junta analisa o requerimento no backoffice.</p>
                        </div>

                        <div class="req-step-pro">
                            <strong>5</strong>
                            <p>Recebe resposta ou atualização por email.</p>
                        </div>
                    </div>
                </div>

                <div class="req-side-card dark">
                    <span class="req-kicker"><i class="bi bi-check-circle-fill"></i> Apoio ao cidadão</span>
                    <h2>Antes de submeter</h2>
                    <p>Confirme se anexou todos os documentos necessários para evitar atrasos na análise do pedido.</p>

                    <div class="req-help-list">
                        <div><span><i class="bi bi-person-vcard"></i></span> Identificação ou comprovativos</div>
                        <div><span><i class="bi bi-geo-alt"></i></span> Morada ou dados do pedido</div>
                        <div><span><i class="bi bi-envelope"></i></span> Email correto para resposta</div>
                    </div>
                </div>
            </aside>

        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('ficheirosReq');
    const texto = document.getElementById('ficheirosReqTexto');

    if (input && texto) {
        input.addEventListener('change', function () {
            const total = input.files ? input.files.length : 0;
            texto.textContent = total > 0
                ? total + (total === 1 ? ' ficheiro selecionado' : ' ficheiros selecionados')
                : 'PDF, imagens ou documentos Word';
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?>