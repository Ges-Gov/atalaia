<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['denuncias_user_id'], $_SESSION['denuncias_user_nome'], $_SESSION['denuncias_user_email']);
header("Location: login.php");
exit;
?>