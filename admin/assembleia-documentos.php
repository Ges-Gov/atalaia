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

$mensagem = '';
$erro = '';

if (isset($_GET['ok'])) {
    if ($_GET['ok'] === 'apagado') $mensagem = "Documento apagado com sucesso.";
    elseif ($_GET['ok'] === 'estado') $mensagem = "Estado do documento atualizado.";
    elseif ($_GET['ok'] === 'criado') $mensagem = "Documento criado com sucesso.";
    elseif ($_GET['ok'] === 'atualizado') $mensagem = "Documento atualizado com sucesso.";
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

                header("Location: assembleia-documentos.php?ok=atualizado");
                exit;

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

                header("Location: assembleia-documentos.php?ok=criado");
                exit;
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
/* Formulário (novo / editar / erro ao gravar) ou lista — como em Documentos da Freguesia */
$modoForm = isset($_GET['novo']) || $editar || ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro);
$valor = function ($campo, $padrao = '') use ($editar) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') return $_POST[$campo] ?? $padrao;
    return $editar[$campo] ?? $padrao;
};
$idForm = $_SERVER['REQUEST_METHOD'] === 'POST' ? (int)($_POST['id'] ?? 0) : (int)($editar['id'] ?? 0);
$filtrado = $pesquisa !== '' || $filtroCategoria !== '' || $filtroAno !== '' || $filtroEstado !== '' || $filtroDestaque;

$adminPageTitle = $modoForm ? ($idForm ? "Editar documento da Assembleia" : "Adicionar documento da Assembleia") : "Documentos da Assembleia";
$adminActive = "assembleia_documentos";
require_once "includes/header.php";
?>

<?php if ($mensagem): ?>
    <div class="admin-ok" style="background:#dcfce7;color:#166534;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($mensagem) ?>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<?php if ($modoForm): ?>

