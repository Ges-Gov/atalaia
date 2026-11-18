<?php
ob_start();
require_once "includes/header.php";
$erro='';
$errosCampo=[];   // erros junto a cada campo (acessibilidade, 18/11/2026)
$uploadDir=__DIR__."/uploads/denuncias/";
if(!is_dir($uploadDir)) mkdir($uploadDir,0775,true);

function gerarCodigoDenuncia(){return prefixoPedidos()."-".date("Y")."-".strtoupper(substr(bin2hex(random_bytes(4)),0,8));}

function guardarAnexosDenuncia($campo,$uploadDir,&$erro){
    $ficheiros=[]; if(empty($_FILES[$campo]['name'])||!is_array($_FILES[$campo]['name'])) return $ficheiros;
    $permitidos=['pdf','doc','docx','jpg','jpeg','png','webp','mp3','mp4','zip'];
    if(count(array_filter($_FILES[$campo]['name'])) > 5){ $erro="Pode anexar no máximo 5 ficheiros."; return []; }
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

    // Cada erro fica junto ao seu campo e diz o que fazer (checklist «Transação» da AMA, 18/11/2026)
    if(!in_array($tipo,['Externo','Interno'],true)) $errosCampo['tipo']='Escolha se a denúncia é externa ou interna.';
    if(!$assunto) $errosCampo['assunto']='Escreva o assunto da denúncia em poucas palavras.';
    if(!$categoria) $errosCampo['categoria']='Escolha a categoria que melhor descreve a situação.';
    if(!$descricao) $errosCampo['descricao']='Descreva a situação: o que aconteceu, quando e onde.';
    // anexos verificados ANTES de gravar (antes, um anexo inválido dava erro depois de a denúncia já estar gravada)
    $nomesAnexos = array_filter((array)($_FILES['anexos']['name'] ?? []));
    if(count($nomesAnexos) > 5) $errosCampo['anexos']='Pode anexar no máximo 5 ficheiros. Escolha outra vez só os mais importantes.';
    else foreach($nomesAnexos as $na){ if(!in_array(strtolower(pathinfo($na,PATHINFO_EXTENSION)),['pdf','doc','docx','jpg','jpeg','png','webp','mp3','mp4','zip'],true)){ $errosCampo['anexos']='O ficheiro «'.$na.'» não é aceite. Use PDF, Word, imagem (JPG, PNG, WEBP), MP3, MP4 ou ZIP.'; break; } }
    // Contactos opcionais (a denúncia pode ser anónima), mas se forem dados têm de ser válidos.
    require_once __DIR__ . '/includes/validacao.php';
    if($email!=='' && !emailValido($email)) $errosCampo['email']='Indique um email válido e que exista (ex.: nome@exemplo.pt), ou deixe o campo vazio.';
    if($telefone!=='' && !telefoneValido($telefone)) $errosCampo['telefone']='Indique um número de telefone válido (ex.: 912 345 678, ou com o indicativo do país se for estrangeiro), ou deixe o campo vazio.';
    if(!isset($_POST['consentimento'])) $errosCampo['consentimento']='Para enviar, autorize o tratamento sigiloso dos dados (caixa no fim do formulário).';

    if(!$errosCampo){
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
<div class="dn-page">
<section class="dn-hero"><div class="container">
    <span class="cd-kicker"><i class="bi bi-shield-lock"></i> <?= te('denuncias.hero.kicker') ?></span>
    <h1><?= te('denuncias.hero.titulo') ?></h1>
    <p><?= te('denuncias.hero.texto1') ?> <span class="req">*</span> <?= te('denuncias.hero.texto2') ?></p>
</div></section>

<section class="section dn-shell"><div class="container"><div class="dn-card">
    <?php if($erro): ?><div class="dn-alert" role="alert"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="dn-form" id="formDenuncia">
        <?php /* etiquetas ligadas aos campos, erros junto a cada campo e o tipo escolhido mantido (acessibilidade, 18/11/2026) */ ?>
        <?= resumoErros($errosCampo, ['tipo' => 'dnTipoExterno', 'assunto' => 'dnAssunto', 'categoria' => 'dnCategoria', 'descricao' => 'dnDescricao', 'anexos' => 'dnAnexos', 'telefone' => 'dnTelefone', 'email' => 'dnEmail']) ?>
        <?php $tipoEscolhido = $_POST['tipo'] ?? ''; ?>

        <div role="radiogroup" aria-labelledby="dnTipoRotulo" aria-required="true"<?= isset($errosCampo['tipo']) ? ' aria-describedby="erro-tipo"' : '' ?>>
            <label class="dn-label" id="dnTipoRotulo"><?= te('denuncias.formulario.tipo') ?> <span class="req" aria-hidden="true">*</span></label>
            <div class="dn-tipos">
                <label class="dn-tipo-card<?= $tipoEscolhido === 'Externo' ? ' sel' : '' ?>" data-tipo>
                    <strong><input type="radio" id="dnTipoExterno" name="tipo" value="Externo" required<?= $tipoEscolhido === 'Externo' ? ' checked' : '' ?>>Externo</strong>
                    <p><?= te('denuncias.formulario.externo_texto') ?></p>
                </label>
                <label class="dn-tipo-card<?= $tipoEscolhido === 'Interno' ? ' sel' : '' ?>" data-tipo>
                    <strong><input type="radio" name="tipo" value="Interno" required<?= $tipoEscolhido === 'Interno' ? ' checked' : '' ?>>Interno</strong>
                    <p><?= te('denuncias.formulario.interno_texto') ?></p>
                </label>
            </div>
            <?= erroCampo($errosCampo, 'tipo') ?>
        </div>

        <div>
            <label class="dn-label" for="dnAssunto"><?= te('denuncias.formulario.assunto') ?> <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="dnAssunto" name="assunto" value="<?= htmlspecialchars($_POST['assunto'] ?? '') ?>" required<?= erroAttrs($errosCampo, 'assunto') ?>>
            <?= erroCampo($errosCampo, 'assunto') ?>
        </div>

        <div>
            <label class="dn-label" for="dnCategoria"><?= te('denuncias.formulario.categoria') ?> <span class="req" aria-hidden="true">*</span></label>
            <select id="dnCategoria" name="categoria" required<?= erroAttrs($errosCampo, 'categoria') ?>>
                <option value=""><?= te('denuncias.formulario.categoria_opcao') ?></option>
                <?php foreach($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= (($_POST['categoria'] ?? '')===$cat)?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <?= erroCampo($errosCampo, 'categoria') ?>
        </div>

        <div>
            <label class="dn-label" for="dnDescricao"><?= te('denuncias.formulario.descricao') ?> <span class="req" aria-hidden="true">*</span></label>
            <textarea id="dnDescricao" name="descricao" placeholder="<?= te('denuncias.formulario.descricao_exemplo') ?>" required<?= erroAttrs($errosCampo, 'descricao') ?>><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
            <?= erroCampo($errosCampo, 'descricao') ?>
        </div>

        <div>
            <label class="dn-label" for="dnAnexos"><?= te('denuncias.formulario.anexos') ?></label>
            <input type="file" id="dnAnexos" name="anexos[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.mp3,.mp4,.zip"<?= erroAttrs($errosCampo, 'anexos') ?>>
            <small class="dn-help"><?= te('denuncias.formulario.anexos_ajuda') ?></small>
            <?= erroCampo($errosCampo, 'anexos') ?>
        </div>

        <div class="dn-grid">
            <div><label class="dn-label" for="dnNome"><?= te('denuncias.formulario.nome') ?></label><input type="text" id="dnNome" name="nome" autocomplete="name" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"><small class="dn-help"><?= te('denuncias.formulario.opcional') ?></small></div>
            <div><label class="dn-label" for="dnTelefone"><?= te('denuncias.formulario.telefone') ?></label><input type="tel" id="dnTelefone" inputmode="tel" autocomplete="tel" maxlength="20" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>"<?= erroAttrs($errosCampo, 'telefone') ?>><small class="dn-help"><?= te('denuncias.formulario.opcional') ?></small><?= erroCampo($errosCampo, 'telefone') ?></div>
            <div><label class="dn-label" for="dnEmail"><?= te('denuncias.formulario.email') ?></label><input type="email" id="dnEmail" name="email" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"<?= erroAttrs($errosCampo, 'email') ?>><small class="dn-help"><?= te('denuncias.formulario.opcional') ?></small><?= erroCampo($errosCampo, 'email') ?></div>
        </div>

        <label class="dn-check">
            <input type="checkbox" name="consentimento" id="consentimento" required<?= !empty($_POST['consentimento']) ? ' checked' : '' ?>>
            <span><?= te('denuncias.formulario.consentimento') ?> <span class="req" aria-hidden="true">*</span></span>
        </label>
        <?= erroCampo($errosCampo, 'consentimento') ?>

        <div>
            <button class="dn-btn" id="btnContinuar" type="submit" disabled>Continuar</button>
        </div>
    </form>
</div></div></section>
</div>

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
