<?php
$adminPageTitle = "Adicionar Ponto";
$adminActive = "pontos";

require_once "includes/header.php";
require_once __DIR__ . "/../includes/galeria.php";

$errosFotos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    $imagem = '';

    if (!empty($_FILES['imagem']['name'])) {

        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

        $imagem = "ponto_" . time() . "." . $ext;

        // Confirmar que a escrita correu bem: se falhar (permissões da pasta no
        // servidor) não guardamos o nome, senão a BD ficaria a apontar para um
        // ficheiro que não existe.
        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], "../assets/img/" . $imagem)) {
            $imagem = '';
            $errosFotos[] = "Não foi possível gravar a imagem principal no servidor (permissões da pasta assets/img).";
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO pontos_interesse
        (
            nome,
            localizacao,
            descricao,
            imagem,
            latitude,
            longitude
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $local,
        $descricao,
        $imagem,
        $latitude,
        $longitude
    ]);

    // Fotos adicionais: vão para a galeria, num álbum com o NOME DO PONTO.
    // O álbum é criado automaticamente (ou reaproveitado, se já existir) —
    // as imagens não são duplicadas: a página do ponto lê-as da galeria.
    $pontoId = (int)$pdo->lastInsertId();

    if (!empty($_FILES['fotos']['name'][0])) {
        $albumId = albumDaOrigem($pdo, 'ponto', $pontoId, $nome);
        [$n, $errosAlbum] = guardarImagensNoAlbum($pdo, $albumId, $_FILES['fotos']);
        $errosFotos = array_merge($errosFotos, $errosAlbum);
    }

    // Só sai se correu tudo bem; havendo erros de upload, mostra-os em vez de os
    // engolir silenciosamente no redirect.
    if (empty($errosFotos)) {
        header("Location: pontos.php");
        exit;
    }
}
?>

<?php if (!empty($errosFotos)): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?php foreach ($errosFotos as $e): ?>
            <div><?= $e ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-form-premium">

    <div class="form-grid">

        <div class="form-group">
            <label>Nome do ponto</label>
            <input type="text" name="nome" required>
        </div>

        <div class="form-group">
            <label>Localização</label>
            <input type="text" name="local" placeholder="Ex: Nora, Barro Branco...">
        </div>

    </div>

    <div class="form-group">
        <label>Descrição</label>
        <textarea name="descricao" rows="6"></textarea>
    </div>

    <div class="form-group">
        <label>Imagem principal</label>
        <input type="file" name="imagem" accept="image/*">
    </div>

    <div class="form-group">
        <label>Fotografias (pode escolher várias)</label>
        <input type="file" name="fotos[]" accept="image/*" multiple>
        <small style="display:block;margin-top:6px;color:#64748b;font-weight:700;">
            Estas fotografias vão automaticamente para a <strong>Galeria</strong>, agrupadas
            num álbum com o nome do ponto.
        </small>
    </div>

    <div class="coords-box">

        <h3><i class="bi bi-geo-alt"></i> Localização no mapa</h3>

        <div class="form-grid">

            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="latitude" placeholder="38.805">
            </div>

            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="longitude" placeholder="-7.46">
            </div>

        </div>

        <small>
            Dica: Google Maps → botão direito → “O que está aqui?” → copiar coordenadas.
        </small>

    </div>

    <div class="admin-actions">
        <button class="btn">
            Guardar ponto
        </button>

        <a class="btn secondary" href="pontos.php">
            Voltar
        </a>
    </div>

</form>

<?php require_once "includes/footer.php"; ?>