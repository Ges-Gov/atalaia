<?php
/* Documentos da Freguesia — editar. Formulário comum com a Assembleia (includes/documentos_ui.php, 20/11/2026). */
$adminPageTitle = "Editar Documento";
$adminActive = "documentos";
require_once "includes/header.php";
require_once "includes/documentos_ui.php";

$categoriasPadrao = ['Atas', 'Editais', 'Convocatórias', 'Regulamentos', 'Planos e Orçamentos', 'Outros Documentos'];
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
$stmt->execute([$id]);
$documento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$documento) {
    echo '<div class="content-box"><h2>Documento não encontrado</h2><p>Pode ter sido eliminado.</p><a class="btn" href="documentos.php">Voltar aos documentos</a></div>';
    require_once "includes/footer.php";
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim((string)($_POST['titulo'] ?? ''));
    $descricao = trim((string)($_POST['descricao'] ?? ''));
    $categoria = trim((string)($_POST['categoria'] ?? ''));
    $data_documento = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_documento'] ?? '') ? $_POST['data_documento'] : null;
    // prazo de afixação/consulta pública: no dia seguinte o documento passa a inativo (cron/arquivar_editais.php)
    $data_limite = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_limite'] ?? '') ? $_POST['data_limite'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $tags = mb_substr(trim((string)($_POST['tags'] ?? '')), 0, 255);   // palavras-chave (migração 095)
    $ficheiro = $documento['ficheiro'];

    if ($titulo === '') {
        $erro = "Escreva o título do documento.";
    } elseif ($categoria === '') {
        $erro = "Escolha a categoria do documento.";
    } elseif (!empty($_FILES['ficheiro']['name'])) {
        [$ext, $erro] = docVerificarFicheiro($_FILES['ficheiro'], 'pdf');
        if ($erro === '') {
            $uploadDir = __DIR__ . "/../assets/docs/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            $novoNome = "documento_" . date('YmdHis') . "_" . bin2hex(random_bytes(4)) . ".pdf";
            if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $uploadDir . $novoNome)) {
                if ($ficheiro !== '' && is_file($uploadDir . basename($ficheiro))) @unlink($uploadDir . basename($ficheiro));
                $ficheiro = $novoNome;
            } else {
                $erro = "Não foi possível guardar o ficheiro no servidor. Tente outra vez ou contacte a GesGov.";
            }
        }
    }

    if ($erro === '') {
        $pdo->prepare("UPDATE documentos SET titulo = ?, descricao = ?, tags = ?, area = 'freguesia', categoria = ?, ficheiro = ?, ativo = ?, data_documento = ?, data_limite = ?,
                       arquivado_em = IF(? = 1, NULL, arquivado_em) WHERE id = ?")
            ->execute([$titulo, $descricao, $tags !== '' ? $tags : null, $categoria, $ficheiro, $ativo, $data_documento, $data_limite, $ativo, $id]);
        header("Location: documentos.php?ok=atualizado");
        exit;
    }
}

docEstilos();
$valor = fn($campo, $omissao = '') => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST[$campo] ?? '') : ($documento[$campo] ?? $omissao);
?>

<?php if ($erro !== ''): ?>
    <div class="admin-erro" role="alert" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php docCampos([
        'valor' => $valor,
        'categorias' => docCategorias($pdo, 'documentos', $categoriasPadrao, (string)$valor('categoria')),
        'aceitar' => 'pdf',
        'ficheiro' => !empty($documento['ficheiro']) ? '../assets/docs/' . $documento['ficheiro'] : '',
        'extras' => ['prazo' => true, 'tags' => true],
        'ativo' => $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['ativo']) : !empty($documento['ativo']),
    ]); ?>

    <button class="btn">Guardar documento</button>
    <a class="btn secondary" href="documentos.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
