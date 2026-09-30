<?php
require_once __DIR__ . "/includes/db.php";

function cpTxt($txt){
    return nl2br(htmlspecialchars($txt ?? ''));
}

// Mesmo mapa de carreiras/categorias/vínculos/estados do admin — ver
// admin/procedimentos-concursais.php para a nota sobre a terminologia legal.
$carreiras = [
    'tecnico_superior' => 'Técnico Superior',
    'assistente_tecnico' => 'Assistente Técnico',
    'assistente_operacional' => 'Assistente Operacional',
];
$categoriasPorCarreira = [
    'tecnico_superior' => ['tecnico_superior' => 'Técnico Superior'],
    'assistente_tecnico' => [
        'coordenador_tecnico' => 'Coordenador Técnico',
        'assistente_tecnico' => 'Assistente Técnico',
    ],
    'assistente_operacional' => [
        'encarregado_geral_operacional' => 'Encarregado Geral Operacional',
        'encarregado_operacional' => 'Encarregado Operacional',
        'assistente_operacional' => 'Assistente Operacional',
    ],
];
$tiposVinculo = [
    'tempo_indeterminado' => 'Contrato de Trabalho em Funções Públicas por Tempo Indeterminado',
    'termo_certo' => 'Contrato de Trabalho em Funções Públicas a Termo Resolutivo Certo',
    'termo_incerto' => 'Contrato de Trabalho em Funções Públicas a Termo Resolutivo Incerto',
];
$estados = [
    'rascunho' => 'Rascunho',
    'publicado' => 'Publicado',
    'encerrado' => 'Encerrado',
    'arquivado' => 'Arquivado',
];

$filtroEstado = $_GET['estado'] ?? '';
$filtroCarreira = $_GET['carreira'] ?? '';
$filtroAno = $_GET['ano'] ?? '';
$pesquisa = trim($_GET['q'] ?? '');

// Rascunhos nunca são públicos, independentemente do filtro escolhido.
$where = ["ativo = 1", "estado != 'rascunho'"];
$params = [];

if ($filtroEstado !== '' && $filtroEstado !== 'rascunho') { $where[] = "estado = ?"; $params[] = $filtroEstado; }
if ($filtroCarreira !== '') { $where[] = "carreira = ?"; $params[] = $filtroCarreira; }
if ($filtroAno !== '') { $where[] = "YEAR(data_publicacao) = ?"; $params[] = $filtroAno; }
if ($pesquisa !== '') {
    $where[] = "(titulo LIKE ? OR referencia LIKE ? OR descricao LIKE ?)";
    $params[] = "%{$pesquisa}%";
    $params[] = "%{$pesquisa}%";
    $params[] = "%{$pesquisa}%";
}

$sql = "SELECT * FROM contratacao_publica WHERE " . implode(" AND ", $where) . " ORDER BY data_publicacao DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$procedimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$anos = $pdo->query("SELECT DISTINCT YEAR(data_publicacao) ano FROM contratacao_publica WHERE ativo=1 AND estado != 'rascunho' AND data_publicacao IS NOT NULL ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);
$carreirasUsadas = $pdo->query("SELECT DISTINCT carreira FROM contratacao_publica WHERE ativo=1 AND estado != 'rascunho' AND carreira IS NOT NULL AND carreira!='' ORDER BY carreira ASC")->fetchAll(PDO::FETCH_COLUMN);

$total = (int)$pdo->query("SELECT COUNT(*) FROM contratacao_publica WHERE ativo=1 AND estado != 'rascunho'")->fetchColumn();
$curso = (int)$pdo->query("SELECT COUNT(*) FROM contratacao_publica WHERE ativo=1 AND estado='publicado'")->fetchColumn();
$concluidos = (int)$pdo->query("SELECT COUNT(*) FROM contratacao_publica WHERE ativo=1 AND estado IN ('encerrado','arquivado')")->fetchColumn();

