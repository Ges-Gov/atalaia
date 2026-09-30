<?php
$adminPageTitle="Composição da Assembleia";
$adminActive="assembleia_composicao";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/membros.php";
requireAssembleiaManager();

$erro=''; $sucesso='';
$grupos=['Mesa da Assembleia','Vogais','Outros'];

if(isset($_GET['apagar'])){
    $id=(int)$_GET['apagar'];
    $st=$pdo->prepare("SELECT foto FROM assembleia_composicao WHERE id=?"); $st->execute([$id]); $m=$st->fetch(PDO::FETCH_ASSOC);
    if($m && !empty($m['foto'])){ $cam=__DIR__.'/..'.fotoMembroUrl($m['foto']); if(is_file($cam)) @unlink($cam); }
    $pdo->prepare("DELETE FROM assembleia_composicao WHERE id=?")->execute([$id]);
    header("Location: assembleia-composicao.php"); exit;
}
if(isset($_GET['toggle'])){
    $pdo->prepare("UPDATE assembleia_composicao SET ativo=IF(ativo=1,0,1), atualizado_em=NOW() WHERE id=?")->execute([(int)$_GET['toggle']]);
    header("Location: assembleia-composicao.php"); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0); $nome=trim($_POST['nome']??''); $cargo=trim($_POST['cargo']??''); $grupo=trim($_POST['grupo']??'Vogais');
    $partido=trim($_POST['partido']??''); $descricao=trim($_POST['descricao']??''); $ordem=(int)($_POST['ordem']??0);
    $destaque=isset($_POST['destaque'])?1:0; $ativo=isset($_POST['ativo'])?1:0; $foto=$_POST['foto_atual']??'';
    if(!$nome || !$cargo || !in_array($grupo,$grupos)) $erro="Preencha nome, cargo e grupo.";
    else{
        // Upload partilhado com o Executivo (includes/membros.php): mesma pasta, mesma
        // validação, e mensagens de erro que dizem o que falhou (permissões, tamanho...)
        // em vez do antigo "Erro ao enviar fotografia", que não ajudava ninguém.
        [$foto, $erroFoto] = guardarFotoMembro($_FILES['foto'] ?? [], (string)$foto);
        if ($erroFoto !== '') $erro = $erroFoto;
        if(!$erro){
            if($id>0){
                $st=$pdo->prepare("UPDATE assembleia_composicao SET nome=?,cargo=?,grupo=?,partido=?,foto=?,descricao=?,ordem=?,destaque=?,ativo=?,atualizado_em=NOW() WHERE id=?");
                $st->execute([$nome,$cargo,$grupo,$partido,$foto,$descricao,$ordem,$destaque,$ativo,$id]);
                $sucesso="Membro atualizado.";
            }else{
                $st=$pdo->prepare("INSERT INTO assembleia_composicao (nome,cargo,grupo,partido,foto,descricao,ordem,destaque,ativo) VALUES (?,?,?,?,?,?,?,?,?)");
                $st->execute([$nome,$cargo,$grupo,$partido,$foto,$descricao,$ordem,$destaque,$ativo]);
                $sucesso="Membro criado.";
            }
        }
    }
}

