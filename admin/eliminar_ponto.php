<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("SELECT imagem FROM pontos_interesse WHERE id = ?");
    $stmt->execute([$id]);
    $ponto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ponto && !empty($ponto['imagem'])) {
        $ficheiro = "../assets/img/" . $ponto['imagem'];

        if (file_exists($ficheiro)) {
            unlink($ficheiro);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM pontos_interesse WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: pontos.php");
exit;