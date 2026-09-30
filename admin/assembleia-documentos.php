<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$uploadDir = __DIR__ . "/../uploads/assembleia/";
$uploadUrl = "/uploads/assembleia/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

/* AÇÕES — antes de qualquer HTML, para o redirect funcionar */
if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];

    $stmt = $pdo->prepare("SELECT ficheiro FROM assembleia_documentos WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $fp = $uploadDir . basename($doc['ficheiro']);
        if (is_file($fp)) {
            @unlink($fp);
        }
        $pdo->prepare("DELETE FROM assembleia_documentos WHERE id = ?")->execute([$id]);
    }

    header("Location: assembleia-documentos.php?ok=apagado");
    exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE assembleia_documentos SET ativo = IF(ativo=1,0,1) WHERE id = ?")->execute([$id]);
    header("Location: assembleia-documentos.php?ok=estado");
    exit;
}

$adminPageTitle = "Documentos da Assembleia";
$adminActive = "assembleia_documentos";
require_once "includes/header.php";

$mensagem = '';
$erro = '';

if (isset($_GET['ok'])) {
    if ($_GET['ok'] === 'apagado') $mensagem = "Documento apagado com sucesso.";
    elseif ($_GET['ok'] === 'estado') $mensagem = "Estado do documento atualizado.";
}

$categoriasPadrao = [
    'Atas',
    'Editais',
    'Convocatórias',
    'Deliberações',
    'Orçamentos',
    'Relatórios',
    'Regulamentos',
    'Outros'
];

/* GUARDAR / EDITAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $dataDocumento = trim($_POST['data_documento'] ?? '');
    $ano = trim($_POST['ano'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$titulo || !$categoria) {
        $erro = "Preencha o título e a categoria.";
    } else {

        $ficheiroAtual = $_POST['ficheiro_atual'] ?? '';

        if (!empty($_FILES['ficheiro']['name'])) {

            $permitidos = ['pdf','doc','docx','xls','xlsx','zip','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $permitidos)) {
                $erro = "Tipo de ficheiro não permitido.";
            } else {

                $novoNome = "assembleia_" . date('YmdHis') . "_" . bin2hex(random_bytes(4)) . "." . $ext;

                if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $uploadDir . $novoNome)) {

                    if ($ficheiroAtual && is_file($uploadDir . basename($ficheiroAtual))) {
                        @unlink($uploadDir . basename($ficheiroAtual));
                    }

                    $ficheiroAtual = $novoNome;

                } else {
                    $erro = "Erro ao enviar o ficheiro.";
                }
            }
        }

        if (!$erro && !$ficheiroAtual) {
            $erro = "Envie um ficheiro.";
        }

        if (!$erro) {

            if (!$ano && $dataDocumento) {
                $ano = date('Y', strtotime($dataDocumento));
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE assembleia_documentos 
                    SET titulo = ?, categoria = ?, descricao = ?, ficheiro = ?, data_documento = ?, ano = ?, tags = ?, destaque = ?, ativo = ?, atualizado_em = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $titulo,
                    $categoria,
                    $descricao,
                    $ficheiroAtual,
                    $dataDocumento ?: null,
                    $ano ?: null,
                    $tags,
                    $destaque,
                    $ativo,
                    $id
                ]);

                $mensagem = "Documento atualizado com sucesso.";

            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO assembleia_documentos
                    (titulo, categoria, descricao, ficheiro, data_documento, ano, tags, destaque, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $titulo,
                    $categoria,
                    $descricao,
                    $ficheiroAtual,
                    $dataDocumento ?: null,
                    $ano ?: null,
                    $tags,
                    $destaque,
                    $ativo
                ]);

                $mensagem = "Documento criado com sucesso.";
            }
        }
    }
}

/* EDITAR */
$editar = null;

