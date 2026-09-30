<?php
/**
 * Tema — tokens de cor da freguesia (CORE: ficheiro idêntico em todos os sites).
 *
 * As cores de cada freguesia vivem na tabela `tema_config` (ver
 * migrations/001_tema_config.sql), não no código. O includes/header.php
 * emite-as como variáveis CSS no :root e o includes/footer.php (core)
 * consome-as. Assim o footer é igual em todos os sites e as correções
 * podem ser portadas sem trocar a marca de cada um.
 */

/**
 * Devolve o valor de um token de tema, ou $default se não existir.
 * Os valores são lidos uma única vez por pedido.
 */
function temaConfig($chave, $default = '')
{
    static $tokens = null;

    if ($tokens === null) {
        global $pdo;
        $tokens = [];
        try {
            $linhas = $pdo->query("SELECT chave, valor FROM tema_config")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($linhas as $l) {
                $tokens[$l['chave']] = $l['valor'];
            }
        } catch (Exception $e) {
            // Tabela ainda não migrada: cai nos defaults abaixo.
            $tokens = [];
        }
    }

    return (isset($tokens[$chave]) && $tokens[$chave] !== '') ? $tokens[$chave] : $default;
}

/**
 * Emite o bloco de variáveis CSS do tema para o :root.
 * Os defaults reproduzem o tema claro/dourado original, para o site não
 * ficar sem cores caso a migração ainda não tenha corrido.
 */
function temaVariaveisCss()
{
    $tokens = [
        'fundo'            => '#FAF6EC',
        'topbar-bg'        => 'var(--cor-secundaria)',
        'topbar-texto'     => '#242A30',
        'topbar-borda'     => 'transparent',
        'hero-1'           => '#FCF8EF',
        'hero-2'           => '#EFE2C2',
        'hero-texto'       => '#242A30',
        'acento'           => 'var(--cor-secundaria)',
        'acento-escuro'    => '#755E00',
        'footer-bg'        => '#F2EAD6',
        'footer-texto'     => '#3a3f47',
        'kicker-img-texto' => '#F0D060',
    ];

    $css = '';
    foreach ($tokens as $nome => $default) {
        // As chaves na BD usam underscore; as variáveis CSS usam hífen.
        $valor = temaConfig(str_replace('-', '_', $nome), $default);
        $css .= '            --tema-' . $nome . ': ' . htmlspecialchars($valor) . ";\n";
    }

    return $css;
}
