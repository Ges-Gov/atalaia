<?php require_once "includes/header.php"; ?>

<?php
$pedido = null;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("
        SELECT * FROM pedidos_junta 
        WHERE codigo = ? AND email = ?
        LIMIT 1
    ");
    $stmt->execute([$codigo, $email]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        $erro = "Pedido não encontrado. Verifique o código e o email utilizado.";
    }
}

?>

<section class="consulta-hero-premium">
    <div class="container">
        <div>
            <span><?= te('consultar_pedido.hero.kicker') ?></span>
            <h1><?= te('consultar_pedido.hero.titulo') ?></h1>
            <p><?= te('consultar_pedido.hero.texto') ?></p>
        </div>

        <div class="consulta-hero-icon"><i class="bi bi-search"></i></div>
    </div>
</section>

<section class="section consulta-premium-section">
    <div class="container">

        <div class="consulta-wrapper">

            <div class="consulta-form-card">
                <span class="section-kicker"><?= te('consultar_pedido.formulario.kicker') ?></span>
                <h2><?= te('consultar_pedido.formulario.titulo') ?></h2>
                <p><?= te('consultar_pedido.formulario.intro') ?></p>

                <form method="POST" class="consulta-form-premium">
                    <input 
                        type="text" 
                        name="codigo" 
                        placeholder="<?= te('consultar_pedido.formulario.ph_codigo') ?>"
                        value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>"
                        required
                    >

                    <input 
                        type="email" 
                        name="email" 
                        placeholder="<?= te('consultar_pedido.formulario.ph_email') ?>"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >

                    <button class="hero-btn" type="submit"><?= te('consultar_pedido.formulario.botao') ?></button>
                </form>

                <?php if ($erro): ?>
                    <div class="alerta-erro consulta-alerta">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="consulta-help-card">
                <h3><?= te('consultar_pedido.passos.titulo') ?></h3>

                <div class="consulta-step">
                    <strong>1</strong>
                    <span><?= te('consultar_pedido.passos.p1') ?></span>
                </div>

                <div class="consulta-step">
                    <strong>2</strong>
                    <span><?= te('consultar_pedido.passos.p2') ?></span>
                </div>

                <div class="consulta-step">
                    <strong>3</strong>
                    <span><?= te('consultar_pedido.passos.p3') ?></span>
                </div>

                <a href="/pedidos.php" class="hero-btn">
                    <?= te('consultar_pedido.passos.botao') ?>
                </a>
            </div>

        </div>

        <?php if ($pedido): ?>
            <div class="pedido-track-card">

                <div class="pedido-track-header">
                    <div>
                        <span class="section-kicker"><?= te('consultar_pedido.resultado.titulo') ?></span>
                        <h2><?= htmlspecialchars($pedido['assunto']) ?></h2>
                        <p><?= te('consultar_pedido.resultado.codigo') ?> <strong><?= htmlspecialchars($pedido['codigo'] ?? '-') ?></strong></p>
                    </div>

                    <span class="estado-badge <?= estadoPedidoClasse($pedido['estado']) ?>">
                        <?= estadoPedidoTexto($pedido['estado']) ?>
                    </span>
                </div>

                <style>
                    /* redesign 10/2026: no telemóvel os 3 passos repartem a largura (antes rolava para o lado e cortava «Resolvido») */
                    @media (max-width: 700px) {
                        .pedido-timeline { overflow-x: visible; }
                        .pedido-timeline .timeline-step { min-width: 0; flex: 0 0 auto; max-width: 30%; }
                        .pedido-timeline .timeline-step strong { display: block; font-size: 12px; line-height: 1.25; }
                        .pedido-timeline .timeline-line { min-width: 12px; }
                    }
                </style>
                <div class="pedido-timeline">
                    <div class="timeline-step active">
                        <span>1</span>
                        <strong>Recebido</strong>
                    </div>

                    <div class="timeline-line"></div>

                    <div class="timeline-step <?= in_array($pedido['estado'], ['em_analise', 'resolvido', 'arquivado']) ? 'active' : '' ?>">
                        <span>2</span>
                        <strong>Em análise</strong>
                    </div>

                    <div class="timeline-line"></div>

                    <div class="timeline-step <?= $pedido['estado'] === 'resolvido' ? 'active' : '' ?>">
                        <span>3</span>
                        <strong>Resolvido</strong>
                    </div>
                </div>

                <div class="pedido-consulta-grid premium">
                    <div>
                        <strong><?= te('consultar_pedido.resultado.categoria') ?></strong>
                        <span><?= htmlspecialchars($pedido['categoria']) ?></span>
                    </div>

                    <div>
                        <strong><?= te('consultar_pedido.resultado.localizacao') ?></strong>
                        <span><?= htmlspecialchars($pedido['localizacao'] ?: '-') ?></span>
                    </div>

                    <div>
                        <strong><?= te('consultar_pedido.resultado.data_envio') ?></strong>
                        <span><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></span>
                    </div>

                    <div>
                        <strong><?= te('consultar_pedido.resultado.atualizacao') ?></strong>
                        <span>
                            <?= !empty($pedido['atualizado_em']) 
                                ? date('d/m/Y H:i', strtotime($pedido['atualizado_em'])) 
                                : 'Ainda sem atualização' 
                            ?>
                        </span>
                    </div>
                </div>

                <div class="pedido-message-grid">
                    <div class="pedido-message-box">
                        <h3><?= te('consultar_pedido.resultado.mensagem') ?></h3>
                        <p><?= nl2br(htmlspecialchars($pedido['mensagem'])) ?></p>
                    </div>

                    <div class="pedido-message-box resposta">
                        <h3><?= te('consultar_pedido.resultado.resposta') ?></h3>

                        <?php if (!empty($pedido['resposta'])): ?>
                            <p><?= nl2br(htmlspecialchars($pedido['resposta'])) ?></p>
                        <?php else: ?>
                            <p><?= te('consultar_pedido.resultado.sem_resposta') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>