<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/cidadao_auth.php";
require_once "includes/header.php";
?>








<?php
$cidadaoId = $_SESSION['cidadao_id'];

if (isset($_GET['ler'])) {
    $id = (int)$_GET['ler'];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO notificacoes_lidas (notificacao_id, cidadao_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$id, $cidadaoId]);

    header("Location: notificacoes.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT n.*,
    CASE WHEN nl.id IS NULL THEN 0 ELSE 1 END AS lida
    FROM notificacoes n
    LEFT JOIN notificacoes_lidas nl
        ON nl.notificacao_id = n.id
        AND nl.cidadao_id = ?
    WHERE n.ativo = 1
    ORDER BY n.criado_em DESC
");
$stmt->execute([$cidadaoId]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-hero">
    <div class="container">
        <h1>Notificações</h1>
        <p>Acompanhe avisos, alertas e comunicações da Junta de Freguesia.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="content-box">
            <h2>As minhas notificações</h2>

            <?php if (!empty($notificacoes)): ?>
                <div class="notificacoes-lista">
                    <?php foreach ($notificacoes as $n): ?>
                        <article class="notificacao-card <?= !$n['lida'] ? 'nova' : '' ?>">
                            <div>
                                <span class="notificacao-tipo tipo-<?= htmlspecialchars($n['tipo']) ?>">
                                    <?= htmlspecialchars($n['tipo']) ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars($n['titulo']) ?>

                                    <?php if (!$n['lida']): ?>
                                        <span class="badge-novo">Nova</span>
                                    <?php endif; ?>
                                </h3>

                                <p><?= nl2br(htmlspecialchars($n['mensagem'])) ?></p>

                                <small><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></small>
                            </div>

                            <?php if (!$n['lida']): ?>
                                <a class="btn secondary" href="notificacoes.php?ler=<?= $n['id'] ?>">
                                    Marcar como lida
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Ainda não existem notificações.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>