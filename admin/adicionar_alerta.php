<?php
$adminPageTitle = "Adicionar Alerta";
$adminActive = "alertas";
require_once "includes/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $mensagem = $_POST['mensagem'];
    $tipo = $_POST['tipo'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO alertas (titulo, mensagem, tipo, ativo, data_inicio, data_fim)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$titulo, $mensagem, $tipo, $ativo, $data_inicio, $data_fim]);

    header("Location: alertas.php");
    exit;
}
?>

<form method="POST">
    <input type="text" name="titulo" placeholder="Título do alerta" required>

    <textarea name="mensagem" placeholder="Mensagem do alerta" required></textarea>

    <label>Tipo</label>
    <select name="tipo">
        <option value="info">Informação</option>
        <option value="aviso">Aviso</option>
        <option value="urgente">Urgente</option>
        <option value="evento">Evento</option>
    </select>

    <label>Data de início</label>
    <input type="date" name="data_inicio">

    <label>Data de fim</label>
    <input type="date" name="data_fim">

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="ativo" value="1" checked style="width:auto;margin:0;">
        Alerta ativo
    </label>

    <button class="btn">Guardar alerta</button>
    <a class="btn secondary" href="alertas.php">Voltar</a>
</form>

<?php require_once "includes/footer.php"; ?>