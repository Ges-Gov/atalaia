<?php
/**
 * «Hoje na freguesia» (15/10/2026, ponto 4 — «mais dinâmico») — CORE.
 *
 * Uma faixa na página inicial que muda todos os dias, só com dados reais do site:
 *   - a data e o estado do tempo (Open-Meteo, da cópia em cache — nunca atrasa a página; se estiver
 *     desatualizada, o browser pede uma nova em segundo plano);
 *   - o atendimento da Junta: «Aberto agora · fecha às 12h00» / «Fechado · abre amanhã às 08h30»,
 *     calculado a partir do horário publicado em Configurações (e recalculado no browser a cada minuto);
 *   - o evento de hoje ou o próximo;
 *   - a farmácia de serviço (só com turno em curso);
 *   - pedidos resolvidos nos últimos 30 dias (só números — nada de pessoal; só aparece se houver).
 * Cada peça só aparece se tiver dados — nunca se inventa nada.
 *
 * Redesign 10/2026 (vindo do modelo novo): a farmácia de serviço e os pedidos resolvidos estão
 * desligados por omissão — ligam-se com hoje_farmacia = 1 / hoje_resolvidos = 1 em tema_config, quando
 * a Junta decidir (a farmácia precisa ainda da tabela farmacias_servico, que não veio). O tempo não tem
 * página própria neste modelo, por isso não é um link; a farmácia leva aos contactos.
 */
require_once __DIR__ . '/tema.php';
require_once __DIR__ . '/calendario.php';
if (is_file(__DIR__ . '/utilidades.php')) require_once __DIR__ . '/utilidades.php';

