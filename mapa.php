<?php require_once "includes/header.php";
require_once __DIR__ . "/includes/mapa.php"; $centroMapa = centroFreguesia(); ?>

<?php
$pontos = $pdo->query("
    SELECT * FROM pontos_interesse 
    WHERE latitude IS NOT NULL 
    AND longitude IS NOT NULL
    AND latitude != ''
    AND longitude != ''
    ORDER BY nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

$economia = $pdo->query("
    SELECT * FROM comercio_local
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    AND latitude != '' AND longitude != ''
    ORDER BY nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

$assoc = $pdo->query("
    SELECT * FROM associacoes
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    AND latitude != '' AND longitude != ''
    ORDER BY nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

$eventosMapa = $pdo->query("
    SELECT * FROM eventos
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    AND latitude != '' AND longitude != ''
    AND data_evento >= NOW()
    ORDER BY data_evento ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalPontos = count($pontos) + count($economia) + count($assoc) + count($eventosMapa);

/* Filtros no topo do mapa (14/10/2026): iguais aos do mapa dos Contactos — cada categoria liga/desliga
   e a seta abre os subfiltros. [rótulo, cor, ícone]; as cores dos pinos são as mesmas. */
$tiposMapa = [
    'ponto'  => [tx('mapa.explorar.filtro_pontos'), 'var(--cor-principal)', 'bi-geo-alt-fill'],
    'assoc'  => [tx('mapa.explorar.filtro_assoc'), '#b4436c', 'bi-people-fill'],
    'eco'    => [tx('mapa.explorar.filtro_eco'), '#2563eb', 'bi-shop'],
    'evento' => [tx('mapa.explorar.filtro_eventos'), '#7c3aed', 'bi-calendar-event-fill'],
];
$subPonto = function (string $n): string {
    if (preg_match('/igreja|capela|convento|ermida|santu[aá]rio/iu', $n)) return 'Património religioso';
    if (preg_match('/museu|pelourinho|pra[cç]a de touros|anta|castelo|fonte|monumento|marco/iu', $n)) return 'Monumentos e museus';
    if (preg_match('/serra|jardim|parque|barragem|ribeira|miradouro|percurso|largo/iu', $n)) return 'Natureza e lazer';
    return 'Equipamentos';
};
$subComercio = function (string $t): string {
    if (preg_match('/aloj|casa de campo|turismo|hosped|residencial|quinta|monte/iu', $t)) return 'Alojamento';
    if (preg_match('/restaur|churrasq|tasca|pizz/iu', $t)) return 'Restaurantes';
    if (preg_match('/caf[eé]|snack|bar|pastel/iu', $t)) return 'Cafés e pastelarias';
    return 'Lojas e serviços';
};
foreach ($pontos as &$x) $x['sub'] = $subPonto((string)$x['nome']); unset($x);
foreach ($economia as &$x) $x['sub'] = $subComercio((string)($x['tipo'] ?? '')); unset($x);
foreach ($assoc as &$x) $x['sub'] = trim((string)($x['categoria'] ?? '')) ?: 'Outras'; unset($x);
foreach ($eventosMapa as &$x) $x['sub'] = trim((string)($x['categoria'] ?? '')) ?: 'Outros'; unset($x);
$nTipo = ['ponto' => count($pontos), 'assoc' => count($assoc), 'eco' => count($economia), 'evento' => count($eventosMapa)];
$subsMapa = [];
foreach (['ponto' => $pontos, 'assoc' => $assoc, 'eco' => $economia, 'evento' => $eventosMapa] as $t => $lista) {
    foreach ($lista as $x) $subsMapa[$t][$x['sub']] = ($subsMapa[$t][$x['sub']] ?? 0) + 1;
    if (!empty($subsMapa[$t])) ksort($subsMapa[$t]);
}
$heroImagem = !empty($pontos[0]['imagem'])
    ? "/assets/img/" . $pontos[0]['imagem']
    : "/assets/img/freguesia-1.jpg";
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

<style>
.mapa-page-insane{
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 34%),
        linear-gradient(180deg,#f8fafc 0%, #ffffff 54%, #f7f4ef 100%);
    padding-bottom:70px;
}

.mapa-hero-insane{
    position:relative;
    min-height:540px;
    overflow:hidden;
    display:flex;
    align-items:center;
    color:white;
    background:
        linear-gradient(90deg, rgba(17,21,28,.93), rgba(17,21,28,.52)),
        linear-gradient(0deg, rgba(0,0,0,.42), transparent 58%),
        url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat;
}

.mapa-hero-insane::before{
    content:"";
    position:absolute;
    width:620px;
    height:620px;
    border-radius:50%;
    right:-220px;
    top:-260px;
    background:rgba(255,255,255,.06);
}

.mapa-hero-insane::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:145px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}

.mapa-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1fr auto;
    gap:38px;
    align-items:center;
    padding:90px 0 135px;
}

.mapa-kicker{
    display:inline-flex;
    align-items:center;
    gap:9px;
    background:rgb(var(--ouro-rgb, 240 180 41) / .15);
    border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .42);
    color:var(--ouro-claro, #f8d77a);
    border-radius:999px;
    padding:10px 16px;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.9px;
    margin-bottom:18px;
}

.mapa-hero-insane h1{
    font-size:clamp(46px,7vw,84px);
    line-height:.98;
    margin:0 0 20px;
    letter-spacing:-2px;
}

.mapa-hero-insane p{
    color:#dbeafe;
    font-size:21px;
    line-height:1.8;
    max-width:760px;
    margin:0 0 30px;
}

.mapa-hero-actions{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
}

.mapa-hero-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:16px 24px;
    border-radius:16px;
    text-decoration:none;
    font-weight:900;
    border:0;
    cursor:pointer;
    transition:.25s ease;
}

.mapa-hero-btn.primary{
    background:var(--cor-secundaria);
    color:#11151B;
    box-shadow:0 18px 45px rgba(0,0,0,.25);
}

.mapa-hero-btn.secondary{
    background:rgba(255,255,255,.14);
    color:white;
    border:1px solid rgba(255,255,255,.25);
    backdrop-filter:blur(12px);
}

.mapa-hero-btn:hover{
    transform:translateY(-4px);
}

.mapa-hero-stat{
    min-width:190px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.20);
    backdrop-filter:blur(16px);
    border-radius:28px;
    padding:28px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.20);
}