$editar=null;
if(isset($_GET['editar'])){
    $st=$pdo->prepare("SELECT * FROM assembleia_composicao WHERE id=?"); $st->execute([(int)$_GET['editar']]); $editar=$st->fetch(PDO::FETCH_ASSOC);
}
$membros=$pdo->query("SELECT * FROM assembleia_composicao ORDER BY CASE WHEN grupo='Mesa da Assembleia' THEN 1 WHEN grupo='Vogais' THEN 2 ELSE 3 END, ordem ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$total=count($membros); $ativos=0; $mesa=0; $vogais=0;
foreach($membros as $m){ if($m['ativo'])$ativos++; if($m['grupo']==='Mesa da Assembleia')$mesa++; if($m['grupo']==='Vogais')$vogais++; }
?>
<style>
.asmcomp-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}.asmcomp-hero h2{margin:8px 0;font-size:34px}.asmcomp-hero p{margin:0;color:#dbeafe}.asmcomp-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.asmcomp-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}.asmcomp-kpi,.asmcomp-card{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.06)}.asmcomp-kpi strong{font-size:32px;color:#11151B;display:block}.asmcomp-kpi span{color:#64748b;font-weight:900}.asmcomp-grid{display:grid;grid-template-columns:390px 1fr;gap:24px;align-items:start}.asmcomp-card h3{margin:0 0 16px;color:#11151B;font-size:24px}
.asmcomp-form{display:grid;gap:14px}.asmcomp-form label{font-weight:900;color:#11151B;font-size:13px}.asmcomp-form input,.asmcomp-form select,.asmcomp-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}.asmcomp-form textarea{min-height:120px;padding:14px}.asmcomp-checks,.asmcomp-actions{display:flex;gap:8px;flex-wrap:wrap}.asmcomp-checks label{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px 12px;border-radius:14px}.asmcomp-checks input{width:auto;min-height:auto}
.asmcomp-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.ok{background:#dcfce7;color:#166534}.err{background:#fee2e2;color:#991b1b}.member-list{display:grid;gap:12px}.member-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;display:grid;grid-template-columns:64px 1fr auto;gap:14px;align-items:center}.member-avatar{width:64px;height:64px;border-radius:18px;overflow:hidden;display:grid;place-items:center;background:#242A30;color:white;font-weight:900}.member-avatar img{width:100%;height:100%;object-fit:cover}.member-row h4{margin:0 0 5px;color:#11151B}.member-row p{margin:0;color:#64748b;font-weight:800;font-size:13px}.badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}.off{background:#fee2e2;color:#991b1b}.gold{background:#fef3c7;color:#92400e}
@media(max-width:1100px){.asmcomp-grid,.asmcomp-hero{grid-template-columns:1fr}.asmcomp-kpis{grid-template-columns:1fr 1fr}.member-row{grid-template-columns:1fr}}@media(max-width:700px){.asmcomp-kpis{grid-template-columns:1fr}}
</style>
<div class="asmcomp-hero"><div><span class="asmcomp-kicker">Assembleia Digital</span><h2>Composição da Assembleia</h2><p>Gerir membros, cargos, partidos, fotografias e ordem de apresentação.</p></div><a class="btn" href="../assembleia-composicao.php" target="_blank">Ver página pública</a></div>
<div class="asmcomp-kpis"><div class="asmcomp-kpi"><strong><?= $total ?></strong><span>Total</span></div><div class="asmcomp-kpi"><strong><?= $ativos ?></strong><span>Ativos</span></div><div class="asmcomp-kpi"><strong><?= $mesa ?></strong><span>Mesa</span></div><div class="asmcomp-kpi"><strong><?= $vogais ?></strong><span>Vogais</span></div></div>
<?php if($sucesso):?><div class="asmcomp-alert ok"><?= htmlspecialchars($sucesso) ?></div><?php endif;?><?php if($erro):?><div class="asmcomp-alert err"><?= htmlspecialchars($erro) ?></div><?php endif;?>
<div class="asmcomp-grid">
<div class="asmcomp-card"><h3><?= $editar?'Editar membro':'Novo membro' ?></h3><form method="POST" enctype="multipart/form-data" class="asmcomp-form">
<input type="hidden" name="id" value="<?= htmlspecialchars($editar['id']??0) ?>"><input type="hidden" name="foto_atual" value="<?= htmlspecialchars($editar['foto']??'') ?>">
<label>Nome *</label><input type="text" name="nome" value="<?= htmlspecialchars($editar['nome']??'') ?>" required>
<label>Cargo *</label><input type="text" name="cargo" value="<?= htmlspecialchars($editar['cargo']??'') ?>" required>
<label>Grupo *</label><select name="grupo"><?php foreach($grupos as $g):?><option value="<?= htmlspecialchars($g) ?>" <?= (($editar['grupo']??'')===$g)?'selected':'' ?>><?= htmlspecialchars($g) ?></option><?php endforeach;?></select>
<label>Partido / Movimento</label><input type="text" name="partido" value="<?= htmlspecialchars($editar['partido']??'') ?>">
<label>Fotografia</label><input type="file" name="foto" accept="image/*"><?php if(!empty($editar['foto'])):?><small>Atual: <?= htmlspecialchars($editar['foto']) ?></small><?php endif;?>
<label>Descrição / Bio</label><textarea name="descricao"><?= htmlspecialchars($editar['descricao']??'') ?></textarea>
<label>Ordem</label><input type="number" name="ordem" value="<?= htmlspecialchars($editar['ordem']??0) ?>">
<div class="asmcomp-checks"><label><input type="checkbox" name="destaque" <?= !empty($editar['destaque'])?'checked':'' ?>> Destaque</label><label><input type="checkbox" name="ativo" <?= !isset($editar['ativo'])||!empty($editar['ativo'])?'checked':'' ?>> Ativo</label></div>
<div class="asmcomp-actions"><button class="btn" type="submit"><?= $editar?'Guardar alterações':'Criar membro' ?></button><?php if($editar):?><a class="btn secondary" href="assembleia-composicao.php">Cancelar</a><?php endif;?></div>
</form></div>
<div class="asmcomp-card"><h3>Membros existentes</h3><div class="member-list">
<?php foreach($membros as $m):?><div class="member-row"><div class="member-avatar"><?php if(!empty($m['foto'])):?><img src="<?= htmlspecialchars(fotoMembroUrl($m['foto'])) ?>"><?php else:?><?= htmlspecialchars(mb_strtoupper(mb_substr($m['nome'],0,2))) ?><?php endif;?></div><div><h4><?= htmlspecialchars($m['nome']) ?></h4><p><span class="badge"><?= htmlspecialchars($m['grupo']) ?></span><span class="badge"><?= htmlspecialchars($m['cargo']) ?></span><?php if($m['partido']):?><span class="badge gold"><?= htmlspecialchars($m['partido']) ?></span><?php endif;?><?php if(!$m['ativo']):?><span class="badge off">Inativo</span><?php endif;?><?php if($m['destaque']):?><span class="badge gold">Destaque</span><?php endif;?></p></div><div class="asmcomp-actions"><a class="btn secondary" href="assembleia-composicao.php?editar=<?= (int)$m['id'] ?>">Editar</a><a class="btn secondary" href="assembleia-composicao.php?toggle=<?= (int)$m['id'] ?>"><?= $m['ativo']?'Ocultar':'Mostrar' ?></a><a class="btn danger" onclick="return confirm('Apagar este membro?')" href="assembleia-composicao.php?apagar=<?= (int)$m['id'] ?>">Apagar</a></div></div><?php endforeach;?>
<?php if(empty($membros)):?><p>Ainda não existem membros.</p><?php endif;?>
</div></div></div>
<?php require_once "includes/footer.php"; ?>