<?php
$marcadoAtivo = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['ativo']) : (!isset($editar['ativo']) || !empty($editar['ativo']));
$marcadoDestaque = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['destaque']) : !empty($editar['destaque']);
?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $idForm ?>">
    <input type="hidden" name="ficheiro_atual" value="<?= htmlspecialchars($valor('ficheiro_atual', $editar['ficheiro'] ?? '')) ?>">

    <input type="text" name="titulo" placeholder="Título do documento" value="<?= htmlspecialchars($valor('titulo')) ?>" required>

    <textarea name="descricao" placeholder="Descrição breve"><?= htmlspecialchars($valor('descricao')) ?></textarea>

    <label>Categoria</label>
    <select name="categoria" required>
        <option value="">Escolher categoria</option>
        <?php foreach ($categoriasPadrao as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $valor('categoria') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Data do documento</label>
    <input type="date" name="data_documento" value="<?= htmlspecialchars($valor('data_documento')) ?>">

    <label>Ano <small style="font-weight:400;color:#64748b;">(se ficar vazio, usa o ano da data do documento)</small></label>
    <input type="number" name="ano" min="1900" max="2100" value="<?= htmlspecialchars($valor('ano', $idForm ? '' : date('Y'))) ?>">

    <label>Palavras-chave <small style="font-weight:400;color:#64748b;">(opcional — ajudam na pesquisa, separadas por vírgulas)</small></label>
    <input type="text" name="tags" placeholder="Ex.: orçamento, reunião, edital" value="<?= htmlspecialchars($valor('tags')) ?>">

    <label>Ficheiro <?php if ($idForm): ?><small style="font-weight:400;color:#64748b;">(só se quiser substituir o atual)</small><?php endif; ?></label>
    <input type="file" name="ficheiro" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.jpg,.jpeg,.png" <?= $idForm ? '' : 'required' ?>>
    <?php if ($idForm && !empty($editar['ficheiro'])): ?>
        <p style="margin:-6px 0 16px;font-size:13px;color:#64748b;">Ficheiro atual: <a href="<?= $uploadUrl . htmlspecialchars($editar['ficheiro']) ?>" target="_blank"><?= htmlspecialchars($editar['ficheiro']) ?></a></p>
    <?php endif; ?>

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
        <input type="checkbox" name="destaque" value="1" <?= $marcadoDestaque ? 'checked' : '' ?> style="width:auto;margin:0;">
        Documento em destaque
    </label>
    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" <?= $marcadoAtivo ? 'checked' : '' ?> style="width:auto;margin:0;">
        Documento ativo
    </label>

    <button class="btn">Guardar documento</button>
    <a class="btn secondary" href="assembleia-documentos.php">Voltar</a>
</form>

<?php else: ?>

<style>
/* filtros por cima da tabela (a Freguesia não os tem; a Assembleia junta muitos documentos) */
.asm-filtros{display:grid;grid-template-columns:minmax(220px,2fr) repeat(3,minmax(150px,1fr)) auto auto auto;gap:10px;margin:0 0 18px;align-items:center}
.admin-main .asm-filtros input:not([type=checkbox]),.admin-main .asm-filtros select{width:100%;height:46px;margin:0;box-sizing:border-box;min-width:0}
.admin-main .asm-filtros select{padding-right:36px;text-overflow:ellipsis}
.admin-main .asm-filtros label{display:inline-flex;align-items:center;gap:8px;margin:0;padding:0;line-height:1;font-weight:600;white-space:nowrap;cursor:pointer}
.admin-main .asm-filtros label input[type=checkbox]{width:18px;height:18px;margin:0;flex:0 0 auto}
.asm-filtros .btn{height:46px;display:inline-flex;align-items:center;margin:0}
@media(max-width:1200px){.asm-filtros{grid-template-columns:1fr 1fr 1fr}.asm-filtros input[name=pesquisa]{grid-column:1/-1}}
@media(max-width:700px){.asm-filtros{grid-template-columns:1fr}}
.asm-pagination{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:20px}
.asm-pagination a,.asm-pagination span{min-width:40px;height:40px;display:grid;place-items:center;border-radius:12px;border:1px solid #e5e7eb;text-decoration:none;font-weight:800}
.asm-pagination a.active{background:var(--cor-principal);border-color:var(--cor-principal);color:#fff}
.asm-destaque{background:var(--cor-principal);color:#fff;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
</style>

<div class="admin-actions">
    <a class="btn" href="assembleia-documentos.php?novo=1">Adicionar documento</a>
</div>

<form method="GET" class="asm-filtros" data-sem-aviso>
    <input type="text" name="pesquisa" aria-label="Pesquisar documentos" placeholder="Pesquisar documentos" value="<?= htmlspecialchars($pesquisa) ?>">
    <select name="categoria">
        <option value="">Categoria</option>
        <?php foreach ($categoriasPadrao as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="ano">
        <option value="">Ano</option>
        <?php foreach ($anosDisponiveis as $anoOpcao): ?>
            <option value="<?= htmlspecialchars($anoOpcao) ?>" <?= $filtroAno == $anoOpcao ? 'selected' : '' ?>><?= htmlspecialchars($anoOpcao) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="estado">
        <option value="">Estado</option>
        <option value="ativo" <?= $filtroEstado === 'ativo' ? 'selected' : '' ?>>Ativo</option>
        <option value="inativo" <?= $filtroEstado === 'inativo' ? 'selected' : '' ?>>Inativo</option>
    </select>
    <label><input type="checkbox" name="destaque" value="1" <?= $filtroDestaque ? 'checked' : '' ?>> Só em destaque</label>
    <button class="btn secondary" type="submit">Filtrar</button>
    <?php if ($filtrado): ?>
        <a class="btn secondary" href="assembleia-documentos.php">Limpar</a>
    <?php endif; ?>
</form>

<div class="table-box">
    <table>
        <tr>
            <th>Título</th>
            <th>Categoria</th>
            <th>Data</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($docs as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['titulo']) ?><?php if (!empty($d['destaque'])): ?> <span class="asm-destaque"><i class="bi bi-star-fill"></i> Destaque</span><?php endif; ?></td>
                <td><?= htmlspecialchars($d['categoria']) ?></td>
                <td><?= !empty($d['data_documento']) ? date('d/m/Y', strtotime($d['data_documento'])) : (!empty($d['ano']) ? htmlspecialchars($d['ano']) : '-') ?></td>
                <td><?= $d['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                    <a class="btn secondary" href="<?= $uploadUrl . htmlspecialchars($d['ficheiro']) ?>" target="_blank">Ver</a>
                    <a class="btn secondary" href="assembleia-documentos.php?editar=<?= (int)$d['id'] ?>">Editar</a>
                    <a class="btn secondary" href="<?= buildAdminDocsUrl(['toggle' => (int)$d['id']]) ?>"><?= $d['ativo'] ? 'Ocultar' : 'Mostrar' ?></a>
                    <a class="btn danger" href="<?= buildAdminDocsUrl(['apagar' => (int)$d['id']]) ?>" onclick="return confirm('Eliminar documento?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($docs)): ?>
            <tr><td colspan="5"><?= $filtrado ? 'Nenhum documento com estes filtros.' : 'Ainda não há documentos da Assembleia.' ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php if ($totalPaginas > 1): ?>
    <div class="asm-pagination">
        <?php if ($paginaAtual > 1): ?><a href="<?= buildAdminDocsUrl(['pagina' => $paginaAtual - 1]) ?>">‹</a><?php endif; ?>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <?php if ($i == 1 || $i == $totalPaginas || abs($i - $paginaAtual) <= 2): ?>
                <a class="<?= $i == $paginaAtual ? 'active' : '' ?>" href="<?= buildAdminDocsUrl(['pagina' => $i]) ?>"><?= $i ?></a>
            <?php elseif ($i == 2 || $i == $totalPaginas - 1): ?>
                <span>…</span>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($paginaAtual < $totalPaginas): ?><a href="<?= buildAdminDocsUrl(['pagina' => $paginaAtual + 1]) ?>">›</a><?php endif; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<?php require_once "includes/footer.php"; ?>
