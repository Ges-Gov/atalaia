<?php
/* Documentos da Freguesia — novo documento. Formulário comum com a Assembleia (includes/documentos_ui.php, 20/11/2026). */
$adminPageTitle = "Adicionar Documento";
$adminActive = "documentos";
require_once "includes/header.php";
require_once "includes/documentos_ui.php";

$categoriasPadrao = ['Atas', 'Editais', 'Convocatórias', 'Regulamentos', 'Planos e Orçamentos', 'Outros Documentos'];
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

    if ($titulo === '') {
        $erro = "Escreva o título do documento.";
    } elseif ($categoria === '') {
        $erro = "Escolha a categoria do documento.";
    } elseif (empty($_FILES['ficheiro']['name'])) {
        $erro = "Escolha o ficheiro PDF do documento.";
    } else {
        [$ext, $erro] = docVerificarFicheiro($_FILES['ficheiro'], 'pdf');
    }

    if ($erro === '') {
        $uploadDir = __DIR__ . "/../assets/docs/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $novoNome = "documento_" . date('YmdHis') . "_" . bin2hex(random_bytes(4)) . ".pdf";
        if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $uploadDir . $novoNome)) {
            $pdo->prepare("INSERT INTO documentos (titulo, descricao, tags, area, categoria, ficheiro, ativo, data_documento, data_limite) VALUES (?, ?, ?, 'freguesia', ?, ?, ?, ?, ?)")
                ->execute([$titulo, $descricao, $tags !== '' ? $tags : null, $categoria, $novoNome, $ativo, $data_documento, $data_limite]);
            header("Location: documentos.php?ok=criado");
            exit;
        }
        $erro = "Não foi possível guardar o ficheiro no servidor. Tente outra vez ou contacte a GesGov.";
    }
}

docEstilos();
$valor = fn($campo, $omissao = '') => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST[$campo] ?? '') : $omissao;
?>

<?php if ($erro !== ''): ?>
    <div class="admin-erro" role="alert" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?><?= !empty($_FILES['ficheiro']['name']) ? ' Volte a escolher o ficheiro antes de guardar.' : '' ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php docCampos([
        'valor' => $valor,
        'categorias' => docCategorias($pdo, 'documentos', $categoriasPadrao, (string)($_POST['categoria'] ?? '')),
        'aceitar' => 'pdf',
        'ficheiro' => '',
        'extras' => ['prazo' => true, 'tags' => true],
        'ativo' => $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['ativo']) : true,
    ]); ?>

    <button class="btn">Guardar documento</button>
    <a class="btn secondary" href="documentos.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>
