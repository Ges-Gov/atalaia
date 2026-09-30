<?php
$adminPageTitle="Funcionamento da Assembleia";
$adminActive="assembleia_funcionamento";
require_once "includes/header.php";
requireAssembleiaManager();

$erro='';$sucesso='';$tipos=['timeline','card','destaque'];

function getCfg($pdo){
 $c=$pdo->query("SELECT * FROM assembleia_funcionamento_config ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
 if(!$c){
  $pdo->query("INSERT INTO assembleia_funcionamento_config (hero_titulo,hero_subtitulo,intro_titulo,intro_texto) VALUES ('Funcionamento da Assembleia','Conheça o funcionamento da Assembleia.','Democracia local em funcionamento','Texto introdutório.')");
  $c=$pdo->query("SELECT * FROM assembleia_funcionamento_config ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
 }
 return $c;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
 $acao=$_POST['acao']??'';
 if($acao==='config'){
  $id=(int)$_POST['id'];
  $vals=[
   trim($_POST['hero_kicker']??''),trim($_POST['hero_titulo']??''),trim($_POST['hero_subtitulo']??''),
   trim($_POST['intro_titulo']??''),trim($_POST['intro_texto']??''),
   trim($_POST['destaque_1_titulo']??''),trim($_POST['destaque_1_valor']??''),
   trim($_POST['destaque_2_titulo']??''),trim($_POST['destaque_2_valor']??''),
   trim($_POST['destaque_3_titulo']??''),trim($_POST['destaque_3_valor']??''),$id
  ];
  if(!$vals[1])$erro="O título principal é obrigatório.";
  else{
   $st=$pdo->prepare("UPDATE assembleia_funcionamento_config SET hero_kicker=?,hero_titulo=?,hero_subtitulo=?,intro_titulo=?,intro_texto=?,destaque_1_titulo=?,destaque_1_valor=?,destaque_2_titulo=?,destaque_2_valor=?,destaque_3_titulo=?,destaque_3_valor=?,atualizado_em=NOW() WHERE id=?");
   $st->execute($vals);$sucesso="Conteúdo principal atualizado.";
  }
 }
 if($acao==='bloco'){
  $id=(int)($_POST['id']??0);$titulo=trim($_POST['titulo']??'');$sub=trim($_POST['subtitulo']??'');$cont=trim($_POST['conteudo']??'');$icone=trim($_POST['icone']??'');$tipo=trim($_POST['tipo']??'card');$ordem=(int)($_POST['ordem']??0);$ativo=isset($_POST['ativo'])?1:0;
  if(!$titulo || !in_array($tipo,$tipos))$erro="Preencha o título e escolha um tipo válido.";
  else{
   if($id>0){$st=$pdo->prepare("UPDATE assembleia_funcionamento_blocos SET titulo=?,subtitulo=?,conteudo=?,icone=?,tipo=?,ordem=?,ativo=?,atualizado_em=NOW() WHERE id=?");$st->execute([$titulo,$sub,$cont,$icone,$tipo,$ordem,$ativo,$id]);$sucesso="Bloco atualizado.";}
   else{$st=$pdo->prepare("INSERT INTO assembleia_funcionamento_blocos (titulo,subtitulo,conteudo,icone,tipo,ordem,ativo) VALUES (?,?,?,?,?,?,?)");$st->execute([$titulo,$sub,$cont,$icone,$tipo,$ordem,$ativo]);$sucesso="Bloco criado.";}
  }
 }
}
if(isset($_GET['apagar'])){$pdo->prepare("DELETE FROM assembleia_funcionamento_blocos WHERE id=?")->execute([(int)$_GET['apagar']]);header("Location: assembleia-funcionamento.php");exit;}
if(isset($_GET['toggle'])){$pdo->prepare("UPDATE assembleia_funcionamento_blocos SET ativo=IF(ativo=1,0,1),atualizado_em=NOW() WHERE id=?")->execute([(int)$_GET['toggle']]);header("Location: assembleia-funcionamento.php");exit;}
$config=getCfg($pdo);
$editar=null;if(isset($_GET['editar'])){$st=$pdo->prepare("SELECT * FROM assembleia_funcionamento_blocos WHERE id=?");$st->execute([(int)$_GET['editar']]);$editar=$st->fetch(PDO::FETCH_ASSOC);}
$blocos=$pdo->query("SELECT * FROM assembleia_funcionamento_blocos ORDER BY ordem ASC,id ASC")->fetchAll(PDO::FETCH_ASSOC);
$total=count($blocos);$ativos=0;$timeline=0;$cards=0;foreach($blocos as $b){if($b['ativo'])$ativos++;if($b['tipo']=='timeline')$timeline++;if($b['tipo']=='card')$cards++;}
?>
<style>
.funcadmin-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}.funcadmin-hero h2{margin:8px 0;font-size:34px}.funcadmin-hero p{margin:0;color:#dbeafe}.func-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}.funcadmin-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}.funcadmin-kpi,.funcadmin-card{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.06)}.funcadmin-kpi strong{font-size:32px;color:#11151B;display:block}.funcadmin-kpi span{color:#64748b;font-weight:900}.funcadmin-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}.funcadmin-card h3{margin:0 0 16px;color:#11151B;font-size:24px}.func-form{display:grid;gap:14px}.func-form label{font-weight:900;color:#11151B;font-size:13px}.func-form input,.func-form select,.func-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}.func-form textarea{min-height:120px;padding:14px;line-height:1.6}.func-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.func-checks,.func-actions{display:flex;gap:8px;flex-wrap:wrap}.func-checks label{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px 12px;border-radius:14px}.func-checks input{width:auto;min-height:auto}.func-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.func-alert.ok{background:#dcfce7;color:#166534}.func-alert.err{background:#fee2e2;color:#991b1b}.bloco-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;display:grid;grid-template-columns:54px 1fr auto;gap:14px;align-items:center;margin-bottom:12px}.bloco-icon{width:54px;height:54px;border-radius:16px;background:#242A30;color:white;display:grid;place-items:center;font-size:24px}.bloco-row h4{margin:0 0 5px;color:#11151B}.bloco-row p{margin:0;color:#64748b;font-weight:800;font-size:13px}.badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}.badge.off{background:#fee2e2;color:#991b1b}@media(max-width:1100px){.funcadmin-grid,.funcadmin-hero{grid-template-columns:1fr}.funcadmin-kpis{grid-template-columns:1fr 1fr}.bloco-row{grid-template-columns:1fr}}@media(max-width:700px){.funcadmin-kpis,.func-form-grid{grid-template-columns:1fr}}
</style>
<div class="funcadmin-hero"><div><span class="func-kicker">Assembleia Digital</span><h2>Funcionamento da Assembleia</h2><p>Editar hero, introdução, destaques, timeline e blocos da página pública.</p></div><a class="btn" href="../assembleia-funcionamento.php" target="_blank">Ver página pública</a></div>
<div class="funcadmin-kpis"><div class="funcadmin-kpi"><strong><?= $total ?></strong><span>Total blocos</span></div><div class="funcadmin-kpi"><strong><?= $ativos ?></strong><span>Ativos</span></div><div class="funcadmin-kpi"><strong><?= $timeline ?></strong><span>Timeline</span></div><div class="funcadmin-kpi"><strong><?= $cards ?></strong><span>Cards</span></div></div>
<?php if($sucesso):?><div class="func-alert ok"><?= htmlspecialchars($sucesso) ?></div><?php endif;?><?php if($erro):?><div class="func-alert err"><?= htmlspecialchars($erro) ?></div><?php endif;?>
<div class="funcadmin-card" style="margin-bottom:24px;"><h3>Conteúdo principal da página</h3><form method="POST" class="func-form"><input type="hidden" name="acao" value="config"><input type="hidden" name="id" value="<?= (int)$config['id'] ?>">
<div class="func-form-grid"><div><label>Kicker</label><input name="hero_kicker" value="<?= htmlspecialchars($config['hero_kicker']??'') ?>"></div><div><label>Título principal *</label><input name="hero_titulo" value="<?= htmlspecialchars($config['hero_titulo']??'') ?>" required></div></div>
<label>Subtítulo do hero</label><textarea name="hero_subtitulo"><?= htmlspecialchars($config['hero_subtitulo']??'') ?></textarea>
<div class="func-form-grid"><div><label>Título introdução</label><input name="intro_titulo" value="<?= htmlspecialchars($config['intro_titulo']??'') ?>"></div><div><label>Texto introdução</label><textarea name="intro_texto"><?= htmlspecialchars($config['intro_texto']??'') ?></textarea></div></div>
<div class="func-form-grid"><div><label>Destaque 1 — título</label><input name="destaque_1_titulo" value="<?= htmlspecialchars($config['destaque_1_titulo']??'') ?>"></div><div><label>Destaque 1 — valor</label><input name="destaque_1_valor" value="<?= htmlspecialchars($config['destaque_1_valor']??'') ?>"></div></div>
<div class="func-form-grid"><div><label>Destaque 2 — título</label><input name="destaque_2_titulo" value="<?= htmlspecialchars($config['destaque_2_titulo']??'') ?>"></div><div><label>Destaque 2 — valor</label><input name="destaque_2_valor" value="<?= htmlspecialchars($config['destaque_2_valor']??'') ?>"></div></div>
<div class="func-form-grid"><div><label>Destaque 3 — título</label><input name="destaque_3_titulo" value="<?= htmlspecialchars($config['destaque_3_titulo']??'') ?>"></div><div><label>Destaque 3 — valor</label><input name="destaque_3_valor" value="<?= htmlspecialchars($config['destaque_3_valor']??'') ?>"></div></div>
<div class="func-actions"><button class="btn" type="submit">Guardar conteúdo principal</button></div></form></div>
<div class="funcadmin-grid"><div class="funcadmin-card"><h3><?= $editar?'Editar bloco':'Novo bloco' ?></h3><form method="POST" class="func-form"><input type="hidden" name="acao" value="bloco"><input type="hidden" name="id" value="<?= htmlspecialchars($editar['id']??0) ?>"><label>Título *</label><input name="titulo" value="<?= htmlspecialchars($editar['titulo']??'') ?>" required><label>Subtítulo</label><input name="subtitulo" value="<?= htmlspecialchars($editar['subtitulo']??'') ?>"><label>Ícone (classe Bootstrap)</label><input name="icone" value="<?= htmlspecialchars($editar['icone']??'') ?>" placeholder="Ex: bi-journal-text"><div class="func-form-grid"><div><label>Tipo</label><select name="tipo"><?php foreach($tipos as $t):?><option value="<?= $t ?>" <?= (($editar['tipo']??'')===$t)?'selected':'' ?>><?= $t ?></option><?php endforeach;?></select></div><div><label>Ordem</label><input type="number" name="ordem" value="<?= htmlspecialchars($editar['ordem']??0) ?>"></div></div><label>Conteúdo</label><textarea name="conteudo"><?= htmlspecialchars($editar['conteudo']??'') ?></textarea><div class="func-checks"><label><input type="checkbox" name="ativo" <?= !isset($editar['ativo'])||!empty($editar['ativo'])?'checked':'' ?>> Ativo</label></div><div class="func-actions"><button class="btn" type="submit"><?= $editar?'Guardar bloco':'Criar bloco' ?></button><?php if($editar):?><a class="btn secondary" href="assembleia-funcionamento.php">Cancelar</a><?php endif;?></div></form></div>
<div class="funcadmin-card"><h3>Blocos existentes</h3><?php foreach($blocos as $b):?><div class="bloco-row"><div class="bloco-icon"><i class="bi <?= htmlspecialchars($b['icone']?:'bi-bank') ?>"></i></div><div><h4><?= htmlspecialchars($b['titulo']) ?></h4><p><span class="badge"><?= htmlspecialchars($b['tipo']) ?></span><span class="badge">Ordem <?= (int)$b['ordem'] ?></span><?php if(!$b['ativo']):?><span class="badge off">Inativo</span><?php endif;?></p></div><div class="func-actions"><a class="btn secondary" href="assembleia-funcionamento.php?editar=<?= (int)$b['id'] ?>">Editar</a><a class="btn secondary" href="assembleia-funcionamento.php?toggle=<?= (int)$b['id'] ?>"><?= $b['ativo']?'Ocultar':'Mostrar' ?></a><a class="btn danger" onclick="return confirm('Apagar este bloco?')" href="assembleia-funcionamento.php?apagar=<?= (int)$b['id'] ?>">Apagar</a></div></div><?php endforeach;?><?php if(empty($blocos)):?><p>Ainda não existem blocos.</p><?php endif;?></div></div>
<?php require_once "includes/footer.php"; ?>
