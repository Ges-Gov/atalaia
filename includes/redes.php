<?php
/**
 * Redes sociais da freguesia (CORE: ficheiro idêntico em todos os sites).
 *
 * Os links vivem em configuracoes_site (admin → Sistema → Configurações → Redes Sociais;
 * colunas novas na migração 041). Devolve só as redes preenchidas, pela ordem abaixo,
 * para o menu e o rodapé mostrarem os mesmos ícones.
 */
function redesSociais(): array
{
    $redes = [
        ['facebook',  'Facebook',    'bi-facebook'],
        ['instagram', 'Instagram',   'bi-instagram'],
        ['youtube',   'YouTube',     'bi-youtube'],
        ['x_twitter', 'X (Twitter)', 'bi-twitter-x'],
        ['tiktok',    'TikTok',      'bi-tiktok'],
        ['linkedin',  'LinkedIn',    'bi-linkedin'],
        ['whatsapp',  'WhatsApp',    'bi-whatsapp'],
    ];
    $out = [];
    foreach ($redes as [$campo, $nome, $icone]) {
        $url = trim((string)siteConfig($campo, ''));
        if ($url === '') continue;
        // WhatsApp: aceita só o número (ex.: 912 345 678) e converte para o link oficial
        if ($campo === 'whatsapp' && !preg_match('#^https?://#i', $url)) {
            $num = preg_replace('/\D/', '', $url);
            if ($num === '') continue;
            if (strlen($num) === 9) $num = '351' . $num;
            $url = 'https://wa.me/' . $num;
        }
        if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
        $out[] = ['nome' => $nome, 'url' => $url, 'icone' => $icone];
    }
    return $out;
}
