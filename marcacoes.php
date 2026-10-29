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
$cidadaoLogado = null;

if (isset($_SESSION['cidadao_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM cidadaos WHERE id = ? AND ativo = 1");
    $stmt->execute([$_SESSION['cidadao_id']]);
    $cidadaoLogado = $stmt->fetch(PDO::FETCH_ASSOC);
}

$nomeForm = $cidadaoLogado['nome'] ?? '';
$emailForm = $cidadaoLogado['email'] ?? '';
$telefoneForm = $cidadaoLogado['telefone'] ?? '';

// 30/10/2026: a página serve dois pedidos — agendar um atendimento ou requisitar/alugar um espaço ou
// equipamento (migração 084: tipo_pedido, recurso, hora_fim, num_pessoas). Os valores voltam ao
// formulário quando há um erro, para não ser preciso escrever tudo de novo.
$tipoPedido = (($_POST['tipo_pedido'] ?? $_GET['tipo'] ?? '') === 'requisicao') ? 'requisicao' : 'atendimento';
$valores = [
    'assunto' => '', 'data' => '', 'hora' => '', 'mensagem' => '', 'tipo_atendimento' => 'presencial',
    'recurso' => '', 'hora_inicio' => '', 'hora_fim' => '', 'num_pessoas' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cidadaoId = $cidadaoLogado['id'] ?? null;

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $valores = [
        'assunto' => trim($_POST['assunto'] ?? ''),
        'data' => trim($_POST['data_marcacao'] ?? ''),
        'hora' => trim($_POST['hora_marcacao'] ?? ''),
        'mensagem' => trim($_POST['mensagem'] ?? ''),
        'tipo_atendimento' => ($_POST['tipo_atendimento'] ?? '') === 'virtual' ? 'virtual' : 'presencial',
        'recurso' => trim($_POST['recurso'] ?? ''),
        'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
        'hora_fim' => trim($_POST['hora_fim'] ?? ''),
        'num_pessoas' => trim($_POST['num_pessoas'] ?? ''),
    ];
    $nomeForm = $nome;
    $emailForm = $email;
    $telefoneForm = $telefone;

    if ($cidadaoLogado) {
        $nome = $cidadaoLogado['nome'];
        $email = $cidadaoLogado['email'];
        if (!empty($cidadaoLogado['telefone'])) {
            $telefone = $cidadaoLogado['telefone'];
        }
    }

    $eRequisicao = $tipoPedido === 'requisicao';
    $data = $valores['data'];
    $hora = $eRequisicao ? $valores['hora_inicio'] : $valores['hora'];
    $horaFim = $eRequisicao ? $valores['hora_fim'] : null;
    $numPessoas = ($eRequisicao && ctype_digit($valores['num_pessoas'])) ? (int)$valores['num_pessoas'] : null;
    $assunto = $eRequisicao ? mb_substr('Requisição: ' . $valores['recurso'], 0, 150) : $valores['assunto'];
    $mensagem = $valores['mensagem'];
    $formatoHora = '/^([01]\d|2[0-3]):[0-5]\d$/';

    $siteWeb = trim($_POST['site_web'] ?? ''); // honeypot: campo invisível, só bots o preenchem

    if ($siteWeb !== '') {
        // Bot apanhado no honeypot: finge sucesso, não processa nem envia nada.
        $sucesso = true;
    } elseif (!$nome || !$email || !$telefone || !$data || !$hora
              || ($eRequisicao ? (!$valores['recurso'] || !$horaFim || !$mensagem) : !$assunto)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!emailValido($email)) {
        $erro = "Indique um email válido e que exista.";
    } elseif (!telefoneValido($telefone)) {
        $erro = "Indique um número de telefone válido (ex.: 912 345 678, ou com o indicativo do país se for estrangeiro).";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data < date('Y-m-d')) {
        $erro = "A data do pedido não pode ser anterior ao dia de hoje.";
    } elseif (!preg_match($formatoHora, $hora) || ($eRequisicao && (!preg_match($formatoHora, $horaFim) || $horaFim <= $hora))) {
        $erro = $eRequisicao ? "Indique a hora de início e a hora de fim (a de fim depois da de início)." : "Escolha uma hora válida.";
    } elseif (mb_strlen($valores['recurso']) > 200 || mb_strlen($mensagem) > 5000) {
        $erro = "O texto é demasiado longo.";
    } elseif (submissaoRecente('marcacao')) {
        $erro = "Já recebemos um pedido seu há pouco. Aguarde uns instantes antes de submeter outro.";
    } else {
        $ocupada = 0;
        if (!$eRequisicao) {
            // Só os atendimentos ocupam vaga na agenda; as requisições são analisadas pela Junta.
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM marcacoes_atendimento
                WHERE data_marcacao = ?
                AND hora_marcacao = ?
                AND tipo_pedido = 'atendimento'
                AND estado IN ('pendente','confirmada')
            ");
            $stmt->execute([$data, $hora]);
            $ocupada = (int)$stmt->fetchColumn();
        }

        if ($ocupada > 0) {
            $erro = "Já existe uma marcação para essa data e hora.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO marcacoes_atendimento
                (tipo_pedido, cidadao_id, nome, email, telefone, assunto, recurso, data_marcacao, hora_marcacao, hora_fim, num_pessoas, mensagem, tipo_atendimento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $tipoPedido,
                $cidadaoId,
                $nome,
                $email,
                telefoneLimpo($telefone),
                $assunto,
                $eRequisicao ? $valores['recurso'] : null,
                $data,
                $hora,
                $horaFim,
                $numPessoas,
                $mensagem,
                $eRequisicao ? 'presencial' : $valores['tipo_atendimento']
            ]);

            $sucesso = true;
            $h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
            $quando = $eRequisicao ? "das {$h($hora)} às {$h($horaFim)}" : $h($hora);
            $titulo = $eRequisicao ? "Novo pedido de requisição" : "Nova marcação de atendimento";

            $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));

            if (!empty($emailJunta)) {
                $htmlJunta = "
                    <h2>{$titulo}</h2>
                    <p><strong>Nome:</strong> {$h($nome)}</p>
                    <p><strong>Email:</strong> {$h($email)}</p>
                    <p><strong>Telefone:</strong> {$h($telefone)}</p>
                    " . ($eRequisicao
                        ? "<p><strong>Espaço ou equipamento:</strong> {$h($valores['recurso'])}</p>"
                          . ($numPessoas ? "<p><strong>N.º de pessoas:</strong> {$numPessoas}</p>" : '')
                        : "<p><strong>Assunto:</strong> {$h($assunto)}</p>") . "
                    <p><strong>Data:</strong> {$h($data)}</p>
                    <p><strong>Hora:</strong> {$quando}</p>
                    <p><strong>" . ($eRequisicao ? 'Finalidade' : 'Mensagem') . ":</strong><br>" . nl2br($h($mensagem)) . "</p>
                ";

                enviarEmailSistema($emailJunta, $titulo, $htmlJunta, $email);
            }

            $htmlCidadao = "
                <h2>Pedido recebido</h2>
                <p>Olá {$h($nome)},</p>
                <p>Recebemos o seu pedido de " . ($eRequisicao ? "requisição" : "marcação de atendimento") . ".</p>
                <p><strong>" . ($eRequisicao ? "Espaço ou equipamento" : "Assunto") . ":</strong> {$h($eRequisicao ? $valores['recurso'] : $assunto)}</p>
                <p><strong>Data:</strong> {$h($data)}</p>
                <p><strong>Hora:</strong> {$quando}</p>
                <p>A Junta irá analisar o pedido e responder-lhe por email.</p>
                <br>
                <p><strong>" . $h(siteConfig('nome_site')) . "</strong></p>
            ";

            enviarEmailSistema($email, "Pedido recebido", $htmlCidadao);

            $valores = array_map(fn() => '', $valores);
            if (!$cidadaoLogado) {
                $nomeForm = $emailForm = $telefoneForm = '';
            }
            $valores['tipo_atendimento'] = 'presencial';
        }
    }
}
?>

