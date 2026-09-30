<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cidadao_id'])) {
    header("Location: /cidadao-login.php");
    exit;
}