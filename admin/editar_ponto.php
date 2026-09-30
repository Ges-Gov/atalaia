<?php
$adminPageTitle = "Editar Ponto";
$adminActive = "pontos";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/galeria.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pontos_interesse WHERE id = ?");
$stmt->execute([$id]);
$ponto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ponto) {
    die("Ponto não encontrado.");
}

$errosFotos = [];

// Apagar uma foto do álbum do ponto
if (isset($_GET['apagar_foto'])) {
    apagarImagemDaGaleria($pdo, (int)$_GET['apagar_foto']);
    header("Location: editar_ponto.php?id=$id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];
    $descricao = $_POST['descricao'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $imagem = $ponto['imagem'];

    // Upload nova imagem principal
    if (!empty($_FILES['imagem']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $novoNome = "ponto_" . time() . "." . $ext;

        // Só apaga a antiga e troca o nome DEPOIS de confirmar que a nova foi mesmo
        // gravada — se a escrita falhar (permissões), ficávamos sem imagem nenhuma.
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], "../assets/img/" . $novoNome)) {
            if (!empty($imagem) && file_exists("../assets/img/" . $imagem)) {
                @unlink("../assets/img/" . $imagem);
            }
            $imagem = $novoNome;
        } else {
            $errosFotos[] = "Não foi possível gravar a imagem principal no servidor (permissões da pasta assets/img).";
        }
    }

    $stmt = $pdo->prepare("
        UPDATE pontos_interesse
        SET nome = ?, localizacao = ?, descricao = ?, imagem = ?, latitude = ?, longitude = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nome,
        $localizacao,
        $descricao,
        $imagem,
        $latitude,
        $longitude,
        $id
    ]);

    // Fotografias adicionais -> álbum da galeria com o nome do ponto.
    // Se o ponto já tinha álbum, o albumDaOrigem() reaproveita-o e atualiza o nome
    // (para o álbum acompanhar o ponto quando este é renomeado).
    $temAlbum = $pdo->prepare("SELECT id FROM galeria_albuns WHERE origem = 'ponto' AND origem_id = ?");
    $temAlbum->execute([$id]);
    $albumExistente = $temAlbum->fetchColumn();

    if (!empty($_FILES['fotos']['name'][0]) || $albumExistente) {
        $albumId = albumDaOrigem($pdo, 'ponto', $id, $nome);

        if (!empty($_FILES['fotos']['name'][0])) {
            [$n, $errosAlbum] = guardarImagensNoAlbum($pdo, $albumId, $_FILES['fotos']);
            $errosFotos = array_merge($errosFotos, $errosAlbum);
        }
    }

    if (empty($errosFotos)) {
        header("Location: pontos.php");
        exit;
    }

    // Recarregar para mostrar o estado atual junto com os erros
    $stmt = $pdo->prepare("SELECT * FROM pontos_interesse WHERE id = ?");
    $stmt->execute([$id]);
    $ponto = $stmt->fetch(PDO::FETCH_ASSOC);
}

$fotosAlbum = imagensDaOrigem($pdo, 'ponto', $id);
?>

<?php if (!empty($errosFotos)): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?php foreach ($errosFotos as $e): ?>
            <div><?= $e ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <input 
        type="text" 
        name="nome" 
        value="<?= htmlspecialchars($ponto['nome']) ?>" 
        placeholder="Nome do ponto"
        required
    >

    <input 
        type="text" 
        name="localizacao" 
        value="<?= htmlspecialchars($ponto['localizacao'] ?? '') ?>" 
        placeholder="Localização (ex: Nora, Barro Branco...)"
    >

    <textarea name="descricao" placeholder="Descrição"><?= htmlspecialchars($ponto['descricao'] ?? '') ?></textarea>

    <?php if (!empty($ponto['imagem'])): ?>
        <p><strong>Imagem atual:</strong></p>
        <img src="../assets/img/<?= htmlspecialchars($ponto['imagem']) ?>" style="max-width:300px;border-radius:12px;margin-bottom:10px;">
    <?php endif; ?>

    <label>Nova imagem principal</label>
    <input type="file" name="imagem" accept="image/*">

    <hr style="margin:22px 0;border:0;border-top:1px solid #e5e7eb;">

    <h3 style="margin:0 0 6px;">Fotografias (Galeria)</h3>
    <p style="color:#64748b;font-weight:700;margin:0 0 14px;">
        Estas fotografias ficam na <strong>Galeria</strong>, num álbum com o nome do ponto
        (<em><?= htmlspecialchars($ponto['nome']) ?></em>). O álbum é criado ou reaproveitado automaticamente.
    </p>

    <?php if (!empty($fotosAlbum)): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:16px;">
            <?php foreach ($fotosAlbum as $f): ?>
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:8px;background:#fff;">
                    <img src="<?= htmlspecialchars('..' . imagemGaleriaUrl($f['ficheiro'])) ?>" alt=""
                         style="width:100%;height:95px;object-fit:cover;border-radius:8px;display:block;margin-bottom:8px;">
                    <a class="btn danger" href="editar_ponto.php?id=<?= $id ?>&apagar_foto=<?= (int)$f['id'] ?>"
                       onclick="return confirm('Apagar esta fotografia?')">Apagar</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#94a3b8;">Ainda não há fotografias neste ponto.</p>
    <?php endif; ?>

    <label>Adicionar fotografias (pode escolher várias)</label>
    <input type="file" name="fotos[]" accept="image/*" multiple>

    <h3 style="margin-top:25px;">Localização no mapa</h3>

    <label>Latitude</label>
    <input 
        type="text" 
        name="latitude" 
        placeholder="Ex: 38.805123"
        value="<?= htmlspecialchars($ponto['latitude'] ?? '') ?>"
    >

    <label>Longitude</label>
    <input 
        type="text" 
        name="longitude" 
        placeholder="Ex: -7.462345"
        value="<?= htmlspecialchars($ponto['longitude'] ?? '') ?>"
    >

    <small style="display:block;margin-bottom:15px;color:#666;">
        Dica: botão direito no Google Maps → copiar coordenadas
    </small>

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="pontos.php">Voltar</a>

</form>

<?php require_once "includes/footer.php"; ?>