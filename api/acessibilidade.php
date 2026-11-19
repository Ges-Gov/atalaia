<?php
/**
 * Registar uma avaliação de acessibilidade a partir da Intranet GesGov (CORE: ficheiro idêntico em todos os sites) —
 * 18/11/2026, Decreto-Lei n.º 83/2018.
 *
 * A Intranet («Acompanhamento das Juntas» → «Registar avaliação de acessibilidade») envia os resultados da avaliação
 * automática (AccessMonitor) e da manual (checklist «10 aspetos críticos»). Cada envio acrescenta uma linha a
 * acessibilidade_avaliacoes (migração 090); a Declaração de Acessibilidade mostra sempre a mais recente.
 *
 * Segurança: só POST, com a mesma chave do api/publicar_noticia.php («Authorization: Bearer <chave>» ou
 * «X-GesGov-Chave: <chave>»); aqui está apenas o SHA-256 dela.
 *
 * Campos (todos opcionais menos «estado»): data_declaracao (AAAA-MM-DD, por omissão hoje), estado (plenamente |
 * parcialmente | nao), auto_data, auto_ferramenta, auto_paginas, auto_nota (0 a 10), auto_resumo, manual_data,
 * manual_paginas, manual_satisfeitas, manual_total, usabilidade.
 * 19/11/2026 (Selo): auto_relatorio_url (ligação ao relatório da avaliação automática), selo (bronze | prata | ouro,
 * só depois de atribuído) e checklists[] = {checklist: 10aspetos | conteudo | transacao, data (AAAA-MM-DD), paginas,
 * satisfeitas, aplicaveis, evidencia_url} — uma linha por checklist em acessibilidade_checklists (migração 092).
 * As ligações aceites são https://… ou um caminho do próprio site (/assets/…).
 * Resposta JSON: {"ok": true, "id": 12, "url": "declaracao-acessibilidade.php"} ou {"ok": false, "erro": "..."}.
 */

if (!defined('PUBLICAR_CHAVE_SHA256')) {
    define('PUBLICAR_CHAVE_SHA256', 'db483e49a3b9d784b45cf915b8b6ea82bbad0a77c89bd1535a8320d90889ba24');
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function acessResponder(int $codigo, array $dados): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    acessResponder(405, ['ok' => false, 'erro' => 'Só POST.']);
}

$autorizacao = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
$chave = preg_match('/^Bearer\s+(\S+)$/i', $autorizacao, $m) ? $m[1] : (string)($_SERVER['HTTP_X_GESGOV_CHAVE'] ?? '');
if ($chave === '' || !hash_equals(PUBLICAR_CHAVE_SHA256, hash('sha256', $chave))) {
    acessResponder(401, ['ok' => false, 'erro' => 'Sem autorização.']);
}

require_once __DIR__ . '/../includes/db.php';

$entrada = $_POST;
if (!$entrada && str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'json')) {
    $entrada = json_decode((string) file_get_contents('php://input'), true) ?: [];
}