<style>
.marcacoes-hero-premium{
    position:relative;
    overflow:hidden;
    padding:92px 0 110px;
    background:
        radial-gradient(circle at 15% 20%, rgb(var(--ouro-rgb, 240 180 41) / .28), transparent 30%),
        radial-gradient(circle at 85% 10%, rgba(255,255,255,.12), transparent 28%),
        linear-gradient(135deg, var(--cor-principal), #11151B 62%, #071c2e);
    color:white;
}

.marcacoes-hero-premium::before{
    content:"";
    position:absolute;
    inset:auto -180px -260px auto;
    width:560px;
    height:560px;
    border-radius:50%;
    background:rgba(255,255,255,.055);
}

.marcacoes-hero-premium::after{
    content:"";
    position:absolute;
    left:0;
    right:0;
    bottom:-1px;
    height:78px;
    background:linear-gradient(180deg, transparent, #f7f4ef);
}

.marcacoes-hero-grid{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1.1fr .9fr;
    gap:42px;
    align-items:center;
}

.marcacoes-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgb(var(--ouro-rgb, 240 180 41) / .16);
    border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .36);
    color:var(--ouro-claro, #f8d77a);
    padding:9px 14px;
    border-radius:999px;
    font-weight:900;
    letter-spacing:.8px;
    text-transform:uppercase;
    font-size:12px;
}

.marcacoes-hero-content h1{
    font-size:clamp(42px, 6vw, 70px);
    line-height:1.02;
    margin:20px 0 18px;
    letter-spacing:-1.5px;
}

.marcacoes-hero-content p{
    max-width:740px;
    margin:0;
    color:#dbeafe;
    font-size:20px;
    line-height:1.8;
}

.marcacoes-hero-actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:30px;
}

