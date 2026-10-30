<?php
/**
 * Versão BRANCA dos logótipos (Links úteis) para o modo escuro (12/10/2026) — CORE.
 *
 * Em fundo escuro, logótipos escuros ou com fundo branco liam-se mal. Para cada logótipo gera-se
 * uma vez "<nome>-v2-branco.png" (PNG transparente, tudo a branco):
 *  - logótipo com fundo transparente → cada parte visível fica branca (mantém a transparência);
 *  - logótipo com fundo sólido (ex.: branco) → o fundo desaparece e o resto fica branco, mais ou
 *    menos opaco conforme se distingue do fundo (mantém letras finas e degradês legíveis).
 * Volta a gerar-se sozinho se o logótipo original for substituído (data do ficheiro).
 *
 * 30/10/2026: logótipos «cheios» (selos, círculos, caixas — a silhueta tapa mais de 60% da área)
 * ficavam uma mancha branca. Para esses grava-se um PNG de 1×1 px como marca e o logótipo aparece
 * sempre a cores. O sufixo passou a "-v2-branco" para as versões antigas voltarem a ser avaliadas.
 */
const LOGO_COBERTURA_MAX = 0.6;

function logoBrancoUrl(string $logo): string
{
    if ($logo === '' || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $logo)) return '';
    $dir = __DIR__ . '/../assets/img/';
    $orig = $dir . $logo;
    if (!is_file($orig)) return '';
    $rel = preg_replace('/\.[a-z]+$/i', '', $logo) . '-v2-branco.png';
    $dest = $dir . $rel;
    if (!is_file($dest) || filemtime($dest) < filemtime($orig)) {
        if (!logoGerarBranco($orig, $dest)) return '';
    }
    $tam = @getimagesize($dest);
    if (!$tam || $tam[0] <= 1) return '';   // marca de logótipo «cheio»: fica a cores
    return '/assets/img/' . $rel . '?v=' . filemtime($dest);
}

function logoGerarBranco(string $orig, string $dest): bool
{
    if (!function_exists('imagecreatefromstring')) return false;
    $im = @imagecreatefromstring((string)@file_get_contents($orig));
    if (!$im) return false;
    if (!imageistruecolor($im)) imagepalettetotruecolor($im);
    $w = imagesx($im); $h = imagesy($im);

    // fundo: média dos 4 cantos (transparente ou cor sólida)
    $cantos = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]];
    $alfaCantos = 0; $fr = $fg = $fb = 0;
    foreach ($cantos as [$x, $y]) {
        $c = imagecolorat($im, $x, $y);
        $alfaCantos += 1 - (($c >> 24) & 0x7F) / 127;
        $fr += ($c >> 16) & 0xFF; $fg += ($c >> 8) & 0xFF; $fb += $c & 0xFF;
    }
    $fundoTransparente = $alfaCantos / 4 < 0.5;
    $fr /= 4; $fg /= 4; $fb /= 4;

    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $soma = 0.0; $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($im, $x, $y);
            $a = 1 - (($c >> 24) & 0x7F) / 127;
            if ($fundoTransparente) {
                $alfa = $a;
            } else {
                $d = max(abs((($c >> 16) & 0xFF) - $fr), abs((($c >> 8) & 0xFF) - $fg), abs(($c & 0xFF) - $fb)) / 255;
                $alfa = $a * min(1, $d / 0.35);
            }
            if ($alfa > 0.1) { $x0 = min($x0, $x); $y0 = min($y0, $y); $x1 = max($x1, $x); $y1 = max($y1, $y); }
            $soma += $alfa;
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 255, 255, 255, (int)round(127 - $alfa * 127)));
        }
    }
    // cobertura: quanto da caixa ocupada pelo desenho fica branco — perto de 1 é uma mancha
    $area = ($x1 >= $x0 && $y1 >= $y0) ? ($x1 - $x0 + 1) * ($y1 - $y0 + 1) : 0;
    if ($area === 0 || $soma / $area > LOGO_COBERTURA_MAX) {
        imagedestroy($out);
        $out = imagecreatetruecolor(1, 1);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagesetpixel($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    }
    $ok = imagepng($out, $dest, 9);
    imagedestroy($im); imagedestroy($out);
    return $ok;
}
