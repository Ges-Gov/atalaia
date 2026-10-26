<?php
require_once "includes/chat_acesso.php";

$pedidoId = (int) ($_GET['pedido_id'] ?? 0);
chatExigirAcesso($pdo, $pedidoId);   // só o dono do pedido ou a Junta

$stmt = $pdo->prepare("
    SELECT * FROM pedido_mensagens
    WHERE pedido_id = ?
    ORDER BY criado_em ASC
");
$stmt->execute([$pedidoId]);

$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($mensagens as $m):

$classe = $m['autor_tipo'] === 'cidadao' ? 'cidadao' : 'admin';
?>

<div class="chat-msg <?= $classe ?>">
    <strong><?= htmlspecialchars($m['autor_nome']) ?></strong>
    <p><?= nl2br(htmlspecialchars($m['mensagem'])) ?></p>
    <small><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></small>
</div>

<?php endforeach; ?>