<?php
/**
 * Pesquisa "Spotlight" (Ctrl+K) — CORE: ficheiro idêntico em todos os sites.
 *
 * Devolve JSON com resultados que levam DIRETAMENTE ao item (a notícia, o evento, o PDF, o
 * telefone do contacto útil), ao contrário de ajax/pesquisa_global.php, que devolve HTML e
 * aponta para as páginas de listagem. Os atalhos de serviço (páginas fixas) vivem no app.js e
 * filtram-se no browser sem pedido ao servidor; aqui só entra o conteúdo da base de dados.
 *
 * As tabelas usam collation *_ci, por isso LIKE já ignora maiúsculas e acentos
 * ("autarquicas" encontra "Autárquicas").
 */
require_once __DIR__ . "/../includes/db.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2 || mb_strlen($q) > 80) {
    echo json_encode(['resultados' => []]);
    exit;
}

$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
$resultados = [];

function resumo($texto, $n = 90) {
    $t = trim(preg_replace('/\s+/', ' ', strip_tags((string)$texto)));
    return mb_strlen($t) > $n ? mb_substr($t, 0, $n) . '…' : $t;
}

function procurar($pdo, $sql, $n, $like) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_fill(0, $n, $like));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return []; // tabela/coluna ainda não migrada neste site: ignora o grupo
    }
}

/* Contactos úteis (GNR, Bombeiros, Centro de Saúde…) — o telefone vem logo no resultado */
foreach (procurar($pdo, "SELECT nome, categoria, telefone, email FROM contactos_uteis
                         WHERE ativo = 1 AND (nome LIKE ? OR categoria LIKE ? OR descricao LIKE ?)
                         ORDER BY destaque DESC, ordem ASC LIMIT 4", 3, $like) as $r) {
    $tel = trim((string)$r['telefone']);
    $resultados[] = [
        'grupo' => 'Contactos úteis',
        'titulo' => $r['nome'],
        'descricao' => $tel !== '' ? 'Telefone: ' . $tel : ($r['email'] ?: $r['categoria']),
        'link' => $tel !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', explode('/', $tel)[0]) : '/contactos.php',
        'icone' => 'bi-telephone-fill',
    ];
}

/* Notícias */
foreach (procurar($pdo, "SELECT id, titulo, descricao, data FROM noticias
                         WHERE titulo LIKE ? OR descricao LIKE ? OR categoria LIKE ?
                         ORDER BY data DESC LIMIT 5", 3, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Notícias',
        'titulo' => $r['titulo'],
        'descricao' => date('d/m/Y', strtotime($r['data'])) . ' · ' . resumo($r['descricao'], 70),
        'link' => '/noticia.php?id=' . (int)$r['id'],
        'icone' => 'bi-newspaper',
    ];
}

/* Eventos */
foreach (procurar($pdo, "SELECT id, titulo, descricao, data_evento, local FROM eventos
                         WHERE titulo LIKE ? OR descricao LIKE ? OR local LIKE ?
                         ORDER BY (data_evento >= CURDATE()) DESC, ABS(DATEDIFF(data_evento, CURDATE())) ASC LIMIT 4", 3, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Eventos',
        'titulo' => $r['titulo'],
        'descricao' => date('d/m/Y', strtotime($r['data_evento'])) . (!empty($r['local']) ? ' · ' . $r['local'] : ''),
        'link' => '/evento.php?id=' . (int)$r['id'],
        'icone' => 'bi-calendar-event-fill',
    ];
}

/* Pontos de interesse */
foreach (procurar($pdo, "SELECT id, nome, localizacao, descricao FROM pontos_interesse
                         WHERE nome LIKE ? OR descricao LIKE ? OR localizacao LIKE ?
                         ORDER BY nome LIMIT 4", 3, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Pontos de interesse',
        'titulo' => $r['nome'],
        'descricao' => $r['localizacao'] ?: resumo($r['descricao'], 70),
        'link' => '/ponto.php?id=' . (int)$r['id'],
        'icone' => 'bi-geo-alt-fill',
    ];
}

/* Documentos da Freguesia (PDF) */
foreach (procurar($pdo, "SELECT titulo, categoria, ficheiro, data_documento FROM documentos
                         WHERE ativo = 1 AND (titulo LIKE ? OR descricao LIKE ? OR categoria LIKE ?)
                         ORDER BY data_documento DESC LIMIT 4", 3, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Documentos',
        'titulo' => $r['titulo'],
        'descricao' => 'PDF' . (!empty($r['data_documento']) ? ' · ' . date('d/m/Y', strtotime($r['data_documento'])) : ''),
        'link' => '/assets/docs/' . rawurlencode($r['ficheiro']),
        'icone' => 'bi-file-earmark-pdf-fill',
        'novo_separador' => true,
    ];
}

/* Documentos da Assembleia (PDF) */
foreach (procurar($pdo, "SELECT titulo, categoria, ficheiro, data_documento FROM assembleia_documentos
                         WHERE ativo = 1 AND (titulo LIKE ? OR descricao LIKE ? OR tags LIKE ?)
                         ORDER BY data_documento DESC LIMIT 4", 3, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Documentos',
        'titulo' => $r['titulo'],
        'descricao' => 'Assembleia · PDF' . (!empty($r['data_documento']) ? ' · ' . date('d/m/Y', strtotime($r['data_documento'])) : ''),
        'link' => '/uploads/assembleia/' . rawurlencode($r['ficheiro']),
        'icone' => 'bi-file-earmark-pdf-fill',
        'novo_separador' => true,
    ];
}

/* Associações e comércio (não têm página própria: levam à listagem) */
foreach (procurar($pdo, "SELECT nome, categoria, telefone FROM associacoes
                         WHERE nome LIKE ? OR descricao LIKE ? LIMIT 3", 2, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Associações',
        'titulo' => $r['nome'],
        'descricao' => $r['categoria'] ?: ($r['telefone'] ? 'Telefone: ' . $r['telefone'] : 'Associação'),
        'link' => '/associacoes.php',
        'icone' => 'bi-people-fill',
    ];
}
foreach (procurar($pdo, "SELECT nome, tipo, telefone FROM comercio_local
                         WHERE nome LIKE ? OR tipo LIKE ? LIMIT 3", 2, $like) as $r) {
    $resultados[] = [
        'grupo' => 'Comércio local',
        'titulo' => $r['nome'],
        'descricao' => trim(($r['tipo'] ?: 'Comércio') . ($r['telefone'] ? ' · ' . $r['telefone'] : '')),
        'link' => '/comercio.php',
        'icone' => 'bi-shop',
    ];
}

echo json_encode(['resultados' => $resultados], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
