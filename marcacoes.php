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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cidadaoId = $cidadaoLogado['id'] ?? null;

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $data = trim($_POST['data_marcacao'] ?? '');
    $hora = trim($_POST['hora_marcacao'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $tipoAtendimento = trim($_POST['tipo_atendimento'] ?? 'presencial');

    if ($cidadaoLogado) {
        $nome = $cidadaoLogado['nome'];
        $email = $cidadaoLogado['email'];
        $telefone = $cidadaoLogado['telefone'] ?? $telefone;
    }

    $siteWeb = trim($_POST['site_web'] ?? ''); // honeypot: campo invisível, só bots o preenchem

    if ($siteWeb !== '') {
        // Bot apanhado no honeypot: finge sucesso, não processa nem envia nada.
        $sucesso = true;
    } elseif (!$nome || !$email || !$assunto || !$data || !$hora) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!emailValido($email)) {
        $erro = "Indique um email válido.";
    } elseif ($data < date('Y-m-d')) {
        $erro = "A data da marcação não pode ser anterior ao dia de hoje.";
    } elseif (submissaoRecente('marcacao')) {
        $erro = "Já recebemos um pedido seu há pouco. Aguarde uns instantes antes de submeter outro.";
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM marcacoes_atendimento
            WHERE data_marcacao = ?
            AND hora_marcacao = ?
            AND estado IN ('pendente','confirmada')
        ");
        $stmt->execute([$data, $hora]);

        if ($stmt->fetchColumn() > 0) {
            $erro = "Já existe uma marcação para essa data e hora.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO marcacoes_atendimento
                (cidadao_id, nome, email, telefone, assunto, data_marcacao, hora_marcacao, mensagem, tipo_atendimento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $cidadaoId,
                $nome,
                $email,
                $telefone,
                $assunto,
                $data,
                $hora,
                $mensagem,
                $tipoAtendimento
            ]);

            $sucesso = true;

            $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));

            if (!empty($emailJunta)) {
                $htmlJunta = "
                    <h2>Nova marcação de atendimento</h2>
                    <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Telefone:</strong> " . htmlspecialchars($telefone) . "</p>
                    <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
                    <p><strong>Data:</strong> " . htmlspecialchars($data) . "</p>
                    <p><strong>Hora:</strong> " . htmlspecialchars($hora) . "</p>
                    <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
                ";

                enviarEmailSistema($emailJunta, "Nova marcação de atendimento", $htmlJunta);
            }

            $htmlCidadao = "
                <h2>Pedido de marcação recebido</h2>
                <p>Olá " . htmlspecialchars($nome) . ",</p>
                <p>Recebemos o seu pedido de marcação de atendimento.</p>
                <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
                <p><strong>Data:</strong> " . htmlspecialchars($data) . "</p>
                <p><strong>Hora:</strong> " . htmlspecialchars($hora) . "</p>
                <p>A Junta irá analisar e confirmar a marcação.</p>
                <br>
                <p><strong>" . htmlspecialchars(siteConfig('nome_site')) . "</strong></p>
            ";

            enviarEmailSistema($email, "Pedido de marcação recebido", $htmlCidadao);
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
        radial-gradient(circle at 15% 20%, rgba(212,170,0,.28), transparent 30%),
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
    background:rgba(212,170,0,.16);
    border:1px solid rgba(212,170,0,.36);
    color:#F0D060;
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
        linear-gradient(135deg, rgba(36,42,50,.08), rgba(212,170,0,.08)),
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
    background:linear-gradient(135deg, rgba(212,170,0,.16), rgba(36,42,50,.07));
    border:1px solid rgba(212,170,0,.28);
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
            <span class="marcacoes-eyebrow"><i class="bi bi-calendar-event"></i> Balcão Virtual</span>
            <h1>Marcação de Atendimento</h1>
            <p>Agende o seu atendimento na Junta de Freguesia de forma simples, organizada e com confirmação por email.</p>

            <div class="marcacoes-hero-actions">
                <a href="#formMarcacao" class="hero-btn">Pedir marcação</a>
                <a href="/consultar-pedido.php" class="hero-btn">Consultar pedidos</a>
            </div>
        </div>

        <div class="marcacoes-status-card">
            <div class="marcacoes-status-top">
                <div>
                    <strong>Atendimento digital ativo</strong>
                    <small>Pedidos encaminhados para análise da Junta</small>
                </div>
                <i class="marcacoes-live-dot"></i>
            </div>

            <div class="marcacoes-mini-grid">
                <div>
                    <span><i class="bi bi-pencil-square"></i></span>
                    <strong>Pedido online</strong>
                    <small>Sem deslocações iniciais</small>
                </div>
                <div>
                    <span><i class="bi bi-envelope-paper"></i></span>
                    <strong>Confirmação</strong>
                    <small>Resposta por email</small>
                </div>
                <div>
                    <span><i class="bi bi-clock"></i></span>
                    <strong>Horários definidos</strong>
                    <small>Escolha a vaga pretendida</small>
                </div>
                <div>
                    <span><i class="bi bi-shield-lock"></i></span>
                    <strong>Área cidadão</strong>
                    <small>Dados preenchidos se entrar</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section marcacoes-section-premium" id="formMarcacao">
    <div class="container marcacoes-shell">

        <div class="marcacoes-card">
            <div class="marcacoes-card-head">
                <span class="section-kicker">Agendamento online</span>
                <h2>Agendar atendimento</h2>
                <p>Preencha os dados abaixo. Os campos assinalados são obrigatórios e a marcação fica sujeita a validação.</p>
            </div>

            <div class="marcacoes-form-wrap">
                <?php if ($cidadaoLogado): ?>
                    <div class="marcacoes-alert success">
                        <span><i class="bi bi-check-lg"></i></span>
                        <div>Está autenticado como <strong><?= htmlspecialchars($cidadaoLogado['nome']) ?></strong>.</div>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="marcacoes-alert success">
                        <span><i class="bi bi-check-lg"></i></span>
                        <div>Pedido de marcação enviado com sucesso. Receberá uma confirmação por email.</div>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="marcacoes-alert error">
                        <span>!</span>
                        <div><?= htmlspecialchars($erro) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="marcacoes-form">
                    <input type="text" name="site_web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">

                    <div class="marcacoes-field">
                        <label>Nome completo *</label>
                        <input type="text" name="nome" placeholder="Nome completo *" value="<?= htmlspecialchars($nomeForm) ?>" <?= $cidadaoLogado ? 'readonly' : '' ?> required>
                    </div>

                    <div class="marcacoes-field">
                        <label>Email *</label>
                        <input type="email" name="email" placeholder="Email *" value="<?= htmlspecialchars($emailForm) ?>" <?= $cidadaoLogado ? 'readonly' : '' ?> required>
                    </div>

                    <div class="marcacoes-field">
                        <label>Telefone</label>
                        <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($telefoneForm) ?>">
                    </div>

                    <div class="marcacoes-field">
                        <label>Tipo de atendimento *</label>
                        <select name="tipo_atendimento" required>
                            <option value="presencial">Presencial</option>
                            <option value="virtual">Virtual</option>
                        </select>
                    </div>

                    <div class="marcacoes-field">
                        <label>Assunto *</label>
                        <select name="assunto" required>
                            <option value="Recursos Humanos">Recursos Humanos</option>
