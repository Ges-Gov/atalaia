<?php
/**
 * Versão BRANCA dos logótipos (Links úteis) para o modo escuro (12/10/2026) — CORE.
 *
 * Em fundo escuro, logótipos escuros ou com fundo branco liam-se mal. Para cada logótipo gera-se
 * uma vez "<nome>-branco.png" (PNG transparente, tudo a branco):
 *  - logótipo com fundo transparente → cada parte visível fica branca (mantém a transparência);
 *  - logótipo com fundo sólido (ex.: branco) → o fundo desaparece e o resto fica branco, mais ou
 *    menos opaco conforme se distingue do fundo (mantém letras finas e degradês legíveis).
 * Volta a gerar-se sozinho se o logótipo original for substituído (data do ficheiro).
 */
function logoBrancoUrl(string $logo): string
{
    if ($logo === '' || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $logo)) return '';
    $dir = __DIR__ . '/../assets/img/';
    $orig = $dir . $logo;
    if (!is_file($orig)) return '';
    $rel = preg_replace('/\.[a-z]+$/i', '', $logo) . '-branco.png';
    $dest = $dir . $rel;
    if (!is_file($dest) || filemtime($dest) < filemtime($orig)) {
        if (!logoGerarBranco($orig, $dest)) return '';
    }
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
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 255, 255, 255, (int)round(127 - $alfa * 127)));
        }
    }
    $ok = imagepng($out, $dest, 9);
    imagedestroy($im); imagedestroy($out);
    return $ok;
}
