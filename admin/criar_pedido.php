<?php
$adminPageTitle = "Criar Ocorrência";
$adminActive = "pedidos";
require_once "includes/header.php";

$categoriasOcorrencia = $pdo->query("
    SELECT id, designacao FROM ocorrencias_categorias WHERE ativo = 1 ORDER BY ordem ASC, designacao ASC
")->fetchAll(PDO::FETCH_ASSOC);

$assuntosPorCategoria = [];
$stmtAssuntos = $pdo->query("
    SELECT a.designacao, c.designacao AS categoria
    FROM ocorrencias_assuntos a
    JOIN ocorrencias_categorias c ON c.id = a.categoria_id
    WHERE a.ativo = 1
    ORDER BY a.ordem ASC, a.designacao ASC
");
foreach ($stmtAssuntos->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $assuntosPorCategoria[$a['categoria']][] = $a['designacao'];
}

$prioridadesDb = $pdo->query("SELECT slug, designacao FROM ocorrencias_prioridades WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);
$entidadesExternas = $pdo->query("SELECT designacao FROM ocorrencias_entidades_externas WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);

$origensLabel = [
    'website' => 'Website', 'telefone' => 'Telefone', 'balcao' => 'Balcão', 'oficio' => 'Ofício',
    'redes_sociais' => 'Redes Sociais', 'app_movel' => 'Aplicação Móvel', 'email' => 'Email', 'outro' => 'Outro',
];

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $subcategoria = trim($_POST['subcategoria'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $prioridadesValidas = array_column($prioridadesDb, 'slug');
    $prioridade = in_array($_POST['prioridade'] ?? '', $prioridadesValidas, true) ? $_POST['prioridade'] : ($prioridadesValidas[0] ?? 'normal');
    $competencia = trim($_POST['competencia'] ?? '') ?: 'Junta de Freguesia';

    $origem = trim($_POST['origem'] ?? 'telefone');
    if (!array_key_exists($origem, $origensLabel)) {
        $origem = 'outro';
    }

    if (!$nome || !$categoria || !$assunto || !$mensagem) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $subcategoriaFinal = $subcategoria !== '' ? $subcategoria : null;

        do {
            $codigoPedido = "AAEJ-" . date('Y') . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmtCheck = $pdo->prepare("SELECT id FROM pedidos_junta WHERE codigo = ?");
            $stmtCheck->execute([$codigoPedido]);
        } while ($stmtCheck->fetch());

        $stmt = $pdo->prepare("
            INSERT INTO pedidos_junta
            (codigo, nome, email, telefone, categoria, subcategoria, assunto, mensagem, localizacao, origem, prioridade, competencia, operador_id,
             nome_original, email_original, telefone_original, categoria_original, subcategoria_original, assunto_original, mensagem_original, localizacao_original)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $codigoPedido, $nome, $email ?: null, $telefone ?: null, $categoria, $subcategoriaFinal, $assunto, $mensagem, $localizacao ?: null,
            $origem, $prioridade, $competencia, $adminId,
            $nome, $email ?: null, $telefone ?: null, $categoria, $subcategoriaFinal, $assunto, $mensagem, $localizacao ?: null,
        ]);

        $novoId = $pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao)
            VALUES (?, 'criacao', 'Ocorrência registada', ?)
        ")->execute([$novoId, 'Registada no backoffice por ' . $adminNome . ' (origem: ' . $origem . ').']);

        header("Location: ver_pedido.php?id=" . $novoId);
        exit;
    }
}
?>

<div class="content-box">
    <h2>Criar Ocorrência</h2>
    <p><small>Para registar ocorrências reportadas por telefone, presencialmente, ou por outro meio que não o formulário público do site.</small></p>

    <?php if ($erro): ?>
        <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <input type="text" name="nome" placeholder="Nome do cidadão *" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-grid">
            <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">

            <select name="origem">
                <?php foreach ($origensLabel as $val => $label): ?>
                    <?php if ($val === 'website') continue; // esta página é só para registo manual, "Website" não faz sentido aqui ?>
                    <option value="<?= $val ?>" <?= ($_POST['origem'] ?? 'telefone') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid">
            <select name="categoria" id="categoriaOcorrencia" required>
                <option value="">Escolha a categoria *</option>
                <?php foreach ($categoriasOcorrencia as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['designacao']) ?>"><?= htmlspecialchars($cat['designacao']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="subcategoria" id="subcategoriaOcorrencia">
                <option value="">Subcategoria (opcional)</option>
            </select>
        </div>

        <input type="text" name="assunto" placeholder="Assunto *" value="<?= htmlspecialchars($_POST['assunto'] ?? '') ?>" required>

        <input type="text" name="localizacao" placeholder="Localização / Rua / Lugar" value="<?= htmlspecialchars($_POST['localizacao'] ?? '') ?>">

        <textarea name="mensagem" placeholder="Descreva a ocorrência *" required><?= htmlspecialchars($_POST['mensagem'] ?? '') ?></textarea>

        <div class="form-grid">
            <select name="prioridade">
                <?php foreach ($prioridadesDb as $pr): ?>
                    <option value="<?= htmlspecialchars($pr['slug']) ?>" <?= ($_POST['prioridade'] ?? 'normal') === $pr['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['designacao']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="competencia">
                <?php foreach ($entidadesExternas as $ent): ?>
                    <option value="<?= htmlspecialchars($ent['designacao']) ?>" <?= ($_POST['competencia'] ?? 'Junta de Freguesia') === $ent['designacao'] ? 'selected' : '' ?>><?= htmlspecialchars($ent['designacao']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn">Criar Ocorrência</button>
        <a class="btn secondary" href="pedidos.php">Cancelar</a>
    </form>
</div>

<script>
const assuntosPorCategoria = <?= json_encode($assuntosPorCategoria, JSON_UNESCAPED_UNICODE) ?>;

document.addEventListener("DOMContentLoaded", function () {
    const selectCategoria = document.getElementById('categoriaOcorrencia');
    const selectSubcategoria = document.getElementById('subcategoriaOcorrencia');

    if (!selectCategoria || !selectSubcategoria) return;

    function criarOpcao(valor, texto) {
        const opt = document.createElement('option');
        opt.value = valor;
        opt.textContent = texto;
        return opt;
    }

    function atualizarSubcategorias() {
        const lista = assuntosPorCategoria[selectCategoria.value] || [];

        selectSubcategoria.replaceChildren(criarOpcao('', 'Subcategoria (opcional)'));

        lista.forEach(function (designacao) {
            selectSubcategoria.appendChild(criarOpcao(designacao, designacao));
        });

        selectSubcategoria.disabled = lista.length === 0;
    }

    selectCategoria.addEventListener('change', atualizarSubcategorias);
    atualizarSubcategorias();
});
</script>

<?php require_once "includes/footer.php"; ?>