<option value="Urbanismo e Habitação">Urbanismo e Habitação</option>
<option value="Comunicação e Informação">Comunicação e Informação</option>
<option value="Bem-estar Animal">Bem-estar Animal</option>
<option value="Proteção Civil">Proteção Civil</option>
<option value="Educação">Educação</option>
<option value="Saúde">Saúde</option>
<option value="Turismo">Turismo</option>
<option value="Desporto">Desporto</option>
<option value="Outros Assuntos">Outros Assuntos</option>
                        </select>
                    </div>

                    <div class="marcacoes-field">
                        <label>Data pretendida *</label>
                        <input type="date" name="data_marcacao" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="marcacoes-field">
                        <label>Hora pretendida *</label>
                        <select name="hora_marcacao" required>
                            <option value="">Escolha a hora *</option>
                            <option value="09:00">09:00</option>
                            <option value="09:30">09:30</option>
                            <option value="10:00">10:00</option>
                            <option value="10:30">10:30</option>
                            <option value="11:00">11:00</option>
                            <option value="11:30">11:30</option>
                            <option value="14:00">14:00</option>
                            <option value="14:30">14:30</option>
                            <option value="15:00">15:00</option>
                            <option value="15:30">15:30</option>
                            <option value="16:00">16:00</option>
                        </select>
                    </div>

                    <div class="marcacoes-field full">
                        <label>Mensagem / observações</label>
                        <textarea name="mensagem" placeholder="Mensagem / observações"></textarea>
                    </div>

                    <div class="marcacoes-submit-row">
                        <small>Ao submeter, o pedido é enviado para validação e poderá receber resposta por email.</small>
                        <button class="hero-btn" type="submit">Pedir marcação</button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="marcacoes-side-card">
            <span class="section-kicker">Processo simples</span>
            <h2>Como funciona?</h2>

            <div class="marcacoes-timeline">
                <div class="marcacoes-step">
                    <strong>1</strong>
                    <div>
                        <h3>Escolha o atendimento</h3>
                        <p>Indique o assunto, a data e a hora pretendida.</p>
                    </div>
                </div>

                <div class="marcacoes-step">
                    <strong>2</strong>
                    <div>
                        <h3>Análise pela Junta</h3>
                        <p>O pedido entra no backoffice para validação interna.</p>
                    </div>
                </div>

                <div class="marcacoes-step">
                    <strong>3</strong>
                    <div>
                        <h3>Confirmação por email</h3>
                        <p>Recebe confirmação ou resposta relativamente à marcação.</p>
                    </div>
                </div>
            </div>

            <div class="marcacoes-note">
                A marcação só fica confirmada depois da validação da Junta de Freguesia.
            </div>

            <div class="marcacoes-contact-strip">
                <div><span><i class="bi bi-geo-alt"></i></span> Atendimento presencial mediante confirmação</div>
                <div><span><i class="bi bi-envelope"></i></span> Confirmação enviada para o email indicado</div>
                <div><span>⏱️</span> Evite deslocações desnecessárias</div>
            </div>
        </aside>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>
