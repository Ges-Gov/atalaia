<?php
$adminPageTitle = "Modelos de Requerimentos";
$adminActive = "modelos_requerimentos";
require_once "includes/header.php";

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$titulo || !$tipo || empty($_FILES['ficheiro']['name'])) {
        $erro = "Preencha o título, tipo e selecione um ficheiro.";
    } else {
        $permitidas = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $permitidas)) {
            $erro = "O ficheiro deve ser PDF, DOC ou DOCX.";
        } else {
            $pasta = "../assets/docs/modelos/";

            if (!is_dir($pasta)) {
                mkdir($pasta, 0777, true);
            }

            $novoNome = "modelo_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $pasta . $novoNome)) {
                $stmt = $pdo->prepare("
                    INSERT INTO modelos_requerimentos
                    (titulo, tipo, descricao, ficheiro, ativo)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$titulo, $tipo, $descricao, $novoNome, $ativo]);

                $sucesso = "Modelo adicionado com sucesso.";
            } else {
                $erro = "Erro ao enviar o ficheiro.";
            }
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $stmt = $pdo->prepare("SELECT ativo FROM modelos_requerimentos WHERE id = ?");
    $stmt->execute([$id]);
    $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($modelo) {
        $novo = $modelo['ativo'] ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE modelos_requerimentos SET ativo = ? WHERE id = ?");
        $stmt->execute([$novo, $id]);
    }

    header("Location: modelos-requerimentos.php");
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    $stmt = $pdo->prepare("SELECT ficheiro FROM modelos_requerimentos WHERE id = ?");
    $stmt->execute([$id]);
    $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($modelo) {
        $ficheiro = "../assets/docs/modelos/" . $modelo['ficheiro'];

        if (file_exists($ficheiro)) {
            unlink($ficheiro);
        }

        $stmt = $pdo->prepare("DELETE FROM modelos_requerimentos WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: modelos-requerimentos.php");
    exit;
}

$modelos = $pdo->query("
    SELECT * FROM modelos_requerimentos
    ORDER BY criado_em DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-box">
    <h2>Adicionar modelo</h2>

    <?php if ($erro): ?>
        <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Título</label>
        <input type="text" name="titulo" placeholder="Ex: Atestado de residência" required>

        <label>Tipo</label>
        <select name="tipo" required>
            <option value="">Escolha o tipo</option>
            <option value="Atestado de residência">Atestado de residência</option>
            <option value="Declaração / comprovativo">Declaração / comprovativo</option>
            <option value="Pedido de certidão">Pedido de certidão</option>
            <option value="Licença / autorização">Licença / autorização</option>
            <option value="Outro requerimento">Outro requerimento</option>
        </select>

        <label>Descrição</label>
        <textarea name="descricao" placeholder="Pequena descrição do documento"></textarea>

        <label>Ficheiro</label>
        <input type="file" name="ficheiro" accept=".pdf,.doc,.docx" required>

        <label>
            <input type="checkbox" name="ativo" checked>
            Ativo / visível no site
        </label>

        <button class="btn" type="submit">Guardar modelo</button>
    </form>
</div>

<div class="table-box" style="margin-top:25px;">
    <table>
        <tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Ficheiro</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($modelos as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['titulo']) ?></td>
                <td><?= htmlspecialchars($m['tipo']) ?></td>
                <td><?= $m['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                    <a href="../assets/docs/modelos/<?= htmlspecialchars($m['ficheiro']) ?>" target="_blank">
                        Abrir
                    </a>
                </td>
                <td>
                    <a class="btn secondary" href="modelos-requerimentos.php?toggle=<?= $m['id'] ?>">
                        <?= $m['ativo'] ? 'Desativar' : 'Ativar' ?>
                    </a>

                    <a class="btn danger" href="modelos-requerimentos.php?eliminar=<?= $m['id'] ?>" onclick="return confirm('Eliminar este modelo?')">
                        Eliminar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($modelos)): ?>
            <tr>
                <td colspan="5">Ainda não existem modelos.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>