<?php
/**
 * Links Úteis (CORE) — logótipos de entidades e parceiros mostrados por cima do rodapé
 * em todas as páginas. Tabela links_uteis (migração 040).
 * "Patrocinado" = espaço de publicidade paga: no site leva a etiqueta "Patrocinado".
 */
$adminPageTitle = "Links Úteis";
$adminActive = "links_uteis";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/galeria.php";   // guardarImagemSimples()

if (!podeAceder('home')) {
    echo '<p>Sem permissão para gerir esta secção.</p>';
    require_once "includes/footer.php";
    exit;
}

$erro = '';

// Apagar (POST, para não se apagar por um simples link)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'apagar') {
    $id = (int)($_POST['id'] ?? 0);
    $logo = (string)$pdo->query("SELECT logo FROM links_uteis WHERE id = $id")->fetchColumn();
    $pdo->prepare("DELETE FROM links_uteis WHERE id = ?")->execute([$id]);
    // só apaga ficheiros carregados pelo admin (os iniciais em links/ ficam)
    if ($logo !== '' && strpos($logo, '/') === false && is_file(__DIR__ . "/../assets/img/" . $logo)) {
        @unlink(__DIR__ . "/../assets/img/" . $logo);
    }
    header("Location: links-uteis.php?ok=apagado");
    exit;
}

