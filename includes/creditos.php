<?php
/**
 * Créditos das imagens de terceiros — CORE: ficheiro idêntico em todos os sites.
 *
 * Junta, para a página /creditos.php, o autor/titular de cada imagem de terceiros:
 *   1. a tabela creditos_imagens (migração 086), onde se registam as que não têm outro sítio;
 *   2. o crédito da foto de cada bloco da Memória da Freguesia (freguesia_memoria.imagem_credito);
 *   3. as fotos da galeria com o título «Foto: autor / fonte (licença)»;
 *   4. as fotos de banco de imagens dos cartões (assets/img/cartoes/CREDITOS.txt).
 * Se a mesma imagem aparece em mais de um sítio, fica o primeiro (a tabela manda).
 */

require_once __DIR__ . "/galeria.php"; // imagemGaleriaUrl(), miniaturaImagem()

/** Separa «Foto: Sara Jaques / Wikimedia Commons (CC BY-SA 4.0)» em [titular, licença, fonte]. */
function creditoPartes(string $texto): array
{
    $texto = trim(preg_replace('/^\s*(Foto|Fotografia|Imagem)\s*:\s*/iu', '', $texto));
    $licenca = '';
    if (preg_match('/\s*\(([^()]*(CC|dom[íi]nio p[úu]blico|public domain|licen)[^()]*)\)\s*$/iu', $texto, $m)) {
        $licenca = trim($m[1]);
        $texto = trim(substr($texto, 0, -strlen($m[0])));
    }
    $fonte = '';
    if (($p = strrpos($texto, ' / ')) !== false) {
        $fonte = trim(substr($texto, $p + 3));
        $texto = trim(substr($texto, 0, $p));
    }
    return [$texto, $licenca, $fonte];
}

function creditosImagens(PDO $pdo): array
{
    $lista = [];
    $juntar = function (string $src, array $c) use (&$lista) {
        if ($src !== '' && trim($c['titular'] ?? '') !== '' && !isset($lista[$src])) $lista[$src] = $c + ['src' => $src];
    };
    $consulta = function (string $sql) use ($pdo): array {
        try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { return []; }   // tabela ainda não existe neste site
    };

    foreach ($consulta("SELECT * FROM creditos_imagens WHERE ativo = 1 ORDER BY onde, id") as $r) {
        $juntar('/' . ltrim($r['ficheiro'], '/'), ['onde' => (string)$r['onde'], 'titular' => $r['titular'],
            'licenca' => (string)$r['licenca'], 'fonte' => '', 'fonte_url' => (string)$r['fonte_url'], 'nota' => (string)$r['nota']]);
    }

    foreach ($consulta("SELECT titulo, imagem, imagem_credito, fonte_url FROM freguesia_memoria
                        WHERE ativo = 1 AND imagem <> '' AND imagem_credito <> '' ORDER BY secao, ordem") as $r) {
        [$titular, $licenca, $fonte] = creditoPartes($r['imagem_credito']);
        $juntar('/assets/img/' . ltrim($r['imagem'], '/'), ['onde' => 'Memória da Freguesia — ' . $r['titulo'], 'titular' => $titular,
            'licenca' => $licenca, 'fonte' => $fonte, 'fonte_url' => '', 'nota' => '']);
    }

    foreach ($consulta("SELECT i.ficheiro, i.titulo, a.nome FROM galeria_imagens i JOIN galeria_albuns a ON a.id = i.album_id
                        WHERE i.ativo = 1 AND a.ativo = 1 AND i.titulo LIKE 'Foto%:%' ORDER BY a.ordem, a.id, i.ordem, i.id") as $r) {
        [$titular, $licenca, $fonte] = creditoPartes($r['titulo']);
        $juntar(imagemGaleriaUrl($r['ficheiro']), ['onde' => 'Galeria — ' . $r['nome'], 'titular' => $titular,
            'licenca' => $licenca, 'fonte' => $fonte, 'fonte_url' => '', 'nota' => '']);
    }

    // «cartao-x.jpg   https://www.pexels.com/photo/... (Autor)»
    $txt = __DIR__ . '/../assets/img/cartoes/CREDITOS.txt';
    if (is_file($txt)) {
        foreach (file($txt, FILE_IGNORE_NEW_LINES) as $ln) {
            if (preg_match('/^(\S+\.(?:jpe?g|png|webp))\s+(https?:\/\/\S+)\s+\(([^)]+)\)/i', $ln, $m)
                && is_file(__DIR__ . '/../assets/img/cartoes/' . $m[1])) {
                $juntar('/assets/img/cartoes/' . $m[1], ['onde' => 'Cartões (imagem ilustrativa)', 'titular' => $m[3],
                    'licenca' => 'Licença Pexels', 'fonte' => 'Pexels', 'fonte_url' => $m[2], 'nota' => '']);
            }
        }
    }

    return array_values($lista);
}
