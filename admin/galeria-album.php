<?php
$adminPageTitle = "Álbum";
$adminActive = "galeria-albuns";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/galeria.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM galeria_albuns WHERE id = ?");
$stmt->execute([$id]);
$album = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    die("Álbum não encontrado.");
}

$errosUpload = [];
$mensagem = "";

// Upload de várias imagens de uma vez
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_fotos'])) {
    if (!empty($_FILES['fotos']['name'][0])) {
        [$n, $errosUpload] = guardarImagensNoAlbum($pdo, $id, $_FILES['fotos']);
        if ($n > 0) {
            $mensagem = "$n imagem(ns) adicionada(s) ao álbum.";
        }
    } else {
        $errosUpload[] = "Escolha pelo menos uma imagem.";
    }
}

// Guardar nome/descrição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_album'])) {
    $pdo->prepare("UPDATE galeria_albuns SET nome = ?, descricao = ?, ativo = ? WHERE id = ?")
        ->execute([
            trim($_POST['nome'] ?? $album['nome']),
            trim($_POST['descricao'] ?? ''),
            isset($_POST['ativo']) ? 1 : 0,
            $id,
        ]);

    header("Location: galeria-album.php?id=$id&ok=guardado");
    exit;
}

// Definir capa
if (isset($_GET['capa'])) {
    $ficheiro = (string)$_GET['capa'];
    $pdo->prepare("UPDATE galeria_albuns SET capa = ? WHERE id = ?")->execute([$ficheiro, $id]);
    header("Location: galeria-album.php?id=$id&ok=capa");
    exit;
}

// Apagar imagem
if (isset($_GET['apagar'])) {
    apagarImagemDaGaleria($pdo, (int)$_GET['apagar']);
    header("Location: galeria-album.php?id=$id&ok=apagada");
    exit;
}

// Recarregar (pode ter mudado a capa)
$stmt->execute([$id]);
$album = $stmt->fetch(PDO::FETCH_ASSOC);

$imagens = imagensDoAlbum($pdo, $id, false);

require_once __DIR__ . "/includes/header.php";
?>

<div class="admin-actions">
    <a class="btn secondary" href="galeria-albuns.php">← Voltar aos álbuns</a>
</div>

<?php if (!empty($_GET['ok']) || $mensagem !== ''): ?>
    <div class="admin-ok" style="background:#dcfce7;color:#166534;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= $mensagem !== '' ? htmlspecialchars($mensagem) : 'Álbum atualizado.' ?>
    </div>
<?php endif; ?>

<?php if (!empty($errosUpload)): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?php foreach ($errosUpload as $e): ?>
            <div><?= $e ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="table-box" style="margin-bottom:22px;">
    <h2 style="margin-top:0;"><?= htmlspecialchars($album['nome']) ?></h2>

    <?php if ($album['origem'] !== 'manual'): ?>
        <p style="background:#eff6ff;color:#1e40af;border-radius:12px;padding:12px 14px;font-weight:700;">
            Este álbum é <strong>automático</strong>: pertence a
            <?= $album['origem'] === 'evento' ? 'um evento' : 'um ponto de interesse' ?>
            e o nome acompanha o da origem. Pode gerir aqui as imagens à mesma.
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Nome do álbum</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($album['nome']) ?>" required>

        <label>Descrição</label>
        <textarea name="descricao"><?= htmlspecialchars($album['descricao'] ?? '') ?></textarea>

        <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
            <input type="checkbox" name="ativo" value="1" <?= $album['ativo'] ? 'checked' : '' ?> style="width:auto;margin:0;">
            Álbum visível no site
        </label>

        <button class="btn" name="guardar_album" value="1">Guardar</button>
    </form>
</div>

<div class="table-box" style="margin-bottom:22px;">
    <h2 style="margin-top:0;">Adicionar imagens</h2>

    <form method="POST" enctype="multipart/form-data">
        <label>Imagens (pode escolher várias de uma vez)</label>
        <input type="file" name="fotos[]" accept="image/*" multiple required>

        <button class="btn" name="enviar_fotos" value="1">Enviar imagens</button>
    </form>
</div>

<div class="table-box">
    <h2 style="margin-top:0;">Imagens do álbum (<?= count($imagens) ?>)</h2>

    <?php if (empty($imagens)): ?>
        <p>Ainda não há imagens neste álbum.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;">
            <?php foreach ($imagens as $img): ?>
                <?php $eCapa = ($album['capa'] === $img['ficheiro']); ?>
                <div style="border:2px solid <?= $eCapa ? 'var(--cor-principal,#242A30)' : '#e5e7eb' ?>;border-radius:14px;padding:10px;background:#fff;">
                    <img src="<?= htmlspecialchars(imagemGaleriaUrl($img['ficheiro'])) ?>" alt=""
                         style="width:100%;height:120px;object-fit:cover;border-radius:10px;display:block;margin-bottom:8px;">

                    <small style="display:block;color:#64748b;margin-bottom:8px;">
                        <?= htmlspecialchars($img['titulo'] ?? '') ?>
                    </small>

                    <?php if ($eCapa): ?>
                        <span class="btn secondary" style="pointer-events:none;opacity:.75;">Capa</span>
                    <?php else: ?>
                        <a class="btn secondary" href="galeria-album.php?id=<?= $id ?>&capa=<?= urlencode($img['ficheiro']) ?>">Definir capa</a>
                    <?php endif; ?>

                    <a class="btn danger" href="galeria-album.php?id=<?= $id ?>&apagar=<?= (int)$img['id'] ?>"
                       onclick="return confirm('Apagar esta imagem?')">Apagar</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