// Gravar (novo ou editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gravar') {
    $id     = (int)($_POST['id'] ?? 0);
    $nome   = trim($_POST['nome'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $ordem  = (int)($_POST['ordem'] ?? 0);
    $patro  = isset($_POST['patrocinado']) ? 1 : 0;
    $ativo  = isset($_POST['ativo']) ? 1 : 0;
    $atual  = '';
    if ($id) {
        $s = $pdo->prepare("SELECT logo FROM links_uteis WHERE id = ?");
        $s->execute([$id]);
        $atual = (string)$s->fetchColumn();
    }

    if ($url !== '' && !preg_match('#^https?://#i', $url)) $url = 'https://' . $url;

    if ($nome === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        $erro = "Indique o nome e um endereço válido (ex.: https://www.exemplo.pt).";
    } else {
        // logótipos iniciais vivem em links/ e não devem ser apagados ao trocar
        [$logo, $erroImg] = guardarImagemSimples($_FILES['logo'] ?? [], 'link', strpos($atual, '/') === false ? $atual : '');
        if ($logo === '' && empty($_FILES['logo']['name'])) $logo = $atual;
        if ($erroImg !== '') {
            $erro = $erroImg;
        } else {
            if ($id) {
                $pdo->prepare("UPDATE links_uteis SET nome = ?, url = ?, logo = ?, patrocinado = ?, ordem = ?, ativo = ? WHERE id = ?")
                    ->execute([$nome, $url, $logo ?: null, $patro, $ordem, $ativo, $id]);
            } else {
                $pdo->prepare("INSERT INTO links_uteis (nome, url, logo, patrocinado, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$nome, $url, $logo ?: null, $patro, $ordem, $ativo]);
            }
            header("Location: links-uteis.php?ok=gravado");
            exit;
        }
    }
}

try {
    $links = $pdo->query("SELECT * FROM links_uteis ORDER BY ordem ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<p>A tabela ainda não existe neste site. Corra as migrações: <code>php migrations/run.php</code></p>';
    require_once "includes/footer.php";
    exit;
}

$editar = null;
if (isset($_GET['editar'])) {
    foreach ($links as $l) if ((int)$l['id'] === (int)$_GET['editar']) $editar = $l;
}
$novo = isset($_GET['novo']);
?>

<p style="color:#64748b;max-width:760px;">
    Logótipos de entidades e parceiros que aparecem <strong>por cima do rodapé, em todas as páginas</strong>.
    Ao clicar, o visitante vai para o site da entidade (numa nova janela). Para publicidade paga, marque
    <strong>Patrocinado</strong>: o logótipo aparece com essa etiqueta, como exige a transparência numa entidade pública.
    Logótipo ideal: PNG com fundo transparente, pelo menos 300 px de largura.
</p>

<?php if (isset($_GET['ok'])): ?>
    <div class="alerta-sucesso"><?= $_GET['ok'] === 'apagado' ? 'Link apagado.' : 'Link guardado.' ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<?php if ($editar || $novo || $erro): ?>
    <?php $f = $editar ?: ['id' => (int)($_POST['id'] ?? 0), 'nome' => $_POST['nome'] ?? '', 'url' => $_POST['url'] ?? '', 'logo' => '', 'patrocinado' => isset($_POST['patrocinado']), 'ordem' => $_POST['ordem'] ?? (count($links) + 1), 'ativo' => 1]; ?>
    <form method="POST" enctype="multipart/form-data" style="margin-bottom:28px;">
        <input type="hidden" name="acao" value="gravar">
        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <h2><?= $f['id'] ? 'Editar link' : 'Novo link' ?></h2>

        <label for="luNome">Nome da entidade *</label>
        <input id="luNome" type="text" name="nome" required maxlength="150" value="<?= htmlspecialchars($f['nome']) ?>" placeholder="Ex.: Câmara Municipal">

        <label for="luUrl">Endereço do site *</label>
        <input id="luUrl" type="text" name="url" required maxlength="500" value="<?= htmlspecialchars($f['url']) ?>" placeholder="https://www.exemplo.pt">

        <label for="luLogo">Logótipo<?= !empty($f['logo']) ? ' (deixe vazio para manter o atual)' : '' ?></label>
        <?php if (!empty($f['logo'])): ?>
            <p><img src="/assets/img/<?= htmlspecialchars($f['logo']) ?>" alt="" style="max-height:56px;max-width:220px;background:#fff;padding:6px;border-radius:8px;border:1px solid #e2e8f0;"></p>
        <?php endif; ?>
        <input id="luLogo" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif">

        <label for="luOrdem">Ordem (1 = primeiro)</label>
        <input id="luOrdem" type="number" name="ordem" value="<?= (int)$f['ordem'] ?>">

        <label style="display:flex;gap:10px;align-items:center;margin:8px 0;">
            <input type="checkbox" name="patrocinado" value="1" <?= !empty($f['patrocinado']) ? 'checked' : '' ?> style="width:auto;margin:0;">
            Patrocinado (publicidade paga)
        </label>
        <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
            <input type="checkbox" name="ativo" value="1" <?= !empty($f['ativo']) ? 'checked' : '' ?> style="width:auto;margin:0;">
            Visível no site
        </label>

        <button class="btn">Guardar</button>
        <a class="btn secondary" href="links-uteis.php">Cancelar</a>
    </form>
<?php else: ?>
    <div class="admin-actions">
        <a class="btn" href="links-uteis.php?novo=1">Adicionar link</a>
    </div>
<?php endif; ?>

<div class="table-box">
    <table>
        <tr>
            <th>Ordem</th>
            <th>Logótipo</th>
            <th>Entidade</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($links as $l): ?>
            <tr>
                <td><?= (int)$l['ordem'] ?></td>
                <td><?php if (!empty($l['logo'])): ?><img src="/assets/img/<?= htmlspecialchars($l['logo']) ?>" alt="" style="max-height:36px;max-width:120px;"><?php endif; ?></td>
                <td>
                    <strong><?= htmlspecialchars($l['nome']) ?></strong><br>
                    <a href="<?= htmlspecialchars($l['url']) ?>" target="_blank" rel="noopener" style="font-size:13px;"><?= htmlspecialchars($l['url']) ?></a>
                </td>
                <td>
                    <?= $l['ativo'] ? 'Visível' : 'Oculto' ?>
                    <?= $l['patrocinado'] ? '<br><small>Patrocinado</small>' : '' ?>
                </td>
                <td>
                    <a class="btn secondary" href="links-uteis.php?editar=<?= (int)$l['id'] ?>">Editar</a>
                    <form method="POST" style="display:inline;margin:0;padding:0;background:none;box-shadow:none;" onsubmit="return confirm('Apagar este link?')">
                        <input type="hidden" name="acao" value="apagar">
                        <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                        <button class="btn danger" type="submit">Apagar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($links)): ?>
            <tr><td colspan="5">Ainda não há links úteis.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
