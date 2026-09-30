<?php
require_once "includes/db.php";

$pedidoId = $_GET['pedido_id'] ?? 0;
$ver = $_GET['ver'] ?? '';

$tipoMostrar = $ver === 'admin' ? 'cidadao' : 'admin';

$stmt = $pdo->prepare("
    SELECT * FROM pedido_typing
    WHERE pedido_id = ?
    AND tipo = ?
    ORDER BY atualizado_em DESC
    LIMIT 1
");
$stmt->execute([$pedidoId, $tipoMostrar]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $diff = time() - strtotime($row['atualizado_em']);

    if ($diff < 3) {
        echo $tipoMostrar === 'cidadao'
            ? "Cidadão está a escrever..."
            : "Junta está a escrever...";
    }
}