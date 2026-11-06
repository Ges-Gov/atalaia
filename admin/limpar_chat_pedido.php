<?php
/*
 * Limpar a conversa com o cidadão de uma ocorrência (apaga as mensagens para sempre).
 * 06/11/2026: a conversa fica sempre guardada no backoffice; só a GesGov a pode limpar, e só por formulário (POST)
 * com token. Antes bastava abrir o endereço, e qualquer utilizador do backoffice podia apagar.
 */
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && hash_equals((string)($_SESSION['pedidos_admin_token'] ?? ''), (string)($_POST['token'] ?? ''))
    && isGesGov()
    && $id > 0) {
    $pdo->prepare("DELETE FROM pedido_mensagens WHERE pedido_id = ?")->execute([$id]);
}

header("Location: " . ($id > 0 ? "ver_pedido.php?id=" . $id : "pedidos.php"));
exit;
