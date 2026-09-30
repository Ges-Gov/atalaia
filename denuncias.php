<?php
ob_start();
require_once "includes/header.php";
$erro='';
$uploadDir=__DIR__."/uploads/denuncias/";
if(!is_dir($uploadDir)) mkdir($uploadDir,0775,true);

function gerarCodigoDenuncia(){return "AAEJ-".date("Y")."-".strtoupper(substr(bin2hex(random_bytes(4)),0,8));}

function guardarAnexosDenuncia($campo,$uploadDir,&$erro){
    $ficheiros=[]; if(empty($_FILES[$campo]['name'])||!is_array($_FILES[$campo]['name'])) return $ficheiros;
    $permitidos=['pdf','doc','docx','jpg','jpeg','png','webp','mp3','mp4','zip'];
    foreach($_FILES[$campo]['name'] as $i=>$nomeOriginal){
        if(empty($nomeOriginal)||empty($_FILES[$campo]['tmp_name'][$i])) continue;
        $ext=strtolower(pathinfo($nomeOriginal,PATHINFO_EXTENSION));
        if(!in_array($ext,$permitidos)){ $erro="Formato inválido num dos anexos."; return []; }
        $novo="denuncia_".date("YmdHis")."_".$i."_".bin2hex(random_bytes(4)).".".$ext;
        if(move_uploaded_file($_FILES[$campo]['tmp_name'][$i],$uploadDir.$novo)) $ficheiros[]=['ficheiro'=>$novo,'original'=>$nomeOriginal];
    }
    return $ficheiros;
}

