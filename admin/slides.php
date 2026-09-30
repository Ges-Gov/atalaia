<?php
$adminPageTitle = "Slides";
$adminActive = "slides";
require_once "includes/header.php";

$slides = $pdo->query("SELECT * FROM slides_homepage ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-actions">
    <a class="btn" href="adicionar_slide.php">Adicionar Slide</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Imagem</th>
            <th>Título</th>
            <th>Subtítulo</th>
            <th>Link</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($slides as $s): ?>
            <tr>
                <td>
                    <?php if (!empty($s['imagem'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($s['imagem']) ?>" style="width:110px;height:65px;object-fit:cover;border-radius:12px;">
                    <?php else: ?>
                        <span>Sem imagem</span>
                    <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($s['titulo']) ?></td>
                <td><?= htmlspecialchars($s['subtitulo']) ?></td>
                <td>
                    <?php if (!empty($s['link_destino'])): ?>
                        <a href="<?= htmlspecialchars($s['link_destino']) ?>" target="_blank">Ver link</a>
                    <?php else: ?>
                        <span>Sem link</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $s['ativo'] ? 'Ativo' : 'Inativo' ?>
                </td>
                <td>
                    <a class="btn secondary" href="editar_slide.php?id=<?= $s['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_slide.php?id=<?= $s['id'] ?>" onclick="return confirm('Eliminar este slide?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>