.marcacoes-hero-actions .btn{
    box-shadow:0 18px 42px rgba(0,0,0,.24);
}

.marcacoes-hero-actions .btn.secondary{
    background:rgba(255,255,255,.14);
    color:white;
    border:1px solid rgba(255,255,255,.24);
    backdrop-filter:blur(10px);
}

.marcacoes-status-card{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    border-radius:34px;
    padding:30px;
    box-shadow:0 30px 80px rgba(0,0,0,.24);
    backdrop-filter:blur(16px);
}

.marcacoes-status-top{
    display:flex;
    justify-content:space-between;
    gap:18px;
    align-items:center;
    margin-bottom:22px;
}

.marcacoes-status-top strong{
    display:block;
    font-size:22px;
    margin-bottom:4px;
}

.marcacoes-status-top small{
    color:#dbeafe;
    font-weight:800;
}

.marcacoes-live-dot{
    width:46px;
    height:46px;
    border-radius:50%;
    background:#22c55e;
    position:relative;
    box-shadow:0 0 0 9px rgba(34,197,94,.12);
}

.marcacoes-live-dot::after{
    content:"";
    position:absolute;
    inset:12px;
    border-radius:50%;
    background:white;
    animation:marcacoesPulse 1.5s infinite;
}

@keyframes marcacoesPulse{
    0%{transform:scale(.75); opacity:1;}
    100%{transform:scale(1.7); opacity:0;}
}

.marcacoes-mini-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:14px;
}

.marcacoes-mini-grid div{
    background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.13);
    border-radius:20px;
    padding:18px;
}

.marcacoes-mini-grid span{
    display:block;
    font-size:28px;
    margin-bottom:8px;
}

.marcacoes-mini-grid strong{
    display:block;
    font-size:16px;
}

.marcacoes-mini-grid small{
    color:#dbeafe;
    font-weight:700;
}

.marcacoes-section-premium{
    margin-top:-60px;
    position:relative;
    z-index:5;
}

.marcacoes-shell{
    display:grid;
    grid-template-columns:minmax(0,1.25fr) minmax(340px,.75fr);
    gap:28px;
    align-items:start;
}

