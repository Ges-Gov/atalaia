<?php
/*
 * Partilha de notícias no Facebook a partir do backoffice.
 *
 * O Facebook não aceita texto pré-preenchido na publicação (só o endereço — a pré-visualização com título, resumo e
 * imagem vem das metatags og:* da notícia). Por isso o botão «Partilhar» copia também o texto da notícia para a área
 * de transferência e o admin-ui.js avisa para o colar (Ctrl+V) na janela do Facebook. (27/10/2026)
 */

/** Endereço público de uma notícia */
function urlNoticiaPublica(int $id): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/noticia.php?id=' . $id;
}

/** Endereço do Facebook para partilhar a notícia */
function urlPartilhaFacebookNoticia(int $id): string
{
    return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(urlNoticiaPublica($id));
}

/** Texto para colar na publicação: título, o início da notícia e o endereço */
function textoPartilhaNoticia(array $noticia, int $max = 600): string
{
    $texto = trim(html_entity_decode(strip_tags((string)($noticia['descricao'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $texto = preg_replace("/[ \t]+/u", ' ', $texto);
    $texto = preg_replace("/\n{3,}/u", "\n\n", $texto);
    if (mb_strlen($texto) > $max) {
        $corte = mb_substr($texto, 0, $max);
        $espaco = mb_strrpos($corte, ' ');
        $texto = rtrim(mb_substr($corte, 0, $espaco ?: $max), " ,;:.-–—") . '…';
    }
    $partes = [trim((string)($noticia['titulo'] ?? ''))];
    if ($texto !== '') $partes[] = $texto;
    $partes[] = 'Leia mais: ' . urlNoticiaPublica((int)($noticia['id'] ?? 0));
    return implode("\n\n", $partes);
}
