<?php require_once "includes/header.php"; ?>

<?php
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
if ($mes < 1 || $mes > 12) $mes = (int)date('m');
if ($ano < 2000 || $ano > 2100) $ano = (int)date('Y');

$primeiroDia = "$ano-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-01";
$diasNoMes = (int)date('t', strtotime($primeiroDia));
$inicioSemana = (int)date('N', strtotime($primeiroDia));

$mesAnterior = $mes - 1; $anoAnterior = $ano;
if ($mesAnterior < 1) { $mesAnterior = 12; $anoAnterior--; }
$mesSeguinte = $mes + 1; $anoSeguinte = $ano;
if ($mesSeguinte > 12) { $mesSeguinte = 1; $anoSeguinte++; }

$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$stmt = $pdo->prepare("
    SELECT *
    FROM eventos
    WHERE MONTH(data_evento) = ?
    AND YEAR(data_evento) = ?
    ORDER BY data_evento ASC
");
$stmt->execute([$mes, $ano]);
$eventosMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventosPorDia = [];
foreach ($eventosMes as $evento) {
    $dia = (int)date('d', strtotime($evento['data_evento']));
    $eventosPorDia[$dia][] = $evento;
}

// Lista de eventos: do PRIMEIRO DIA do mês selecionado em diante (não apenas o mês).
// O calendário continua a mostrar só o mês ($eventosMes); é esta lista que alimenta
// a grelha "Próximos Eventos", que antes ficava vazia sempre que o mês não tinha
// eventos, mesmo havendo-os nos meses seguintes.
$stmtDesde = $pdo->prepare("
    SELECT *
    FROM eventos
    WHERE data_evento >= ?
    ORDER BY data_evento ASC
");
$stmtDesde->execute([$primeiroDia . " 00:00:00"]);
$eventosDesdeMes = $stmtDesde->fetchAll(PDO::FETCH_ASSOC);

$proximoEvento = $pdo->query("
    SELECT *
    FROM eventos
    WHERE data_evento >= NOW()
    ORDER BY data_evento ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$heroImagem = !empty($proximoEvento['imagem'])
    ? "/assets/img/" . $proximoEvento['imagem']
    : "/assets/img/freguesia-1.jpg";
?>

<style>
.eventos-page-insane{background:radial-gradient(circle at top left,rgba(36,42,50,.08),transparent 34%),linear-gradient(180deg,#f8fafc 0%,#fff 54%,#f7f4ef 100%);padding-bottom:70px}
.eventos-hero-insane{position:relative;min-height:420px;overflow:hidden;display:flex;align-items:center;color:white;background:linear-gradient(90deg,rgba(17,21,28,.93),rgba(17,21,28,.58)),linear-gradient(0deg,rgba(0,0,0,.38),transparent 58%),url('<?= htmlspecialchars($heroImagem) ?>') center/cover no-repeat}
.eventos-hero-insane:before{content:"";position:absolute;width:620px;height:620px;border-radius:50%;right:-220px;top:-260px;background:rgba(255,255,255,.06)}
.eventos-hero-insane:after{content:"";position:absolute;inset:auto 0 0 0;height:120px;background:linear-gradient(0deg,#f8fafc,transparent)}
.eventos-hero-inner{position:relative;z-index:2;padding:80px 0 125px}
.eventos-kicker{display:inline-flex;align-items:center;gap:9px;background:rgba(212,170,0,.15);border:1px solid rgba(212,170,0,.42);color:#F0D060;border-radius:999px;padding:10px 16px;font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.9px;margin-bottom:18px}
.eventos-hero-insane h1{font-size:clamp(46px,7vw,84px);line-height:.98;margin:0 0 20px;letter-spacing:-2px}
.eventos-hero-insane p{color:#dbeafe;font-size:21px;line-height:1.8;max-width:780px;margin:0}
.eventos-main{position:relative;z-index:5;margin-top:-78px}
.agenda-top-grid-insane{display:grid;grid-template-columns:.92fr 1.08fr;gap:28px;align-items:stretch}
.evento-feature-insane{background:linear-gradient(135deg,#242A30,#11151B);border-radius:34px;padding:34px;color:white;box-shadow:0 24px 70px rgba(0,0,0,.18);overflow:hidden;position:relative;min-height:560px;display:flex;flex-direction:column}
.evento-feature-insane:before{content:"";position:absolute;width:360px;height:360px;border-radius:50%;right:-140px;top:-160px;background:rgba(255,255,255,.06)}
.evento-feature-insane>*{position:relative;z-index:2}
.evento-feature-badge{width:max-content;display:inline-flex;align-items:center;background:#D4AA00;color:#11151B;border-radius:999px;padding:10px 16px;font-weight:900;font-size:13px;text-transform:uppercase;margin-bottom:22px}
.evento-feature-insane h2{font-size:clamp(34px,4vw,52px);line-height:1.08;letter-spacing:-1.4px;margin:0 0 18px}
.evento-feature-insane p{color:#dbeafe;line-height:1.8;font-size:16px;margin:0 0 18px}
.evento-feature-date{display:inline-flex;gap:9px;align-items:center;color:white;font-weight:900;margin:6px 0 18px}
.evento-countdown{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);border-radius:18px;padding:16px 18px;color:#D4AA00;font-weight:900;margin-bottom:18px}
.evento-feature-image{width:100%;height:250px;border-radius:24px;overflow:hidden;background:rgba(255,255,255,.10);margin-top:auto;box-shadow:0 20px 50px rgba(0,0,0,.22)}
.evento-feature-image img{width:100%;height:100%;object-fit:cover;display:block;transition:.45s}
.evento-feature-image:hover img{transform:scale(1.05)}
.evento-feature-noimg{width:100%;height:100%;display:grid;place-items:center;color:#D4AA00;font-size:64px;font-weight:900}
.evento-feature-btn{margin-top:18px;display:flex;justify-content:center;text-decoration:none;color:white;border:1px solid rgba(255,255,255,.30);background:rgba(255,255,255,.08);border-radius:16px;padding:14px;font-weight:900;transition:.25s}
.evento-feature-btn:hover{transform:translateY(-3px);background:rgba(255,255,255,.14)}
.agenda-month-insane{background:white;border-radius:34px;padding:32px;box-shadow:0 24px 70px rgba(0,0,0,.10);border:1px solid #eef2f7}
.agenda-month-head-insane{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:26px}
.agenda-month-head-insane a{width:48px;height:48px;border-radius:16px;display:grid;place-items:center;background:#242A30;color:white;text-decoration:none;font-size:26px;font-weight:900;box-shadow:0 14px 32px rgba(36,42,50,.20);transition:.25s}
.agenda-month-head-insane a:hover{transform:translateY(-3px)}
.agenda-month-title{text-align:center}.agenda-month-title strong{display:block;color:#11151B;font-size:26px;margin-bottom:4px}.agenda-month-title small{color:#64748b;font-weight:800}
.agenda-weekdays-insane{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;margin-bottom:12px}.agenda-weekdays-insane span{color:#52606d;font-weight:900;text-align:center;font-size:13px}
.agenda-calendar-insane{display:grid;grid-template-columns:repeat(7,1fr);gap:10px}
.agenda-day-insane{min-height:78px;border-radius:18px;background:#f8fafc;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;position:relative;font-weight:900;color:#11151B;transition:.25s}
.agenda-day-insane.empty{opacity:0;pointer-events:none}.agenda-day-insane.today{border-color:#D4AA00}.agenda-day-insane.has-event{background:#242A30;color:white;border-color:#242A30;box-shadow:0 16px 36px rgba(36,42,50,.18)}
.event-dot-insane{position:absolute;bottom:12px;width:8px;height:8px;background:#D4AA00;border-radius:50%}
.agenda-day-insane:hover{transform:translateY(-4px);z-index:30}
.agenda-tooltip-insane{position:absolute;left:50%;bottom:calc(100% + 14px);transform:translateX(-50%) translateY(8px);width:300px;background:white;color:#11151B;border-radius:22px;box-shadow:0 26px 70px rgba(0,0,0,.22);border:1px solid #eef2f7;padding:16px;opacity:0;pointer-events:none;transition:.25s;z-index:99;text-align:left}
.agenda-tooltip-insane:after{content:"";position:absolute;left:50%;top:100%;transform:translateX(-50%);border:10px solid transparent;border-top-color:white}
/* "Ponte" invisível sobre o vão entre o dia e o pop-up: sem ela, ao mover o rato
   do dia para o pop-up perde-se o :hover e ele desaparece antes de se poder clicar. */
.agenda-tooltip-insane:before{content:"";position:absolute;left:0;right:0;top:100%;height:16px}
/* Visível e CLICÁVEL enquanto o rato estiver no dia OU no próprio pop-up.
   (pointer-events:auto é essencial: com "none" os cliques atravessavam-no.) */
.agenda-day-insane:hover .agenda-tooltip-insane,
.agenda-tooltip-insane:hover{opacity:1;transform:translateX(-50%) translateY(0);pointer-events:auto}
.agenda-tooltip-insane b{display:block;margin-bottom:12px;color:#11151B}
.tooltip-event-card{display:block;text-decoration:none;color:inherit;cursor:pointer;border-top:1px solid #eef2f7;padding:12px 8px 10px;margin-top:12px;border-radius:14px;transition:background .18s}
.tooltip-event-card:hover{background:#f8fafc}
.tooltip-event-cta{display:inline-flex;align-items:center;gap:6px;margin-top:10px;font-weight:900;font-size:13px;color:var(--cor-principal,#242A30)}
.tooltip-event-card:hover .tooltip-event-cta{gap:10px}.tooltip-event-card:first-of-type{border-top:0;padding-top:0;margin-top:0}
.tooltip-event-img{width:100%;height:105px;object-fit:cover;border-radius:16px;display:block;margin-bottom:10px}.tooltip-event-card strong{display:block;color:#242A30;margin-bottom:6px}.tooltip-event-card small{color:#64748b;font-weight:800}.tooltip-event-card p{margin:8px 0 0;color:#52606d;font-size:13px;line-height:1.5;font-weight:600}
.eventos-list-section{margin-top:42px}.eventos-section-head{display:flex;justify-content:space-between;gap:20px;align-items:end;margin-bottom:24px}.eventos-section-head h2{color:#11151B;font-size:clamp(32px,4vw,48px);letter-spacing:-1px;margin:8px 0 0}
.eventos-all-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;color:#242A30;border:1px solid #dbeafe;background:white;border-radius:16px;padding:12px 18px;font-weight:900;transition:.25s}.eventos-all-btn:hover{transform:translateY(-3px);background:#eff6ff}
.eventos-grid-insane{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:24px}
.evento-card-insane{background:white;border:1px solid #eef2f7;border-radius:28px;overflow:hidden;box-shadow:0 18px 50px rgba(0,0,0,.08);transition:.3s;position:relative;display:block;text-decoration:none;color:inherit}.evento-card-insane:hover{transform:translateY(-8px);box-shadow:0 30px 75px rgba(0,0,0,.14)}
.evento-card-img{height:210px;position:relative;overflow:hidden;background:linear-gradient(135deg,#242A30,#11151B)}.evento-card-img img{width:100%;height:100%;object-fit:cover;display:block;transition:.45s}.evento-card-insane:hover .evento-card-img img{transform:scale(1.08)}
.evento-card-date{position:absolute;left:16px;top:16px;width:64px;min-height:70px;border-radius:16px;background:white;color:#11151B;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:900;box-shadow:0 14px 34px rgba(0,0,0,.18)}.evento-card-date strong{font-size:24px;line-height:1}.evento-card-date span{font-size:12px;color:#242A30}
.evento-card-body{padding:22px}.evento-card-body h3{color:#11151B;font-size:21px;line-height:1.25;margin:0 0 12px}.evento-card-body small{display:block;color:#64748b;font-weight:800;margin-bottom:12px}.evento-card-body p{color:#52606d;line-height:1.7;margin:0 0 16px;font-size:14px}
.evento-badge{display:inline-flex;background:#dcfce7;color:#166534;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:900}.eventos-empty{background:white;border-radius:28px;padding:38px;text-align:center;color:#64748b;font-weight:800;box-shadow:0 18px 45px rgba(0,0,0,.08)}
@media(max-width:1200px){.agenda-top-grid-insane{grid-template-columns:1fr}.eventos-grid-insane{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.eventos-hero-insane{min-height:360px}.eventos-main{margin-top:-52px}.evento-feature-insane,.agenda-month-insane{padding:24px;border-radius:26px}.agenda-calendar-insane,.agenda-weekdays-insane{gap:6px}.agenda-day-insane{min-height:54px;border-radius:14px}.agenda-tooltip-insane{width:245px}.eventos-grid-insane{grid-template-columns:1fr}.eventos-section-head{flex-direction:column;align-items:flex-start}}
</style>

<main class="eventos-page-insane">

    <section class="eventos-hero-insane">
        <div class="container">
            <div class="eventos-hero-inner">
                <span class="eventos-kicker">Agenda Municipal</span>
                <h1>Eventos</h1>
                <p>Consulte a programação, atividades, encontros e eventos da freguesia.</p>
            </div>
        </div>
    </section>

    <section class="container eventos-main">

        <div class="agenda-top-grid-insane">

            <article class="evento-feature-insane">
                <span class="evento-feature-badge">Próximo evento</span>

                <?php if ($proximoEvento): ?>
                    <h2><?= htmlspecialchars($proximoEvento['titulo']) ?></h2>
                    <p><?= htmlspecialchars(mb_substr($proximoEvento['descricao'] ?? '', 0, 180)) ?><?= mb_strlen($proximoEvento['descricao'] ?? '') > 180 ? '...' : '' ?></p>

                    <div class="evento-feature-date"><i class="bi bi-calendar-event-fill"></i> <?= date('d/m/Y H:i', strtotime($proximoEvento['data_evento'])) ?><?php if (!empty($proximoEvento['data_fim'])): ?> &ndash; <?= date('d/m/Y H:i', strtotime($proximoEvento['data_fim'])) ?><?php endif; ?></div>

                    <div class="evento-countdown" data-evento="<?= htmlspecialchars($proximoEvento['data_evento']) ?>">A calcular...</div>

                    <div class="evento-feature-image">
                        <?php if (!empty($proximoEvento['imagem'])): ?>
                            <img src="/assets/img/<?= htmlspecialchars($proximoEvento['imagem']) ?>" alt="<?= htmlspecialchars($proximoEvento['titulo']) ?>" style="object-position:<?= (int)($proximoEvento['imagem_foco_x'] ?? 50) ?>% <?= (int)($proximoEvento['imagem_foco_y'] ?? 50) ?>%">
                        <?php else: ?>
                            <div class="evento-feature-noimg"><i class="bi bi-calendar-event-fill"></i></div>
                        <?php endif; ?>
                    </div>

                    <a href="/evento.php?id=<?= (int)$proximoEvento['id'] ?>" class="evento-feature-btn">Ver detalhes do evento →</a>
                <?php else: ?>
                    <h2>Sem eventos agendados</h2>
                    <p>Quando existir um novo evento, ele aparecerá automaticamente aqui.</p>
                <?php endif; ?>
            </article>

            <article class="agenda-month-insane" id="agenda">
                <div class="agenda-month-head-insane">
                    <a href="?mes=<?= $mesAnterior ?>&ano=<?= $anoAnterior ?>#agenda">‹</a>
                    <div class="agenda-month-title">
                        <strong><?= $nomesMeses[$mes] ?> <?= $ano ?></strong>
                        <small><?= count($eventosMes) ?> evento(s) neste mês</small>
                    </div>
                    <a href="?mes=<?= $mesSeguinte ?>&ano=<?= $anoSeguinte ?>#agenda">›</a>
                </div>

                <div class="agenda-weekdays-insane">
                    <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span><span>Dom</span>
                </div>

                <div class="agenda-calendar-insane">
                    <?php for ($i = 1; $i < $inicioSemana; $i++): ?>
                        <div class="agenda-day-insane empty"></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $diasNoMes; $dia++): ?>
                        <?php
                        $temEventos = !empty($eventosPorDia[$dia]);
                        $isHoje = date('Y-m-d') === date('Y-m-d', strtotime("$ano-$mes-$dia"));
                        ?>

                        <div class="agenda-day-insane <?= $temEventos ? 'has-event' : '' ?> <?= $isHoje ? 'today' : '' ?>">
                            <strong><?= $dia ?></strong>

                            <?php if ($temEventos): ?>
                                <span class="event-dot-insane"></span>
                                <div class="agenda-tooltip-insane">
                                    <b>Evento(s) neste dia</b>

                                    <?php foreach ($eventosPorDia[$dia] as $ev): ?>
                                        <a class="tooltip-event-card" href="/evento.php?id=<?= (int)$ev['id'] ?>">
                                            <?php if (!empty($ev['imagem'])): ?>
                                                <img class="tooltip-event-img" src="/assets/img/<?= htmlspecialchars($ev['imagem']) ?>" alt="<?= htmlspecialchars($ev['titulo'] ?? 'Evento') ?>" style="object-position:<?= (int)($ev['imagem_foco_x'] ?? 50) ?>% <?= (int)($ev['imagem_foco_y'] ?? 50) ?>%">
                                            <?php endif; ?>

                                            <strong><?= htmlspecialchars($ev['titulo'] ?? 'Evento sem título') ?></strong>
                                            <small><?= date('d/m/Y H:i', strtotime($ev['data_evento'])) ?></small>

                                            <?php if (!empty($ev['descricao'])): ?>
                                                <p><?= htmlspecialchars(mb_substr(strip_tags($ev['descricao']), 0, 90)) ?>...</p>
                                            <?php endif; ?>
                                            <span class="tooltip-event-cta">Ver evento <i class="bi bi-arrow-right"></i></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </article>
        </div>

        <section class="eventos-list-section" id="eventos-lista">
            <div class="eventos-section-head">
                <div>
                    <span class="eventos-kicker">Programação</span>
                    <h2>Próximos Eventos</h2>
                </div>
                <a href="/eventos.php" class="eventos-all-btn">Ver todos os eventos →</a>
            </div>

            <?php if (!empty($eventosDesdeMes)): ?>
                <div class="eventos-grid-insane">
                    <?php foreach ($eventosDesdeMes as $e): ?>
                        <a class="evento-card-insane" href="/evento.php?id=<?= (int)$e['id'] ?>">
                            <div class="evento-card-img">
                                <?php if (!empty($e['imagem'])): ?>
                                    <img src="/assets/img/<?= htmlspecialchars($e['imagem']) ?>" alt="<?= htmlspecialchars($e['titulo']) ?>" style="object-position:<?= (int)($e['imagem_foco_x'] ?? 50) ?>% <?= (int)($e['imagem_foco_y'] ?? 50) ?>%">
                                <?php else: ?>
                                    <div class="evento-feature-noimg"><i class="bi bi-calendar-event-fill"></i></div>
                                <?php endif; ?>

                                <div class="evento-card-date">
                                    <strong><?= date('d', strtotime($e['data_evento'])) ?></strong>
                                    <span><?= strtoupper(mb_substr($nomesMeses[(int)date('m', strtotime($e['data_evento']))], 0, 3)) ?></span>
                                </div>
                            </div>

                            <div class="evento-card-body">
                                <h3><?= htmlspecialchars($e['titulo']) ?></h3>
                                <small><i class="bi bi-calendar-event-fill"></i> <?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></small>
                                <p><?= htmlspecialchars(mb_substr(strip_tags($e['descricao'] ?? ''), 0, 130)) ?><?= mb_strlen(strip_tags($e['descricao'] ?? '')) > 130 ? '...' : '' ?></p>
                                <span class="evento-badge"><?= !empty($e['categoria']) ? htmlspecialchars($e['categoria']) : 'Evento' ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="eventos-empty">Não existem eventos registados para este mês.</div>
            <?php endif; ?>
        </section>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const countdown = document.querySelector(".evento-countdown");
    if (!countdown) return;
    const dataEvento = new Date(countdown.dataset.evento).getTime();

    function atualizarCountdown() {
        const agora = new Date().getTime();
        const distancia = dataEvento - agora;

        if (distancia <= 0) {
            countdown.innerHTML = "O evento já começou";
            return;
        }

        const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((distancia / (1000 * 60 * 60)) % 24);
        const minutos = Math.floor((distancia / (1000 * 60)) % 60);

        countdown.innerHTML = "Faltam " + dias + " dias, " + horas + "h " + minutos + "min";
    }

    atualizarCountdown();
    setInterval(atualizarCountdown, 60000);
});
</script>

<?php require_once "includes/footer.php"; ?>
