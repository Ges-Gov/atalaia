<?php
/**
 * Avisos à população (CORE) — barra no topo de todas as páginas.
 *
 * Mostra os alertas ativos (admin → Junta Virtual → Alertas) dentro do período definido.
 * Redesenho 10/2026: antes o texto corria em contínuo (letreiro), o que falha a WCAG 2.2.2
 * e é difícil de ler; agora é estático, com ícone, título e mensagem lado a lado.
 * Os avisos podem ser fechados (fica guardado no browser), exceto os "urgente".
 * Sem alertas ativos, não imprime nada (não deixa espaço vazio).
 */
if (empty($config['alertas_ativos'])) {
    return;
}

$hoje = date('Y-m-d');
try {
    $stmt = $pdo->prepare("
        SELECT * FROM alertas
        WHERE ativo = 1
          AND (data_inicio IS NULL OR data_inicio <= ?)
          AND (data_fim IS NULL OR data_fim >= ?)
        ORDER BY FIELD(tipo, 'urgente', 'aviso', 'evento', 'info'), criado_em DESC
        LIMIT 3
    ");
    $stmt->execute([$hoje, $hoje]);
    $alertasAtivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $alertasAtivos = [];
}
if (!$alertasAtivos) return;

$alertaTipos = [
    'urgente' => ['bi-exclamation-octagon-fill', 'Urgente'],
    'aviso'   => ['bi-exclamation-triangle-fill', 'Aviso'],
    'evento'  => ['bi-calendar-event-fill', 'Agenda'],
    'info'    => ['bi-info-circle-fill', 'Informação'],
];
?>
<div class="avisos" role="region" aria-label="Avisos à população">
    <?php foreach ($alertasAtivos as $a):
        $tipo = isset($alertaTipos[$a['tipo']]) ? $a['tipo'] : 'info';
        // a "chave" muda se o aviso for editado — assim um aviso alterado volta a aparecer
        $chave = 'aviso-' . (int)$a['id'] . '-' . substr(md5($a['titulo'] . $a['mensagem']), 0, 8);
    ?>
        <div class="aviso aviso--<?= $tipo ?>" data-aviso="<?= $chave ?>">
            <div class="aviso__interior">
                <span class="aviso__ico" aria-hidden="true"><i class="bi <?= $alertaTipos[$tipo][0] ?>"></i></span>
                <p class="aviso__texto">
                    <span class="aviso__tipo"><?= $alertaTipos[$tipo][1] ?>:</span>
                    <strong><?= htmlspecialchars($a['titulo']) ?></strong>
                    <span class="aviso__msg"><?= htmlspecialchars($a['mensagem']) ?></span>
                    <?php if (!empty($a['data_fim'])): ?>
                        <span class="aviso__data">(até <?= date('d/m', strtotime($a['data_fim'])) ?>)</span>
                    <?php endif; ?>
                </p>
                <?php if ($tipo !== 'urgente'): ?>
                    <button type="button" class="aviso__fechar" data-fechar-aviso aria-label="Fechar aviso: <?= htmlspecialchars($a['titulo']) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script>
(function () {
    // esconde já os avisos fechados antes (sem "piscar" ao carregar)
    document.querySelectorAll('[data-aviso]').forEach(function (el) {
        try { if (localStorage.getItem(el.dataset.aviso)) el.hidden = true; } catch (e) {}
        var b = el.querySelector('[data-fechar-aviso]');
        if (b) b.addEventListener('click', function () {
            try { localStorage.setItem(el.dataset.aviso, '1'); } catch (e) {}
            el.hidden = true;
            var m = document.getElementById('conteudo-principal'); if (m) m.focus({ preventScroll: true });
        });
    });
})();
</script>
