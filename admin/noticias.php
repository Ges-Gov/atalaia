<?php
$adminPageTitle = "Notícias";
$adminActive = "noticias";
require_once "includes/header.php";

$noticias = $pdo->query("SELECT * FROM noticias ORDER BY data DESC")->fetchAll(PDO::FETCH_ASSOC);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

function urlPartilhaFacebook($baseUrl, $id) {
    $urlNoticia = $baseUrl . '/noticia.php?id=' . (int)$id;
    return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($urlNoticia);
}
?>

<?php if (!empty($_GET['fb_novo'])): ?>
<?php
$fbNovoId = (int)$_GET['fb_novo'];
$fbNovaUrl = htmlspecialchars('https://www.facebook.com/sharer/sharer.php?u=' . urlencode($baseUrl . '/noticia.php?id=' . $fbNovoId));
?>
<div style="background:#e7f0fb;border:1.5px solid #1877F2;border-radius:18px;padding:22px 24px;margin-bottom:22px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
        <strong style="display:block;color:#11151B;font-size:17px;margin-bottom:5px;"><i class="bi bi-check-circle-fill" style="color:#1877F2;margin-right:6px;"></i>Notícia publicada com sucesso!</strong>
        <span style="color:#334155;font-size:14px;">Quer partilhar esta notícia na página do Facebook da Junta de Freguesia?</span>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?= $fbNovaUrl ?>" target="_blank" rel="noopener" class="btn" style="background:#1877F2;"><i class="bi bi-facebook"></i> Partilhar no Facebook</a>
        <a href="noticias.php" class="btn secondary">Não, obrigado</a>
    </div>
</div>
<?php endif; ?>

<div class="admin-actions">
    <a class="btn" href="adicionar_noticia.php">Adicionar notícia</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Título</th>
            <th>Data</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($noticias as $n): ?>
            <tr>
                <td><?= htmlspecialchars($n['titulo']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($n['data'])) ?></td>
                <td>
                    <a class="btn secondary" href="editar_noticia.php?id=<?= $n['id'] ?>">Editar</a>
                    <a class="btn" style="background:#1877F2;" href="<?= htmlspecialchars(urlPartilhaFacebook($baseUrl, $n['id'])) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i> Partilhar</a>
                    <a class="btn danger" href="eliminar_noticia.php?id=<?= $n['id'] ?>" onclick="return confirm('Eliminar notícia?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>