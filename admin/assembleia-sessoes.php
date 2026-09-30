<?php
$adminPageTitle = "Sessões da Assembleia";
$adminActive = "assembleia_sessoes";
require_once "includes/header.php";
requireAssembleiaManager();

$mensagem = '';
$erro = '';
$tipos = ['Sessão Ordinária', 'Sessão Extraordinária', 'Reunião Preparatória', 'Outra'];
$uploadDir = __DIR__ . "/../uploads/assembleia-anexos/";
$uploadUrl = "/uploads/assembleia-anexos/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
$extPermitidas = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','webp'];

function tamanhoHumanoSessao($bytes) {
    $bytes = (int)$bytes;
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . " MB";
    if ($bytes >= 1024) return round($bytes / 1024, 1) . " KB";
    return $bytes . " B";
}

function guardarAnexosSessao($pdo, $sessaoId, $uploadDir, $extPermitidas, &$erro, &$mensagem) {
    if (empty($_FILES['anexos']['name'][0])) return;
    $observacao = trim($_POST['anexos_observacao'] ?? '');

    foreach ($_FILES['anexos']['name'] as $idx => $nomeOriginal) {
        if (!$nomeOriginal) continue;
        $tmp = $_FILES['anexos']['tmp_name'][$idx] ?? '';
        $size = (int)($_FILES['anexos']['size'][$idx] ?? 0);
        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if (!in_array($ext, $extPermitidas)) { $erro .= " Formato não permitido: " . htmlspecialchars($nomeOriginal) . "."; continue; }
        if ($size > 15 * 1024 * 1024) { $erro .= " Ficheiro demasiado grande: " . htmlspecialchars($nomeOriginal) . "."; continue; }

        $novoNome = "sessao_" . (int)$sessaoId . "_" . date('YmdHis') . "_" . bin2hex(random_bytes(5)) . "." . $ext;
        if (move_uploaded_file($tmp, $uploadDir . $novoNome)) {
            $origem = ($_SESSION['admin_tipo'] ?? '') === 'presidente_assembleia' ? 'presidente_assembleia' : 'admin';
            $titulo = pathinfo($nomeOriginal, PATHINFO_FILENAME);
            $stmt = $pdo->prepare("INSERT INTO assembleia_sessao_anexos (sessao_id, utilizador_id, titulo, observacao, ficheiro_original, ficheiro_guardado, extensao, tamanho_bytes, origem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sessaoId, $_SESSION['admin_id'] ?? null, $titulo, $observacao, $nomeOriginal, $novoNome, $ext, $size, $origem]);
        } else {
            $erro .= " Erro ao carregar: " . htmlspecialchars($nomeOriginal) . ".";
        }
    }
    if (!$erro) $mensagem .= " Anexos carregados com sucesso.";
}

if (isset($_GET['apagar_anexo'])) {
    $anexoId = (int)$_GET['apagar_anexo'];
    $stmt = $pdo->prepare("SELECT * FROM assembleia_sessao_anexos WHERE id=?");
    $stmt->execute([$anexoId]);
    $anexo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($anexo) {
        $ficheiro = $uploadDir . basename($anexo['ficheiro_guardado']);
        if (is_file($ficheiro)) @unlink($ficheiro);
        $pdo->prepare("DELETE FROM assembleia_sessao_anexos WHERE id=?")->execute([$anexoId]);
    }
    $voltar = isset($_GET['sessao']) ? "?editar=" . (int)$_GET['sessao'] : "";
    header("Location: assembleia-sessoes.php" . $voltar); exit;
}

if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];
    $stmt = $pdo->prepare("SELECT ficheiro_guardado FROM assembleia_sessao_anexos WHERE sessao_id=?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) { $f = $uploadDir . basename($a['ficheiro_guardado']); if (is_file($f)) @unlink($f); }
    $pdo->prepare("DELETE FROM assembleia_sessao_anexos WHERE sessao_id=?")->execute([$id]);
    try { $pdo->prepare("DELETE FROM assembleia_sessao_documentos WHERE sessao_id=?")->execute([$id]); } catch(Exception $e) {}
    $pdo->prepare("DELETE FROM assembleia_sessoes WHERE id=?")->execute([$id]);
    header("Location: assembleia-sessoes.php?ok=apagado"); exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE assembleia_sessoes SET ativo=IF(ativo=1,0,1), atualizado_em=NOW() WHERE id=?")->execute([$id]);
    header("Location: assembleia-sessoes.php?ok=estado"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Sessão Ordinária');
    $descricao = trim($_POST['descricao'] ?? '');
    $local = trim($_POST['local_sessao'] ?? '');
    $data = trim($_POST['data_sessao'] ?? '');
    $hora = trim($_POST['hora_sessao'] ?? '');
    $estado = trim($_POST['estado'] ?? 'agendada');
    $ordem = trim($_POST['ordem_trabalhos'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$titulo || !$data || !$hora) $erro = "Preencha o título, data e hora.";
    else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE assembleia_sessoes SET titulo=?, tipo=?, descricao=?, local_sessao=?, data_sessao=?, hora_sessao=?, estado=?, ordem_trabalhos=?, observacoes=?, destaque=?, ativo=?, atualizado_em=NOW() WHERE id=?");
            $stmt->execute([$titulo,$tipo,$descricao,$local,$data,$hora,$estado,$ordem,$observacoes,$destaque,$ativo,$id]);
            $sessaoId = $id; $mensagem = "Sessão atualizada com sucesso.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO assembleia_sessoes (titulo,tipo,descricao,local_sessao,data_sessao,hora_sessao,estado,ordem_trabalhos,observacoes,destaque,ativo) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$titulo,$tipo,$descricao,$local,$data,$hora,$estado,$ordem,$observacoes,$destaque,$ativo]);
            $sessaoId = (int)$pdo->lastInsertId(); $mensagem = "Sessão criada com sucesso.";
        }
        guardarAnexosSessao($pdo, $sessaoId, $uploadDir, $extPermitidas, $erro, $mensagem);
    }
}

