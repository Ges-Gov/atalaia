<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/config.php";

if (empty($_SESSION['cidadao_id'])) {
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT n.*
        FROM notificacoes n
        LEFT JOIN notificacoes_lidas nl
            ON nl.notificacao_id = n.id
            AND nl.cidadao_id = ?
        WHERE n.ativo = 1
        AND nl.id IS NULL
        ORDER BY n.criado_em DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['cidadao_id']]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $notificacoes = [];
}
?>

<?php if (!empty($notificacoes)): ?>

    <?php foreach ($notificacoes as $n): ?>
        <a class="notif-item" href="/notificacoes.php">
            <span><?= htmlspecialchars($n['tipo'] ?? 'Aviso') ?></span>
            <strong><?= htmlspecialchars($n['titulo'] ?? 'Notificação') ?></strong>
            <small>
                <?= !empty($n['criado_em']) ? date('d/m/Y H:i', strtotime($n['criado_em'])) : '' ?>
            </small>
        </a>
    <?php endforeach; ?>

<?php else: ?>

    <p class="notif-empty">Sem novas notificações.</p>

<?php endif; ?>