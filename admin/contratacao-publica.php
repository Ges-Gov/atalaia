<?php
$adminPageTitle = "Contratação Pública";
$adminActive = "contratacao_publica";
require_once "includes/header.php";

$erro = '';
$sucesso = '';

function getContratacaoPublicaConfig($pdo) {
    $stmt = $pdo->query("SELECT * FROM contratacao_publica_pagina ORDER BY id ASC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        $pdo->query("
            INSERT INTO contratacao_publica_pagina
            (hero_kicker, hero_titulo, texto, botao_texto, botao_link)
            VALUES
            ('Transparência e Gestão Pública', 'Contratação Pública', '', 'Ver no Portal BASE', NULL)
        ");
        $stmt = $pdo->query("SELECT * FROM contratacao_publica_pagina ORDER BY id ASC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $config;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $hero_kicker = trim($_POST['hero_kicker'] ?? '');
    $hero_titulo = trim($_POST['hero_titulo'] ?? '');
    $texto = trim($_POST['texto'] ?? '');
    $botao_texto = trim($_POST['botao_texto'] ?? '');
    $botao_link = trim($_POST['botao_link'] ?? '');

    if (!$hero_titulo) {
        $erro = "O título é obrigatório.";
    } elseif ($botao_link !== '' && !filter_var($botao_link, FILTER_VALIDATE_URL)) {
        $erro = "O link do botão não é um URL válido.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE contratacao_publica_pagina
            SET hero_kicker=?, hero_titulo=?, texto=?, botao_texto=?, botao_link=?, atualizado_em=NOW()
            WHERE id=?
        ");
        $stmt->execute([$hero_kicker, $hero_titulo, $texto, $botao_texto, $botao_link ?: null, $id]);
        $sucesso = "Conteúdo atualizado.";
    }
}

$config = getContratacaoPublicaConfig($pdo);
$linkAutomatico = "https://www.base.gov.pt/Base4/pt/pesquisa/?type=contratos&adjudicante=" . urlencode(siteConfig('nome_site', 'Junta de Freguesia'));
?>

<style>
.cpinfo-admin-hero{background:linear-gradient(135deg,var(--cor-principal),#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18)}
.cpinfo-admin-hero h2{margin:8px 0;font-size:34px}.cpinfo-admin-hero p{margin:0;color:#dbeafe}
.cpinfo-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.cpinfo-card{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:24px;box-shadow:0 14px 34px rgba(15,23,42,.06)}
.cpinfo-form{display:grid;gap:14px}.cpinfo-form label{font-weight:900;color:#11151B;font-size:13px}
.cpinfo-form input,.cpinfo-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}
.cpinfo-form textarea{min-height:100px;padding:14px;line-height:1.6}
.cpinfo-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cpinfo-hint{color:#64748b;font-weight:700;font-size:13px;margin-top:-6px}
.cpinfo-actions{display:flex;gap:8px;flex-wrap:wrap}
.cpinfo-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}
.cpinfo-alert.ok{background:#dcfce7;color:#166534}.cpinfo-alert.err{background:#fee2e2;color:#991b1b}
.btn{display:inline-flex;align-items:center;gap:8px;background:var(--cor-principal);color:white!important;text-decoration:none;border:0;border-radius:14px;padding:13px 18px;font-weight:900;cursor:pointer}
</style>

<div class="cpinfo-admin-hero">
    <span class="cpinfo-kicker">Recursos Humanos ▸ Contratação Pública</span>
    <h2>Contratação Pública</h2>
    <p>Esta página é só um texto institucional + um botão para o Portal BASE — a listagem de
        procedimentos concursais (recrutamento) passou a viver em "Procedimentos Concursais".</p>
</div>

<?php if($sucesso): ?><div class="cpinfo-alert ok"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if($erro): ?><div class="cpinfo-alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="cpinfo-card">
    <h3>Conteúdo da página pública</h3>

    <form method="POST" class="cpinfo-form">
        <input type="hidden" name="id" value="<?= (int)$config['id'] ?>">

        <div class="cpinfo-form-grid">
            <div><label>Kicker</label><input type="text" name="hero_kicker" value="<?= htmlspecialchars($config['hero_kicker'] ?? '') ?>"></div>
            <div><label>Título *</label><input type="text" name="hero_titulo" value="<?= htmlspecialchars($config['hero_titulo'] ?? '') ?>" required></div>
        </div>

        <div><label>Texto</label><textarea name="texto"><?= htmlspecialchars($config['texto'] ?? '') ?></textarea></div>

        <div class="cpinfo-form-grid">
            <div><label>Texto do botão</label><input type="text" name="botao_texto" value="<?= htmlspecialchars($config['botao_texto'] ?? '') ?>"></div>
            <div>
                <label>Link do botão</label>
                <input type="url" name="botao_link" value="<?= htmlspecialchars($config['botao_link'] ?? '') ?>" placeholder="<?= htmlspecialchars($linkAutomatico) ?>">
            </div>
        </div>
        <p class="cpinfo-hint">Se deixares o link em branco, o botão usa automaticamente uma pesquisa no
            Portal BASE filtrada pelo nome desta freguesia (<?= htmlspecialchars(siteConfig('nome_site', '')) ?>).</p>

        <div class="cpinfo-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn" style="background:white;color:#11151B!important;border:1px solid #e5e7eb;" href="../contratacao-publica.php" target="_blank">Ver página pública</a>
        </div>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>
