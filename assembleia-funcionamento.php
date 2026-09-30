<?php require_once "includes/header.php"; ?>
<?php
$cfg=[
'hero_kicker'=>'Funcionamento da Assembleia',
'hero_titulo'=>'Funcionamento da Assembleia',
'hero_subtitulo'=>'Conheça a forma como a Assembleia de Freguesia funciona, delibera e garante a participação democrática.',
'intro_titulo'=>'Democracia local em funcionamento',
'intro_texto'=>'A Assembleia de Freguesia é o órgão deliberativo da freguesia, responsável pela discussão, apreciação e aprovação das principais decisões locais.',
'destaque_1_titulo'=>'Sessões','destaque_1_valor'=>'Ordinárias e extraordinárias',
'destaque_2_titulo'=>'Participação','destaque_2_valor'=>'Aberta aos cidadãos',
'destaque_3_titulo'=>'Decisões','destaque_3_valor'=>'Registadas em ata'
];
try{
 $r=$pdo->query("SELECT * FROM assembleia_funcionamento_config WHERE ativo=1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
 if($r)$cfg=array_merge($cfg,$r);
}catch(Exception $e){}
$blocos=[];
try{
 $blocos=$pdo->query("SELECT * FROM assembleia_funcionamento_blocos WHERE ativo=1 ORDER BY ordem ASC,id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}
$timeline=[];$cards=[];$destaques=[];
foreach($blocos as $b){ if($b['tipo']=='timeline')$timeline[]=$b; elseif($b['tipo']=='card')$cards[]=$b; else $destaques[]=$b; }
function br($t){ return nl2br(htmlspecialchars($t ?? '')); }
?>
<style>
.func-hero{background:radial-gradient(circle at 15% 20%,rgba(212,170,0,.24),transparent 30%),linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);color:white;padding:90px 0 105px;position:relative;overflow:hidden}.func-hero:after{content:"";position:absolute;width:600px;height:600px;border-radius:50%;right:-250px;top:-280px;background:rgba(255,255,255,.07)}.func-hero .container{position:relative;z-index:2}.func-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.36);color:#F0D060;padding:9px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px}.func-hero h1{font-size:clamp(42px,5vw,72px);margin:18px 0 14px;line-height:1.02;max-width:980px}.func-hero p{max-width:850px;color:#dbeafe;font-size:20px;line-height:1.75;margin:0}
.func-shell{margin-top:-52px;position:relative;z-index:5}.func-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}.func-stat,.func-intro,.func-section{background:white;border:1px solid #e5e7eb;border-radius:30px;padding:28px;box-shadow:0 18px 50px rgba(15,23,42,.08)}.func-stat span{display:block;color:#64748b;font-weight:900;margin-bottom:8px}.func-stat strong{display:block;color:#11151B;font-size:25px;line-height:1.2}.func-intro{display:grid;grid-template-columns:.9fr 1.1fr;gap:26px;align-items:center}.func-intro h2{margin:0;color:#11151B;font-size:36px}.func-intro p,.timeline-item p,.func-card p,.func-highlight p{color:#64748b;line-height:1.75;margin:0}.func-section{margin-top:26px}.func-section h2{color:#11151B;margin:0 0 20px;font-size:34px}
.timeline{display:grid;gap:18px}.timeline-item{display:grid;grid-template-columns:72px 1fr;gap:18px;align-items:start;background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:24px;padding:20px}.timeline-icon{width:72px;height:72px;border-radius:22px;background:linear-gradient(135deg,#242A30,#11151B);color:white;display:grid;place-items:center;font-size:30px}.timeline-item h3,.func-card h3{margin:0 0 6px;color:#11151B;font-size:23px}.timeline-item small,.func-card small,.func-highlight small{display:block;color:#242A30;font-weight:900;margin-bottom:10px}.func-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.func-card{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:26px;padding:24px;transition:.22s}.func-card:hover{transform:translateY(-4px);box-shadow:0 22px 50px rgba(15,23,42,.10)}.func-card .icon{width:64px;height:64px;border-radius:20px;display:grid;place-items:center;background:#eef2ff;color:#242A30;font-size:30px;margin-bottom:15px}.func-highlight{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;display:grid;grid-template-columns:80px 1fr;gap:20px;align-items:center;margin-top:18px}.func-highlight .icon{width:80px;height:80px;border-radius:26px;background:rgba(255,255,255,.14);display:grid;place-items:center;font-size:36px}.func-highlight h3{margin:0 0 8px;font-size:27px}.func-highlight small{color:#F0D060}.func-highlight p{color:#dbeafe}
@media(max-width:1050px){.func-stats,.func-grid,.func-intro{grid-template-columns:1fr}}@media(max-width:720px){.timeline-item,.func-highlight{grid-template-columns:1fr}}
</style>
<section class="func-hero"><div class="container"><span class="func-kicker"><?= htmlspecialchars($cfg['hero_kicker']) ?></span><h1><?= htmlspecialchars($cfg['hero_titulo']) ?></h1><p><?= htmlspecialchars($cfg['hero_subtitulo']) ?></p></div></section>
<section class="section func-shell"><div class="container">
<div class="func-stats"><div class="func-stat"><span><?= htmlspecialchars($cfg['destaque_1_titulo']) ?></span><strong><?= htmlspecialchars($cfg['destaque_1_valor']) ?></strong></div><div class="func-stat"><span><?= htmlspecialchars($cfg['destaque_2_titulo']) ?></span><strong><?= htmlspecialchars($cfg['destaque_2_valor']) ?></strong></div><div class="func-stat"><span><?= htmlspecialchars($cfg['destaque_3_titulo']) ?></span><strong><?= htmlspecialchars($cfg['destaque_3_valor']) ?></strong></div></div>
<div class="func-intro"><h2><?= htmlspecialchars($cfg['intro_titulo']) ?></h2><p><?= br($cfg['intro_texto']) ?></p></div>
<?php if($timeline):?><div class="func-section"><h2>Como funciona</h2><div class="timeline"><?php foreach($timeline as $b):?><article class="timeline-item"><div class="timeline-icon"><i class="bi <?= htmlspecialchars($b['icone'] ?: 'bi-bank') ?>"></i></div><div><h3><?= htmlspecialchars($b['titulo']) ?></h3><?php if($b['subtitulo']):?><small><?= htmlspecialchars($b['subtitulo']) ?></small><?php endif;?><p><?= br($b['conteudo']) ?></p></div></article><?php endforeach;?></div></div><?php endif;?>
<?php if($cards):?><div class="func-section"><h2>Princípios de funcionamento</h2><div class="func-grid"><?php foreach($cards as $b):?><article class="func-card"><div class="icon"><i class="bi <?= htmlspecialchars($b['icone'] ?: 'bi-stars') ?>"></i></div><h3><?= htmlspecialchars($b['titulo']) ?></h3><?php if($b['subtitulo']):?><small><?= htmlspecialchars($b['subtitulo']) ?></small><?php endif;?><p><?= br($b['conteudo']) ?></p></article><?php endforeach;?></div></div><?php endif;?>
<?php if($destaques):?><div class="func-section"><h2>Transparência democrática</h2><?php foreach($destaques as $b):?><article class="func-highlight"><div class="icon"><i class="bi <?= htmlspecialchars($b['icone'] ?: 'bi-search') ?>"></i></div><div><h3><?= htmlspecialchars($b['titulo']) ?></h3><?php if($b['subtitulo']):?><small><?= htmlspecialchars($b['subtitulo']) ?></small><?php endif;?><p><?= br($b['conteudo']) ?></p></div></article><?php endforeach;?></div><?php endif;?>
</div></section>
<?php require_once "includes/footer.php"; ?>
