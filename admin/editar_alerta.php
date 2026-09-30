<?php
$adminPageTitle = "Editar Alerta";
$adminActive = "alertas";
require_once "includes/header.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM alertas WHERE id = ?");
$stmt->execute([$id]);
$alerta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alerta) {
    die("Alerta não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $mensagem = $_POST['mensagem'];
    $tipo = $_POST['tipo'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

    $stmt = $pdo->prepare("
        UPDATE alertas
        SET titulo = ?, mensagem = ?, tipo = ?, ativo = ?, data_inicio = ?, data_fim = ?
        WHERE id = ?
    ");
    $stmt->execute([$titulo, $mensagem, $tipo, $ativo, $data_inicio, $data_fim, $id]);

    header("Location: alertas.php");
    exit;
}
?>

<form method="POST">
    <input type="text" name="titulo" value="<?= htmlspecialchars($alerta['titulo']) ?>" required>

    <textarea name="mensagem" required><?= htmlspecialchars($alerta['mensagem']) ?></textarea>

    <label>Tipo</label>
    <select name="tipo">
        <option value="info" <?= $alerta['tipo'] == 'info' ? 'selected' : '' ?>>Informação</option>
        <option value="aviso" <?= $alerta['tipo'] == 'aviso' ? 'selected' : '' ?>>Aviso</option>
        <option value="urgente" <?= $alerta['tipo'] == 'urgente' ? 'selected' : '' ?>>Urgente</option>
        <option value="evento" <?= $alerta['tipo'] == 'evento' ? 'selected' : '' ?>>Evento</option>
    </select>

    <label>Data de início</label>
    <input type="date" name="data_inicio" value="<?= htmlspecialchars($alerta['data_inicio'] ?? '') ?>">

    <label>Data de fim</label>
    <input type="date" name="data_fim" value="<?= htmlspecialchars($alerta['data_fim'] ?? '') ?>">

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" <?= $alerta['ativo'] ? 'checked' : '' ?> style="width:auto;margin:0;">
        Alerta ativo
    </label>

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="alertas.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>