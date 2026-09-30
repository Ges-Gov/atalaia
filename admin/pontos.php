<?php
$adminPageTitle = "Pontos de Interesse";
$adminActive = "pontos";
require_once "includes/header.php";

$pontos = $pdo->query("SELECT * FROM pontos_interesse ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-actions">
    <a class="btn" href="adicionar_ponto.php">Adicionar ponto</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Localização</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($pontos as $p): ?>
            <tr>
                <td>
                    <?php if (!empty($p['imagem'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($p['imagem']) ?>" style="width:80px;height:55px;object-fit:cover;border-radius:10px;">
                    <?php else: ?>
                        <span>Sem imagem</span>
                    <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($p['nome']) ?></td>
                <td><?= htmlspecialchars($p['localizacao']) ?></td>

                <td>
                    <a class="btn secondary" href="editar_ponto.php?id=<?= $p['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_ponto.php?id=<?= $p['id'] ?>" onclick="return confirm('Eliminar este ponto?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>