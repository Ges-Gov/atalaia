<?php
/**
 * Últimas publicações do site em JSON (CORE: ficheiro idêntico em todos os sites).
 *
 * Usado pelo «Acompanhamento das Juntas» da Intranet GesGov, que mostra o site de cada junta ao lado do
 * Facebook para se perceber se o site acompanha o que é publicado lá (11/2026). Só GET e só dados que já são
 * públicos nas páginas de notícias e eventos: título, data e link.
 *
 * Versão 2 (11/2026): também os documentos publicados por categoria (total e data do último — para o semáforo
 * dos documentos obrigatórios) e as visitas por mês (só totais, do includes/visitas.php).
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

// Uma consulta que falhe (tabela/coluna que um site antigo ainda não tem) não estraga o resto da resposta.
$tentar = function (string $sql) use ($pdo): array {
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
};

// Documentos publicados, por categoria: os da Junta (sem os arquivados) e os da Assembleia de Freguesia.
$documentos = [];
$docsJunta = $tentar("SELECT categoria, COUNT(*) total, MAX(COALESCE(data_documento, DATE(criado_em))) ultimo
    FROM documentos WHERE ativo = 1 AND arquivado_em IS NULL GROUP BY categoria")
    ?: $tentar("SELECT categoria, COUNT(*) total, MAX(COALESCE(data_documento, DATE(criado_em))) ultimo
    FROM documentos WHERE ativo = 1 GROUP BY categoria");
foreach ($docsJunta as $d) {
    $documentos[] = ['origem' => 'junta', 'categoria' => (string) $d['categoria'], 'total' => (int) $d['total'], 'ultimo' => $data($d['ultimo'])];
}
foreach ($tentar("SELECT categoria, COUNT(*) total, MAX(COALESCE(data_documento, DATE(criado_em))) ultimo
    FROM assembleia_documentos WHERE ativo = 1 GROUP BY categoria") as $d) {
    $documentos[] = ['origem' => 'assembleia', 'categoria' => (string) $d['categoria'], 'total' => (int) $d['total'], 'ultimo' => $data($d['ultimo'])];
}

// Visitas: só totais por mês (últimos 13 meses) e dos últimos 30 dias — nada por pessoa.
$visitasMeses = [];
foreach ($tentar("SELECT DATE_FORMAT(data, '%Y-%m') mes, SUM(total) total FROM visitas_diarias
    WHERE data >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 12 MONTH) GROUP BY mes ORDER BY mes") as $v) {
    $visitasMeses[$v['mes']] = (int) $v['total'];
}
$visitas30 = $tentar('SELECT COALESCE(SUM(total), 0) total, MIN(data) desde FROM visitas_diarias WHERE data > DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
$visitasDesde = $tentar('SELECT MIN(data) desde FROM visitas_diarias');

echo json_encode([
    'versao' => 2,
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
    'documentos' => $documentos,
    'visitas' => [
        'desde' => $data($visitasDesde[0]['desde'] ?? null),
        'ultimos_30_dias' => (int) ($visitas30[0]['total'] ?? 0),
        'meses' => (object) $visitasMeses,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
