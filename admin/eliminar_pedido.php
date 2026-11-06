<?php
/*
 * Eliminar uma ocorrência PARA SEMPRE (com fotos, mensagens e histórico) — só para enganos (duplicada, de teste…).
 * Para guardar uma ocorrência tratada usa-se «Arquivar» (arquivar_pedido.php).
 * 06/11/2026: só por formulário (POST) com token e só para o administrador; antes bastava abrir o endereço,
 * e qualquer utilizador do backoffice podia apagar.
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
    $_SESSION['pedidos_aviso'] = ['erro', 'Pedido inválido. Para eliminar, use o botão na lista de ocorrências.'];
    header("Location: $voltar");
    exit;
}
if (!(isAdminMaster() || isGesGov())) {
    $_SESSION['pedidos_aviso'] = ['erro', 'Só o administrador pode eliminar ocorrências. Use «Arquivar».'];
    header("Location: $voltar");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT codigo FROM pedidos_junta WHERE id = ?");
$st->execute([$id]);
$codigo = $st->fetchColumn();

if ($codigo !== false) {
    $stmtImgs = $pdo->prepare("SELECT imagem FROM pedidos_imagens WHERE pedido_id = ?");
    $stmtImgs->execute([$id]);
    foreach ($stmtImgs->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $nome = basename((string)$img['imagem']);   // as fotos ficam em assets/img/ (pedidos.php)
        if ($nome !== '' && is_file("../assets/img/" . $nome)) {
            unlink("../assets/img/" . $nome);
        }
    }
    $pdo->prepare("DELETE FROM pedidos_imagens WHERE pedido_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM pedidos_junta WHERE id = ?")->execute([$id]);
    $_SESSION['pedidos_aviso'] = ['ok', 'Ocorrência ' . $codigo . ' eliminada.'];
}

header("Location: $voltar");
exit;