$texto = function (string $campo, int $max) use ($entrada): ?string {
    $v = trim(strip_tags((string)($entrada[$campo] ?? '')));
    return $v === '' ? null : mb_substr($v, 0, $max);
};
$dataCampo = function (string $campo) use ($entrada): ?string {
    $v = trim((string)($entrada[$campo] ?? ''));
    if ($v === '') return null;
    $d = DateTime::createFromFormat('!Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) acessResponder(422, ['ok' => false, 'erro' => "Data inválida em «{$campo}» (use AAAA-MM-DD)."]);
    return $v;
};
$inteiro = function (string $campo, int $max = 100000) use ($entrada): ?int {
    $v = trim((string)($entrada[$campo] ?? ''));
    if ($v === '') return null;
    if (!ctype_digit($v) || (int) $v > $max) acessResponder(422, ['ok' => false, 'erro' => "Número inválido em «{$campo}»."]);
    return (int) $v;
};

$estado = (string)($entrada['estado'] ?? '');
if (!in_array($estado, ['plenamente', 'parcialmente', 'nao'], true)) {
    acessResponder(422, ['ok' => false, 'erro' => 'O estado tem de ser plenamente, parcialmente ou nao.']);
}

$nota = trim(str_replace(',', '.', (string)($entrada['auto_nota'] ?? '')));
if ($nota !== '' && (!is_numeric($nota) || (float) $nota < 0 || (float) $nota > 10)) {
    acessResponder(422, ['ok' => false, 'erro' => 'A nota do AccessMonitor tem de estar entre 0 e 10.']);
}

$linha = [
    'data_declaracao' => $dataCampo('data_declaracao') ?? date('Y-m-d'),
    'estado' => $estado,
    'auto_data' => $dataCampo('auto_data'),
    'auto_ferramenta' => $texto('auto_ferramenta', 80),
    'auto_paginas' => $inteiro('auto_paginas', 10000),
    'auto_nota' => $nota === '' ? null : round((float) $nota, 2),
    'auto_resumo' => $texto('auto_resumo', 255),
    'manual_data' => $dataCampo('manual_data'),
    'manual_paginas' => $inteiro('manual_paginas', 10000),
    'manual_satisfeitas' => $inteiro('manual_satisfeitas', 1000),
    'manual_total' => $inteiro('manual_total', 1000),
    'usabilidade' => $texto('usabilidade', 4000),
];
if ($linha['manual_satisfeitas'] !== null && $linha['manual_total'] !== null && $linha['manual_satisfeitas'] > $linha['manual_total']) {
    acessResponder(422, ['ok' => false, 'erro' => 'Há mais requisitos satisfeitos do que o total.']);
}

// Ligações: https://… ou um caminho deste site; nada de javascript:, data:, etc.
$ligacao = function ($v, string $campo): ?string {
    $v = trim((string) $v);
    if ($v === '') return null;
    if (mb_strlen($v) > 255 || !preg_match('~^(https://[^\s<>"\']+|/[^\s<>"\']*)$~i', $v)) {
        acessResponder(422, ['ok' => false, 'erro' => "Ligação inválida em «{$campo}» (use https://… ou /caminho)."]);
    }
    return $v;
};
$selo = trim((string)($entrada['selo'] ?? ''));
if ($selo !== '' && !in_array($selo, ['bronze', 'prata', 'ouro'], true)) {
    acessResponder(422, ['ok' => false, 'erro' => 'O selo tem de ser bronze, prata ou ouro.']);
}

$checklists = [];
$nomesChecklists = ['10aspetos', 'conteudo', 'transacao'];
foreach ((array)($entrada['checklists'] ?? []) as $i => $c) {
    if (!is_array($c)) acessResponder(422, ['ok' => false, 'erro' => "Checklist {$i} inválida."]);
    $nome = (string)($c['checklist'] ?? '');
    if (!in_array($nome, $nomesChecklists, true)) {
        acessResponder(422, ['ok' => false, 'erro' => 'A checklist tem de ser 10aspetos, conteudo ou transacao.']);
    }
    $d = DateTime::createFromFormat('!Y-m-d', (string)($c['data'] ?? ''));
    if (!$d || $d->format('Y-m-d') !== (string) $c['data']) {
        acessResponder(422, ['ok' => false, 'erro' => "Data inválida na checklist «{$nome}» (use AAAA-MM-DD)."]);
    }
    $num = function ($v) use ($nome) {
        $v = trim((string) $v);
        if ($v === '') return null;
        if (!ctype_digit($v) || (int) $v > 10000) acessResponder(422, ['ok' => false, 'erro' => "Número inválido na checklist «{$nome}»."]);
        return (int) $v;
    };
    $sat = $num($c['satisfeitas'] ?? '');
    $apl = $num($c['aplicaveis'] ?? '');
    if ($sat === null || !$apl || $sat > $apl) {
        acessResponder(422, ['ok' => false, 'erro' => "Na checklist «{$nome}», os requisitos satisfeitos têm de estar entre 0 e os aplicáveis."]);
    }
    $checklists[] = [
        'checklist' => $nome,
        'data_avaliacao' => (string) $c['data'],
        'paginas' => $num($c['paginas'] ?? ''),
        'satisfeitas' => $sat,
        'aplicaveis' => $apl,
        'evidencia_url' => $ligacao($c['evidencia_url'] ?? '', "evidência da checklist {$nome}"),
    ];
}
$extra = [
    'auto_relatorio_url' => $ligacao($entrada['auto_relatorio_url'] ?? '', 'auto_relatorio_url'),
    'selo' => $selo === '' ? null : $selo,
];

try {
    $temExtra = (bool) $pdo->query("SHOW COLUMNS FROM acessibilidade_avaliacoes LIKE 'selo'")->fetch();
} catch (Throwable $e) {
    acessResponder(409, ['ok' => false, 'erro' => 'O site ainda não tem a tabela acessibilidade_avaliacoes (falta correr a migração 090).']);
}
if (($checklists || array_filter($extra, fn($v) => $v !== null)) && !$temExtra) {
    acessResponder(409, ['ok' => false, 'erro' => 'O site ainda não tem as checklists (falta correr a migração 092).']);
}
if ($temExtra) $linha += $extra;

try {
    $pdo->beginTransaction();
    $colunas = array_keys($linha);
    $sql = 'INSERT INTO acessibilidade_avaliacoes (' . implode(', ', $colunas) . ') VALUES (:' . implode(', :', $colunas) . ')';
    $pdo->prepare($sql)->execute($linha);
    $id = (int) $pdo->lastInsertId();
    $ins = $pdo->prepare('INSERT INTO acessibilidade_checklists (avaliacao_id, checklist, data_avaliacao, paginas, satisfeitas, aplicaveis, evidencia_url)
                          VALUES (:avaliacao_id, :checklist, :data_avaliacao, :paginas, :satisfeitas, :aplicaveis, :evidencia_url)');
    foreach ($checklists as $c) $ins->execute(['avaliacao_id' => $id] + $c);
    $pdo->commit();
    acessResponder(200, ['ok' => true, 'id' => $id, 'checklists' => count($checklists), 'url' => 'declaracao-acessibilidade.php']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    acessResponder(500, ['ok' => false, 'erro' => 'Não foi possível gravar a avaliação.']);
}
