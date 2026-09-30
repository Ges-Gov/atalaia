<?php
$adminPageTitle = "Ver Requerimento";
$adminActive = "requerimentos";
require_once "includes/header.php";
require_once "../includes/mail_helper.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM requerimentos WHERE id = ?");
$stmt->execute([$id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    die("Requerimento não encontrado.");
}

$stmtFiles = $pdo->prepare("
    SELECT * FROM requerimentos_ficheiros
    WHERE requerimento_id = ?
    ORDER BY id ASC
");
$stmtFiles->execute([$id]);
$ficheiros = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'] ?? $req['estado'];
    $resposta = trim($_POST['resposta'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE requerimentos
        SET estado = ?, resposta = ?, atualizado_em = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$estado, $resposta, $id]);

    if (!empty($req['email'])) {
        $html = "
            <h2>Atualização do requerimento</h2>
            <p>Olá {$req['nome']},</p>
            <p>O seu requerimento foi atualizado.</p>
            <p><strong>Código:</strong> {$req['codigo']}</p>
            <p><strong>Tipo:</strong> {$req['tipo']}</p>
            <p><strong>Assunto:</strong> {$req['assunto']}</p>
            <p><strong>Estado:</strong> " . str_replace('_', ' ', $estado) . "</p>
            <p><strong>Resposta:</strong><br>" . nl2br(htmlspecialchars($resposta)) . "</p>
            <br>
            <p><strong>" . siteConfig('nome_site') . "</strong></p>
        ";

        enviarEmailSistema($req['email'], "Atualização do seu requerimento", $html);
    }

    header("Location: ver_requerimento.php?id=" . $id);
    exit;
}
?>

<div class="content-box admin-detail-box">
    <h2><?= htmlspecialchars($req['assunto']) ?></h2>

    <p><strong>Código:</strong> <?= htmlspecialchars($req['codigo']) ?></p>
    <p><strong>Nome:</strong> <?= htmlspecialchars($req['nome']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($req['email']) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($req['telefone'] ?? '-') ?></p>
    <p><strong>Tipo:</strong> <?= htmlspecialchars($req['tipo']) ?></p>
    <p><strong>Estado:</strong> <?= htmlspecialchars(str_replace('_', ' ', $req['estado'])) ?></p>
    <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($req['criado_em'])) ?></p>

    <?php if (!empty($req['mensagem'])): ?>
        <hr>
        <h3>Mensagem</h3>
        <p><?= nl2br(htmlspecialchars($req['mensagem'])) ?></p>
    <?php endif; ?>

    <hr>

    <h3>Ficheiros anexados</h3>

    <?php if (!empty($ficheiros)): ?>
        <div class="dash-doc-list">
            <?php foreach ($ficheiros as $f): ?>
                <div class="dash-doc-item">
                    <div class="dash-doc-icon">DOC</div>
                    <div>
                        <strong><?= htmlspecialchars($f['nome_original'] ?: $f['ficheiro']) ?></strong>
                        <span><?= htmlspecialchars($f['ficheiro']) ?></span>
                    </div>
                    <a href="../assets/docs/requerimentos/<?= htmlspecialchars($f['ficheiro']) ?>" target="_blank">Abrir</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Sem ficheiros anexados.</p>
    <?php endif; ?>
</div>

<form method="POST">
    <h2>Gerir requerimento</h2>

    <label>Estado</label>
    <select name="estado">
        <option value="pendente" <?= $req['estado'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
        <option value="em_analise" <?= $req['estado'] == 'em_analise' ? 'selected' : '' ?>>Em análise</option>
        <option value="deferido" <?= $req['estado'] == 'deferido' ? 'selected' : '' ?>>Deferido</option>
        <option value="indeferido" <?= $req['estado'] == 'indeferido' ? 'selected' : '' ?>>Indeferido</option>
        <option value="concluido" <?= $req['estado'] == 'concluido' ? 'selected' : '' ?>>Concluído</option>
    </select>

    <label>Resposta / observações</label>
    <textarea name="resposta"><?= htmlspecialchars($req['resposta'] ?? '') ?></textarea>

    <button class="btn">Guardar e notificar cidadão</button>
    <a class="btn secondary" href="requerimentos.php">Voltar</a>
    <a class="btn danger" href="eliminar_requerimento.php?id=<?= $req['id'] ?>" onclick="return confirm('Eliminar este requerimento e os ficheiros anexados?')">Eliminar</a>
</form>

<?php require_once "includes/footer.php"; ?>