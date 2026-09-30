<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM contactos_uteis WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: contactos.php");
exit;