<?php
$adminPageTitle = "Adicionar Economia Local";
$adminActive = "comercio";
require_once "includes/header.php";

function uploadImagemComercio($campo) {
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
        $base = 'comercio';
    }

    $nome = 'comercio-' . $base . '-' . time() . '.' . $ext;
    move_uploaded_file($_FILES[$campo]['tmp_name'], "../assets/img/" . $nome);

    return $nome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $tipo = $_POST['tipo'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $morada = $_POST['morada'];
    $website = trim($_POST['website'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $outros_contactos = trim($_POST['outros_contactos'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $imagem = uploadImagemComercio('imagem');

    $stmt = $pdo->prepare("
        INSERT INTO comercio_local (nome, tipo, telefone, imagem, email, morada, website, facebook, instagram, outros_contactos, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nome, $tipo, $telefone, $imagem, $email, $morada, $website, $facebook, $instagram, $outros_contactos, $latitude, $longitude]);

    header("Location: comercio.php");
    exit;
}
?>

<style>
.comercio-admin-form{
    background:white;
    border-radius:24px;
    padding:26px;
    box-shadow:0 16px 40px rgba(0,0,0,.08);
    border:1px solid #eef2f7;
}
.comercio-admin-form input,
.comercio-admin-form textarea{
    width:100%;
    box-sizing:border-box;
}
.comercio-upload-box{
    border:2px dashed #cbd5e1;
    border-radius:18px;
    padding:24px;
    background:#f8fafc;
    margin:12px 0;
}
.comercio-upload-box strong{
    display:block;
    color:#11151B;
    margin-bottom:8px;
}
</style>

<form method="POST" enctype="multipart/form-data" class="comercio-admin-form">
    <input type="text" name="nome" placeholder="Nome do estabelecimento" required>
    <input type="text" name="tipo" placeholder="Tipo / tag: Restaurante, Café, Turismo Rural, Minimercado...">
    <input type="text" name="telefone" placeholder="Telefone">

    <div class="comercio-upload-box">
        <strong>Imagem</strong>
        <input type="file" name="imagem" accept="image/*">
    </div>

    <input type="email" name="email" placeholder="Email">
    <input type="url" name="website" placeholder="Website (https://...)">
    <input type="url" name="facebook" placeholder="Link do Facebook">
    <input type="url" name="instagram" placeholder="Link do Instagram">
    <textarea name="morada" placeholder="Morada"></textarea>
    <textarea name="outros_contactos" placeholder="Outros contactos (2.º telefone, telemóvel, horário, pessoa de contacto...)"></textarea>

    <div class="comercio-upload-box">
        <strong>Coordenadas (para aparecer no mapa)</strong>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:0 0 10px;">No Google Maps: botão direito no local → clica nas coordenadas para copiar e cola aqui.</p>
        <input type="text" name="latitude" placeholder="Latitude (ex.: 38.8403)">
        <input type="text" name="longitude" placeholder="Longitude (ex.: -7.2899)">
    </div>

    <button class="btn">Guardar</button>
    <a class="btn secondary" href="comercio.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
