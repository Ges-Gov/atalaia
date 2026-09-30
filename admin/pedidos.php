<?php
$adminPageTitle = "Pedidos à Junta";
$adminActive = "pedidos";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/mapa.php"; $centroMapa = centroFreguesia();

$estado = $_GET['estado'] ?? '';
$aba = $_GET['aba'] ?? 'gestao';
$fSubcategoria = $_GET['subcategoria'] ?? '';
$fPrioridade = $_GET['prioridade'] ?? '';
$fTag = $_GET['tag'] ?? '';



















$where = [];
$params = [];

if ($estado !== '') {
    $where[] = "estado = ?";
    $params[] = $estado;
}

if (isOperador()) {
    $where[] = "operador_id = ?";
    $params[] = (int)$adminId;
}

if ($aba === 'atribuidas') {
    $where[] = "operador_id = ?";
    $params[] = (int)$adminId;
} elseif ($aba === 'tratadas') {
    $where[] = "estado IN ('resolvido', 'arquivado')";
} elseif ($aba === 'minhas') {
    $where[] = "EXISTS (SELECT 1 FROM pedidos_funcionarios pf WHERE pf.pedido_id = pedidos_junta.id AND pf.admin_id = ?)";
    $params[] = (int)$adminId;
}

if ($fSubcategoria !== '') {
    $where[] = "subcategoria = ?";
    $params[] = $fSubcategoria;
}

if ($fPrioridade !== '') {
    $where[] = "prioridade = ?";
    $params[] = $fPrioridade;
}

