<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$uploadDir = __DIR__ . "/../uploads/assembleia/";
$uploadUrl = "/uploads/assembleia/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

require_once "includes/documentos_ui.php";   // 20/11/2026: formulário, filtros e paginação comuns com a Freguesia
$docToken = docToken();

/* AÇÕES — antes de qualquer HTML, para o redirect funcionar. 20/11/2026: por POST com token (antes eram ligações GET) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_acao'])) {
    $id = (int)($_POST['id'] ?? 0);
    $voltarFiltros = http_build_query(array_diff_key($_GET, ['ok' => 1]));
    if (!hash_equals($docToken, (string)($_POST['token'] ?? ''))) {
        header("Location: assembleia-documentos.php?ok=expirou");
        exit;
    }
    if ($_POST['doc_acao'] === 'apagar') {
        $stmt = $pdo->prepare("SELECT ficheiro FROM assembleia_documentos WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($doc) {
            $fp = $uploadDir . basename($doc['ficheiro']);
            if (is_file($fp)) @unlink($fp);
            $pdo->prepare("DELETE FROM assembleia_documentos WHERE id = ?")->execute([$id]);
            try { $pdo->prepare("DELETE FROM assembleia_ponto_documentos WHERE documento_id = ?")->execute([$id]); } catch (Throwable $e) {}
            try { $pdo->prepare("DELETE FROM assembleia_sessao_documentos WHERE documento_id = ?")->execute([$id]); } catch (Throwable $e) {}
        }
        header("Location: assembleia-documentos.php?ok=apagado");
        exit;
    }
    if ($_POST['doc_acao'] === 'estado') {
        $pdo->prepare("UPDATE assembleia_documentos SET ativo = IF(ativo=1,0,1) WHERE id = ?")->execute([$id]);
        header("Location: assembleia-documentos.php?ok=estado" . ($voltarFiltros !== '' ? '&' . $voltarFiltros : ''));
        exit;
    }
}

$mensagem = '';
$erro = '';

if (isset($_GET['ok'])) {
    if ($_GET['ok'] === 'apagado') $mensagem = "Documento apagado com sucesso.";
    elseif ($_GET['ok'] === 'estado') $mensagem = "Estado do documento atualizado.";
    elseif ($_GET['ok'] === 'criado') $mensagem = "Documento criado com sucesso.";
    elseif ($_GET['ok'] === 'atualizado') $mensagem = "Documento atualizado com sucesso.";
    elseif ($_GET['ok'] === 'expirou') $erro = "A página esteve aberta muito tempo. Volte a tentar.";
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['doc_acao'])) {

    $id = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $dataDocumento = trim($_POST['data_documento'] ?? '');
    $ano = trim($_POST['ano'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$titulo) {
        $erro = "Escreva o título do documento.";
    } elseif (!$categoria) {
        $erro = "Escolha a categoria do documento.";
    } else {

        $ficheiroAtual = $_POST['ficheiro_atual'] ?? '';

        if (!empty($_FILES['ficheiro']['name'])) {

            // 20/11/2026: a mesma verificação da Freguesia — extensão da lista E conteúdo a condizer
            [$ext, $erro] = docVerificarFicheiro($_FILES['ficheiro'], 'varios');

            if ($erro === '') {

                $novoNome = "assembleia_" . date('YmdHis') . "_" . bin2hex(random_bytes(4)) . "." . $ext;

                if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $uploadDir . $novoNome)) {

                    if ($ficheiroAtual && is_file($uploadDir . basename($ficheiroAtual))) {
                        @unlink($uploadDir . basename($ficheiroAtual));
                    }

                    $ficheiroAtual = $novoNome;

                } else {
                    $erro = "Não foi possível guardar o ficheiro no servidor. Tente outra vez ou contacte a GesGov.";
                }
            }
        }

        if (!$erro && !$ficheiroAtual) {
            $erro = "Escolha o ficheiro do documento.";
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
<?php docEstilos(); ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $idForm ?>">
    <input type="hidden" name="ficheiro_atual" value="<?= htmlspecialchars($valor('ficheiro_atual', $editar['ficheiro'] ?? '')) ?>">

    <?php docCampos([
        'valor' => $valor,
        'categorias' => docCategorias($pdo, 'assembleia_documentos', $categoriasPadrao, (string)$valor('categoria')),
        'aceitar' => 'varios',
        'ficheiro' => ($idForm && !empty($editar['ficheiro'])) ? $uploadUrl . $editar['ficheiro'] : '',
        'extras' => ['ano' => true, 'tags' => true, 'destaque' => true],
        'ativo' => $marcadoAtivo,
        'destaque' => $marcadoDestaque,
    ]); ?>

    <button class="btn">Guardar documento</button>
    <a class="btn secondary" href="assembleia-documentos.php">Voltar</a>
</form>

<?php else: ?>

<?php docEstilos(); ?>

<div class="admin-actions">
    <a class="btn" href="assembleia-documentos.php?novo=1">Adicionar documento</a>
</div>

<?php docFiltros(['url' => 'assembleia-documentos.php', 'pesquisa' => $pesquisa, 'categoria' => $filtroCategoria, 'ano' => $filtroAno, 'estado' => $filtroEstado,
                  'categorias' => docCategorias($pdo, 'assembleia_documentos', $categoriasPadrao), 'anos' => $anosDisponiveis, 'filtrado_extra' => $filtroDestaque,
                  'extra' => '<label><input type="checkbox" name="destaque" value="1"' . ($filtroDestaque ? ' checked' : '') . '> Só em destaque</label>']); ?>
<p class="doc-contagem"><?= (int)$totalFiltrado ?> <?= (int)$totalFiltrado === 1 ? 'documento' : 'documentos' ?><?= $filtrado ? ' com estes filtros' : '' ?></p>

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
                <td><?= htmlspecialchars($d['titulo']) ?><?php if (!empty($d['destaque'])): ?> <span class="doc-destaque"><i class="bi bi-star-fill" aria-hidden="true"></i> Destaque</span><?php endif; ?></td>
                <td><?= htmlspecialchars($d['categoria']) ?></td>
                <td><?= !empty($d['data_documento']) ? date('d/m/Y', strtotime($d['data_documento'])) : (!empty($d['ano']) ? htmlspecialchars($d['ano']) : '-') ?></td>
                <td><?= $d['ativo'] ? 'Visível' : '<span class="doc-oculto">Oculto</span>' ?></td>
                <td>
                    <div class="doc-linha-acoes">
                        <a class="btn secondary" href="<?= $uploadUrl . htmlspecialchars($d['ficheiro']) ?>" target="_blank" rel="noopener">Ver</a>
                        <a class="btn secondary" href="assembleia-documentos.php?editar=<?= (int)$d['id'] ?>">Editar</a>
                        <?= docAcoesLinha(buildAdminDocsUrl([]), (int)$d['id'], (bool)$d['ativo'], $docToken) ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($docs)): ?>
            <tr><td colspan="5"><?= $filtrado ? 'Nenhum documento com estes filtros.' : 'Ainda não há documentos da Assembleia.' ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php docPaginacao($paginaAtual, $totalPaginas, fn($n) => buildAdminDocsUrl(['pagina' => $n])); ?>

<?php endif; ?>

<?php require_once "includes/footer.php"; ?>
