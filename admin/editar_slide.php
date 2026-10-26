<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/galeria.php";
require_once "includes/slides_ordem.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM slides_homepage WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$slide = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slide) {
    die("Slide não encontrado.");
}

$erro = '';

if ($_POST) {
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $link_destino = trim($_POST['link_destino'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    [$tipo, $videoUrl, $videoAjuste, $videoFormato, $erroVideo] = slideVideoDoPost();
    // Antes gravava-se com o NOME ORIGINAL do browser e sem validar a extensão
    // (ver includes/galeria.php). O helper valida, gera nome único e confere a escrita.
    [$imagem, $erroImg] = guardarImagemSimples($_FILES['imagem'] ?? [], 'slide', (string)$slide['imagem']);

    if ($erroImg !== '') {
        $erro = $erroImg;
    }

    if ($erroVideo !== '') {
        $erro = $erroVideo;
    }

    if ($titulo === '') {
        $erro = 'Indique o título do slide.';
    }

    // Só grava se não houver erro nenhum (nem de título, nem de imagem).
    if ($erro === '') {
        $stmt = $pdo->prepare("
            UPDATE slides_homepage
            SET titulo = ?, subtitulo = ?, imagem = ?, link_destino = ?, ativo = ?,
                tipo = ?, video_url = ?, video_ajuste = ?, video_formato = ?
            WHERE id = ?
        ");
        $stmt->execute([$titulo, $subtitulo, $imagem, $link_destino, $ativo, $tipo, $videoUrl, $videoAjuste, $videoFormato, $id]);
        reordenarSlides($pdo, $id, posicaoSlidePedida() ?? (int)($slide['ordem'] ?? 0) ?: null);

        header("Location: slides.php");
        exit;
    }
}
?>

<?php
// Página dentro do layout do backoffice (12/10/2026) — antes era uma página solta, sem menu
$adminPageTitle = "Editar slide";
$adminActive = "slides";
require_once "includes/header.php";
?>
<style>
    .slide-form .erro{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:12px 16px;border-radius:12px;font-weight:600;margin-bottom:18px;}
    .slide-form .actions{display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;}
    .slide-form .check{display:flex;align-items:center;gap:10px;margin-top:6px;}
    .slide-form .slide-tipo{border:1px solid var(--adm-borda);border-radius:14px;padding:12px 16px 4px;margin:4px 0 18px;display:flex;gap:22px;flex-wrap:wrap}
    .slide-form .slide-tipo legend{font-weight:600;font-size:14px;padding:0 6px}
    .slide-form .slide-video{background:var(--adm-superficie-2);border:1px solid var(--adm-borda);border-radius:14px;padding:16px 18px 6px;margin:-6px 0 20px}
    .slide-form .slide-video[hidden]{display:none}
    .slide-form .slide-video > label:not(.check){display:block;margin:14px 0 6px}
    .slide-form .preview{margin:0 0 18px;}
    .slide-form .preview img{width:280px;max-width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:14px;box-shadow:0 12px 30px rgba(0,0,0,.12);}
</style>
<div class="slide-form">

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Título</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($slide['titulo']) ?>" required>

        <label>Subtítulo</label>
        <textarea name="subtitulo"><?= htmlspecialchars($slide['subtitulo']) ?></textarea>

        <label>Ordem de apresentação</label>
        <input type="number" name="ordem" min="1" max="<?= (int)$pdo->query("SELECT COUNT(*) FROM slides_homepage")->fetchColumn() ?>" value="<?= htmlspecialchars((string)($slide['ordem'] ?? '')) ?>" placeholder="Ex.: 1">
        <small>1 = primeiro slide, 2 = segundo, e assim por diante. Os outros slides ajustam-se sozinhos.</small>

        <?php slideVideoCampos($slide); ?>

        <label>Imagem atual</label>
        <?php if (!empty($slide['imagem'])): ?>
            <div class="preview">
                <img src="../assets/img/<?= htmlspecialchars($slide['imagem']) ?>" alt="Slide">
            </div>
        <?php else: ?>
            <small>Sem imagem.</small>
        <?php endif; ?>

        <label>Alterar imagem</label>
        <input type="file" name="imagem" accept="image/*">

        <label>Link do botão / artigo</label>
        <input type="text" name="link_destino" value="<?= htmlspecialchars($slide['link_destino'] ?? '') ?>" placeholder="Ex: /noticia.php?id=1">
        <small>Este link será usado no botão “Ver artigo” do slide.</small>

        <label class="check">
            <input type="checkbox" name="ativo" <?= !empty($slide['ativo']) ? 'checked' : '' ?>>
            Slide ativo
        </label>

        <div class="actions">
            <button class="btn" type="submit">Guardar Alterações</button>
            <a class="btn secondary" href="slides.php">Voltar</a>
        </div>
    </form>
</div>
<?php require_once "includes/footer.php"; ?>
