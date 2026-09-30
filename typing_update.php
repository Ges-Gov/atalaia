<?php
require_once "includes/db.php";

$pedidoId = $_POST['pedido_id'] ?? 0;
$tipo = $_POST['tipo'] ?? '';

if ($pedidoId && in_array($tipo, ['cidadao','admin'])) {

    $stmt = $pdo->prepare("
        INSERT INTO pedido_typing (pedido_id, tipo)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE atualizado_em = NOW()
    ");

    $stmt->execute([$pedidoId, $tipo]);
}