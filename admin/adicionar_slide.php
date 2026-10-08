<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/galeria.php";
require_once "includes/slides_ordem.php";

$erro = '';

if ($_POST) {
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $link_destino = trim($_POST['link_destino'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Antes gravava-se o ficheiro com o NOME ORIGINAL vindo do browser e sem validar
    // a extensão: dois "foto.jpg" sobrepunham-se, e um "x.php" ficava executável em
    // assets/img/. O helper valida a extensão, gera um nome único e confere a escrita.
    [$imagem, $erroImg] = guardarImagemSimples($_FILES['imagem'] ?? [], 'slide');

    if ($erroImg !== '') {
        $erro = $erroImg;
    }

    if ($titulo === '') {
        $erro = 'Indique o título do slide.';
    }

    // Só grava se NÃO houver erro nenhum — nem de título, nem de imagem. Sem esta
    // condição, um upload falhado passava despercebido e o slide era criado à
    // mesma, a apontar para uma imagem inexistente.
    if ($erro === '') {
        $stmt = $pdo->prepare("
            INSERT INTO slides_homepage (titulo, subtitulo, imagem, link_destino, ativo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titulo, $subtitulo, $imagem, $link_destino, $ativo]);
        reordenarSlides($pdo, (int)$pdo->lastInsertId(), posicaoSlidePedida());

        header("Location: slides.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Slide</title>
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
        small{color:#64748b;font-weight:700;}
    </style>
</head>
<body>
<div class="box">
    <h1>Adicionar Slide</h1>

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Título</label>
        <input type="text" name="titulo" placeholder="Título do slide" required>

        <label>Subtítulo</label>
        <textarea name="subtitulo" placeholder="Texto de apoio do slide"></textarea>

        <label>Ordem de apresentação</label>
        <input type="number" name="ordem" min="1" max="<?= (int)$pdo->query("SELECT COUNT(*) FROM slides_homepage")->fetchColumn() + 1 ?>" value="" placeholder="Vazio = fica em último">
        <small>1 = primeiro slide, 2 = segundo, e assim por diante. Os outros slides ajustam-se sozinhos.</small>

        <label>Imagem</label>
        <input type="file" name="imagem" accept="image/*">

        <label>Link do botão / artigo</label>
        <input type="text" name="link_destino" placeholder="Ex: /noticia.php?id=1">
        <small>Este link será usado no botão “Ver artigo” do slide.</small>

        <label class="check">
            <input type="checkbox" name="ativo" checked>
            Slide ativo
        </label>

        <div class="actions">
            <button class="btn" type="submit">Guardar Slide</button>
            <a class="btn secondary" href="slides.php">Voltar</a>
        </div>
    </form>
</div>
</body>
</html>
