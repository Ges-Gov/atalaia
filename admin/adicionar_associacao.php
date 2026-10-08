<?php
$adminPageTitle = "Adicionar Associação";
$adminActive = "associacoes";
require_once "includes/header.php";

function uploadImagemAssociacao($campo) {
    if (empty($_FILES[$campo]['name'])) {
        return null;
    }

    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas)) {
        return null;
    }

    if (!is_dir("../assets/img")) {
        mkdir("../assets/img", 0777, true);
    }

    $base = pathinfo($_FILES[$campo]['name'], PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $base);
    $base = trim($base, '-');

    if ($base === '') {
        $base = 'associacao';
    }

    $nome = 'associacao-' . $base . '-' . time() . '.' . $ext;
    move_uploaded_file($_FILES[$campo]['tmp_name'], "../assets/img/" . $nome);

    return $nome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $morada = $_POST['morada'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $descricao = $_POST['descricao'];
    $website = trim($_POST['website'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $categoria = in_array($_POST['categoria'] ?? '', ['Cultura', 'Desporto', 'Comunidade'], true) ? $_POST['categoria'] : null;
    $outros_contactos = trim($_POST['outros_contactos'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $imagem = uploadImagemAssociacao('imagem');

    $stmt = $pdo->prepare("
        INSERT INTO associacoes (nome, morada, email, telefone, imagem, descricao, website, facebook, instagram, outros_contactos, latitude, longitude, categoria)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nome, $morada, $email, $telefone, $imagem, $descricao, $website, $facebook, $instagram, $outros_contactos, $latitude, $longitude, $categoria]);

    header("Location: associacoes.php");
    exit;
}
?>

<style>
.assoc-admin-form{
    background:white;
    border-radius:24px;
    padding:26px;
    box-shadow:0 16px 40px rgba(0,0,0,.08);
    border:1px solid #eef2f7;
}
.assoc-admin-form input,
.assoc-admin-form textarea{
    width:100%;
    box-sizing:border-box;
}
.assoc-upload-box{
    border:2px dashed #cbd5e1;
    border-radius:18px;
    padding:24px;
    background:#f8fafc;
    margin:12px 0;
}
.assoc-upload-box strong{
    display:block;
    color:#11151B;
    margin-bottom:8px;
}
</style>

<form method="POST" enctype="multipart/form-data" class="assoc-admin-form">
    <input type="text" name="nome" placeholder="Nome da associação" required>
    <select name="categoria">
        <?php $catAtual = $associacao['categoria'] ?? ''; ?>
        <option value="">Categoria (Cultura, Desporto ou Comunidade)</option>
        <?php foreach (['Cultura', 'Desporto', 'Comunidade'] as $c): ?>
            <option value="<?= $c ?>" <?= $catAtual === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <textarea name="morada" placeholder="Morada"></textarea>
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="telefone" placeholder="Telefone">
    <input type="url" name="website" placeholder="Website (https://...)">
    <input type="url" name="facebook" placeholder="Link do Facebook">
    <input type="url" name="instagram" placeholder="Link do Instagram">
    <textarea name="outros_contactos" placeholder="Outros contactos (2.º telefone, telemóvel, horário, pessoa de contacto...)"></textarea>

    <div class="assoc-upload-box">
        <strong>Imagem da associação</strong>
        <input type="file" name="imagem" accept="image/*">
    </div>

    <textarea name="descricao" placeholder="Descrição"></textarea>

    <div class="assoc-upload-box">
        <strong>Coordenadas (para aparecer no mapa)</strong>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:0 0 10px;">No Google Maps: botão direito no local → clica nas coordenadas para copiar e cola aqui.</p>
        <input type="text" name="latitude" placeholder="Latitude (ex.: 38.8403)">
        <input type="text" name="longitude" placeholder="Longitude (ex.: -7.2899)">
    </div>

    <button class="btn">Guardar associação</button>
    <a class="btn secondary" href="associacoes.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
