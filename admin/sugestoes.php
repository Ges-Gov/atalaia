<?php
$adminPageTitle = "Sugestões";
$adminActive = "sugestoes";
require_once "includes/header.php";

if (!podeAceder('virtual')) {
    die("Sem acesso a esta secção.");
}

if (empty($_SESSION['sugestoes_admin_token'])) {
    $_SESSION['sugestoes_admin_token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['sugestoes_admin_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($token, (string)($_POST['token'] ?? ''))) {
    $id = (int)($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'lida') {
        $pdo->prepare("UPDATE sugestoes SET lida = 1 WHERE id = ?")->execute([$id]);
    } elseif ($acao === 'nao_lida') {
        $pdo->prepare("UPDATE sugestoes SET lida = 0 WHERE id = ?")->execute([$id]);
    } elseif ($acao === 'apagar') {
        $pdo->prepare("DELETE FROM sugestoes WHERE id = ?")->execute([$id]);
    }
    header("Location: sugestoes.php" . ($acao !== 'apagar' && $id ? "#s$id" : ''));
    exit;
}

$sugestoes = [];
try {
    $sugestoes = $pdo->query("SELECT * FROM sugestoes ORDER BY criado_em DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="content-box">A tabela de sugestões ainda não existe (falta correr a migração 035).</div>';
}
$naoLidas = count(array_filter($sugestoes, fn($s) => !$s['lida']));
?>

<div class="content-box">
    <h3>Sugestões recebidas pelo site</h3>
    <p style="margin:0;color:#64748b">
        Mensagens enviadas pelo formulário <a href="/sugestoes.php" target="_blank">Sugestões</a> do site.
        <?= count($sugestoes) ?> no total<?= $naoLidas ? ", <strong>$naoLidas por ler</strong>" : '' ?>.
    </p>
</div>

<div class="sug-lista">
    <?php foreach ($sugestoes as $s): ?>
        <details class="sug-item <?= $s['lida'] ? '' : 'nova' ?>" id="s<?= (int)$s['id'] ?>">
            <summary>
                <span class="sug-assunto"><?= $s['lida'] ? '' : '<span class="sug-ponto" title="Por ler"></span>' ?><?= htmlspecialchars($s['assunto']) ?></span>
                <span class="sug-quem"><?= htmlspecialchars($s['nome']) ?> · <?= date('d/m/Y H:i', strtotime($s['criado_em'])) ?></span>
            </summary>
            <div class="sug-corpo">
                <p class="sug-contacto"><strong><?= htmlspecialchars($s['nome']) ?></strong> —
                    <a href="mailto:<?= htmlspecialchars($s['email']) ?>?subject=<?= rawurlencode('Re: ' . $s['assunto']) ?>"><?= htmlspecialchars($s['email']) ?></a></p>
                <div class="sug-texto"><?= nl2br(htmlspecialchars($s['mensagem'])) ?></div>
                <form method="post" class="sug-acoes">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <a class="btn" href="mailto:<?= htmlspecialchars($s['email']) ?>?subject=<?= rawurlencode('Re: ' . $s['assunto']) ?>">Responder por email</a>
                    <?php if ($s['lida']): ?>
                        <button class="btn secondary" name="acao" value="nao_lida">Marcar como não lida</button>
                    <?php else: ?>
                        <button class="btn secondary" name="acao" value="lida">Marcar como lida</button>
                    <?php endif; ?>
                    <button class="btn danger" name="acao" value="apagar" onclick="return confirm('Apagar esta sugestão?')">Apagar</button>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
    <?php if (!$sugestoes): ?>
        <div class="content-box">Ainda não chegaram sugestões.</div>
    <?php endif; ?>
</div>

<style>
.sug-lista{display:flex;flex-direction:column;gap:12px;margin-top:16px;}
.sug-item{background:#fff;border-radius:16px;box-shadow:0 6px 20px rgba(15,23,42,.06);border:1px solid #e5e7eb;}
.sug-item.nova{border-color:var(--cor-principal);}
.sug-item summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:16px;align-items:center;padding:16px 20px;flex-wrap:wrap;}
.sug-item summary::-webkit-details-marker{display:none;}
.sug-assunto{font-weight:800;color:#11151B;display:flex;align-items:center;gap:8px;}
.sug-item.nova .sug-assunto{color:var(--cor-principal);}
.sug-ponto{width:9px;height:9px;border-radius:50%;background:var(--cor-principal);display:inline-block;flex-shrink:0;}
.sug-quem{color:#64748b;font-size:13px;}
.sug-corpo{padding:0 20px 18px;border-top:1px solid #eef2f7;}
.sug-contacto{margin:14px 0 8px;}
.sug-texto{background:#f8fafc;border-radius:12px;padding:14px 16px;line-height:1.6;white-space:normal;}
.sug-acoes{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;}
</style>

<?php require_once "includes/footer.php"; ?>
