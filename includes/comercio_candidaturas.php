<?php
/**
 * Economia Local em modo de candidaturas (Atalaia, 06/11/2026, pedido do presidente da Junta).
 *
 * Com tema_config «comercio_candidaturas» = 1, os estabelecimentos deixam de aparecer no site (página, mapa, pesquisa,
 * contactos, assistente) — os dados ficam na tabela comercio_local — e a página /comercio.php mostra um texto de
 * apresentação e um formulário para os negócios da freguesia pedirem para ser divulgados. As candidaturas ficam na
 * tabela comercio_candidaturas (migração 088), a Junta recebe um email e trata-as no backoffice
 * (admin/comercio-candidaturas.php). Sem a chave (ou = 0) tudo fica como antes.
 */

const COMERCIO_CAND_DIR = __DIR__ . '/../uploads/comercio-candidaturas/';
const COMERCIO_CAND_TIPOS = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
const COMERCIO_CAND_MAX_MB = 5;
const COMERCIO_CAND_ESTADOS = ['nova' => 'Nova', 'analise' => 'Em análise', 'aceite' => 'Aceite', 'recusada' => 'Recusada'];

/** A página Economia Local está em modo de candidaturas (e os estabelecimentos escondidos)? */
function comercioEmCandidaturas(): bool
{
    static $v = null;
    if ($v !== null) return $v;
    global $pdo;
    try {
        $st = $pdo->prepare("SELECT valor FROM tema_config WHERE chave = 'comercio_candidaturas'");
        $st->execute();
        return $v = ((string)$st->fetchColumn() === '1');
    } catch (Throwable $e) {
        return $v = false;
    }
}

/** Guarda a imagem enviada numa pasta que o site não serve diretamente. Devolve [nome gravado | null, erro]. */
function comercioCandGuardarImagem(array $f): array
{
    $erro = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($erro === UPLOAD_ERR_NO_FILE) return [null, 'Envie o logótipo ou uma imagem do estabelecimento.'];
    if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE || ($f['size'] ?? 0) > COMERCIO_CAND_MAX_MB * 1024 * 1024) {
        return [null, 'A imagem tem mais de ' . COMERCIO_CAND_MAX_MB . ' MB. Reduza-a e tente de novo.'];
    }
    if ($erro !== UPLOAD_ERR_OK) return [null, 'Não foi possível receber a imagem. Tente de novo.'];

    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    $mime = function_exists('finfo_open') ? (string)finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) : '';
    $info = @getimagesize($f['tmp_name']);
    if (!isset(COMERCIO_CAND_TIPOS[$ext]) || ($mime !== '' && !in_array($mime, COMERCIO_CAND_TIPOS, true)) || !$info) {
        return [null, 'A imagem tem de ser JPG, PNG ou WEBP.'];
    }

    if (!is_dir(COMERCIO_CAND_DIR) && !@mkdir(COMERCIO_CAND_DIR, 0775, true) && !is_dir(COMERCIO_CAND_DIR)) {
        return [null, 'Não foi possível guardar a imagem. Contacte a Junta de Freguesia.'];
    }
    if (!is_file(COMERCIO_CAND_DIR . '.htaccess')) @file_put_contents(COMERCIO_CAND_DIR . '.htaccess', "Require all denied\n");
    if (!is_file(COMERCIO_CAND_DIR . 'index.html')) @file_put_contents(COMERCIO_CAND_DIR . 'index.html', '');

    $novo = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!move_uploaded_file($f['tmp_name'], COMERCIO_CAND_DIR . $novo)) {
        return [null, 'Não foi possível guardar a imagem. Tente de novo.'];
    }
    return [$novo, ''];
}

/** Nome gravado de uma imagem de candidatura, só se tiver o formato que geramos. */
function comercioCandImagemNome($valor): string
{
    $v = (string)$valor;
    return preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $v) ? $v : '';
}
