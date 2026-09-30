<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    die("Evento não encontrado.");
}

if (isset($_GET['apagar'])) {
    $del = $pdo->prepare("DELETE FROM eventos_inscricoes WHERE id = ? AND evento_id = ?");
    $del->execute([(int)$_GET['apagar'], $id]);
    header("Location: evento_inscricoes.php?id=" . $id);
    exit;
}

$stmtCamposDef = $pdo->prepare("SELECT * FROM eventos_campos_extra WHERE evento_id = ? ORDER BY ordem ASC, id ASC");
$stmtCamposDef->execute([$id]);
$camposDef = $stmtCamposDef->fetchAll(PDO::FETCH_ASSOC);
$camposLabelPorId = [];
foreach ($camposDef as $cd) $camposLabelPorId[$cd['id']] = $cd['label'];

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->prepare("SELECT nome, email, telefone, num_pessoas, observacoes, campos_extra, criado_em FROM eventos_inscricoes WHERE evento_id = ? ORDER BY criado_em ASC");
    $rows->execute([$id]);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="inscricoes-' . preg_replace('/[^a-z0-9\-]+/i', '-', $evento['titulo']) . '.csv"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    $cabecalho = ['Nome', 'Email', 'Telefone', 'Nº Pessoas'];
    foreach ($camposDef as $cd) $cabecalho[] = $cd['label'];
    $cabecalho[] = 'Observações';
    $cabecalho[] = 'Data de inscrição';
    fputcsv($out, $cabecalho);

    foreach ($rows as $r) {
        $extra = $r['campos_extra'] ? json_decode($r['campos_extra'], true) : [];
        $linha = [$r['nome'], $r['email'], $r['telefone'], $r['num_pessoas']];
        foreach ($camposDef as $cd) $linha[] = $extra[$cd['id']] ?? '';
        $linha[] = $r['observacoes'];
        $linha[] = date('d/m/Y H:i', strtotime($r['criado_em']));
        fputcsv($out, $linha);
    }
    fclose($out);
    exit;
}

$inscricoes = $pdo->prepare("SELECT * FROM eventos_inscricoes WHERE evento_id = ? ORDER BY criado_em DESC");
$inscricoes->execute([$id]);
$inscricoes = $inscricoes->fetchAll(PDO::FETCH_ASSOC);

$totalPessoas = 0;
foreach ($inscricoes as $insc) $totalPessoas += (int)$insc['num_pessoas'];

$adminPageTitle = "Inscrições — " . $evento['titulo'];
$adminActive = "eventos";
require_once "includes/header.php";
?>

<style>
.evento-insc-resumo{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;background:white;border:1px solid #eef2f7;border-radius:20px;padding:20px 22px;box-shadow:0 14px 34px rgba(0,0,0,.06);margin-bottom:20px;}
.evento-insc-resumo h3{margin:0 0 4px;color:#11151B;}
.evento-insc-resumo span{color:#64748b;font-weight:700;font-size:14px;}
</style>

<div class="admin-actions">
    <a class="btn secondary" href="editar_evento.php?id=<?= (int)$id ?>"><i class="bi bi-arrow-left"></i> Voltar ao evento</a>
    <?php if (!empty($inscricoes)): ?>
        <a class="btn" href="evento_inscricoes.php?id=<?= (int)$id ?>&export=csv"><i class="bi bi-download"></i> Exportar CSV</a>
    <?php endif; ?>
</div>

<div class="evento-insc-resumo">
    <div>
        <h3><?= htmlspecialchars($evento['titulo']) ?></h3>
        <span><?= count($inscricoes) ?> inscrição(ões) · <?= $totalPessoas ?> pessoa(s)<?= !empty($evento['inscricoes_vagas']) ? ' de ' . (int)$evento['inscricoes_vagas'] . ' vagas' : ' (sem limite de vagas)' ?></span>
    </div>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Nome</th>
            <th>Contacto</th>
            <th>Pessoas</th>
            <?php if (!empty($camposDef)): ?><th>Campos extra</th><?php endif; ?>
            <th>Observações</th>
            <th>Data</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($inscricoes as $insc): ?>
            <tr>
                <td><?= htmlspecialchars($insc['nome']) ?></td>
                <td>
                    <?= htmlspecialchars($insc['email']) ?>
                    <?php if (!empty($insc['telefone'])): ?><br><small><?= htmlspecialchars($insc['telefone']) ?></small><?php endif; ?>
                </td>
                <td><?= (int)$insc['num_pessoas'] ?></td>
                <?php if (!empty($camposDef)): ?>
                    <td>
                        <?php
                        $extraView = $insc['campos_extra'] ? json_decode($insc['campos_extra'], true) : [];
                        foreach ($extraView as $campoId => $valor):
                            if (!isset($camposLabelPorId[$campoId]) || $valor === '') continue;
                        ?>
                            <div><strong><?= htmlspecialchars($camposLabelPorId[$campoId]) ?>:</strong> <?= htmlspecialchars($valor) ?></div>
                        <?php endforeach; ?>
                    </td>
                <?php endif; ?>
                <td><?= nl2br(htmlspecialchars($insc['observacoes'] ?? '')) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($insc['criado_em'])) ?></td>
                <td>
                    <a class="btn danger" href="evento_inscricoes.php?id=<?= (int)$id ?>&apagar=<?= (int)$insc['id'] ?>" onclick="return confirm('Cancelar esta inscrição?')">Cancelar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($inscricoes)): ?>
            <tr><td colspan="<?= !empty($camposDef) ? 7 : 6 ?>">Ainda não existem inscrições para este evento.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
