<?php require_once "includes/header.php"; ?>

<?php
function safeCount($pdo, $sql) {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function safeFetch($pdo, $sql) {
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

$total = safeCount($pdo, "SELECT COUNT(*) FROM pedidos_junta");
$pendentes = safeCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado='pendente'");
$analise = safeCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado='em_analise'");
$resolvidos = safeCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado='resolvido'");
$arquivados = safeCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado='arquivado'");

$totalRequerimentos = safeCount($pdo, "SELECT COUNT(*) FROM requerimentos");
$totalMarcacoes = safeCount($pdo, "SELECT COUNT(*) FROM marcacoes_atendimento");

$taxaResolucao = $total > 0 ? round(($resolvidos / $total) * 100) : 0;
$taxaPendentes = $total > 0 ? round(($pendentes / $total) * 100) : 0;
$taxaAnalise = $total > 0 ? round(($analise / $total) * 100) : 0;
$taxaArquivados = $total > 0 ? round(($arquivados / $total) * 100) : 0;

$categorias = safeFetch($pdo, "
    SELECT categoria, COUNT(*) AS total
    FROM pedidos_junta
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 6
");

$pedidosMes = safeFetch($pdo, "
    SELECT DATE_FORMAT(criado_em, '%m/%Y') AS mes, COUNT(*) AS total
    FROM pedidos_junta
    GROUP BY YEAR(criado_em), MONTH(criado_em)
    ORDER BY YEAR(criado_em), MONTH(criado_em)
    LIMIT 12
");

$ultimosResolvidos = safeFetch($pdo, "
    SELECT codigo, assunto, categoria, localizacao, atualizado_em
    FROM pedidos_junta
    WHERE estado = 'resolvido'
    ORDER BY atualizado_em DESC
    LIMIT 5
");
?>

<style>
/* TRANSPARÊNCIA PREMIUM - apenas visual */
.transparencia-premium-page{
    background:
        radial-gradient(circle at top left, rgba(212,170,0,.18), transparent 34%),
        radial-gradient(circle at top right, rgba(36,42,50,.16), transparent 36%),
        linear-gradient(180deg,#f7f4ef 0%,#f8fafc 42%,#ffffff 100%);
}

.trans-premium-hero{
    position:relative;
    overflow:hidden;
    padding:92px 0 110px;
    background:
        linear-gradient(135deg, rgba(36,42,50,.98), rgba(17,21,28,.96)),
        url('/assets/img/freguesia-1.jpg') center/cover no-repeat;
    color:white;
}

.trans-premium-hero::before,
.trans-premium-hero::after{
    content:"";
    position:absolute;
    border-radius:999px;
    pointer-events:none;
}

.trans-premium-hero::before{
    width:520px;
    height:520px;
    right:-160px;
    top:-210px;
    background:rgba(212,170,0,.13);
    filter:blur(2px);
}

.trans-premium-hero::after{
    width:420px;
    height:420px;
    left:-170px;
    bottom:-230px;
    background:rgba(255,255,255,.06);
}

.trans-hero-grid{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:minmax(0,1.2fr) 380px;
    gap:42px;
    align-items:center;
}

.trans-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 14px;
    border-radius:999px;
    background:rgba(212,170,0,.16);
    border:1px solid rgba(212,170,0,.36);
    color:#F0D060;
    font-weight:900;
    letter-spacing:.8px;
    text-transform:uppercase;
    font-size:13px;
}

.trans-hero-content h1{
    margin:20px 0 18px;
    font-size:clamp(40px,6vw,72px);
    line-height:1.02;
    letter-spacing:-1.8px;
}

.trans-hero-content p{
    color:#dbeafe;
    font-size:20px;
    line-height:1.8;
    max-width:780px;
    margin:0;
}

.trans-hero-actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:30px;
}

.trans-hero-actions .btn{
    border-radius:16px;
    padding:14px 18px;
    box-shadow:0 16px 34px rgba(0,0,0,.18);
}

.trans-score-orb{
    position:relative;
    min-height:380px;
    display:grid;
    place-items:center;
}

.trans-score-card{
    width:310px;
    min-height:310px;
    border-radius:50%;
    display:grid;
    place-items:center;
    text-align:center;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.22);
    box-shadow:0 32px 80px rgba(0,0,0,.28), inset 0 0 0 18px rgba(255,255,255,.035);
    backdrop-filter:blur(14px);
    animation:transFloat 5s ease-in-out infinite;
}

.trans-score-card strong{
    display:block;
    font-size:76px;
    line-height:1;
    color:var(--cor-secundaria);
    letter-spacing:-3px;
}

.trans-score-card span{
    display:block;
    color:white;
    font-weight:900;
    font-size:18px;
    margin-top:8px;
}

.trans-score-card small{
    display:block;
    color:#dbeafe;
    max-width:210px;
    margin:12px auto 0;
    line-height:1.5;
}

.trans-orb-badge{
    position:absolute;
    z-index:3;
    background:white;
    color:#11151B;
    border-radius:18px;
    padding:13px 15px;
    box-shadow:0 18px 45px rgba(0,0,0,.22);
    font-weight:900;
}

.trans-orb-badge b{
    color:var(--cor-principal);
    font-size:22px;
    margin-right:4px;
}

.trans-orb-badge.one{top:28px;left:0;}
.trans-orb-badge.two{right:0;bottom:42px;}

@keyframes transFloat{
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-10px)}
}

.trans-dashboard-wrap{
    margin-top:-64px;
    position:relative;
    z-index:4;
}

.trans-live-strip{
    background:rgba(255,255,255,.96);
    backdrop-filter:blur(12px);
    border:1px solid rgba(229,231,235,.9);
    border-radius:28px;
    padding:18px;
    box-shadow:0 22px 65px rgba(0,0,0,.12);
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:24px;
}

.trans-live-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:16px;
    border-radius:22px;
    background:#f8fafc;
    border:1px solid #eef2f7;
}

