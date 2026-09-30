<?php
$pageTitle="Canal de Denúncias"; $active="dashboard"; require_once "layout-top.php";
$total=(int)$pdo->query("SELECT COUNT(*) FROM denuncias")->fetchColumn();
$recebidas=(int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE estado='recebida'")->fetchColumn();
$analise=(int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE estado='em_analise'")->fetchColumn();
$concluidas=(int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE estado='concluida'")->fetchColumn();
$denuncias=$pdo->query("SELECT * FROM denuncias ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);
function estadoDen($e){return ['recebida'=>'Recebida','em_analise'=>'Em análise','info_solicitada'=>'Informação solicitada','encaminhada'=>'Encaminhada','concluida'=>'Concluída','arquivada'=>'Arquivada'][$e]??$e;}
?>
<style>.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.stat{background:white;border:1px solid #e5e7eb;border-radius:22px;padding:22px}.stat strong{display:block;color:#242A30;font-size:36px}.row{background:white;border:1px solid #e5e7eb;border-radius:20px;padding:16px;margin-bottom:12px;display:grid;grid-template-columns:1fr auto;gap:14px;align-items:center}.row h3{margin:0 0 8px;color:#11151B}@media(max-width:900px){.stats,.row{grid-template-columns:1fr}}</style>
<div class="stats"><div class="stat"><strong><?= $total ?></strong><span>Total</span></div><div class="stat"><strong><?= $recebidas ?></strong><span>Recebidas</span></div><div class="stat"><strong><?= $analise ?></strong><span>Em análise</span></div><div class="stat"><strong><?= $concluidas ?></strong><span>Concluídas</span></div></div>
<?php foreach($denuncias as $d): ?><div class="row"><div><h3><?= htmlspecialchars($d['assunto']) ?></h3><span class="badge"><?= htmlspecialchars($d['codigo']) ?></span><span class="badge"><?= htmlspecialchars(estadoDen($d['estado'])) ?></span><span class="badge <?= $d['prioridade']==='alta'?'alta':'' ?>"><?= htmlspecialchars($d['prioridade']) ?></span><span class="badge"><?= htmlspecialchars($d['categoria']) ?></span></div><div><a class="btn" href="ver.php?id=<?= (int)$d['id'] ?>">Ver denúncia</a></div></div><?php endforeach; ?>
<?php if(empty($denuncias)): ?><div class="row">Ainda não existem denúncias.</div><?php endif; ?>
<?php require_once "layout-bottom.php"; ?>