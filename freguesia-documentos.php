<?php require_once "includes/header.php"; ?>

<?php
// Filtros (opcionais) — os documentos continuam agrupados por categoria
$pesquisa  = trim($_GET['pesquisa'] ?? '');
$catFiltro = trim($_GET['categoria'] ?? '');
$anoFiltro = trim($_GET['ano'] ?? '');

$where = ["ativo = 1", "area = 'freguesia'"];
$params = [];

if ($pesquisa !== '') {
    $where[] = "(titulo LIKE ? OR descricao LIKE ?)";
    $params[] = "%$pesquisa%";
    $params[] = "%$pesquisa%";
}
if ($catFiltro !== '') {
    $where[] = "categoria = ?";
    $params[] = $catFiltro;
}
if ($anoFiltro !== '') {
    $where[] = "YEAR(data_documento) = ?";
    $params[] = $anoFiltro;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT * FROM documentos
    WHERE $whereSql
    ORDER BY categoria ASC, data_documento DESC, criado_em DESC
");
$stmt->execute($params);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalDocumentos = count($documentos);

// Agrupar por categoria (documentos sem categoria vão para "Outros")
$grupos = [];
foreach ($documentos as $d) {
    $cat = trim($d['categoria'] ?? '');
    if ($cat === '') $cat = 'Outros';
    $grupos[$cat][] = $d;
}
ksort($grupos, SORT_NATURAL | SORT_FLAG_CASE);

// Opções para os filtros (lista completa, independente do filtro atual)
$categoriasLista = $pdo->query("SELECT DISTINCT categoria FROM documentos WHERE ativo=1 AND area='freguesia' AND categoria <> '' ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN);
$anosLista = $pdo->query("SELECT DISTINCT YEAR(data_documento) AS ano FROM documentos WHERE ativo=1 AND area='freguesia' AND data_documento IS NOT NULL ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
.docs-page-premium{
    background:
        radial-gradient(circle at top left, rgba(36,42,50,.08), transparent 33%),
        linear-gradient(180deg,#f8fafc 0%,#ffffff 54%,#f7f4ef 100%);
    padding-bottom:70px;
}

.docs-hero-premium{
    background:
        radial-gradient(circle at right top, rgba(255,255,255,.12), transparent 33%),
        linear-gradient(135deg,#242A30,#11151B);
    color:white;
    padding:70px 0 120px;
    position:relative;
    overflow:hidden;
}

.docs-hero-premium::before{
    content:"";
    position:absolute;
    right:7%;
    bottom:-8px;
    width:240px;
    height:170px;
    background:rgba(255,255,255,.08);
    border-radius:26px 26px 0 0;
    transform:rotate(-4deg);
}

.docs-hero-premium::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:90px;
    background:linear-gradient(0deg,#f8fafc,transparent);
}

.docs-hero-inner{
    display:flex;
    align-items:center;
    gap:26px;
    position:relative;
    z-index:2;
}

.docs-hero-icon{
    width:88px;
    height:88px;
    border-radius:28px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    display:grid;
    place-items:center;
    font-size:42px;
    flex-shrink:0;
    backdrop-filter:blur(12px);
}

.docs-hero-premium h1{
    font-size:clamp(38px,5vw,58px);
    margin:0 0 12px;
    letter-spacing:-1.5px;
}

.docs-hero-premium p{
    margin:0;
    color:#dbeafe;
    font-size:18px;
    line-height:1.7;
    max-width:850px;
}

.docs-filter-floating{
    margin-top:-58px;
    position:relative;
    z-index:5;
}

.docs-filter-card{
    background:white;
    border-radius:28px;
    padding:24px;
    box-shadow:0 22px 55px rgba(0,0,0,.11);
    border:1px solid #eef2f7;
}

.docs-filter-form{
    display:grid;
    grid-template-columns:1.7fr 1fr 1fr auto auto;
    gap:14px;
    align-items:center;
}

.docs-input-wrap,
.docs-select-wrap{
    position:relative;
}

.docs-input-wrap span,
.docs-select-wrap span{
    position:absolute;
    left:17px;
    top:50%;
    transform:translateY(-50%);
    color:#242A30;
    font-size:18px;
    z-index:2;
}

.docs-filter-form input,
.docs-filter-form select{
    width:100%;
    height:58px;
    border:1px solid #dbe3ea;
    background:#f8fafc;
    border-radius:16px;
    padding:0 18px 0 48px;
    box-sizing:border-box;
    font-size:15px;
    color:#11151B;
}

.docs-filter-form input:focus,
.docs-filter-form select:focus{
    outline:none;
    background:white;
    border-color:#242A30;
    box-shadow:0 0 0 4px rgba(36,42,50,.10);
}

.docs-filter-btn,
.docs-clear-btn{
    height:58px;
    border-radius:16px;
    border:0;
    padding:0 24px;
    font-weight:900;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    transition:.25s;
    white-space:nowrap;
}

.docs-filter-btn{
    background:#242A30;
    color:white;
    box-shadow:0 14px 30px rgba(36,42,50,.20);
}

.docs-clear-btn{
    background:white;
    color:#11151B;
    border:1px solid #e5e7eb;
}

.docs-filter-btn:hover,
.docs-clear-btn:hover{
    transform:translateY(-3px);
}

.docs-content-wrap{
    padding-top:46px;
}

.docs-title-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    margin-bottom:24px;
}

.docs-title-row h2{
    display:flex;
    align-items:center;
    gap:12px;
    margin:0;
    color:#11151B;
    font-size:28px;
}

.docs-title-row h2 span{
    width:40px;
    height:40px;
    border-radius:14px;
    background:#eff6ff;
    display:grid;
    place-items:center;
    color:#242A30;
}

.docs-count-pill{
    background:#eff6ff;
    color:#242A30;
    border-radius:999px;
    padding:12px 18px;
    font-weight:900;
    box-shadow:0 10px 25px rgba(36,42,50,.08);
}

.docs-grid-premium{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:22px;
}

.doc-card-premium{
    background:white;
    border:1px solid #e7edf4;
    border-radius:24px;
    overflow:hidden;
    min-height:260px;
    display:flex;
    flex-direction:column;
    box-shadow:0 16px 38px rgba(0,0,0,.07);
    transition:.3s ease;
    position:relative;
}

.doc-card-premium:hover{
    transform:translateY(-7px);
    box-shadow:0 26px 60px rgba(0,0,0,.13);
}

.doc-card-bookmark{
    position:absolute;
    right:18px;
    top:18px;
    color:#8aa0b9;
    font-size:20px;
}

.doc-card-body{
    padding:28px 26px 22px;
    display:grid;
    grid-template-columns:76px 1fr;
    gap:18px;
    flex:1;
}

.doc-pdf-icon{
    width:64px;
    height:76px;
    border-radius:14px;
    background:linear-gradient(135deg,#ef4444,#dc2626);
    color:white;
    display:grid;
    place-items:center;
    font-weight:900;
    font-size:18px;
    box-shadow:0 14px 28px rgba(220,38,38,.26);
    position:relative;
}

.doc-pdf-icon::after{
    content:"";
    position:absolute;
    top:0;
    right:0;
    border-top:18px solid rgba(255,255,255,.55);
    border-left:18px solid transparent;
}

.doc-card-content{
    min-width:0;
}

.doc-category{
    display:inline-flex;
    align-items:center;
    background:#dbeafe;
    color:#242A30;
    padding:7px 11px;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    margin-bottom:12px;
}

.doc-card-content h3{
    margin:0 0 12px;
    color:#11151B;
    font-size:20px;
    line-height:1.3;
}

.doc-card-content p{
    margin:0;
    color:#52606d;
    font-size:14px;
    line-height:1.7;
}

.doc-card-footer{
    border-top:1px solid #eef2f7;
    background:#fbfdff;
    padding:14px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
}

.doc-meta-mini{
    display:flex;
    align-items:center;
    gap:14px;
    color:#52606d;
    font-size:13px;
    font-weight:800;
}

.doc-download-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#242A30;
    text-decoration:none;
    font-size:13px;
    font-weight:900;
    transition:.25s;
}

.doc-download-link:hover{
    color:#D4AA00;
}

.docs-empty{
    background:white;
    border-radius:24px;
    padding:38px;
    text-align:center;
    color:#52606d;
    font-weight:800;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
}

.docs-pagination{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
    margin-top:38px;
    flex-wrap:wrap;
}

.docs-pagination a,
.docs-pagination span{
    min-width:48px;
    height:48px;
    padding:0 14px;
    border-radius:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:white;
    border:1px solid #e5e7eb;
    color:#11151B;
    text-decoration:none;
    font-weight:900;
    box-shadow:0 10px 24px rgba(0,0,0,.06);
}

.docs-pagination a.active,
.docs-pagination a:hover{
    background:#242A30;
    color:white;
    transform:translateY(-3px);
}

.docs-showing{
    text-align:center;
    margin-top:16px;
    color:#64748b;
    font-weight:800;
}

@media(max-width:1200px){
    .docs-grid-premium{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .docs-filter-form{
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:760px){
    .docs-hero-inner{
        align-items:flex-start;
        flex-direction:column;
    }

    .docs-filter-form{
        grid-template-columns:1fr;
    }

    .docs-grid-premium{
        grid-template-columns:1fr;
    }

    .docs-title-row{
        align-items:flex-start;
        flex-direction:column;
    }

    .doc-card-body{
        grid-template-columns:64px 1fr;
        padding:22px;
    }
}
</style>

<main class="docs-page-premium">

    <section class="docs-hero-premium">
        <div class="container">
            <div class="docs-hero-inner">
                <div class="docs-hero-icon"><i class="bi bi-file-earmark-text-fill"></i></div>

                <div>
                    <h1>Documentos da Freguesia</h1>
                    <p>
                        Consulte e descarregue atas, editais, regulamentos,
                        relatórios e outros documentos publicados.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="docs-filter-floating">
        <div class="container">
            <div class="docs-filter-card">
                <form method="GET" class="docs-filter-form">
                    <div class="docs-input-wrap">
                        <span><i class="bi bi-search"></i></span>
                        <input type="text" name="pesquisa" placeholder="Pesquisar por título ou descrição..." value="<?= htmlspecialchars($pesquisa) ?>">
                    </div>

                    <div class="docs-select-wrap">
                        <span><i class="bi bi-folder2"></i></span>
                        <select name="categoria">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categoriasLista as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $catFiltro === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="docs-select-wrap">
                        <span><i class="bi bi-calendar-event-fill"></i></span>
                        <select name="ano">
                            <option value="">Todos os anos</option>
                            <?php foreach ($anosLista as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $anoFiltro == $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="docs-filter-btn" type="submit"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                    <a class="docs-clear-btn" href="freguesia-documentos.php"><i class="bi bi-arrow-counterclockwise"></i> Limpar</a>
                </form>
            </div>
        </div>
    </section>

    <section class="docs-content-wrap">
        <div class="container">

            <?php if (!empty($grupos)): ?>

                <?php foreach ($grupos as $cat => $docs): ?>

                    <div class="docs-title-row">
                        <h2><span><i class="bi bi-folder2-open"></i></span> <?= htmlspecialchars($cat) ?></h2>
                        <div class="docs-count-pill">
                            <?= count($docs) ?> documento(s)
                        </div>
                    </div>

                    <div class="docs-grid-premium" style="margin-bottom:52px;">

                        <?php foreach ($docs as $d): ?>
                            <article class="doc-card-premium">
                                <div class="doc-card-bookmark"></div>

                                <div class="doc-card-body">
                                    <div class="doc-pdf-icon">PDF</div>

                                    <div class="doc-card-content">
                                        <h3><?= htmlspecialchars($d['titulo']) ?></h3>

                                        <?php if (!empty($d['descricao'])): ?>
                                            <p><?= htmlspecialchars(mb_substr(strip_tags($d['descricao']), 0, 115)) ?><?= mb_strlen(strip_tags($d['descricao'])) > 115 ? '...' : '' ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="doc-card-footer">
                                    <div class="doc-meta-mini">
                                        <?php if (!empty($d['data_documento'])): ?>
                                            <span><i class="bi bi-calendar-event-fill"></i> <?= date('d/m/Y', strtotime($d['data_documento'])) ?></span>
                                        <?php endif; ?>

                                        <span>PDF</span>
                                    </div>

                                    <a
                                        class="doc-download-link"
                                        href="/assets/docs/<?= htmlspecialchars($d['ficheiro']) ?>"
                                        target="_blank"
                                    >
                                        <i class="bi bi-download"></i> Ver / Descarregar
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>
                <div class="docs-empty">
                    <?php if ($pesquisa !== '' || $catFiltro !== '' || $anoFiltro !== ''): ?>
                        Nenhum documento corresponde à pesquisa. <a href="freguesia-documentos.php">Limpar filtros</a>.
                    <?php else: ?>
                        Ainda não existem documentos publicados.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php require_once "includes/footer.php"; ?>
