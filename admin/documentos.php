<?php
$adminPageTitle = "Documentos";
$adminActive = "documentos";
require_once "includes/header.php";

$documentos = $pdo->query("SELECT * FROM documentos ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-actions">
    <a class="btn" href="adicionar_documento.php">Adicionar documento</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Título</th>
            <th>Categoria</th>
            <th>Data</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($documentos as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['titulo']) ?></td>
                <td><?= htmlspecialchars($d['categoria']) ?></td>
                <td><?= !empty($d['data_documento']) ? date('d/m/Y', strtotime($d['data_documento'])) : '-' ?></td>
                <td><?= $d['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                    <a class="btn secondary" href="../assets/docs/<?= htmlspecialchars($d['ficheiro']) ?>" target="_blank">Ver</a>
                    <a class="btn secondary" href="editar_documento.php?id=<?= $d['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_documento.php?id=<?= $d['id'] ?>" onclick="return confirm('Eliminar documento?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>