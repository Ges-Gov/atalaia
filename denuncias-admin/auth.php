<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../includes/db.php";
if (empty($_SESSION['denuncias_user_id'])) {
    header("Location: login.php");
    exit;
}
$denunciasUserNome = $_SESSION['denuncias_user_nome'] ?? 'Utilizador';
$denunciasUserEmail = $_SESSION['denuncias_user_email'] ?? '';
?>