.marcacoes-card,
.marcacoes-side-card{
    background:rgba(255,255,255,.96);
    border:1px solid rgba(226,232,240,.9);
    border-radius:32px;
    box-shadow:0 24px 70px rgba(15,23,42,.12);
    overflow:hidden;
}

.marcacoes-card-head{
    padding:30px 32px 22px;
    background:
        linear-gradient(135deg, rgba(36,42,50,.08), rgb(var(--ouro-rgb, 240 180 41) / .08)),
        white;
    border-bottom:1px solid #eef2f7;
}

.marcacoes-card-head h2{
    margin:10px 0 8px;
    color:#11151B;
    font-size:34px;
    letter-spacing:-.5px;
}

.marcacoes-card-head p{
    margin:0;
    color:#64748b;
    line-height:1.7;
}

.marcacoes-form-wrap{
    padding:30px 32px 34px;
}

.marcacoes-form{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px 22px;
}

.marcacoes-field{
    position:relative;
}

.marcacoes-field.full{
    grid-column:span 2;
}

.marcacoes-field label{
    display:block;
    color:#11151B;
    font-size:13px;
    font-weight:900;
    margin-bottom:8px;
    letter-spacing:.2px;
}

.marcacoes-field input,
.marcacoes-field select,
.marcacoes-field textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid #dbe4ee;
    background:#f8fafc;
    color:#11151B;
    border-radius:16px;
    min-height:52px;
    padding:0 16px;
    outline:none;
    font-size:15px;
    font-weight:700;
    transition:.22s ease;
}

.marcacoes-field textarea{
    min-height:138px;
    resize:vertical;
    padding:15px 16px;
    line-height:1.6;
}

.marcacoes-field input:focus,
.marcacoes-field select:focus,
.marcacoes-field textarea:focus{
    background:white;
    border-color:var(--cor-principal);
    box-shadow:0 0 0 4px rgba(36,42,50,.1);
}