.trans-live-icon{
    width:46px;
    height:46px;
    border-radius:16px;
    background:var(--cor-principal);
    color:white;
    display:grid;
    place-items:center;
    font-size:22px;
    flex-shrink:0;
}

.trans-live-item strong,
.trans-live-item span{
    display:block;
}

.trans-live-item strong{
    color:#11151B;
    font-size:22px;
    line-height:1.05;
}

.trans-live-item span{
    color:#64748b;
    font-weight:800;
    font-size:13px;
    margin-top:3px;
}

.trans-section-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:22px;
    margin:34px 0 22px;
}

.trans-section-head span{
    color:var(--cor-principal);
    text-transform:uppercase;
    font-weight:900;
    letter-spacing:.8px;
    font-size:13px;
}

.trans-section-head h2{
    color:#11151B;
    font-size:38px;
    margin:8px 0 0;
    letter-spacing:-.7px;
}

.trans-section-head p{
    max-width:620px;
    margin:0;
    color:#64748b;
    line-height:1.7;
}

.transparencia-grid.trans-grid-premium{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:18px;
    margin-bottom:28px;
}

.trans-card{
    position:relative;
    overflow:hidden;
    background:white;
    border:1px solid #eef2f7;
    border-radius:28px;
    padding:24px;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
    transition:.28s ease;
}

.trans-card::before{
    content:"";
    position:absolute;
    inset:auto -20px -45px auto;
    width:120px;
    height:120px;
    border-radius:50%;
    background:rgba(36,42,50,.07);
}

.trans-card:hover{
    transform:translateY(-8px);
    box-shadow:0 26px 70px rgba(0,0,0,.13);
}

.trans-card span{
    width:54px;
    height:54px;
    display:grid;
    place-items:center;
    border-radius:18px;
    background:#f1f5f9;
    font-size:26px;
    margin-bottom:18px;
}

