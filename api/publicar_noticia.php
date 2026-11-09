<?php
/**
 * Publicar uma notícia a partir da Intranet GesGov (CORE: ficheiro idêntico em todos os sites) — 11/2026.
 *
 * O «Acompanhamento das Juntas» da Intranet deixa a equipa da GesGov publicar no site de uma junta o que ela pôs
 * só no Facebook. Faz o mesmo que o admin/adicionar_noticia.php: título, texto, data, categoria, foto de capa e
 * fotos da galeria (assets/img/ + noticias_imagens). A notícia fica logo publicada.
 *
 * Segurança: só POST por HTTPS, com «Authorization: Bearer <chave>». A chave vive só no .env da Intranet
 * (JUNTAS_SITES_TOKEN); aqui está apenas o SHA-256 dela, que não permite chegar à chave. As imagens são
 * validadas (extensão + getimagesize) e gravadas com um nome novo. O texto é guardado como texto simples.
 *
 * Resposta JSON: {"ok": true, "id": 123, "url": "noticia.php?id=123"} ou {"ok": false, "erro": "..."}.
 */

const PUBLICAR_CHAVE_SHA256 = 'db483e49a3b9d784b45cf915b8b6ea82bbad0a77c89bd1535a8320d90889ba24';
const PUBLICAR_IMAGEM_MAX = 15 * 1024 * 1024;
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

$titulo = trim((string)($_POST['titulo'] ?? ''));
$descricao = trim(str_replace("\r\n", "\n", (string)($_POST['descricao'] ?? '')));
if ($titulo === '' || $descricao === '') {
    publicarResponder(422, ['ok' => false, 'erro' => 'Faltam o título ou o texto.']);
}
$titulo = mb_substr($titulo, 0, 255);
// Texto simples (como nos sites do modelo antigo): sem etiquetas HTML.
$descricao = strip_tags($descricao);

$data = date('Y-m-d H:i:s');
if (!empty($_POST['data'])) {
    $t = strtotime((string)$_POST['data']);
    if ($t !== false) $data = date('Y-m-d H:i:s', $t);
}
$categoria = in_array($_POST['categoria'] ?? '', $GLOBALS['CATEGORIAS_CONTEUDO'] ?? [], true) ? $_POST['categoria'] : null;

/** Grava uma imagem enviada (só jpg/png/webp, imagem verdadeira, até 15 MB). Devolve o nome ou null. */
function publicarGuardarImagem(array $f): ?string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) return null;
    if (($f['size'] ?? 0) > PUBLICAR_IMAGEM_MAX) return null;
    $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset($tipos[$info[2]])) return null;
    $ext = $tipos[$info[2]];
    $base = trim(preg_replace('/[^a-zA-Z0-9\-_]/', '-', pathinfo((string)$f['name'], PATHINFO_FILENAME)), '-') ?: 'foto';
    $nome = 'noticia-' . substr($base, 0, 60) . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], __DIR__ . '/../assets/img/' . $nome) ? $nome : null;
}

$imagem = isset($_FILES['imagem']) ? publicarGuardarImagem($_FILES['imagem']) : null;

$stmt = $pdo->prepare('INSERT INTO noticias (titulo, descricao, imagem, imagem_foco_x, imagem_foco_y, data, categoria) VALUES (?, ?, ?, 50, 50, ?, ?)');
$stmt->execute([$titulo, $descricao, $imagem, $data, $categoria]);
$id = (int)$pdo->lastInsertId();

// Fotos da galeria (galeria[] — várias)
$fotosGaleria = 0;
if (!empty($_FILES['galeria']['name']) && is_array($_FILES['galeria']['name'])) {
    $stmtImg = $pdo->prepare('INSERT INTO noticias_imagens (noticia_id, ficheiro, ordem) VALUES (?, ?, ?)');
    foreach (array_slice(array_keys($_FILES['galeria']['name']), 0, PUBLICAR_GALERIA_MAX) as $i) {
        $guardada = publicarGuardarImagem([
            'name' => $_FILES['galeria']['name'][$i] ?? '',
            'tmp_name' => $_FILES['galeria']['tmp_name'][$i] ?? '',
            'size' => $_FILES['galeria']['size'][$i] ?? 0,
            'error' => $_FILES['galeria']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        ]);
        if ($guardada) {
            try {
                $stmtImg->execute([$id, $guardada, $fotosGaleria]);
                $fotosGaleria++;
            } catch (Throwable $e) {
                // Site sem a tabela noticias_imagens: fica só a notícia.
            }
        }
    }
}

publicarResponder(201, [
    'ok' => true,
    'id' => $id,
    'url' => 'noticia.php?id=' . $id,
    'capa' => $imagem !== null,
    'fotos_galeria' => $fotosGaleria,
]);
