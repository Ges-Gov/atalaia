<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("SELECT ficheiro FROM requerimentos_ficheiros WHERE requerimento_id = ?");
    $stmt->execute([$id]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $caminho = "../assets/docs/requerimentos/" . $f['ficheiro'];
        if (!empty($f['ficheiro']) && file_exists($caminho)) {
            unlink($caminho);
        }
    }

    $stmt = $pdo->prepare("SELECT documento_pdf FROM requerimentos WHERE id = ?");
    $stmt->execute([$id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($req && !empty($req['documento_pdf'])) {
        $caminho = "../assets/docs/requerimentos/" . $req['documento_pdf'];
        if (file_exists($caminho)) {
            unlink($caminho);
        }
    }

    $pdo->prepare("DELETE FROM requerimentos_ficheiros WHERE requerimento_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM requerimentos WHERE id = ?")->execute([$id]);
}

header("Location: requerimentos.php");
exit;
