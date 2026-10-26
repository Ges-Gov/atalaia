<?php
$adminPageTitle = "Slides";
$adminActive = "slides";

/* Guardar a ordem (13/10/2026): pedido em segundo plano feito ao arrastar ou com as setas.
   Recebe a lista completa de ids pela nova ordem e renumera 1, 2, 3… */
/* Mostrar/ocultar um slide no site (13/10/2026): interruptor na coluna Estado, guardado sem recarregar */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'visivel') {
    require_once __DIR__ . "/includes/auth.php";
    require_once __DIR__ . "/../includes/db.php";
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_POST['id'] ?? 0);
    $v = (($_POST['valor'] ?? $_POST['ativo'] ?? '') === '1') ? 1 : 0;
    $st = $pdo->prepare("UPDATE slides_homepage SET ativo = ? WHERE id = ?");
    $st->execute([$v, $id]);
    $ok = (bool)$pdo->query("SELECT COUNT(*) FROM slides_homepage WHERE id = " . $id)->fetchColumn();
    if (!$ok) http_response_code(404);
    echo json_encode(['ok' => $ok, 'ativo' => $v]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'ordenar') {
    require_once __DIR__ . "/includes/auth.php";
    require_once __DIR__ . "/../includes/db.php";
    header('Content-Type: application/json; charset=utf-8');
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))))));
    $existentes = array_map('intval', $pdo->query("SELECT id FROM slides_homepage")->fetchAll(PDO::FETCH_COLUMN));
    sort($existentes); $cmp = $ids; sort($cmp);
    if ($cmp !== $existentes) { http_response_code(409); echo json_encode(['ok' => false, 'erro' => 'A lista mudou entretanto. Atualize a página.']); exit; }
    $up = $pdo->prepare("UPDATE slides_homepage SET ordem = ? WHERE id = ?");
    foreach ($ids as $i => $id) $up->execute([$i + 1, $id]);
    echo json_encode(['ok' => true]);
    exit;
}

require_once "includes/header.php";

try {
    $slides = $pdo->query("SELECT * FROM slides_homepage ORDER BY COALESCE(ordem, 999999), id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {   // migração 038 ainda não corrida
    $slides = $pdo->query("SELECT * FROM slides_homepage ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="admin-actions">
    <a class="btn" href="adicionar_slide.php">Adicionar Slide</a>
</div>

<p class="sl-ajuda"><i class="bi bi-arrows-move" aria-hidden="true"></i> Arraste pela pega <i class="bi bi-grip-vertical" aria-hidden="true"></i> ou use as setas para mudar a ordem. Use o interruptor da coluna Estado para ocultar um slide sem o apagar. Tudo fica guardado automaticamente.</p>

<div class="table-box">
    <table class="sl-tabela" data-sem-filtro>
        <thead><tr>
            <th>Ordem</th>
            <th>Imagem</th>
            <th>Título</th>
            <th>Subtítulo</th>
            <th>Link</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr></thead>
        <tbody id="slOrdem" data-ordenar="slides.php">
        <?php foreach ($slides as $k => $s): ?>
            <tr data-id="<?= (int)$s['id'] ?>" class="<?= $s['ativo'] ? '' : 'sl-oculto' ?>">
                <td class="sl-ordem">
                    <span class="sl-pega" title="Arrastar para mudar a ordem" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
                    <b class="sl-num"><?= $k + 1 ?>º</b>
                    <span class="sl-setas">
                        <button type="button" data-mover="-1" aria-label="Subir «<?= htmlspecialchars($s['titulo']) ?>»"><i class="bi bi-chevron-up"></i></button>
                        <button type="button" data-mover="1" aria-label="Descer «<?= htmlspecialchars($s['titulo']) ?>»"><i class="bi bi-chevron-down"></i></button>
                    </span>
                </td>
                <td>
                    <?php if (!empty($s['imagem'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($s['imagem']) ?>" style="width:110px;height:65px;object-fit:cover;border-radius:12px;">
                    <?php else: ?>
                        <span>Sem imagem</span>
                    <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($s['titulo']) ?><?php if (($s['tipo'] ?? '') === 'video'): ?> <span class="estado-badge estado-confirmada" style="margin-left:6px"><i class="bi bi-youtube"></i> Vídeo</span><?php endif; ?></td>
                <td><?= htmlspecialchars($s['subtitulo'] ?? '') ?></td>
                <td>
                    <?php if (!empty($s['link_destino'])): ?>
                        <a href="<?= htmlspecialchars($s['link_destino']) ?>" target="_blank">Ver link</a>
                    <?php else: ?>
                        <span>Sem link</span>
                    <?php endif; ?>
                </td>
                <td>
                    <label class="sl-interruptor" title="Mostrar ou ocultar este slide no site">
                        <input type="checkbox" data-alternar="visivel" data-escurecer data-txt-on="Visível" data-txt-off="Oculto" data-msg-on="Slide visível no site" data-msg-off="Slide oculto — já não aparece no site" <?= $s['ativo'] ? 'checked' : '' ?> aria-label="Mostrar «<?= htmlspecialchars($s['titulo']) ?>» no site">
                        <span class="sl-interruptor__trilho" aria-hidden="true"></span>
                        <span class="sl-interruptor__txt"><?= $s['ativo'] ? 'Visível' : 'Oculto' ?></span>
                    </label>
                </td>
                <td>
                    <a class="btn secondary" href="editar_slide.php?id=<?= $s['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_slide.php?id=<?= $s['id'] ?>" onclick="return confirm('Eliminar este slide?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="assets/ordenar.js?v=20261013a" defer></script>


<?php require_once "includes/footer.php"; ?>