<?php
/* Eliminar um documento da Freguesia. 20/11/2026: só por POST com token (a lista usa o botão «Eliminar», que vai para
   documentos.php); uma ligação antiga por GET já não apaga nada — volta à lista. */
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = (int)($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0 && !empty($_SESSION['doc_token'])
    && hash_equals((string)$_SESSION['doc_token'], (string)($_POST['token'] ?? ''))) {
    $stmt = $pdo->prepare("SELECT ficheiro FROM documentos WHERE id = ?");
    $stmt->execute([$id]);
    $ficheiro = (string)$stmt->fetchColumn();
    $caminho = __DIR__ . "/../assets/docs/" . basename($ficheiro);
    if ($ficheiro !== '' && is_file($caminho)) unlink($caminho);
    $pdo->prepare("DELETE FROM documentos WHERE id = ?")->execute([$id]);
    header("Location: documentos.php?ok=apagado");
    exit;
}

header("Location: documentos.php");
exit;
