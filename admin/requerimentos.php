<?php
$adminPageTitle = "Requerimentos";
$adminActive = "requerimentos";
require_once "includes/header.php";

$estado = $_GET['estado'] ?? '';

$where = '';
$params = [];

if ($estado !== '') {
    $where = "WHERE estado = ?";
    $params[] = $estado;
}

$stmt = $pdo->prepare("
    SELECT * FROM requerimentos
    $where
    ORDER BY criado_em DESC
");
$stmt->execute($params);
$requerimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-actions">
    <a class="btn secondary" href="requerimentos.php">Todos</a>
    <a class="btn secondary" href="requerimentos.php?estado=pendente">Pendentes</a>
    <a class="btn secondary" href="requerimentos.php?estado=em_analise">Em análise</a>
    <a class="btn secondary" href="requerimentos.php?estado=deferido">Deferidos</a>
    <a class="btn secondary" href="requerimentos.php?estado=indeferido">Indeferidos</a>
    <a class="btn secondary" href="requerimentos.php?estado=concluido">Concluídos</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Data</th>
            <th>Código</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Assunto</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($requerimentos as $r): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></td>
                <td><?= htmlspecialchars($r['codigo']) ?></td>
                <td><?= htmlspecialchars($r['nome']) ?></td>
                <td><?= htmlspecialchars($r['tipo']) ?></td>
                <td><?= htmlspecialchars($r['assunto']) ?></td>
                <td>
                    <span class="estado-badge estado-<?= htmlspecialchars($r['estado']) ?>">
                        <?= htmlspecialchars(str_replace('_', ' ', $r['estado'])) ?>
                    </span>
                </td>
                <td>
                    <a class="btn secondary" href="ver_requerimento.php?id=<?= $r['id'] ?>">Ver</a>
                    <a class="btn danger" href="eliminar_requerimento.php?id=<?= $r['id'] ?>" onclick="return confirm('Eliminar este requerimento e os ficheiros anexados?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($requerimentos)): ?>
            <tr>
                <td colspan="7">Ainda não existem requerimentos.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>