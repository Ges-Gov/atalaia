<?php
/**
 * Publicar no site a partir da Intranet GesGov (CORE: ficheiro idêntico em todos os sites) — 11/2026.
 *
 * O «Acompanhamento das Juntas» da Intranet deixa a equipa da GesGov publicar no site de uma junta. Campo «tipo»:
 *  - noticia (por omissão) → como o admin/adicionar_noticia.php: título, texto, data, categoria, capa e galeria
 *    (assets/img/ + noticias_imagens);
 *  - evento → como o admin/adicionar_evento.php: título, texto, datas, categoria, local, coordenadas, inscrições,
 *    imagem e fotos (álbum da Galeria ligado ao evento, includes/galeria.php);
 *  - documento → como o admin/adicionar_documento.php (documentos da Junta, área «freguesia»): PDF em assets/docs/;
 *  - documento_assembleia → como o admin/assembleia-documentos.php: PDF/Word/Excel/ZIP/imagem em uploads/assembleia/.
 * Fica tudo logo publicado (ativo).
 *
 * Segurança: só POST, com «Authorization: Bearer <chave>» ou «X-GesGov-Chave: <chave>». A chave vive só no .env da
 * Intranet (JUNTAS_SITES_TOKEN); aqui está apenas o SHA-256 dela. Os ficheiros são validados pelo conteúdo (imagens
 * com getimagesize, PDF pelo cabeçalho) e gravados com nome novo. Os textos são guardados como texto simples.
 *
 * Resposta JSON: {"ok": true, "tipo": "...", "id": 123, "url": "..."} ou {"ok": false, "erro": "..."}.
 */

const PUBLICAR_CHAVE_SHA256 = 'db483e49a3b9d784b45cf915b8b6ea82bbad0a77c89bd1535a8320d90889ba24';
const PUBLICAR_IMAGEM_MAX = 15 * 1024 * 1024;
const PUBLICAR_FICHEIRO_MAX = 40 * 1024 * 1024;
const PUBLICAR_GALERIA_MAX = 30;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function publicarResponder(int $codigo, array $dados): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    publicarResponder(405, ['ok' => false, 'erro' => 'Só POST.']);
}

// Também em «X-GesGov-Chave»: alguns Apache (ex. o cPanel do Atalaia) não passam o Authorization ao PHP.
$autorizacao = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
$chave = preg_match('/^Bearer\s+(\S+)$/i', $autorizacao, $m) ? $m[1] : (string)($_SERVER['HTTP_X_GESGOV_CHAVE'] ?? '');
if ($chave === '' || !hash_equals(PUBLICAR_CHAVE_SHA256, hash('sha256', $chave))) {
    publicarResponder(401, ['ok' => false, 'erro' => 'Sem autorização.']);
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/categorias.php';

/* ---------- auxiliares ---------- */

/** Texto simples (sem HTML), sem espaços à volta; null se vazio. */
function publicarTexto(string $campo, int $max = 0): ?string
{
    $v = trim(strip_tags(str_replace("\r\n", "\n", (string)($_POST[$campo] ?? ''))));
    if ($v === '') return null;
    return $max > 0 ? mb_substr($v, 0, $max) : $v;
}

/** Data/hora «AAAA-MM-DD HH:MM:SS» (ou só a data com $soData); null se vazia ou inválida. */
function publicarData(string $campo, bool $soData = false): ?string
{
    $v = trim((string)($_POST[$campo] ?? ''));
    if ($v === '' || ($t = strtotime($v)) === false) return null;
    return date($soData ? 'Y-m-d' : 'Y-m-d H:i:s', $t);
}

/** Ficheiro enviado sem erro (um dos de $_FILES, já normalizado). */
function publicarEnviado(?array $f): bool
{
    return $f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !empty($f['tmp_name']) && is_uploaded_file($f['tmp_name']);
}

/** Grava uma imagem (jpg/png/webp verdadeira, até 15 MB) em assets/img/ com $prefixo. Devolve o nome ou null. */
function publicarGuardarImagem(array $f, string $prefixo): ?string
{
    if (!publicarEnviado($f) || ($f['size'] ?? 0) > PUBLICAR_IMAGEM_MAX) return null;
    $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset($tipos[$info[2]])) return null;
    $base = trim(preg_replace('/[^a-zA-Z0-9\-_]/', '-', pathinfo((string)$f['name'], PATHINFO_FILENAME)), '-') ?: 'foto';
    $nome = $prefixo . '-' . substr($base, 0, 60) . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $tipos[$info[2]];
    return move_uploaded_file($f['tmp_name'], __DIR__ . '/../assets/img/' . $nome) ? $nome : null;
}