$categorias = [
    'Proteção da privacidade e dos dados pessoais e segurança da rede e dos sistemas de informação',
    'Incumprimento de Código e Conduta',
    'Abuso de poder',
    'Assédio moral',
    'Assédio sexual',
    'Conflito de interesses',
    'Corrupção',
    'Descriminação/preconceito',
    'Furto/Roubo',
    'Quebra de sigilo ou segurança de informação',
    'Outros',
];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $tipo=trim($_POST['tipo']??'');
    $categoria=trim($_POST['categoria']??'');
    $assunto=trim($_POST['assunto']??'');
    $descricao=trim($_POST['descricao']??'');
    $nome=trim($_POST['nome']??'');
    $telefone=trim($_POST['telefone']??'');
    $email=trim($_POST['email']??'');

    if(!in_array($tipo,['Externo','Interno'],true)||!$categoria||!$assunto||!$descricao) $erro="Preencha os campos obrigatórios (Tipo, Assunto, Categoria e Descrição).";
    if(!$erro && !isset($_POST['consentimento'])) $erro="Tem de autorizar o tratamento sigiloso dos dados.";

    if(!$erro){
        $anonima = ($nome==='' && $telefone==='' && $email==='') ? 1 : 0;
        $codigo=gerarCodigoDenuncia();
        $prioridade=(stripos($descricao,'urgente')!==false||stripos($assunto,'urgente')!==false)?'alta':'normal';
        $stmt=$pdo->prepare("INSERT INTO denuncias (codigo,tipo,categoria,assunto,descricao,anonima,nome,telefone,email,estado,prioridade) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$codigo,$tipo,$categoria,$assunto,$descricao,$anonima,$nome?:null,$telefone?:null,$email?:null,'recebida',$prioridade]);
        $denunciaId=(int)$pdo->lastInsertId();
        $anexos=guardarAnexosDenuncia('anexos',$uploadDir,$erro);
        if(!$erro&&!empty($anexos)){ $stmtA=$pdo->prepare("INSERT INTO denuncias_anexos (denuncia_id, ficheiro, ficheiro_original) VALUES (?,?,?)"); foreach($anexos as $a) $stmtA->execute([$denunciaId,$a['ficheiro'],$a['original']]);}
        if(!$erro){ header("Location: denuncia-sucesso.php?codigo=".urlencode($codigo)); exit; }
    }
}
?>
<style>
.dn-page{background:linear-gradient(180deg,#f8fafc,#fff 55%,#f7f4ef)}
.dn-hero{padding:72px 0 92px;background:linear-gradient(135deg,#242A30,#11151B);color:white}
.dn-hero h1{font-size:clamp(34px,5vw,58px);margin:12px 0}
.dn-hero p{color:#dbeafe;font-size:18px;line-height:1.7;max-width:820px}
.dn-shell{margin-top:-46px;position:relative;z-index:2}
.dn-card{background:white;border:1px solid #e5e7eb;border-radius:30px;padding:30px;box-shadow:0 20px 55px rgba(0,0,0,.10);margin-bottom:40px}
.dn-form{display:grid;gap:20px}
.dn-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.dn-form label.dn-label{font-weight:900;color:#11151B;display:block;margin-bottom:8px}
.req{color:#dc2626}
.dn-form input,.dn-form select,.dn-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;border-radius:16px;min-height:54px;padding:0 15px;font-weight:800}
.dn-form textarea{min-height:170px;padding:15px;line-height:1.65}
.dn-help{color:#64748b;font-size:13px;font-weight:700}
.dn-tipos{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.dn-tipo-card{display:block;border:2px solid #e5e7eb;border-radius:18px;padding:18px;cursor:pointer;background:#f8fafc;transition:.2s}
.dn-tipo-card:hover{border-color:#cbd5e1}
.dn-tipo-card.sel{border-color:var(--cor-principal);background:#eff6ff;box-shadow:0 10px 26px rgba(36,42,50,.12)}
.dn-tipo-card input{width:auto;min-height:auto;margin-right:8px}
.dn-tipo-card strong{color:#11151B;font-size:17px}
.dn-tipo-card p{margin:10px 0 0;color:#52606d;font-size:13.5px;line-height:1.6;font-weight:600}
.dn-check{display:flex;gap:12px;align-items:flex-start;background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:16px}
.dn-check input{width:20px;height:20px;min-height:auto;margin-top:2px;flex-shrink:0}
.dn-btn{border:0;background:#94a3b8;color:#fff;border-radius:16px;padding:17px 22px;font-weight:900;font-size:16px;cursor:not-allowed;transition:.25s;opacity:.85}
.dn-btn.ativo{background:#242A30;cursor:pointer;opacity:1;box-shadow:0 14px 30px rgba(36,42,50,.3)}
.dn-btn.ativo:hover{transform:translateY(-3px)}
.dn-alert{background:#fee2e2;color:#991b1b;border-radius:16px;padding:14px;font-weight:900}
@media(max-width:760px){.dn-grid{grid-template-columns:1fr}.dn-tipos{grid-template-columns:1fr}.dn-card{padding:22px}}
</style>
<main class="dn-page">
<section class="dn-hero"><div class="container">
    <span class="cd-kicker"><i class="bi bi-shield-lock"></i> Canal de Denúncias</span>
    <h1>Efetuar uma denúncia</h1>
    <p>Preencha o formulário com o máximo de informação possível. Os campos marcados com <span class="req">*</span> são obrigatórios. A identificação é opcional — pode denunciar de forma anónima.</p>
</div></section>

<section class="section dn-shell"><div class="container"><div class="dn-card">
    <?php if($erro): ?><div class="dn-alert" role="alert"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="dn-form" id="formDenuncia">

        <div>
            <label class="dn-label">Tipo de Denúncia <span class="req">*</span></label>
            <div class="dn-tipos">
                <label class="dn-tipo-card" data-tipo>
                    <strong><input type="radio" name="tipo" value="Externo" required>Externo</strong>
                    <p>Pode usar este canal para denunciar violações do código de conduta ou violações da lei, incluindo questões relacionadas com suborno e corrupção, lei da concorrência, fraude, crime financeiro, questões de qualidade e segurança alimentar, assédio e discriminação, controlos de comércio internacional, proteção de dados pessoais, direitos e proteção de indivíduos, danos ambientais graves ou conflitos de interesse.</p>
                </label>
                <label class="dn-tipo-card" data-tipo>
                    <strong><input type="radio" name="tipo" value="Interno" required>Interno</strong>
                    <p>Poderá beneficiar de proteção do denunciante a pessoa singular que denuncie, ou divulgue publicamente, uma infração, com fundamento em informações obtidas no âmbito da sua atividade profissional desenvolvida na entidade, podendo ser considerados denunciantes: os trabalhadores com vínculo de emprego à entidade; os prestadores de serviços, contratantes, subcontratantes e fornecedores, bem como quaisquer pessoas que atuem sob a sua supervisão e direção; os membros dos Órgãos Executivo e Deliberativo da entidade; voluntários e estagiários, remunerados ou não remunerados.</p>
                </label>
            </div>
        </div>

        <div>
            <label class="dn-label">Assunto <span class="req">*</span></label>
            <input type="text" name="assunto" value="<?= htmlspecialchars($_POST['assunto'] ?? '') ?>" required>
        </div>

        <div>
            <label class="dn-label">Categoria <span class="req">*</span></label>
            <select name="categoria" required>
                <option value="">Selecione a categoria</option>
                <?php foreach($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= (($_POST['categoria'] ?? '')===$cat)?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="dn-label">Descrição <span class="req">*</span></label>
            <textarea name="descricao" placeholder="Descreva a situação com o máximo de detalhe possível." required><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="dn-label">Anexar documentos</label>
            <input type="file" name="anexos[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.mp3,.mp4,.zip">
            <small class="dn-help">Opcional. Pode anexar ficheiros que ajudem a fundamentar a denúncia.</small>
        </div>

        <div class="dn-grid">
            <div><label class="dn-label">Nome</label><input type="text" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"><small class="dn-help">Opcional</small></div>
            <div><label class="dn-label">Telefone</label><input type="text" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>"><small class="dn-help">Opcional</small></div>
            <div><label class="dn-label">Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"><small class="dn-help">Opcional</small></div>
        </div>

        <label class="dn-check">
            <input type="checkbox" name="consentimento" id="consentimento" required>
            <span>Autorizo que os meus dados sejam tratados de forma sigilosa apenas para os fins a que se destinam, comunicar a denúncia.</span>
        </label>

        <div>
            <button class="dn-btn" id="btnContinuar" type="submit" disabled>Continuar</button>
        </div>
    </form>
</div></div></section>
</main>

<script>
(function(){
    // Destacar cartão de tipo selecionado
    document.querySelectorAll('.dn-tipo-card input[type=radio]').forEach(function(r){
        r.addEventListener('change', function(){
            document.querySelectorAll('.dn-tipo-card').forEach(function(c){ c.classList.remove('sel'); });
            if(r.checked){ r.closest('.dn-tipo-card').classList.add('sel'); }
        });
    });
    // Botão cinzento -> azul quando o consentimento está marcado
    var chk = document.getElementById('consentimento');
    var btn = document.getElementById('btnContinuar');
    function sync(){ if(chk.checked){ btn.classList.add('ativo'); btn.disabled=false; } else { btn.classList.remove('ativo'); btn.disabled=true; } }
    chk.addEventListener('change', sync);
    sync();
})();
</script>

<?php
require_once "includes/footer.php";
ob_end_flush();
?>
