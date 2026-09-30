<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header("Location: /admin/login.php?redirect=/area-vogais.php"); exit; }
require_once __DIR__ . "/includes/config.php";

$stmtUser = $pdo->prepare("SELECT * FROM admin_utilizadores WHERE id=? AND ativo=1");
$stmtUser->execute([$_SESSION['admin_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user || !in_array($user['tipo'], ['admin','vogal','presidente_assembleia'])) die("Acesso reservado à Assembleia.");

$uploadDir = __DIR__ . "/uploads/assembleia-anexos/";
$uploadUrl = "/uploads/assembleia-anexos/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
$extPermitidas = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','webp'];
$msg=''; $erro='';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['acao'] ?? '') === 'upload_anexos') {
    $sessaoId=(int)($_POST['sessao_id']??0);
    $observacao=trim($_POST['observacao']??'');
    $st=$pdo->prepare("SELECT id FROM assembleia_sessoes WHERE id=? AND ativo=1");
    $st->execute([$sessaoId]);
    if(!$st->fetch()) $erro="Escolha uma sessão válida.";
    elseif(empty($_FILES['anexos']['name'][0])) $erro="Escolha pelo menos um ficheiro.";
    else {
        foreach($_FILES['anexos']['name'] as $idx=>$nomeOriginal){
            if(!$nomeOriginal) continue;
            $tmp=$_FILES['anexos']['tmp_name'][$idx]??'';
            $size=(int)($_FILES['anexos']['size'][$idx]??0);
            $ext=strtolower(pathinfo($nomeOriginal,PATHINFO_EXTENSION));
            if(!in_array($ext,$extPermitidas)){ $erro.=" Formato não permitido: ".htmlspecialchars($nomeOriginal)."."; continue; }
            if($size > 15*1024*1024){ $erro.=" Ficheiro demasiado grande: ".htmlspecialchars($nomeOriginal)."."; continue; }
            $novo="vogal_sessao_".$sessaoId."_".date('YmdHis')."_".bin2hex(random_bytes(5)).".".$ext;
            if(move_uploaded_file($tmp,$uploadDir.$novo)){
                $titulo=pathinfo($nomeOriginal,PATHINFO_FILENAME);
                $origem=$user['tipo']==='presidente_assembleia'?'presidente_assembleia':'vogal';
                $stmt=$pdo->prepare("INSERT INTO assembleia_sessao_anexos (sessao_id,utilizador_id,titulo,observacao,ficheiro_original,ficheiro_guardado,extensao,tamanho_bytes,origem) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$sessaoId,$_SESSION['admin_id'],$titulo,$observacao,$nomeOriginal,$novo,$ext,$size,$origem]);
            } else $erro.=" Erro ao carregar: ".htmlspecialchars($nomeOriginal).".";
        }
        if(!$erro) $msg="Anexos enviados com sucesso.";
    }
}

$sessoes=$pdo->query("SELECT * FROM assembleia_sessoes WHERE ativo=1 ORDER BY data_sessao DESC, hora_sessao DESC")->fetchAll(PDO::FETCH_ASSOC);
$stmt=$pdo->prepare("SELECT a.*, s.titulo sessao_titulo, s.data_sessao, s.hora_sessao FROM assembleia_sessao_anexos a INNER JOIN assembleia_sessoes s ON s.id=a.sessao_id  ORDER BY a.criado_em DESC");
$stmt->execute();
$meusAnexos=$stmt->fetchAll(PDO::FETCH_ASSOC);
function tamanhoHumanoVogal($bytes){ $bytes=(int)$bytes; if($bytes>=1048576)return round($bytes/1048576,1)." MB"; if($bytes>=1024)return round($bytes/1024,1)." KB"; return $bytes." B"; }
require_once "includes/header.php";
?>
<style>
.vogal-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;padding:70px 0}.vogal-hero h1{font-size:48px;margin:10px 0}.vogal-hero p{color:#dbeafe;font-size:18px}.vogal-wrap{display:grid;grid-template-columns:1fr .8fr;gap:24px}.vogal-card{background:white;border:1px solid #e5e7eb;border-radius:26px;padding:24px;box-shadow:0 18px 50px rgba(15,23,42,.08);margin-top:24px}.vogal-card h2{margin-top:0;color:#11151B}.vogal-card p{color:#64748b;font-weight:800;line-height:1.6}.vogal-form{display:grid;gap:14px}.vogal-form label{font-weight:900;color:#11151B;font-size:13px}.vogal-form select,.vogal-form input,.vogal-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}.vogal-form textarea{min-height:120px;padding:14px}.anexo-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;margin-bottom:12px}.anexo-row h3{margin:0 0 8px;color:#11151B}.anexo-row small{display:block;color:#64748b;font-weight:800;line-height:1.5}.alert-vogal{padding:14px 16px;border-radius:16px;margin-top:20px;font-weight:900}.ok{background:#dcfce7;color:#166534}.err{background:#fee2e2;color:#991b1b}.logout-area{max-width:1400px;margin:30px auto 0;padding:0 20px;display:flex;justify-content:flex-end}.logout-area a{background:#dc2626;color:white;padding:12px 18px;border-radius:14px;text-decoration:none;font-weight:900;box-shadow:0 10px 25px rgba(220,38,38,.25)}@media(max-width:900px){.vogal-wrap{grid-template-columns:1fr}}
</style>
<section class="vogal-hero"><div class="container"><span><i class="bi bi-paperclip"></i> Área reservada</span><h1>Área dos Vogais</h1><p>Envio de documentos/anexos para as sessões e ordem de trabalhos da Assembleia.</p></div></section>
<div class="logout-area"><a href="/admin/logout.php"><i class="bi bi-box-arrow-right"></i> Terminar Sessão</a></div>
<section class="section"><div class="container">
<?php if($msg):?><div class="alert-vogal ok"><?= htmlspecialchars($msg) ?></div><?php endif;?><?php if($erro):?><div class="alert-vogal err"><?= htmlspecialchars($erro) ?></div><?php endif;?>
<div class="vogal-wrap"><div class="vogal-card"><h2><i class="bi bi-paperclip"></i> Enviar anexos para a ordem de trabalhos</h2><p>Escolha a sessão e carregue documentos para apreciação. Pode enviar PDF, Word, Excel ou imagens.</p><form method="POST" enctype="multipart/form-data" class="vogal-form"><input type="hidden" name="acao" value="upload_anexos"><label>Sessão *</label><select name="sessao_id" required><option value="">Escolher sessão</option><?php foreach($sessoes as $s):?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['titulo']) ?> — <?= date('d/m/Y',strtotime($s['data_sessao'])) ?> · <?= substr($s['hora_sessao'],0,5) ?></option><?php endforeach;?></select><label>Anexos *</label><input type="file" name="anexos[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"><label>Observação / enquadramento</label><textarea name="observacao" placeholder="Ex: Documento para incluir no ponto 2 da ordem de trabalhos"></textarea><button class="hero-btn" type="submit">Enviar anexos</button></form></div>
<div class="vogal-card"><h2><i class="bi bi-folder2-open"></i> Anexos enviados pelos membros</h2><?php foreach($meusAnexos as $a):?><div class="anexo-row"><h3><?= htmlspecialchars($a['titulo'] ?: $a['ficheiro_original']) ?></h3><small>Sessão: <?= htmlspecialchars($a['sessao_titulo']) ?><br>Data: <?= date('d/m/Y',strtotime($a['data_sessao'])) ?> · <?= substr($a['hora_sessao'],0,5) ?><br>Ficheiro: <?= htmlspecialchars($a['ficheiro_original']) ?> · <?= tamanhoHumanoVogal($a['tamanho_bytes']) ?><br>Enviado em: <?= date('d/m/Y H:i',strtotime($a['criado_em'])) ?></small><?php if(!empty($a['observacao'])):?><p><?= htmlspecialchars($a['observacao']) ?></p><?php endif;?><a class="hero-btn" href="<?= $uploadUrl.htmlspecialchars($a['ficheiro_guardado']) ?>" target="_blank">Download</a></div><?php endforeach;?><?php if(empty($meusAnexos)):?><p>Ainda não enviou anexos.</p><?php endif;?></div></div>
<!-- BLOCO VOTAÇÕES OCULTO TEMPORARIAMENTE. A lógica de votações não foi apagada do projeto; nesta fase a área dos vogais mostra apenas uploads/anexos da ordem de trabalhos. -->
</div></section>
<?php require_once "includes/footer.php"; ?>
