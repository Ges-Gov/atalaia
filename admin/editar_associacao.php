<?php
$adminPageTitle = "Editar Associação";
$adminActive = "associacoes";
require_once "includes/header.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM associacoes WHERE id = ?");
$stmt->execute([$id]);
$associacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$associacao) {
    die("Associação não encontrada.");
}

function uploadImagemAssociacao($campo, $imagemAtual = '') {
    if (empty($_FILES[$campo]['name'])) {
        return $imagemAtual;
    }

    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas)) {
        return $imagemAtual;
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

    if (!empty($imagemAtual) && file_exists("../assets/img/" . $imagemAtual)) {
        @unlink("../assets/img/" . $imagemAtual);
    }

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
    $outros_contactos = trim($_POST['outros_contactos'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $imagem = uploadImagemAssociacao('imagem', $associacao['imagem'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE associacoes
        SET nome = ?, morada = ?, email = ?, telefone = ?, imagem = ?, descricao = ?,
            website = ?, facebook = ?, instagram = ?, outros_contactos = ?, latitude = ?, longitude = ?
        WHERE id = ?
    ");
    $stmt->execute([$nome, $morada, $email, $telefone, $imagem, $descricao, $website, $facebook, $instagram, $outros_contactos, $latitude, $longitude, $id]);

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
.assoc-preview{
    width:260px;
    height:160px;
    object-fit:cover;
    border-radius:18px;
    box-shadow:0 14px 34px rgba(0,0,0,.14);
    margin:12px 0;
    display:block;
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
    <input type="text" name="nome" value="<?= htmlspecialchars($associacao['nome']) ?>" required>

    <textarea name="morada" placeholder="Morada"><?= htmlspecialchars($associacao['morada'] ?? '') ?></textarea>

    <input type="email" name="email" value="<?= htmlspecialchars($associacao['email'] ?? '') ?>" placeholder="Email">

    <input type="text" name="telefone" value="<?= htmlspecialchars($associacao['telefone'] ?? '') ?>" placeholder="Telefone">
    <input type="url" name="website" value="<?= htmlspecialchars($associacao['website'] ?? '') ?>" placeholder="Website (https://...)">
    <input type="url" name="facebook" value="<?= htmlspecialchars($associacao['facebook'] ?? '') ?>" placeholder="Link do Facebook">
    <input type="url" name="instagram" value="<?= htmlspecialchars($associacao['instagram'] ?? '') ?>" placeholder="Link do Instagram">
    <textarea name="outros_contactos" placeholder="Outros contactos (2.º telefone, telemóvel, horário, pessoa de contacto...)"><?= htmlspecialchars($associacao['outros_contactos'] ?? '') ?></textarea>

    <div class="assoc-upload-box">
        <strong>Imagem da associação</strong>

        <?php if (!empty($associacao['imagem'])): ?>
            <img class="assoc-preview" src="../assets/img/<?= htmlspecialchars($associacao['imagem']) ?>" alt="<?= htmlspecialchars($associacao['nome']) ?>">
        <?php endif; ?>

        <input type="file" name="imagem" accept="image/*">
    </div>

    <textarea name="descricao" placeholder="Descrição"><?= htmlspecialchars($associacao['descricao'] ?? '') ?></textarea>

    <div class="assoc-upload-box">
        <strong>Coordenadas (para aparecer no mapa)</strong>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:0 0 10px;">No Google Maps: botão direito no local → clica nas coordenadas para copiar e cola aqui.</p>
        <input type="text" name="latitude" value="<?= htmlspecialchars($associacao['latitude'] ?? '') ?>" placeholder="Latitude (ex.: 38.8403)">
        <input type="text" name="longitude" value="<?= htmlspecialchars($associacao['longitude'] ?? '') ?>" placeholder="Longitude (ex.: -7.2899)">
    </div>

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="associacoes.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
