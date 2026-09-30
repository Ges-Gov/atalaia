<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/config.php";

if (empty($_SESSION['cidadao_id'])) {
    echo 0;
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notificacoes n
        LEFT JOIN notificacoes_lidas nl
            ON nl.notificacao_id = n.id
            AND nl.cidadao_id = ?
        WHERE n.ativo = 1
        AND nl.id IS NULL
    ");
    $stmt->execute([$_SESSION['cidadao_id']]);
    echo (int)$stmt->fetchColumn();
} catch (Exception $e) {
    echo 0;
}