if (!function_exists('farmaciaDeServico')) {
    /** Turno em curso (tabela farmacias_servico, do modelo novo); null sem tabela ou sem turno. */
    function farmaciaDeServico(PDO $pdo): ?array
    {
        try {
            $st = $pdo->query("SELECT * FROM farmacias_servico WHERE inicio <= NOW() AND fim > NOW() ORDER BY inicio DESC LIMIT 1");
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('telefonesDe')) {
    /** "268 550 160 / 268 550 161" → [['268 550 160', '+351268550160'], …] para links tel:. */
    function telefonesDe(?string $texto): array
    {
        $out = [];
        foreach (preg_split('#\s*(?:/|;|,| ou )\s*#u', (string)$texto) as $parte) {
            $digitos = preg_replace('/\D+/', '', $parte);
            if (strlen($digitos) === 9) $out[] = [trim($parte), '+351' . $digitos];
            elseif (strlen($digitos) >= 3 && strlen($digitos) <= 6) $out[] = [trim($parte), $digitos];
            elseif (strlen($digitos) === 12 && strpos($digitos, '351') === 0) $out[] = [trim($parte), '+' . $digitos];
        }
        return $out;
    }
}

/**
 * Lê o horário publicado («Segunda a sexta-feira: 08h30-17h30 (encerra das 12h00 às 13h00 para almoço)»)
 * → ['dias' => [1..5], 'intervalos' => [[510, 720], [780, 1050]]] em minutos desde a meia-noite.
 * Devolve null se o texto não tiver um horário reconhecível (o estado «aberto/fechado» não aparece).
 */
function horarioJunta(?string $texto = null): ?array
{
    $t = mb_strtolower($texto ?? (string)siteConfig('horario', ''));
    $hora = '(\d{1,2})\s*[h:]\s*(\d{2})?';
    $ate = '\s*(?:-|–|—|às|as|a)\s*';
    if (!preg_match_all("/$hora$ate$hora/u", $t, $todos, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) return null;
    $abertos = []; $pausas = [];
    foreach ($todos as $m) {
        $a = (int)$m[1][0] * 60 + (int)($m[2][0] ?? 0);
        $b = (int)$m[3][0] * 60 + (int)($m[4][0] ?? 0);
        if ($b <= $a || $b > 24 * 60) continue;
        // um período logo a seguir a «encerra/fecha/pausa/almoço» é uma pausa, não um período aberto
        $antes = mb_substr(substr($t, 0, $m[0][1]), -28);
        if (preg_match('/(encerra|fecha|pausa|almo[cç]o|intervalo)\D*$/u', $antes)) $pausas[] = [$a, $b]; else $abertos[] = [$a, $b];
    }
    if (!$abertos) return null;
    usort($abertos, fn($x, $y) => $x[0] <=> $y[0]);
    // um só período aberto com uma pausa lá dentro → dois períodos («08h30-17h30, encerra das 12h00 às 13h00»)
    if (count($abertos) === 1 && $pausas) {
        [$ini, $fim] = $abertos[0]; [$pi, $pf] = $pausas[0];
        if ($pi > $ini && $pf < $fim && $pf > $pi) $abertos = [[$ini, $pi], [$pf, $fim]];
    }
    $dias = [1, 2, 3, 4, 5];
    if (preg_match('/segunda\S*\s+a\s+s[áa]bado/u', $t)) $dias = [1, 2, 3, 4, 5, 6];
    elseif (preg_match('/segunda\S*\s+a\s+domingo|todos os dias/u', $t)) $dias = [1, 2, 3, 4, 5, 6, 7];
    return ['dias' => $dias, 'intervalos' => $abertos];
}

function horaTexto(int $min): string { return sprintf('%02dh%02d', intdiv($min, 60), $min % 60); }

/** Estado do atendimento num instante: ['aberto' => bool, 'titulo' => 'Aberto agora', 'detalhe' => 'fecha às 12h00'] */
function estadoAtendimento(array $h, ?DateTimeInterface $agora = null): array
{
    $agora = $agora ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Lisbon'));
    $dia = (int)$agora->format('N');
    $min = (int)$agora->format('G') * 60 + (int)$agora->format('i');
    if (in_array($dia, $h['dias'], true)) {
        foreach ($h['intervalos'] as [$a, $b]) {
            if ($min >= $a && $min < $b) return ['aberto' => true, 'titulo' => 'Aberto agora', 'detalhe' => 'fecha às ' . horaTexto($b)];
        }
        foreach ($h['intervalos'] as [$a, $b]) {
            if ($min < $a) return ['aberto' => false, 'titulo' => 'Fechado', 'detalhe' => 'abre às ' . horaTexto($a)];
        }
    }
    $nomes = [1 => 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
    for ($i = 1; $i <= 7; $i++) {
        $d = ($dia - 1 + $i) % 7 + 1;
        if (in_array($d, $h['dias'], true)) {
            return ['aberto' => false, 'titulo' => 'Fechado', 'detalhe' => 'abre ' . ($i === 1 ? 'amanhã' : $nomes[$d]) . ' às ' . horaTexto($h['intervalos'][0][0])];
        }
    }
    return ['aberto' => false, 'titulo' => 'Fechado', 'detalhe' => ''];
}

/** Data por extenso: «quarta-feira, 15 de outubro» */
function dataPorExtenso(DateTimeInterface $d): string
{
    $dias = [1 => 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
    $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    return $dias[(int)$d->format('N')] . ', ' . (int)$d->format('j') . ' de ' . $meses[(int)$d->format('n')];
}

/** Data curta: «qui., 10 dez.» */
function dataCurta(DateTimeInterface $d): string
{
    $dias = [1 => 'seg.', 'ter.', 'qua.', 'qui.', 'sex.', 'sáb.', 'dom.'];
    $meses = [1 => 'jan.', 'fev.', 'mar.', 'abr.', 'mai.', 'jun.', 'jul.', 'ago.', 'set.', 'out.', 'nov.', 'dez.'];
    return $dias[(int)$d->format('N')] . ', ' . (int)$d->format('j') . ' ' . $meses[(int)$d->format('n')];
}

/** «hoje às 21h00» / «amanhã às 09h00» / «qui., 10 dez. às 09h00» */
function quandoRelativo(DateTimeInterface $quando, DateTimeInterface $agora): string
{
    $hora = horaTexto((int)$quando->format('G') * 60 + (int)$quando->format('i'));
    $dif = (int)$agora->setTime(0, 0)->diff(DateTimeImmutable::createFromInterface($quando)->setTime(0, 0))->format('%r%a');
    return ($dif === 0 ? 'hoje' : ($dif === 1 ? 'amanhã' : dataCurta($quando))) . ' às ' . $hora;
}

/** Estado do tempo SÓ da cache (nunca vai à internet aqui): [dados|null, desatualizado?] */
function hojeTempoCache(): array
{
    $f = __DIR__ . '/../cache/tempo.json';
    if (!is_file($f)) return [null, true];
    $d = json_decode((string)file_get_contents($f), true) ?: null;
    return [$d, filemtime($f) < time() - 1800];
}

/** Dados do bloco (cada chave a null quando não há nada a mostrar) */
function hojeDados(PDO $pdo): array
{
    $agora = new DateTimeImmutable('now', new DateTimeZone('Europe/Lisbon'));
    [$tempo, $tempoVelho] = hojeTempoCache();
    $horario = horarioJunta();

    $evento = null; $eventoHoje = false;
    try {
        $evento = $pdo->query("SELECT id, titulo, data_evento, data_fim FROM eventos
            WHERE DATE(data_evento) <= CURDATE() AND COALESCE(DATE(data_fim), DATE(data_evento)) >= CURDATE()
            ORDER BY data_evento LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
        $eventoHoje = (bool)$evento;
        if (!$evento) $evento = $pdo->query("SELECT id, titulo, data_evento, data_fim FROM eventos WHERE data_evento > NOW() ORDER BY data_evento LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {}

    $resolvidos = null;
    if (temaConfig('hoje_resolvidos', '0') === '1') try {
        $r = $pdo->query("SELECT COUNT(*) n, AVG(TIMESTAMPDIFF(HOUR, criado_em, atualizado_em)) h FROM pedidos_junta
            WHERE estado = 'resolvido' AND atualizado_em >= NOW() - INTERVAL 30 DAY")->fetch(PDO::FETCH_ASSOC);
        if ((int)$r['n'] > 0) $resolvidos = ['n' => (int)$r['n'], 'dias' => $r['h'] !== null ? max(1, (int)round($r['h'] / 24)) : null];
    } catch (Throwable $e) {}

    return [
        'agora' => $agora, 'tempo' => $tempo, 'tempo_velho' => $tempoVelho, 'horario' => $horario,
        'atendimento' => $horario ? estadoAtendimento($horario, $agora) : null,
        'evento' => $evento, 'evento_hoje' => $eventoHoje,
        'farmacia' => temaConfig('hoje_farmacia', '0') === '1' ? farmaciaDeServico($pdo) : null, 'resolvidos' => $resolvidos,
    ];
}

/** Imprime a faixa «Hoje na freguesia» */
function blocoHoje(PDO $pdo): void
{
    $d = hojeDados($pdo);
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $tel = telefonesDe(siteConfig('telefone', ''))[0] ?? null;
    ?>
<section class="hoje" aria-labelledby="hojeTitulo" data-spy="nao">
    <div class="container">
        <div class="hoje__faixa">
            <div class="hoje__cabeca">
                <span class="hoje__rotulo" id="hojeTitulo">Hoje na freguesia</span>
                <strong class="hoje__data"><?= $h(dataPorExtenso($d['agora'])) ?></strong>
            </div>

            <div class="hoje__item hoje__item--tempo" data-hoje-tempo<?= $d['tempo_velho'] ? ' data-atualizar' : '' ?><?= $d['tempo'] ? '' : ' hidden' ?>>
                <span class="hoje__ico" aria-hidden="true"><i class="bi <?= $h($d['tempo']['icone'] ?? 'bi-cloud-sun') ?>" data-t-icone></i></span>
                <span class="hoje__txt">
                    <small>Tempo</small>
                    <strong><span data-t-temp><?= isset($d['tempo']['temperatura']) ? (int)$d['tempo']['temperatura'] . '°' : '' ?></span> <span data-t-texto><?= $h($d['tempo']['texto'] ?? '') ?></span></strong>
                    <?php $hojeT = $d['tempo']['dias'][0] ?? null; ?>
                    <span data-t-extremos><?= $hojeT ? 'máx. ' . (int)$hojeT['max'] . '° · mín. ' . (int)$hojeT['min'] . '°' : '' ?></span>
                </span>
            </div>

            <?php if ($d['atendimento']): $at = $d['atendimento']; ?>
            <a class="hoje__item hoje__item--junta" href="/contactos.php" data-horario='<?= $h(json_encode($d['horario'])) ?>'>
                <span class="hoje__ico" aria-hidden="true"><i class="bi bi-building"></i></span>
                <span class="hoje__txt">
                    <small>Atendimento</small>
                    <strong><span class="hoje__estado<?= $at['aberto'] ? ' hoje__estado--aberto' : '' ?>" data-h-titulo><?= $h($at['titulo']) ?></span></strong>
                    <span data-h-detalhe><?= $h($at['detalhe']) ?><?= $tel ? ' · ' . $h($tel[0]) : '' ?></span>
                </span>
            </a>
            <?php endif; ?>

            <?php if ($d['evento']): $ev = $d['evento']; $diaInteiro = eventoDiaInteiro($ev); ?>
            <a class="hoje__item hoje__item--evento" href="/evento.php?id=<?= (int)$ev['id'] ?>">
                <span class="hoje__ico" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                <span class="hoje__txt">
                    <small><?= $d['evento_hoje'] ? 'Hoje na agenda' : 'Próximo evento' ?></small>
                    <strong class="hoje__titulo"><?= $h($ev['titulo']) ?></strong>
                    <span><?php
                        $de = new DateTimeImmutable($ev['data_evento']);
                        if ($d['evento_hoje']) echo $diaInteiro ? 'todo o dia' : 'às ' . $h(horaTexto((int)$de->format('G') * 60 + (int)$de->format('i')));
                        else echo $h(dataCurta($de)) . ($diaInteiro ? '' : ' · ' . $h(horaTexto((int)$de->format('G') * 60 + (int)$de->format('i'))));
                    ?></span>
                </span>
            </a>
            <?php endif; ?>

            <?php if ($d['farmacia']): $f = $d['farmacia']; ?>
            <a class="hoje__item hoje__item--farmacia" href="/contactos.php">
                <span class="hoje__ico" aria-hidden="true"><i class="bi bi-capsule"></i></span>
                <span class="hoje__txt">
                    <small>Farmácia de serviço</small>
                    <strong class="hoje__titulo"><?= $h($f['nome']) ?></strong>
                    <span>até <?= $h(quandoRelativo(new DateTimeImmutable($f['fim'], new DateTimeZone('Europe/Lisbon')), $d['agora'])) ?></span>
                </span>
            </a>
            <?php endif; ?>

            <?php if ($d['resolvidos']): $r = $d['resolvidos']; ?>
            <a class="hoje__item hoje__item--pedidos" href="/transparencia.php">
                <span class="hoje__ico" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                <span class="hoje__txt">
                    <small>Junta Virtual</small>
                    <strong><?= $r['n'] ?> pedido<?= $r['n'] === 1 ? '' : 's' ?> resolvido<?= $r['n'] === 1 ? '' : 's' ?></strong>
                    <span>nos últimos 30 dias<?= $r['dias'] ? ' · em média ' . $r['dias'] . ' dia' . ($r['dias'] === 1 ? '' : 's') : '' ?></span>
                </span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
/* «Hoje na freguesia»: o atendimento recalcula-se a cada minuto (a página pode ficar aberta) e o tempo,
   se a cópia em cache estiver velha, é pedido em segundo plano — a página nunca espera pela internet. */
(function () {
    var j = document.querySelector('[data-horario]');
    if (j) {
        var H = JSON.parse(j.getAttribute('data-horario')), nomes = ['', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
        var hh = function (m) { return String(Math.floor(m / 60)).padStart(2, '0') + 'h' + String(m % 60).padStart(2, '0'); };
        var tel = (j.querySelector('[data-h-detalhe]').textContent.split(' · ')[1] || '');
        var atualizar = function () {
            var p = {}; new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Lisbon', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date()).forEach(function (x) { p[x.type] = x.value; });
            var dia = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6, Sun: 7 }[p.weekday], min = +p.hour * 60 + +p.minute, r = null;
            if (H.dias.indexOf(dia) >= 0) {
                H.intervalos.forEach(function (i) { if (!r && min >= i[0] && min < i[1]) r = [true, 'Aberto agora', 'fecha às ' + hh(i[1])]; });
                H.intervalos.forEach(function (i) { if (!r && min < i[0]) r = [false, 'Fechado', 'abre às ' + hh(i[0])]; });
            }
            for (var k = 1; !r && k <= 7; k++) { var d = (dia - 1 + k) % 7 + 1; if (H.dias.indexOf(d) >= 0) r = [false, 'Fechado', 'abre ' + (k === 1 ? 'amanhã' : nomes[d]) + ' às ' + hh(H.intervalos[0][0])]; }
            if (!r) return;
            var t = j.querySelector('[data-h-titulo]'); t.textContent = r[1]; t.classList.toggle('hoje__estado--aberto', r[0]);
            j.querySelector('[data-h-detalhe]').textContent = r[2] + (tel ? ' · ' + tel : '');
        };
        atualizar(); setInterval(atualizar, 60000);
    }
    var t = document.querySelector('[data-hoje-tempo][data-atualizar]');
    if (t && window.fetch) {
        fetch('/api/signage/tempo.php', { credentials: 'omit' }).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) {
            if (!d || !d.tempo) return;
            var x = d.tempo, h0 = (x.dias || [])[0];
            t.querySelector('[data-t-temp]').textContent = x.temperatura + '°';
            t.querySelector('[data-t-texto]').textContent = x.texto;
            t.querySelector('[data-t-icone]').className = 'bi ' + x.icone;
            t.querySelector('[data-t-extremos]').textContent = h0 ? 'máx. ' + h0.max + '° · mín. ' + h0.min + '°' : '';
            t.hidden = false;
        }).catch(function () {});
    }
})();
</script>
    <?php
}
