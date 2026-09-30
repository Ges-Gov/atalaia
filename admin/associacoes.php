<?php
$adminPageTitle = "Associações";
$adminActive = "associacoes";
require_once "includes/header.php";

$associacoes = $pdo->query("SELECT * FROM associacoes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.admin-assoc-thumb{
    width:74px;
    height:54px;
    object-fit:cover;
    border-radius:12px;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
}
.admin-assoc-noimg{
    width:74px;
    height:54px;
    border-radius:12px;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:#D4AA00;
    display:grid;
    place-items:center;
    font-weight:900;
}
</style>

<div class="admin-actions">
    <a class="btn" href="adicionar_associacao.php">Adicionar associação</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Telefone</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($associacoes as $a): ?>
            <tr>
                <td>
                    <?php if (!empty($a['imagem'])): ?>
                        <img class="admin-assoc-thumb" src="../assets/img/<?= htmlspecialchars($a['imagem']) ?>" alt="<?= htmlspecialchars($a['nome']) ?>">
                    <?php else: ?>
                        <div class="admin-assoc-noimg"><i class="bi bi-people"></i></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($a['nome']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['telefone']) ?></td>
                <td>
                    <a class="btn secondary" href="editar_associacao.php?id=<?= $a['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_associacao.php?id=<?= $a['id'] ?>" onclick="return confirm('Eliminar esta associação?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
