<?php
/**
 * Textos do site editáveis no backoffice (12/10/2026) — CORE.
 *
 * Os títulos, etiquetas, introduções e botões das páginas deixam de estar escritos no código:
 * cada um tem uma CHAVE (ex.: 'noticias.hero.titulo') registada em includes/textos-registo.php
 * com o texto original. O backoffice (blocos «Texto do topo…» nas páginas de cada secção, admin/includes/textos_bloco.php) guarda só os que forem alterados, na tabela
 * textos_site; sem alteração (ou campo vazio) vale o original — o site fica igual até alguém mudar.
 *
 * Uso nas páginas:
 *   <?= te('noticias.hero.titulo') ?>   → texto escapado (e com quebras de linha se for um texto longo)
 *   tx('noticias.hero.titulo')          → texto simples, para usar em PHP (ex.: atributos, <title>)
 * Marcadores aceites em qualquer texto: {nome_site}, {freguesia} (nome curto), {municipio}, {ano}.
 *
 * Para tornar um texto novo editável: acrescentá-lo ao registo (com o texto original) e usar te().
 */
require_once __DIR__ . '/config.php';

function textosRegisto(): array
{
    static $r = null;
    if ($r === null) $r = require __DIR__ . '/textos-registo.php';
    return $r;
}

/** [chave => [rótulo, texto original, tipo ('linha'|'texto'), página, secção]] */
function textosCampos(): array
{
    static $c = null;
    if ($c === null) {
        $c = [];
        foreach (textosRegisto() as $pag => $def) {
            foreach ($def['campos'] as $k => $campo) {
                $c[$k] = [$campo[0], $campo[1], $campo[2] ?? 'linha', $pag, explode('.', $k)[1] ?? ''];
            }
        }
    }
    return $c;
}

function textosGuardados(): array
{
    static $g = null;
    if ($g === null) {
        global $pdo;
        $g = [];
        try { $g = $pdo->query("SELECT chave, valor FROM textos_site")->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Throwable $e) {}
    }
    return $g;
}

function textosMarcadores(string $v): string
{
    if (strpos($v, '{') === false) return $v;
    // freguesia com o nome do concelho (sede): «Sousel · Sousel» → «Sousel»; «…, concelho de Sousel» → «…, sede do concelho»
    if (freguesiaSedeConcelho()) {
        $v = preg_replace('/\{freguesia\}\s*(?:·|,|-|–|—)\s*\{municipio\}/u', '{freguesia}', $v);
        $v = preg_replace('/,\s*(?:no\s+)?concelho\s+de\s+\{municipio\}/u', ', sede do concelho', $v);
    }
    // «de {freguesia}» / «de {municipio}» com a preposição certa («da Golegã», «do Torrão»)
    $v = preg_replace('/\bde (\{freguesia\})/u', preposicaoFreguesia() . ' $1', $v);
    $v = preg_replace('/\bde (\{municipio\})/u', preposicaoConcelho() . ' $1', $v);
    return strtr($v, [
        '{nome_site}' => siteConfig('nome_site', 'Junta de Freguesia'),
        '{municipio}' => siteConfig('municipio', ''),
        '{freguesia}' => nomeFreguesia(),
        '{ano}'       => date('Y'),
    ]);
}

/** Texto (simples, não escapado) da chave: o alterado no backoffice ou o original */
function tx(string $chave): string
{
    $g = textosGuardados();
    $v = (isset($g[$chave]) && trim($g[$chave]) !== '') ? $g[$chave] : (textosCampos()[$chave][1] ?? '');
    return textosMarcadores($v);
}

/** Texto pronto a escrever no HTML (escapado; textos longos mantêm as quebras de linha) */
function te(string $chave): string
{
    $v = htmlspecialchars(tx($chave), ENT_QUOTES, 'UTF-8');
    return (textosCampos()[$chave][2] ?? 'linha') === 'texto' ? nl2br($v, false) : $v;
}
