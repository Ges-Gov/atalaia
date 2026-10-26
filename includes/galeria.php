<?php
/**
 * Galeria por álbuns (CORE: ficheiro idêntico em todos os sites).
 *
 * As fotos de um evento ou de um ponto de interesse NÃO são duplicadas para a
 * galeria: elas vivem na galeria, dentro de um álbum ligado à sua origem
 * (galeria_albuns.origem + origem_id). A página do evento/ponto lê-as daqui.
 * Assim há uma única fonte de verdade — não há cópias para manter sincronizadas.
 *
 * Ver migrations/005_galeria_albuns.sql.
 */

/** Pasta onde ficam os ficheiros NOVOS da galeria (relativa à raiz do site). */
const GALERIA_DIR = "/assets/img/galeria/";

/** Pasta antiga: as imagens que já existiam na galeria da homepage vivem aqui. */
const GALERIA_DIR_LEGADO = "/assets/img/";

/**
 * Devolve o URL de uma imagem da galeria, esteja ela na pasta nova ou na antiga.
 *
 * É preciso porque as imagens que vieram da galeria antiga (tabela homepage_galeria,
 * migrada em 005) continuam gravadas em assets/img/, e não em assets/img/galeria/.
 * Sem isto, essas imagens apareceriam partidas: a linha existe na base de dados mas
 * o ficheiro não está na pasta nova.
 *
 * Não é preciso mover ficheiros nem mexer na base de dados — procura-se nas duas.
 */
function imagemGaleriaUrl(?string $ficheiro): string
{
    $ficheiro = trim((string)$ficheiro);
    if ($ficheiro === '') {
        return '';
    }

    $nome = basename($ficheiro);
    $raiz = __DIR__ . "/..";

    foreach ([GALERIA_DIR, GALERIA_DIR_LEGADO] as $dir) {
        if (is_file($raiz . $dir . $nome)) {
            return $dir . $nome;
        }
    }

    // Não encontrado: aponta para a pasta nova (mostra imagem partida, o que é
    // preferível a esconder em silêncio que o ficheiro desapareceu).
    return GALERIA_DIR . $nome;
}

/**
 * Devolve o id do álbum de uma origem ('evento' ou 'ponto'), criando-o se ainda
 * não existir. Se já existir, apenas atualiza o nome (para o álbum acompanhar o
 * título do evento / nome do ponto quando este é editado).
 *
 * É este get-or-create que implementa o "cria automaticamente um álbum ou associa
 * a um já existente".
 */