/* 30/10/2026: escolha entre atendimento e requisição de espaço/equipamento */
.marcacoes-tipo{border:0;margin:0;padding:0;min-width:0;}
.marcacoes-tipo legend{color:#11151B;font-size:13px;font-weight:900;margin-bottom:8px;letter-spacing:.2px;padding:0;}
.marcacoes-tipo__ops{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
.marcacoes-field .marcacoes-tipo__op{position:relative;display:block;cursor:pointer;margin:0;}
.marcacoes-field .marcacoes-tipo__op input{position:absolute;opacity:0;width:1px;height:1px;min-height:0;padding:0;}
.marcacoes-tipo__op span{display:flex;align-items:center;gap:10px;min-height:56px;padding:12px 16px;border:2px solid #dbe4ee;border-radius:16px;
    background:#f8fafc;font-size:15px;font-weight:800;color:#11151B;line-height:1.3;transition:.2s ease;}
.marcacoes-tipo__op span i{font-size:20px;flex-shrink:0;}
.marcacoes-tipo__op input:checked + span{border-color:var(--cor-principal);background:color-mix(in srgb, var(--cor-principal) 8%, #fff);color:var(--cor-principal);}
.marcacoes-tipo__op input:focus-visible + span{outline:2px solid var(--cor-principal);outline-offset:2px;}
.marcacoes-horas{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.marcacoes-form [data-pedido][hidden]{display:none !important;}
@media(max-width:700px){.marcacoes-tipo__ops{grid-template-columns:1fr;}}

.marcacoes-field input[readonly]{
    background:#eef6ff;
    color:#334155;
    cursor:not-allowed;
}

.marcacoes-submit-row{
    grid-column:span 2;
    display:flex;
    gap:14px;
    align-items:center;
    justify-content:space-between;
    margin-top:4px;
    padding-top:18px;
    border-top:1px solid #eef2f7;
}

.marcacoes-submit-row small{
    color:#64748b;
    line-height:1.5;
    font-weight:700;
}

.marcacoes-submit-row .btn{
    border:0;
    min-height:52px;
    padding:0 24px;
    border-radius:16px;
    font-weight:900;
    box-shadow:0 14px 32px rgba(36,42,50,.2);
}

.marcacoes-alert{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:15px 16px;
    border-radius:18px;
    margin-bottom:18px;
    font-weight:800;
    line-height:1.5;
}

.marcacoes-alert span{
    width:30px;
    height:30px;
    border-radius:10px;
    display:grid;
    place-items:center;
    flex-shrink:0;
}

.marcacoes-alert.success{
    background:#dcfce7;
    color:#166534;
}

.marcacoes-alert.success span{
    background:#22c55e;
    color:white;
}

.marcacoes-alert.error{
    background:#fee2e2;
    color:#991b1b;
}

.marcacoes-alert.error span{
    background:#ef4444;
    color:white;
}

.marcacoes-side-card{
    padding:28px;
    position:sticky;
    top:118px;
}

.marcacoes-side-card h2{
    margin:0 0 18px;
    color:#11151B;
    font-size:28px;
}

.marcacoes-timeline{
    display:grid;
    gap:18px;
    margin:22px 0;
    position:relative;
}

.marcacoes-timeline::before{
    content:"";
    position:absolute;
    left:20px;
    top:18px;
    bottom:18px;
    width:2px;
    background:#dbe4ee;
}

.marcacoes-step{
    display:grid;
    grid-template-columns:42px 1fr;
    gap:14px;
    position:relative;
}

.marcacoes-step strong{
    width:42px;
    height:42px;
    border-radius:15px;
    background:linear-gradient(135deg,var(--cor-principal),#11151B);
    color:white;
    display:grid;
    place-items:center;
    box-shadow:0 10px 24px rgba(36,42,50,.2);
    z-index:1;
}

.marcacoes-step div{
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:18px;
    padding:14px 16px;
}

.marcacoes-step h3{
    margin:0 0 5px;
    color:#11151B;
    font-size:16px;
}

.marcacoes-step p{
    margin:0;
    color:#64748b;
    line-height:1.55;
}

.marcacoes-note{
    background:linear-gradient(135deg, rgb(var(--ouro-rgb, 240 180 41) / .16), rgba(36,42,50,.07));
    border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .28);
    border-radius:20px;
    padding:17px;
    color:#334155;
    line-height:1.65;
    font-weight:700;
}

.marcacoes-contact-strip{
    margin-top:18px;
    display:grid;
    grid-template-columns:1fr;
    gap:10px;
}

.marcacoes-contact-strip div{
    display:flex;
    align-items:center;
    gap:12px;
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:16px;
    padding:13px 14px;
    color:#334155;
    font-weight:800;
}

.marcacoes-contact-strip span{
    width:34px;
    height:34px;
    border-radius:12px;
    background:white;
    display:grid;
    place-items:center;
    box-shadow:0 8px 20px rgba(15,23,42,.08);
}

@media(max-width:1050px){
    .marcacoes-hero-grid,
    .marcacoes-shell{
        grid-template-columns:1fr;
    }

    .marcacoes-side-card{
        position:static;
    }
}

@media(max-width:760px){
    .marcacoes-hero-premium{
        padding:70px 0 96px;
    }

    .marcacoes-hero-actions,
    .marcacoes-submit-row{
        flex-direction:column;
        align-items:stretch;
    }

    .marcacoes-mini-grid,
    .marcacoes-form{
        grid-template-columns:1fr;
    }

    .marcacoes-field.full,
    .marcacoes-submit-row{
        grid-column:span 1;
    }

    .marcacoes-card-head,
    .marcacoes-form-wrap,
    .marcacoes-side-card{
        padding:24px;
    }

    .marcacoes-card-head h2{
        font-size:28px;
    }
}
</style>

<section class="marcacoes-hero-premium">
    <div class="container marcacoes-hero-grid">
        <div class="marcacoes-hero-content">
            <span class="marcacoes-eyebrow"><i class="bi bi-calendar-event"></i> <?= te('marcacoes.hero.kicker') ?></span>
            <h1><?= te('marcacoes.hero.titulo') ?></h1>
            <p><?= te('marcacoes.hero.texto') ?></p>

            <div class="marcacoes-hero-actions">
                <a href="#formMarcacao" class="hero-btn"><?= te('marcacoes.hero.botao1') ?></a>
                <a href="/consultar-pedido.php" class="hero-btn"><?= te('marcacoes.hero.botao2') ?></a>
            </div>
        </div>

        <div class="marcacoes-status-card">
            <div class="marcacoes-status-top">
                <div>
                    <strong><?= te('marcacoes.hero.cartao_titulo') ?></strong>
                    <small><?= te('marcacoes.hero.cartao_texto') ?></small>
                </div>
                <i class="marcacoes-live-dot"></i>
            </div>

            <div class="marcacoes-mini-grid">
                <div>
                    <span><i class="bi bi-pencil-square"></i></span>
                    <strong><?= te('marcacoes.destaques.d1_titulo') ?></strong>
                    <small><?= te('marcacoes.destaques.d1_texto') ?></small>
                </div>
                <div>
                    <span><i class="bi bi-envelope-paper"></i></span>
                    <strong><?= te('marcacoes.destaques.d2_titulo') ?></strong>
                    <small><?= te('marcacoes.destaques.d2_texto') ?></small>
                </div>
                <div>
                    <span><i class="bi bi-clock"></i></span>
                    <strong><?= te('marcacoes.destaques.d3_titulo') ?></strong>
                    <small><?= te('marcacoes.destaques.d3_texto') ?></small>
                </div>
                <div>
                    <span><i class="bi bi-shield-lock"></i></span>
                    <strong><?= te('marcacoes.destaques.d4_titulo') ?></strong>
                    <small><?= te('marcacoes.destaques.d4_texto') ?></small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section marcacoes-section-premium" id="formMarcacao">
    <div class="container marcacoes-shell">

        <div class="marcacoes-card">
            <div class="marcacoes-card-head">
                <span class="section-kicker"><?= te('marcacoes.formulario.kicker') ?></span>
                <h2><?= te('marcacoes.formulario.titulo') ?></h2>
                <p><?= te('marcacoes.formulario.intro') ?></p>
            </div>

            <div class="marcacoes-form-wrap">
                <?php if ($cidadaoLogado): ?>
                    <div class="marcacoes-alert success">
                        <span><i class="bi bi-check-lg"></i></span>
                        <div><?= te('marcacoes.formulario.autenticado') ?> <strong><?= htmlspecialchars($cidadaoLogado['nome']) ?></strong>.</div>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="marcacoes-alert success">
                        <span><i class="bi bi-check-lg"></i></span>
                        <div><?= te('marcacoes.formulario.sucesso') ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="marcacoes-alert error">
                        <span>!</span>
                        <div><?= htmlspecialchars($erro) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="marcacoes-form" id="marcacoesForm">
                    <input type="text" name="site_web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">

                    <fieldset class="marcacoes-field full marcacoes-tipo">
                        <legend><?= te('marcacoes.formulario.tipo_pedido') ?></legend>
                        <div class="marcacoes-tipo__ops">
                        <label class="marcacoes-tipo__op">
                            <input type="radio" name="tipo_pedido" value="atendimento" <?= $tipoPedido === 'atendimento' ? 'checked' : '' ?>>
                            <span><i class="bi bi-person-video2" aria-hidden="true"></i> <?= te('marcacoes.formulario.op_atendimento') ?></span>
                        </label>
                        <label class="marcacoes-tipo__op">
                            <input type="radio" name="tipo_pedido" value="requisicao" <?= $tipoPedido === 'requisicao' ? 'checked' : '' ?>>
                            <span><i class="bi bi-building" aria-hidden="true"></i> <?= te('marcacoes.formulario.op_requisicao') ?></span>
                        </label>
                        </div>
                    </fieldset>

                    <div class="marcacoes-field">
                        <label for="marc-nome"><?= te('marcacoes.formulario.nome') ?></label>
                        <input type="text" id="marc-nome" name="nome" autocomplete="name" placeholder="<?= te('marcacoes.formulario.ph_nome') ?>" value="<?= htmlspecialchars($nomeForm) ?>" <?= $cidadaoLogado ? 'readonly' : '' ?> required>
                    </div>

                    <div class="marcacoes-field">
                        <label for="marc-email"><?= te('marcacoes.formulario.email') ?></label>
                        <input type="email" id="marc-email" name="email" autocomplete="email" placeholder="<?= te('marcacoes.formulario.ph_email') ?>" value="<?= htmlspecialchars($emailForm) ?>" <?= $cidadaoLogado ? 'readonly' : '' ?> required>
                    </div>

                    <div class="marcacoes-field">
                        <label for="marc-telefone"><?= te('marcacoes.formulario.telefone') ?></label>
                        <input type="tel" id="marc-telefone" name="telefone" autocomplete="tel" inputmode="tel" maxlength="20" placeholder="<?= te('marcacoes.formulario.ph_telefone') ?>" value="<?= htmlspecialchars($telefoneForm) ?>" required>
                    </div>

                    <!-- Agendar um atendimento -->
                    <div class="marcacoes-field" data-pedido="atendimento">
                        <label for="marc-tipo"><?= te('marcacoes.formulario.tipo') ?></label>
                        <select id="marc-tipo" name="tipo_atendimento" required>
                            <option value="presencial"><?= te('marcacoes.formulario.tipo_presencial') ?></option>
                            <option value="virtual" <?= $valores['tipo_atendimento'] === 'virtual' ? 'selected' : '' ?>><?= te('marcacoes.formulario.tipo_virtual') ?></option>
                        </select>
                    </div>

                    <div class="marcacoes-field" data-pedido="atendimento">
                        <label for="marc-assunto"><?= te('marcacoes.formulario.assunto') ?></label>
                        <select id="marc-assunto" name="assunto" required>
                            <?php foreach (['Recursos Humanos', 'Urbanismo e Habitação', 'Comunicação e Informação', 'Bem-estar Animal', 'Proteção Civil',
                                            'Educação', 'Saúde', 'Turismo', 'Desporto', 'Outros Assuntos'] as $opcao): ?>
                                <option value="<?= htmlspecialchars($opcao) ?>" <?= $valores['assunto'] === $opcao ? 'selected' : '' ?>><?= htmlspecialchars($opcao) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Requisitar ou alugar um espaço ou equipamento -->
                    <div class="marcacoes-field full" data-pedido="requisicao">
                        <label for="marc-recurso"><?= te('marcacoes.formulario.recurso') ?></label>
                        <input type="text" id="marc-recurso" name="recurso" maxlength="200" placeholder="<?= te('marcacoes.formulario.ph_recurso') ?>" value="<?= htmlspecialchars($valores['recurso']) ?>" required>
                    </div>

                    <div class="marcacoes-field">
                        <label for="marc-data"><?= te('marcacoes.formulario.data') ?></label>
                        <input type="date" id="marc-data" name="data_marcacao" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($valores['data']) ?>" required>
                    </div>

                    <div class="marcacoes-field" data-pedido="atendimento">
                        <label for="marc-hora"><?= te('marcacoes.formulario.hora') ?></label>
                        <select id="marc-hora" name="hora_marcacao" required>
                            <option value=""><?= te('marcacoes.formulario.hora_opcao') ?></option>
                            <?php foreach (['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00'] as $opcao): ?>
                                <option value="<?= $opcao ?>" <?= $valores['hora'] === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="marcacoes-field marcacoes-horas" data-pedido="requisicao">
                        <div>
                            <label for="marc-hora-inicio"><?= te('marcacoes.formulario.hora_inicio') ?></label>
                            <input type="time" id="marc-hora-inicio" name="hora_inicio" value="<?= htmlspecialchars($valores['hora_inicio']) ?>" required>
                        </div>
                        <div>
                            <label for="marc-hora-fim"><?= te('marcacoes.formulario.hora_fim') ?></label>
                            <input type="time" id="marc-hora-fim" name="hora_fim" value="<?= htmlspecialchars($valores['hora_fim']) ?>" required>
                        </div>
                    </div>

                    <div class="marcacoes-field" data-pedido="requisicao">
                        <label for="marc-pessoas"><?= te('marcacoes.formulario.num_pessoas') ?></label>
                        <input type="number" id="marc-pessoas" name="num_pessoas" min="1" max="100000" inputmode="numeric" value="<?= htmlspecialchars($valores['num_pessoas']) ?>">
                    </div>

                    <div class="marcacoes-field full" data-pedido="atendimento">
                        <label for="marc-mensagem"><?= te('marcacoes.formulario.mensagem') ?></label>
                        <textarea id="marc-mensagem" name="mensagem" maxlength="5000" placeholder="<?= te('marcacoes.formulario.ph_mensagem') ?>"><?= $tipoPedido === 'atendimento' ? htmlspecialchars($valores['mensagem']) : '' ?></textarea>
                    </div>

                    <div class="marcacoes-field full" data-pedido="requisicao">
                        <label for="marc-finalidade"><?= te('marcacoes.formulario.finalidade') ?></label>
                        <textarea id="marc-finalidade" name="mensagem" maxlength="5000" placeholder="<?= te('marcacoes.formulario.ph_finalidade') ?>" required><?= $tipoPedido === 'requisicao' ? htmlspecialchars($valores['mensagem']) : '' ?></textarea>
                    </div>

                    <div class="marcacoes-submit-row">
                        <small><?= te('marcacoes.formulario.nota') ?></small>
                        <button class="hero-btn" type="submit"><?= te('marcacoes.formulario.botao') ?></button>
                    </div>
                </form>
                <script>
                // Mostra só os campos do tipo de pedido escolhido; os escondidos ficam «disabled»,
                // para não serem enviados nem bloquearem o envio por serem obrigatórios.
                (function () {
                    var form = document.getElementById('marcacoesForm');
                    if (!form) return;
                    function aplicar() {
                        var escolhido = (form.querySelector('input[name="tipo_pedido"]:checked') || {}).value || 'atendimento';
                        form.querySelectorAll('[data-pedido]').forEach(function (bloco) {
                            var ativo = bloco.getAttribute('data-pedido') === escolhido;
                            bloco.hidden = !ativo;
                            bloco.querySelectorAll('input, select, textarea').forEach(function (campo) { campo.disabled = !ativo; });
                        });
                    }
                    form.querySelectorAll('input[name="tipo_pedido"]').forEach(function (r) { r.addEventListener('change', aplicar); });
                    aplicar();
                })();
                </script>
            </div>
        </div>

        <aside class="marcacoes-side-card">
            <span class="section-kicker"><?= te('marcacoes.passos.kicker') ?></span>
            <h2><?= te('marcacoes.passos.titulo') ?></h2>

            <div class="marcacoes-timeline">
                <div class="marcacoes-step">
                    <strong>1</strong>
                    <div>
                        <h3><?= te('marcacoes.passos.p1_titulo') ?></h3>
                        <p><?= te('marcacoes.passos.p1_texto') ?></p>
                    </div>
                </div>

                <div class="marcacoes-step">
                    <strong>2</strong>
                    <div>
                        <h3><?= te('marcacoes.passos.p2_titulo') ?></h3>
                        <p><?= te('marcacoes.passos.p2_texto') ?></p>
                    </div>
                </div>

                <div class="marcacoes-step">
                    <strong>3</strong>
                    <div>
                        <h3><?= te('marcacoes.passos.p3_titulo') ?></h3>
                        <p><?= te('marcacoes.passos.p3_texto') ?></p>
                    </div>
                </div>
            </div>

            <div class="marcacoes-note">
                <?= te('marcacoes.sidebar.aviso') ?>
            </div>

            <div class="marcacoes-contact-strip">
                <div><span><i class="bi bi-geo-alt"></i></span> <?= te('marcacoes.sidebar.nota1') ?></div>
                <div><span><i class="bi bi-envelope"></i></span> <?= te('marcacoes.sidebar.nota2') ?></div>
                <div><span>⏱️</span> <?= te('marcacoes.sidebar.nota3') ?></div>
            </div>
        </aside>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>
