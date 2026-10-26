<?php
require_once "includes/chat_acesso.php";

$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
// cada um só marca o seu lado da conversa como «a escrever»
$tipo = chatExigirAcesso($pdo, $pedidoId);

if ($pedidoId && in_array($tipo, ['cidadao','admin'])) {

    $stmt = $pdo->prepare("
        INSERT INTO pedido_typing (pedido_id, tipo)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE atualizado_em = NOW()
    ");

    $stmt->execute([$pedidoId, $tipo]);
}