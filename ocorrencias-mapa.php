<?php require_once "includes/header.php";
require_once __DIR__ . "/includes/mapa.php"; $centroMapa = centroFreguesia(); ?>

<?php
$pedidos = $pdo->query("
    SELECT id, codigo, categoria, assunto, mensagem, localizacao, estado, latitude, longitude, criado_em
    FROM pedidos_junta
    WHERE publico = 1
    AND latitude IS NOT NULL
    AND longitude IS NOT NULL
    AND latitude != ''
    AND longitude != ''
    ORDER BY criado_em DESC
")->fetchAll(PDO::FETCH_ASSOC);

$categorias = $pdo->query("
    SELECT DISTINCT categoria
    FROM pedidos_junta
    WHERE publico = 1
    AND latitude IS NOT NULL
    AND longitude IS NOT NULL
    AND latitude != ''
    AND longitude != ''
    ORDER BY categoria ASC
")->fetchAll(PDO::FETCH_COLUMN);

$totalOcorrencias = count($pedidos);
$totalPendentes = 0;
$totalAnalise = 0;
$totalResolvidas = 0;
$totalArquivadas = 0;

foreach ($pedidos as $pedido) {
    if (($pedido['estado'] ?? '') === 'pendente') {
        $totalPendentes++;
    } elseif (($pedido['estado'] ?? '') === 'em_analise') {
        $totalAnalise++;
    } elseif (($pedido['estado'] ?? '') === 'resolvido') {
        $totalResolvidas++;
    } elseif (($pedido['estado'] ?? '') === 'arquivado') {
        $totalArquivadas++;
    }
}

$percentagemResolvidas = $totalOcorrencias > 0 ? round(($totalResolvidas / $totalOcorrencias) * 100) : 0;
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css">

<style>
.ocorrencias-map-page{
    background:
        radial-gradient(circle at top left, rgba(212,170,0,.18), transparent 34%),
        radial-gradient(circle at bottom right, rgba(36,42,50,.12), transparent 32%),
        #f8fafc;
    padding:34px 24px 50px;
}

.ocorrencias-map-hero{
    max-width:1480px;
    margin:0 auto 24px;
    background:linear-gradient(135deg,#242A30,#11151B 62%,#071827);
    border-radius:34px;
    padding:34px;
    color:white;
    position:relative;
    overflow:hidden;
    box-shadow:0 28px 80px rgba(17,21,28,.22);
}

.ocorrencias-map-hero::before{
    content:"";
    position:absolute;
    width:520px;
    height:520px;
    border-radius:50%;
    right:-210px;
    top:-260px;
    background:rgba(255,255,255,.075);
}

.ocorrencias-map-hero::after{
    content:"";
    position:absolute;
    width:260px;
    height:260px;
    border-radius:50%;
    left:42%;
    bottom:-150px;
    background:rgba(212,170,0,.16);
    filter:blur(2px);
}

.ocorrencias-hero-inner{
    display:grid;
    grid-template-columns:1.1fr .9fr;
    gap:34px;
    align-items:center;
    position:relative;
    z-index:2;
}

.ocorrencias-kicker{
    display:inline-flex;
    align-items:center;
    gap:9px;
    background:rgba(212,170,0,.15);
    border:1px solid rgba(212,170,0,.35);
    color:#F0D060;
    padding:8px 13px;
    border-radius:999px;
    font-size:13px;
    font-weight:900;
    letter-spacing:.7px;
    text-transform:uppercase;
}

.ocorrencias-map-hero h1{
    margin:18px 0 14px;
    font-size:clamp(36px,5vw,66px);
    line-height:1.02;
    letter-spacing:-1.5px;
}

.ocorrencias-map-hero p{
    margin:0;
    max-width:760px;
    color:#dbeafe;
    font-size:18px;
    line-height:1.8;
}

.ocorrencias-hero-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:24px;
}

.ocorrencias-hero-actions .btn,
.ocorrencias-sidebar .btn,
.popup-pro .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    border:0;
    text-decoration:none;
    cursor:pointer;
    font-weight:900;
    border-radius:15px;
    padding:13px 18px;
    background:var(--cor-secundaria,#D4AA00);
    color:#11151B;
    box-shadow:0 14px 32px rgba(0,0,0,.18);
    transition:.25s ease;
}

.ocorrencias-hero-actions .btn:hover,
.ocorrencias-sidebar .btn:hover,
.popup-pro .btn:hover{
    transform:translateY(-3px);
    filter:brightness(1.04);
}

.ocorrencias-hero-actions .btn.secondary,
.ocorrencias-sidebar .btn.secondary{
    background:#eef2f7;
    color:#11151B;
    box-shadow:none;
}

.ocorrencias-hero-actions .btn.ghost{
    background:rgba(255,255,255,.12);
    color:white;
    border:1px solid rgba(255,255,255,.18);
    box-shadow:none;
}

.ocorrencias-kpi-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:14px;
}

.ocorrencias-kpi-card{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.16);
    border-radius:24px;
    padding:22px;
    backdrop-filter:blur(12px);
    box-shadow:0 18px 45px rgba(0,0,0,.10);
}

