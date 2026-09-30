<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmtImgs = $pdo->prepare("SELECT imagem FROM pedidos_imagens WHERE pedido_id = ?");
    $stmtImgs->execute([$id]);
    $imagens = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($imagens as $img) {
        $ficheiro = "../assets/img/" . $img['imagem'];

        if (file_exists($ficheiro)) {
            unlink($ficheiro);
        }
    }

    $stmtDeleteImgs = $pdo->prepare("DELETE FROM pedidos_imagens WHERE pedido_id = ?");
    $stmtDeleteImgs->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM pedidos_junta WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: pedidos.php");
exit;