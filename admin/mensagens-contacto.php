<?php
/**
 * Mensagens de Contacto (CORE) — enviadas pelo formulário da página Contactos.
 * Tabela contactos_mensagens (migração 042). Também são enviadas por email para a Junta
 * quando o envio de email está configurado no servidor (coluna email_enviado).
 */
$adminPageTitle = "Mensagens de Contacto";
$adminActive = "mensagens_contacto";
require_once "includes/header.php";

if (!podeAceder('virtual')) {
    echo '<p>Sem permissão para ver as mensagens.</p>';
    require_once "includes/footer.php";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'lida')     $pdo->prepare("UPDATE contactos_mensagens SET lida = 1 WHERE id = ?")->execute([$id]);
    if ($acao === 'por_ler')  $pdo->prepare("UPDATE contactos_mensagens SET lida = 0 WHERE id = ?")->execute([$id]);
    if ($acao === 'apagar')   $pdo->prepare("DELETE FROM contactos_mensagens WHERE id = ?")->execute([$id]);
    header("Location: mensagens-contacto.php" . (isset($_GET['todas']) ? '?todas=1' : ''));
    exit;
}

$todas = isset($_GET['todas']);
try {
    $msgs = $pdo->query("SELECT * FROM contactos_mensagens " . ($todas ? "" : "WHERE lida = 0 ") . "ORDER BY criado_em DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
    $porLer = (int)$pdo->query("SELECT COUNT(*) FROM contactos_mensagens WHERE lida = 0")->fetchColumn();
} catch (Exception $e) {
    echo '<p>A tabela ainda não existe neste site. Corra as migrações: <code>php migrations/run.php</code></p>';
    require_once "includes/footer.php";
    exit;
}
?>

<p style="color:#64748b;max-width:760px;">
    Mensagens enviadas pelo formulário da página <a href="/contactos.php#formulario" target="_blank">Contactos</a>.
    Para responder, use o botão <strong>Responder</strong> (abre o seu programa de email com o endereço do cidadão).
    Os dados pessoais devem ser apagados quando já não forem necessários (RGPD).
</p>

<div class="admin-actions">
    <a class="btn <?= $todas ? 'secondary' : '' ?>" href="mensagens-contacto.php">Por ler (<?= $porLer ?>)</a>
    <a class="btn <?= $todas ? '' : 'secondary' ?>" href="mensagens-contacto.php?todas=1">Todas</a>
</div>

<?php if (empty($msgs)): ?>
    <div class="table-box"><p style="padding:18px;margin:0;"><?= $todas ? 'Ainda não há mensagens.' : 'Não há mensagens por ler.' ?></p></div>
<?php endif; ?>

<?php foreach ($msgs as $m): ?>
    <div class="table-box" style="padding:18px 20px;margin-bottom:14px;<?= $m['lida'] ? 'opacity:.75;' : 'border-left:4px solid var(--cor-principal, #355F3A);' ?>">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <strong style="font-size:16px;"><?= htmlspecialchars($m['assunto']) ?></strong>
                <?= $m['lida'] ? '' : '<span style="margin-left:8px;font-size:12px;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:999px;">Nova</span>' ?>
                <div style="color:#475569;font-size:14px;margin-top:4px;">
                    <?= htmlspecialchars($m['nome']) ?> · <a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a>
                    <?= $m['telefone'] ? ' · ' . htmlspecialchars($m['telefone']) : '' ?>
                </div>
            </div>
            <small style="color:#64748b;"><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?><?= $m['email_enviado'] ? ' · enviada por email' : '' ?></small>
        </div>
        <p style="white-space:pre-wrap;margin:12px 0;line-height:1.6;"><?= htmlspecialchars($m['mensagem']) ?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn" href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['assunto']) ?>">Responder</a>
            <form method="POST" style="display:inline;margin:0;padding:0;background:none;box-shadow:none;">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="acao" value="<?= $m['lida'] ? 'por_ler' : 'lida' ?>">
                <button class="btn secondary" type="submit"><?= $m['lida'] ? 'Marcar por ler' : 'Marcar como lida' ?></button>
            </form>
            <form method="POST" style="display:inline;margin:0;padding:0;background:none;box-shadow:none;" onsubmit="return confirm('Apagar esta mensagem?')">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="acao" value="apagar">
                <button class="btn danger" type="submit">Apagar</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once "includes/footer.php"; ?>