.ocorrencias-kpi-card span{
    display:flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    border-radius:14px;
    background:rgba(255,255,255,.14);
    font-size:22px;
    margin-bottom:12px;
}

.ocorrencias-kpi-card strong{
    display:block;
    font-size:40px;
    line-height:1;
    color:white;
}

.ocorrencias-kpi-card small{
    display:block;
    margin-top:8px;
    color:#dbeafe;
    font-weight:800;
}

.ocorrencias-map-shell{
    max-width:1480px;
    margin:0 auto;
    display:grid;
    grid-template-columns:410px 1fr;
    min-height:790px;
    border-radius:34px;
    overflow:hidden;
    box-shadow:0 30px 90px rgba(17,21,28,.18);
    border:1px solid rgba(226,232,240,.9);
    background:white;
}

.ocorrencias-sidebar{
    background:linear-gradient(180deg,#ffffff,#f8fafc);
    padding:26px;
    border-right:1px solid #e5e7eb;
    overflow-y:auto;
    z-index:5;
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 transparent;
}

.ocorrencias-sidebar::-webkit-scrollbar,
.ocorrencias-list::-webkit-scrollbar{
    width:8px;
}

.ocorrencias-sidebar::-webkit-scrollbar-thumb,
.ocorrencias-list::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:999px;
}

.ocorrencias-sidebar-head{
    background:#11151B;
    color:white;
    border-radius:24px;
    padding:22px;
    margin-bottom:18px;
    position:relative;
    overflow:hidden;
}

.ocorrencias-sidebar-head::after{
    content:"";
    position:absolute;
    width:160px;
    height:160px;
    border-radius:50%;
    right:-80px;
    top:-80px;
    background:rgba(212,170,0,.20);
}

.ocorrencias-sidebar h1{
    margin:0 0 8px;
    color:white;
    font-size:27px;
    line-height:1.12;
    position:relative;
    z-index:2;
}

.ocorrencias-sidebar-head p{
    margin:0;
    color:#dbeafe;
    line-height:1.55;
    position:relative;
    z-index:2;
}

.ocorrencias-legenda{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:9px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:13px;
    margin-bottom:16px;
}

.ocorrencias-legenda span,
.map-legend span{
    display:flex;
    align-items:center;
    gap:7px;
    font-weight:900;
    font-size:13px;
    color:#334155;
}

.ocorrencias-sidebar .btn{
    width:100%;
    margin-bottom:18px;
}

.ocorrencias-filter-box{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:22px;
    padding:18px;
    box-shadow:0 12px 32px rgba(15,23,42,.06);
}

.ocorrencias-sidebar h2{
    color:#11151B;
    font-size:20px;
    margin:0 0 14px;
}

