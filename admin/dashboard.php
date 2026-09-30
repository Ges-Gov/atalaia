<?php
$adminPageTitle = "Dashboard";
$adminActive = "dashboard";

require_once "includes/auth.php";
require_once "../includes/db.php";

/* Ícone/cor/título de um evento do feed operacional — usado tanto no
   carregamento inicial da página como na atualização automática via AJAX,
   para as duas versões nunca poderem divergir uma da outra.
   $iconeClasse é só o nome da classe do Bootstrap Icons (ex.: "bi-person-gear"),
   nunca HTML — o feed_live devolve JSON e o JS monta os elementos por DOM,
   sem inserir HTML vindo do servidor diretamente na página. */
function dashFeedIcone($tipo, $estado, $prioridade) {
    $iconeClasse = 'bi-pin-angle';
    $cor = 'var(--cor-principal)';
    $titulo = 'Atividade operacional';

    switch ($tipo) {
        case 'atribuicao_operador':
            $iconeClasse = 'bi-person-gear'; $cor = '#7c3aed'; $titulo = 'Operador atribuído';
            break;
        case 'estado_ocorrencia':
            $iconeClasse = 'bi-arrow-repeat'; $cor = '#2563eb'; $titulo = 'Estado atualizado';
            break;
        case 'chat_admin':
            $iconeClasse = 'bi-chat-dots'; $cor = '#0d9488'; $titulo = 'Nova resposta no chat';
            break;
        case 'prioridade':
            $iconeClasse = 'bi-exclamation-octagon-fill'; $cor = '#dc2626'; $titulo = 'Prioridade atualizada';
            break;
        case 'novo_pedido':
            $iconeClasse = 'bi-envelope-paper-fill'; $cor = '#f59f00'; $titulo = 'Nova ocorrência';
            break;
        case 'novo_requerimento':
            $iconeClasse = 'bi-file-earmark-plus-fill'; $cor = '#0ea5e9'; $titulo = 'Novo requerimento — Balcão Virtual';
            break;
    }

    if (($estado ?? '') === 'resolvido') {
        $iconeClasse = 'bi-check-circle-fill'; $cor = '#16a34a';
    }

    if (($prioridade ?? '') === 'critico') {
        $iconeClasse = 'bi-exclamation-octagon-fill'; $cor = '#dc2626';
    }

    return [$iconeClasse, $cor, $titulo];
}

