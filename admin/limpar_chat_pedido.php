<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM pedido_mensagens WHERE pedido_id = ?");
    $stmt->execute([$id]);
}

header("Location: ver_pedido.php?id=" . $id);
exit;