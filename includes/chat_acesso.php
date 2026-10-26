<?php
/**
 * Chat dos pedidos: quem pode ler/escrever no chat de um pedido (CORE, 20/10/2026).
 * Antes, chat_fetch.php / typing_*.php respondiam a qualquer pessoa para qualquer pedido_id
 * (dava para ler as conversas entre cidadãos e a Junta). Agora só:
 *  - um utilizador ativo do painel (sessão do backoffice), ou
 *  - o cidadão dono do pedido (sessão de cidadão).
 * Devolve 'admin' ou 'cidadao'; caso contrário responde 403 e termina.
 * Baseado em includes/chat_acesso.php do modelo novo, adaptado ao login atual (sem 2FA).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function chatExigirAcesso(PDO $pdo, int $pedidoId): string
{
    if ($pedidoId > 0 && !empty($_SESSION['admin_id'])) {
        $st = $pdo->prepare("SELECT 1 FROM admin_utilizadores WHERE id = ? AND ativo = 1");
        $st->execute([(int) $_SESSION['admin_id']]);
        if ($st->fetchColumn()) {
            return 'admin';
        }
    }
    if ($pedidoId > 0 && !empty($_SESSION['cidadao_id'])) {
        $st = $pdo->prepare("SELECT 1 FROM pedidos_junta WHERE id = ? AND cidadao_id = ?");
        $st->execute([$pedidoId, (int) $_SESSION['cidadao_id']]);
        if ($st->fetchColumn()) {
            return 'cidadao';
        }
    }
    http_response_code(403);
    exit;
}
