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

function estadoPedidoTexto($estado) {
    return match ($estado) {
        'pendente' => 'Pendente',
        'em_analise' => 'Em análise',
        'resolvido' => 'Resolvido',
        'arquivado' => 'Arquivado',
        default => ucfirst(str_replace('_', ' ', $estado))
    };
}

function estadoPedidoClasse($estado) {
    return match ($estado) {
        'pendente' => 'estado-pendente',
        'em_analise' => 'estado-em_analise',
        'resolvido' => 'estado-resolvido',
        'arquivado' => 'estado-arquivado',
        default => ''
    };
}
?>

<section class="consulta-hero-premium">
    <div class="container">
        <div>
            <span>Junta Virtual</span>
            <h1>Consultar Pedido</h1>
            <p>Acompanhe o estado do seu pedido, veja atualizações e consulte a resposta da Junta.</p>
        </div>

        <div class="consulta-hero-icon"><i class="bi bi-search"></i></div>
    </div>
</section>

<section class="section consulta-premium-section">
    <div class="container">

        <div class="consulta-wrapper">

            <div class="consulta-form-card">
                <span class="section-kicker">Acompanhamento online</span>
                <h2>Introduza os dados do pedido</h2>
                <p>Use o código recebido por email e o email utilizado na submissão.</p>

                <form method="POST" class="consulta-form-premium">
                    <input 
                        type="text" 
                        name="codigo" 
                        placeholder="Código do pedido"
                        value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>"
                        required
                    >

                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Email utilizado"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >

                    <button class="hero-btn" type="submit">Consultar pedido</button>
                </form>

                <?php if ($erro): ?>
                    <div class="alerta-erro consulta-alerta">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="consulta-help-card">
                <h3>Como consultar?</h3>

                <div class="consulta-step">
                    <strong>1</strong>
                    <span>Introduza o código do pedido.</span>
                </div>

                <div class="consulta-step">
                    <strong>2</strong>
                    <span>Use o mesmo email da submissão.</span>
                </div>

                <div class="consulta-step">
                    <strong>3</strong>
                    <span>Veja o estado e resposta da Junta.</span>
                </div>

                <a href="/pedidos.php" class="hero-btn">
                    Fazer novo pedido
                </a>
            </div>

        </div>

        <?php if ($pedido): ?>
            <div class="pedido-track-card">

                <div class="pedido-track-header">
                    <div>
                        <span class="section-kicker">Pedido encontrado</span>
                        <h2><?= htmlspecialchars($pedido['assunto']) ?></h2>
                        <p>Código: <strong><?= htmlspecialchars($pedido['codigo'] ?? '-') ?></strong></p>
                    </div>

                    <span class="estado-badge <?= estadoPedidoClasse($pedido['estado']) ?>">
                        <?= estadoPedidoTexto($pedido['estado']) ?>
                    </span>
                </div>

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
                        <strong>Categoria</strong>
                        <span><?= htmlspecialchars($pedido['categoria']) ?></span>
                    </div>

                    <div>
                        <strong>Localização</strong>
                        <span><?= htmlspecialchars($pedido['localizacao'] ?: '-') ?></span>
                    </div>

                    <div>
                        <strong>Data de envio</strong>
                        <span><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></span>
                    </div>

                    <div>
                        <strong>Última atualização</strong>
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
                        <h3>Mensagem enviada</h3>
                        <p><?= nl2br(htmlspecialchars($pedido['mensagem'])) ?></p>
                    </div>

                    <div class="pedido-message-box resposta">
                        <h3>Resposta da Junta</h3>

                        <?php if (!empty($pedido['resposta'])): ?>
                            <p><?= nl2br(htmlspecialchars($pedido['resposta'])) ?></p>
                        <?php else: ?>
                            <p>Ainda não existe resposta registada para este pedido.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>