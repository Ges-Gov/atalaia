<?php
$adminPageTitle = "Economia Local";
$adminActive = "comercio";
require_once "includes/header.php";

$comercio = $pdo->query("SELECT * FROM comercio_local ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.admin-comercio-thumb{
    width:76px;
    height:56px;
    object-fit:cover;
    border-radius:14px;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
}
.admin-comercio-noimg{
    width:76px;
    height:56px;
    border-radius:14px;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:#D4AA00;
    display:grid;
    place-items:center;
    font-weight:900;
    font-size:22px;
}
</style>

<div class="admin-actions">
    <a class="btn" href="adicionar_comercio.php">Adicionar comércio</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Telefone</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($comercio as $c): ?>
            <tr>
                <td>
                    <?php if (!empty($c['imagem'])): ?>
                        <img class="admin-comercio-thumb" src="../assets/img/<?= htmlspecialchars($c['imagem']) ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
                    <?php else: ?>
                        <div class="admin-comercio-noimg"><i class="bi bi-shop"></i></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['nome']) ?></td>
                <td><?= htmlspecialchars($c['tipo']) ?></td>
                <td><?= htmlspecialchars($c['telefone']) ?></td>
                <td>
                    <a class="btn secondary" href="editar_comercio.php?id=<?= $c['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_comercio.php?id=<?= $c['id'] ?>" onclick="return confirm('Eliminar este comércio?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
