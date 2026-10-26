<?php
/**
 * Vídeos do YouTube nos slides do topo (12/10/2026) — CORE.
 * Sempre pelo domínio youtube-nocookie.com e só depois de aceites os cookies (ver footer.php).
 *
 * Qualidade: o YouTube já não deixa um site forçar a qualidade (setPlaybackQuality e "vq" foram
 * desativados) — escolhe-a pelo TAMANHO do leitor. Por isso o vídeo de fundo é desenhado pelo menos
 * do tamanho do ecrã (recebe a melhor qualidade disponível para esse ecrã), e o botão «Ver com som»
 * abre o leitor completo do YouTube, onde a pessoa escolhe a qualidade na roda dentada (até 4K).
 */

/** ID do vídeo a partir de qualquer link do YouTube (watch?v=, youtu.be/, shorts/, embed/, live/) */
function youtubeId(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) return $url;
    if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:embed/|shorts/|live/|v/|watch\?(?:.*&)?v=))([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
    return '';
}

/** Vídeo de fundo do slide: sem som, sem controlos, controlável por JS (enablejsapi) */
function youtubeEmbedFundo(string $id): string
{
    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id) . '?' . http_build_query([
        'enablejsapi' => 1, 'autoplay' => 0, 'mute' => 1, 'controls' => 0, 'playsinline' => 1,
        'rel' => 0, 'modestbranding' => 1, 'iv_load_policy' => 3, 'disablekb' => 1, 'fs' => 0,
        // sem loop do YouTube (o app.js recomeça o vídeo quando acaba) e sem «origin»: atrás do túnel
        // público o servidor vê outro domínio e a comunicação com o leitor falhava.
        // «vq» é ignorado pelo YouTube hoje, mas não faz mal pedir; a qualidade vem do leitor de 1920 px.
        'vq' => 'hd1080',
    ]);
}

/** Leitor completo (com som, controlos e escolha de qualidade) para o botão «Ver com som» */
function youtubeEmbedLeitor(string $id): string
{
    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id) . '?autoplay=1&controls=1&rel=0&modestbranding=1&playsinline=1';
}

/** Miniatura do YouTube (para quando o slide de vídeo não tem imagem própria) */
function youtubeMiniatura(string $id): string
{
    return 'https://i.ytimg.com/vi/' . rawurlencode($id) . '/hqdefault.jpg';
}
