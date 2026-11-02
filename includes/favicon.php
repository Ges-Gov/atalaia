<?php
/**
 * Ícones do site (favicon) a partir do brasão (02/11/2026) — CORE.
 *
 * O Google só usa um favicon quadrado com lado múltiplo de 48 px; o brasão inteiro (ex.: 289×304) era ignorado e o
 * Google continuava a mostrar o ícone do site antigo. Gera-se uma vez, a partir do ficheiro de tema_config «favicon»
 * (ou do logótipo), em assets/img/favicon/:
 *  - <nome>-48.png e <nome>-192.png: quadrados, fundo transparente, o brasão centrado;
 *  - <nome>-180.png: para o iPhone (fundo branco, porque o iOS põe preto nas zonas transparentes);
 *  - /favicon.ico na raiz (16, 32 e 48 px), que muitos browsers e o Google pedem diretamente.
 * Volta a gerar-se sozinho se o brasão for substituído (data do ficheiro).
 */
function faviconLinks(string $fich): string
{
    if ($fich === '' || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $fich)) return '';
    $raiz = __DIR__ . '/..';
    $orig = $raiz . '/assets/img/' . $fich;
    if (!is_file($orig)) return '';
    $base = preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($fich, PATHINFO_FILENAME));
    $dir = $raiz . '/assets/img/favicon';
    $t = filemtime($orig);
    $alvos = [48 => "$base-48.png", 192 => "$base-192.png", 180 => "$base-180.png"];
    $falta = false;
    foreach ($alvos as $f) if (!is_file("$dir/$f") || filemtime("$dir/$f") < $t) $falta = true;
    $ico = $raiz . '/favicon.ico';
    if ($falta || !is_file($ico) || filemtime($ico) < $t) faviconGerar($orig, $dir, $alvos, $ico);
    $h = fn(string $f) => '/assets/img/favicon/' . rawurlencode($f) . '?v=' . (is_file("$dir/$f") ? filemtime("$dir/$f") : $t);
    if (!is_file("$dir/{$alvos[192]}")) {   // sem GD ou sem permissão de escrita: fica o brasão, como antes
        $u = '/assets/img/' . htmlspecialchars($fich);
        return "    <link rel=\"icon\" type=\"image/png\" href=\"$u\">\n    <link rel=\"apple-touch-icon\" href=\"$u\">\n";
    }
    return '    <link rel="icon" type="image/png" sizes="192x192" href="' . $h($alvos[192]) . "\">\n"
         . '    <link rel="icon" type="image/png" sizes="48x48" href="' . $h($alvos[48]) . "\">\n"
         . '    <link rel="apple-touch-icon" sizes="180x180" href="' . $h($alvos[180]) . "\">\n";
}

/** Brasão centrado num quadrado de $lado px ($fundo: null = transparente, ou [r, g, b]) */
function faviconQuadrado($im, int $lado, ?array $fundo)
{
    $w = imagesx($im); $h = imagesy($im);
    $q = imagecreatetruecolor($lado, $lado);
    imagealphablending($q, false); imagesavealpha($q, true);
    imagefill($q, 0, 0, $fundo ? imagecolorallocate($q, ...$fundo) : imagecolorallocatealpha($q, 0, 0, 0, 127));
    imagealphablending($q, true);
    $margem = $fundo ? 0.10 : 0.04;
    $escala = min(($lado * (1 - 2 * $margem)) / $w, ($lado * (1 - 2 * $margem)) / $h);
    $nw = max(1, (int)round($w * $escala)); $nh = max(1, (int)round($h * $escala));
    imagecopyresampled($q, $im, (int)(($lado - $nw) / 2), (int)(($lado - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
    return $q;
}

function faviconPng($im): string
{
    ob_start(); imagepng($im, null, 9); return (string)ob_get_clean();
}

function faviconGerar(string $orig, string $dir, array $alvos, string $ico): void
{
    if (!function_exists('imagecreatefromstring')) return;
    $im = @imagecreatefromstring((string)@file_get_contents($orig));
    if (!$im) return;
    if (!imageistruecolor($im)) imagepalettetotruecolor($im);
    imagealphablending($im, false); imagesavealpha($im, true);

    // corta as margens vazias (transparentes) à volta do brasão, para ele ocupar o ícone todo
    $w = imagesx($im); $h = imagesy($im); $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
    for ($y = 0; $y < $h; $y += 2) for ($x = 0; $x < $w; $x += 2) {
        if ((imagecolorat($im, $x, $y) >> 24 & 0x7F) < 100) { $x0 = min($x0, $x); $y0 = min($y0, $y); $x1 = max($x1, $x); $y1 = max($y1, $y); }
    }
    if ($x1 > $x0 && $y1 > $y0 && ($x1 - $x0 < $w - 4 || $y1 - $y0 < $h - 4)) {
        $c = imagecrop($im, ['x' => max(0, $x0 - 1), 'y' => max(0, $y0 - 1), 'width' => min($w, $x1 - $x0 + 3), 'height' => min($h, $y1 - $y0 + 3)]);
        if ($c) { imagedestroy($im); $im = $c; imagealphablending($im, false); imagesavealpha($im, true); }
    }

    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { imagedestroy($im); return; }
    foreach ($alvos as $lado => $f) {
        $q = faviconQuadrado($im, $lado, $lado === 180 ? [255, 255, 255] : null);
        @imagepng($q, "$dir/$f", 9); imagedestroy($q);
    }

    // favicon.ico com PNG lá dentro (16, 32 e 48 px) — formato aceite por todos os browsers atuais
    $pngs = [];
    foreach ([16, 32, 48] as $lado) { $q = faviconQuadrado($im, $lado, null); $pngs[$lado] = faviconPng($q); imagedestroy($q); }
    $cab = pack('vvv', 0, 1, count($pngs)); $dirs = ''; $dados = ''; $pos = 6 + 16 * count($pngs);
    foreach ($pngs as $lado => $png) {
        $dirs .= pack('CCCCvvVV', $lado % 256, $lado % 256, 0, 0, 1, 32, strlen($png), $pos);
        $dados .= $png; $pos += strlen($png);
    }
    @file_put_contents($ico, $cab . $dirs . $dados);
    imagedestroy($im);
}
