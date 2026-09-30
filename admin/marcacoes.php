<?php
$adminPageTitle = "Marcações";
$adminActive = "marcacoes";
require_once "includes/header.php";

$estado = $_GET['estado'] ?? '';
$responsavelFiltro = $_GET['responsavel'] ?? '';

$where = [];
$params = [];

if ($estado !== '') {
    $where[] = "estado = ?";
    $params[] = $estado;
}

if ($responsavelFiltro !== '') {
    $where[] = "responsavel = ?";
    $params[] = $responsavelFiltro;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare("
    SELECT * FROM marcacoes_atendimento
    $whereSql
    ORDER BY data_marcacao ASC, hora_marcacao ASC
");
$stmt->execute($params);
$marcacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$todasMarcacoes = $pdo->query("
    SELECT * FROM marcacoes_atendimento
    ORDER BY data_marcacao ASC, hora_marcacao ASC
")->fetchAll(PDO::FETCH_ASSOC);

$responsaveis = [
    'Presidente',
    'Vice-Presidente',
    'Vogal',
    'Técnico Administrativo',
    'Gabinete Técnico',
    'Outro Responsável'
];

$hoje = date('Y-m-d');
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$diasSemana = [];

for ($i = 0; $i < 7; $i++) {
    $diasSemana[] = date('Y-m-d', strtotime($inicioSemana . " +$i days"));
}

$kpis = [
    'total' => count($todasMarcacoes),
    'pendente' => 0,
    'confirmada' => 0,
    'cancelada' => 0,
    'concluida' => 0,
    'hoje' => 0
];

foreach ($todasMarcacoes as $m) {
    if (isset($kpis[$m['estado']])) {
        $kpis[$m['estado']]++;
    }

    if ($m['data_marcacao'] == $hoje) {
        $kpis['hoje']++;
    }
}

function corResponsavelMarcacao($responsavel) {
    switch ($responsavel) {
        case 'Presidente':
            return '#2563eb';
        case 'Vice-Presidente':
            return '#7c3aed';
        case 'Vogal':
            return '#16a34a';
        case 'Técnico Administrativo':
            return '#ea580c';
        case 'Gabinete Técnico':
            return '#0d9488';
        default:
            return '#64748b';
    }
}

function labelEstadoMarcacao($estado) {
    switch ($estado) {
        case 'confirmada':
            return 'Confirmada';
        case 'cancelada':
            return 'Cancelada';
        case 'concluida':
            return 'Concluída';
        default:
            return 'Pendente';
    }
}
?>

<style>
.agenda-exec-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:32px;padding:32px;margin-bottom:24px;box-shadow:0 24px 70px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;position:relative;overflow:hidden}
.agenda-exec-hero::after{content:"";position:absolute;width:420px;height:420px;right:-170px;top:-200px;border-radius:50%;background:rgba(255,255,255,.07)}
.agenda-exec-hero>*{position:relative;z-index:2}
.agenda-exec-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-weight:900;letter-spacing:.7px;text-transform:uppercase;font-size:12px}
.agenda-exec-hero h2{margin:14px 0 8px;font-size:40px;line-height:1.05}
.agenda-exec-hero p{margin:0;color:#dbeafe;line-height:1.7}
.agenda-live-pill{display:inline-flex;align-items:center;gap:9px;background:#dcfce7;color:#166534;padding:11px 15px;border-radius:999px;font-weight:900;text-transform:uppercase;box-shadow:0 16px 36px rgba(0,0,0,.12)}
.agenda-live-pill i{width:10px;height:10px;border-radius:50%;background:#22c55e;animation:agendaPulse 1.4s infinite}
@keyframes agendaPulse{0%{box-shadow:0 0 0 0 rgba(34,197,94,.7)}70%{box-shadow:0 0 0 10px rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}
.agenda-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px}
.agenda-kpi{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 16px 40px rgba(15,23,42,.07)}
.agenda-kpi span{display:block;font-size:25px;margin-bottom:8px}.agenda-kpi strong{display:block;font-size:32px;color:#11151B;line-height:1}.agenda-kpi small{display:block;margin-top:7px;color:#64748b;font-weight:800}
.agenda-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px}.agenda-filters .btn{text-decoration:none}
.agenda-main-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(360px,.85fr);gap:24px;align-items:start}
.agenda-card{background:white;border:1px solid #e5e7eb;border-radius:28px;padding:24px;box-shadow:0 18px 50px rgba(15,23,42,.08)}
.agenda-card h3{margin:0 0 16px;color:#11151B;font-size:24px}
.agenda-calendar{display:grid;grid-template-columns:repeat(7,1fr);border:1px solid #e5e7eb;border-radius:22px;overflow:hidden;background:#e5e7eb;gap:1px}
.agenda-day{min-height:220px;background:#fff;padding:13px}.agenda-day.today{background:linear-gradient(180deg,#fff,#f8fbff);box-shadow:inset 0 0 0 3px rgba(36,42,50,.12)}
.agenda-day-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.agenda-day-head strong{color:#11151B;font-size:14px}.agenda-day-head small{background:#eef2ff;color:#242A30;width:30px;height:30px;display:grid;place-items:center;border-radius:50%;font-weight:900}
.agenda-event{display:block;text-decoration:none;color:white;border-radius:14px;padding:10px;margin-bottom:8px;box-shadow:0 10px 24px rgba(15,23,42,.12);transition:.2s ease}.agenda-event:hover{transform:translateY(-2px);filter:brightness(1.04)}.agenda-event strong{display:block;font-size:13px;line-height:1.2;margin-bottom:5px}.agenda-event span{display:block;font-size:12px;opacity:.92}.agenda-event small{display:inline-flex;margin-top:7px;padding:4px 8px;background:rgba(255,255,255,.18);border-radius:999px;font-weight:900;font-size:10px;text-transform:uppercase}
.agenda-side-list{display:grid;gap:12px}.agenda-side-item{display:grid;grid-template-columns:44px 1fr auto;gap:12px;align-items:center;background:#f8fafc;border:1px solid #eef2f7;border-radius:18px;padding:13px;text-decoration:none;transition:.2s ease}.agenda-side-item:hover{background:white;transform:translateX(4px);box-shadow:0 12px 28px rgba(15,23,42,.07)}
.agenda-side-icon{width:44px;height:44px;border-radius:16px;color:white;display:grid;place-items:center;font-weight:900}.agenda-side-item strong{display:block;color:#11151B;font-size:14px}.agenda-side-item span{color:#64748b;font-size:12px;font-weight:800}.agenda-side-item small{color:#64748b;font-weight:900;text-align:right}
.agenda-responsaveis{display:grid;gap:9px;margin-top:18px}.agenda-resp-row{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;border:1px solid #eef2f7;border-radius:16px;padding:11px 13px}.agenda-resp-row span{display:flex;align-items:center;gap:8px;color:#11151B;font-weight:900}.agenda-dot{width:12px;height:12px;border-radius:50%}
.agenda-table-wrap{margin-top:24px}
@media(max-width:1180px){.agenda-main-grid,.agenda-exec-hero{grid-template-columns:1fr}.agenda-kpi-grid{grid-template-columns:repeat(2,1fr)}.agenda-calendar{grid-template-columns:1fr}.agenda-day{min-height:auto}}
@media(max-width:700px){.agenda-kpi-grid{grid-template-columns:1fr}}
</style>

<div class="agenda-exec-hero">
    <div>
        <span class="agenda-exec-kicker">Agenda Executiva Digital</span>
        <h2>Central de Agendamentos</h2>
        <p>Calendário interno da Junta com marcações por responsável, estado e disponibilidade semanal.</p>
    </div>
    <div class="agenda-live-pill"><i></i>Agenda ativa</div>
</div>

<div class="agenda-kpi-grid">
    <div class="agenda-kpi"><span><i class="bi bi-calendar-event"></i></span><strong><?= (int)$kpis['total'] ?></strong><small>Total</small></div>
    <div class="agenda-kpi"><span>⏳</span><strong><?= (int)$kpis['pendente'] ?></strong><small>Pendentes</small></div>
    <div class="agenda-kpi"><span><i class="bi bi-check-circle-fill"></i></span><strong><?= (int)$kpis['confirmada'] ?></strong><small>Confirmadas</small></div>
    <div class="agenda-kpi"><span><i class="bi bi-flag"></i></span><strong><?= (int)$kpis['concluida'] ?></strong><small>Concluídas</small></div>
    <div class="agenda-kpi"><span><i class="bi bi-fire"></i></span><strong><?= (int)$kpis['hoje'] ?></strong><small>Hoje</small></div>
</div>

<div class="agenda-filters">
    <a class="btn secondary" href="marcacoes.php">Todas</a>
    <a class="btn secondary" href="marcacoes.php?estado=pendente">Pendentes</a>
    <a class="btn secondary" href="marcacoes.php?estado=confirmada">Confirmadas</a>
    <a class="btn secondary" href="marcacoes.php?estado=cancelada">Canceladas</a>
    <a class="btn secondary" href="marcacoes.php?estado=concluida">Concluídas</a>
    <?php foreach ($responsaveis as $resp): ?>
        <a class="btn secondary" href="marcacoes.php?responsavel=<?= urlencode($resp) ?>"><?= htmlspecialchars($resp) ?></a>
    <?php endforeach; ?>
</div>

<div class="agenda-main-grid">
    <div class="agenda-card">
        <h3>Calendário da semana</h3>
        <div class="agenda-calendar">
            <?php foreach ($diasSemana as $dia): ?>
                <?php
                $marcacoesDia = array_filter($todasMarcacoes, function($m) use ($dia) {
                    return $m['data_marcacao'] == $dia;
                });
                ?>
                <div class="agenda-day <?= $dia == $hoje ? 'today' : '' ?>">
                    <div class="agenda-day-head">
                        <strong><?= strtoupper(date('D', strtotime($dia))) ?></strong>
                        <small><?= date('d', strtotime($dia)) ?></small>
                    </div>
                    <?php foreach ($marcacoesDia as $m): ?>
                        <?php $cor = corResponsavelMarcacao($m['responsavel'] ?? ''); ?>
                        <a class="agenda-event" href="ver_marcacao.php?id=<?= (int)$m['id'] ?>" style="background:<?= $cor ?>">
                            <strong><?= substr($m['hora_marcacao'], 0, 5) ?> · <?= htmlspecialchars($m['nome']) ?></strong>
                            <span><?= htmlspecialchars($m['assunto']) ?></span>
                            <span><?= htmlspecialchars($m['responsavel'] ?: 'A definir') ?></span>
                            <?php if (($m['tipo_atendimento'] ?? 'presencial') === 'virtual'): ?>
                                <small style="background:rgba(37,99,235,.95);color:white;"><i class="bi bi-laptop"></i> Virtual</small>
                            <?php else: ?>
                                <small style="background:rgba(255,255,255,.20);color:white;"><i class="bi bi-building"></i> Presencial</small>
                            <?php endif; ?>
                            <small><?= htmlspecialchars(labelEstadoMarcacao($m['estado'])) ?></small>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($marcacoesDia)): ?>
                        <span style="color:#94a3b8;font-size:13px;font-weight:800;">Sem marcações</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="agenda-card">
        <h3>Próximas marcações</h3>
        <div class="agenda-side-list">
            <?php foreach (array_slice($marcacoes, 0, 8) as $m): ?>
                <?php $corResp = corResponsavelMarcacao($m['responsavel'] ?? ''); ?>
                <a class="agenda-side-item" href="ver_marcacao.php?id=<?= (int)$m['id'] ?>">
                    <div class="agenda-side-icon" style="background:<?= $corResp ?>"><i class="bi bi-calendar-event"></i></div>
                    <div>
                        <strong><?= htmlspecialchars($m['nome']) ?></strong>
                        <span><?= htmlspecialchars($m['assunto']) ?> · <?= htmlspecialchars($m['responsavel'] ?: 'A definir') ?> · <?= (($m['tipo_atendimento'] ?? 'presencial') === 'virtual') ? '<i class="bi bi-laptop"></i> Virtual' : '<i class="bi bi-building"></i> Presencial' ?></span>
                    </div>
                    <small><?= date('d/m', strtotime($m['data_marcacao'])) ?><br><?= substr($m['hora_marcacao'], 0, 5) ?></small>
                </a>
            <?php endforeach; ?>
            <?php if (empty($marcacoes)): ?>
                <p>Ainda não existem marcações.</p>
            <?php endif; ?>
        </div>
        <div class="agenda-responsaveis">
            <?php foreach ($responsaveis as $resp): ?>
                <div class="agenda-resp-row">
                    <span><i class="agenda-dot" style="background:<?= corResponsavelMarcacao($resp) ?>"></i><?= htmlspecialchars($resp) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="agenda-card agenda-table-wrap">
    <h3>Lista completa</h3>
    <div class="table-box">
        <table>
            <tr>
                <th>Data</th>
                <th>Hora</th>
                <th>Nome</th>
                <th>Assunto</th>
                <th>Responsável</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($marcacoes as $m): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($m['data_marcacao'])) ?></td>
                    <td><?= substr($m['hora_marcacao'], 0, 5) ?></td>
                    <td><?= htmlspecialchars($m['nome']) ?></td>
                    <td><?= htmlspecialchars($m['assunto']) ?></td>
                    <td><?= htmlspecialchars($m['responsavel'] ?: 'A definir') ?></td>
                    <td>
                        <?php if (($m['tipo_atendimento'] ?? 'presencial') === 'virtual'): ?>
                            <span class="estado-badge" style="background:#2563eb;color:#fff;"><i class="bi bi-laptop"></i> Virtual</span>
                        <?php else: ?>
                            <span class="estado-badge" style="background:#475569;color:#fff;"><i class="bi bi-building"></i> Presencial</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="estado-badge estado-<?= htmlspecialchars($m['estado']) ?>">
                            <?= htmlspecialchars(labelEstadoMarcacao($m['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <a class="btn secondary" href="ver_marcacao.php?id=<?= $m['id'] ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($marcacoes)): ?>
                <tr><td colspan="8">Ainda não existem marcações.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
