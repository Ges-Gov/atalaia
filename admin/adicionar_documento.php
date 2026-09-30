<?php
$adminPageTitle = "Adicionar Documento";
$adminActive = "documentos";
require_once "includes/header.php";

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $categoria = $_POST['categoria'];
    $data_documento = !empty($_POST['data_documento']) ? $_POST['data_documento'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $area = 'freguesia';

    $ficheiro = '';
    $uploadDir = "../assets/docs/";

    if (!empty($_FILES['ficheiro']['name'])) {
        $ext = strtolower(pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $erro = "Apenas ficheiros PDF são permitidos. Converta o documento para PDF antes de enviar.";
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $novoNome = "documento_" . time() . ".pdf";

            if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $uploadDir . $novoNome)) {
                $ficheiro = $novoNome;
            } else {
                $erro = "Não foi possível enviar o ficheiro para o servidor. Tente novamente ou contacte o suporte.";
            }
        }
    } else {
        $erro = "Selecione um ficheiro PDF.";
    }

    if ($erro === '') {
        $stmt = $pdo->prepare("
            INSERT INTO documentos (titulo, descricao, area, categoria, ficheiro, ativo, data_documento)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titulo, $descricao, $area, $categoria, $ficheiro, $ativo, $data_documento]);

        header("Location: documentos.php");
        exit;
    }
}
?>

<?php if ($erro !== ''): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="titulo" placeholder="Título do documento" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>

    <textarea name="descricao" placeholder="Descrição breve"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>

    <label>Categoria</label>
    <select name="categoria" required>
        <?php foreach (['Atas', 'Editais', 'Convocatórias', 'Regulamentos', 'Planos e Orçamentos', 'Outros Documentos'] as $cat): ?>
            <option value="<?= $cat ?>" <?= (($_POST['categoria'] ?? '') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
        <?php endforeach; ?>
    </select>

    <label>Data do documento</label>
    <input type="date" name="data_documento" value="<?= htmlspecialchars($_POST['data_documento'] ?? '') ?>">

    <label>Ficheiro PDF</label>
    <input type="file" name="ficheiro" accept="application/pdf" required>

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" checked style="width:auto;margin:0;">
        Documento ativo
    </label>

    <button class="btn">Guardar documento</button>
    <a class="btn secondary" href="documentos.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
