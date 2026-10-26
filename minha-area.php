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
        <h1><?= te('minha_area.hero.titulo') ?></h1>
        <p>Bem-vindo, <?= htmlspecialchars($_SESSION['cidadao_nome']) ?>. <?= te('minha_area.hero.subtitulo') ?></p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="minha-area-hero">
            <div>
                <span><?= te('minha_area.intro.kicker') ?></span>
                <h2><?= te('minha_area.intro.titulo') ?></h2>
                <p>
                    <?= te('minha_area.intro.texto') ?>

                </p>

                










<div class="hero-actions">

    <a href="pedidos.php" class="btn-pedido">
        <i class="fa-solid fa-pen-to-square"></i>
        <?= te('minha_area.acoes.registar') ?>
    </a>

    <a href="consultar-pedido.php" class="btn-consultar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <?= te('minha_area.acoes.consultar') ?>
    </a>

    <a href="/cidadao-logout.php" class="btn-logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    <?= te('minha_area.acoes.sair') ?>
</a>

</div>















            </div>

            <div class="minha-area-mini-stats">
                <div>
                    <strong><?= (int)$totalPedidos ?></strong>
                    <small><?= te('minha_area.resumo.pedidos') ?></small>
                </div>

                <div>
                    <strong><?= (int)$totalRequerimentos ?></strong>
                    <small><?= te('minha_area.resumo.requerimentos') ?></small>
                </div>

                <div>
                    <strong><?= (int)$totalMarcacoes ?></strong>
                    <small><?= te('minha_area.resumo.marcacoes') ?></small>
                </div>

                <div>
                    <strong><?= (int)$notificacoesNovas ?></strong>
                    <small><?= te('minha_area.resumo.notificacoes') ?></small>
                </div>
            </div>
        </div>

        <div class="quick-actions-grid">

            <a href="/pedidos.php" class="quick-action-card destaque">
                <span><i class="bi bi-envelope-paper"></i></span>
                <strong><?= te('minha_area.servicos.s1_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s1_texto') ?></small>
            </a>

            <a href="/consultar-pedido.php" class="quick-action-card">
                <span><i class="bi bi-search"></i></span>
                <strong><?= te('minha_area.servicos.s2_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s2_texto') ?></small>
            </a>

            <a href="/requerimentos.php" class="quick-action-card">
                <span><i class="bi bi-file-earmark-text"></i></span>
                <strong><?= te('minha_area.servicos.s3_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s3_texto') ?></small>
            </a>

            <a href="/marcacoes.php" class="quick-action-card">
                <span><i class="bi bi-calendar-event"></i></span>
                <strong><?= te('minha_area.servicos.s4_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s4_texto') ?></small>
            </a>

            <a href="/documentos-automaticos.php" class="quick-action-card">
                <span><i class="bi bi-lightning-charge"></i></span>
                <strong><?= te('minha_area.servicos.s5_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s5_texto') ?></small>
            </a>

            <a href="/atestado-residencia.php" class="quick-action-card">
                <span><i class="bi bi-house-door"></i></span>
                <strong><?= te('minha_area.servicos.s6_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s6_texto') ?></small>
            </a>

            <a href="/notificacoes.php" class="quick-action-card destaque">
                <span><i class="bi bi-bell"></i></span>
                <strong>
                    <?= te('minha_area.servicos.s7_titulo') ?>
                    <?php if ($notificacoesNovas > 0): ?>
                        <em><?= (int)$notificacoesNovas ?></em>
                    <?php endif; ?>
                </strong>
                <small><?= te('minha_area.servicos.s7_texto') ?></small>
            </a>

            <a href="/ocorrencias-mapa.php" class="quick-action-card">
                <span><i class="bi bi-map"></i></span>
                <strong><?= te('minha_area.servicos.s8_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s8_texto') ?></small>
            </a>

            <a href="/transparencia.php" class="quick-action-card">
                <span><i class="bi bi-bar-chart"></i></span>
                <strong><?= te('minha_area.servicos.s9_titulo') ?></strong>
                <small><?= te('minha_area.servicos.s9_texto') ?></small>
            </a>

        </div>

        <div class="content-box app-area-actions" style="margin-top:30px;">
            <h2><?= te('minha_area.atividade.titulo') ?></h2>
            <p><?= te('minha_area.atividade.intro') ?></p>

            <div class="quick-actions-grid" style="margin-top:20px;">
                <div class="quick-action-card">
                    <span><i class="bi bi-envelope-paper"></i></span>
                    <strong><?= (int)$totalPedidos ?> pedido(s)</strong>
                    <small><?= te('minha_area.atividade.pedidos_texto') ?></small>
                </div>

                <div class="quick-action-card">
                    <span><i class="bi bi-file-earmark-text"></i></span>
                    <strong><?= (int)$totalRequerimentos ?> requerimento(s)</strong>
                    <small><?= te('minha_area.atividade.requerimentos_texto') ?></small>
                </div>

                <div class="quick-action-card">
                    <span><i class="bi bi-calendar-event"></i></span>
                    <strong><?= (int)$totalMarcacoes ?> marcação(ões)</strong>
                    <small><?= te('minha_area.atividade.marcacoes_texto') ?></small>
                </div>

                <div class="quick-action-card">
                    <span><i class="bi bi-bell"></i></span>
                    <strong><?= (int)$notificacoesNovas ?> nova(s)</strong>
                    <small><?= te('minha_area.atividade.notificacoes_texto') ?></small>
                </div>
            </div>
        </div>

        <div class="content-box" style="margin-top:30px;">
            <div style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap;">
                <div>
                    <h2><?= te('minha_area.pedidos.titulo') ?></h2>
                    <p><?= te('minha_area.pedidos.intro') ?></p>
                </div>

                <a class="btn" href="/pedidos.php"><?= te('minha_area.pedidos.botao_novo') ?></a>
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
                                    <?= te('minha_area.pedidos.botao_ver') ?>
                                </a>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div style="margin-top:20px;">
                    <p><?= te('minha_area.pedidos.vazio') ?></p>
                    <a class="btn" href="/pedidos.php"><?= te('minha_area.pedidos.vazio_botao') ?></a>
                </div>

            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>