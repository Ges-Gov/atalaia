<?php
session_start();
unset($_SESSION['cidadao_id']);
unset($_SESSION['cidadao_nome']);

header("Location: cidadao-login.php");
exit;