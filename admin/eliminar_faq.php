<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([$id]);
}

header("Location: faqs.php");
exit;
