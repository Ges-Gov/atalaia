<?php require_once "includes/header.php"; ?>
<?php
$cfg=['hero_kicker'=>'Recursos Humanos','hero_titulo'=>'Recursos Humanos','hero_subtitulo'=>'Transparência, organização e valorização profissional ao serviço da freguesia.','intro_titulo'=>'Gestão de pessoas com transparência','intro_texto'=>'Nesta área são disponibilizadas informações e documentos relacionados com mapa de pessoal, recrutamento, concursos, avaliação, formação, organograma, regulamentos e legislação aplicável à Administração Pública.','destaque_1_titulo'=>'Documentos','destaque_1_valor'=>'Downloads públicos','destaque_2_titulo'=>'Concursos','destaque_2_valor'=>'Avisos e resultados','destaque_3_titulo'=>'Formação','destaque_3_valor'=>'Capacitação interna'];
try{$r=$pdo->query("SELECT * FROM recursos_humanos_config WHERE ativo=1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC); if($r)$cfg=array_merge($cfg,$r);}catch(Exception $e){}
$docs=[];try{$docs=$pdo->query("SELECT * FROM recursos_humanos_documentos WHERE ativo=1 ORDER BY destaque DESC, ordem ASC, data_documento DESC, titulo ASC")->fetchAll(PDO::FETCH_ASSOC);}catch(Exception $e){}
$cats=['mapa_pessoal'=>['Mapa de Pessoal','Documento com a caracterização dos postos de trabalho e número de efetivos.','<i class="bi bi-people-fill"></i>'],'recrutamento_concursos'=>['Recrutamento e Concursos','Avisos, atas, critérios de seleção e resultados de procedimentos concursais.','<i class="bi bi-megaphone-fill"></i>'],'avaliacao_siadap'=>['Plano de Gestão e Avaliação','Informações sobre SIADAP e regulamentos internos de avaliação.','<i class="bi bi-bar-chart-fill"></i>'],'plano_formacao'=>['Plano de Formação','Plano anual de capacitação e desenvolvimento dos colaboradores.','<i class="bi bi-mortarboard-fill"></i>'],'organigrama'=>['Organograma','Estrutura hierárquica e composição organizacional.','<i class="bi bi-bank2"></i>'],'transparencia_legislacao'=>['Transparência e Legislação','Regulamentos de pessoal e legislação aplicável.','<i class="bi bi-clipboard-check-fill"></i>'],'outros'=>['Outros Documentos','Documentos adicionais de recursos humanos.','<i class="bi bi-folder2-open"></i>']];

$categoriaFiltro = $_GET['categoria'] ?? '';
$emCategoria = $categoriaFiltro !== '' && isset($cats[$categoriaFiltro]);

$por=[];foreach($docs as $d){$por[$d['categoria']?:'outros'][]=$d;} function rh($t){return nl2br(htmlspecialchars($t??''));} function est($e){return ['ativo'=>'Ativo','aberto'=>'Aberto','encerrado'=>'Encerrado','arquivo'=>'Arquivo'][$e]??$e;} function ico($f){$e=strtolower(pathinfo($f??'',PATHINFO_EXTENSION)); if($e==='pdf')return '<i class="bi bi-file-earmark-pdf-fill"></i>'; if(in_array($e,['doc','docx']))return '<i class="bi bi-file-earmark-word-fill"></i>'; if(in_array($e,['xls','xlsx']))return '<i class="bi bi-file-earmark-excel-fill"></i>'; if(in_array($e,['jpg','jpeg','png','webp']))return '<i class="bi bi-file-earmark-image-fill"></i>'; return '<i class="bi bi-file-earmark-text-fill"></i>';}

function rhListaDocs(array $lista): void
{
    if (empty($lista)) {
        echo '<div class="rh-empty"><i class="bi bi-folder2-open"></i><span>Ainda não existem documentos publicados nesta área.</span></div>';
        return;
    }
    echo '<ul class="rh-lista">';
    foreach ($lista as $d) {
        $ficheiro = $d['ficheiro_guardado'] ?? '';
        $url = $ficheiro !== '' ? '/uploads/recursos-humanos/' . rawurlencode($ficheiro) : '';
        echo '<li class="rh-item">';
        echo '<div class="rh-item-icon">' . ico($ficheiro ?: ($d['ficheiro_original'] ?? '')) . '</div>';
        echo '<div class="rh-item-body"><strong>' . htmlspecialchars($d['titulo']) . '</strong><div class="rh-item-meta">';
        if (!empty($d['data_documento'])) echo '<span><i class="bi bi-calendar3"></i> ' . date('d/m/Y', strtotime($d['data_documento'])) . '</span>';
        if (!empty($d['estado']) && $d['estado'] !== 'ativo') {
            $cls = $d['estado'] === 'aberto' ? 'open' : ($d['estado'] === 'encerrado' ? 'closed' : '');
            echo '<span class="rh-estado ' . $cls . '">' . htmlspecialchars(est($d['estado'])) . '</span>';
        }
        echo '</div>';
        if (!empty($d['descricao'])) echo '<p>' . rh($d['descricao']) . '</p>';
        echo '</div>';
        if ($url !== '') {
            echo '<div class="rh-item-acoes"><a class="rh-btn" href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Ver</a>';
            echo '<a class="rh-btn rh-btn-icon" href="' . htmlspecialchars($url) . '" download title="Descarregar" aria-label="Descarregar"><i class="bi bi-download"></i></a></div>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<style>
.rh-hero{background:radial-gradient(circle at 15% 18%,rgb(var(--ouro-rgb, 240 180 41) / .25),transparent 30%),linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);color:white;padding:96px 0 112px;position:relative;overflow:hidden}
.rh-hero:after{content:"";position:absolute;right:-250px;top:-280px;width:620px;height:620px;background:rgba(255,255,255,.07);border-radius:50%}
.rh-hero .container{position:relative;z-index:2}
.rh-kicker{display:inline-flex;background:rgb(var(--ouro-rgb, 240 180 41) / .16);border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .38);color:var(--ouro-claro, #f8d77a);padding:9px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px}
.rh-hero h1{font-size:clamp(42px,5vw,76px);margin:18px 0 14px;line-height:1.02}
.rh-hero p{max-width:900px;color:#dbeafe;font-size:20px;line-height:1.75;margin:0}
.rh-shell{margin-top:-52px;position:relative;z-index:5}
.rh-stats,.rh-quick{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.rh-stat,.rh-intro,.rh-section,.rh-quick-card,.rh-panel{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:26px;box-shadow:0 14px 40px rgba(15,23,42,.07)}
.rh-stat span{display:block;color:#64748b;font-weight:900;margin-bottom:8px}
.rh-stat strong{display:block;color:#11151B;font-size:22px}
.rh-intro{display:grid;grid-template-columns:.85fr 1.15fr;gap:26px;align-items:center;margin-bottom:24px}
.rh-intro h2{margin:0;color:#11151B;font-size:32px}
.rh-intro p{color:#64748b;line-height:1.8;margin:0}
.rh-quick-card{text-decoration:none;color:inherit;transition:.22s}
.rh-quick-card:hover{transform:translateY(-3px);box-shadow:0 20px 44px rgba(15,23,42,.10)}
.rh-quick-card b{display:block;font-size:26px;margin-bottom:8px;color:var(--cor-principal)}
.rh-quick-card strong{display:block;color:#11151B;font-size:18px}
.rh-quick-card span{color:#64748b;font-size:14px}
.rh-section{margin-top:22px}
.rh-section h2{color:#11151B;margin:0;font-size:24px;display:flex;align-items:center;gap:10px}
.rh-section h2 i{color:var(--cor-principal)}
.rh-section>p{color:#64748b;margin:6px 0 16px}
.rh-panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.rh-panel-head span{color:#64748b;font-weight:700}
.rh-voltar{display:inline-flex;align-items:center;gap:6px;color:var(--cor-principal);font-weight:800;text-decoration:none;font-size:14px}
.rh-lista{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.rh-item{display:flex;align-items:center;gap:16px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;transition:.18s}
.rh-item:hover{border-color:color-mix(in srgb,var(--cor-principal) 40%,#e5e7eb);background:#fafbfc}
.rh-item-icon{flex:0 0 46px;height:46px;border-radius:12px;display:grid;place-items:center;font-size:22px;color:var(--cor-principal);background:color-mix(in srgb,var(--cor-principal) 10%,#fff)}
.rh-item-body{flex:1;min-width:0}
.rh-item-body strong{display:block;color:#11151B;font-size:16px;line-height:1.35}
.rh-item-meta{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:4px;color:#64748b;font-size:13px;font-weight:600}
.rh-item-body p{margin:6px 0 0;color:#64748b;font-size:14px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.rh-estado{background:#f1f5f9;color:#334155;border-radius:999px;padding:2px 9px;font-size:11px;font-weight:800}
.rh-estado.open{background:#dcfce7;color:#166534}
.rh-estado.closed{background:#fee2e2;color:#991b1b}
.rh-item-acoes{display:flex;gap:8px;flex:0 0 auto}
.rh-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:11px;background:var(--cor-principal);color:#fff!important;text-decoration:none;font-weight:800;font-size:14px}
.rh-btn-icon{padding:9px 11px;background:#f1f5f9;color:#334155!important}
.rh-btn:hover{filter:brightness(1.08)}
.rh-empty{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:16px;padding:18px 20px;color:#64748b;font-weight:700}
.rh-empty i{font-size:22px}
@media(max-width:1100px){.rh-stats,.rh-intro,.rh-quick{grid-template-columns:1fr 1fr}}
@media(max-width:760px){.rh-stats,.rh-intro,.rh-quick{grid-template-columns:1fr}.rh-item{flex-wrap:wrap;align-items:flex-start}.rh-item-icon{flex-basis:40px;height:40px;font-size:19px}.rh-item-acoes{width:100%;padding-left:56px}}
@media(max-width:700px){.rh-hero{padding:56px 0 84px}.rh-hero h1{font-size:clamp(28px,8vw,40px);letter-spacing:-1px}.rh-hero p{font-size:15px;line-height:1.5}.rh-panel,.rh-section{padding:18px;border-radius:20px}}
</style>
<?php if ($emCategoria): $m = $cats[$categoriaFiltro]; $lista = $por[$categoriaFiltro] ?? []; ?>
<section class="rh-hero"><div class="container"><span class="rh-kicker"><?= htmlspecialchars($cfg['hero_kicker']) ?></span><h1><?= htmlspecialchars($m[0]) ?></h1><p><?= htmlspecialchars($m[1]) ?></p></div></section>
<section class="section rh-shell"><div class="container">
    <div class="rh-panel">
        <div class="rh-panel-head">
            <span><?= count($lista) ?> documento<?= count($lista) === 1 ? '' : 's' ?></span>
            <a class="rh-voltar" href="/recursos-humanos.php"><i class="bi bi-arrow-left"></i> <?= te('recursos_humanos.navegacao.voltar') ?></a>
        </div>
        <?php rhListaDocs($lista); ?>
    </div>
</div></section>
<?php else: ?>
<section class="rh-hero"><div class="container"><span class="rh-kicker"><?= htmlspecialchars($cfg['hero_kicker']) ?></span><h1><?= htmlspecialchars($cfg['hero_titulo']) ?></h1><p><?= htmlspecialchars($cfg['hero_subtitulo']) ?></p></div></section>
<section class="section rh-shell"><div class="container">
    <div class="rh-stats"><div class="rh-stat"><span><?= htmlspecialchars($cfg['destaque_1_titulo'] ?? '') ?></span><strong><?= htmlspecialchars($cfg['destaque_1_valor'] ?? '') ?></strong></div><div class="rh-stat"><span><?= htmlspecialchars($cfg['destaque_2_titulo'] ?? '') ?></span><strong><?= htmlspecialchars($cfg['destaque_2_valor'] ?? '') ?></strong></div><div class="rh-stat"><span><?= htmlspecialchars($cfg['destaque_3_titulo'] ?? '') ?></span><strong><?= htmlspecialchars($cfg['destaque_3_valor'] ?? '') ?></strong></div></div>
    <div class="rh-intro"><h2><?= htmlspecialchars($cfg['intro_titulo']) ?></h2><p><?= rh($cfg['intro_texto']) ?></p></div>
    <div class="rh-quick"><?php foreach($cats as $k=>$m): ?><a class="rh-quick-card" href="/recursos-humanos.php?categoria=<?= urlencode($k) ?>"><b><?= $m[2] ?></b><strong><?= htmlspecialchars($m[0]) ?></strong><span><?= htmlspecialchars($m[1]) ?></span></a><?php endforeach; ?></div>
    <?php foreach($cats as $k=>$m): ?>
        <div class="rh-section" id="<?= htmlspecialchars($k) ?>"><h2><?= $m[2] ?> <?= htmlspecialchars($m[0]) ?></h2><p><?= htmlspecialchars($m[1]) ?></p><?php rhListaDocs($por[$k] ?? []); ?></div>
    <?php endforeach; ?>
</div></section>
<?php endif; ?>
<?php require_once "includes/footer.php"; ?>
