<?php
/*
 * 19/11/2026 (acessibilidade, CORE): texto simples escrito no backoffice → HTML com parágrafos.
 *
 * Antes as notícias e os eventos saíam com nl2br(): cada linha em branco virava <br><br> e um texto longo
 * ficava um só bloco com dezenas de <br> seguidos. O AccessMonitor marca isso (br_01, «<br> usado para
 * separar parágrafos ou fazer listas») e o leitor de ecrã não consegue saltar de parágrafo em parágrafo.
 *
 * Regras:
 *  - uma ou mais linhas em branco separam parágrafos (<p>);
 *  - dentro de um parágrafo, até 3 mudanças de linha ficam com <br> (morada, assinatura…);
 *  - com mais do que 3, cada linha fica num <span class="linha"> (display:block no style.css), que se vê igual
 *    e deixa de ser uma «lista feita com <br>».
 * Com $cadaLinha = true, cada linha é um parágrafo (os eventos: o CSS antigo, white-space:pre-line + nl2br,
 * mostrava cada mudança de linha como uma linha em branco, e assim fica igual).
 * O texto é sempre escapado (htmlspecialchars); não aceita HTML.
 */
function textoParagrafos(?string $texto, bool $cadaLinha = false): string
{
    $texto = str_replace(["\r\n", "\r"], "\n", (string)$texto);
    $blocos = preg_split($cadaLinha ? '/\n+/' : '/\n[ \t]*\n+/', trim($texto)) ?: [];
    $html = '';
    foreach ($blocos as $bloco) {
        $linhas = array_values(array_filter(array_map('trim', explode("\n", $bloco)), fn($l) => $l !== ''));
        if (!$linhas) {
            continue;
        }
        $linhas = array_map(fn($l) => htmlspecialchars($l, ENT_QUOTES, 'UTF-8'), $linhas);
        if (count($linhas) - 1 <= 3) {
            $html .= '<p>' . implode('<br>', $linhas) . '</p>';
        } else {
            $html .= '<p><span class="linha">' . implode('</span><span class="linha">', $linhas) . '</span></p>';
        }
    }
    return $html;
}
