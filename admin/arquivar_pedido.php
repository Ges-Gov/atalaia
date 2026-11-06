<?php
/*
 * Arquivar uma ocorrência (06/11/2026, pedido do presidente da Junta da Atalaia): em vez de a eliminar depois de
 * tratada, fica guardada com o estado «Arquivado» — com as mensagens, a resposta e o histórico — e continua
 * consultável no separador «Tratadas» e no filtro de estado.
 */
require_once "includes/auth.php";
require_once "../includes/db.php";

$voltar = 'pedidos.php';
$ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '') && substr((string)parse_url($ref, PHP_URL_PATH), -17) === '/admin/pedidos.php') {
    $voltar = 'pedidos.php' . (parse_url($ref, PHP_URL_QUERY) ? '?' . parse_url($ref, PHP_URL_QUERY) : '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals((string)($_SESSION['pedidos_admin_token'] ?? ''), (string)($_POST['token'] ?? ''))) {
    $_SESSION['pedidos_aviso'] = ['erro', 'Pedido inválido. Volte a tentar a partir da lista de ocorrências.'];
    header("Location: $voltar");
    exit;
}
if (!(isAdminMaster() || isOperador() || podeAceder('virtual'))) {
    $_SESSION['pedidos_aviso'] = ['erro', 'Sem permissão para arquivar ocorrências.'];
    header("Location: $voltar");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT id, codigo, estado FROM pedidos_junta WHERE id = ?");
$st->execute([$id]);
$pedido = $st->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    $_SESSION['pedidos_aviso'] = ['erro', 'Ocorrência não encontrada.'];
} elseif ($pedido['estado'] === 'arquivado') {
    $_SESSION['pedidos_aviso'] = ['ok', 'A ocorrência ' . $pedido['codigo'] . ' já estava arquivada.'];
} else {
    $pdo->prepare("UPDATE pedidos_junta SET estado = 'arquivado', estado_interno = 'tratado' WHERE id = ?")->execute([$id]);
    try {
        $pdo->prepare("INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao) VALUES (?, 'estado', ?, ?)")
            ->execute([$id, 'Ocorrência arquivada', $adminNome . ' arquivou esta ocorrência (fica guardada).']);
        $pdo->prepare("INSERT INTO logs_operacionais (admin_id, pedido_id, tipo, mensagem) VALUES (?, ?, 'estado', ?)")
            ->execute([$adminId, $id, 'Ocorrência arquivada']);
    } catch (Throwable $e) {
        // sem as tabelas de histórico: o estado já ficou gravado
    }
    $_SESSION['pedidos_aviso'] = ['ok', 'Ocorrência ' . $pedido['codigo'] . ' arquivada. Fica guardada e pode ser consultada no separador «Tratadas».'];
}

header("Location: $voltar");
exit;
