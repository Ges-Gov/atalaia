<?php
/**
 * Membros (Executivo e Assembleia) — CORE: ficheiro idêntico em todos os sites.
 *
 * Antes, a inserção de membros do Executivo e da Assembleia estava feita de duas
 * maneiras diferentes:
 *
 *   Executivo   -> gravava a foto em  assets/img/
 *   Assembleia  -> gravava a foto em  uploads/assembleia-composicao/
 *
 * ...com validações, nomes de ficheiro e mensagens de erro diferentes. Era isso
 * que fazia a experiência de inserção ser diferente nos dois sítios, e era também
 * o que fazia as fotos dos membros da Assembleia falharem em servidores onde a
 * pasta uploads/ não tem permissões de escrita (enquanto as do Executivo passavam).
 *
 * A partir daqui os dois usam este mesmo helper e a mesma pasta.
 *
 * Compatibilidade: as fotos ANTIGAS continuam a aparecer. O fotoMembroUrl() procura
 * o ficheiro na pasta nova e, se não estiver lá, nas pastas antigas — por isso não
 * é preciso mover ficheiros nem mexer na base de dados.
 */

require_once __DIR__ . "/galeria.php"; // reutiliza erroUploadTexto()

/** Pasta (relativa à raiz do site) onde passam a ficar as fotos dos membros. */
const MEMBROS_DIR = "/assets/img/membros/";

/** Pastas antigas, mantidas só para as fotos que já lá estão. */
const MEMBROS_DIRS_LEGADO = [
    "/uploads/assembleia-composicao/", // Assembleia
    "/assets/img/",                    // Executivo
];

/**
 * Devolve o URL da foto de um membro, esteja ela na pasta nova ou numa das antigas.
 * Devolve '' se não houver foto (ou se o ficheiro não existir em lado nenhum).
 */
function fotoMembroUrl(?string $foto): string
{
    $foto = trim((string)$foto);
    if ($foto === '') {
        return '';
    }

    // Se já vier um caminho completo (dados antigos), respeita-o.
    if (str_starts_with($foto, '/')) {
        return $foto;
    }

    $nome = basename($foto);
    $raiz = __DIR__ . "/..";

    foreach (array_merge([MEMBROS_DIR], MEMBROS_DIRS_LEGADO) as $dir) {
        if (is_file($raiz . $dir . $nome)) {
            return $dir . $nome;
        }
    }

    // Não encontrado em disco: aponta para a pasta nova (mostra imagem partida,
    // o que é preferível a esconder silenciosamente que o ficheiro desapareceu).
    return MEMBROS_DIR . $nome;
}

/**
 * Guarda a foto enviada de um membro. Devolve [nomeDoFicheiro, erro].
 *
 * - Se não foi enviada foto nova, devolve a atual sem tocar em nada.
 * - Se a escrita falhar, devolve erro EXPLÍCITO (permissões, tamanho, etc.) em vez
 *   do antigo "Erro ao enviar fotografia", que não dizia nada a quem o via.
 * - Só apaga a foto antiga DEPOIS de a nova estar mesmo gravada.
 */
function guardarFotoMembro(array $ficheiro, string $fotoAtual = ''): array
{
    if (empty($ficheiro['name'])) {
        return [$fotoAtual, ''];
    }

    if (($ficheiro['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [$fotoAtual, "Não foi possível enviar a fotografia: " . erroUploadTexto((int)$ficheiro['error'])];
    }

    $ext = strtolower(pathinfo($ficheiro['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return [$fotoAtual, "Formato de imagem inválido. Use JPG, PNG ou WEBP."];
    }

    $destinoAbs = __DIR__ . "/.." . MEMBROS_DIR;

    if (!is_dir($destinoAbs) && !@mkdir($destinoAbs, 0775, true) && !is_dir($destinoAbs)) {
        return [$fotoAtual, "Não foi possível criar a pasta " . MEMBROS_DIR . " no servidor (permissões)."];
    }

    $novo = "membro_" . date('YmdHis') . "_" . bin2hex(random_bytes(4)) . "." . $ext;

    if (!move_uploaded_file($ficheiro['tmp_name'], $destinoAbs . $novo)) {
        return [
            $fotoAtual,
            "Não foi possível gravar a fotografia no servidor. Verifique as permissões de escrita da pasta " . MEMBROS_DIR . "."
        ];
    }

    // A nova está gravada: só agora se apaga a antiga (e apenas se estiver na pasta nova).
    if ($fotoAtual !== '') {
        $antiga = $destinoAbs . basename($fotoAtual);
        if (is_file($antiga)) {
            @unlink($antiga);
        }
    }

    return [$novo, ''];
}