if (isset($_GET['editar'])) {
    $idEditar = (int)$_GET['editar'];

    $stmt = $pdo->prepare("SELECT * FROM assembleia_documentos WHERE id = ?");
    $stmt->execute([$idEditar]);

    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* FILTROS + PAGINAÇÃO */
$porPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $porPagina;

$pesquisa = trim($_GET['pesquisa'] ?? '');
$filtroCategoria = trim($_GET['categoria'] ?? '');
$filtroAno = trim($_GET['ano'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');
$filtroDestaque = isset($_GET['destaque']) && $_GET['destaque'] === '1';

$where = [];
$params = [];

if ($pesquisa !== '') {
    $where[] = "(titulo LIKE ? OR descricao LIKE ? OR tags LIKE ?)";
    $params[] = "%{$pesquisa}%";
    $params[] = "%{$pesquisa}%";
    $params[] = "%{$pesquisa}%";
}

if ($filtroCategoria !== '') {
    $where[] = "categoria = ?";
    $params[] = $filtroCategoria;
}

if ($filtroAno !== '') {
    $where[] = "ano = ?";
    $params[] = $filtroAno;
}

if ($filtroEstado !== '') {
    $where[] = "ativo = ?";
    $params[] = $filtroEstado === 'ativo' ? 1 : 0;
}

if ($filtroDestaque) {
    $where[] = "destaque = 1";
}

$whereSql = "";
if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

$stmtTotalFiltrado = $pdo->prepare("SELECT COUNT(*) FROM assembleia_documentos $whereSql");
$stmtTotalFiltrado->execute($params);
$totalFiltrado = (int)$stmtTotalFiltrado->fetchColumn();

$totalPaginas = max(1, (int)ceil($totalFiltrado / $porPagina));

$stmtDocs = $pdo->prepare("
    SELECT *
    FROM assembleia_documentos
    $whereSql
    ORDER BY criado_em DESC
    LIMIT $porPagina OFFSET $offset
");
$stmtDocs->execute($params);
$docs = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

/* KPIS GERAIS */
$total = (int)$pdo->query("SELECT COUNT(*) FROM assembleia_documentos")->fetchColumn();
$ativos = (int)$pdo->query("SELECT COUNT(*) FROM assembleia_documentos WHERE ativo = 1")->fetchColumn();
$destaques = (int)$pdo->query("SELECT COUNT(*) FROM assembleia_documentos WHERE destaque = 1")->fetchColumn();

$anosDisponiveis = $pdo->query("
    SELECT DISTINCT ano 
    FROM assembleia_documentos 
    WHERE ano IS NOT NULL AND ano <> ''
    ORDER BY ano DESC
")->fetchAll(PDO::FETCH_COLUMN);

function buildAdminDocsUrl($overrides = []) {
    $query = $_GET;

    // Limpar ações/flags da query base ANTES de aplicar os overrides,
    // senão removeria o próprio 'apagar'/'toggle' que estamos a gerar.
    unset($query['apagar'], $query['toggle'], $query['ok']);

    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($query[$k]);
        } else {
            $query[$k] = $v;
        }
    }

    return "assembleia-documentos.php?" . http_build_query($query);
}
?>

<style>
.asm-hero{
    background:linear-gradient(135deg,#242A30,#11151B);
    color:white;
    border-radius:30px;
    padding:30px;
    margin-bottom:24px;
    box-shadow:0 22px 60px rgba(15,23,42,.18);
    display:flex;
    justify-content:space-between;
    gap:20px;
    align-items:center;
}
.asm-hero h2{margin:8px 0;font-size:34px}
.asm-hero p{margin:0;color:#dbeafe}
.asm-kicker{
    display:inline-flex;
    background:rgba(212,170,0,.16);
    border:1px solid rgba(212,170,0,.35);
    color:#F0D060;
    padding:8px 13px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    text-transform:uppercase
}
.asm-kpis{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    margin-bottom:24px
}
.asm-kpi,.asm-card{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:24px;
    padding:20px;
    box-shadow:0 14px 34px rgba(15,23,42,.06)
}
.asm-kpi strong{font-size:32px;color:#11151B;display:block}
.asm-kpi span{color:#64748b;font-weight:900}
.asm-grid{
    display:grid;
    grid-template-columns:minmax(360px,.9fr) minmax(0,1.1fr);
    gap:24px;
    align-items:start
}
.asm-card h3{margin:0 0 16px;color:#11151B;font-size:24px}
.asm-form{display:grid;gap:14px}
.asm-form label{font-weight:900;color:#11151B;font-size:13px}
.asm-form input,.asm-form select,.asm-form textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid #dbe4ee;
    background:#f8fafc;
    color:#11151B;
    border-radius:15px;
    min-height:48px;
    padding:0 14px;
    font-weight:800
}
.asm-form textarea{
    min-height:110px;
    padding:14px;
    line-height:1.6
}
.asm-checks{display:flex;gap:14px;flex-wrap:wrap}
.asm-checks label{
    display:flex;
    align-items:center;
    gap:8px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    padding:10px 12px;
    border-radius:14px
}
.asm-checks input{width:auto;min-height:auto}
.asm-actions,.asm-doc-actions{display:flex;gap:8px;flex-wrap:wrap}
.asm-alert{
    padding:14px 16px;
    border-radius:16px;
    margin-bottom:16px;
    font-weight:900
}
.asm-alert.ok{background:#dcfce7;color:#166534}
.asm-alert.err{background:#fee2e2;color:#991b1b}
/* O .table-box normal (tabela genérica) tinha aqui uma grelha rígida de
   7 colunas — texto a cortar em ecrãs normais, dentro de um cartão que já
   só tem metade da largura da página. Passa a flexível, com largura
   mínima por campo, quebrando para a linha seguinte quando não cabe. */
.asm-filtros{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:18px;
}
.asm-filtros input:not([type="checkbox"]),
.asm-filtros select{
    flex:1 1 150px;
    min-width:130px;
    height:46px;
    border:1px solid #dbe4ee;
    background:#f8fafc;
    color:#11151B;
    border-radius:14px;
    padding:0 12px;
    font-weight:800;
    box-sizing:border-box;
}
.asm-filtros input[name="pesquisa"]{
    flex:2 1 220px;
}
.asm-filtros select[name="categoria"]{
    flex-basis:180px;
}
/* Botões: sem isto esticavam para a altura da linha toda (comportamento
   padrão do flex) e ficavam com o texto desalinhado um do outro. */
.asm-filtros button,
.asm-filtros a.btn{
    flex:0 0 auto;
    align-self:center;
    height:46px;
    box-sizing:border-box;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0 18px;
}
/* Checkbox "Só destaque" — pílula com a mesma altura dos outros campos,
   substitui o dropdown Destaque/Sim/Não de antes (só interessava mostrar
   todos ou só os destacados). */
.asm-filtros label.asm-check-destaque{
    flex:0 0 auto;
    align-self:center;
    height:46px;
    box-sizing:border-box;
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:0 14px;
    border:1px solid #dbe4ee;
    background:#f8fafc;
    border-radius:14px;
    font-weight:600;
    font-size:14px;
    color:#11151B;
    margin-bottom:0;
    cursor:pointer;
    white-space:nowrap;
}
.asm-doc-list{display:grid;gap:12px}
.asm-doc-item{
    display:grid;
    grid-template-columns:52px 1fr auto;
    gap:14px;
    align-items:center;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:14px
}
.asm-doc-icon{
    width:52px;
    height:52px;
    border-radius:17px;
    background:#242A30;
    color:white;
    display:grid;
    place-items:center;
    font-weight:900
}
.asm-doc-item h4{margin:0 0 5px;color:#11151B}
.asm-doc-item p{margin:0}
.asm-badge{
    display:inline-flex;
    padding:5px 8px;
    border-radius:999px;
    background:#eef2ff;
    color:#242A30;
    font-size:11px;
    font-weight:900;
    margin-right:5px
}
.asm-badge.off{background:#fee2e2;color:#991b1b}
.asm-top-list{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-bottom:14px;
}
.asm-top-list small{
    background:#eef2ff;
    color:#242A30;
    padding:8px 12px;
    border-radius:999px;
    font-weight:900;
}
.asm-pagination{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    justify-content:center;
    margin-top:20px;
}
.asm-pagination a,
.asm-pagination span{
    min-width:40px;
    height:40px;
    display:grid;
    place-items:center;
    border-radius:14px;
    border:1px solid #e5e7eb;
    background:#f8fafc;
    color:#11151B;
    text-decoration:none;
    font-weight:900;
}
.asm-pagination a.active{
    background:#242A30;
    color:white;
}
@media(max-width:1100px){
    .asm-grid{grid-template-columns:1fr}
    .asm-kpis{grid-template-columns:1fr 1fr}
    .asm-doc-item{grid-template-columns:1fr}
}
@media(max-width:700px){
    .asm-kpis{grid-template-columns:1fr}
    .asm-filtros input,.asm-filtros select{flex-basis:100%;}
    .asm-hero{flex-direction:column;align-items:flex-start}
}
</style>

<div class="asm-hero">
    <div>
        <span class="asm-kicker">Assembleia Digital</span>
        <h2>Documentos da Assembleia</h2>
        <p>Gerir atas, editais, convocatórias, deliberações e documentos oficiais publicados no site.</p>
    </div>
    <a class="btn" href="../assembleia-documentos.php" target="_blank">Ver página pública</a>
</div>

<div class="asm-kpis">
    <div class="asm-kpi"><strong><?= (int)$total ?></strong><span>Total documentos</span></div>
    <div class="asm-kpi"><strong><?= (int)$ativos ?></strong><span>Ativos</span></div>
    <div class="asm-kpi"><strong><?= (int)$destaques ?></strong><span>Em destaque</span></div>
    <div class="asm-kpi"><strong><?= (int)$totalFiltrado ?></strong><span>Resultado filtrado</span></div>
</div>

<?php if ($mensagem): ?>
    <div class="asm-alert ok"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="asm-alert err"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="asm-grid">

    <div class="asm-card">
        <h3><?= $editar ? 'Editar documento' : 'Novo documento' ?></h3>

        <form method="POST" enctype="multipart/form-data" class="asm-form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? 0) ?>">
            <input type="hidden" name="ficheiro_atual" value="<?= htmlspecialchars($editar['ficheiro'] ?? '') ?>">

            <div>
                <label>Título *</label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($editar['titulo'] ?? '') ?>" required>
            </div>

            <div>
                <label>Categoria *</label>
                <select name="categoria" required>
                    <option value="">Escolher categoria</option>
                    <?php foreach ($categoriasPadrao as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= (($editar['categoria'] ?? '') === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Descrição</label>
                <textarea name="descricao"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea>
            </div>

            <div>
                <label>Data do documento</label>
                <input type="date" name="data_documento" value="<?= htmlspecialchars($editar['data_documento'] ?? '') ?>">
            </div>

            <div>
                <label>Ano</label>
                <input type="number" name="ano" min="1900" max="2100" value="<?= htmlspecialchars($editar['ano'] ?? date('Y')) ?>">
            </div>

            <div>
                <label>Tags</label>
                <input type="text" name="tags" placeholder="Ex: orçamento, reunião, edital" value="<?= htmlspecialchars($editar['tags'] ?? '') ?>">
            </div>

            <div>
                <label>Ficheiro <?= $editar ? '(opcional)' : '*' ?></label>
                <input type="file" name="ficheiro" <?= $editar ? '' : 'required' ?>>
                <?php if (!empty($editar['ficheiro'])): ?>
                    <small>Atual: <?= htmlspecialchars($editar['ficheiro']) ?></small>
                <?php endif; ?>
            </div>

            <div class="asm-checks">
                <label><input type="checkbox" name="destaque" <?= !empty($editar['destaque']) ? 'checked' : '' ?>> Destaque</label>
                <label><input type="checkbox" name="ativo" <?= !isset($editar['ativo']) || !empty($editar['ativo']) ? 'checked' : '' ?>> Ativo</label>
            </div>

            <div class="asm-actions">
                <button class="btn" type="submit"><?= $editar ? 'Guardar alterações' : 'Publicar documento' ?></button>
                <?php if ($editar): ?>
                    <a class="btn secondary" href="assembleia-documentos.php">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="asm-card">
        <div class="asm-top-list">
            <h3>Documentos existentes</h3>
            <small><?= (int)$totalFiltrado ?> resultado(s)</small>
        </div>

        <form method="GET" class="asm-filtros">
            <input type="text" name="pesquisa" placeholder="Pesquisar título, descrição ou tags..." value="<?= htmlspecialchars($pesquisa) ?>">

            <select name="categoria">
                <option value="">Todas categorias</option>
                <?php foreach ($categoriasPadrao as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="ano">
                <option value="">Ano</option>
                <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                    <option value="<?= htmlspecialchars($anoOpcao) ?>" <?= $filtroAno == $anoOpcao ? 'selected' : '' ?>>
                        <?= htmlspecialchars($anoOpcao) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="estado">
                <option value="">Estado</option>
                <option value="ativo" <?= $filtroEstado === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $filtroEstado === 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>

            <label class="asm-check-destaque">
                <input type="checkbox" name="destaque" value="1" <?= $filtroDestaque ? 'checked' : '' ?>>
                Só destaque
            </label>

            <button class="btn" type="submit">Filtrar</button>
            <a class="btn secondary" href="assembleia-documentos.php">Limpar</a>
        </form>

        <div class="asm-doc-list">
            <?php foreach ($docs as $d): ?>
                <?php $ext = strtoupper(pathinfo($d['ficheiro'], PATHINFO_EXTENSION)); ?>

                <div class="asm-doc-item">
                    <div class="asm-doc-icon"><?= htmlspecialchars($ext ?: 'DOC') ?></div>

                    <div>
                        <h4><?= htmlspecialchars($d['titulo']) ?></h4>
                        <p>
                            <span class="asm-badge"><?= htmlspecialchars($d['categoria']) ?></span>

                            <?php if (!empty($d['ano'])): ?>
                                <span class="asm-badge"><?= htmlspecialchars($d['ano']) ?></span>
                            <?php endif; ?>

                            <?php if (!$d['ativo']): ?>
                                <span class="asm-badge off">Inativo</span>
                            <?php endif; ?>

                            <?php if ($d['destaque']): ?>
                                <span class="asm-badge">Destaque</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="asm-doc-actions">
                        <a class="btn secondary" href="<?= $uploadUrl . htmlspecialchars($d['ficheiro']) ?>" target="_blank">Ver</a>
                        <a class="btn secondary" href="<?= buildAdminDocsUrl(['editar' => (int)$d['id']]) ?>">Editar</a>
                        <a class="btn secondary" href="<?= buildAdminDocsUrl(['toggle' => (int)$d['id']]) ?>">
                            <?= $d['ativo'] ? 'Ocultar' : 'Mostrar' ?>
                        </a>
                        <a class="btn danger" href="<?= buildAdminDocsUrl(['apagar' => (int)$d['id']]) ?>" onclick="return confirm('Apagar este documento?')">Apagar</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($docs)): ?>
                <p>Ainda não existem documentos com estes filtros.</p>
            <?php endif; ?>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <div class="asm-pagination">
                <?php if ($paginaAtual > 1): ?>
                    <a href="<?= buildAdminDocsUrl(['pagina' => $paginaAtual - 1]) ?>">‹</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPaginas || abs($i - $paginaAtual) <= 2): ?>
                        <a class="<?= $i == $paginaAtual ? 'active' : '' ?>" href="<?= buildAdminDocsUrl(['pagina' => $i]) ?>">
                            <?= $i ?>
                        </a>
                    <?php elseif ($i == 2 || $i == $totalPaginas - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <a href="<?= buildAdminDocsUrl(['pagina' => $paginaAtual + 1]) ?>">›</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php require_once "includes/footer.php"; ?>
