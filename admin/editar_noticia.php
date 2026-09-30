<?php
$adminPageTitle = "Editar Notícia";
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

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ?");
$stmt->execute([$id]);
$noticia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$noticia) {
    die("Notícia não encontrada.");
}

// Remover foto da galeria
if (isset($_GET['remover_img'])) {
    $imgId = (int)$_GET['remover_img'];
    $s = $pdo->prepare("SELECT ficheiro FROM noticias_imagens WHERE id = ? AND noticia_id = ?");
    $s->execute([$imgId, $id]);
    $fic = $s->fetchColumn();
    if ($fic) {
        $fp = "../assets/img/" . basename($fic);
        if (is_file($fp)) @unlink($fp);
        $pdo->prepare("DELETE FROM noticias_imagens WHERE id = ?")->execute([$imgId]);
    }
    header("Location: editar_noticia.php?id=" . $id);
    exit;
}

// Definir uma foto da galeria como capa (troca com a capa atual)
if (isset($_GET['capa'])) {
    $imgId = (int)$_GET['capa'];
    $s = $pdo->prepare("SELECT ficheiro FROM noticias_imagens WHERE id = ? AND noticia_id = ?");
    $s->execute([$imgId, $id]);
    $novaCapa = $s->fetchColumn();
    if ($novaCapa) {
        $capaAntiga = $noticia['imagem'];
        $pdo->prepare("UPDATE noticias SET imagem = ? WHERE id = ?")->execute([$novaCapa, $id]);
        $pdo->prepare("DELETE FROM noticias_imagens WHERE id = ?")->execute([$imgId]);
        if (!empty($capaAntiga)) {
            $pdo->prepare("INSERT INTO noticias_imagens (noticia_id, ficheiro, ordem) VALUES (?, ?, 0)")->execute([$id, $capaAntiga]);
        }
    }
    header("Location: editar_noticia.php?id=" . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data = !empty($_POST['data']) ? str_replace('T', ' ', $_POST['data']) . ':00' : $noticia['data'];
    $categoria = in_array($_POST['categoria'] ?? '', $GLOBALS['CATEGORIAS_CONTEUDO'], true) ? $_POST['categoria'] : null;
    $imagem = $noticia['imagem'];

    $novaCapa = guardarImagemNoticia($_FILES['imagem'] ?? []);
    if ($novaCapa) {
        $imagem = $novaCapa;
    }
    $focoX = max(0, min(100, (int)($_POST['foco_x'] ?? 50)));
    $focoY = max(0, min(100, (int)($_POST['foco_y'] ?? 50)));

    $pdo->prepare("UPDATE noticias SET titulo = ?, descricao = ?, imagem = ?, imagem_foco_x = ?, imagem_foco_y = ?, data = ?, categoria = ? WHERE id = ?")
        ->execute([$titulo, $descricao, $imagem, $focoX, $focoY, $data, $categoria, $id]);

    if (!empty($_FILES['galeria']['name'][0])) {
        $stmtImg = $pdo->prepare("INSERT INTO noticias_imagens (noticia_id, ficheiro, ordem) VALUES (?, ?, ?)");
        foreach ($_FILES['galeria']['name'] as $i => $nome) {
            if (empty($nome)) continue;
            $f = ['name' => $_FILES['galeria']['name'][$i], 'tmp_name' => $_FILES['galeria']['tmp_name'][$i]];
            $g = guardarImagemNoticia($f);
            if ($g) {
                $stmtImg->execute([$id, $g, $i]);
            }
        }
    }

    header("Location: noticias.php");
    exit;
}

$galeria = $pdo->prepare("SELECT * FROM noticias_imagens WHERE noticia_id = ? ORDER BY ordem ASC, id ASC");
$galeria->execute([$id]);
$galeriaFotos = $galeria->fetchAll(PDO::FETCH_ASSOC);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlNoticia = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/noticia.php?id=' . $id;
$fbShare = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($urlNoticia);
?>

<style>
.noticia-form{background:white;border-radius:24px;padding:26px;box-shadow:0 16px 40px rgba(0,0,0,.08);border:1px solid #eef2f7;}
.noticia-form label{display:block;font-weight:900;color:#11151B;margin:16px 0 6px;}
.noticia-form .hint{color:#6b7280;font-weight:700;font-size:13px;margin:4px 0 0;}
.capa-atual{max-width:220px;border-radius:14px;margin:6px 0 4px;display:block;box-shadow:0 8px 20px rgba(0,0,0,.12);}
.galeria-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin:8px 0 4px;}
.galeria-item{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;}
.galeria-item img{width:100%;height:110px;object-fit:cover;display:block;}
.galeria-item-acts{display:flex;gap:6px;padding:8px;flex-wrap:wrap;}
.galeria-item-acts a{flex:1;text-align:center;font-size:12px;font-weight:900;text-decoration:none;padding:7px 8px;border-radius:9px;}
.gi-capa{background:#242A30;color:#fff;}
.gi-rem{background:#fee2e2;color:#991b1b;}
</style>

<form method="POST" enctype="multipart/form-data" class="noticia-form">
    <label>Título *</label>
    <input type="text" name="titulo" value="<?= htmlspecialchars($noticia['titulo']) ?>" required>

    <label>Descrição *</label>
    <textarea name="descricao" required><?= htmlspecialchars($noticia['descricao']) ?></textarea>

    <label>Data de publicação</label>
    <input type="datetime-local" name="data" value="<?= date('Y-m-d\TH:i', strtotime($noticia['data'])) ?>">

    <label>Categoria</label>
    <select name="categoria">
        <option value="">Sem categoria</option>
        <?php foreach ($GLOBALS['CATEGORIAS_CONTEUDO'] as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($noticia['categoria'] ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Imagem de capa (principal)</label>
    <?php if (!empty($noticia['imagem'])): ?>
        <img class="capa-atual" src="../assets/img/<?= htmlspecialchars($noticia['imagem']) ?>" alt="Capa atual">
    <?php else: ?>
        <p class="hint">Sem imagem de capa definida.</p>
    <?php endif; ?>
    <input type="file" name="imagem" id="imagemCapaInput" accept="image/*">
    <p class="hint">Escolher um ficheiro substitui a capa atual.</p>

    <div class="foco-picker-wrap" id="focoPickerWrap" hidden>
        <p class="hint">Clique na imagem para marcar o ponto mais importante (ex.: uma cara). Esse ponto mantém-se sempre visível, mesmo quando a imagem é cortada em proporções diferentes (cartão de listagem vs. topo da notícia).</p>
        <div class="foco-picker">
            <img id="focoPreviewImg" alt="Pré-visualização da imagem de capa">
            <div class="foco-marker" id="focoMarker"></div>
        </div>
        <input type="hidden" name="foco_x" id="focoX" value="<?= (int)$noticia['imagem_foco_x'] ?>">
        <input type="hidden" name="foco_y" id="focoY" value="<?= (int)$noticia['imagem_foco_y'] ?>">
    </div>

    <label>Adicionar fotos à galeria</label>
    <input type="file" name="galeria[]" accept="image/*" multiple>

    <div style="margin:18px 0;">
        <button class="btn">Guardar alterações</button>
        <a class="btn" style="background:#1877F2;" href="<?= htmlspecialchars($fbShare) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i> Partilhar no Facebook</a>
    </div>
</form>

<?php if (!empty($galeriaFotos)): ?>
    <div class="noticia-form">
        <label>Fotos da galeria (<?= count($galeriaFotos) ?>)</label>
        <p class="hint">Pode tornar qualquer foto na capa, ou removê-la.</p>
        <div class="galeria-grid">
            <?php foreach ($galeriaFotos as $g): ?>
                <div class="galeria-item">
                    <img src="../assets/img/<?= htmlspecialchars($g['ficheiro']) ?>" alt="">
                    <div class="galeria-item-acts">
                        <a class="gi-capa" href="editar_noticia.php?id=<?= $id ?>&capa=<?= (int)$g['id'] ?>" onclick="return confirm('Tornar esta foto a capa? A capa atual passa para a galeria.');">Tornar capa</a>
                        <a class="gi-rem" href="editar_noticia.php?id=<?= $id ?>&remover_img=<?= (int)$g['id'] ?>" onclick="return confirm('Remover esta foto?');">Remover</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

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
        <?php if (!empty($noticia['imagem'])): ?>
        ,existingUrl: "../assets/img/<?= htmlspecialchars($noticia['imagem'], ENT_QUOTES) ?>"
        ,initialX: <?= (int)$noticia['imagem_foco_x'] ?>
        ,initialY: <?= (int)$noticia['imagem_foco_y'] ?>
        <?php endif; ?>
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