$editar = null; $anexosEditar = [];
if (isset($_GET['editar'])) {
    $idEditar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM assembleia_sessoes WHERE id=?");
    $stmt->execute([$idEditar]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editar) {
        $stmt = $pdo->prepare("SELECT a.*, u.nome AS utilizador_nome, u.username, u.tipo AS utilizador_tipo FROM assembleia_sessao_anexos a LEFT JOIN admin_utilizadores u ON u.id=a.utilizador_id WHERE a.sessao_id=? ORDER BY a.criado_em DESC");
        $stmt->execute([$idEditar]);
        $anexosEditar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$sessoes = $pdo->query("SELECT * FROM assembleia_sessoes ORDER BY data_sessao DESC, hora_sessao DESC")->fetchAll(PDO::FETCH_ASSOC);
$total=count($sessoes); $ativas=0; $agendadas=0; $realizadas=0;
foreach($sessoes as $s){ if($s['ativo'])$ativas++; if($s['estado']==='agendada')$agendadas++; if($s['estado']==='realizada')$realizadas++; }
function adminSessaoEstadoLabel($estado){ if($estado==='realizada')return 'Realizada'; if($estado==='cancelada')return 'Cancelada'; return 'Agendada'; }
?>
<style>
.asm-sess-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}.asm-sess-hero h2{margin:8px 0;font-size:34px}.asm-sess-hero p{margin:0;color:#dbeafe}.asm-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.asm-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}.asm-kpi,.asm-card{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.06)}.asm-kpi strong{font-size:32px;color:#11151B;display:block}.asm-kpi span{color:#64748b;font-weight:900}.asm-grid{display:grid;grid-template-columns:minmax(360px,.9fr) minmax(0,1.1fr);gap:24px;align-items:start}.asm-card h3{margin:0 0 16px;color:#11151B;font-size:24px}.asm-form{display:grid;gap:14px}.asm-form label{font-weight:900;color:#11151B;font-size:13px}.asm-form input,.asm-form select,.asm-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}.asm-form textarea{min-height:120px;padding:14px}.asm-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.asm-checks,.asm-actions,.asm-list-actions{display:flex;gap:8px;flex-wrap:wrap}.asm-checks label{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px 12px;border-radius:14px}.asm-checks input{width:auto;min-height:auto}.asm-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.asm-alert.ok{background:#dcfce7;color:#166534}.asm-alert.err{background:#fee2e2;color:#991b1b}.asm-sess-list{display:grid;gap:12px}.asm-sess-item{display:grid;grid-template-columns:62px 1fr auto;gap:14px;align-items:center;background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px}.asm-date{width:62px;height:62px;border-radius:18px;background:#242A30;color:white;display:grid;place-items:center;text-align:center}.asm-date b{display:block;font-size:23px}.asm-date small{font-size:11px;font-weight:900;text-transform:uppercase}.asm-sess-item h4{margin:0 0 6px;color:#11151B}.asm-sess-item p{margin:0;color:#64748b;font-weight:800;font-size:13px}.asm-badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}.asm-badge.off{background:#fee2e2;color:#991b1b}
.anexos-box{border:1px solid #dbe4ee;background:#f8fafc;border-radius:18px;padding:16px;display:grid;gap:12px}.anexos-box h4{margin:0;color:#11151B}.anexo-item{display:grid;grid-template-columns:1fr auto auto;gap:10px;align-items:center;background:white;border:1px solid #eef2f7;border-radius:14px;padding:12px}.anexo-item strong{color:#11151B}.anexo-item small{display:block;color:#64748b;font-weight:800;margin-top:3px}
@media(max-width:1100px){.asm-grid,.asm-sess-hero{grid-template-columns:1fr}.asm-kpis{grid-template-columns:1fr 1fr}.asm-sess-item,.anexo-item{grid-template-columns:1fr}}@media(max-width:700px){.asm-kpis,.asm-form-grid{grid-template-columns:1fr}}
</style>
<div class="asm-sess-hero"><div><span class="asm-kicker">Assembleia Digital</span><h2>Sessões da Assembleia</h2><p>Gerir sessões, ordem de trabalhos e anexos parlamentares.</p></div><a class="btn" href="../assembleia-sessoes.php" target="_blank">Ver página pública</a></div>
<div class="asm-kpis"><div class="asm-kpi"><strong><?= (int)$total ?></strong><span>Total sessões</span></div><div class="asm-kpi"><strong><?= (int)$ativas ?></strong><span>Ativas</span></div><div class="asm-kpi"><strong><?= (int)$agendadas ?></strong><span>Agendadas</span></div><div class="asm-kpi"><strong><?= (int)$realizadas ?></strong><span>Realizadas</span></div></div>
<?php if($mensagem):?><div class="asm-alert ok"><?= htmlspecialchars($mensagem) ?></div><?php endif;?><?php if($erro):?><div class="asm-alert err"><?= htmlspecialchars($erro) ?></div><?php endif;?>
<div class="asm-grid"><div class="asm-card"><h3><?= $editar?'Editar sessão':'Nova sessão' ?></h3><form method="POST" enctype="multipart/form-data" class="asm-form"><input type="hidden" name="id" value="<?= htmlspecialchars($editar['id']??0) ?>">
<div><label>Título *</label><input type="text" name="titulo" value="<?= htmlspecialchars($editar['titulo']??'') ?>" required></div>
<div class="asm-form-grid"><div><label>Tipo</label><select name="tipo"><?php foreach($tipos as $tipo):?><option value="<?= htmlspecialchars($tipo) ?>" <?= (($editar['tipo']??'')===$tipo)?'selected':'' ?>><?= htmlspecialchars($tipo) ?></option><?php endforeach;?></select></div><div><label>Estado</label><select name="estado"><option value="agendada" <?= (($editar['estado']??'')==='agendada')?'selected':'' ?>>Agendada</option><option value="realizada" <?= (($editar['estado']??'')==='realizada')?'selected':'' ?>>Realizada</option><option value="cancelada" <?= (($editar['estado']??'')==='cancelada')?'selected':'' ?>>Cancelada</option></select></div></div>
<div class="asm-form-grid"><div><label>Data *</label><input type="date" name="data_sessao" value="<?= htmlspecialchars($editar['data_sessao']??'') ?>" required></div><div><label>Hora *</label><input type="time" name="hora_sessao" value="<?= htmlspecialchars(isset($editar['hora_sessao'])?substr($editar['hora_sessao'],0,5):'') ?>" required></div></div>
<div><label>Local</label><input type="text" name="local_sessao" value="<?= htmlspecialchars($editar['local_sessao']??'') ?>"></div><div><label>Descrição</label><textarea name="descricao"><?= htmlspecialchars($editar['descricao']??'') ?></textarea></div><div><label>Ordem de trabalhos</label><textarea name="ordem_trabalhos"><?= htmlspecialchars($editar['ordem_trabalhos']??'') ?></textarea></div><div><label>Observações internas</label><textarea name="observacoes"><?= htmlspecialchars($editar['observacoes']??'') ?></textarea></div>
<div class="anexos-box"><h4><i class="bi bi-paperclip"></i> Anexos da ordem de trabalhos</h4><p style="margin:0;color:#64748b;font-weight:800;">Upload múltiplo: PDF, Word, Excel e imagens.</p><label>Escolher anexos</label><input type="file" name="anexos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"><label>Observação comum</label><textarea name="anexos_observacao"></textarea></div>
<?php if($editar && !empty($anexosEditar)):?><div class="anexos-box"><h4><i class="bi bi-folder2-open"></i> Anexos desta sessão</h4><?php foreach($anexosEditar as $a):?><div class="anexo-item"><div><strong><?= htmlspecialchars($a['titulo'] ?: $a['ficheiro_original']) ?></strong><small><?= htmlspecialchars($a['ficheiro_original']) ?> · <?= tamanhoHumanoSessao($a['tamanho_bytes']) ?><br>Enviado por: <?= htmlspecialchars($a['utilizador_nome'] ?: $a['username'] ?: 'Sistema') ?> (<?= htmlspecialchars($a['origem']) ?>) · <?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></small><?php if(!empty($a['observacao'])):?><small>Obs: <?= htmlspecialchars($a['observacao']) ?></small><?php endif;?></div><a class="btn secondary" href="<?= $uploadUrl.htmlspecialchars($a['ficheiro_guardado']) ?>" target="_blank">Download</a><a class="btn danger" href="assembleia-sessoes.php?apagar_anexo=<?= (int)$a['id'] ?>&sessao=<?= (int)$editar['id'] ?>" onclick="return confirm('Apagar este anexo?')">Apagar</a></div><?php endforeach;?></div><?php endif;?>
<div class="asm-checks"><label><input type="checkbox" name="destaque" <?= !empty($editar['destaque'])?'checked':'' ?>> Destaque</label><label><input type="checkbox" name="ativo" <?= !isset($editar['ativo'])||!empty($editar['ativo'])?'checked':'' ?>> Ativo</label></div><div class="asm-actions"><button class="btn" type="submit"><?= $editar?'Guardar alterações':'Criar sessão' ?></button><?php if($editar):?><a class="btn secondary" href="assembleia-sessoes.php">Cancelar</a><?php endif;?></div></form></div>
<div class="asm-card"><h3>Sessões existentes</h3><div class="asm-sess-list"><?php foreach($sessoes as $s):?><div class="asm-sess-item"><div class="asm-date"><div><b><?= date('d', strtotime($s['data_sessao'])) ?></b><small><?= date('M', strtotime($s['data_sessao'])) ?></small></div></div><div><h4><?= htmlspecialchars($s['titulo']) ?></h4><p><span class="asm-badge"><?= htmlspecialchars(adminSessaoEstadoLabel($s['estado'])) ?></span><span class="asm-badge"><?= htmlspecialchars($s['tipo']) ?></span><?php if(!$s['ativo']):?><span class="asm-badge off">Inativa</span><?php endif;?><?php if($s['destaque']):?><span class="asm-badge">Destaque</span><?php endif;?></p><p><?= date('d/m/Y', strtotime($s['data_sessao'])) ?> · <?= substr($s['hora_sessao'],0,5) ?> · <?= htmlspecialchars($s['local_sessao'] ?: 'Local a definir') ?></p></div><div class="asm-list-actions"><a class="btn secondary" href="assembleia-sessoes.php?editar=<?= (int)$s['id'] ?>">Editar</a><a class="btn secondary" href="assembleia-sessoes.php?toggle=<?= (int)$s['id'] ?>"><?= $s['ativo']?'Ocultar':'Mostrar' ?></a><a class="btn danger" href="assembleia-sessoes.php?apagar=<?= (int)$s['id'] ?>" onclick="return confirm('Apagar esta sessão?')">Apagar</a></div></div><?php endforeach;?><?php if(empty($sessoes)):?><p>Ainda não existem sessões da Assembleia.</p><?php endif;?></div></div></div>
<?php require_once "includes/footer.php"; ?>