/** As várias fotos de um campo «nome[]», cada uma como um $_FILES simples. */
function publicarVarios(string $campo): array
{
    $lista = [];
    if (!empty($_FILES[$campo]['name']) && is_array($_FILES[$campo]['name'])) {
        foreach (array_slice(array_keys($_FILES[$campo]['name']), 0, PUBLICAR_GALERIA_MAX) as $i) {
            $lista[] = [
                'name' => $_FILES[$campo]['name'][$i] ?? '',
                'tmp_name' => $_FILES[$campo]['tmp_name'][$i] ?? '',
                'size' => $_FILES[$campo]['size'][$i] ?? 0,
                'error' => $_FILES[$campo]['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            ];
        }
    }
    return $lista;
}

$tipo = (string)($_POST['tipo'] ?? 'noticia');
$titulo = publicarTexto('titulo', 255);
if ($titulo === null) {
    publicarResponder(422, ['ok' => false, 'erro' => 'Falta o título.']);
}
$descricao = publicarTexto('descricao');
$categoriaConteudo = in_array($_POST['categoria'] ?? '', $GLOBALS['CATEGORIAS_CONTEUDO'] ?? [], true) ? $_POST['categoria'] : null;

/* ---------- notícia ---------- */
if ($tipo === 'noticia') {
    if ($descricao === null) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Faltam o título ou o texto.']);
    }
    $imagem = isset($_FILES['imagem']) ? publicarGuardarImagem($_FILES['imagem'], 'noticia') : null;
    $pdo->prepare('INSERT INTO noticias (titulo, descricao, imagem, imagem_foco_x, imagem_foco_y, data, categoria) VALUES (?, ?, ?, 50, 50, ?, ?)')
        ->execute([$titulo, $descricao, $imagem, publicarData('data') ?? date('Y-m-d H:i:s'), $categoriaConteudo]);
    $id = (int)$pdo->lastInsertId();

    $fotos = 0;
    $stmtImg = $pdo->prepare('INSERT INTO noticias_imagens (noticia_id, ficheiro, ordem) VALUES (?, ?, ?)');
    foreach (publicarVarios('galeria') as $f) {
        if ($g = publicarGuardarImagem($f, 'noticia')) {
            try { $stmtImg->execute([$id, $g, $fotos]); $fotos++; } catch (Throwable $e) {}
        }
    }

    publicarResponder(201, ['ok' => true, 'tipo' => 'noticia', 'id' => $id, 'url' => 'noticia.php?id=' . $id, 'capa' => $imagem !== null, 'fotos_galeria' => $fotos]);
}

/* ---------- evento ---------- */
if ($tipo === 'evento') {
    $dataEvento = publicarData('data_evento');
    if ($dataEvento === null) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Falta a data do evento.']);
    }
    $coordenada = fn ($c) => is_numeric($_POST[$c] ?? null) ? (string)(float)$_POST[$c] : null;
    $vagas = trim((string)($_POST['inscricoes_vagas'] ?? '')) !== '' ? max(1, (int)$_POST['inscricoes_vagas']) : null;
    $imagem = isset($_FILES['imagem']) ? publicarGuardarImagem($_FILES['imagem'], 'evento') : null;

    $pdo->prepare('INSERT INTO eventos (titulo, descricao, data_evento, data_fim, imagem, imagem_foco_x, imagem_foco_y, categoria, local, latitude, longitude, inscricoes_ativas, inscricoes_vagas, inscricoes_ate) VALUES (?, ?, ?, ?, ?, 50, 50, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $titulo, (string)$descricao, $dataEvento, publicarData('data_fim'), $imagem, $categoriaConteudo,
            publicarTexto('local', 255), $coordenada('latitude'), $coordenada('longitude'),
            !empty($_POST['inscricoes_ativas']) ? 1 : 0, $vagas, publicarData('inscricoes_ate'),
        ]);
    $id = (int)$pdo->lastInsertId();

    // Fotografias → Galeria, num álbum com o título do evento (como no backoffice).
    $fotos = 0;
    $listaFotos = publicarVarios('fotos');
    if ($listaFotos) {
        require_once __DIR__ . '/../includes/galeria.php';
        try {
            $albumId = albumDaOrigem($pdo, 'evento', $id, $titulo);
            $pacote = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
            foreach ($listaFotos as $f) {
                foreach (['name', 'tmp_name', 'error', 'size'] as $k) $pacote[$k][] = $f[$k];
                $pacote['type'][] = '';
            }
            [$fotos] = guardarImagensNoAlbum($pdo, $albumId, $pacote);
        } catch (Throwable $e) {
            // O evento fica; só as fotos não.
        }
    }

    publicarResponder(201, ['ok' => true, 'tipo' => 'evento', 'id' => $id, 'url' => 'evento.php?id=' . $id, 'capa' => $imagem !== null, 'fotos_galeria' => (int)$fotos]);
}