if ($fTag !== '') {
    $where[] = "EXISTS (SELECT 1 FROM pedidos_tags pt WHERE pt.pedido_id = pedidos_junta.id AND pt.tag_id = ?)";
    $params[] = (int)$fTag;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare("
    SELECT *
    FROM pedidos_junta
    $whereSql
    ORDER BY criado_em DESC
");























$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Tags de cada pedido listado (para mostrar na tabela) */
$tagsPorPedido = [];
if (!empty($pedidos)) {
    $ids = array_column($pedidos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtTagsPedidos = $pdo->prepare("
        SELECT pt.pedido_id, t.designacao
        FROM pedidos_tags pt
        JOIN ocorrencias_tags t ON t.id = pt.tag_id
        WHERE pt.pedido_id IN ($placeholders)
    ");
    $stmtTagsPedidos->execute($ids);
    foreach ($stmtTagsPedidos->fetchAll(PDO::FETCH_ASSOC) as $tp) {
        $tagsPorPedido[$tp['pedido_id']][] = $tp['designacao'];
    }
}

/* Funcionários colaboradores de cada pedido listado */
$funcionariosPorPedido = [];
if (!empty($pedidos)) {
    $ids = array_column($pedidos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtFuncPedidos = $pdo->prepare("
        SELECT pf.pedido_id, u.nome, u.username
        FROM pedidos_funcionarios pf
        JOIN admin_utilizadores u ON u.id = pf.admin_id
        WHERE pf.pedido_id IN ($placeholders)
    ");
    $stmtFuncPedidos->execute($ids);
    foreach ($stmtFuncPedidos->fetchAll(PDO::FETCH_ASSOC) as $fp) {
        $funcionariosPorPedido[$fp['pedido_id']][] = $fp['nome'] ?: $fp['username'];
    }
}

/* Opções para os filtros */
$subcategoriasDisponiveis = $pdo->query("
    SELECT DISTINCT subcategoria FROM pedidos_junta
    WHERE subcategoria IS NOT NULL AND subcategoria != ''
    ORDER BY subcategoria ASC
")->fetchAll(PDO::FETCH_COLUMN);

$tagsDisponiveis = $pdo->query("
    SELECT id, designacao FROM ocorrencias_tags WHERE ativo = 1 ORDER BY designacao ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* MAPA OPERACIONAL DAS OCORRÊNCIAS */
$whereMapa = [];
$paramsMapa = [];

if ($estado !== '') {
    $whereMapa[] = "estado = ?";
    $paramsMapa[] = $estado;
}








































$whereMapa[] = "latitude IS NOT NULL";
$whereMapa[] = "longitude IS NOT NULL";
$whereMapa[] = "latitude != ''";
$whereMapa[] = "longitude != ''";

$whereMapaSql = "WHERE " . implode(" AND ", $whereMapa);

$stmtMapa = $pdo->prepare("
    SELECT id, codigo, categoria, assunto, mensagem, localizacao, estado, latitude, longitude, criado_em
    FROM pedidos_junta
    $whereMapaSql
    ORDER BY criado_em DESC
");
$stmtMapa->execute($paramsMapa);
$pedidosMapa = $stmtMapa->fetchAll(PDO::FETCH_ASSOC);

$whereCategorias = [];
$paramsCategorias = [];













































$whereCategorias[] = "latitude IS NOT NULL";
$whereCategorias[] = "longitude IS NOT NULL";
$whereCategorias[] = "latitude != ''";
$whereCategorias[] = "longitude != ''";

$whereCategoriasSql = "WHERE " . implode(" AND ", $whereCategorias);

$stmtCategorias = $pdo->prepare("
    SELECT DISTINCT categoria
    FROM pedidos_junta
    $whereCategoriasSql
    ORDER BY categoria ASC
");
$stmtCategorias->execute($paramsCategorias);
$categoriasMapa = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);

$totalMapa = count($pedidosMapa);
$totalMapaPendentes = 0;
$totalMapaAnalise = 0;
$totalMapaResolvidas = 0;
$totalMapaArquivadas = 0;

foreach ($pedidosMapa as $pm) {
    if (($pm['estado'] ?? '') === 'pendente') $totalMapaPendentes++;
    elseif (($pm['estado'] ?? '') === 'em_analise') $totalMapaAnalise++;
    elseif (($pm['estado'] ?? '') === 'resolvido') $totalMapaResolvidas++;
    elseif (($pm['estado'] ?? '') === 'arquivado') $totalMapaArquivadas++;
}

$percentagemMapaResolvidas = $totalMapa > 0 ? round(($totalMapaResolvidas / $totalMapa) * 100) : 0;

?>

<style>
.pedidos-header-actions{display:flex;justify-content:space-between;align-items:stretch;flex-wrap:wrap;gap:14px;margin-bottom:16px;}
.pedidos-tabs{display:flex;gap:4px;border-bottom:2px solid #e5e7eb;flex-wrap:wrap;}
.pedidos-tabs a{display:flex;align-items:center;height:44px;box-sizing:border-box;padding:0 16px;color:#64748b;font-weight:800;text-decoration:none;border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;}
.pedidos-tabs a:hover{color:#11151B;}
.pedidos-tabs a.active{color:var(--cor-principal,#242A30);border-bottom-color:var(--cor-principal,#242A30);}
.pedidos-header-buttons{display:flex;gap:10px;align-items:center;}
.pedidos-header-buttons .btn{display:flex;align-items:center;height:44px;box-sizing:border-box;padding:0 18px;}
.btn-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;box-sizing:border-box;border-radius:12px;background:#f1f3f5;color:#495057;text-decoration:none;font-size:18px;flex-shrink:0;}
.btn-icon:hover{background:#e9ecef;color:#11151B;}

.pedidos-filtros{display:grid;grid-template-columns:repeat(4, 1fr) auto auto;gap:10px;align-items:center;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:14px 16px;margin-bottom:20px;}
.pedidos-filtros select{height:42px;box-sizing:border-box;width:100%;border:1px solid #dbe4ee;border-radius:10px;padding:0 10px;background:#fff;color:#11151B;font-weight:700;}
.pedidos-filtros .btn{height:42px;box-sizing:border-box;display:flex;align-items:center;padding:0 16px;white-space:nowrap;}
@media (max-width: 960px){.pedidos-filtros{grid-template-columns:1fr 1fr;}}
@media (max-width: 560px){.pedidos-filtros{grid-template-columns:1fr;}}

.prioridade-critico{background:#ffe3e3;color:#c92a2a}
.prioridade-urgente{background:#ffe8cc;color:#d9480f}
.prioridade-normal{background:#e9ecef;color:#495057}
.prioridade-baixo{background:#f1f3f5;color:#868e96}

.tag-pill{display:inline-flex;align-items:center;background:color-mix(in srgb, var(--cor-principal, #242A30) 12%, white);color:var(--cor-principal,#242A30);border-radius:999px;padding:5px 11px;font-size:12px;font-weight:800;margin:2px 4px 2px 0;white-space:nowrap;}
</style>

<div class="pedidos-header-actions">
    <div class="pedidos-tabs">
        <a class="<?= $aba === 'gestao' ? 'active' : '' ?>" href="pedidos.php?aba=gestao">Gestão de Assuntos</a>
        <a class="<?= $aba === 'atribuidas' ? 'active' : '' ?>" href="pedidos.php?aba=atribuidas">Atribuídas</a>
        <a class="<?= $aba === 'tratadas' ? 'active' : '' ?>" href="pedidos.php?aba=tratadas">Tratadas</a>
        <a class="<?= $aba === 'minhas' ? 'active' : '' ?>" href="pedidos.php?aba=minhas">Minhas Ocorrências</a>
    </div>

    <div class="pedidos-header-buttons">
        <a class="btn" href="criar_pedido.php">+ Criar Ocorrência</a>
        <?php if (isAdminMaster()): ?>
            <a class="btn-icon" href="ocorrencias_config.php" title="Configurações"><i class="bi bi-sliders"></i></a>
        <?php endif; ?>
    </div>
</div>

<form method="GET" class="pedidos-filtros">
    <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">

    <select name="estado" onchange="this.form.submit()">
        <option value="">Todos os estados</option>
        <option value="pendente" <?= $estado === 'pendente' ? 'selected' : '' ?>>Pendentes</option>
        <option value="em_analise" <?= $estado === 'em_analise' ? 'selected' : '' ?>>Em análise</option>
        <option value="resolvido" <?= $estado === 'resolvido' ? 'selected' : '' ?>>Resolvidos</option>
        <option value="arquivado" <?= $estado === 'arquivado' ? 'selected' : '' ?>>Arquivados</option>
    </select>

    <select name="subcategoria" onchange="this.form.submit()">
        <option value="">Todas as subcategorias</option>
        <?php foreach ($subcategoriasDisponiveis as $sub): ?>
            <option value="<?= htmlspecialchars($sub) ?>" <?= $fSubcategoria === $sub ? 'selected' : '' ?>><?= htmlspecialchars($sub) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="prioridade" onchange="this.form.submit()">
        <option value="">Todas as prioridades</option>
        <option value="critico" <?= $fPrioridade === 'critico' ? 'selected' : '' ?>>Crítico</option>
        <option value="urgente" <?= $fPrioridade === 'urgente' ? 'selected' : '' ?>>Urgente</option>
        <option value="normal" <?= $fPrioridade === 'normal' ? 'selected' : '' ?>>Normal</option>
        <option value="baixo" <?= $fPrioridade === 'baixo' ? 'selected' : '' ?>>Baixo</option>
    </select>

    <select name="tag" onchange="this.form.submit()">
        <option value="">Todas as tags</option>
        <?php foreach ($tagsDisponiveis as $tg): ?>
            <option value="<?= (int)$tg['id'] ?>" <?= (string)$fTag === (string)$tg['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tg['designacao']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn secondary">Filtrar</button>
    <a class="btn secondary" href="pedidos.php">Limpar filtros</a>
</form>

<div class="table-box moderna">
    <table class="tabela-moderna">
        <thead>
        <tr>
            <th>Data</th>
            <th>Dias</th>
            <th>Origem</th>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Assunto</th>
            <th>Prioridade</th>
            <th>Estado</th>
            <th>Funcionários</th>
            <th>Tags</th>
            <th>Mensagens</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>

        <?php foreach ($pedidos as $p): ?>

            <?php
            $stmtUnread = $pdo->prepare("
                SELECT COUNT(*) 
                FROM pedido_mensagens
                WHERE pedido_id = ? 
                AND autor_tipo = 'cidadao'
                AND lida = 0
            ");
            $stmtUnread->execute([$p['id']]);
            $naoLidas = $stmtUnread->fetchColumn();

            $criadoEm = strtotime($p['criado_em']);
            $fimContagem = in_array($p['estado'], ['resolvido', 'arquivado'], true) && !empty($p['atualizado_em'])
                ? strtotime($p['atualizado_em'])
                : time();
            $diasAberto = max(0, (int) floor(($fimContagem - $criadoEm) / 86400));
            $origensLabel = [
                'website' => 'Website', 'telefone' => 'Telefone', 'balcao' => 'Balcão', 'oficio' => 'Ofício',
                'redes_sociais' => 'Redes Sociais', 'app_movel' => 'Aplicação Móvel', 'email' => 'Email', 'outro' => 'Outro',
            ];
            $estadosInternosLabelListagem = ['por_tratar' => 'Por tratar', 'em_tratamento' => 'Em tratamento', 'tratado' => 'Tratado'];
            ?>

            <tr>
                <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
                <td><?= $diasAberto ?></td>
                <td><?= htmlspecialchars($origensLabel[$p['origem'] ?? 'website'] ?? ucfirst($p['origem'] ?? 'Website')) ?></td>
                <td class="td-truncate td-nome" title="<?= htmlspecialchars($p['nome']) ?>"><?= htmlspecialchars($p['nome']) ?></td>
                <td class="td-truncate td-categoria" title="<?= htmlspecialchars($p['categoria'] . (!empty($p['subcategoria']) ? ' — ' . $p['subcategoria'] : '')) ?>">
                    <?= htmlspecialchars($p['categoria']) ?>
                    <?php if (!empty($p['subcategoria'])): ?>
                        <small><?= htmlspecialchars($p['subcategoria']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="td-truncate td-assunto" title="<?= htmlspecialchars($p['assunto']) ?>">
                    <?= htmlspecialchars($p['assunto']) ?>

                    <?php if ($naoLidas > 0): ?>
                        <span class="badge-novo"><?= (int)$naoLidas ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="estado-badge prioridade-<?= htmlspecialchars($p['prioridade'] ?? 'normal') ?>">
                        <?= htmlspecialchars(ucfirst($p['prioridade'] ?? 'normal')) ?>
                    </span>
                </td>
                <td class="td-truncate td-estado" title="<?= htmlspecialchars(str_replace('_', ' ', $p['estado']) . ' (' . ($estadosInternosLabelListagem[$p['estado_interno'] ?? 'por_tratar'] ?? 'Por tratar') . ')') ?>">
                    <span class="estado-badge estado-<?= htmlspecialchars($p['estado']) ?>">
                        <?= htmlspecialchars(str_replace('_', ' ', $p['estado'])) ?>
                    </span>
                    <small>(<?= htmlspecialchars($estadosInternosLabelListagem[$p['estado_interno'] ?? 'por_tratar'] ?? 'Por tratar') ?>)</small>
                </td>
                <td>
                    <?= htmlspecialchars(implode(', ', $funcionariosPorPedido[$p['id']] ?? [])) ?: '-' ?>
                </td>
                <td>
                    <?php foreach (($tagsPorPedido[$p['id']] ?? []) as $tagNome): ?>
                        <span class="tag-pill"><?= htmlspecialchars($tagNome) ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php if ($naoLidas > 0): ?>
                        <span class="badge-novo"><?= (int)$naoLidas ?> nova(s)</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <div class="acoes-btns">
                        <a class="btn-acao ver" href="ver_pedido.php?id=<?= $p['id'] ?>" title="Ver"><i class="bi bi-eye"></i></a>
                        <?php if (isAdminMaster()): ?>
                            <a class="btn-acao eliminar" href="eliminar_pedido.php?id=<?= $p['id'] ?>" onclick="return confirm('Eliminar ocorrência?')" title="Eliminar"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </div>


                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($pedidos)): ?>
            <tr>
                <td colspan="12">Ainda não existem pedidos.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css">

<style>
.admin-ocorrencias-map-section{margin-top:28px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:28px;padding:24px;box-shadow:0 18px 50px rgba(15,23,42,.08)}
.admin-map-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:24px;padding:24px;display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:center;margin-bottom:18px}
.admin-map-hero h2{margin:10px 0 8px;font-size:32px;color:white}.admin-map-hero p{margin:0;color:#dbeafe;line-height:1.7}
.admin-map-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.38);color:#F0D060;padding:8px 13px;border-radius:999px;font-weight:900;text-transform:uppercase;font-size:12px}
.admin-map-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.admin-map-kpis div{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);border-radius:16px;padding:14px;text-align:center}.admin-map-kpis strong{display:block;font-size:28px}.admin-map-kpis small{color:#dbeafe;font-weight:800}
.admin-map-shell{display:grid;grid-template-columns:350px 1fr;min-height:620px;background:white;border:1px solid #e5e7eb;border-radius:24px;overflow:hidden;box-shadow:0 20px 55px rgba(15,23,42,.08)}
.admin-map-sidebar{padding:20px;border-right:1px solid #e5e7eb;background:linear-gradient(180deg,#fff,#f8fafc);overflow-y:auto}.admin-map-sidebar-head{background:#11151B;color:white;border-radius:20px;padding:18px;margin-bottom:14px}.admin-map-sidebar-head h3{margin:0 0 8px;color:white}.admin-map-sidebar-head p{margin:0;color:#dbeafe;line-height:1.5}
.admin-map-legenda,.admin-map-legend{display:grid;gap:8px}.admin-map-legenda{grid-template-columns:1fr 1fr;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:12px;margin-bottom:14px}.admin-map-legenda span,.admin-map-legend span{display:flex;align-items:center;gap:7px;font-weight:900;font-size:13px;color:#334155}
.admin-map-filter-box{background:white;border:1px solid #e5e7eb;border-radius:18px;padding:15px;box-shadow:0 10px 25px rgba(15,23,42,.05)}.admin-map-filter-box h3{margin:0 0 12px;color:#11151B}.admin-map-filter-box input,.admin-map-filter-box select{width:100%;height:44px;border:1px solid #dbe4ee;border-radius:13px;padding:0 12px;margin-bottom:10px;background:#f8fafc;color:#11151B;font-weight:800;box-sizing:border-box}
.admin-map-list-title{display:flex;justify-content:space-between;align-items:center;margin:18px 0 10px}.admin-map-list-title strong{color:#11151B;font-size:18px}.admin-map-list-title small{min-width:34px;height:34px;display:grid;place-items:center;background:#242A30;color:white;border-radius:50%;font-weight:900}
.admin-ocorrencias-list{display:grid;gap:10px;max-height:285px;overflow-y:auto;padding-right:4px}.admin-ocorrencia-item{width:100%;border:1px solid #eef2f7;background:white;border-radius:16px;padding:12px;display:grid;grid-template-columns:34px 1fr auto;gap:10px;text-align:left;cursor:pointer;transition:.22s ease;box-shadow:0 8px 18px rgba(15,23,42,.04)}.admin-ocorrencia-item:hover{background:#f8fafc;transform:translateX(4px)}.admin-ocorrencia-icon{width:30px;height:30px;border-radius:12px;display:grid;place-items:center;color:white;font-weight:900}.admin-ocorrencia-item h3{margin:0;color:#11151B;font-size:14px}.admin-ocorrencia-item p{margin:4px 0 0;color:#64748b;font-size:12px}.admin-ocorrencia-item small{font-size:11px;font-weight:900;text-transform:uppercase;text-align:right}
.admin-map-wrap{position:relative;min-height:620px;background:#dbeafe}#adminMapaOcorrencias{width:100%;height:100%;min-height:620px;z-index:1}.admin-map-legend{position:absolute;top:18px;right:18px;z-index:900;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);padding:16px;border-radius:18px;border:1px solid rgba(226,232,240,.9);box-shadow:0 18px 45px rgba(15,23,42,.14)}
.admin-map-floating-card{position:absolute;left:18px;bottom:18px;z-index:900;width:min(340px,calc(100% - 36px));background:rgba(17,21,28,.94);color:white;border:1px solid rgba(255,255,255,.14);border-radius:20px;padding:15px;box-shadow:0 20px 55px rgba(0,0,0,.20)}.admin-map-floating-card strong{display:block;margin-bottom:6px}.admin-map-floating-card p{margin:0;color:#dbeafe;line-height:1.5;font-size:13px}
.admin-map-stats{margin-top:18px;display:grid;grid-template-columns:1fr 1fr;gap:16px}.admin-map-stat-panel{background:white;border:1px solid #e5e7eb;border-radius:22px;padding:18px;box-shadow:0 12px 32px rgba(15,23,42,.06)}.admin-progress-label{display:flex;justify-content:space-between;font-weight:900;color:#334155;margin-bottom:8px}.admin-progress-track{height:12px;background:#e5e7eb;border-radius:999px;overflow:hidden}.admin-progress-fill{height:100%;background:linear-gradient(90deg,#242A30,#D4AA00)}.admin-mini-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.admin-mini-card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:14px}.admin-mini-card strong{display:block;font-size:26px;color:#11151B}.admin-mini-card span{display:block;margin-top:4px;color:#64748b;font-weight:800;font-size:13px}
.dot{width:13px;height:13px;border-radius:50%;display:inline-block}.dot-pendente{background:#f59f00}.dot-analise{background:#2563eb}.dot-resolvido{background:#2b8a3e}.dot-arquivado{background:#6b7280}
.popup-pro{width:280px}.popup-pro h3{margin:0;color:#11151B;font-size:19px}.popup-pro small{display:inline-flex;margin:9px 0 10px;padding:6px 10px;background:#eef2f7;color:#242A30;border-radius:999px;font-weight:900}.popup-pro p{margin:10px 0;color:#475569;line-height:1.5}.popup-pro .btn{display:inline-flex;text-decoration:none;background:#242A30;color:white;padding:10px 13px;border-radius:12px;font-weight:900}.popup-estado{display:inline-flex;padding:7px 10px;border-radius:999px;color:white;font-size:12px;font-weight:900;text-transform:uppercase;margin-bottom:10px}.popup-morada{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:10px}
.marker-cluster-small,.marker-cluster-medium,.marker-cluster-large{background-color:rgba(36,42,50,.22)!important}.marker-cluster-small div,.marker-cluster-medium div,.marker-cluster-large div{background-color:#242A30!important;color:white!important;font-weight:900!important}
@media(max-width:1100px){.admin-map-hero,.admin-map-shell,.admin-map-stats{grid-template-columns:1fr}.admin-map-kpis{grid-template-columns:repeat(2,1fr)}.admin-map-sidebar{border-right:0;border-bottom:1px solid #e5e7eb}.admin-map-wrap,#adminMapaOcorrencias{height:600px;min-height:600px}}
@media(max-width:720px){.admin-ocorrencias-map-section{padding:14px}.admin-map-kpis,.admin-map-legenda,.admin-mini-grid{grid-template-columns:1fr}.admin-map-legend{position:static;margin:12px}.admin-map-floating-card{display:none}}
</style>

<section class="admin-ocorrencias-map-section">
    <div class="admin-map-hero">
        <div>
            <span class="admin-map-kicker"><?= isOperador() ? '● Mapa das minhas ocorrências' : '● Mapa operacional interno' ?></span>
            <h2>Ocorrências no mapa</h2>
            <p><?= isOperador() ? 'Visualize no mapa apenas as ocorrências atribuídas à sua conta.' : 'Visualize todas as ocorrências georreferenciadas, filtre por estado/categoria e abra rapidamente cada registo.' ?></p>
        </div>
        <div class="admin-map-kpis">
            <div><strong><?= $totalMapa ?></strong><small>Total</small></div>
            <div><strong><?= $totalMapaPendentes ?></strong><small>Pendentes</small></div>
            <div><strong><?= $totalMapaAnalise ?></strong><small>Em análise</small></div>
            <div><strong><?= $totalMapaResolvidas ?></strong><small>Resolvidas</small></div>
        </div>
    </div>

    <div class="admin-map-shell">
        <aside class="admin-map-sidebar">
            <div class="admin-map-sidebar-head"><h3>Centro operacional</h3><p>Filtros rápidos, feed lateral e mapa com os pontos das ocorrências.</p></div>
            <div class="admin-map-legenda">
                <span><i class="dot dot-pendente"></i> Pendente</span><span><i class="dot dot-analise"></i> Em análise</span><span><i class="dot dot-resolvido"></i> Resolvido</span><span><i class="dot dot-arquivado"></i> Arquivado</span>
            </div>
            <div class="admin-map-filter-box">
                <h3>Filtros do mapa</h3>
                <input type="text" id="adminPesquisaOcorrencias" placeholder="Pesquisar por assunto, código, local...">
                <select id="adminFiltroCategoria"><option value="">Todas as categorias</option><?php foreach ($categoriasMapa as $cat): ?><option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option><?php endforeach; ?></select>
                <select id="adminFiltroEstado"><option value="">Todos os estados</option><option value="pendente">Pendente</option><option value="em_analise">Em análise</option><option value="resolvido">Resolvido</option><option value="arquivado">Arquivado</option></select>
                <button type="button" class="btn secondary" id="adminLimparFiltrosMapa">Limpar filtros</button>
            </div>
            <div class="admin-map-list-title"><strong>Feed operacional</strong><small id="adminContadorOcorrencias"><?= count($pedidosMapa) ?></small></div>
            <div class="admin-ocorrencias-list" id="adminListaOcorrencias"></div>
        </aside>
        <div class="admin-map-wrap">
            <div class="admin-map-legend"><strong>Legenda</strong><span><i class="dot dot-pendente"></i> Pendente</span><span><i class="dot dot-analise"></i> Em análise</span><span><i class="dot dot-resolvido"></i> Resolvido</span><span><i class="dot dot-arquivado"></i> Arquivado</span></div>
            <div class="admin-map-floating-card"><strong>Mapa interno</strong><p>Use este mapa para perceber rapidamente onde estão as ocorrências e abrir o detalhe no backoffice.</p></div>
            <div id="adminMapaOcorrencias"></div>
        </div>
    </div>

    <div class="admin-map-stats">
        <div class="admin-map-stat-panel"><h3>Resumo operacional</h3><div class="admin-progress-label"><span>Taxa de resolução</span><span><?= $percentagemMapaResolvidas ?>%</span></div><div class="admin-progress-track"><div class="admin-progress-fill" style="width:<?= $percentagemMapaResolvidas ?>%"></div></div></div>
        <div class="admin-map-stat-panel"><h3>Estado atual</h3><div class="admin-mini-grid"><div class="admin-mini-card"><strong><?= $totalMapaPendentes ?></strong><span>Pendentes</span></div><div class="admin-mini-card"><strong><?= $totalMapaAnalise ?></strong><span>Em tratamento</span></div><div class="admin-mini-card"><strong><?= $totalMapaResolvidas ?></strong><span>Resolvidas</span></div><div class="admin-mini-card"><strong><?= $totalMapaArquivadas ?></strong><span>Arquivadas</span></div></div></div>
    </div>
</section>

<script>const adminOcorrencias = <?= json_encode($pedidosMapa, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mapaDiv = document.getElementById('adminMapaOcorrencias');
    if (!mapaDiv || typeof L === 'undefined') return;
    const mapa = L.map('adminMapaOcorrencias').setView([<?= $centroMapa["lat"] ?>, <?= $centroMapa["lng"] ?>], <?= $centroMapa["zoom"] ?>);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '&copy; OpenStreetMap'}).addTo(mapa);
    const cluster = L.markerClusterGroup(); mapa.addLayer(cluster);
    const inputPesquisa = document.getElementById('adminPesquisaOcorrencias');
    const filtroEstado = document.getElementById('adminFiltroEstado');
    const filtroCategoria = document.getElementById('adminFiltroCategoria');
    const limparBtn = document.getElementById('adminLimparFiltrosMapa');
    const contador = document.getElementById('adminContadorOcorrencias');
    const listaOcorrencias = document.getElementById('adminListaOcorrencias');
    let markersPorId = {};
    function corEstado(estado){ if(estado==='resolvido') return '#2b8a3e'; if(estado==='em_analise') return '#2563eb'; if(estado==='arquivado') return '#6b7280'; return '#f59f00';}
    function textoEstado(estado){ if(estado==='resolvido') return 'Resolvido'; if(estado==='em_analise') return 'Em análise'; if(estado==='arquivado') return 'Arquivado'; return 'Pendente';}
    function limparTexto(valor){ if(!valor) return '-'; return String(valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
    function normalizar(valor){ return String(valor||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');}
    function criarMarker(o){
        const lat=parseFloat(o.latitude), lng=parseFloat(o.longitude); if(!lat || !lng) return null;
        const marker=L.circleMarker([lat,lng],{radius:10,color:corEstado(o.estado),fillColor:corEstado(o.estado),fillOpacity:.88,weight:3});
        const mensagem=limparTexto(o.mensagem||'').substring(0,130);
        marker.bindPopup(`<div class="popup-pro"><span class="popup-estado" style="background:${corEstado(o.estado)}">${textoEstado(o.estado)}</span><h3>${limparTexto(o.assunto)}</h3><small>${limparTexto(o.categoria)}</small><div class="popup-morada"><strong>Morada:</strong><br>${limparTexto(o.localizacao)}</div><p>${mensagem}...</p><a href="ver_pedido.php?id=${encodeURIComponent(o.id)}" class="btn">Abrir no backoffice</a></div>`);
        return marker;
    }
    // Limite da freguesia: vem de assets/geo/freguesia.geojson (um ficheiro por site).
    // Antes estava aqui embutido, com centenas de coordenadas — era isso que obrigava
    // este ficheiro a ser diferente em cada freguesia.
    const limiteFreguesia = <?= geojsonFreguesiaJs() ?>;
    const limiteLayer=L.geoJSON(limiteFreguesia,{style:{color:'#ff3b30',weight:4,opacity:1,dashArray:'8,6',fillColor:'#ff3b30',fillOpacity:.06}}).addTo(mapa);
    function renderLista(lista){
        if(!listaOcorrencias) return; listaOcorrencias.innerHTML='';
        lista.slice(0,10).forEach(function(o){
            const item=document.createElement('button'); item.type='button'; item.className='admin-ocorrencia-item';
            item.innerHTML=`<span class="admin-ocorrencia-icon" style="background:${corEstado(o.estado)}">!</span><span><h3>${limparTexto(o.assunto)}</h3><p>${limparTexto(o.localizacao)}</p></span><span><small style="color:${corEstado(o.estado)}">${textoEstado(o.estado)}</small></span>`;
            item.addEventListener('click',function(){ const marker=markersPorId[o.id]; if(marker){ mapa.setView(marker.getLatLng(),16); marker.openPopup(); }});
            listaOcorrencias.appendChild(item);
        });
    }
    function aplicarFiltros(){
        cluster.clearLayers(); markersPorId={};
        const termo=normalizar(inputPesquisa?inputPesquisa.value:''), estado=filtroEstado?filtroEstado.value:'', categoria=filtroCategoria?filtroCategoria.value:'';
        const bounds=[], visiveis=[];
        adminOcorrencias.forEach(function(o){
            const lat=parseFloat(o.latitude), lng=parseFloat(o.longitude); if(!lat || !lng) return;
            const textoPesquisa=normalizar((o.assunto||'')+' '+(o.codigo||'')+' '+(o.localizacao||'')+' '+(o.categoria||'')+' '+(o.mensagem||''));
            if(termo && !textoPesquisa.includes(termo)) return; if(estado && o.estado!==estado) return; if(categoria && o.categoria!==categoria) return;
            const marker=criarMarker(o); if(marker){ cluster.addLayer(marker); markersPorId[o.id]=marker; bounds.push([lat,lng]); visiveis.push(o); }
        });
        if(contador) contador.textContent=visiveis.length; renderLista(visiveis);
        if(bounds.length>0) mapa.fitBounds(bounds,{padding:[45,45],maxZoom:15}); else mapa.fitBounds(limiteLayer.getBounds(),{padding:[45,45]});
        limiteLayer.bringToFront();
    }
    if(inputPesquisa) inputPesquisa.addEventListener('input',aplicarFiltros);
    if(filtroEstado) filtroEstado.addEventListener('change',aplicarFiltros);
    if(filtroCategoria) filtroCategoria.addEventListener('change',aplicarFiltros);
    if(limparBtn) limparBtn.addEventListener('click',function(){ if(inputPesquisa) inputPesquisa.value=''; if(filtroEstado) filtroEstado.value=''; if(filtroCategoria) filtroCategoria.value=''; aplicarFiltros();});
    aplicarFiltros();
    if(adminOcorrencias.length===0) mapa.fitBounds(limiteLayer.getBounds(),{padding:[45,45]});
    setTimeout(function(){ mapa.invalidateSize(); limiteLayer.bringToFront(); },500);
});
</script>

<?php require_once "includes/footer.php"; ?>