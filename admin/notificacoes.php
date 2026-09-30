<?php
$adminPageTitle = "Notificações";
$adminActive = "notificacoes";
require_once "includes/header.php";

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'geral');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$titulo || !$mensagem) {
        $erro = "Preencha o título e a mensagem.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (titulo, mensagem, tipo, ativo)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$titulo, $mensagem, $tipo, $ativo]);

        $sucesso = "Notificação criada com sucesso.";
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $stmt = $pdo->prepare("SELECT ativo FROM notificacoes WHERE id = ?");
    $stmt->execute([$id]);
    $n = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($n) {
        $novo = $n['ativo'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE notificacoes SET ativo = ? WHERE id = ?");
        $stmt->execute([$novo, $id]);
    }

    header("Location: notificacoes.php");
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    $pdo->prepare("DELETE FROM notificacoes_lidas WHERE notificacao_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM notificacoes WHERE id = ?")->execute([$id]);

    header("Location: notificacoes.php");
    exit;
}

$notificacoes = $pdo->query("
    SELECT * FROM notificacoes
    ORDER BY criado_em DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-box">
    <h2>Criar notificação</h2>

    <?php if ($erro): ?>
        <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Título</label>
        <input type="text" name="titulo" placeholder="Ex: Corte de água amanhã" required>

        <label>Tipo</label>
        <select name="tipo">
            <option value="geral">Geral</option>
            <option value="urgente">Urgente</option>
            <option value="evento">Evento</option>
            <option value="servico">Serviço</option>
            <option value="documento">Documento</option>
        </select>

        <label>Mensagem</label>
        <textarea name="mensagem" placeholder="Escreva a mensagem..." required></textarea>

        <label>
            <input type="checkbox" name="ativo" checked>
            Ativa / visível para cidadãos
        </label>

        <button class="btn" type="submit">Publicar notificação</button>
    </form>
</div>

<div class="table-box" style="margin-top:25px;">
    <table>
        <tr>
            <th>Data</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($notificacoes as $n): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></td>
                <td><?= htmlspecialchars($n['titulo']) ?></td>
                <td><?= htmlspecialchars($n['tipo']) ?></td>
                <td><?= $n['ativo'] ? 'Ativa' : 'Inativa' ?></td>
                <td>
                    <a class="btn secondary" href="notificacoes.php?toggle=<?= $n['id'] ?>">
                        <?= $n['ativo'] ? 'Desativar' : 'Ativar' ?>
                    </a>

                    <a class="btn danger" href="notificacoes.php?eliminar=<?= $n['id'] ?>" onclick="return confirm('Eliminar esta notificação?')">
                        Eliminar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($notificacoes)): ?>
            <tr>
                <td colspan="5">Ainda não existem notificações.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>