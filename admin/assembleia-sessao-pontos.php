<?php
/*
 * Pontos da ordem de trabalhos de uma sessão da Assembleia (20/11/2026, pedido da direção): como nas Atas da Intranet —
 * período, ordem, subpontos, proposta, documentos de cada ponto e resultado da votação (registado depois da sessão).
 * Também liga à sessão os documentos gerais (convocatória, edital, ata) e gera a convocatória/edital em PDF.
 */
$adminPageTitle = "Pontos da sessão";
$adminActive = "assembleia_sessoes";
require_once "includes/header.php";
requireAssembleiaManager();
require_once "../includes/assembleia_pontos.php";

$sessaoId = (int)($_GET['sessao'] ?? $_POST['sessao'] ?? 0);
$st = $pdo->prepare("SELECT * FROM assembleia_sessoes WHERE id = ?");
$st->execute([$sessaoId]);
$sessao = $st->fetch(PDO::FETCH_ASSOC);
if (!$sessao) {
    echo '<div class="content-box"><h2>Sessão não encontrada</h2><p>A sessão pode ter sido apagada.</p><a class="btn" href="assembleia-sessoes.php">Voltar às sessões</a></div>';
    require_once "includes/footer.php";
    exit;
}

$periodos = assembleiaPeriodos();
$resultados = assembleiaResultados();
if (empty($_SESSION['asm_token'])) $_SESSION['asm_token'] = bin2hex(random_bytes(16));
$token = $_SESSION['asm_token'];
$aviso = $_SESSION['asm_aviso'] ?? null;
unset($_SESSION['asm_aviso']);
$irPara = function (string $tipo, string $texto, string $extra = '') use ($sessaoId) {
    $_SESSION['asm_aviso'] = [$tipo, $texto];
    header('Location: assembleia-sessao-pontos.php?sessao=' . $sessaoId . $extra);
    exit;
};
$temTabela = true;
try { $pdo->query("SELECT 1 FROM assembleia_pontos LIMIT 1"); } catch (Throwable $e) { $temTabela = false; }

/* Pontos desta sessão (linhas simples), para validar pai/ordem */
$linhas = [];
if ($temTabela) {
    $stL = $pdo->prepare("SELECT * FROM assembleia_pontos WHERE sessao_id = ? ORDER BY ordem ASC, id ASC");
    $stL->execute([$sessaoId]);
    foreach ($stL->fetchAll(PDO::FETCH_ASSOC) as $l) $linhas[(int)$l['id']] = $l;
}
/* Renumera a ordem de um grupo de irmãos (mesma sessão, mesmo pai, mesmo período): 1, 2, 3… */
$renumerar = function (?int $paiId, string $periodo) use ($pdo, $sessaoId) {
    $st = $pdo->prepare("SELECT id FROM assembleia_pontos WHERE sessao_id = ? AND periodo = ? AND " . ($paiId ? "pai_id = ?" : "pai_id IS NULL") . " ORDER BY ordem ASC, id ASC");
    $st->execute($paiId ? [$sessaoId, $periodo, $paiId] : [$sessaoId, $periodo]);
    $up = $pdo->prepare("UPDATE assembleia_pontos SET ordem = ? WHERE id = ?");
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $i => $pid) $up->execute([$i + 1, $pid]);
};