.mapa-hero-stat strong{
    display:block;
    color:var(--ouro, #C8A02E);
    font-size:56px;
    line-height:1;
    margin-bottom:8px;
}

.mapa-hero-stat span{
    color:#dbeafe;
    font-weight:900;
}

.mapa-main{
    position:relative;
    z-index:5;
    margin-top:-78px;
}

.mapa-shell{
    background:white;
    border-radius:36px;
    padding:18px;
    box-shadow:0 28px 80px rgba(0,0,0,.14);
    border:1px solid rgba(226,232,240,.95);
    display:grid;
    grid-template-columns:390px 1fr;
    gap:18px;
    min-height:760px;
    overflow:hidden;
}

.mapa-sidebar-insane{
    background:linear-gradient(180deg,#ffffff,#f8fafc);
    border:1px solid #eef2f7;
    border-radius:28px;
    padding:22px;
    display:flex;
    flex-direction:column;
    min-height:720px;
}

.sidebar-head{
    margin-bottom:18px;
}

.sidebar-head span{
    color:#242A30;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.7px;
}

.sidebar-head h2{
    color:#11151B;
    margin:6px 0 8px;
    font-size:28px;
    letter-spacing:-.7px;
}

.sidebar-head p{
    color:#64748b;
    margin:0;
    line-height:1.6;
    font-size:14px;
}

.mapa-mfiltros{margin:0 auto 10px}
.ponto-card-insane.fora-filtro{display:none !important}

.mapa-limite-card{
    background:linear-gradient(135deg,#242A30,#11151B);
    color:white;
    border-radius:22px;
    padding:18px;
    margin-bottom:16px;
    position:relative;
    overflow:hidden;
}

.mapa-limite-card::after{
    content:"";
    position:absolute;
    right:-50px;
    top:-50px;
    width:150px;
    height:150px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
}

.mapa-limite-card strong{
    display:block;
    color:var(--ouro, #C8A02E);
    margin-bottom:5px;
}

.mapa-limite-card span{
    color:#dbeafe;
    font-size:13px;
    line-height:1.5;
}

.map-search-wrap{
    position:relative;
    margin-bottom:14px;
}

.map-search-wrap span{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:#242A30;
    font-size:18px;
}

.map-search{
    width:100%;
    height:54px;
    border-radius:18px;
    border:1px solid #dbe3ea;
    background:#f8fafc;
    padding:0 16px 0 48px;
    box-sizing:border-box;
    font-size:15px;
    color:#11151B;
}

.map-search:focus{
    outline:none;
    border-color:#242A30;
    background:white;
    box-shadow:0 0 0 4px rgba(36,42,50,.10);
}

.map-toolbar{
    display:flex;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
}

.map-tool-btn{
    flex:1;
    border:0;
    background:#eff6ff;
    color:#242A30;
    border-radius:14px;
    padding:12px;
    font-weight:900;
    cursor:pointer;
    transition:.25s;
}

.map-tool-btn:hover{
    background:#242A30;
    color:white;
    transform:translateY(-2px);
}

.map-filtros{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:16px;
}

.map-filtro-chip{
    display:inline-flex;
    align-items:center;
    gap:7px;
    border:1.5px solid #e5e7eb;
    background:white;
    color:#64748b;
    border-radius:999px;
    padding:8px 14px;
    font-weight:800;
    font-size:13px;
    cursor:pointer;
    transition:.2s;
    user-select:none;
}

.map-filtro-chip .dot{
    width:9px;
    height:9px;
    border-radius:50%;
    flex:0 0 auto;
}

.map-filtro-chip[data-tipo="ponto"] .dot{background:var(--ouro-texto, #856611)}
.map-filtro-chip[data-tipo="eco"] .dot{background:#b45309}
.map-filtro-chip[data-tipo="assoc"] .dot{background:#0f766e}
.map-filtro-chip[data-tipo="evento"] .dot{background:#7c3aed}

.map-filtro-chip.ativo{
    border-color:#242A30;
    background:#242A30;
    color:white;
}

.map-filtro-chip.ativo .dot{
    box-shadow:0 0 0 2px rgba(255,255,255,.5);
}

.lista-pontos-insane{
    display:flex;
    flex-direction:column;
    gap:12px;
    overflow:auto;
    padding-right:4px;
    max-height:500px;
}

.lista-pontos-insane::-webkit-scrollbar{
    width:8px;
}

.lista-pontos-insane::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:999px;
}

.ponto-card-insane{
    width:100%;
    border:1px solid #e5e7eb;
    background:white;
    border-radius:20px;
    padding:10px;
    display:grid;
    grid-template-columns:74px 1fr auto;
    gap:12px;
    align-items:center;
    text-align:left;
    cursor:pointer;
    transition:.25s;
    box-shadow:0 10px 26px rgba(0,0,0,.045);
}

.ponto-card-insane:hover,
.ponto-card-insane.active{
    transform:translateY(-3px);
    border-color:#242A30;
    box-shadow:0 18px 40px rgba(36,42,50,.14);
}

.ponto-card-insane img,
.mini-sem-imagem-insane{
    width:74px;
    height:64px;
    border-radius:16px;
    object-fit:cover;
    display:block;
}

.mini-sem-imagem-insane{
    background:linear-gradient(135deg,#242A30,#11151B);
    color:var(--ouro, #C8A02E);
    display:grid;
    place-items:center;
    font-weight:900;
}

.ponto-card-insane strong{
    display:block;
    color:#11151B;
    font-size:15px;
    line-height:1.25;
    margin-bottom:4px;
}

.ponto-card-insane span{
    color:#64748b;
    font-size:12px;
    line-height:1.4;
}

.card-arrow{
    color:#242A30;
    font-weight:900;
    font-size:18px;
}

.map-wrap{
    position:relative;
    border-radius:28px;
    overflow:hidden;
    min-height:720px;
    background:#e5e7eb;
}

#mapa{
    width:100%;
    height:100%;
    min-height:720px;
    z-index:1;
}

.map-overlay-top{
    position:absolute;
    left:20px;
    right:20px;
    top:20px;
    z-index:600;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    pointer-events:none;
}

.map-live-pill{
    pointer-events:auto;
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:rgba(255,255,255,.88);
    border:1px solid rgba(255,255,255,.70);
    backdrop-filter:blur(14px);
    color:#11151B;
    padding:12px 16px;
    border-radius:999px;
    font-weight:900;
    box-shadow:0 14px 34px rgba(0,0,0,.12);
}

.live-dot{
    width:10px;
    height:10px;
    border-radius:50%;
    background:#22c55e;
    box-shadow:0 0 0 6px rgba(34,197,94,.16);
}

.map-floating-actions{
    pointer-events:auto;
    display:flex;
    gap:10px;
}

.map-floating-actions button{
    border:0;
    background:rgba(17,21,28,.92);
    color:white;
    border-radius:14px;
    padding:12px 14px;
    font-weight:900;
    cursor:pointer;
    box-shadow:0 14px 34px rgba(0,0,0,.16);
}

.custom-marker{
    width:42px;
    height:42px;
    border-radius:16px 16px 16px 4px;
    background:linear-gradient(135deg,color-mix(in srgb,var(--cor-principal) 70%,#000),var(--cor-principal));
    transform:rotate(-45deg);
    border:3px solid white;
    box-shadow:0 12px 28px rgba(36,42,50,.35);
    display:grid;
    place-items:center;
}

.custom-marker.eco{background:linear-gradient(135deg,#1d4ed8,#2563eb)}
.custom-marker.assoc{background:linear-gradient(135deg,#8f2f52,#b4436c)}
.custom-marker.evento{background:linear-gradient(135deg,#6d28d9,#7c3aed)}
.custom-marker.eco span,.custom-marker.assoc span,.custom-marker.evento span{color:#fff}
.custom-marker span{
    transform:rotate(45deg);
    color:#fff;
    font-size:19px;
}

.leaflet-popup-content-wrapper{
    border-radius:24px !important;
    padding:0 !important;
    overflow:hidden;
    box-shadow:0 24px 60px rgba(0,0,0,.20) !important;
}

.leaflet-popup-content{
    margin:0 !important;
    width:310px !important;
}

.popup-pro-insane{
    background:white;
}

.popup-img{
    width:100%;
    height:170px;
    object-fit:cover;
    display:block;
}

.popup-no-img{
    height:150px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:var(--ouro, #C8A02E);
    font-weight:900;
    font-size:22px;
}

.popup-content{
    padding:18px;
}

.popup-content h3{
    margin:0 0 8px;
    color:#11151B;
    font-size:22px;
}

.popup-content small{
    display:block;
    color:#242A30;
    font-weight:900;
    margin-bottom:12px;
}

.popup-content p{
    color:#52606d;
    line-height:1.6;
    margin:0 0 15px;
    font-size:14px;
}

.popup-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.popup-actions a{
    flex:1;
    display:inline-flex;
    justify-content:center;
    text-decoration:none;
    background:#242A30;
    color:white;
    border-radius:12px;
    padding:11px 10px;
    font-weight:900;
    font-size:13px;
}

.popup-actions a.secondary{
    background:#eff6ff;
    color:#242A30;
}

.limite-popup h3{
    margin:0 0 8px;
    color:#11151B;
}

.limite-popup p{
    margin:0;
    color:#52606d;
}

.map-footer-info{
    margin-top:22px;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
}

.map-info-card{
    background:white;
    border:1px solid #eef2f7;
    border-radius:24px;
    padding:22px;
    box-shadow:0 14px 34px rgba(0,0,0,.06);
}

.map-info-card strong{
    display:block;
    color:#11151B;
    margin-bottom:8px;
    font-size:18px;
}

.map-info-card span{
    color:#64748b;
    line-height:1.7;
}

@media(max-width:1100px){
    .mapa-shell{
        grid-template-columns:1fr;
    }

    .mapa-sidebar-insane{
        min-height:auto;
    }

    .lista-pontos-insane{
        max-height:360px;
    }

    .map-footer-info{
        grid-template-columns:1fr;
    }

    .mapa-hero-inner{
        grid-template-columns:1fr;
    }

    .mapa-hero-stat{
        width:max-content;
    }
}

@media(max-width:760px){
    .mapa-hero-insane{
        min-height:400px;
    }

    .mapa-hero-inner{
        padding:50px 0 90px;
    }

    .mapa-hero-insane h1{
        font-size:clamp(28px,8vw,40px);
        letter-spacing:-1px;
    }

    .mapa-hero-insane p{
        font-size:15px;
        line-height:1.5;
        margin:0 0 20px;
    }

    .mapa-main{
        margin-top:-52px;
    }

    .mapa-shell{
        padding:12px;
        border-radius:26px;
    }

    .mapa-sidebar-insane{
        padding:16px;
        border-radius:22px;
    }

    .map-wrap,
    #mapa{
        min-height:520px;
    }

    .map-overlay-top{
        left:12px;
        right:12px;
        top:12px;
        flex-direction:column;
        align-items:flex-start;
    }

    







.mapa-hero-actions{
        align-items:center;
    }

    .mapa-hero-btn{
        width:240px;
        justify-content:center;
    }

    .mapa-hero-stat{
        width:240px;
        min-width:0;
        padding:20px 18px;
        margin:10px auto 0;
        border-radius:22px;
    }

    .mapa-hero-stat strong{
        font-size:42px;
    }

    .mapa-hero-stat span{
        font-size:15px;
    }











    
}
</style>

<main class="mapa-page-insane">

    <section class="mapa-hero-insane">
        <div class="container">
            <div class="mapa-hero-inner">
                <div>
                    <span class="mapa-kicker"><?= te('mapa.hero.kicker') ?></span>
                    <h1><?= te('mapa.hero.titulo') ?></h1>
                    <p>
                        <?= te('mapa.hero.subtitulo') ?>

                    </p>

                    <div class="mapa-hero-actions">
                        <a href="#mapa-interativo" class="mapa-hero-btn primary"><?= te('mapa.hero.botao1') ?></a>
                        <button type="button" id="modoExplorarHero" class="mapa-hero-btn secondary"><?= te('mapa.hero.botao2') ?></button>
                    </div>
                </div>

                <aside class="mapa-hero-stat">
                    <strong><?= (int)$totalPontos ?></strong>
                    <span><?= te('mapa.hero.contador') ?></span>
                </aside>
            </div>
        </div>
    </section>

    <section class="container mapa-main" id="mapa-interativo">

        <?php /* Filtros no topo do mapa (14/10/2026) — iguais aos do mapa dos Contactos */ ?>
        <div class="ct-mfiltros mapa-mfiltros" role="group" aria-label="Filtrar o mapa">
            <?php foreach ($tiposMapa as $t => [$rot, $cor, $ic]): if (empty($nTipo[$t])) continue; $subsT = $subsMapa[$t] ?? []; ?>
            <div class="ct-mfiltro" style="--c:<?= $cor ?>" data-grupo-mapa="<?= $t ?>">
                <button type="button" class="ct-mfiltro__btn" data-filtro-mapa="<?= $t ?>" aria-pressed="true">
                    <span class="ct-mfiltro__icone" aria-hidden="true"><i class="bi <?= $ic ?>"></i></span>
                    <span><?= htmlspecialchars($rot) ?></span> <b data-conta-mapa="<?= $t ?>"><?= $nTipo[$t] ?></b>
                </button>
                <?php if (count($subsT) > 1): ?>
                <button type="button" class="ct-mfiltro__mais" aria-expanded="false" aria-controls="mapaSub-<?= $t ?>" aria-label="Mais filtros de <?= htmlspecialchars($rot) ?>"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                <div class="ct-msub" id="mapaSub-<?= $t ?>" role="group" aria-label="Tipos de <?= htmlspecialchars(mb_strtolower($rot)) ?>" hidden>
                    <p class="ct-msub__titulo"><?= htmlspecialchars($rot) ?></p>
                    <?php foreach ($subsT as $s => $q): ?>
                    <label class="ct-msub__op"><input type="checkbox" data-sub-mapa="<?= $t ?>" value="<?= htmlspecialchars($s) ?>" checked><span><?= htmlspecialchars($s) ?></span><b><?= $q ?></b></label>
                    <?php endforeach; ?>
                    <div class="ct-msub__acoes">
                        <button type="button" data-sub-todos="<?= $t ?>">Todos</button>
                        <button type="button" data-sub-nenhum="<?= $t ?>">Nenhum</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <button type="button" class="ct-mfiltros__repor" data-mapa-repor hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Mostrar tudo</button>
        </div>
        <p class="ct-mfiltros__estado" data-mapa-estado aria-live="polite"></p>

        <div class="mapa-shell">

            <aside class="mapa-sidebar-insane">
                <div class="sidebar-head">
                    <span><?= te('mapa.explorar.kicker') ?></span>
                    <h2><?= te('mapa.explorar.titulo') ?></h2>
                    <p><?= te('mapa.explorar.intro') ?></p>
                </div>

                <div class="mapa-limite-card">
                    <strong><?= te('mapa.explorar.limite_titulo') ?></strong>
                    <span><?= te('mapa.explorar.limite_texto') ?></span>
                </div>

                <div class="map-search-wrap">
                    <span><i class="bi bi-search"></i></span>
                    <input 
                        type="text" 
                        id="mapSearch" 
                        placeholder="<?= te('mapa.explorar.ph_pesquisa') ?>"
                        class="map-search"
                    >
                </div>

                <div class="map-toolbar">
                    <button type="button" class="map-tool-btn" id="btnTodos"><?= te('mapa.explorar.botao_todos') ?></button>
                    <button type="button" class="map-tool-btn" id="btnLimite">Limite</button>
                    <button type="button" class="map-tool-btn" id="btnExplorar">Explorar</button>
                </div>

                <div id="listaPontos" class="lista-pontos-insane">
                    <?php foreach ($pontos as $index => $p): ?>
                        <button 
                            class="ponto-card-insane"
                            data-index="<?= $index ?>"
                            data-search="<?= strtolower(htmlspecialchars($p['nome'] . ' ' . ($p['localizacao'] ?? ''))) ?>"
                        >
                            <?php if (!empty($p['imagem'])): ?>
                                <img src="/assets/img/<?= htmlspecialchars($p['imagem']) ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                            <?php else: ?>
                                <div class="mini-sem-imagem-insane"><?= htmlspecialchars(temaConfig("logo_iniciais", "")) ?></div>
                            <?php endif; ?>

                            <div>
                                <strong><?= htmlspecialchars($p['nome']) ?></strong>
                                <span><?= htmlspecialchars($p['localizacao'] ?? nomeFreguesia()) ?></span>
                            </div>

                            <div class="card-arrow">›</div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="map-wrap">
                <div class="map-overlay-top">
                    <div class="map-live-pill">
                        <span class="live-dot"></span>
                        <?= te('mapa.mapa.estado') ?>
                    </div>

                    <div class="map-floating-actions">
                        <button type="button" id="btnCenter"><?= te('mapa.mapa.botao_centrar') ?></button>
                        <button type="button" id="btnZoom"><?= te('mapa.mapa.botao_zoom') ?></button>
                    </div>
                </div>

                <div id="mapa"></div>
            </div>

        </div>

        <div class="map-footer-info">
            <div class="map-info-card">
                <strong><i class="bi bi-geo-alt-fill"></i> <?= te('mapa.info.c1_titulo') ?></strong>
                <span><?= te('mapa.info.c1_texto') ?></span>
            </div>

            <div class="map-info-card">
                <strong><i class="bi bi-compass-fill"></i> <?= te('mapa.info.c2_titulo') ?></strong>
                <span><?= te('mapa.info.c2_texto') ?></span>
            </div>

            <div class="map-info-card">
                <strong><i class="bi bi-car-front-fill"></i> <?= te('mapa.info.c3_titulo') ?></strong>
                <span><?= te('mapa.info.c3_texto') ?></span>
            </div>
        </div>

    </section>

</main>

<script>
const pontos = <?= json_encode($pontos, JSON_UNESCAPED_UNICODE) ?>;
const economia = <?= json_encode($economia, JSON_UNESCAPED_UNICODE) ?>;
const assoc = <?= json_encode($assoc, JSON_UNESCAPED_UNICODE) ?>;
const eventosMapa = <?= json_encode($eventosMapa, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const mapa = L.map('mapa', {
        scrollWheelZoom: true,
        zoomControl: false
    }).setView([<?= $centroMapa["lat"] ?>, <?= $centroMapa["lng"] ?>], <?= $centroMapa["zoom"] ?>);

    L.control.zoom({
        position: 'bottomright'
    }).addTo(mapa);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    const customIcon = L.divIcon({
        className: '',
        html: '<div class="custom-marker"><span><i class="bi bi-geo-alt-fill"></i></span></div>',
        iconSize: [42, 42],
        iconAnchor: [21, 42],
        popupAnchor: [0, -42]
    });

    const markers = [];
    const bounds = [];
    const registados = [];   // {m, tipo, sub} — para os filtros do topo
    const registar = (mk, tipo, sub) => { registados.push({ m: mk, tipo: tipo, sub: sub || '' }); return mk; };

    // Uma layer group por categoria — permite ligar/desligar cada tipo de
    // marcador no mapa (filtro), sem afetar os outros nem a lista lateral.
    const gruposPorTipo = {
        ponto:  L.layerGroup().addTo(mapa),
        assoc:  L.layerGroup().addTo(mapa),
        eco:    L.layerGroup().addTo(mapa),
        evento: L.layerGroup().addTo(mapa)
    };

    pontos.forEach(function(ponto, index) {
        const lat = parseFloat(ponto.latitude);
        const lng = parseFloat(ponto.longitude);

        if (!lat || !lng) return;

        bounds.push([lat, lng]);

        const imagem = ponto.imagem
            ? `<img src="/assets/img/${escapeHtml(ponto.imagem)}" class="popup-img">`
            : `<div class="popup-no-img"><?= htmlspecialchars(nomeFreguesia()) ?></div>`;

        const popup = `
            <div class="popup-pro-insane">
                ${imagem}
                <div class="popup-content">
                    <h3>${escapeHtml(ponto.nome)}</h3>
                    <small><i class="bi bi-geo-alt-fill"></i> ${escapeHtml(ponto.localizacao ?? <?= json_encode(nomeFreguesia()) ?>)}</small>
                    <p>${escapeHtml((ponto.descricao ?? '').substring(0, 180))}</p>
                    <div class="popup-actions">
                        <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank">Como chegar</a>
                        <a href="/pontos.php" class="secondary">Ver pontos</a>
                    </div>
                </div>
            </div>
        `;

        const marker = registar(L.marker([lat, lng], { icon: customIcon, title: ponto.nome || "", alt: ponto.nome || "" }).addTo(gruposPorTipo.ponto).bindPopup(popup), 'ponto', ponto.sub);
        marker._indiceLista = index;
        markers[index] = marker;
    });

    const ecoIcon = L.divIcon({
        className: '',
        html: '<div class="custom-marker eco"><span><i class="bi bi-shop"></i></span></div>',
        iconSize: [42, 42], iconAnchor: [21, 42], popupAnchor: [0, -42]
    });
    const assocIcon = L.divIcon({
        className: '',
        html: '<div class="custom-marker assoc"><span><i class="bi bi-people-fill"></i></span></div>',
        iconSize: [42, 42], iconAnchor: [21, 42], popupAnchor: [0, -42]
    });

    (economia || []).forEach(function(item) {
        const lat = parseFloat(item.latitude), lng = parseFloat(item.longitude);
        if (!lat || !lng) return;
        bounds.push([lat, lng]);
        const imagem = item.imagem
            ? `<img src="/assets/img/${escapeHtml(item.imagem)}" class="popup-img">`
            : `<div class="popup-no-img">Economia Local</div>`;
        const popup = `
            <div class="popup-pro-insane">${imagem}
                <div class="popup-content">
                    <h3>${escapeHtml(item.nome)}</h3>
                    <small><i class="bi bi-shop"></i> ${escapeHtml(item.tipo || 'Economia Local')}</small>
                    <p>${escapeHtml((item.morada || '').substring(0, 160))}</p>
                    <div class="popup-actions">
                        <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank">Como chegar</a>
                        <a href="/comercio.php" class="secondary">Economia Local</a>
                    </div>
                </div>
            </div>`;
        registar(L.marker([lat, lng], { icon: ecoIcon, title: item.nome || "", alt: item.nome || "" }).addTo(gruposPorTipo.eco).bindPopup(popup), 'eco', item.sub);
    });

    (assoc || []).forEach(function(item) {
        const lat = parseFloat(item.latitude), lng = parseFloat(item.longitude);
        if (!lat || !lng) return;
        bounds.push([lat, lng]);
        const imagem = item.imagem
            ? `<img src="/assets/img/${escapeHtml(item.imagem)}" class="popup-img">`
            : `<div class="popup-no-img">Associação</div>`;
        const popup = `
            <div class="popup-pro-insane">${imagem}
                <div class="popup-content">
                    <h3>${escapeHtml(item.nome)}</h3>
                    <small><i class="bi bi-people-fill"></i> Associação</small>
                    <p>${escapeHtml((item.descricao || '').substring(0, 160))}</p>
                    <div class="popup-actions">
                        <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank">Como chegar</a>
                        <a href="/associacoes.php" class="secondary">Associações</a>
                    </div>
                </div>
            </div>`;
        registar(L.marker([lat, lng], { icon: assocIcon, title: item.nome || "", alt: item.nome || "" }).addTo(gruposPorTipo.assoc).bindPopup(popup), 'assoc', item.sub);
    });

    const eventoIcon = L.divIcon({
        className: '',
        html: '<div class="custom-marker evento"><span><i class="bi bi-calendar-event-fill"></i></span></div>',
        iconSize: [42, 42], iconAnchor: [21, 42], popupAnchor: [0, -42]
    });

    (eventosMapa || []).forEach(function(item) {
        const lat = parseFloat(item.latitude), lng = parseFloat(item.longitude);
        if (!lat || !lng) return;
        bounds.push([lat, lng]);
        const imagem = item.imagem
            ? `<img src="/assets/img/${escapeHtml(item.imagem)}" class="popup-img">`
            : `<div class="popup-no-img">Evento</div>`;
        const dataFormatada = item.data_evento
            ? new Date(item.data_evento.replace(' ', 'T')).toLocaleDateString('pt-PT', { day: '2-digit', month: 'long', year: 'numeric' })
            : '';
        const popup = `
            <div class="popup-pro-insane">${imagem}
                <div class="popup-content">
                    <h3>${escapeHtml(item.titulo)}</h3>
                    <small><i class="bi bi-calendar-event-fill"></i> ${escapeHtml(item.local || dataFormatada)}</small>
                    <p>${escapeHtml((item.descricao || '').substring(0, 160))}</p>
                    <div class="popup-actions">
                        <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank">Como chegar</a>
                        <a href="/eventos.php" class="secondary">Eventos</a>
                    </div>
                </div>
            </div>`;
        registar(L.marker([lat, lng], { icon: eventoIcon, title: item.titulo || "", alt: item.titulo || "" }).addTo(gruposPorTipo.evento).bindPopup(popup), 'evento', item.sub);
    });

    /* Filtros no topo do mapa (14/10/2026): categoria (liga/desliga) + subfiltros (tipos).
       Um local aparece se a categoria estiver ligada E o seu tipo estiver marcado. */
    (function () {
        const reduz = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
        const ligado = {}, desmarcado = {};
        Object.keys(gruposPorTipo).forEach(t => { ligado[t] = true; desmarcado[t] = {}; });
        const estadoEl = document.querySelector('[data-mapa-estado]');
        const visivel = r => ligado[r.tipo] && !desmarcado[r.tipo][r.sub];
        function aplicar(enquadrar) {
            let n = 0; const porTipo = {}, pts = [];
            registados.forEach(r => {
                const v = visivel(r), g = gruposPorTipo[r.tipo];
                if (v) { n++; porTipo[r.tipo] = (porTipo[r.tipo] || 0) + 1; pts.push(r.m.getLatLng()); }
                if (v && !g.hasLayer(r.m)) g.addLayer(r.m);
                else if (!v && g.hasLayer(r.m)) { r.m.closePopup(); g.removeLayer(r.m); }
                if (r.m._indiceLista !== undefined) {
                    const card = document.querySelector('.ponto-card-insane[data-index="' + r.m._indiceLista + '"]');
                    if (card) card.classList.toggle('fora-filtro', !v);
                }
            });
            document.querySelectorAll('[data-grupo-mapa]').forEach(g => {
                const t = g.dataset.grupoMapa, totalT = registados.filter(r => r.tipo === t).length, vis = porTipo[t] || 0;
                g.querySelector('[data-filtro-mapa]').setAttribute('aria-pressed', ligado[t] && vis ? 'true' : 'false');
                g.classList.toggle('ct-mfiltro--parcial', ligado[t] && vis > 0 && vis < totalT);
                g.querySelector('[data-conta-mapa]').textContent = ligado[t] && vis > 0 && vis < totalT ? vis + '/' + totalT : totalT;
            });
            const total = registados.length;
            document.querySelectorAll('[data-mapa-repor]').forEach(b => { b.hidden = n === total; });
            if (estadoEl) estadoEl.textContent = n === total ? '' : 'A mostrar ' + n + ' de ' + total + ' locais';
            if (enquadrar && pts.length) mapa.flyToBounds(L.latLngBounds(pts), { padding: [60, 60], maxZoom: 16, duration: reduz ? 0 : .8 });
        }
        function marcarSubs(t, valor) {
            desmarcado[t] = {};
            document.querySelectorAll('[data-sub-mapa="' + t + '"]').forEach(c => { c.checked = valor; if (!valor) desmarcado[t][c.value] = true; });
        }
        document.querySelectorAll('[data-filtro-mapa]').forEach(b => b.addEventListener('click', () => {
            const t = b.dataset.filtroMapa, on = b.getAttribute('aria-pressed') !== 'true';
            ligado[t] = on;
            if (on && registados.every(r => r.tipo !== t || desmarcado[t][r.sub])) marcarSubs(t, true);
            aplicar(true);
        }));
        document.querySelectorAll('[data-sub-mapa]').forEach(c => c.addEventListener('change', () => {
            const t = c.dataset.subMapa;
            if (c.checked) { delete desmarcado[t][c.value]; ligado[t] = true; } else desmarcado[t][c.value] = true;
            aplicar(true);
        }));
        document.querySelectorAll('[data-sub-todos], [data-sub-nenhum]').forEach(b => b.addEventListener('click', () => {
            const t = b.dataset.subTodos || b.dataset.subNenhum, todos = !!b.dataset.subTodos;
            marcarSubs(t, todos); if (todos) ligado[t] = true;
            aplicar(true);
        }));
        document.querySelectorAll('[data-mapa-repor]').forEach(b => b.addEventListener('click', () => {
            Object.keys(gruposPorTipo).forEach(t => { ligado[t] = true; marcarSubs(t, true); });
            fecharSubs(); aplicar(true);
        }));
        // painéis de subfiltros: um aberto de cada vez; fecham com Esc ou ao clicar fora
        function fecharSubs(exceto) {
            document.querySelectorAll('.ct-mfiltro__mais[aria-expanded="true"]').forEach(b => {
                if (b === exceto) return;
                b.setAttribute('aria-expanded', 'false');
                document.getElementById(b.getAttribute('aria-controls')).hidden = true;
            });
        }
        document.querySelectorAll('.ct-mfiltro__mais').forEach(b => b.addEventListener('click', e => {
            e.stopPropagation();
            const painel = document.getElementById(b.getAttribute('aria-controls')), abrir = b.getAttribute('aria-expanded') !== 'true';
            fecharSubs(b);
            b.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            painel.hidden = !abrir;
            if (abrir) {
                painel.style.marginLeft = '0px';
                const r = painel.getBoundingClientRect(), folga = 12;
                if (r.left < folga) painel.style.marginLeft = (folga - r.left) + 'px';
                else if (r.right > innerWidth - folga) painel.style.marginLeft = (innerWidth - folga - r.right) + 'px';
                const primeiro = painel.querySelector('input'); if (primeiro) primeiro.focus();
            }
        }));
        document.addEventListener('click', e => { if (!e.target.closest('.ct-msub')) fecharSubs(); });
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            const aberto = document.querySelector('.ct-mfiltro__mais[aria-expanded="true"]');
            if (aberto) { fecharSubs(); aberto.focus(); }
        });
    })();

    // Limite da freguesia: vem de assets/geo/freguesia.geojson (um ficheiro por site).
    // Antes estava aqui embutido, com centenas de coordenadas — era isso que obrigava
    // este ficheiro a ser diferente em cada freguesia.
    const limiteFreguesia = <?= geojsonFreguesiaJs() ?>;

    const limiteLayer = L.geoJSON(limiteFreguesia, {
        style: {
            color: <?= json_encode(temaPaletaMarca()['--ouro'] ?? '#C8A02E') ?>,   // linha do limite: cor de destaque da marca
            weight: 4,
            opacity: 1,
            dashArray: '10,7',
            fillColor: '#242A30',
            fillOpacity: 0.08
        }
    }).addTo(mapa);

    limiteLayer.bindPopup(`
        <div class="popup-content limite-popup">
            <h3>Limite da Freguesia</h3>
            <p><?= htmlspecialchars(localFreguesia(', ')) ?></p>
        </div>
    `);

    function fitLimite() {
        if (limiteLayer.getBounds().isValid()) mapa.fitBounds(limiteLayer.getBounds(), {
            padding: [35, 35]
        });
    }

    fitLimite();
    limiteLayer.bringToFront();

    document.querySelectorAll(".ponto-card-insane").forEach(function(card) {
        card.addEventListener("click", function () {
            const index = parseInt(card.dataset.index);

            if (markers[index]) {
                mapa.setView(markers[index].getLatLng(), 16, {
                    animate: true,
                    duration: 0.7
                });
                markers[index].openPopup();
            }

            document.querySelectorAll(".ponto-card-insane").forEach(c => c.classList.remove("active"));
            card.classList.add("active");
        });
    });

    const search = document.getElementById("mapSearch");

    if (search) {
        search.addEventListener("input", function () {
            const termo = search.value.toLowerCase();
            let encontrados = 0;

            document.querySelectorAll(".ponto-card-insane").forEach(function(card) {
                const texto = card.dataset.search;
                const match = texto.includes(termo);
                card.style.display = match ? "grid" : "none";
                if (match) encontrados++;
            });
        });
    }

    function fitTodos() {
        if (bounds.length > 0) {
            mapa.fitBounds(bounds, { padding: [45, 45] });
        } else {
            fitLimite();
        }
    }

    document.getElementById("btnTodos")?.addEventListener("click", fitTodos);
    document.getElementById("btnLimite")?.addEventListener("click", fitLimite);
    document.getElementById("btnCenter")?.addEventListener("click", fitLimite);
    document.getElementById("btnZoom")?.addEventListener("click", function(){
        mapa.zoomIn();
    });

    let explorarTimer = null;

    function modoExplorar() {
        if (!markers.length) return;

        let i = 0;

        if (explorarTimer) {
            clearInterval(explorarTimer);
            explorarTimer = null;
        }

        function abrirProximo() {
            const marker = markers[i];

            if (marker) {
                mapa.setView(marker.getLatLng(), 16, {
                    animate: true,
                    duration: 0.8
                });

                marker.openPopup();

                document.querySelectorAll(".ponto-card-insane").forEach(c => c.classList.remove("active"));

                const card = document.querySelector(`.ponto-card-insane[data-index="${i}"]`);
                if (card) {
                    card.classList.add("active");
                    card.scrollIntoView({ behavior: "smooth", block: "nearest" });
                }
            }

            i++;
            if (i >= markers.length) i = 0;
        }

        abrirProximo();
        explorarTimer = setInterval(abrirProximo, 4500);
    }

    document.getElementById("btnExplorar")?.addEventListener("click", modoExplorar);
    document.getElementById("modoExplorarHero")?.addEventListener("click", function(){
        document.getElementById("mapa-interativo")?.scrollIntoView({ behavior: "smooth" });
        setTimeout(modoExplorar, 700);
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
