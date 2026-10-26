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
    [$tipo, $videoUrl, $videoAjuste, $videoFormato, $erroVideo] = slideVideoDoPost();

    // Antes gravava-se o ficheiro com o NOME ORIGINAL vindo do browser e sem validar
    // a extensão: dois "foto.jpg" sobrepunham-se, e um "x.php" ficava executável em
    // assets/img/. O helper valida a extensão, gera um nome único e confere a escrita.
    [$imagem, $erroImg] = guardarImagemSimples($_FILES['imagem'] ?? [], 'slide');

    if ($erroImg !== '') {
        $erro = $erroImg;
    }

    if ($erroVideo !== '') {
        $erro = $erroVideo;
    }

    if ($titulo === '') {
        $erro = 'Indique o título do slide.';
    }

    // Só grava se NÃO houver erro nenhum — nem de título, nem de imagem. Sem esta
    // condição, um upload falhado passava despercebido e o slide era criado à
    // mesma, a apontar para uma imagem inexistente.
    if ($erro === '') {
        $stmt = $pdo->prepare("
            INSERT INTO slides_homepage (titulo, subtitulo, imagem, link_destino, ativo, tipo, video_url, video_ajuste, video_formato)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titulo, $subtitulo, $imagem, $link_destino, $ativo, $tipo, $videoUrl, $videoAjuste, $videoFormato]);
        reordenarSlides($pdo, (int)$pdo->lastInsertId(), posicaoSlidePedida());

        header("Location: slides.php");
        exit;
    }
}
?>

<?php
// Página dentro do layout do backoffice (12/10/2026) — antes era uma página solta, sem menu
$adminPageTitle = "Adicionar slide";
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
</style>
<div class="slide-form">

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

        <?php slideVideoCampos($_POST ? ['tipo' => $_POST['tipo'] ?? 'imagem', 'video_url' => $_POST['video_url'] ?? ''] : []); ?>

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
<?php require_once "includes/footer.php"; ?>
