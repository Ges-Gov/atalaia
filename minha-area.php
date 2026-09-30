<?php
require_once __DIR__ . "/includes/cidadao_auth.php";
require_once __DIR__ . "/includes/header.php";
?>


<?php
$cidadaoId = $_SESSION['cidadao_id'];

function minhaAreaCount($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM pedidos_junta
    WHERE cidadao_id = ?
    ORDER BY criado_em DESC
");
$stmt->execute([$cidadaoId]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtNotif = $pdo->prepare("
    SELECT COUNT(*)
    FROM notificacoes n
    LEFT JOIN notificacoes_lidas nl
        ON nl.notificacao_id = n.id
        AND nl.cidadao_id = ?
    WHERE n.ativo = 1
    AND nl.id IS NULL
");
$stmtNotif->execute([$cidadaoId]);
$notificacoesNovas = $stmtNotif->fetchColumn();

$totalPedidos = count($pedidos);

$totalRequerimentos = minhaAreaCount($pdo, "
    SELECT COUNT(*) FROM requerimentos WHERE cidadao_id = ?
", [$cidadaoId]);

$totalMarcacoes = minhaAreaCount($pdo, "
    SELECT COUNT(*) FROM marcacoes_atendimento WHERE cidadao_id = ?
", [$cidadaoId]);

$ultimosPedidos = array_slice($pedidos, 0, 5);
?>

<section class="page-hero">
    <div class="container">
        <h1>Área do Cidadão</h1>
        <p>Bem-vindo, <?= htmlspecialchars($_SESSION['cidadao_nome']) ?>. Acompanhe os seus serviços digitais num só lugar.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="minha-area-hero">
            <div>
                <span>Junta Virtual</span>
                <h2>A sua Junta no bolso</h2>
                <p>
                    Consulte pedidos, acompanhe respostas, faça marcações, submeta requerimentos,
                    veja notificações e aceda aos serviços online da Junta de Freguesia.
                </p>

                










<div class="hero-actions">

    <a href="pedidos.php" class="btn-pedido">
        <i class="fa-solid fa-pen-to-square"></i>
        Registar ocorrência
    </a>

    <a href="consultar-pedido.php" class="btn-consultar">
        <i class="fa-solid fa-magnifying-glass"></i>
        Consultar ocorrência
    </a>

    <a href="/cidadao-logout.php" class="btn-logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    Terminar sessão
</a>

</div>















            </div>

            <div class="minha-area-mini-stats">
                <div>
                    <strong><?= (int)$totalPedidos ?></strong>
                    <small>Pedidos</small>
                </div>

                <div>
                    <strong><?= (int)$totalRequerimentos ?></strong>
                    <small>Requerimentos</small>
                </div>

                <div>
                    <strong><?= (int)$totalMarcacoes ?></strong>
                    <small>Marcações</small>
                </div>

                <div>
                    <strong><?= (int)$notificacoesNovas ?></strong>
                    <small>Notificações</small>
                </div>
            </div>
        </div>

        <div class="quick-actions-grid">

            <a href="/pedidos.php" class="quick-action-card destaque">
                <span><i class="bi bi-envelope-paper"></i></span>
                <strong>Nova ocorrência</strong>
                <small>Comunicar uma situação, problema ou ocorrência à Junta.</small>
            </a>

            <a href="/consultar-pedido.php" class="quick-action-card">
                <span><i class="bi bi-search"></i></span>
                <strong>Consultar ocorrência</strong>
                <small>Acompanhar o estado da ocorrência através do código recebido.</small>
            </a>

            <a href="/requerimentos.php" class="quick-action-card">
                <span><i class="bi bi-file-earmark-text"></i></span>
                <strong>Requerimentos online</strong>
                <small>Submeter requerimentos e documentos digitais.</small>
            </a>

            <a href="/marcacoes.php" class="quick-action-card">
                <span><i class="bi bi-calendar-event"></i></span>
                <strong>Marcação de atendimento</strong>
                <small>Agendar atendimento presencial ou apoio da Junta.</small>
            </a>

            <a href="/documentos-automaticos.php" class="quick-action-card">
                <span><i class="bi bi-lightning-charge"></i></span>
                <strong>Documentos automáticos</strong>
                <small>Gerar documentos e pedidos de forma rápida.</small>
            </a>

            <a href="/atestado-residencia.php" class="quick-action-card">
                <span><i class="bi bi-house-door"></i></span>
                <strong>Atestado automático</strong>
                <small>Iniciar pedido de atestado de residência.</small>
            </a>

            <a href="/notificacoes.php" class="quick-action-card destaque">
                <span><i class="bi bi-bell"></i></span>
                <strong>
                    Notificações
                    <?php if ($notificacoesNovas > 0): ?>
                        <em><?= (int)$notificacoesNovas ?></em>
                    <?php endif; ?>
                </strong>
                <small>Avisos, respostas e comunicações importantes.</small>
            </a>

            <a href="/ocorrencias-mapa.php" class="quick-action-card">
                <span><i class="bi bi-map"></i></span>
                <strong>Mapa de ocorrências</strong>
                <small>Consultar situações públicas registadas no mapa.</small>
            </a>

            <a href="/transparencia.php" class="quick-action-card">
                <span><i class="bi bi-bar-chart"></i></span>
                <strong>Transparência</strong>
                <small>Consultar informação pública e dados institucionais.</small>
            </a>

        </div>

        <div class="content-box app-area-actions" style="margin-top:30px;">
            <h2>Resumo da minha atividade</h2>
            <p>Visão geral dos seus serviços digitais associados a esta conta.</p>

            <div class="quick-actions-grid" style="margin-top:20px;">
                <div class="quick-action-card">
                    <span><i class="bi bi-envelope-paper"></i></span>
                    <strong><?= (int)$totalPedidos ?> pedido(s)</strong>
                    <small>Total de pedidos submetidos por si.</small>
                </div>

                <div class="quick-action-card">
                    <span><i class="bi bi-file-earmark-text"></i></span>
                    <strong><?= (int)$totalRequerimentos ?> requerimento(s)</strong>
                    <small>Total de requerimentos associados à sua conta.</small>
                </div>

                <div class="quick-action-card">
                    <span><i class="bi bi-calendar-event"></i></span>
                    <strong><?= (int)$totalMarcacoes ?> marcação(ões)</strong>
                    <small>Marcações de atendimento registadas.</small>
                </div>

                <div class="quick-action-card">
                    <span><i class="bi bi-bell"></i></span>
                    <strong><?= (int)$notificacoesNovas ?> nova(s)</strong>
                    <small>Notificações ainda por ler.</small>
                </div>
            </div>
        </div>

        <div class="content-box" style="margin-top:30px;">
            <div style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap;">
                <div>
                    <h2>Últimos pedidos</h2>
                    <p>Acompanhe os pedidos mais recentes enviados à Junta.</p>
                </div>

                <a class="btn" href="/pedidos.php">Novo pedido</a>
            </div>

            <?php if (!empty($ultimosPedidos)): ?>

                <div class="documentos-tabela" style="margin-top:20px;">

                    <?php foreach ($ultimosPedidos as $p): ?>

                        <?php
                        $stmtUnread = $pdo->prepare("
                            SELECT COUNT(*) 
                            FROM pedido_mensagens
                            WHERE pedido_id = ? 
                            AND autor_tipo = 'admin'
                            AND lida = 0
                        ");
                        $stmtUnread->execute([$p['id']]);
                        $naoLidas = $stmtUnread->fetchColumn();
                        ?>

                        <article class="documento-linha">

                            <div class="doc-icon">#</div>

                            <div class="doc-info">
                                <h3>
                                    <?= htmlspecialchars($p['assunto']) ?>

                                    <?php if ($naoLidas > 0): ?>
                                        <span class="badge-novo">
                                            <?= (int)$naoLidas ?> nova(s)
                                        </span>
                                    <?php endif; ?>
                                </h3>

                                <div class="doc-meta">
                                    <span>Código: <?= htmlspecialchars($p['codigo'] ?? '-') ?></span>
                                    <span>Estado: <?= htmlspecialchars(str_replace('_', ' ', $p['estado'])) ?></span>
                                    <span><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></span>
                                </div>

                                <?php if (!empty($p['resposta'])): ?>
                                    <p><strong>Resposta da Junta:</strong> <?= htmlspecialchars($p['resposta']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex;align-items:center;">
                                <a class="btn" href="/pedido-detalhe.php?id=<?= (int)$p['id'] ?>">
                                    Ver / Conversar
                                </a>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div style="margin-top:20px;">
                    <p>Ainda não submeteu pedidos com esta conta.</p>
                    <a class="btn" href="/pedidos.php">Fazer o primeiro pedido</a>
                </div>

            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>