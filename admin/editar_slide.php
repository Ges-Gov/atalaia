<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/galeria.php";

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
    // Antes gravava-se com o NOME ORIGINAL do browser e sem validar a extensão
    // (ver includes/galeria.php). O helper valida, gera nome único e confere a escrita.
    [$imagem, $erroImg] = guardarImagemSimples($_FILES['imagem'] ?? [], 'slide', (string)$slide['imagem']);

    if ($erroImg !== '') {
        $erro = $erroImg;
    }

    if ($titulo === '') {
        $erro = 'Indique o título do slide.';
    }

    // Só grava se não houver erro nenhum (nem de título, nem de imagem).
    if ($erro === '') {
        $stmt = $pdo->prepare("
            UPDATE slides_homepage
            SET titulo = ?, subtitulo = ?, imagem = ?, link_destino = ?, ativo = ?
            WHERE id = ?
        ");
        $stmt->execute([$titulo, $subtitulo, $imagem, $link_destino, $ativo, $id]);

        header("Location: slides.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Slide</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:40px;color:#11151B;}
        .box{max-width:900px;margin:auto;background:#fff;border-radius:24px;padding:32px;box-shadow:0 18px 45px rgba(0,0,0,.08);}
        h1{margin-top:0;color:#242A30;}
        label{display:block;font-weight:800;margin:18px 0 8px;}
        input,textarea{width:100%;box-sizing:border-box;border:1px solid #dbe3ea;border-radius:14px;padding:14px 16px;font-size:15px;outline:none;}
        textarea{min-height:120px;resize:vertical;}
        input:focus,textarea:focus{border-color:#242A30;box-shadow:0 0 0 4px rgba(36,42,50,.10);}
        .actions{display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;}
        .btn{border:0;border-radius:14px;padding:13px 18px;background:#242A30;color:#fff;font-weight:900;text-decoration:none;cursor:pointer;}
        .btn.secondary{background:#64748b;}
        .erro{background:#fee2e2;color:#991b1b;padding:13px 16px;border-radius:14px;font-weight:800;margin-bottom:18px;}
        .check{display:flex;align-items:center;gap:10px;margin-top:18px;font-weight:800;}
        .check input{width:auto;}
        .preview{margin-top:12px;}
        .preview img{width:240px;height:130px;object-fit:cover;border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.12);}
        small{color:#64748b;font-weight:700;}
    </style>
</head>
<body>
<div class="box">
    <h1>Editar Slide</h1>

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Título</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($slide['titulo']) ?>" required>

        <label>Subtítulo</label>
        <textarea name="subtitulo"><?= htmlspecialchars($slide['subtitulo']) ?></textarea>

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
</body>
</html>
