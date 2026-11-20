<?php
/*
 * Percurso das ocorrências (20/11/2026, pedido da direção) — CORE.
 * Cada passo do «Histórico de Resolução» (pedido_timeline) guarda quem o fez (autor) e, nos encaminhamentos, para quem
 * foi (para). Quem trata a ocorrência pode encaminhá-la a outra pessoa da equipa e registar o que fez, com fotografias.
 * Sem a migração 093 (colunas autor_/para_), regista como antes, sem autor — nunca falha por causa disso.
 */

/** Regista um passo no percurso. Devolve o id da linha (0 se não foi possível). */
function percursoRegistar(PDO $pdo, int $pedidoId, string $tipo, string $titulo, ?string $descricao = null,
                          ?int $paraId = null, ?string $paraNome = null): int
{
    $autorId = (int)($_SESSION['admin_id'] ?? 0) ?: null;
    $autorNome = (string)($_SESSION['admin_nome'] ?? ($_SESSION['admin'] ?? ''));
    try {
        $pdo->prepare("INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao, autor_id, autor_nome, para_id, para_nome)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$pedidoId, $tipo, $titulo, $descricao, $autorId, $autorNome !== '' ? $autorNome : null, $paraId, $paraNome]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        try {   // BD ainda sem a migração 093
            $pdo->prepare("INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao) VALUES (?, ?, ?, ?)")
                ->execute([$pedidoId, $tipo, $titulo, $descricao]);
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e2) {
            return 0;
        }
    }
}

/** Pessoas da equipa que podem tratar ocorrências (administradores e assistentes operacionais ativos): [id => nome]. */
function percursoPessoas(PDO $pdo): array
{
    $ocultos = function_exists('utilizadoresGesGov') ? utilizadoresGesGov() : [];
    $pessoas = [];
    foreach ($pdo->query("SELECT id, nome, username, tipo FROM admin_utilizadores
                          WHERE tipo IN ('admin', 'operador') AND ativo = 1 ORDER BY nome ASC, username ASC") as $u) {
        if (in_array($u['username'], $ocultos, true)) continue;
        $pessoas[(int)$u['id']] = ['nome' => trim((string)$u['nome']) !== '' ? $u['nome'] : $u['username'], 'tipo' => $u['tipo']];
    }
    return $pessoas;
}

/** Nome de um utilizador do backoffice (ou '' se não existir). */
function percursoNome(PDO $pdo, ?int $id): string
{
    if (!$id) return '';
    $st = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(nome), ''), username) FROM admin_utilizadores WHERE id = ?");
    $st->execute([$id]);
    return (string)($st->fetchColumn() ?: '');
}

/**
 * Guarda as fotografias de um passo (campo de ficheiros múltiplo). Só imagens verdadeiras (getimagesize), nome gerado
 * por nós, reduzidas a 1600 px (JPEG) para não encher o disco. Devolve [n.º guardadas, lista de erros].
 */
function percursoGuardarFotos(PDO $pdo, int $pedidoId, int $timelineId, array $ficheiros, int $maximo = 6): array
{
    $guardadas = 0; $erros = [];
    if (empty($ficheiros['name']) || !is_array($ficheiros['name'])) return [0, []];
    $pasta = __DIR__ . '/../../uploads/ocorrencias/' . $pedidoId . '/';
    $n = 0;
    foreach ($ficheiros['name'] as $i => $nomeOriginal) {
        if ((string)$nomeOriginal === '') continue;
        if (++$n > $maximo) { $erros[] = "Só se guardam $maximo fotografias de cada vez."; break; }
        $nomeMostrado = mb_substr(basename((string)$nomeOriginal), 0, 60);
        $erroCod = (int)($ficheiros['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($erroCod !== UPLOAD_ERR_OK) {
            $erros[] = "«{$nomeMostrado}» não foi enviada: " . (function_exists('erroUploadTexto') ? erroUploadTexto($erroCod) : "erro $erroCod");
            continue;
        }
        $tmp = (string)($ficheiros['tmp_name'][$i] ?? '');
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $erros[] = "«{$nomeMostrado}» não é uma fotografia JPG, PNG ou WEBP.";
            continue;
        }
        if (!is_dir($pasta) && !@mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            $erros[] = 'Não foi possível criar a pasta das fotografias no servidor (permissões).';
            break;
        }
        $novo = 'intervencao_' . $timelineId . '_' . bin2hex(random_bytes(8));
        $destino = percursoReduzirFoto($tmp, (int)$info[2], $pasta . $novo);
        if ($destino === '') {
            $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
            if (!move_uploaded_file($tmp, $pasta . $novo . '.' . $ext)) { $erros[] = "«{$nomeMostrado}» não foi gravada (permissões)."; continue; }
            $destino = $novo . '.' . $ext;
        }
        try {
            $pdo->prepare("INSERT INTO pedido_timeline_fotos (timeline_id, pedido_id, ficheiro) VALUES (?, ?, ?)")
                ->execute([$timelineId, $pedidoId, $pedidoId . '/' . $destino]);
            $guardadas++;
        } catch (Throwable $e) {
            @unlink($pasta . $destino);
            $erros[] = 'As fotografias ainda não se podem guardar neste site (falta a atualização da base de dados).';
            break;
        }
    }
    return [$guardadas, $erros];
}

/** Reduz para 1600 px no lado maior e grava em JPEG (corrige a rotação das fotos do telemóvel). Devolve o nome ou ''. */
function percursoReduzirFoto(string $tmp, int $tipo, string $destinoSemExt): string
{
    if (!function_exists('imagecreatetruecolor')) return '';
    $img = null;
    if ($tipo === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) $img = @imagecreatefromjpeg($tmp);
    elseif ($tipo === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) $img = @imagecreatefrompng($tmp);
    elseif ($tipo === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($tmp);
    if (!$img) return '';
    if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($tmp)['Orientation'] ?? 1);
        $rodar = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rodar) { $r = imagerotate($img, $rodar, 0); if ($r) { imagedestroy($img); $img = $r; } }
    }
    $w = imagesx($img); $h = imagesy($img); $max = 1600;
    $f = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $f)); $nh = max(1, (int)round($h * $f));
    $nova = imagecreatetruecolor($nw, $nh);
    imagefill($nova, 0, 0, imagecolorallocate($nova, 255, 255, 255));   // PNG transparente → fundo branco
    imagecopyresampled($nova, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = imagejpeg($nova, $destinoSemExt . '.jpg', 82);
    imagedestroy($img); imagedestroy($nova);
    return $ok ? basename($destinoSemExt) . '.jpg' : '';
}

/** Fotografias dos passos: [timeline_id => [url, …]]. */
function percursoFotos(PDO $pdo, int $pedidoId): array
{
    $fotos = [];
    try {
        $st = $pdo->prepare("SELECT timeline_id, ficheiro FROM pedido_timeline_fotos WHERE pedido_id = ? ORDER BY id ASC");
        $st->execute([$pedidoId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) $fotos[(int)$f['timeline_id']][] = '/uploads/ocorrencias/' . $f['ficheiro'];
    } catch (Throwable $e) {}
    return $fotos;
}