function albumDaOrigem(PDO $pdo, string $origem, int $origemId, string $nome): int
{
    $stmt = $pdo->prepare("SELECT id FROM galeria_albuns WHERE origem = ? AND origem_id = ?");
    $stmt->execute([$origem, $origemId]);
    $id = $stmt->fetchColumn();

    if ($id) {
        // O álbum segue o nome da origem (ex.: o ponto foi renomeado).
        $pdo->prepare("UPDATE galeria_albuns SET nome = ? WHERE id = ?")
            ->execute([$nome, (int)$id]);
        return (int)$id;
    }

    $pdo->prepare("
        INSERT INTO galeria_albuns (nome, origem, origem_id, ativo)
        VALUES (?, ?, ?, 1)
    ")->execute([$nome, $origem, $origemId]);

    return (int)$pdo->lastInsertId();
}

/**
 * Imagens de um álbum, por ordem.
 */
function imagensDoAlbum(PDO $pdo, int $albumId, bool $apenasAtivas = true): array
{
    $sql = "SELECT * FROM galeria_imagens WHERE album_id = ?";
    if ($apenasAtivas) {
        $sql .= " AND ativo = 1";
    }
    $sql .= " ORDER BY ordem ASC, id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$albumId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Imagens associadas a um evento/ponto (através do seu álbum). Devolve [] se a
 * origem ainda não tiver álbum.
 */
function imagensDaOrigem(PDO $pdo, string $origem, int $origemId): array
{
    $stmt = $pdo->prepare("SELECT id FROM galeria_albuns WHERE origem = ? AND origem_id = ?");
    $stmt->execute([$origem, $origemId]);
    $albumId = $stmt->fetchColumn();

    return $albumId ? imagensDoAlbum($pdo, (int)$albumId) : [];
}

/**
 * Tira das fotos de um álbum as que repetem a foto principal (ou outra já incluída). Muitas vezes a foto
 * principal é também a primeira do álbum, às vezes copiada com outro nome ou noutra pasta — compara-se o
 * conteúdo (tamanho e, se igual, md5), como na apresentação da página inicial. $principal e $url($ficheiro)
 * são caminhos web ("/assets/img/…"). Sem isto, o carrossel das fichas mostrava a mesma foto duas vezes.
 */
function fotosSemRepetidas(array $fotos, string $principal = '', ?callable $url = null): array
{
    $url = $url ?? 'imagemGaleriaUrl';
    $chave = function (string $web): string {
        $caminho = __DIR__ . '/..' . (string)parse_url(html_entity_decode($web), PHP_URL_PATH);
        return is_file($caminho) ? filesize($caminho) . '-' . md5_file($caminho) : $web;
    };
    // também conta como repetida a foto com o mesmo nome de ficheiro (a mesma foto, recomprimida noutra pasta)
    $nome = fn(string $web): string => 'n:' . strtolower(basename((string)parse_url(html_entity_decode($web), PHP_URL_PATH)));
    $vistas = $principal !== '' ? [$chave($principal) => true, $nome($principal) => true] : [];
    $saida = [];
    foreach ($fotos as $f) {
        $web = $url($f['ficheiro']);
        $k = $chave($web); $n = $nome($web);
        if (isset($vistas[$k]) || isset($vistas[$n])) continue;
        $vistas[$k] = $vistas[$n] = true;
        $saida[] = $f;
    }
    return $saida;
}

/**
 * Recebe o $_FILES de um input múltiplo (name="fotos[]") e grava as imagens
 * válidas no álbum indicado. Devolve [nGravadas, arrayDeErros].
 *
 * Verifica sempre o resultado do move_uploaded_file(): se a escrita falhar
 * (pasta sem permissões no servidor, por exemplo) NÃO insere a linha na BD —
 * caso contrário ficaria um registo a apontar para um ficheiro inexistente.
 */
function guardarImagensNoAlbum(PDO $pdo, int $albumId, array $ficheiros): array
{
    $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $destinoAbs = __DIR__ . "/.." . GALERIA_DIR;
    $erros      = [];
    $gravadas   = 0;

    if (!is_dir($destinoAbs) && !@mkdir($destinoAbs, 0775, true) && !is_dir($destinoAbs)) {
        return [0, ["Não foi possível criar a pasta " . GALERIA_DIR . " no servidor (permissões)."]];
    }

    // Continua a numeração a partir da última imagem do álbum.
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(ordem), 0) FROM galeria_imagens WHERE album_id = ?");
    $stmt->execute([$albumId]);
    $ordem = (int)$stmt->fetchColumn();

    $inserir = $pdo->prepare("
        INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
        VALUES (?, ?, ?, ?, 1)
    ");

    $total = count($ficheiros['name'] ?? []);

    for ($i = 0; $i < $total; $i++) {
        $nomeOriginal = $ficheiros['name'][$i] ?? '';
        if ($nomeOriginal === '') {
            continue;
        }

        if (($ficheiros['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $erros[] = htmlspecialchars($nomeOriginal) . ": " . erroUploadTexto((int)$ficheiros['error'][$i]);
            continue;
        }

        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        if (!in_array($ext, $permitidas, true)) {
            $erros[] = htmlspecialchars($nomeOriginal) . ": formato não permitido (use JPG, PNG, WEBP ou GIF).";
            continue;
        }

        $novoNome = "galeria_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

        if (!move_uploaded_file($ficheiros['tmp_name'][$i], $destinoAbs . $novoNome)) {
            $erros[] = htmlspecialchars($nomeOriginal) . ": não foi possível gravar no servidor (permissões da pasta " . GALERIA_DIR . ").";
            continue;
        }

        $ordem++;
        $inserir->execute([$albumId, $novoNome, pathinfo($nomeOriginal, PATHINFO_FILENAME), $ordem]);
        $gravadas++;
    }

    // Se o álbum ainda não tem capa, usa a primeira imagem.
    if ($gravadas > 0) {
        $pdo->prepare("
            UPDATE galeria_albuns a
            SET a.capa = (
                SELECT gi.ficheiro FROM galeria_imagens gi
                WHERE gi.album_id = a.id ORDER BY gi.ordem ASC, gi.id ASC LIMIT 1
            )
            WHERE a.id = ? AND (a.capa IS NULL OR a.capa = '')
        ")->execute([$albumId]);
    }

    return [$gravadas, $erros];
}

/**
 * Apaga uma imagem do álbum (linha + ficheiro em disco).
 */
function apagarImagemDaGaleria(PDO $pdo, int $imagemId): void
{
    $stmt = $pdo->prepare("SELECT album_id, ficheiro FROM galeria_imagens WHERE id = ?");
    $stmt->execute([$imagemId]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$img) {
        return;
    }

    $caminho = __DIR__ . "/.." . GALERIA_DIR . $img['ficheiro'];
    if (is_file($caminho)) {
        @unlink($caminho);
    }

    $pdo->prepare("DELETE FROM galeria_imagens WHERE id = ?")->execute([$imagemId]);

    // Se a imagem apagada era a capa, promove a seguinte.
    $pdo->prepare("
        UPDATE galeria_albuns a
        SET a.capa = (
            SELECT gi.ficheiro FROM galeria_imagens gi
            WHERE gi.album_id = a.id ORDER BY gi.ordem ASC, gi.id ASC LIMIT 1
        )
        WHERE a.id = ? AND a.capa = ?
    ")->execute([(int)$img['album_id'], $img['ficheiro']]);
}

/**
 * Guarda UMA imagem enviada num formulário do backoffice, em assets/img/.
 * Devolve [nomeDoFicheiro, erro].
 *
 * Existe porque três páginas (adicionar_slide, editar_slide e homepage_destaque)
 * gravavam o ficheiro com o NOME ORIGINAL que vinha do browser e sem validar a
 * extensão. Isso trazia três problemas a sério:
 *
 *   1. Colisão: duas pessoas a enviar "foto.jpg" — a segunda substituía a imagem
 *      do slide da primeira, sem aviso nenhum.
 *   2. Segurança: nada impedia enviar "x.php". O ficheiro ficava em assets/img/,
 *      onde o servidor executa PHP — ou seja, execução de código no servidor.
 *   3. O resultado do move_uploaded_file() não era verificado: se a escrita
 *      falhasse (permissões), ficava na BD um nome de ficheiro que não existia.
 *
 * Aqui: extensão validada por lista, nome único gerado por nós, escrita conferida.
 */
function guardarImagemSimples(array $ficheiro, string $prefixo, string $atual = ''): array
{
    if (empty($ficheiro['name'])) {
        return [$atual, ''];
    }

    if (($ficheiro['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [$atual, "Não foi possível enviar a imagem: " . erroUploadTexto((int)$ficheiro['error'])];
    }

    $ext = strtolower(pathinfo($ficheiro['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return [$atual, "Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF."];
    }

    $destinoAbs = __DIR__ . "/../assets/img/";
    if (!is_dir($destinoAbs) && !@mkdir($destinoAbs, 0775, true) && !is_dir($destinoAbs)) {
        return [$atual, "Não foi possível criar a pasta assets/img no servidor (permissões)."];
    }

    // Nome gerado por nós: nunca o que vem do browser.
    $novo = $prefixo . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

    if (!move_uploaded_file($ficheiro['tmp_name'], $destinoAbs . $novo)) {
        return [$atual, "Não foi possível gravar a imagem no servidor. Verifique as permissões da pasta assets/img."];
    }

    // Só apaga a antiga depois de a nova estar mesmo gravada.
    if ($atual !== '' && is_file($destinoAbs . basename($atual))) {
        @unlink($destinoAbs . basename($atual));
    }

    return [$novo, ''];
}

/** Traduz o código de erro do PHP para uma mensagem que se percebe. */
function erroUploadTexto(int $codigo): string
{
    switch ($codigo) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "o ficheiro é maior do que o limite do servidor (upload_max_filesize).";
        case UPLOAD_ERR_PARTIAL:
            return "o envio foi interrompido a meio.";
        case UPLOAD_ERR_NO_FILE:
            return "não foi enviado nenhum ficheiro.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "o servidor não tem pasta temporária configurada.";
        case UPLOAD_ERR_CANT_WRITE:
            return "o servidor não conseguiu escrever em disco (permissões).";
        case UPLOAD_ERR_EXTENSION:
            return "o envio foi bloqueado por uma extensão do PHP.";
        default:
            return "erro desconhecido no envio (código $codigo).";
    }
}

/**
 * Miniatura de uma foto do site (ex.: "/assets/img/galeria/x.jpg") para tiras de miniaturas.
 * Gerada uma vez com o GD em assets/img/miniaturas/ (JPEG, $largura px) e reaproveitada
 * enquanto o original não mudar. Sem GD ou se falhar, devolve a própria foto.
 */
function miniaturaImagem(string $src, int $largura = 240): string
{
    $raiz = realpath(__DIR__ . '/..');
    $orig = $raiz . $src;
    if (!function_exists('imagecreatetruecolor') || !is_file($orig)) return $src;
    $nome = substr(md5($src), 0, 12) . '-' . $largura . '.jpg';
    $dest = $raiz . '/assets/img/miniaturas/' . $nome;
    $url = '/assets/img/miniaturas/' . $nome;
    if (is_file($dest) && filemtime($dest) >= filemtime($orig)) return $url;
    $info = @getimagesize($orig);
    if (!$info || $info[0] < 1) return $src;
    if ($info[0] <= $largura) return $src;   // a foto já é mais pequena: nunca aumentar
    $img = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($orig),
        IMAGETYPE_PNG  => @imagecreatefrompng($orig),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($orig) : false,
        default        => false,
    };
    if (!$img) return $src;
    $alt = max(1, (int)round($info[1] * $largura / $info[0]));
    $mini = imagecreatetruecolor($largura, $alt);
    imagefill($mini, 0, 0, imagecolorallocate($mini, 255, 255, 255));
    imagecopyresampled($mini, $img, 0, 0, 0, 0, $largura, $alt, $info[0], $info[1]);
    if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
    $ok = @imagejpeg($mini, $dest, 78);
    imagedestroy($img);
    imagedestroy($mini);
    return $ok ? $url : $src;
}
