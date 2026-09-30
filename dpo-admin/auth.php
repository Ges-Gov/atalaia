<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../includes/db.php";
if (empty($_SESSION['dpo_user_id'])) { header("Location: login.php"); exit; }
$dpoUserNome = $_SESSION['dpo_user_nome'] ?? 'DPO';
$dpoUserEmail = $_SESSION['dpo_user_email'] ?? '';
?>