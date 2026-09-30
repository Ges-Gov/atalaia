<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("SELECT ficheiro FROM documentos WHERE id = ?");
    $stmt->execute([$id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($documento && !empty($documento['ficheiro'])) {
        $ficheiro = "../assets/docs/" . $documento['ficheiro'];

        if (file_exists($ficheiro)) {
            unlink($ficheiro);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM documentos WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: documentos.php");
exit;