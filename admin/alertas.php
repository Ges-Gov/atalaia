<?php
$adminPageTitle = "Alertas";
$adminActive = "alertas";
require_once "includes/header.php";

$alertas = $pdo->query("SELECT * FROM alertas ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-actions">
    <a class="btn" href="adicionar_alerta.php">Adicionar alerta</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Data fim</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($alertas as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['titulo']) ?></td>
                <td><?= htmlspecialchars($a['tipo']) ?></td>
                <td><?= $a['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td><?= !empty($a['data_fim']) ? date('d/m/Y', strtotime($a['data_fim'])) : '-' ?></td>
                <td>
                    <a class="btn secondary" href="editar_alerta.php?id=<?= $a['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_alerta.php?id=<?= $a['id'] ?>" onclick="return confirm('Eliminar este alerta?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>