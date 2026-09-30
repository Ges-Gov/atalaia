<?php
ob_start();
require_once "includes/header.php";

$erro = '';

function gerarCodigoDpo(){
    return "DPO-" . date("Y") . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $aceita = isset($_POST['aceita_politica']) ? 1 : 0;
    $autoriza = isset($_POST['autoriza_resposta']) ? 1 : 0;

    if (!$nome || !$email || !$mensagem) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!$aceita || !$autoriza) {
        $erro = "Tem de aceitar a política de privacidade e autorizar o envio dos dados para resposta.";
    } else {
        $codigo = gerarCodigoDpo();

        $stmt = $pdo->prepare("INSERT INTO dpo_pedidos (codigo,nome,email,mensagem,aceita_politica,autoriza_resposta,estado) VALUES (?,?,?,?,?,?,'recebido')");
        $stmt->execute([$codigo, $nome, $email, $mensagem, $aceita, $autoriza]);

        header("Location: dpo-sucesso.php?codigo=" . urlencode($codigo));
        exit;
    }
}
?>
<style>
.dpo-page{background:linear-gradient(180deg,#f8fafc 0%,#fff 55%,#f7f4ef 100%)}
.dpo-hero{padding:92px 0 112px;background:linear-gradient(135deg,rgba(36,42,50,.98),rgba(17,21,28,.94)),url('/assets/img/freguesia-1.jpg') center/cover no-repeat;color:white}
.dpo-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:40px;align-items:center}
.dpo-kicker{display:inline-flex;gap:8px;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.38);color:#F0D060;border-radius:999px;padding:10px 15px;font-size:13px;font-weight:900;text-transform:uppercase}
.dpo-hero h1{font-size:clamp(40px,6vw,72px);line-height:1.02;margin:20px 0 18px}
.dpo-hero p{color:#dbeafe;font-size:20px;line-height:1.75;max-width:820px}
.dpo-card-hero{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(14px);border-radius:34px;padding:30px;box-shadow:0 32px 80px rgba(0,0,0,.25)}
.dpo-card-hero strong{display:block;font-size:60px;color:var(--cor-secundaria)}
.dpo-shell{margin-top:-58px;position:relative;z-index:5}
.dpo-box{background:white;border:1px solid #e5e7eb;border-radius:30px;padding:30px;box-shadow:0 22px 65px rgba(0,0,0,.12);margin-bottom:32px}
.dpo-form-grid{display:grid;grid-template-columns:.85fr 1.15fr;gap:28px}
.dpo-info h2,.dpo-form h2{color:#11151B;margin-top:0;font-size:30px}.dpo-info p{color:#64748b;line-height:1.75;font-weight:700}
.dpo-mini{display:grid;gap:12px;margin-top:18px}.dpo-mini div{background:#f8fafc;border:1px solid #eef2f7;border-radius:18px;padding:16px;font-weight:800;color:#11151B}
.dpo-form{display:grid;gap:15px}.dpo-form label{font-weight:900;color:#11151B}
.dpo-form input,.dpo-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;border-radius:16px;min-height:52px;padding:0 14px;font-weight:800;color:#11151B}
.dpo-form textarea{min-height:210px;padding:14px;line-height:1.65}
.dpo-check{display:flex;gap:10px;align-items:flex-start;color:#11151B;font-weight:800}.dpo-check input{width:auto;min-height:auto;margin-top:4px}
.dpo-btn{border:0;background:var(--cor-secundaria);color:#11151B;border-radius:16px;padding:16px 22px;font-weight:900;font-size:16px;cursor:pointer;box-shadow:0 14px 30px rgba(212,170,0,.3);transition:.25s}.dpo-btn:hover{transform:translateY(-3px)}
.dpo-alert{background:#fee2e2;color:#991b1b;border-radius:16px;padding:14px;font-weight:900}
.dpo-rights{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.dpo-right{background:white;border:1px solid #eef2f7;border-radius:24px;padding:22px;box-shadow:0 14px 34px rgba(0,0,0,.06)}
.dpo-right h3{margin:0 0 8px;color:#11151B}.dpo-right p{margin:0;color:#64748b;line-height:1.65;font-weight:700}
@media(max-width:950px){.dpo-grid,.dpo-form-grid,.dpo-rights{grid-template-columns:1fr}.dpo-shell{margin-top:-42px}}
</style>
<main class="dpo-page">
<section class="dpo-hero"><div class="container dpo-grid"><div><span class="dpo-kicker"><i class="bi bi-shield-lock"></i> Proteção de Dados</span><h1>Encarregado de Proteção de Dados</h1><p>Contacte o Encarregado de Proteção de Dados da Junta de Freguesia para questões relacionadas com privacidade, tratamento de dados pessoais e exercício de direitos no âmbito do RGPD.</p></div><aside class="dpo-card-hero"><strong><i class="bi bi-shield"></i></strong><h2>RGPD</h2><p>Canal dedicado para pedidos e comunicações sobre proteção de dados pessoais.</p></aside></div></section>
<section class="section dpo-shell"><div class="container">
<div class="dpo-box"><div class="dpo-form-grid"><div class="dpo-info"><h2>Dados pessoais</h2><p>Utilize este formulário para apresentar questões, exercer direitos ou solicitar informação relacionada com o tratamento dos seus dados pessoais.</p><div class="dpo-mini"><div><i class="bi bi-check-circle-fill"></i> Direito de acesso</div><div><i class="bi bi-check-circle-fill"></i> Retificação ou atualização</div><div><i class="bi bi-check-circle-fill"></i> Apagamento ou limitação</div><div><i class="bi bi-check-circle-fill"></i> Oposição ao tratamento</div></div></div>
<form method="POST" class="dpo-form"><h2>Contactar EPD/DPO</h2><?php if($erro): ?><div class="dpo-alert"><?= htmlspecialchars($erro) ?></div><?php endif; ?><div><label>Nome *</label><input type="text" name="nome" required></div><div><label>Email *</label><input type="email" name="email" required></div><div><label>Mensagem *</label><textarea name="mensagem" required></textarea></div><label class="dpo-check"><input type="checkbox" name="aceita_politica" required><span>Tenho conhecimento e aceito a Política de Privacidade.</span></label><label class="dpo-check"><input type="checkbox" name="autoriza_resposta" required><span>Autorizo que os dados sejam enviados para efeitos de resposta.</span></label><button class="dpo-btn" type="submit">Enviar Pedido</button></form></div></div>
<div class="dpo-rights"><article class="dpo-right"><h3><i class="bi bi-file-earmark-text"></i> Acesso aos dados</h3><p>Solicite informação sobre os dados pessoais tratados pela entidade.</p></article><article class="dpo-right"><h3><i class="bi bi-pencil"></i> Retificação</h3><p>Peça a correção ou atualização dos seus dados pessoais.</p></article><article class="dpo-right"><h3><i class="bi bi-trash"></i> Limitação ou apagamento</h3><p>Solicite limitação, oposição ou apagamento quando aplicável.</p></article></div>
</div></section></main>
<?php require_once "includes/footer.php"; ob_end_flush(); ?>
