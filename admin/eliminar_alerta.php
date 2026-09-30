<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM alertas WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: alertas.php");
exit;