$anexosPorProcedimento = [];
try {
    $rowsAnexos = $pdo->query("SELECT * FROM contratacao_publica_anexos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsAnexos as $a) {
        $anexosPorProcedimento[(int)$a['procedimento_id']][] = $a;
    }
} catch (Exception $e) {}

require_once __DIR__ . "/includes/header.php";
?>

<style>
.cp-page{background:linear-gradient(180deg,#f8fafc 0%,#fff 52%,#f7f4ef 100%);}
.cp-hero{position:relative;min-height:560px;display:flex;align-items:center;color:white;overflow:hidden;background:linear-gradient(90deg,rgba(17,21,28,.93),rgba(17,21,28,.50)),linear-gradient(0deg,rgba(0,0,0,.28),transparent),url('/assets/img/freguesia-2.jpg') center/cover no-repeat;}
.cp-hero:after{content:"";position:absolute;left:0;right:0;bottom:0;height:150px;background:linear-gradient(0deg,#f8fafc,transparent);}
.cp-hero-inner{position:relative;z-index:2;max-width:900px;padding:90px 0 130px;}
.cp-kicker{display:inline-flex;align-items:center;gap:8px;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.38);color:#F0D060;border-radius:999px;padding:10px 16px;font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.9px;margin-bottom:18px;}
.cp-hero h1{font-size:clamp(42px,6vw,78px);line-height:1;margin:0 0 22px;letter-spacing:-1.5px;}
.cp-hero p{font-size:21px;line-height:1.75;color:#dbeafe;margin:0;max-width:780px;}
.cp-shell{position:relative;z-index:4;margin-top:-72px;}
.cp-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px;}
.cp-stat{background:white;border:1px solid #e5e7eb;border-radius:28px;padding:26px;box-shadow:0 18px 50px rgba(15,23,42,.09);}
.cp-stat strong{display:block;color:#242A30;font-size:42px;line-height:1;}
.cp-stat span{display:block;color:#64748b;font-weight:900;margin-top:8px;}
.cp-filter{background:white;border:1px solid #e5e7eb;border-radius:30px;padding:22px;box-shadow:0 18px 50px rgba(15,23,42,.08);display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr auto auto;gap:12px;align-items:center;margin-bottom:26px;}
.cp-filter input,.cp-filter select{height:54px;border:1px solid #dbe4ee;background:#f8fafc;border-radius:16px;padding:0 14px;font-weight:800;color:#11151B;width:100%;box-sizing:border-box;}
.cp-filter select{appearance:none;-webkit-appearance:none;-moz-appearance:none;padding-right:38px;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%2311151B'><path d='M4 6l4 4 4-4'/></svg>");background-repeat:no-repeat;background-position:right 14px center;background-size:14px;}
.cp-anexos-list{display:flex;flex-direction:column;gap:8px;}
.cp-anexo-link{display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e5e7eb;color:#11151B!important;text-decoration:none;border-radius:14px;padding:11px 13px;font-weight:800;font-size:13px;transition:.2s;}
.cp-anexo-link:hover{background:var(--cor-principal);color:white!important;border-color:var(--cor-principal);}
.cp-anexo-link span{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cp-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--cor-principal);color:white!important;text-decoration:none;border:0;border-radius:16px;padding:16px 18px;font-weight:900;cursor:pointer;min-height:54px;}
.cp-btn.secondary{background:white;color:#11151B!important;border:1px solid #e5e7eb;}
.cp-section-head{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:20px;}
.cp-section-head h2{font-size:38px;color:#11151B;margin:0;}
.cp-count{background:#eef2ff;color:#242A30;border-radius:999px;padding:11px 16px;font-weight:900;}
.cp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-bottom:50px;}
.cp-card{background:white;border:1px solid #e5e7eb;border-radius:30px;box-shadow:0 18px 50px rgba(15,23,42,.08);overflow:hidden;transition:.24s;}
.cp-card:hover{transform:translateY(-5px);box-shadow:0 26px 70px rgba(15,23,42,.13);}
.cp-card-top{padding:24px 24px 18px;display:flex;gap:16px;align-items:flex-start;}
.cp-icon{width:64px;height:64px;border-radius:20px;background:#fee2e2;color:#c92a2a;display:grid;place-items:center;font-size:18px;font-weight:900;box-shadow:0 14px 30px rgba(201,42,42,.16);flex-shrink:0;}
.cp-card h3{color:#11151B;font-size:23px;margin:0 0 8px;line-height:1.2;}
.cp-badges{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:10px;}
.cp-badge{display:inline-flex;background:#eef2ff;color:#242A30;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:900;text-transform:uppercase;}
.cp-badge.open{background:#dcfce7;color:#166534;}
.cp-badge.done{background:#e0f2fe;color:#075985;}
.cp-desc{padding:0 24px 20px;color:#64748b;line-height:1.65;min-height:72px;}
.cp-meta{border-top:1px solid #eef2f7;background:#f8fafc;padding:16px 24px;display:grid;gap:8px;color:#475569;font-weight:800;}
.cp-card-actions{padding:18px 24px 24px;}
.cp-download{display:flex;justify-content:center;align-items:center;background:var(--cor-principal);color:white;text-decoration:none;border-radius:15px;padding:13px 14px;font-weight:900;}
.cp-empty{background:white;border:1px dashed #cbd5e1;border-radius:26px;padding:30px;color:#64748b;font-weight:900;text-align:center;margin-bottom:40px;}
@media(max-width:1150px){.cp-filter{grid-template-columns:1fr 1fr}.cp-grid{grid-template-columns:1fr 1fr}}
@media(max-width:760px){.cp-hero{min-height:520px}.cp-hero-inner{padding:70px 0 115px}.cp-hero p{font-size:17px}.cp-stats,.cp-filter,.cp-grid{grid-template-columns:1fr}.cp-stat{width:240px;margin:0 auto;text-align:center;padding:20px 18px;border-radius:22px}.cp-stat strong{font-size:42px}.cp-section-head{display:block}.cp-count{display:inline-flex;margin-top:12px}}
</style>

<main class="cp-page">
    <section class="cp-hero">
        <div class="container">
            <div class="cp-hero-inner">
                <span class="cp-kicker"><i class="bi bi-briefcase"></i> Recursos Humanos</span>
                <h1>Procedimentos Concursais</h1>
                <p>Consulte os procedimentos concursais em curso e concluídos para recrutamento de
                    pessoal desta freguesia — avisos, critérios de seleção, resultados e documentos
                    associados a cada procedimento.</p>
            </div>
        </div>
    </section>

    <section class="container cp-shell">
        <div class="cp-stats">
            <div class="cp-stat"><strong><?= $total ?></strong><span>procedimentos publicados</span></div>
            <div class="cp-stat"><strong><?= $curso ?></strong><span>em curso</span></div>
            <div class="cp-stat"><strong><?= $concluidos ?></strong><span>concluídos</span></div>
        </div>

        <form class="cp-filter" method="GET">
            <input type="text" name="q" value="<?= htmlspecialchars($pesquisa) ?>" placeholder="Pesquisar por título, referência ou descrição...">
            <select name="carreira">
                <option value="">Todas as carreiras</option>
                <?php foreach($carreirasUsadas as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $filtroCarreira===$c?'selected':'' ?>><?= htmlspecialchars($carreiras[$c] ?? $c) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="estado">
                <option value="">Todos os estados</option>
                <?php foreach(['publicado','encerrado','arquivado'] as $e): ?>
                    <option value="<?= htmlspecialchars($e) ?>" <?= $filtroEstado===$e?'selected':'' ?>><?= htmlspecialchars($estados[$e]) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="ano">
                <option value="">Todos os anos</option>
                <?php foreach($anos as $a): ?>
                    <option value="<?= htmlspecialchars($a) ?>" <?= $filtroAno==$a?'selected':'' ?>><?= htmlspecialchars($a) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="cp-btn" type="submit">Filtrar</button>
            <a class="cp-btn secondary" href="/procedimentos-concursais.php">Limpar</a>
        </form>

        <div class="cp-section-head">
            <h2>Procedimentos publicados</h2>
            <span class="cp-count"><?= count($procedimentos) ?> resultado(s)</span>
        </div>

        <?php if(!empty($procedimentos)): ?>
            <div class="cp-grid">
                <?php foreach($procedimentos as $p): ?>
                    <article class="cp-card">
                        <div class="cp-card-top">
                            <div class="cp-icon">PDF</div>
                            <div>
                                <div class="cp-badges">
                                    <?php if(!empty($p['carreira'])): ?><span class="cp-badge"><?= htmlspecialchars($carreiras[$p['carreira']] ?? $p['carreira']) ?></span><?php endif; ?>
                                    <?php if(!empty($p['categoria'])): ?><span class="cp-badge"><?= htmlspecialchars(($categoriasPorCarreira[$p['carreira']] ?? [])[$p['categoria']] ?? $p['categoria']) ?></span><?php endif; ?>
                                    <?php if(!empty($p['estado'])): ?><span class="cp-badge <?= $p['estado']==='publicado'?'open':($p['estado']==='encerrado'||$p['estado']==='arquivado'?'done':'') ?>"><?= htmlspecialchars($estados[$p['estado']] ?? $p['estado']) ?></span><?php endif; ?>
                                </div>
                                <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                            </div>
                        </div>
                        <div class="cp-desc"><?= cpTxt(mb_substr($p['descricao'] ?? '', 0, 180)) ?></div>
                        <div class="cp-meta">
                            <?php if(!empty($p['referencia'])): ?><span><i class="bi bi-hash"></i> Referência <?= htmlspecialchars($p['referencia']) ?></span><?php endif; ?>
                            <?php if(!empty($p['vagas'])): ?><span><i class="bi bi-people"></i> <?= (int)$p['vagas'] ?> posto(s) de trabalho</span><?php endif; ?>
                            <?php if(!empty($p['tipo_vinculo'])): ?><span><i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($tiposVinculo[$p['tipo_vinculo']] ?? $p['tipo_vinculo']) ?></span><?php endif; ?>
                            <?php if(!empty($p['data_publicacao'])): ?><span><i class="bi bi-calendar-event"></i> Publicado em <?= date('d/m/Y', strtotime($p['data_publicacao'])) ?></span><?php endif; ?>
                            <?php if(!empty($p['inicio_candidaturas']) || !empty($p['fim_candidaturas'])): ?>
                                <span><i class="bi bi-calendar-range"></i> Candidaturas
                                    <?= !empty($p['inicio_candidaturas']) ? date('d/m/Y', strtotime($p['inicio_candidaturas'])) : '—' ?>
                                    a
                                    <?= !empty($p['fim_candidaturas']) ? date('d/m/Y', strtotime($p['fim_candidaturas'])) : '—' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="cp-card-actions">
                            <?php $anexosProc = $anexosPorProcedimento[(int)$p['id']] ?? []; ?>
                            <?php if(!empty($anexosProc)): ?>
                                <div class="cp-anexos-list">
                                    <?php foreach($anexosProc as $a): ?>
                                        <a class="cp-anexo-link" href="/uploads/contratacao-publica/<?= htmlspecialchars($a['ficheiro']) ?>" target="_blank">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span><?= htmlspecialchars($a['ficheiro_original'] ?: $a['ficheiro']) ?></span>
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif(!empty($p['ficheiro'])): ?>
                                <a class="cp-download" href="/download.php?tipo=contratacao_publica&id=<?= (int) $p['id'] ?>" target="_blank">Ver / Descarregar</a>
                            <?php else: ?>
                                <span class="cp-download" style="background:#94a3b8;">Sem ficheiro</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cp-empty">Ainda não existem procedimentos publicados para os filtros selecionados.</div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
