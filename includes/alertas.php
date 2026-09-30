
<?php
if (empty($config['alertas_ativos'])) {
    return;
}
?>



<?php
$hoje = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT * FROM alertas
    WHERE ativo = 1
    AND (data_inicio IS NULL OR data_inicio <= ?)
    AND (data_fim IS NULL OR data_fim >= ?)
    ORDER BY criado_em DESC
");
$stmt->execute([$hoje, $hoje]);
$alertasAtivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
























<?php if (!empty($alertasAtivos)): ?>

<?php
$tipoPrincipal = $alertasAtivos[0]['tipo'] ?? 'info';
?>

<div class="alertas-site alerta-<?= htmlspecialchars($tipoPrincipal) ?>">

    <div class="alerta-site">

        <div class="alerta-track">

            <?php foreach ($alertasAtivos as $a): ?>

                <div class="alerta-item">

                    <span class="alerta-icon">
                        <?php
                        switch($a['tipo']) {
                            case 'urgente':
                                echo '<i class="bi bi-exclamation-octagon-fill"></i>';
                                break;

                            case 'aviso':
                                echo '<i class="bi bi-exclamation-triangle-fill"></i>';
                                break;

                            case 'evento':
                                echo '<i class="bi bi-calendar-event-fill"></i>';
                                break;

                            default:
                                echo '<i class="bi bi-info-circle-fill"></i>';
                        }
                        ?>
                    </span>

                    <strong>
                        <?= htmlspecialchars($a['titulo']) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars($a['mensagem']) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>

<?php endif; ?>