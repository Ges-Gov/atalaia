<?php
$adminPageTitle = "Adicionar Notícia";
$adminActive = "noticias";
require_once "includes/header.php";
require_once "../includes/categorias.php";

function guardarImagemNoticia($file) {
    if (empty($file['name'])) return null;
    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $permitidas)) return null;
    $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
    $nome = 'noticia-' . trim($base, '-') . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], "../assets/img/" . $nome)) {
        return $nome;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data = !empty($_POST['data']) ? str_replace('T', ' ', $_POST['data']) . ':00' : date('Y-m-d H:i:s');
    $categoria = in_array($_POST['categoria'] ?? '', $GLOBALS['CATEGORIAS_CONTEUDO'], true) ? $_POST['categoria'] : null;

    // Imagem de capa
    $imagem = guardarImagemNoticia($_FILES['imagem'] ?? []);
    $focoX = max(0, min(100, (int)($_POST['foco_x'] ?? 50)));
    $focoY = max(0, min(100, (int)($_POST['foco_y'] ?? 50)));

    $stmt = $pdo->prepare("INSERT INTO noticias (titulo, descricao, imagem, imagem_foco_x, imagem_foco_y, data, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $descricao, $imagem, $focoX, $focoY, $data, $categoria]);
    $noticiaId = (int)$pdo->lastInsertId();

    // Fotos adicionais (galeria)
    if (!empty($_FILES['galeria']['name'][0])) {
        $stmtImg = $pdo->prepare("INSERT INTO noticias_imagens (noticia_id, ficheiro, ordem) VALUES (?, ?, ?)");
        foreach ($_FILES['galeria']['name'] as $i => $nome) {
            if (empty($nome)) continue;
            $f = [
                'name' => $_FILES['galeria']['name'][$i],
                'tmp_name' => $_FILES['galeria']['tmp_name'][$i],
            ];
            $guardada = guardarImagemNoticia($f);
            if ($guardada) {
                $stmtImg->execute([$noticiaId, $guardada, $i]);
            }
        }
    }

    header("Location: noticias.php?fb_novo=" . $noticiaId);
    exit;
}
?>

<style>
.noticia-form{background:white;border-radius:24px;padding:26px;box-shadow:0 16px 40px rgba(0,0,0,.08);border:1px solid #eef2f7;}
.noticia-form label{display:block;font-weight:900;color:#11151B;margin:16px 0 6px;}
.noticia-form .hint{color:#6b7280;font-weight:700;font-size:13px;margin:4px 0 0;}
</style>

<form method="POST" enctype="multipart/form-data" class="noticia-form">
    <label>Título *</label>
    <input type="text" name="titulo" placeholder="Título da notícia" required>

    <label>Descrição *</label>
    <textarea name="descricao" placeholder="Descrição da notícia" required></textarea>

    <label>Data de publicação</label>
    <input type="datetime-local" name="data" value="<?= date('Y-m-d\TH:i') ?>">

    <label>Categoria</label>
    <select name="categoria">
        <option value="">Sem categoria</option>
        <?php foreach ($GLOBALS['CATEGORIAS_CONTEUDO'] as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>
    <p class="hint">Ajuda a organizar por tema (para no futuro filtrar por assunto em mailing/relatórios).</p>

    <label>Imagem de capa (principal)</label>
    <input type="file" name="imagem" id="imagemCapaInput" accept="image/*">
    <p class="hint">Esta é a imagem que aparece nas listagens e no topo da notícia.</p>

    <div class="foco-picker-wrap" id="focoPickerWrap" hidden>
        <p class="hint">Clique na imagem para marcar o ponto mais importante (ex.: uma cara). Esse ponto mantém-se sempre visível, mesmo quando a imagem é cortada em proporções diferentes (cartão de listagem vs. topo da notícia).</p>
        <div class="foco-picker">
            <img id="focoPreviewImg" alt="Pré-visualização da imagem de capa">
            <div class="foco-marker" id="focoMarker"></div>
        </div>
        <input type="hidden" name="foco_x" id="focoX" value="50">
        <input type="hidden" name="foco_y" id="focoY" value="50">
    </div>

    <label>Fotos adicionais (galeria)</label>
    <input type="file" name="galeria[]" accept="image/*" multiple>
    <p class="hint">Pode selecionar várias. Aparecem na galeria dentro da notícia. Depois de criar, pode escolher qualquer uma como capa em "Editar".</p>

    <div style="margin-top:18px;">
        <button class="btn">Guardar notícia</button>
    </div>
</form>

<script src="assets/foco-imagem.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    initFocoPicker({
        fileInputId: "imagemCapaInput",
        previewWrapId: "focoPickerWrap",
        previewImgId: "focoPreviewImg",
        markerId: "focoMarker",
        hiddenXId: "focoX",
        hiddenYId: "focoY"
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
