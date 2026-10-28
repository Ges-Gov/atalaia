<?php
/*
 * Plataforma de sites de Juntas de Freguesia — © 2026 GesGov. Todos os direitos reservados.
 * Licença GG-ATALAIA-005E52FF — uso exclusivo de: Freguesias de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
 * Proibida a cópia, modificação, redistribuição ou venda sem autorização escrita da GesGov.
 */
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
        'acento-escuro'    => '#856611',
        'footer-bg'        => '#F2EAD6',
        'footer-texto'     => '#3a3f47',
        'kicker-img-texto' => '#FCE9B8',
        // véu escuro por cima das fotos do slider (à esquerda / à direita) e altura do slider no computador
        'hero-veu'          => '.62',
        'hero-veu-fim'      => '.12',
        'hero-altura'       => '90vh',
        // véu escuro por cima das imagens de fundo dos separadores das páginas (topo / fundo)
        'separador-veu'     => '.62',
        'separador-veu-fim' => '.78',
    ];

    $css = '';
    foreach ($tokens as $nome => $default) {
        // As chaves na BD usam underscore; as variáveis CSS usam hífen.
        $valor = temaConfig(str_replace('-', '_', $nome), $default);
        $css .= '            --tema-' . $nome . ': ' . htmlspecialchars($valor) . ";\n";
    }
    foreach (temaPaletaMarca() as $var => $v) $css .= '            ' . $var . ': ' . $v . ";\n";
    // fundo da etiqueta sobre as fotografias do topo (tema_config «hero_kicker_fundo»: cor ou «transparente»)
    $kf = temaConfig('hero_kicker_fundo', '');
    if ($kf !== '') $css .= '            --hero-kicker-fundo: ' . htmlspecialchars($kf === 'transparente' ? 'transparent' : $kf) . ";\n";
    if ($kf === 'transparente') $css .= "            --hero-kicker-sombra: 0 1px 8px rgba(0, 0, 0, .55);\n";   // legível sobre a foto

    return $css;
}

/**
 * Paleta «de destaque» da marca (14/10/2026). O CSS tinha o dourado do modelo escrito à mão em
 * muitos sítios (etiquetas, bordas, brilhos, fundos de avisos); agora usa var(--ouro…, dourado) —
 * o dourado fica como valor de reserva. Se a cor secundária da freguesia NÃO for dourada/amarela
 * (ex.: o verde do Ciborro), estas variáveis passam a derivar das cores da marca e o dourado
 * desaparece; se for dourada (ex.: Sousel), não se emite nada e fica tudo exatamente igual.
 */
function temaPaletaMarca(): array
{
    static $p = null;
    if ($p !== null) return $p;
    $hex = function_exists('siteConfig') ? (string)siteConfig('cor_secundaria', '') : '';
    $pri = function_exists('siteConfig') ? (string)siteConfig('cor_principal', '') : '';
    if (!preg_match('/^#[0-9a-f]{6}$/i', $hex)) return $p = [];
    $rgb = [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    [$h, $s] = temaHsl($rgb);
    if ($h >= 32 && $h <= 60 && $s > 0.35) return $p = [];          // é dourada: fica o dourado original
    $mix = fn(array $a, array $b, float $t) => sprintf('#%02X%02X%02X', ...array_map(fn($x, $y) => (int)round($x + ($y - $x) * $t), $a, $b));
    $priRgb = preg_match('/^#[0-9a-f]{6}$/i', $pri) ? [hexdec(substr($pri, 1, 2)), hexdec(substr($pri, 3, 2)), hexdec(substr($pri, 5, 2))] : $rgb;
    return $p = [
        '--ouro'         => strtoupper($hex),
        '--ouro-rgb'     => implode(' ', $rgb),
        '--ouro-claro'   => $mix($rgb, [255, 255, 255], .55),   // textos claros sobre fotografias/fundos escuros
        '--ouro-vivo'    => $mix($rgb, [255, 255, 255], .3),    // destaques no tema escuro
        '--ouro-fundo'   => $mix($rgb, [255, 255, 255], .86),   // fundos suaves de etiquetas/avisos
        '--ouro-fundo-2' => $mix($rgb, [255, 255, 255], .7),
        '--ouro-texto'   => $mix($priRgb, [0, 0, 0], .25),      // texto escuro sobre os fundos suaves
    ];
}
function temaHsl(array $rgb): array
{
    [$r, $g, $b] = array_map(fn($v) => $v / 255, $rgb);
    $mx = max($r, $g, $b); $mn = min($r, $g, $b); $l = ($mx + $mn) / 2;
    if ($mx == $mn) return [0, 0, $l];
    $d = $mx - $mn; $s = $l > .5 ? $d / (2 - $mx - $mn) : $d / ($mx + $mn);
    $h = $mx == $r ? ($g - $b) / $d + ($g < $b ? 6 : 0) : ($mx == $g ? ($b - $r) / $d + 2 : ($r - $g) / $d + 4);
    return [$h * 60, $s, $l];
}

/**
 * Tokens do modo escuro, emitidos em :root[data-tema="escuro"].
 * Tal como os claros, cada valor pode ser substituído por uma linha em `tema_config`
 * (chave à esquerda); os defaults são uma paleta neutra quente que serve qualquer brasão.
 * O assets/css/tema-escuro.css (gerado) usa as --escuro-*; os --tema-* são redefinidos aqui
 * para o footer/hero (core) acompanharem sem mais nada.
 */
function temaVariaveisEscuroCss()
{
    $tokens = [
        // chave tema_config      => [variável CSS, default]
        'escuro_fundo'            => ['escuro-fundo',          '#14171b'],
        'escuro_superficie'       => ['escuro-superficie',     '#1c2025'],
        'escuro_superficie_2'     => ['escuro-superficie-2',   '#181b20'],
        'escuro_superficie_3'     => ['escuro-superficie-3',   '#262b31'],
        'escuro_borda'            => ['escuro-borda',          '#353c45'],
        'escuro_texto'            => ['escuro-texto',          '#ebe8e2'],
        'escuro_texto_2'          => ['escuro-texto-2',        '#b8bec6'],
        'fundo_escuro'            => ['tema-fundo',            'var(--escuro-fundo)'],
        'hero_1_escuro'           => ['tema-hero-1',           '#1b1f24'],
        'hero_2_escuro'           => ['tema-hero-2',           '#23282e'],
        'hero_texto_escuro'       => ['tema-hero-texto',       '#f2efe8'],
        'topbar_texto_escuro'     => ['tema-topbar-texto',     '#14171b'],
        'acento_escuro_escuro'    => ['tema-acento-escuro',    temaPaletaMarca()['--ouro-vivo'] ?? '#e3c463'],
        'footer_bg_escuro'        => ['tema-footer-bg',        '#101316'],
        'footer_texto_escuro'     => ['tema-footer-texto',     '#c9cdd3'],
    ];

    $css = "            color-scheme: dark;\n";
    foreach ($tokens as $chave => [$var, $default]) {
        $css .= '            --' . $var . ': ' . htmlspecialchars(temaConfig($chave, $default)) . ";\n";
    }

    return $css;
}
