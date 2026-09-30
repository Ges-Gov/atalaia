<?php
$adminPageTitle = "Eventos";
$adminActive = "eventos";
require_once "includes/header.php";

$eventos = $pdo->query("SELECT * FROM eventos ORDER BY data_evento ASC")->fetchAll(PDO::FETCH_ASSOC);

$inscritosPorEvento = [];
try {
    $rowsInsc = $pdo->query("SELECT evento_id, COUNT(*) n, COALESCE(SUM(num_pessoas),0) pessoas FROM eventos_inscricoes GROUP BY evento_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsInsc as $r) $inscritosPorEvento[(int)$r['evento_id']] = $r;
} catch (Exception $e) {}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
?>

<style>
.admin-evento-thumb{
    width:82px;
    height:58px;
    object-fit:cover;
    border-radius:14px;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
}
.admin-evento-noimg{
    width:82px;
    height:58px;
    border-radius:14px;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:#D4AA00;
    display:grid;
    place-items:center;
    font-weight:900;
    font-size:22px;
}
</style>

<?php if (!empty($_GET['fb_novo'])): ?>
<?php
$fbNovoId = (int)$_GET['fb_novo'];
$fbNovaUrl = htmlspecialchars('https://www.facebook.com/sharer/sharer.php?u=' . urlencode($baseUrl . '/evento.php?id=' . $fbNovoId));
?>
<div style="background:#e7f0fb;border:1.5px solid #1877F2;border-radius:18px;padding:22px 24px;margin-bottom:22px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
        <strong style="display:block;color:#11151B;font-size:17px;margin-bottom:5px;"><i class="bi bi-check-circle-fill" style="color:#1877F2;margin-right:6px;"></i>Evento publicado com sucesso!</strong>
        <span style="color:#334155;font-size:14px;">Quer partilhar este evento na página do Facebook da Junta de Freguesia?</span>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?= $fbNovaUrl ?>" target="_blank" rel="noopener" class="btn" style="background:#1877F2;"><i class="bi bi-facebook"></i> Partilhar no Facebook</a>
        <a href="eventos.php" class="btn secondary">Não, obrigado</a>
    </div>
</div>
<?php endif; ?>

<div class="admin-actions">
    <a class="btn" href="adicionar_evento.php">Adicionar evento</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Imagem</th>
            <th>Título</th>
            <th>Data</th>
            <th>Inscrições</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($eventos as $e): ?>
            <tr>
                <td>
                    <?php if (!empty($e['imagem'])): ?>
                        <img class="admin-evento-thumb" src="../assets/img/<?= htmlspecialchars($e['imagem']) ?>" alt="<?= htmlspecialchars($e['titulo']) ?>">
                    <?php else: ?>
                        <div class="admin-evento-noimg"><i class="bi bi-calendar-event"></i></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($e['titulo']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></td>
                <td>
                    <?php if (!empty($e['inscricoes_ativas'])): ?>
                        <?php $ri = $inscritosPorEvento[(int)$e['id']] ?? ['n' => 0, 'pessoas' => 0]; ?>
                        <a class="btn secondary" href="evento_inscricoes.php?id=<?= (int)$e['id'] ?>">
                            <?= (int)$ri['pessoas'] ?><?= !empty($e['inscricoes_vagas']) ? '/' . (int)$e['inscricoes_vagas'] : '' ?> pessoa(s)
                        </a>
                    <?php else: ?>
                        <span style="color:#94a3b8;font-weight:700;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn secondary" href="editar_evento.php?id=<?= $e['id'] ?>">Editar</a>
                    <a class="btn" style="background:#1877F2;" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($baseUrl . '/evento.php?id=' . (int)$e['id']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i> Partilhar</a>
                    <a class="btn danger" href="eliminar_evento.php?id=<?= $e['id'] ?>" onclick="return confirm('Eliminar evento?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