/* ---------- documento da Junta ---------- */
if ($tipo === 'documento') {
    $categoria = publicarTexto('categoria', 100);
    $f = $_FILES['ficheiro'] ?? null;
    if ($categoria === null) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Falta a categoria.']);
    }
    if (!publicarEnviado($f) || ($f['size'] ?? 0) > PUBLICAR_FICHEIRO_MAX) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Falta o ficheiro PDF (ou é maior que 40 MB).']);
    }
    if (strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION)) !== 'pdf' || file_get_contents($f['tmp_name'], false, null, 0, 5) !== '%PDF-') {
        publicarResponder(422, ['ok' => false, 'erro' => 'O ficheiro não é um PDF válido.']);
    }
    $pasta = __DIR__ . '/../assets/docs/';
    if (!is_dir($pasta)) @mkdir($pasta, 0775, true);
    $nome = 'documento_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
    if (!move_uploaded_file($f['tmp_name'], $pasta . $nome)) {
        publicarResponder(500, ['ok' => false, 'erro' => 'Não foi possível gravar o ficheiro no site.']);
    }

    $valores = [$titulo, (string)$descricao, 'freguesia', $categoria, $nome, isset($_POST['ativo']) && !$_POST['ativo'] ? 0 : 1, publicarData('data_documento', true)];
    try {
        $pdo->prepare('INSERT INTO documentos (titulo, descricao, area, categoria, ficheiro, ativo, data_documento, data_limite) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([...$valores, publicarData('data_limite', true)]);
    } catch (Throwable $e) {
        // Sites do modelo antigo: sem «data_limite» (prazo de afixação).
        $pdo->prepare('INSERT INTO documentos (titulo, descricao, area, categoria, ficheiro, ativo, data_documento) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute($valores);
    }
    $id = (int)$pdo->lastInsertId();

    publicarResponder(201, ['ok' => true, 'tipo' => 'documento', 'id' => $id, 'url' => 'assets/docs/' . $nome]);
}

/* ---------- documento da Assembleia de Freguesia ---------- */
if ($tipo === 'documento_assembleia') {
    $categoria = publicarTexto('categoria', 100);
    $f = $_FILES['ficheiro'] ?? null;
    if ($categoria === null) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Falta a categoria.']);
    }
    if (!publicarEnviado($f) || ($f['size'] ?? 0) > PUBLICAR_FICHEIRO_MAX) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Falta o ficheiro (ou é maior que 40 MB).']);
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png'];
    $inicio = (string)file_get_contents($f['tmp_name'], false, null, 0, 8);
    $valido = match (true) {
        !in_array($ext, $permitidos, true) => false,
        $ext === 'pdf' => str_starts_with($inicio, '%PDF-'),
        in_array($ext, ['jpg', 'jpeg', 'png'], true) => (bool)@getimagesize($f['tmp_name']),
        in_array($ext, ['docx', 'xlsx', 'zip'], true) => str_starts_with($inicio, "PK"),
        default => str_starts_with($inicio, "\xD0\xCF\x11\xE0"),   // .doc / .xls antigos
    };
    if (!$valido) {
        publicarResponder(422, ['ok' => false, 'erro' => 'Tipo de ficheiro não permitido (PDF, Word, Excel, ZIP ou imagem).']);
    }
    $pasta = __DIR__ . '/../uploads/assembleia/';
    if (!is_dir($pasta)) @mkdir($pasta, 0775, true);
    $nome = 'assembleia_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $pasta . $nome)) {
        publicarResponder(500, ['ok' => false, 'erro' => 'Não foi possível gravar o ficheiro no site.']);
    }

    $dataDocumento = publicarData('data_documento', true);
    $ano = preg_match('/^\d{4}$/', trim((string)($_POST['ano'] ?? ''))) ? trim($_POST['ano']) : ($dataDocumento ? substr($dataDocumento, 0, 4) : null);
    $pdo->prepare('INSERT INTO assembleia_documentos (titulo, categoria, descricao, ficheiro, data_documento, ano, tags, destaque, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)')
        ->execute([$titulo, $categoria, (string)$descricao, $nome, $dataDocumento, $ano, (string)publicarTexto('tags', 255), !empty($_POST['destaque']) ? 1 : 0]);
    $id = (int)$pdo->lastInsertId();

    publicarResponder(201, ['ok' => true, 'tipo' => 'documento_assembleia', 'id' => $id, 'url' => 'uploads/assembleia/' . $nome]);
}

publicarResponder(422, ['ok' => false, 'erro' => 'Tipo desconhecido (noticia, evento, documento ou documento_assembleia).']);