function dashFeedQuery($pdo) {
    return $pdo->query("
        SELECT * FROM (
            SELECT
                l.id, l.admin_id, l.pedido_id, l.tipo, l.mensagem,
                l.criado_em AS data_registo,
                p.codigo, p.assunto, p.estado, p.prioridade,
                u.nome AS admin_nome, u.username AS admin_username,
                'pedido' AS destino
            FROM logs_operacionais l
            LEFT JOIN pedidos_junta p ON p.id = l.pedido_id
            LEFT JOIN admin_utilizadores u ON u.id = l.admin_id

            UNION ALL

            SELECT
                NULL, NULL, p.id, 'novo_pedido',
                CONCAT('Nova ocorrência registada por ', p.nome),
                p.criado_em,
                p.codigo, p.assunto, p.estado, p.prioridade,
                NULL, NULL,
                'pedido'
            FROM pedidos_junta p

            UNION ALL

            SELECT
                NULL, NULL, r.id, 'novo_requerimento',
                CONCAT('Novo requerimento (Balcão Virtual) de ', r.nome),
                r.criado_em,
                r.codigo, r.assunto, r.estado, NULL,
                NULL, NULL,
                'requerimento'
            FROM requerimentos r
        ) feed
        ORDER BY data_registo DESC
        LIMIT 15
    ");
}

function dashFeedParaJson(array $linhas) {
    $itens = [];
    foreach ($linhas as $a) {
        [$iconeClasse, $cor, $titulo] = dashFeedIcone($a['tipo'] ?? '', $a['estado'] ?? '', $a['prioridade'] ?? '');
        $itens[] = [
            'icone_classe' => $iconeClasse,
            'cor' => $cor,
            'titulo' => $titulo,
            'autor' => $a['admin_nome'] ?: ($a['admin_username'] ?: 'Sistema'),
            'mensagem' => $a['mensagem'],
            'assunto' => $a['assunto'] ?: null,
            'codigo' => $a['codigo'] ?: ('#' . $a['pedido_id']),
            'data' => date('d/m H:i', strtotime($a['data_registo'])),
            'pedido_id' => $a['pedido_id'] ? (int) $a['pedido_id'] : null,
            'link' => ($a['destino'] ?? 'pedido') === 'requerimento' ? 'ver_requerimento.php' : 'ver_pedido.php',
        ];
    }
    return $itens;
}

if (isset($_GET['feed_live'])) {
    header('Content-Type: application/json');
    try {
        echo json_encode(dashFeedParaJson(dashFeedQuery($pdo)->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
        echo '[]';
    }
    exit;
}

require_once "includes/header.php";

$atividadeRecente = [];

try {
    $atividadeRecente = dashFeedQuery($pdo)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $atividadeRecente = [];
}

function dashCount($pdo, $sql) {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function dashFetch($pdo, $sql) {
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

$totalNoticias = dashCount($pdo, "SELECT COUNT(*) FROM noticias");
$totalEventos = dashCount($pdo, "SELECT COUNT(*) FROM eventos");
$totalPontos = dashCount($pdo, "SELECT COUNT(*) FROM pontos_interesse");
$totalDocumentos = dashCount($pdo, "SELECT COUNT(*) FROM documentos WHERE ativo = 1");

$totalPedidos = dashCount($pdo, "SELECT COUNT(*) FROM pedidos_junta");
$pendentes = dashCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'pendente'");
$analise = dashCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'em_analise'");
$resolvidos = dashCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'resolvido'");

$totalRequerimentos = dashCount($pdo, "SELECT COUNT(*) FROM requerimentos");
$reqPendentes = dashCount($pdo, "SELECT COUNT(*) FROM requerimentos WHERE estado = 'pendente'");
$reqAnalise = dashCount($pdo, "SELECT COUNT(*) FROM requerimentos WHERE estado = 'em_analise'");
$reqConcluidos = dashCount($pdo, "SELECT COUNT(*) FROM requerimentos WHERE estado IN ('deferido','concluido')");
$reqAtrasados = dashCount($pdo, "
    SELECT COUNT(*)
    FROM requerimentos r
    LEFT JOIN balcao_documentos d ON d.id = r.documento_balcao_id
    WHERE r.estado NOT IN ('deferido', 'indeferido', 'concluido')
    AND d.prazo_dias IS NOT NULL
    AND r.criado_em < DATE_SUB(NOW(), INTERVAL d.prazo_dias DAY)
");

$totalMarcacoes = dashCount($pdo, "SELECT COUNT(*) FROM marcacoes_atendimento");
$marcPendentes = dashCount($pdo, "SELECT COUNT(*) FROM marcacoes_atendimento WHERE estado = 'pendente'");
$marcConfirmadas = dashCount($pdo, "SELECT COUNT(*) FROM marcacoes_atendimento WHERE estado = 'confirmada'");

$notificacoesAtivas = dashCount($pdo, "SELECT COUNT(*) FROM notificacoes WHERE ativo = 1");

$ultimosPedidos = dashFetch($pdo, "
    SELECT * FROM pedidos_junta
    ORDER BY criado_em DESC
    LIMIT 5
");

$ultimosRequerimentos = dashFetch($pdo, "
    SELECT * FROM requerimentos
    ORDER BY criado_em DESC
    LIMIT 5
");

$ultimasMarcacoes = dashFetch($pdo, "
    SELECT * FROM marcacoes_atendimento
    ORDER BY criado_em DESC
    LIMIT 5
");

$pedidosMes = dashFetch($pdo, "
    SELECT DATE_FORMAT(criado_em, '%m/%Y') AS mes, COUNT(*) AS total
    FROM pedidos_junta
    GROUP BY YEAR(criado_em), MONTH(criado_em)
    ORDER BY YEAR(criado_em), MONTH(criado_em)
    LIMIT 12
");

$requerimentosMes = dashFetch($pdo, "
    SELECT DATE_FORMAT(criado_em, '%m/%Y') AS mes, COUNT(*) AS total
    FROM requerimentos
    GROUP BY YEAR(criado_em), MONTH(criado_em)
    ORDER BY YEAR(criado_em), MONTH(criado_em)
    LIMIT 12
");

$pedidosEstado = dashFetch($pdo, "
    SELECT estado, COUNT(*) AS total
    FROM pedidos_junta
    GROUP BY estado
");

$requerimentosEstado = dashFetch($pdo, "
    SELECT estado, COUNT(*) AS total
    FROM requerimentos
    GROUP BY estado
");

$categoriasPedidos = dashFetch($pdo, "
    SELECT categoria, COUNT(*) AS total
    FROM pedidos_junta
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 5
");

$taxaResolucao = $totalPedidos > 0 ? round(($resolvidos / $totalPedidos) * 100) : 0;
?>

<div class="dash-insane">

    <div class="dash-hero-insane">
        <div class="dash-hero-info">
            <div class="dash-hero-eyebrow"><i class="bi bi-building"></i> <?= htmlspecialchars(siteConfig('nome_site', 'Backoffice')) ?></div>
            <h1>Painel de Gestão</h1>
            <p>Visão geral do Balcão Virtual, ocorrências, requerimentos e atividade da freguesia.</p>
        </div>

        <div class="dash-hero-kpis">
            <div class="dash-hero-ring">
                <svg viewBox="0 0 36 36">
                    <path class="dash-ring-track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="dash-ring-value" stroke-dasharray="<?= $taxaResolucao ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="dash-hero-ring-label">
                    <strong><?= $taxaResolucao ?>%</strong>
                    <small>Resolução</small>
                </div>
            </div>

            <div class="dash-hero-stat">
                <div class="dash-hero-stat-icon"><i class="bi bi-bell-fill"></i></div>
                <div>
                    <strong><?= (int)$notificacoesAtivas ?></strong>
                    <small>Notificações</small>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-kpi-grid">
        <div class="dash-kpi-card">
            <div class="dash-kpi-icon"><i class="bi bi-envelope-paper"></i></div>
            <small>Pedidos</small>
            <strong><?= (int)$totalPedidos ?></strong>
            <em><?= (int)$pendentes ?> pendente(s)</em>
        </div>

        <div class="dash-kpi-card warning">
            <div class="dash-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <small>Em análise</small>
            <strong><?= (int)$analise ?></strong>
            <em>Ocorrências em tratamento</em>
        </div>

        <div class="dash-kpi-card success">
            <div class="dash-kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
            <small>Resolvidos</small>
            <strong><?= (int)$resolvidos ?></strong>
            <em><?= $taxaResolucao ?>% de resolução</em>
        </div>

        <div class="dash-kpi-card purple">
            <div class="dash-kpi-icon"><i class="bi bi-file-earmark-text"></i></div>
            <small>Requerimentos</small>
            <strong><?= (int)$totalRequerimentos ?></strong>
            <em><?= (int)$reqPendentes ?> pendente(s)</em>
        </div>

        <div class="dash-kpi-card warning">
            <div class="dash-kpi-icon"><i class="bi bi-alarm-fill"></i></div>
            <small>Balcão Virtual</small>
            <strong><?= (int)$reqAtrasados ?></strong>
            <em>fora do prazo definido</em>
        </div>

        <div class="dash-kpi-card blue">
            <div class="dash-kpi-icon"><i class="bi bi-calendar-event"></i></div>
            <small>Marcações</small>
            <strong><?= (int)$totalMarcacoes ?></strong>
            <em><?= (int)$marcConfirmadas ?> confirmada(s)</em>
        </div>

        <div class="dash-kpi-card dark">
            <div class="dash-kpi-icon"><i class="bi bi-archive"></i></div>
            <small>Conteúdos</small>
            <strong><?= (int)($totalNoticias + $totalEventos + $totalDocumentos) ?></strong>
            <em>Notícias, eventos e docs</em>
        </div>
    </div>

    <div class="dash-main-grid">
        <div class="dash-panel-insane large">
            <div class="dash-panel-head">
                <div>
                    <h3>Atividade mensal</h3>
                    <small>Pedidos e requerimentos nos últimos meses</small>
                </div>
            </div>
            <canvas id="graficoAtividadeMensal"></canvas>
        </div>

        <div class="dash-panel-insane">
            <div class="dash-panel-head">
                <div>
                    <h3>Estados dos pedidos</h3>
                    <small>Distribuição atual</small>
                </div>
            </div>
            <canvas id="graficoPedidosEstado"></canvas>
        </div>
    </div>

    <div class="dash-main-grid">
        <div class="dash-panel-insane">
            <div class="dash-panel-head">
                <div>
                    <h3>Estados dos requerimentos</h3>
                    <small>Fluxo documental</small>
                </div>
            </div>
            <canvas id="graficoRequerimentosEstado"></canvas>
        </div>

        <div class="dash-panel-insane">
            <div class="dash-panel-head">
                <div>
                    <h3>Categorias mais reportadas</h3>
                    <small>Top ocorrências</small>
                </div>
            </div>

            <div class="dash-bars">
                <?php
                $maxCat = 1;
                foreach ($categoriasPedidos as $c) {
                    if ($c['total'] > $maxCat) $maxCat = $c['total'];
                }
                ?>

                <?php foreach ($categoriasPedidos as $c): ?>
                    <?php $percent = ($c['total'] / $maxCat) * 100; ?>
                    <div class="dash-bar-item">
                        <div>
                            <strong><?= htmlspecialchars($c['categoria']) ?></strong>
                            <span><?= (int)$c['total'] ?></span>
                        </div>
                        <div class="dash-bar-track">
                            <div class="dash-bar-fill" style="width:<?= $percent ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($categoriasPedidos)): ?>
                    <p>Ainda não existem dados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dash-activity-grid">

        <div class="dash-panel-insane">
            <div class="dash-panel-head">
                <div>
                    <h3>Últimos pedidos</h3>
                    <small>Ocorrências recentes</small>
                </div>
                <a href="pedidos.php">Ver todos</a>
            </div>

            <div class="dash-list-insane">
                <?php foreach ($ultimosPedidos as $p): ?>
                    <div class="dash-list-row">
                        <div>
                            <strong><?= htmlspecialchars($p['assunto']) ?></strong>
                            <span><?= htmlspecialchars($p['nome']) ?> · <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></span>
                        </div>
                        <a href="ver_pedido.php?id=<?= $p['id'] ?>">Ver</a>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($ultimosPedidos)): ?>
                    <p>Ainda não existem pedidos.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel-insane">
            <div class="dash-panel-head">
                <div>
                    <h3>Últimos requerimentos</h3>
                    <small>Documentos submetidos</small>
                </div>
                <a href="requerimentos.php">Ver todos</a>
            </div>

            <div class="dash-list-insane">
                <?php foreach ($ultimosRequerimentos as $r): ?>
                    <div class="dash-list-row">
                        <div>
                            <strong><?= htmlspecialchars($r['assunto']) ?></strong>
                            <span><?= htmlspecialchars($r['codigo']) ?> · <?= htmlspecialchars(str_replace('_', ' ', $r['estado'])) ?></span>
                        </div>
                        <a href="ver_requerimento.php?id=<?= $r['id'] ?>">Ver</a>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($ultimosRequerimentos)): ?>
                    <p>Ainda não existem requerimentos.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel-insane">
            <div class="dash-panel-head">
                <div>
                    <h3>Últimas marcações</h3>
                    <small>Atendimento presencial</small>
                </div>
                <a href="marcacoes.php">Ver todas</a>
            </div>

            <div class="dash-list-insane">
                <?php foreach ($ultimasMarcacoes as $m): ?>
                    <div class="dash-list-row">
                        <div>
                            <strong><?= htmlspecialchars($m['assunto']) ?></strong>
                            <span><?= htmlspecialchars($m['nome']) ?> · <?= date('d/m/Y', strtotime($m['data_marcacao'])) ?> <?= substr($m['hora_marcacao'], 0, 5) ?></span>
                        </div>
                        <a href="ver_marcacao.php?id=<?= $m['id'] ?>">Ver</a>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($ultimasMarcacoes)): ?>
                    <p>Ainda não existem marcações.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<style>
.feed-operacional-panel{position:relative;overflow:hidden;border:1px solid color-mix(in srgb, var(--cor-principal) 10%, transparent);}
.feed-operacional-panel::before{content:"";position:absolute;width:320px;height:320px;border-radius:50%;right:-150px;top:-170px;background:color-mix(in srgb, var(--cor-principal) 5%, transparent);pointer-events:none;}
.feed-operacional-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;position:relative;z-index:2;}
.feed-operacional-live{display:inline-flex;align-items:center;gap:8px;background:#dcfce7;color:#166534;padding:8px 12px;border-radius:999px;font-weight:900;font-size:12px;text-transform:uppercase;}
.feed-operacional-live i{width:9px;height:9px;background:#22c55e;border-radius:50%;display:inline-block;animation:pulseLive 1.4s infinite;}

.feed-operacional-list{
    display:grid;
    gap:12px;
    margin-top:18px;
    position:relative;
    z-index:2;
    max-height:420px;
    overflow-y:auto;
    padding-right:8px;
}

.feed-operacional-list::-webkit-scrollbar{
    width:7px;
}

.feed-operacional-list::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:999px;
}

.feed-operacional-item{display:grid;grid-template-columns:50px 1fr auto;gap:14px;align-items:center;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:14px;transition:.2s ease;}
.feed-operacional-item:hover{background:white;box-shadow:0 10px 26px rgba(15,23,42,.06);}
.feed-operacional-icon{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;color:white;font-size:19px;}
.feed-operacional-content strong{display:block;color:var(--tema-admin-escuro);font-size:14.5px;margin-bottom:4px;}
.feed-operacional-content span{display:block;color:#64748b;font-size:13px;line-height:1.45;}
.feed-operacional-meta{text-align:right;display:grid;gap:6px;}
.feed-operacional-meta time{color:#64748b;font-weight:800;font-size:12px;}
.feed-operacional-meta a{text-decoration:none;background:var(--cor-principal);color:white;padding:7px 10px;border-radius:999px;font-size:12px;font-weight:800;}
@keyframes pulseLive{0%{box-shadow:0 0 0 0 rgba(34,197,94,.7);}70%{box-shadow:0 0 0 9px rgba(34,197,94,0);}100%{box-shadow:0 0 0 0 rgba(34,197,94,0);}}
@media(max-width:760px){.feed-operacional-item{grid-template-columns:42px 1fr;}.feed-operacional-meta{grid-column:1/-1;text-align:left;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;}}
</style>

<div class="dash-panel-insane feed-operacional-panel" style="margin-top:24px;">
    <div class="dash-panel-head feed-operacional-top">
        <div>
            <h3>Central Operacional Live</h3>
            <small>Atividade real dos admins, operadores e ocorrências</small>
        </div>
        <div class="feed-operacional-live"><i></i> LIVE</div>
    </div>

    <div class="feed-operacional-list" id="feedOperacionalLive">
        <?php if (!empty($atividadeRecente)): ?>
            <?php foreach (dashFeedParaJson($atividadeRecente) as $item): ?>
                <div class="feed-operacional-item">
                    <div class="feed-operacional-icon" style="background:<?= htmlspecialchars($item['cor']) ?>"><i class="bi <?= htmlspecialchars($item['icone_classe']) ?>"></i></div>
                    <div class="feed-operacional-content">
                        <strong><?= htmlspecialchars($item['titulo']) ?></strong>
                        <span>
                            <b><?= htmlspecialchars($item['autor']) ?></b>
                            · <?= htmlspecialchars($item['mensagem']) ?>
                            <?php if (!empty($item['assunto'])): ?>
                                <br><small><?= htmlspecialchars($item['codigo']) ?> — <?= htmlspecialchars($item['assunto']) ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="feed-operacional-meta">
                        <time><?= htmlspecialchars($item['data']) ?></time>
                        <?php if (!empty($item['pedido_id'])): ?>
                            <a href="<?= htmlspecialchars($item['link']) ?>?id=<?= (int) $item['pedido_id'] ?>">Abrir</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Sem atividade operacional ainda. Quando atribuíres operadores, mudares estados ou responderes no chat, vai aparecer aqui.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const pedidosMesLabels = <?= json_encode(array_column($pedidosMes, 'mes')) ?>;
const pedidosMesDados = <?= json_encode(array_map('intval', array_column($pedidosMes, 'total'))) ?>;

const requerimentosMesLabels = <?= json_encode(array_column($requerimentosMes, 'mes')) ?>;
const requerimentosMesDados = <?= json_encode(array_map('intval', array_column($requerimentosMes, 'total'))) ?>;

const labelsMensais = [...new Set([...pedidosMesLabels, ...requerimentosMesLabels])];

function dadosPorLabel(labelsBase, dadosBase, labelsFinal) {
    return labelsFinal.map(label => {
        const index = labelsBase.indexOf(label);
        return index >= 0 ? dadosBase[index] : 0;
    });
}

const pedidosEstadoLabels = <?= json_encode(array_map(function($e) {
    return ucfirst(str_replace('_', ' ', $e['estado']));
}, $pedidosEstado)) ?>;

const pedidosEstadoDados = <?= json_encode(array_map('intval', array_column($pedidosEstado, 'total'))) ?>;

const requerimentosEstadoLabels = <?= json_encode(array_map(function($e) {
    return ucfirst(str_replace('_', ' ', $e['estado']));
}, $requerimentosEstado)) ?>;

const requerimentosEstadoDados = <?= json_encode(array_map('intval', array_column($requerimentosEstado, 'total'))) ?>;

new Chart(document.getElementById('graficoAtividadeMensal'), {
    type: 'line',
    data: {
        labels: labelsMensais,
        datasets: [
            {
                label: 'Pedidos',
                data: dadosPorLabel(pedidosMesLabels, pedidosMesDados, labelsMensais),
                borderWidth: 3,
                tension: 0.35,
                fill: true
            },
            {
                label: 'Requerimentos',
                data: dadosPorLabel(requerimentosMesLabels, requerimentosMesDados, labelsMensais),
                borderWidth: 3,
                tension: 0.35,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});

new Chart(document.getElementById('graficoPedidosEstado'), {
    type: 'doughnut',
    data: {
        labels: pedidosEstadoLabels,
        datasets: [{
            data: pedidosEstadoDados,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

new Chart(document.getElementById('graficoRequerimentosEstado'), {
    type: 'bar',
    data: {
        labels: requerimentosEstadoLabels,
        datasets: [{
            label: 'Requerimentos',
            data: requerimentosEstadoDados,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const feed = document.getElementById("feedOperacionalLive");
    if (!feed) return;

    function criarItemFeed(item) {
        const div = document.createElement("div");
        div.className = "feed-operacional-item";

        const iconeWrap = document.createElement("div");
        iconeWrap.className = "feed-operacional-icon";
        iconeWrap.style.background = item.cor;
        const icone = document.createElement("i");
        icone.className = "bi " + item.icone_classe;
        iconeWrap.appendChild(icone);

        const conteudo = document.createElement("div");
        conteudo.className = "feed-operacional-content";
        const titulo = document.createElement("strong");
        titulo.textContent = item.titulo;
        const linha = document.createElement("span");
        const autor = document.createElement("b");
        autor.textContent = item.autor;
        linha.appendChild(autor);
        linha.appendChild(document.createTextNode(" · " + item.mensagem));
        if (item.assunto) {
            linha.appendChild(document.createElement("br"));
            const small = document.createElement("small");
            small.textContent = item.codigo + " — " + item.assunto;
            linha.appendChild(small);
        }
        conteudo.appendChild(titulo);
        conteudo.appendChild(linha);

        const meta = document.createElement("div");
        meta.className = "feed-operacional-meta";
        const tempo = document.createElement("time");
        tempo.textContent = item.data;
        meta.appendChild(tempo);
        if (item.pedido_id) {
            const link = document.createElement("a");
            link.href = item.link + "?id=" + item.pedido_id;
            link.textContent = "Abrir";
            meta.appendChild(link);
        }

        div.appendChild(iconeWrap);
        div.appendChild(conteudo);
        div.appendChild(meta);
        return div;
    }

    setInterval(function () {
        fetch("dashboard.php?feed_live=1")
            .then(res => res.json())
            .then(itens => {
                feed.replaceChildren(...itens.map(criarItemFeed));
            })
            .catch(() => {});
    }, 5000);
});
</script>

<?php require_once "includes/footer.php"; ?>
