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
    // prazo de afixação/consulta pública: no dia seguinte o documento passa a inativo (cron/arquivar_editais.php)
    $data_limite = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_limite'] ?? '') ? $_POST['data_limite'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $area = 'freguesia';
    $ficheiro = $documento['ficheiro'];

    if (!empty($_FILES['ficheiro']['name'])) {
        $ext = strtolower(pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $erro = "Apenas ficheiros PDF são permitidos. Converta o documento para PDF antes de enviar.";
        } elseif (file_get_contents($_FILES['ficheiro']['tmp_name'], false, null, 0, 5) !== '%PDF-') {
            // a extensão não chega: o conteúdo tem de ser mesmo um PDF
            $erro = "O ficheiro não é um PDF válido.";
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
            SET titulo = ?, descricao = ?, area = ?, categoria = ?, ficheiro = ?, ativo = ?, data_documento = ?, data_limite = ?,
                arquivado_em = IF(? = 1, NULL, arquivado_em)
            WHERE id = ?
        ");
        $stmt->execute([$titulo, $descricao, $area, $categoria, $ficheiro, $ativo, $data_documento, $data_limite, $ativo, $id]);

        header("Location: documentos.php");
        exit;
    }

    $documento = array_merge($documento, compact('titulo', 'descricao', 'categoria', 'data_documento', 'data_limite', 'ativo'));
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

    <label>Prazo de afixação / consulta pública <small style="font-weight:400;color:#64748b;">(opcional — editais, consultas públicas)</small></label>
    <input type="date" name="data_limite" value="<?= htmlspecialchars($documento['data_limite'] ?? '') ?>">
    <p style="margin:-6px 0 16px;font-size:13px;color:#64748b;">No dia seguinte a esta data o documento sai do site automaticamente (fica inativo, pode voltar a ativá-lo). Sem data, fica sempre.</p>

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
