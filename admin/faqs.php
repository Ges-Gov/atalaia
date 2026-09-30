<?php
$adminPageTitle = "FAQ's";
$adminActive = "faqs";
require_once "includes/header.php";

$faqs = $pdo->query("SELECT * FROM faqs ORDER BY ordem ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-actions">
    <a class="btn" href="adicionar_faq.php">Adicionar pergunta</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Ordem</th>
            <th>Pergunta</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($faqs as $f): ?>
            <tr>
                <td><?= (int)$f['ordem'] ?></td>
                <td><?= htmlspecialchars($f['pergunta']) ?></td>
                <td><?= $f['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                    <a class="btn secondary" href="editar_faq.php?id=<?= $f['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_faq.php?id=<?= $f['id'] ?>" onclick="return confirm('Eliminar esta pergunta?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($faqs)): ?>
            <tr>
                <td colspan="4">Ainda não existem perguntas frequentes.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
