<?php
/* Documentos da Freguesia — lista. Igual à da Assembleia (includes/documentos_ui.php, 20/11/2026): pesquisa, filtros,
   paginação, «Ocultar/Mostrar» e «Eliminar» por POST com token (antes eram ligações GET). */
$adminPageTitle = "Documentos";
$adminActive = "documentos";
require_once "includes/header.php";
require_once "includes/documentos_ui.php";

$token = docToken();
$categoriasPadrao = ['Atas', 'Editais', 'Convocatórias', 'Regulamentos', 'Planos e Orçamentos', 'Outros Documentos'];
$pastaDocs = __DIR__ . "/../assets/docs/";

/* Ações da lista (antes de qualquer HTML útil: o header.php tem buffer, o redirect funciona) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_acao'])) {
    $id = (int)($_POST['id'] ?? 0);
    if (!hash_equals($token, (string)($_POST['token'] ?? ''))) {
        header("Location: documentos.php?ok=expirou"); exit;
    }
    if ($_POST['doc_acao'] === 'estado') {
        $pdo->prepare("UPDATE documentos SET ativo = IF(ativo = 1, 0, 1), arquivado_em = IF(ativo = 1, NULL, arquivado_em) WHERE id = ?")->execute([$id]);
        header("Location: documentos.php?ok=estado" . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['ok' => 1])) : '')); exit;
    }
    if ($_POST['doc_acao'] === 'apagar') {
        $st = $pdo->prepare("SELECT ficheiro FROM documentos WHERE id = ?");
        $st->execute([$id]);
        $f = (string)$st->fetchColumn();
        if ($f !== '' && is_file($pastaDocs . basename($f))) @unlink($pastaDocs . basename($f));
        $pdo->prepare("DELETE FROM documentos WHERE id = ?")->execute([$id]);
        header("Location: documentos.php?ok=apagado"); exit;
    }
}

require_once __DIR__ . "/includes/textos_bloco.php";
textosBloco("Texto do topo da página «Documentos da Freguesia»", ['freguesia_documentos.hero.titulo','freguesia_documentos.hero.texto'], '/freguesia-documentos.php');

$mensagens = ['criado' => 'Documento guardado.', 'atualizado' => 'Documento atualizado.', 'estado' => 'Visibilidade do documento atualizada.',
              'apagado' => 'Documento eliminado.', 'expirou' => 'A página esteve aberta muito tempo. Volte a tentar.'];
$mensagem = $mensagens[$_GET['ok'] ?? ''] ?? '';

/* Filtros + paginação (iguais aos da Assembleia) */
$pesquisa = trim((string)($_GET['pesquisa'] ?? ''));
$filtroCategoria = trim((string)($_GET['categoria'] ?? ''));
$filtroAno = preg_match('/^\d{4}$/', (string)($_GET['ano'] ?? '')) ? $_GET['ano'] : '';
$filtroEstado = in_array($_GET['estado'] ?? '', ['ativo', 'inativo'], true) ? $_GET['estado'] : '';
$where = []; $params = [];
if ($pesquisa !== '') { $where[] = "(titulo LIKE ? OR descricao LIKE ? OR tags LIKE ?)"; $params[] = "%$pesquisa%"; $params[] = "%$pesquisa%"; $params[] = "%$pesquisa%"; }
if ($filtroCategoria !== '') { $where[] = "categoria = ?"; $params[] = $filtroCategoria; }
if ($filtroAno !== '') { $where[] = "YEAR(COALESCE(data_documento, criado_em)) = ?"; $params[] = (int)$filtroAno; }
if ($filtroEstado !== '') { $where[] = "ativo = ?"; $params[] = $filtroEstado === 'ativo' ? 1 : 0; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$porPagina = 20;
$st = $pdo->prepare("SELECT COUNT(*) FROM documentos $whereSql");
$st->execute($params);
$total = (int)$st->fetchColumn();
$totalPaginas = max(1, (int)ceil($total / $porPagina));
$pagina = min($totalPaginas, max(1, (int)($_GET['pagina'] ?? 1)));
$st = $pdo->prepare("SELECT * FROM documentos $whereSql ORDER BY COALESCE(data_documento, DATE(criado_em)) DESC, id DESC LIMIT $porPagina OFFSET " . (($pagina - 1) * $porPagina));
$st->execute($params);
$documentos = $st->fetchAll(PDO::FETCH_ASSOC);
$anos = $pdo->query("SELECT DISTINCT YEAR(COALESCE(data_documento, criado_em)) a FROM documentos ORDER BY a DESC")->fetchAll(PDO::FETCH_COLUMN);
$urlPagina = fn(int $n) => 'documentos.php?' . http_build_query(array_merge(array_diff_key($_GET, ['ok' => 1]), ['pagina' => $n]));
$filtrado = $pesquisa !== '' || $filtroCategoria !== '' || $filtroAno !== '' || $filtroEstado !== '';

docEstilos();
?>

<?php if ($mensagem): ?>
    <div class="admin-ok" role="status" style="background:<?= ($_GET['ok'] ?? '') === 'expirou' ? '#fee2e2;color:#991b1b' : '#dcfce7;color:#166534' ?>;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($mensagem) ?>
    </div>
<?php endif; ?>

<div class="admin-actions">
    <a class="btn" href="adicionar_documento.php">Adicionar documento</a>
</div>

<?php docFiltros(['url' => 'documentos.php', 'pesquisa' => $pesquisa, 'categoria' => $filtroCategoria, 'ano' => $filtroAno, 'estado' => $filtroEstado,
                  'categorias' => docCategorias($pdo, 'documentos', $categoriasPadrao), 'anos' => $anos]); ?>
<p class="doc-contagem"><?= $total ?> <?= $total === 1 ? 'documento' : 'documentos' ?><?= $filtrado ? ' com estes filtros' : '' ?></p>

<div class="table-box">
    <table>
        <tr>
            <th>Título</th>
            <th>Categoria</th>
            <th>Data</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($documentos as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['titulo']) ?></td>
                <td><?= htmlspecialchars($d['categoria']) ?></td>
                <td><?= !empty($d['data_documento']) ? date('d/m/Y', strtotime($d['data_documento'])) : '-' ?></td>
                <td><?= $d['ativo'] ? 'Visível' : '<span class="doc-oculto">Oculto</span>' ?></td>
                <td>
                    <div class="doc-linha-acoes">
                        <a class="btn secondary" href="../assets/docs/<?= htmlspecialchars($d['ficheiro']) ?>" target="_blank" rel="noopener">Ver</a>
                        <a class="btn secondary" href="editar_documento.php?id=<?= (int)$d['id'] ?>">Editar</a>
                        <?= docAcoesLinha('documentos.php' . ($_GET ? '?' . http_build_query(array_diff_key($_GET, ['ok' => 1])) : ''), (int)$d['id'], (bool)$d['ativo'], $token) ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($documentos)): ?>
            <tr><td colspan="5"><?= $filtrado ? 'Nenhum documento com estes filtros.' : 'Ainda não há documentos.' ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php docPaginacao($pagina, $totalPaginas, $urlPagina); ?>

<?php require_once "includes/footer.php"; ?>