.trans-card strong{
    display:block;
    font-size:42px;
    line-height:1;
    color:var(--cor-principal);
    letter-spacing:-1.5px;
}

.trans-card p{
    margin:9px 0 0;
    color:#64748b;
    font-weight:900;
}

.trans-card.warning span{background:#fff3bf;}
.trans-card.warning strong{color:#f59f00;}
.trans-card.info span{background:#dbeafe;}
.trans-card.info strong{color:#2563eb;}
.trans-card.success span{background:#dcfce7;}
.trans-card.success strong{color:#16a34a;}
.trans-card.muted span{background:#e5e7eb;}
.trans-card.muted strong{color:#475569;}

.trans-analytics-grid{
    display:grid;
    grid-template-columns:1.25fr .75fr;
    gap:24px;
    margin-bottom:24px;
}

.trans-panel{
    background:rgba(255,255,255,.96);
    border:1px solid #eef2f7;
    border-radius:30px;
    padding:28px;
    box-shadow:0 20px 55px rgba(0,0,0,.08);
    overflow:hidden;
}

.trans-panel-head{
    display:flex;
    justify-content:space-between;
    gap:18px;
    align-items:flex-start;
    margin-bottom:20px;
}

.trans-panel-head h2{
    margin:0 0 6px;
    color:#11151B;
    font-size:26px;
}

.trans-panel-head p{
    margin:0;
    color:#64748b;
    line-height:1.6;
}

.trans-panel-badge{
    white-space:nowrap;
    border-radius:999px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    color:var(--cor-principal);
    padding:8px 12px;
    font-weight:900;
    font-size:13px;
}

.trans-chart-box{
    height:360px;
    position:relative;
}

.trans-category-list{
    display:grid;
    gap:16px;
}

.trans-bar-item{
    padding:16px;
    border-radius:20px;
    background:#f8fafc;
    border:1px solid #eef2f7;
    transition:.25s ease;
}

.trans-bar-item:hover{
    transform:translateX(6px);
    background:white;
    box-shadow:0 14px 35px rgba(0,0,0,.07);
}

.trans-bar-item > div:first-child{
    display:flex;
    justify-content:space-between;
    gap:16px;
    margin-bottom:10px;
    color:#11151B;
}

.trans-bar-item span{
    color:#64748b;
    font-weight:900;
    white-space:nowrap;
}

.trans-bar-track{
    height:13px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
}

.trans-bar-fill{
    height:100%;
    background:linear-gradient(90deg,var(--cor-principal),var(--cor-secundaria));
    border-radius:999px;
    box-shadow:0 0 18px rgba(212,170,0,.35);
}

.trans-status-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-top:18px;
}

.trans-status-pill{
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:18px;
    padding:15px;
}

.trans-status-pill strong{
    display:block;
    color:#11151B;
    font-size:22px;
}

.trans-status-pill span{
    display:block;
    color:#64748b;
    font-weight:800;
    font-size:13px;
    margin-top:4px;
}

.trans-resolvidos-list{
    display:grid;
    gap:14px;
}

.trans-resolvido-item{
    position:relative;
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-left:5px solid #16a34a;
    border-radius:20px;
    padding:16px 16px 16px 18px;
    transition:.25s ease;
}

.trans-resolvido-item:hover{
    background:white;
    transform:translateX(6px);
    box-shadow:0 14px 35px rgba(0,0,0,.08);
}

.trans-resolvido-item strong,
.trans-resolvido-item span,
.trans-resolvido-item small,
.trans-resolvido-item time{
    display:block;
}

.trans-resolvido-item strong{
    color:#11151B;
    margin-bottom:5px;
}

.trans-resolvido-item span{
    color:var(--cor-principal);
    font-weight:900;
    font-size:13px;
}

.trans-resolvido-item small{
    color:#64748b;
    margin-top:6px;
    line-height:1.5;
}

.trans-empty{
    padding:22px;
    border-radius:20px;
    background:#f8fafc;
    border:1px dashed #cbd5e1;
    color:#64748b;
    font-weight:800;
}

.trans-cta-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
    margin-top:24px;
}

.trans-cta-card{
    position:relative;
    overflow:hidden;
    border-radius:30px;
    padding:32px;
    background:linear-gradient(135deg,var(--cor-principal),#11151B);
    color:white;
    box-shadow:0 22px 60px rgba(0,0,0,.16);
}

.trans-cta-card.light{
    background:white;
    color:#11151B;
    border:1px solid #eef2f7;
}

.trans-cta-card::after{
    content:"";
    position:absolute;
    right:-80px;
    bottom:-110px;
    width:250px;
    height:250px;
    border-radius:50%;
    background:rgba(212,170,0,.14);
}

.trans-cta-card h2{
    position:relative;
    z-index:2;
    margin:0 0 12px;
    font-size:30px;
}

.trans-cta-card p{
    position:relative;
    z-index:2;
    margin:0 0 22px;
    color:inherit;
    opacity:.86;
    line-height:1.75;
}

.trans-cta-card .btn{
    position:relative;
    z-index:2;
    margin-right:8px;
    margin-bottom:8px;
    border-radius:15px;
}

@media(max-width:1150px){
    .transparencia-grid.trans-grid-premium{
        grid-template-columns:repeat(3,1fr);
    }

    .trans-live-strip{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:950px){
    .trans-hero-grid,
    .trans-analytics-grid,
    .trans-cta-grid{
        grid-template-columns:1fr;
    }

    .trans-score-orb{
        min-height:auto;
    }

    .trans-score-card{
        width:260px;
        min-height:260px;
    }

    .trans-orb-badge{
        position:static;
        margin-top:12px;
        display:inline-block;
    }

    .trans-section-head,
    .trans-panel-head{
        flex-direction:column;
        align-items:flex-start;
    }

    .trans-status-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:650px){
    .trans-premium-hero{
        padding:70px 0 90px;
    }

    .trans-live-strip,
    .transparencia-grid.trans-grid-premium,
    .trans-status-grid{
        grid-template-columns:1fr;
    }

    .trans-dashboard-wrap{
        margin-top:-48px;
    }

    .trans-panel,
    .trans-cta-card{
        padding:22px;
        border-radius:24px;
    }

    .trans-chart-box{
        height:310px;
    }
}
</style>

<main class="transparencia-premium-page">
    <section class="trans-premium-hero">
        <div class="container trans-hero-grid">
            <div class="trans-hero-content">
                <span class="trans-kicker"><i class="bi bi-bar-chart-fill"></i> Portal da Transparência</span>
                <h1>Atalaia e Alto Estanqueiro-Jardia em números.</h1>
                <p>
                    Indicadores públicos sobre pedidos, ocorrências, requerimentos e marcações,
                    apresentados de forma clara para aproximar a Junta dos cidadãos.
                </p>

                <div class="trans-hero-actions">
                    <a class="hero-btn" href="/ocorrencias-mapa.php">Ver mapa público</a>
                    <a class="hero-btn" href="/pedidos.php">Submeter pedido</a>
                </div>
            </div>

            <div class="trans-score-orb">
                <div class="trans-orb-badge one"><b><?= (int)$total ?></b> pedidos</div>
                <div class="trans-score-card">
                    <div>
                        <strong><?= $taxaResolucao ?>%</strong>
                        <span>Taxa de resolução</span>
                        <small>Percentagem de pedidos já tratados pela Junta.</small>
                    </div>
                </div>
                <div class="trans-orb-badge two"><b><?= (int)$resolvidos ?></b> resolvidos</div>
            </div>
        </div>
    </section>

    <section class="section trans-dashboard-wrap">
        <div class="container">

            <div class="trans-live-strip">
                <div class="trans-live-item">
                    <div class="trans-live-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div>
                        <strong><?= (int)$total ?></strong>
                        <span>Pedidos recebidos</span>
                    </div>
                </div>

                <div class="trans-live-item">
                    <div class="trans-live-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <strong><?= (int)$resolvidos ?></strong>
                        <span>Pedidos resolvidos</span>
                    </div>
                </div>

                <div class="trans-live-item">
                    <div class="trans-live-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                    <div>
                        <strong><?= (int)$totalRequerimentos ?></strong>
                        <span>Requerimentos online</span>
                    </div>
                </div>

                <div class="trans-live-item">
                    <div class="trans-live-icon"><i class="bi bi-calendar-event-fill"></i></div>
                    <div>
                        <strong><?= (int)$totalMarcacoes ?></strong>
                        <span>Marcações registadas</span>
                    </div>
                </div>
            </div>

            <div class="trans-section-head">
                <div>
                    <span>Indicadores principais</span>
                    <h2>Painel público da atividade</h2>
                </div>
                <p>
                    Uma visão simples e transparente sobre o estado dos pedidos e serviços digitais da freguesia.
                </p>
            </div>

            <div class="transparencia-grid trans-grid-premium">
                <div class="trans-card">
                    <span><i class="bi bi-envelope-fill"></i></span>
                    <strong><?= (int)$total ?></strong>
                    <p>Pedidos recebidos</p>
                </div>

                <div class="trans-card warning">
                    <span><i class="bi bi-hourglass-split"></i></span>
                    <strong><?= (int)$pendentes ?></strong>
                    <p>Pendentes</p>
                </div>

                <div class="trans-card info">
                    <span><i class="bi bi-search"></i></span>
                    <strong><?= (int)$analise ?></strong>
                    <p>Em análise</p>
                </div>

                <div class="trans-card success">
                    <span><i class="bi bi-check-circle-fill"></i></span>
                    <strong><?= (int)$resolvidos ?></strong>
                    <p>Resolvidos</p>
                </div>

                <div class="trans-card">
                    <span><i class="bi bi-file-earmark-text-fill"></i></span>
                    <strong><?= (int)$totalRequerimentos ?></strong>
                    <p>Requerimentos</p>
                </div>

                <div class="trans-card muted">
                    <span><i class="bi bi-calendar-event-fill"></i></span>
                    <strong><?= (int)$totalMarcacoes ?></strong>
                    <p>Marcações</p>
                </div>
            </div>

            <div class="trans-analytics-grid">
                <div class="trans-panel">
                    <div class="trans-panel-head">
                        <div>
                            <h2>Evolução mensal dos pedidos</h2>
                            <p>Volume de comunicações registadas nos últimos meses.</p>
                        </div>
                        <span class="trans-panel-badge">Últimos 12 meses</span>
                    </div>
                    <div class="trans-chart-box">
                        <canvas id="graficoPedidosMes"></canvas>
                    </div>
                </div>

                <div class="trans-panel">
                    <div class="trans-panel-head">
                        <div>
                            <h2>Estado dos pedidos</h2>
                            <p>Distribuição atual por fase de tratamento.</p>
                        </div>
                        <span class="trans-panel-badge">Tempo real</span>
                    </div>
                    <div class="trans-chart-box">
                        <canvas id="graficoEstados"></canvas>
                    </div>

                    <div class="trans-status-grid">
                        <div class="trans-status-pill">
                            <strong><?= $taxaPendentes ?>%</strong>
                            <span>Pendentes</span>
                        </div>
                        <div class="trans-status-pill">
                            <strong><?= $taxaAnalise ?>%</strong>
                            <span>Em análise</span>
                        </div>
                        <div class="trans-status-pill">
                            <strong><?= $taxaResolucao ?>%</strong>
                            <span>Resolvidos</span>
                        </div>
                        <div class="trans-status-pill">
                            <strong><?= $taxaArquivados ?>%</strong>
                            <span>Arquivados</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="trans-analytics-grid">
                <div class="trans-panel">
                    <div class="trans-panel-head">
                        <div>
                            <h2>Categorias mais reportadas</h2>
                            <p>Áreas com maior número de pedidos registados pelos cidadãos.</p>
                        </div>
                        <span class="trans-panel-badge">Top 6</span>
                    </div>

                    <div class="trans-category-list">
                        <?php foreach ($categorias as $c): ?>
                            <?php $percent = $total > 0 ? round(($c['total'] / $total) * 100) : 0; ?>

                            <div class="trans-bar-item">
                                <div>
                                    <strong><?= htmlspecialchars($c['categoria']) ?></strong>
                                    <span><?= (int)$c['total'] ?> pedido(s)</span>
                                </div>

                                <div class="trans-bar-track">
                                    <div class="trans-bar-fill" style="width:<?= $percent ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($categorias)): ?>
                            <div class="trans-empty">Ainda não existem dados suficientes.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="trans-panel">
                    <div class="trans-panel-head">
                        <div>
                            <h2>Últimos pedidos resolvidos</h2>
                            <p>Atividade recente concluída pela Junta.</p>
                        </div>
                        <span class="trans-panel-badge">Concluídos</span>
                    </div>

                    <?php if (!empty($ultimosResolvidos)): ?>
                        <div class="trans-resolvidos-list">
                            <?php foreach ($ultimosResolvidos as $r): ?>
                                <div class="trans-resolvido-item">
                                    <strong><?= htmlspecialchars($r['assunto'] ?? '') ?></strong>
                                    <span><?= htmlspecialchars($r['categoria'] ?? '') ?><?= !empty($r['codigo']) ? ' · ' . htmlspecialchars($r['codigo']) : '' ?></span>

                                    <?php if (!empty($r['localizacao'])): ?>
                                        <small><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($r['localizacao']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="trans-empty">Ainda não existem pedidos resolvidos.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="trans-cta-grid">
                <div class="trans-cta-card">
                    <h2>Mapa público de ocorrências</h2>
                    <p>
                        Consulte as ocorrências públicas reportadas pelos cidadãos, com filtros por estado,
                        categoria e localização.
                    </p>
                    <a class="hero-btn" href="/ocorrencias-mapa.php">Ver mapa de ocorrências</a>
                    <a class="hero-btn" href="/pedidos.php">Submeter pedido</a>
                </div>

                <div class="trans-cta-card light">
                    <h2>Compromisso com a transparência</h2>
                    <p>
                        Esta página apresenta indicadores automáticos sobre os pedidos submetidos pelos cidadãos,
                        permitindo acompanhar a atividade da Junta de Freguesia de forma simples, clara e transparente.
                    </p>
                    <a class="hero-btn" href="/contactos.php">Contactar Junta</a>
                </div>
            </div>

        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labelsMes = <?= json_encode(array_column($pedidosMes, 'mes')) ?>;
const dadosMes = <?= json_encode(array_map('intval', array_column($pedidosMes, 'total'))) ?>;

new Chart(document.getElementById('graficoPedidosMes'), {
    type: 'line',
    data: {
        labels: labelsMes,
        datasets: [{
            label: 'Pedidos',
            data: dadosMes,
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 7
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
                ticks: { precision: 0 },
                grid: { color: 'rgba(148,163,184,.18)' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

new Chart(document.getElementById('graficoEstados'), {
    type: 'doughnut',
    data: {
        labels: ['Pendentes', 'Em análise', 'Resolvidos', 'Arquivados'],
        datasets: [{
            data: [
                <?= (int)$pendentes ?>,
                <?= (int)$analise ?>,
                <?= (int)$resolvidos ?>,
                <?= (int)$arquivados ?>
            ],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '64%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 18,
                    font: { weight: 'bold' }
                }
            }
        }
    }
});
</script>

<?php require_once "includes/footer.php"; ?>