$erroForm = ''; $valores = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $temTabela) {
    if (!hash_equals($token, (string)($_POST['token'] ?? ''))) $irPara('erro', 'A página esteve aberta muito tempo. Volte a tentar.');
    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'guardar_ponto') {
        $id = (int)($_POST['ponto_id'] ?? 0);
        $periodo = array_key_exists($_POST['periodo'] ?? '', $periodos) ? $_POST['periodo'] : 'OD';
        $paiId = (int)($_POST['pai_id'] ?? 0) ?: null;
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $resultado = array_key_exists($_POST['resultado'] ?? '', $resultados) ? $_POST['resultado'] : null;
        $num = fn($k) => ($v = trim((string)($_POST[$k] ?? ''))) === '' ? null : max(0, min(999, (int)$v));
        $valores = $_POST;
        if ($id && !isset($linhas[$id])) $irPara('erro', 'Esse ponto já não existe.');
        if ($paiId && (!isset($linhas[$paiId]) || !empty($linhas[$paiId]['pai_id']) || $paiId === $id)) {
            $erroForm = 'Escolha um ponto principal em «É subponto de», ou deixe «Nenhum».';
        } elseif ($paiId && $id && array_filter($linhas, fn($l) => (int)$l['pai_id'] === $id)) {
            $erroForm = 'Este ponto tem subpontos, por isso não pode passar a ser subponto de outro.';
        } elseif ($titulo === '') {
            $erroForm = 'Escreva o título do ponto (o que vai ser discutido ou votado).';
        }
        if ($erroForm === '') {
            if ($paiId) $periodo = $linhas[$paiId]['periodo'];   // o subponto fica no período do ponto principal
            $dados = [$periodo, $paiId, mb_substr($titulo, 0, 500), trim((string)($_POST['descricao'] ?? '')) ?: null,
                      mb_substr(trim((string)($_POST['proposta_ref'] ?? '')), 0, 150) ?: null, isset($_POST['tem_votacao']) ? 1 : 0,
                      $resultado, isset($_POST['unanimidade']) && $resultado ? 1 : 0, $num('votos_favor'), $num('votos_contra'), $num('votos_abstencao'),
                      trim((string)($_POST['deliberacao'] ?? '')) ?: null];
            if ($id) {
                $antes = $linhas[$id];
                $pdo->prepare("UPDATE assembleia_pontos SET periodo = ?, pai_id = ?, titulo = ?, descricao = ?, proposta_ref = ?, tem_votacao = ?,
                               resultado = ?, unanimidade = ?, votos_favor = ?, votos_contra = ?, votos_abstencao = ?, deliberacao = ?, atualizado_em = NOW()
                               WHERE id = ? AND sessao_id = ?")->execute(array_merge($dados, [$id, $sessaoId]));
                // mudou de grupo: vai para o fim do novo grupo
                if ((int)$antes['pai_id'] !== (int)$paiId || $antes['periodo'] !== $periodo) {
                    $pdo->prepare("UPDATE assembleia_pontos SET ordem = 100000 WHERE id = ?")->execute([$id]);
                    $renumerar($paiId, $periodo); $renumerar($antes['pai_id'] ? (int)$antes['pai_id'] : null, $antes['periodo']);
                }
                // os subpontos acompanham o ponto principal se este mudar de período
                $pdo->prepare("UPDATE assembleia_pontos SET periodo = ? WHERE pai_id = ?")->execute([$periodo, $id]);
            } else {
                $stO = $pdo->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM assembleia_pontos WHERE sessao_id = ? AND periodo = ? AND " . ($paiId ? "pai_id = ?" : "pai_id IS NULL"));
                $stO->execute($paiId ? [$sessaoId, $periodo, $paiId] : [$sessaoId, $periodo]);
                $pdo->prepare("INSERT INTO assembleia_pontos (periodo, pai_id, titulo, descricao, proposta_ref, tem_votacao, resultado, unanimidade,
                               votos_favor, votos_contra, votos_abstencao, deliberacao, sessao_id, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(array_merge($dados, [$sessaoId, (int)$stO->fetchColumn()]));
                $id = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("DELETE FROM assembleia_ponto_documentos WHERE ponto_id = ?")->execute([$id]);
            $insDoc = $pdo->prepare("INSERT IGNORE INTO assembleia_ponto_documentos (ponto_id, documento_id) SELECT ?, id FROM assembleia_documentos WHERE id = ?");
            foreach (array_unique(array_map('intval', (array)($_POST['docs'] ?? []))) as $docId) if ($docId > 0) $insDoc->execute([$id, $docId]);
            $pdo->prepare("UPDATE assembleia_sessoes SET atualizado_em = NOW() WHERE id = ?")->execute([$sessaoId]);
            $irPara('ok', !empty($_POST['ponto_id']) ? 'Ponto guardado.' : 'Ponto acrescentado.', '#ponto-' . $id);
        }
    } elseif ($acao === 'mover') {
        $id = (int)($_POST['ponto_id'] ?? 0);
        if (isset($linhas[$id])) {
            $p = $linhas[$id]; $paiId = $p['pai_id'] ? (int)$p['pai_id'] : null;
            $irmaos = array_values(array_filter($linhas, fn($l) => (int)$l['pai_id'] === (int)$paiId && $l['periodo'] === $p['periodo']));
            usort($irmaos, fn($a, $b) => [(int)$a['ordem'], (int)$a['id']] <=> [(int)$b['ordem'], (int)$b['id']]);
            $ids = array_map(fn($l) => (int)$l['id'], $irmaos);
            $pos = array_search($id, $ids, true);
            $para = $pos + (($_POST['dir'] ?? '') === 'cima' ? -1 : 1);
            if ($pos !== false && isset($ids[$para])) {
                [$ids[$pos], $ids[$para]] = [$ids[$para], $ids[$pos]];
                $up = $pdo->prepare("UPDATE assembleia_pontos SET ordem = ? WHERE id = ?");
                foreach ($ids as $i => $pid) $up->execute([$i + 1, $pid]);
            }
        }
        $irPara('ok', 'Ordem atualizada.', '#ponto-' . $id);
    } elseif ($acao === 'apagar_ponto') {
        $id = (int)($_POST['ponto_id'] ?? 0);
        if (isset($linhas[$id])) {
            $ids = array_merge([$id], array_map('intval', array_keys(array_filter($linhas, fn($l) => (int)$l['pai_id'] === $id))));
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM assembleia_ponto_documentos WHERE ponto_id IN ($marcas)")->execute($ids);
            $pdo->prepare("DELETE FROM assembleia_pontos WHERE id IN ($marcas)")->execute($ids);
            $renumerar($linhas[$id]['pai_id'] ? (int)$linhas[$id]['pai_id'] : null, $linhas[$id]['periodo']);
        }
        $irPara('ok', 'Ponto apagado.');
    } elseif ($acao === 'converter_texto') {
        if (!$linhas && trim((string)$sessao['ordem_trabalhos']) !== '') {
            $ordem = [];
            $ins = $pdo->prepare("INSERT INTO assembleia_pontos (sessao_id, periodo, ordem, titulo, tem_votacao) VALUES (?, ?, ?, ?, 1)");
            foreach (assembleiaTextoParaPontos((string)$sessao['ordem_trabalhos']) as [$per, $tit]) {
                $ordem[$per] = ($ordem[$per] ?? 0) + 1;
                $ins->execute([$sessaoId, $per, $ordem[$per], $tit]);
            }
            $irPara('ok', 'A ordem de trabalhos passou a ' . array_sum($ordem) . ' pontos. Confira-os e acerte o que for preciso (subpontos, votação, documentos).');
        }
        $irPara('erro', 'Esta sessão já tem pontos.');
    } elseif ($acao === 'documentos_sessao') {
        try {
            $pdo->prepare("DELETE FROM assembleia_sessao_documentos WHERE sessao_id = ?")->execute([$sessaoId]);
            $ins = $pdo->prepare("INSERT IGNORE INTO assembleia_sessao_documentos (sessao_id, documento_id) SELECT ?, id FROM assembleia_documentos WHERE id = ?");
            foreach (array_unique(array_map('intval', (array)($_POST['docs_sessao'] ?? []))) as $docId) if ($docId > 0) $ins->execute([$sessaoId, $docId]);
        } catch (Throwable $e) {}
        $irPara('ok', 'Documentos da sessão guardados.', '#documentos-sessao');
    }
}

/* Dados para mostrar */
$pontos = $temTabela ? assembleiaPontos($pdo, $sessaoId, false) : [];
$totalPontos = count($linhas);
$editarId = (int)($_GET['editar_ponto'] ?? ($valores['ponto_id'] ?? 0));
$pontoEditar = $linhas[$editarId] ?? null;
$subDe = (int)($_GET['sub'] ?? 0);
$numeros = [];
foreach ($pontos as $lista) foreach ($lista as $p) { $numeros[(int)$p['id']] = $p['num']; foreach ($p['filhos'] as $f) $numeros[(int)$f['id']] = $f['num']; }

$docsLigados = [];
if ($pontoEditar) {
    $stD = $pdo->prepare("SELECT documento_id FROM assembleia_ponto_documentos WHERE ponto_id = ?");
    $stD->execute([$editarId]);
    $docsLigados = array_map('intval', $stD->fetchAll(PDO::FETCH_COLUMN));
}
if ($valores !== null) $docsLigados = array_map('intval', (array)($valores['docs'] ?? []));
$v = function (string $campo, $omissao = '') use ($valores, $pontoEditar) {
    if ($valores !== null) return $valores[$campo] ?? '';
    return $pontoEditar[$campo] ?? $omissao;
};

$documentos = [];
try {
    $documentos = $pdo->query("SELECT id, titulo, categoria, data_documento, ano FROM assembleia_documentos WHERE ativo = 1
                               ORDER BY COALESCE(data_documento, criado_em) DESC, id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$docsSessao = [];
try {
    $stS = $pdo->prepare("SELECT documento_id FROM assembleia_sessao_documentos WHERE sessao_id = ?");
    $stS->execute([$sessaoId]);
    $docsSessao = array_map('intval', $stS->fetchAll(PDO::FETCH_COLUMN));
} catch (Throwable $e) {}

$principais = [];
foreach ($pontos as $per => $lista) foreach ($lista as $p) $principais[] = $p;
$estadoNome = ['agendada' => 'Agendada', 'realizada' => 'Realizada', 'cancelada' => 'Cancelada'][$sessao['estado']] ?? $sessao['estado'];

$listaDocs = function (string $nome, array $marcados, string $idBase) use ($documentos): string {
    if (!$documentos) return '<p class="asp-ajuda">Ainda não há documentos da Assembleia. Carregue-os em <a href="assembleia-documentos.php" target="_blank" rel="noopener">Assembleia › Documentos</a>.</p>';
    $h = '<label class="asp-filtro-rotulo" for="' . $idBase . '-filtro">Procurar documento</label><input type="search" id="' . $idBase . '-filtro" class="asp-filtro" data-filtro="' . $idBase . '-lista" autocomplete="off">'
       . '<div class="asp-docs" id="' . $idBase . '-lista">';
    foreach ($documentos as $d) {
        $data = !empty($d['data_documento']) ? date('d/m/Y', strtotime($d['data_documento'])) : (string)($d['ano'] ?? '');
        $h .= '<label class="asp-doc"><input type="checkbox" name="' . $nome . '[]" value="' . (int)$d['id'] . '"' . (in_array((int)$d['id'], $marcados, true) ? ' checked' : '') . '>'
            . '<span><b>' . htmlspecialchars($d['titulo']) . '</b><small>' . htmlspecialchars(trim($d['categoria'] . ($data ? ' · ' . $data : ''))) . '</small></span></label>';
    }
    return $h . '</div><p class="asp-ajuda">Falta algum? Carregue-o em <a href="assembleia-documentos.php" target="_blank" rel="noopener">Assembleia › Documentos</a> e volte a abrir esta página.</p>';
};

/* Um ponto da lista (sem o </li> final, para os subpontos ficarem dentro) */
$mostrarPonto = function (array $p, bool $primeiro, bool $ultimo, bool $sub) use ($token, $sessaoId, $editarId): string {
    $res = assembleiaResultadoTexto($p);
    $num = htmlspecialchars($p['num']);
    $campos = '<input type="hidden" name="token" value="' . htmlspecialchars($token) . '"><input type="hidden" name="sessao" value="' . (int)$sessaoId . '"><input type="hidden" name="ponto_id" value="' . (int)$p['id'] . '">';
    $h = '<li class="asp-ponto' . ((int)$p['id'] === $editarId ? ' is-editar' : '') . '" id="ponto-' . (int)$p['id'] . '">'
       . '<div class="asp-ponto-cab"><span class="asp-num">' . $num . '</span><span class="asp-titulo">' . htmlspecialchars($p['titulo']) . '</span></div><div class="asp-info">';
    if (!empty($p['proposta_ref'])) $h .= '<span>' . htmlspecialchars($p['proposta_ref']) . '</span>';
    $h .= '<span>' . ($p['tem_votacao'] ? 'Com votação' : 'Sem votação') . '</span>';
    if ($res !== '') $h .= '<span class="asp-res-' . htmlspecialchars((string)$p['resultado']) . '">' . htmlspecialchars($res) . '</span>';
    if ($p['docs']) $h .= '<span><i class="bi bi-paperclip" aria-hidden="true"></i> ' . count($p['docs']) . (count($p['docs']) === 1 ? ' documento' : ' documentos') . '</span>';
    $h .= '</div><div class="asp-acoes">'
       . '<a class="btn secondary" href="?sessao=' . (int)$sessaoId . '&amp;editar_ponto=' . (int)$p['id'] . '#form-ponto">Editar<span class="asp-sr"> o ponto ' . $num . '</span></a>';
    if (!$sub) $h .= '<a class="btn secondary" href="?sessao=' . (int)$sessaoId . '&amp;sub=' . (int)$p['id'] . '#form-ponto">Subponto<span class="asp-sr"> do ponto ' . $num . '</span></a>';
    foreach (['cima' => ['bi-arrow-up', 'Subir', $primeiro], 'baixo' => ['bi-arrow-down', 'Descer', $ultimo]] as $dir => [$ico, $rot, $off]) {
        if ($off) continue;
        $h .= '<form method="POST" data-sem-aviso>' . $campos . '<input type="hidden" name="acao" value="mover"><input type="hidden" name="dir" value="' . $dir . '">'
            . '<button class="btn secondary" type="submit" title="' . $rot . '"><i class="bi ' . $ico . '" aria-hidden="true"></i><span class="asp-sr">' . $rot . ' o ponto ' . $num . '</span></button></form>';
    }
    $h .= '<form method="POST" data-sem-aviso onsubmit="return confirm(\'Apagar o ponto ' . $num . ($p['filhos'] ? ' e os seus subpontos' : '') . '?\')">' . $campos
        . '<input type="hidden" name="acao" value="apagar_ponto"><button class="btn danger" type="submit">Apagar<span class="asp-sr"> o ponto ' . $num . '</span></button></form></div>';
    return $h;
};
?>
<style>
.asp-sr{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.asp-topo{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start}
.asp-topo h2{margin:0 0 6px}
.asp-meta{display:flex;gap:8px;flex-wrap:wrap;color:#475569;font-size:14px}
.asp-meta span{background:#f1f5f9;border-radius:999px;padding:5px 11px;font-weight:600}
.asp-botoes{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.asp-aviso{display:flex;gap:10px;align-items:flex-start;border-radius:12px;padding:12px 14px;margin:16px 0 0;font-weight:700}
.asp-aviso--ok{background:#dcfce7;color:#14532d}.asp-aviso--erro{background:#fee2e2;color:#7f1d1d}
.asp-grelha{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:20px;align-items:start;margin-top:20px}
@media(max-width:1100px){.asp-grelha{grid-template-columns:minmax(0,1fr)}}
.asp-periodo{font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:800;margin:18px 0 8px}
.asp-lista{list-style:none;margin:0;padding:0;display:grid;gap:10px}
.asp-ponto{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:12px 14px}
.asp-ponto.is-editar{border-color:var(--cor-principal);box-shadow:0 0 0 2px color-mix(in srgb,var(--cor-principal) 25%,transparent)}
.asp-ponto-cab{display:flex;gap:10px;align-items:flex-start}
.asp-num{min-width:34px;height:30px;padding:0 6px;border-radius:9px;background:var(--cor-principal);color:#fff;font-weight:800;display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.asp-sub .asp-num{background:#e2e8f0;color:#1e293b}
.asp-titulo{font-weight:700;color:#1b2430;line-height:1.4;padding-top:4px}
.asp-info{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0 0 44px;font-size:12.5px}
.asp-info span{background:#f1f5f9;color:#334155;border-radius:999px;padding:3px 9px;font-weight:600}
.asp-info .asp-res-aprovado{background:#dcfce7;color:#14532d}.asp-info .asp-res-rejeitado{background:#fee2e2;color:#7f1d1d}
.asp-acoes{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0 0 44px}
.admin-main .asp-acoes form{display:inline-flex;margin:0;padding:0;background:none;border:0;box-shadow:none;border-radius:0}
.admin-main .asp-acoes .btn{min-height:34px;padding:0 12px;font-size:13px}
.asp-sub{list-style:none;margin:10px 0 0 30px;padding:0;display:grid;gap:8px}
.asp-sub .asp-ponto{background:#f8fafc}
.asp-vazio{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:14px;padding:18px;color:#475569}
.asp-ajuda{font-size:13px;color:#64748b;margin:4px 0 12px}
.admin-main .asp-form fieldset{border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;margin:6px 0 16px}
.asp-form legend{font-weight:800;padding:0 6px;color:#1b2430}
.asp-num3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.asp-docs{max-height:280px;overflow:auto;display:grid;gap:6px;margin:6px 0 4px;padding-right:4px}
.admin-main .asp-doc{display:flex;gap:10px;align-items:flex-start;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:8px 10px;font-weight:400;cursor:pointer;margin:0}
.admin-main .asp-doc input{margin-top:3px;width:auto;min-height:0}
.admin-main .asp-doc[hidden]{display:none}
.admin-main .asp-doc b{display:block;font-size:13.5px;line-height:1.35;color:#1b2430}
.admin-main .asp-doc small{display:block;margin:3px 0 0;color:#64748b;font-size:12px;line-height:1.35}
.admin-main .asp-check{display:flex;align-items:center;gap:8px;font-weight:600;margin:6px 0 14px}
.admin-main .asp-check input{width:auto;min-height:0;margin:0}
.asp-converter{background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:14px 16px;margin-top:16px}
.asp-converter ol{margin:8px 0 12px;padding-left:20px;color:#334155}
.admin-main .asp-converter form{margin:0;padding:0;background:none;border:0;box-shadow:none}
</style>

<div class="content-box">
    <div class="asp-topo">
        <div>
            <h2><?= htmlspecialchars($sessao['titulo']) ?></h2>
            <div class="asp-meta">
                <span><?= htmlspecialchars($sessao['tipo'] ?: 'Sessão') ?></span>
                <span><i class="bi bi-calendar-event" aria-hidden="true"></i> <?= date('d/m/Y', strtotime($sessao['data_sessao'])) ?> · <?= substr((string)$sessao['hora_sessao'], 0, 5) ?></span>
                <span><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= htmlspecialchars($sessao['local_sessao'] ?: 'Local a definir') ?></span>
                <span><?= htmlspecialchars($estadoNome) ?></span>
            </div>
        </div>
        <div class="asp-botoes">
            <a class="btn secondary" href="assembleia-sessoes.php?editar=<?= (int)$sessaoId ?>"><i class="bi bi-pencil" aria-hidden="true"></i> Dados da sessão</a>
            <?php if ($totalPontos): ?>
                <a class="btn secondary" href="assembleia-sessao-pdf.php?sessao=<?= (int)$sessaoId ?>&amp;tipo=convocatoria" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Convocatória</a>
                <a class="btn secondary" href="assembleia-sessao-pdf.php?sessao=<?= (int)$sessaoId ?>&amp;tipo=edital" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Edital</a>
                <?php if ($sessao['estado'] === 'realizada'): ?>
                    <a class="btn secondary" href="assembleia-sessao-pdf.php?sessao=<?= (int)$sessaoId ?>&amp;tipo=deliberacoes" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Edital das deliberações</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($aviso): ?>
        <div class="asp-aviso asp-aviso--<?= $aviso[0] === 'ok' ? 'ok' : 'erro' ?>" role="<?= $aviso[0] === 'ok' ? 'status' : 'alert' ?>">
            <i class="bi <?= $aviso[0] === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>" aria-hidden="true"></i><span><?= htmlspecialchars($aviso[1]) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!$temTabela): ?>
        <div class="asp-aviso asp-aviso--erro" role="alert"><span>Falta atualizar a base de dados deste site (migração 094). Peça à GesGov.</span></div>
    <?php elseif (!$totalPontos && trim((string)$sessao['ordem_trabalhos']) !== ''): $previa = assembleiaTextoParaPontos((string)$sessao['ordem_trabalhos']); ?>
        <div class="asp-converter">
            <strong>Esta sessão tem a ordem de trabalhos escrita em texto.</strong>
            <p class="asp-ajuda" style="margin:4px 0 0">Pode passá-la a pontos de uma vez (o texto fica guardado). Ficam assim:</p>
            <ol><?php foreach ($previa as [$per, $tit]): ?><li><?= htmlspecialchars($tit) ?><?= $per !== 'OD' ? ' <small>(' . htmlspecialchars($periodos[$per]) . ')</small>' : '' ?></li><?php endforeach; ?></ol>
            <form method="POST" data-sem-aviso>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"><input type="hidden" name="sessao" value="<?= (int)$sessaoId ?>">
                <input type="hidden" name="acao" value="converter_texto">
                <button class="btn" type="submit"><i class="bi bi-list-ol" aria-hidden="true"></i> Passar a <?= count($previa) ?> pontos</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="asp-grelha">
    <div class="content-box" style="margin:0">
        <h2 style="margin-top:0">Ordem de trabalhos</h2>
        <?php if (!$pontos): ?>
            <div class="asp-vazio">Ainda não há pontos. Acrescente o primeiro no formulário <span class="asp-ao-lado">ao lado</span>.</div>
        <?php endif; ?>
        <?php foreach ($pontos as $per => $lista): ?>
            <h3 class="asp-periodo"><?= htmlspecialchars($periodos[$per]) ?></h3>
            <ol class="asp-lista">
                <?php foreach ($lista as $i => $p):
                    echo $mostrarPonto($p, $i === 0, $i === count($lista) - 1, false);
                    if ($p['filhos']) {
                        echo '<ol class="asp-sub">';
                        foreach ($p['filhos'] as $k => $f) echo $mostrarPonto($f, $k === 0, $k === count($p['filhos']) - 1, true) . '</li>';
                        echo '</ol>';
                    }
                    echo '</li>';
                endforeach; ?>
            </ol>
        <?php endforeach; ?>
    </div>

    <div class="content-box" style="margin:0" id="form-ponto">
        <?php
        $tituloForm = $pontoEditar ? 'Editar o ponto ' . ($numeros[$editarId] ?? '') : ($subDe && isset($linhas[$subDe]) ? 'Novo subponto do ponto ' . ($numeros[$subDe] ?? '') : 'Novo ponto');
        $paiAtual = (int)($valores !== null ? ($valores['pai_id'] ?? 0) : ($pontoEditar['pai_id'] ?? $subDe));
        ?>
        <h2 style="margin-top:0"><?= htmlspecialchars($tituloForm) ?></h2>
        <?php if ($erroForm): ?><div class="asp-aviso asp-aviso--erro" role="alert" style="margin:0 0 14px"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span><?= htmlspecialchars($erroForm) ?></span></div><?php endif; ?>
        <form method="POST" class="asp-form" action="assembleia-sessao-pontos.php?sessao=<?= (int)$sessaoId ?>#form-ponto">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"><input type="hidden" name="sessao" value="<?= (int)$sessaoId ?>">
            <input type="hidden" name="acao" value="guardar_ponto"><input type="hidden" name="ponto_id" value="<?= (int)($pontoEditar['id'] ?? 0) ?>">
            <p class="asp-ajuda">Os campos com * são obrigatórios.</p>

            <label for="asp-titulo">Título do ponto *</label>
            <textarea id="asp-titulo" name="titulo" rows="2" required><?= htmlspecialchars((string)$v('titulo')) ?></textarea>

            <label for="asp-periodo">Período</label>
            <select id="asp-periodo" name="periodo">
                <?php $perAtual = (string)$v('periodo', $subDe && isset($linhas[$subDe]) ? $linhas[$subDe]['periodo'] : 'OD'); foreach ($periodos as $k => $nome): ?>
                    <option value="<?= $k ?>" <?= $perAtual === $k ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="asp-pai">É subponto de</label>
            <select id="asp-pai" name="pai_id" aria-describedby="asp-pai-ajuda">
                <option value="">Nenhum (é um ponto principal)</option>
                <?php foreach ($principais as $pp): if ((int)$pp['id'] === $editarId) continue; ?>
                    <option value="<?= (int)$pp['id'] ?>" <?= $paiAtual === (int)$pp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pp['num'] . '. ' . mb_strimwidth($pp['titulo'], 0, 70, '…')) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="asp-ajuda" id="asp-pai-ajuda">Um subponto fica numerado dentro do ponto principal (ex.: 2.1) e no mesmo período.</p>

            <label for="asp-descricao">Descrição (opcional)</label>
            <textarea id="asp-descricao" name="descricao" rows="3"><?= htmlspecialchars((string)$v('descricao')) ?></textarea>

            <label for="asp-proposta">Proposta de referência (opcional)</label>
            <input type="text" id="asp-proposta" name="proposta_ref" maxlength="150" value="<?= htmlspecialchars((string)$v('proposta_ref')) ?>" aria-describedby="asp-proposta-ajuda">
            <p class="asp-ajuda" id="asp-proposta-ajuda">Por exemplo: Proposta n.º 12/2026 da Junta de Freguesia.</p>

            <label class="asp-check"><input type="checkbox" name="tem_votacao" value="1" <?= ($valores !== null ? !empty($valores['tem_votacao']) : (int)($pontoEditar['tem_votacao'] ?? 1) === 1) ? 'checked' : '' ?>> Este ponto é votado</label>

            <fieldset>
                <legend>Documentos deste ponto</legend>
                <?= $listaDocs('docs', $docsLigados, 'asp-docs-ponto') ?>
            </fieldset>

            <fieldset>
                <legend>Resultado (depois da sessão)</legend>
                <label for="asp-resultado">Resultado</label>
                <select id="asp-resultado" name="resultado">
                    <option value="">Ainda sem resultado</option>
                    <?php foreach ($resultados as $k => $nome): ?><option value="<?= $k ?>" <?= (string)$v('resultado') === $k ? 'selected' : '' ?>><?= $nome ?></option><?php endforeach; ?>
                </select>
                <label class="asp-check"><input type="checkbox" name="unanimidade" value="1" <?= ($valores !== null ? !empty($valores['unanimidade']) : !empty($pontoEditar['unanimidade'])) ? 'checked' : '' ?>> Por unanimidade</label>
                <div class="asp-num3">
                    <div><label for="asp-favor">A favor</label><input type="number" min="0" max="999" id="asp-favor" name="votos_favor" value="<?= htmlspecialchars((string)$v('votos_favor')) ?>"></div>
                    <div><label for="asp-contra">Contra</label><input type="number" min="0" max="999" id="asp-contra" name="votos_contra" value="<?= htmlspecialchars((string)$v('votos_contra')) ?>"></div>
                    <div><label for="asp-abst">Abstenções</label><input type="number" min="0" max="999" id="asp-abst" name="votos_abstencao" value="<?= htmlspecialchars((string)$v('votos_abstencao')) ?>"></div>
                </div>
                <label for="asp-deliberacao">Deliberação (opcional)</label>
                <textarea id="asp-deliberacao" name="deliberacao" rows="2"><?= htmlspecialchars((string)$v('deliberacao')) ?></textarea>
            </fieldset>

            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> <?= $pontoEditar ? 'Guardar ponto' : 'Acrescentar ponto' ?></button>
                <?php if ($pontoEditar || $subDe): ?><a class="btn secondary" href="?sessao=<?= (int)$sessaoId ?>">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="content-box" id="documentos-sessao" style="margin-top:20px">
    <h2 style="margin-top:0">Documentos da sessão</h2>
    <p class="asp-ajuda">Documentos de toda a sessão (convocatória, edital, ata, minuta). Aparecem no site junto da sessão.</p>
    <form method="POST" class="asp-form">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"><input type="hidden" name="sessao" value="<?= (int)$sessaoId ?>"><input type="hidden" name="acao" value="documentos_sessao">
        <?= $listaDocs('docs_sessao', $docsSessao, 'asp-docs-sessao') ?>
        <button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Guardar documentos da sessão</button>
    </form>
</div>

<script>
// Procurar na lista de documentos (mostra só os que têm o texto escrito; os já escolhidos ficam sempre)
document.querySelectorAll('input[data-filtro]').forEach(function (campo) {
    var lista = document.getElementById(campo.getAttribute('data-filtro'));
    if (!lista) return;
    campo.addEventListener('input', function () {
        var q = campo.value.trim().toLowerCase();
        lista.querySelectorAll('.asp-doc').forEach(function (l) {
            var marcado = l.querySelector('input').checked;
            l.hidden = q !== '' && !marcado && l.textContent.toLowerCase().indexOf(q) === -1;
        });
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
