<?php
$adminPageTitle = "Editar Documento";
$adminActive = "documentos";
require_once "includes/header.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
$stmt->execute([$id]);
$documento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$documento) {
    die("Documento não encontrado.");
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $categoria = $_POST['categoria'];
    $data_documento = !empty($_POST['data_documento']) ? $_POST['data_documento'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $area = 'freguesia';
    $ficheiro = $documento['ficheiro'];

    if (!empty($_FILES['ficheiro']['name'])) {
        $ext = strtolower(pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $erro = "Apenas ficheiros PDF são permitidos. Converta o documento para PDF antes de enviar.";
        } else {
            $uploadDir = "../assets/docs/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $novoNome = "documento_" . time() . ".pdf";

            if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $uploadDir . $novoNome)) {
                if (!empty($ficheiro)) {
                    $old = $uploadDir . $ficheiro;
                    if (file_exists($old)) {
                        unlink($old);
                    }
                }
                $ficheiro = $novoNome;
            } else {
                $erro = "Não foi possível enviar o ficheiro para o servidor. Tente novamente ou contacte o suporte.";
            }
        }
    }

    if ($erro === '') {
        $stmt = $pdo->prepare("
            UPDATE documentos
            SET titulo = ?, descricao = ?, area = ?, categoria = ?, ficheiro = ?, ativo = ?, data_documento = ?
            WHERE id = ?
        ");
        $stmt->execute([$titulo, $descricao, $area, $categoria, $ficheiro, $ativo, $data_documento, $id]);

        header("Location: documentos.php");
        exit;
    }

    $documento = array_merge($documento, compact('titulo', 'descricao', 'categoria', 'data_documento', 'ativo'));
}
?>

<?php if ($erro !== ''): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="titulo" value="<?= htmlspecialchars($documento['titulo']) ?>" required>

    <textarea name="descricao"><?= htmlspecialchars($documento['descricao'] ?? '') ?></textarea>

    <label>Categoria</label>
    <select name="categoria" required>
        <?php foreach (['Atas', 'Editais', 'Convocatórias', 'Regulamentos', 'Planos e Orçamentos', 'Outros Documentos'] as $cat): ?>
            <option value="<?= $cat ?>" <?= $documento['categoria'] == $cat ? 'selected' : '' ?>><?= $cat ?></option>
        <?php endforeach; ?>
    </select>

    <label>Data do documento</label>
    <input type="date" name="data_documento" value="<?= htmlspecialchars($documento['data_documento'] ?? '') ?>">

    <?php if (!empty($documento['ficheiro'])): ?>
        <p>
            <strong>Ficheiro atual:</strong>
            <a href="../assets/docs/<?= htmlspecialchars($documento['ficheiro']) ?>" target="_blank">Ver PDF</a>
        </p>
    <?php endif; ?>

    <label>Novo PDF</label>
    <input type="file" name="ficheiro" accept="application/pdf">

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" <?= $documento['ativo'] ? 'checked' : '' ?> style="width:auto;margin:0;">
        Documento ativo
    </label>

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="documentos.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
