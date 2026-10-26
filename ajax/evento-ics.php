<?php
/**
 * Ficheiro .ics de um evento (CORE) — abre no calendário do telemóvel/computador
 * (Apple Calendário, Outlook, Google, etc.). /ajax/evento-ics.php?id=N
 */
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/calendario.php";

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$st->execute([$id]);
$e = $st->fetch(PDO::FETCH_ASSOC);
if (!$e) { http_response_code(404); exit('Evento não encontrado.'); }

// texto ICS: escapar \ ; , e quebras de linha; linhas dobradas a 75 octetos
$esc = fn($s) => str_replace(["\\", ";", ",", "\r\n", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", "\\n", ""], (string)$s);
$dobrar = function ($linha) {
    $out = ''; while (strlen($linha) > 75) { $c = mb_strcut($linha, 0, 75, 'UTF-8'); $out .= $c . "\r\n "; $linha = substr($linha, strlen($c)); }
    return $out . $linha . "\r\n";
};
[$ini, $fim] = eventoDatasCal($e);
$dia = eventoDiaInteiro($e);
// endereço público do evento: domínio configurado no admin (atrás de proxies o Host é o interno)
$dominio = rtrim((string)siteConfig('dominio', ''), '/');
$base = $dominio !== '' ? (preg_match('#^https?://#', $dominio) ? $dominio : 'https://' . $dominio)
      : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$host = parse_url($base, PHP_URL_HOST) ?: 'freguesia';
$url = $base . '/evento.php?id=' . $id;

$linhas = [
    'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Junta de Freguesia//Agenda//PT', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
    'BEGIN:VEVENT',
    'UID:evento-' . $id . '@' . $host,
    'DTSTAMP:' . gmdate('Ymd\THis\Z'),
    $dia ? 'DTSTART;VALUE=DATE:' . $ini : 'DTSTART;TZID=Europe/Lisbon:' . $ini,
    $dia ? 'DTEND;VALUE=DATE:' . $fim : 'DTEND;TZID=Europe/Lisbon:' . $fim,
    'SUMMARY:' . $esc($e['titulo']),
    'DESCRIPTION:' . $esc(trim(mb_substr(strip_tags((string)$e['descricao']), 0, 1000)) . "\n\n" . $url),
    'LOCATION:' . $esc(trim((string)($e['local'] ?? '')) ?: nomeFreguesia()),
    'URL:' . $url,
    'ORGANIZER;CN=' . $esc(siteConfig('nome_site', 'Junta de Freguesia')) . ':mailto:' . (siteConfig('email', '') ?: 'nao-responder@' . $host),
    'END:VEVENT', 'END:VCALENDAR',
];
$semAcentos = strtr(mb_strtolower($e['titulo']), ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c', 'º' => 'o', 'ª' => 'a']);
$nome = preg_replace('/[^a-z0-9]+/', '-', $semAcentos) ?: 'evento';
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . trim($nome, '-') . '.ics"');
foreach ($linhas as $l) echo $dobrar($l);
