<?php
/**
 * "Adicionar ao calendário" (CORE) — links para Google Calendar e ficheiro .ics (Apple,
 * Outlook, telemóveis), e texto "Faltam N dias". Eventos com hora 00:00 são tratados como
 * eventos de dia inteiro (é assim que o backoffice guarda eventos sem hora).
 */
function eventoDiaInteiro(array $e): bool
{
    return date('H:i', strtotime($e['data_evento'])) === '00:00'
        && (empty($e['data_fim']) || date('H:i', strtotime($e['data_fim'])) === '00:00');
}

/** Datas no formato do Google/ICS: [inicio, fim]. */
function eventoDatasCal(array $e): array
{
    $ini = strtotime($e['data_evento']);
    if (eventoDiaInteiro($e)) {
        $fim = !empty($e['data_fim']) ? strtotime($e['data_fim']) : $ini;
        return [date('Ymd', $ini), date('Ymd', strtotime('+1 day', $fim))];   // fim exclusivo
    }
    $fim = !empty($e['data_fim']) ? strtotime($e['data_fim']) : strtotime('+2 hours', $ini);
    return [date('Ymd\THis', $ini), date('Ymd\THis', $fim)];
}

function eventoLinkGoogle(array $e): string
{
    [$i, $f] = eventoDatasCal($e);
    $q = [
        'action'  => 'TEMPLATE',
        'text'    => $e['titulo'],
        'dates'   => $i . '/' . $f,
        'details' => trim(mb_substr(strip_tags((string)($e['descricao'] ?? '')), 0, 600)),
        'location'=> trim((string)($e['local'] ?? '')) ?: nomeFreguesia(),
        'ctz'     => 'Europe/Lisbon',
    ];
    return 'https://calendar.google.com/calendar/render?' . http_build_query($q);
}

/** "Hoje", "Amanhã", "Faltam 5 dias" — ou '' se já passou. */
function eventoFaltam(array $e): string
{
    $hoje = new DateTime('today', new DateTimeZone('Europe/Lisbon'));
    $dia = new DateTime(date('Y-m-d', strtotime($e['data_evento'])), new DateTimeZone('Europe/Lisbon'));
    $d = (int)$hoje->diff($dia)->format('%r%a');
    if ($d < 0) return '';
    return $d === 0 ? 'Hoje' : ($d === 1 ? 'Amanhã' : 'Faltam ' . $d . ' dias');
}

/** Bloco de botões "Adicionar ao calendário". */
function botoesCalendario(array $e): string
{
    if (empty($e['data_evento']) || strtotime($e['data_evento']) < strtotime('today')) return '';
    $t = htmlspecialchars($e['titulo']);
    return '<div class="cal-botoes" role="group" aria-label="Adicionar «' . $t . '» ao calendário">'
         . '<span class="cal-botoes__rot"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Adicionar ao calendário:</span>'
         . '<a class="cal-btn" href="' . htmlspecialchars(eventoLinkGoogle($e)) . '" target="_blank" rel="noopener">Google <span class="sr-only">(abre numa nova janela)</span></a>'
         . '<a class="cal-btn" href="/ajax/evento-ics.php?id=' . (int)$e['id'] . '">Apple / Outlook <span class="sr-only">(descarrega ficheiro .ics)</span></a>'
         . '</div>';
}