.ocorrencias-sidebar input,
.ocorrencias-sidebar select{
    width:100%;
    height:48px;
    border:1px solid #dbe4ee;
    border-radius:14px;
    padding:0 14px;
    margin-bottom:11px;
    background:#f8fafc;
    color:#11151B;
    font-weight:800;
    outline:none;
    transition:.2s ease;
    box-sizing:border-box;
}

.ocorrencias-sidebar input:focus,
.ocorrencias-sidebar select:focus{
    background:white;
    border-color:var(--cor-principal,#242A30);
    box-shadow:0 0 0 4px rgba(36,42,50,.10);
}

.ocorrencias-sidebar-list-title{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:20px;
    margin-bottom:10px;
}

.ocorrencias-sidebar-list-title strong{
    color:#11151B;
    font-size:20px;
}

.ocorrencias-sidebar-list-title small{
    min-width:38px;
    height:38px;
    display:grid;
    place-items:center;
    background:var(--cor-principal,#242A30);
    color:white;
    border-radius:50%;
    font-weight:900;
}

.ocorrencias-list{
    display:grid;
    gap:10px;
    max-height:325px;
    overflow-y:auto;
    padding-right:4px;
}

.ocorrencia-item{
    width:100%;
    border:1px solid #eef2f7;
    background:white;
    border-radius:18px;
    padding:13px;
    display:grid;
    grid-template-columns:38px 1fr auto;
    gap:11px;
    text-align:left;
    cursor:pointer;
    transition:.22s ease;
    box-shadow:0 8px 20px rgba(15,23,42,.04);
}

.ocorrencia-item:hover{
    background:#f8fafc;
    transform:translateX(5px);
    border-color:#dbe4ee;
    box-shadow:0 14px 30px rgba(15,23,42,.08);
}

.ocorrencia-item-icon{
    width:32px;
    height:32px;
    border-radius:13px;
    display:grid;
    place-items:center;
    color:white;
    font-size:14px;
    font-weight:900;
    box-shadow:0 10px 20px rgba(0,0,0,.12);
}

.ocorrencia-item h3{
    margin:0;
    color:#11151B;
    font-size:15px;
    line-height:1.22;
}

.ocorrencia-item p{
    margin:5px 0 0;
    color:#64748b;
    font-size:13px;
    line-height:1.35;
}

.ocorrencia-item small{
    display:block;
    font-weight:900;
    font-size:11px;
    text-align:right;
    text-transform:uppercase;
}

.ocorrencia-item time{
    display:block;
    color:#64748b;
    font-size:12px;
    margin-top:5px;
    text-align:right;
}

.ocorrencias-map-wrap{
    position:relative;
    min-height:790px;
    background:#dbeafe;
}

#mapaOcorrencias{
    width:100%;
    height:100%;
    min-height:790px;
    z-index:1;
}

.map-legend{
    position:absolute;
    top:24px;
    right:24px;
    z-index:900;
    background:rgba(255,255,255,.94);
    backdrop-filter:blur(12px);
    padding:18px;
    border-radius:22px;
    border:1px solid rgba(226,232,240,.9);
    box-shadow:0 20px 55px rgba(15,23,42,.16);
    display:grid;
    gap:10px;
}

.map-legend strong{
    color:#11151B;
    margin-bottom:4px;
}

.mapa-floating-card{
    position:absolute;
    left:24px;
    bottom:24px;
    z-index:900;
    width:min(360px,calc(100% - 48px));
    background:rgba(17,21,28,.94);
    color:white;
    border:1px solid rgba(255,255,255,.14);
    border-radius:24px;
    padding:18px;
    box-shadow:0 24px 70px rgba(0,0,0,.22);
    backdrop-filter:blur(14px);
}

.mapa-floating-card strong{
    display:block;
    font-size:18px;
    margin-bottom:7px;
}

.mapa-floating-card p{
    margin:0;
    color:#dbeafe;
    line-height:1.6;
    font-size:14px;
}

.dot{
    width:14px;
    height:14px;
    border-radius:50%;
    display:inline-block;
    box-shadow:0 0 0 4px rgba(15,23,42,.06);
}

.dot-pendente{background:#f59f00;}
.dot-analise{background:#2563eb;}
.dot-resolvido{background:#2b8a3e;}
.dot-arquivado{background:#6b7280;}

.leaflet-popup-content-wrapper{
    border-radius:22px;
    box-shadow:0 24px 70px rgba(15,23,42,.20);
}

.leaflet-popup-content{
    margin:0;
}

.popup-pro{
    width:285px;
    overflow:hidden;
}

.popup-pro h3{
    margin:0;
    color:#11151B;
    font-size:20px;
    line-height:1.2;
}

.popup-pro small{
    display:inline-flex;
    align-items:center;
    margin:10px 0 12px;
    padding:6px 10px;
    background:#eef2f7;
    color:#242A30;
    border-radius:999px;
    font-weight:900;
}

.popup-pro p{
    margin:10px 0;
    color:#475569;
    line-height:1.55;
}

.popup-pro .popup-head{
    padding:18px 18px 8px;
}

.popup-pro .popup-body{
    padding:0 18px 18px;
}

.popup-estado{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 10px;
    border-radius:999px;
    color:white;
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
    margin-bottom:10px;
}

.popup-morada{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:11px;
    margin:10px 0;
    color:#334155;
}

.limite-popup h3{
    margin:0 0 6px;
    color:#11151B;
}

.marker-cluster-small,
.marker-cluster-medium,
.marker-cluster-large{
    background-color:rgba(36,42,50,.22)!important;
}

.marker-cluster-small div,
.marker-cluster-medium div,
.marker-cluster-large div{
    background-color:#242A30!important;
    color:white!important;
    font-weight:900!important;
}

.ocorrencias-stats-section{
    max-width:1480px;
    margin:24px auto 0;
    display:grid;
    grid-template-columns:1.15fr .85fr;
    gap:22px;
}

.ocorrencias-stat-panel{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:30px;
    padding:26px;
    box-shadow:0 18px 50px rgba(15,23,42,.08);
}

.ocorrencias-stat-panel h2{
    margin:0 0 16px;
    color:#11151B;
}

.ocorrencias-progress-row{
    margin-bottom:16px;
}

.ocorrencias-progress-label{
    display:flex;
    justify-content:space-between;
    color:#334155;
    font-weight:900;
    margin-bottom:8px;
}

.ocorrencias-progress-track{
    height:13px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
}

.ocorrencias-progress-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#242A30,#D4AA00);
}

.ocorrencias-mini-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:12px;
}

.ocorrencias-mini-card{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:20px;
    padding:18px;
}

.ocorrencias-mini-card strong{
    display:block;
    font-size:30px;
    color:#11151B;
}

.ocorrencias-mini-card span{
    display:block;
    margin-top:5px;
    color:#64748b;
    font-weight:800;
}

@media(max-width:1100px){
    .ocorrencias-hero-inner,
    .ocorrencias-map-shell,
    .ocorrencias-stats-section{
        grid-template-columns:1fr;
    }

    .ocorrencias-map-shell{
        min-height:auto;
    }

    .ocorrencias-sidebar{
        border-right:0;
        border-bottom:1px solid #e5e7eb;
    }

    .ocorrencias-list{
        max-height:360px;
    }

    .ocorrencias-map-wrap,
    #mapaOcorrencias{
        height:640px;
        min-height:640px;
    }
}

@media(max-width:720px){
    .ocorrencias-map-page{
        padding:18px 12px 34px;
    }

    .ocorrencias-map-hero{
        padding:24px;
        border-radius:26px;
    }

    .ocorrencias-kpi-grid,
    .ocorrencias-mini-grid,
    .ocorrencias-legenda{
        grid-template-columns:1fr;
    }

    .ocorrencias-sidebar{
        padding:18px;
    }

    .map-legend{
        position:static;
        margin:14px;
        grid-template-columns:1fr 1fr;
    }

    .mapa-floating-card{
        display:none;
    }

    .ocorrencias-map-wrap,
    #mapaOcorrencias{
        height:560px;
        min-height:560px;
    }
}
</style>

<section class="ocorrencias-map-page">

    <div class="ocorrencias-map-hero">
        <div class="ocorrencias-hero-inner">
            <div>
                <span class="ocorrencias-kicker">● Centro operacional digital</span>
                <h1>Ocorrências da Freguesia em tempo real.</h1>
                <p>
                    Consulte no mapa público as ocorrências comunicadas, acompanhe estados,
                    filtre por categoria e visualize a distribuição dentro do território da freguesia.
                </p>

                <div class="ocorrencias-hero-actions">
                    <a href="/pedidos.php" class="btn">⊕ Reportar ocorrência</a>
                    <a href="#mapaOcorrencias" class="btn ghost">Ver mapa</a>
                </div>
            </div>

            <div class="ocorrencias-kpi-grid">
                <div class="ocorrencias-kpi-card">
                    <span><i class="bi bi-geo-alt"></i></span>
                    <strong><?= $totalOcorrencias ?></strong>
                    <small>Ocorrências públicas no mapa</small>
                </div>
                <div class="ocorrencias-kpi-card">
                    <span><i class="bi bi-hourglass-split"></i></span>
                    <strong><?= $totalPendentes ?></strong>
                    <small>Pendentes</small>
                </div>
                <div class="ocorrencias-kpi-card">
                    <span><i class="bi bi-search"></i></span>
                    <strong><?= $totalAnalise ?></strong>
                    <small>Em análise</small>
                </div>
                <div class="ocorrencias-kpi-card">
                    <span><i class="bi bi-check-circle-fill"></i></span>
                    <strong><?= $totalResolvidas ?></strong>
                    <small>Resolvidas</small>
                </div>
            </div>
        </div>
    </div>

    <div class="ocorrencias-map-shell">

        <aside class="ocorrencias-sidebar">
            <div class="ocorrencias-sidebar-head">
                <h1>Ocorrências no Mapa</h1>
                <p>Filtra, consulta e acompanha os pedidos públicos georreferenciados.</p>
            </div>

            <div class="ocorrencias-legenda">
                <span><i class="dot dot-pendente"></i> Pendente</span>
                <span><i class="dot dot-analise"></i> Em análise</span>
                <span><i class="dot dot-resolvido"></i> Resolvido</span>
                <span><i class="dot dot-arquivado"></i> Arquivado</span>
            </div>

            <a href="/pedidos.php" class="btn">
                ⊕ Nova Ocorrência
            </a>

            <div class="ocorrencias-filter-box">
                <h2>Filtros inteligentes</h2>

                <input type="text" id="pesquisaOcorrencias" placeholder="Pesquisar por assunto, código, local...">

                <select id="filtroCategoria">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtroEstado">
                    <option value="">Todos os estados</option>
                    <option value="pendente">Pendente</option>
                    <option value="em_analise">Em análise</option>
                    <option value="resolvido">Resolvido</option>
                    <option value="arquivado">Arquivado</option>
                </select>

                <button type="button" class="btn secondary" id="limparFiltrosMapa">
                    Limpar filtros
                </button>
            </div>

            <div class="ocorrencias-sidebar-list-title">
                <strong>Feed público</strong>
                <small id="contadorOcorrencias"><?= count($pedidos) ?></small>
            </div>

            <div class="ocorrencias-list" id="listaOcorrencias"></div>
        </aside>

        <div class="ocorrencias-map-wrap">
            <div class="map-legend">
                <strong>Legenda</strong>
                <span><i class="dot dot-pendente"></i> Pendente</span>
                <span><i class="dot dot-analise"></i> Em análise</span>
                <span><i class="dot dot-resolvido"></i> Resolvido</span>
                <span><i class="dot dot-arquivado"></i> Arquivado</span>
            </div>

            <div class="mapa-floating-card">
                <strong>Território da freguesia</strong>
                <p>O contorno vermelho assinala a zona de referência da freguesia. Clique nos pontos para consultar detalhes.</p>
            </div>

            <div id="mapaOcorrencias"></div>
        </div>

    </div>

    <div class="ocorrencias-stats-section">
        <div class="ocorrencias-stat-panel">
            <h2>Resumo operacional</h2>

            <div class="ocorrencias-progress-row">
                <div class="ocorrencias-progress-label">
                    <span>Taxa de resolução</span>
                    <span><?= $percentagemResolvidas ?>%</span>
                </div>
                <div class="ocorrencias-progress-track">
                    <div class="ocorrencias-progress-fill" style="width:<?= $percentagemResolvidas ?>%"></div>
                </div>
            </div>

            <div class="ocorrencias-mini-grid">
                <div class="ocorrencias-mini-card">
                    <strong><?= $totalPendentes ?></strong>
                    <span>Pedidos a aguardar análise</span>
                </div>
                <div class="ocorrencias-mini-card">
                    <strong><?= $totalAnalise ?></strong>
                    <span>Pedidos em tratamento</span>
                </div>
            </div>
        </div>

        <div class="ocorrencias-stat-panel">
            <h2>Transparência pública</h2>
            <div class="ocorrencias-mini-grid">
                <div class="ocorrencias-mini-card">
                    <strong><?= count($categorias) ?></strong>
                    <span>Categorias disponíveis</span>
                </div>
                <div class="ocorrencias-mini-card">
                    <strong><?= $totalArquivadas ?></strong>
                    <span>Ocorrências arquivadas</span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const ocorrencias = <?= json_encode($pedidos, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const mapa = L.map('mapaOcorrencias').setView([<?= $centroMapa["lat"] ?>, <?= $centroMapa["lng"] ?>], <?= $centroMapa["zoom"] ?>);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    const cluster = L.markerClusterGroup();
    mapa.addLayer(cluster);

    const inputPesquisa = document.getElementById('pesquisaOcorrencias');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const limparBtn = document.getElementById('limparFiltrosMapa');
    const contador = document.getElementById('contadorOcorrencias');
    const listaOcorrencias = document.getElementById('listaOcorrencias');

    let markersPorId = {};

    function corEstado(estado) {
        if (estado === 'resolvido') return '#2b8a3e';
        if (estado === 'em_analise') return '#2563eb';
        if (estado === 'arquivado') return '#6b7280';
        return '#f59f00';
    }

    function textoEstado(estado) {
        if (estado === 'resolvido') return 'Resolvido';
        if (estado === 'em_analise') return 'Em análise';
        if (estado === 'arquivado') return 'Arquivado';
        return 'Pendente';
    }

    function limparTexto(valor) {
        if (!valor) return '-';

        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizar(valor) {
        return String(valor || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function criarMarker(o) {
        const lat = parseFloat(o.latitude);
        const lng = parseFloat(o.longitude);

        if (!lat || !lng) return null;

        const marker = L.circleMarker([lat, lng], {
            radius: 10,
            color: corEstado(o.estado),
            fillColor: corEstado(o.estado),
            fillOpacity: 0.88,
            weight: 3
        });

        const morada = limparTexto(o.localizacao);
        const assunto = limparTexto(o.assunto);
        const categoria = limparTexto(o.categoria);
        const mensagem = limparTexto(o.mensagem || '').substring(0, 130);
        const urlCodigo = encodeURIComponent(o.codigo || '');

        marker.bindPopup(`
            <div class="popup-pro">
                <div class="popup-head">
                    <span class="popup-estado" style="background:${corEstado(o.estado)}">${textoEstado(o.estado)}</span>
                    <h3>${assunto}</h3>
                    <small>${categoria}</small>
                </div>

                <div class="popup-body">
                    <div class="popup-morada">
                        <strong>Morada:</strong><br>
                        ${morada}
                    </div>

                    <p>${mensagem}...</p>

                    <a href="/ocorrencia.php?codigo=${urlCodigo}" class="btn">
                        Ver detalhe
                    </a>
                </div>
            </div>
        `);

        return marker;
    }

    // Limite da freguesia: vem de assets/geo/freguesia.geojson (um ficheiro por site).
    // Antes estava aqui embutido, com centenas de coordenadas — era isso que obrigava
    // este ficheiro a ser diferente em cada freguesia.
    const limiteFreguesia = <?= geojsonFreguesiaJs() ?>;

    const limiteLayer = L.geoJSON(limiteFreguesia, {
        style: {
            color: '#ff3b30',
            weight: 4,
            opacity: 1,
            dashArray: '8,6',
            fillColor: '#ff3b30',
            fillOpacity: 0.06
        }
    }).addTo(mapa);

    limiteLayer.bindPopup(`
        <div class="popup-pro limite-popup">
            <h3>Limite da Freguesia</h3>
            <p>Atalaia e Alto Estanqueiro-Jardia, Montijo</p>
        </div>
    `);

    function renderLista(lista) {
        listaOcorrencias.innerHTML = '';

        lista.slice(0, 8).forEach(function(o) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'ocorrencia-item';

            item.innerHTML = `
                <span class="ocorrencia-item-icon" style="background:${corEstado(o.estado)}">!</span>
                <span>
                    <h3>${limparTexto(o.assunto)}</h3>
                    <p>${limparTexto(o.localizacao)}</p>
                </span>
                <span>
                    <small style="color:${corEstado(o.estado)}">${textoEstado(o.estado)}</small>
                    <time>${new Date(o.criado_em).toLocaleDateString('pt-PT')}</time>
                </span>
            `;

            item.addEventListener('click', function() {
                const marker = markersPorId[o.id];

                if (marker) {
                    mapa.setView(marker.getLatLng(), 16);
                    marker.openPopup();
                }
            });

            listaOcorrencias.appendChild(item);
        });
    }

    function aplicarFiltros() {
        cluster.clearLayers();
        markersPorId = {};

        const termo = normalizar(inputPesquisa.value);
        const estado = filtroEstado.value;
        const categoria = filtroCategoria.value;

        const bounds = [];
        const visiveis = [];

        ocorrencias.forEach(function (o) {
            const lat = parseFloat(o.latitude);
            const lng = parseFloat(o.longitude);

            if (!lat || !lng) return;

            const textoPesquisa = normalizar(
                (o.assunto || '') + ' ' +
                (o.codigo || '') + ' ' +
                (o.localizacao || '') + ' ' +
                (o.categoria || '') + ' ' +
                (o.mensagem || '')
            );

            if (termo && !textoPesquisa.includes(termo)) return;
            if (estado && o.estado !== estado) return;
            if (categoria && o.categoria !== categoria) return;

            const marker = criarMarker(o);

            if (marker) {
                cluster.addLayer(marker);
                markersPorId[o.id] = marker;
                bounds.push([lat, lng]);
                visiveis.push(o);
            }
        });

        contador.textContent = visiveis.length;
        renderLista(visiveis);

        if (bounds.length > 0) {
            mapa.fitBounds(bounds, { padding: [45, 45], maxZoom: 15 });
        } else {
            mapa.fitBounds(limiteLayer.getBounds(), { padding: [45, 45] });
        }

        limiteLayer.bringToFront();
    }

    inputPesquisa.addEventListener('input', aplicarFiltros);
    filtroEstado.addEventListener('change', aplicarFiltros);
    filtroCategoria.addEventListener('change', aplicarFiltros);

    limparBtn.addEventListener('click', function () {
        inputPesquisa.value = '';
        filtroEstado.value = '';
        filtroCategoria.value = '';
        aplicarFiltros();
    });

    aplicarFiltros();

    if (ocorrencias.length === 0) {
        mapa.fitBounds(limiteLayer.getBounds(), { padding: [45, 45] });
    }

    setTimeout(function () {
        mapa.invalidateSize();
        limiteLayer.bringToFront();
    }, 400);
});
</script>

<?php require_once "includes/footer.php"; ?>