<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM comercio_local WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: comercio.php");
exit;