<?php require_once "includes/header.php"; ?>

<?php
$stmt = $pdo->query("
    SELECT *
    FROM assembleia_composicao
    WHERE ativo = 1
    ORDER BY 
        CASE WHEN grupo='Mesa da Assembleia' THEN 1 WHEN grupo='Vogais' THEN 2 ELSE 3 END,
        ordem ASC,
        nome ASC
");
$membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mesa=[]; $vogais=[]; $outros=[];
foreach($membros as $m){
    if($m['grupo']==='Mesa da Assembleia') $mesa[]=$m;
    elseif($m['grupo']==='Vogais') $vogais[]=$m;
    else $outros[]=$m;
}
require_once __DIR__ . "/includes/membros.php";
// Resolve a pasta nova (assets/img/membros/) e, para fotos antigas, as antigas.
function fotoMembro($m){ return !empty($m['foto']) ? htmlspecialchars(fotoMembroUrl($m['foto'])) : ""; }
function iniciais($nome){
    $p=preg_split('/\s+/', trim($nome)); $i='';
    foreach($p as $x){ if($x!=='') $i.=mb_strtoupper(mb_substr($x,0,1)); if(mb_strlen($i)>=2) break; }
    return $i ?: 'AF';
}
function renderMembro($m){
    $foto=fotoMembro($m);
    ?>
    <article class="member-card <?= !empty($m['destaque']) ? 'destaque' : '' ?>">
        <div class="member-top">
            <?php if($foto): ?>
                <div class="member-photo"><img src="<?= $foto ?>" alt="<?= htmlspecialchars($m['nome']) ?>"></div>
            <?php else: ?>
                <div class="member-initials"><?= htmlspecialchars(iniciais($m['nome'])) ?></div>
            <?php endif; ?>
            <div>
                <h3><?= htmlspecialchars($m['nome']) ?></h3>
                <span class="member-role"><?= htmlspecialchars($m['cargo']) ?></span>
                <?php if(!empty($m['partido'])): ?><br><span class="member-party"><?= htmlspecialchars($m['partido']) ?></span><?php endif; ?>
            </div>
        </div>
        <?php if(!empty($m['descricao'])): ?><p><?= nl2br(htmlspecialchars($m['descricao'])) ?></p><?php endif; ?>
    </article>
    <?php
}
?>

<style>
.comp-hero{background:radial-gradient(circle at 15% 20%,rgba(212,170,0,.22),transparent 30%),linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);color:white;padding:86px 0 96px;position:relative;overflow:hidden}.comp-hero .container{position:relative;z-index:2}.comp-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:9px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px}.comp-hero h1{font-size:clamp(42px,5vw,70px);margin:18px 0 14px;line-height:1.02}.comp-hero p{max-width:800px;color:#dbeafe;font-size:20px;line-height:1.75;margin:0}
.comp-shell{margin-top:-48px;position:relative;z-index:5}.comp-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}.comp-stat,.comp-section{background:white;border:1px solid #e5e7eb;border-radius:28px;padding:24px;box-shadow:0 18px 50px rgba(15,23,42,.08)}.comp-stat span{font-size:28px}.comp-stat strong{display:block;font-size:34px;color:#11151B}.comp-stat small{color:#64748b;font-weight:900}
.comp-section{margin-top:24px}.comp-head h2{margin:0;color:#11151B;font-size:32px}.comp-head p{color:#64748b}.comp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.member-card{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:26px;padding:22px;transition:.22s}.member-card:hover{transform:translateY(-4px);box-shadow:0 24px 55px rgba(15,23,42,.10)}.member-card.destaque{border-color:rgba(212,170,0,.5)}
.member-top{display:flex;align-items:center;gap:16px;margin-bottom:16px}.member-photo,.member-initials{width:86px;height:86px;border-radius:26px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,#242A30,#11151B);color:white;display:grid;place-items:center;font-weight:900;font-size:26px;box-shadow:0 16px 34px rgba(36,42,50,.20)}.member-photo img{width:100%;height:100%;object-fit:cover}.member-card h3{margin:0 0 6px;color:#11151B;font-size:21px}.member-role{display:inline-flex;background:#eef2ff;color:#242A30;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:900}.member-party{display:inline-flex;background:#fef3c7;color:#92400e;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:900;margin-top:8px}.member-card p{color:#64748b;line-height:1.65}
.empty-comp{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:22px;padding:26px;color:#64748b;font-weight:800}
@media(max-width:1050px){.comp-grid,.comp-stats{grid-template-columns:1fr 1fr}}@media(max-width:720px){.comp-grid,.comp-stats{grid-template-columns:1fr}}
</style>

<section class="comp-hero"><div class="container"><span class="comp-kicker"><i class="bi bi-bank2"></i> Assembleia de Freguesia</span><h1>Composição da Assembleia</h1><p>Conheça a composição da Assembleia de Freguesia, a mesa, os vogais e os representantes eleitos.</p></div></section>

<section class="section comp-shell"><div class="container">
    <div class="comp-stats">
        <div class="comp-stat"><span><i class="bi bi-people-fill"></i></span><strong><?= count($membros) ?></strong><small>Membros ativos</small></div>
        <div class="comp-stat"><span><i class="bi bi-bank2"></i></span><strong><?= count($mesa) ?></strong><small>Mesa da Assembleia</small></div>
        <div class="comp-stat"><span><i class="bi bi-bank2"></i></span><strong><?= count($vogais) ?></strong><small>Vogais</small></div>
    </div>

    <div class="comp-section"><div class="comp-head"><h2>Mesa da Assembleia</h2><p>Presidência e secretariado da Assembleia de Freguesia.</p></div>
        <?php if($mesa): ?><div class="comp-grid"><?php foreach($mesa as $m) renderMembro($m); ?></div><?php else: ?><div class="empty-comp">Ainda não existem membros da mesa publicados.</div><?php endif; ?>
    </div>

    <div class="comp-section"><div class="comp-head"><h2>Vogais da Assembleia</h2><p>Representantes eleitos que integram o órgão deliberativo da freguesia.</p></div>
        <?php if($vogais): ?><div class="comp-grid"><?php foreach($vogais as $m) renderMembro($m); ?></div><?php else: ?><div class="empty-comp">Ainda não existem vogais publicados.</div><?php endif; ?>
    </div>

    <?php if($outros): ?><div class="comp-section"><div class="comp-head"><h2>Outros elementos</h2></div><div class="comp-grid"><?php foreach($outros as $m) renderMembro($m); ?></div></div><?php endif; ?>
</div></section>

<?php require_once "includes/footer.php"; ?>
