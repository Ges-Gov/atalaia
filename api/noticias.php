<?php
/**
 * Últimas publicações do site em JSON (CORE: ficheiro idêntico em todos os sites).
 *
 * Usado pelo «Acompanhamento das Juntas» da Intranet GesGov, que mostra o site de cada junta ao lado do
 * Facebook para se perceber se o site acompanha o que é publicado lá (11/2026). Só GET e só dados que já são
 * públicos nas páginas de notícias e eventos: título, data e link.
 */
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');

$noticias = $pdo->query('SELECT id, titulo, data FROM noticias ORDER BY data DESC, id DESC LIMIT 15')
    ->fetchAll(PDO::FETCH_ASSOC);
// Os eventos não têm data de publicação — vai a data do evento.
$eventos = $pdo->query('SELECT id, titulo, data_evento FROM eventos ORDER BY data_evento DESC, id DESC LIMIT 10')
    ->fetchAll(PDO::FETCH_ASSOC);

// Tal como está na base de dados (hora de Lisboa, «AAAA-MM-DD HH:MM:SS») — sem converter fusos.
$data = fn ($valor) => $valor ? (string) $valor : null;

// Identificação pública da junta (a mesma do rodapé do site). SELECT * porque os sites mais antigos não têm
// todas as colunas (ex. «facebook» veio na migração 041).
$cfg = $pdo->query('SELECT * FROM configuracoes_site LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$campo = fn ($nome) => trim((string) ($cfg[$nome] ?? '')) ?: null;

echo json_encode([
    'site' => [
        'nome' => $campo('nome_site'),
        'email' => $campo('email'),
        'facebook' => $campo('facebook'),
    ],
    'total_noticias' => (int) $pdo->query('SELECT COUNT(*) FROM noticias')->fetchColumn(),
    'noticias' => array_map(fn ($n) => [
        'titulo' => (string) $n['titulo'],
        'data' => $data($n['data']),
        'url' => 'noticia.php?id=' . (int) $n['id'],
    ], $noticias),
    'eventos' => array_map(fn ($e) => [
        'titulo' => (string) $e['titulo'],
        'data' => $data($e['data_evento']),
        'url' => 'evento.php?id=' . (int) $e['id'],
    ], $eventos),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
