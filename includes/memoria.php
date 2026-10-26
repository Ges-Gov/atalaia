<?php
/**
 * Memória da freguesia (CORE: ficheiro idêntico em todos os sites).
 *
 * Mostra os blocos da tabela freguesia_memoria (migração 044) em cinco secções:
 * arquivo (imagens antigas), lendas, fé e devoção, calendário de tradições e
 * passado/presente/futuro — cada bloco com a fonte de onde foi retirado. No fim,
 * a lista de todas as fontes. O conteúdo de cada freguesia gere-se no admin
 * (A Freguesia → Memória da Freguesia). Secções sem blocos não aparecem.
 */
function mostrarMemoriaFreguesia(PDO $pdo): void
{
    try {
        $linhas = $pdo->query("SELECT * FROM freguesia_memoria WHERE ativo = 1 ORDER BY secao, ordem, id")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return;   // migração 044 ainda não corrida
    }
    if (!$linhas) return;

    $s = [];
    foreach ($linhas as $l) $s[$l['secao']][] = $l;
    $h = fn($t) => htmlspecialchars((string)$t);
    $img = fn($f) => '/assets/img/' . str_replace('%2F', '/', rawurlencode((string)$f));

    // fontes únicas, por ordem de aparecimento
    $fontes = [];
    foreach ($linhas as $l) {
        foreach (array_map('trim', explode(';', (string)$l['fonte_nome'])) as $i => $nome) {
            if ($nome === '') continue;
            $fontes[$nome] = $fontes[$nome] ?? ($i === 0 ? (string)$l['fonte_url'] : '');
        }
    }
    $fonte = function ($l) use ($h) {
        if (empty($l['fonte_nome'])) return '';
        $t = '<i class="bi bi-book" aria-hidden="true"></i> Fonte: ' . $h($l['fonte_nome']);
        return '<p class="mem-fonte">' . (!empty($l['fonte_url'])
            ? '<a href="' . $h($l['fonte_url']) . '" target="_blank" rel="noopener">' . $t . ' <span class="sr-only">(abre numa nova janela)</span></a>'
            : $t) . '</p>';
    };
    ?>
    <div class="memoria">

        <?php if (!empty($s['arquivo'])):
            $fotosArq = array_values(array_filter(array_map(fn($l) => $l['imagem'] ? $img($l['imagem']) : null, $s['arquivo']))); ?>
        <section class="mem-sec mem-arquivo" aria-labelledby="memArquivo">
            <div class="mem-cab">
                <span class="freguesia-kicker"><?= te('memoria.arquivo.kicker') ?></span>
                <h2 id="memArquivo"><?= te('memoria.arquivo.titulo') ?></h2>
                <p><?= te('memoria.arquivo.texto') ?></p>
            </div>
            <div class="mem-arquivo__grelha">
                <?php foreach ($s['arquivo'] as $k => $l): ?>
                    <figure class="mem-foto<?= $k === 0 ? ' mem-foto--grande' : '' ?>">
                        <?php if ($l['imagem']): ?>
                            <button type="button" class="mem-foto__img" aria-label="Ampliar: <?= $h($l['titulo']) ?>"
                                data-lightbox='<?= $h(json_encode(['titulo' => tx('memoria.arquivo.titulo'), 'fotos' => $fotosArq, 'inicio' => array_search($img($l['imagem']), $fotosArq)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                                <img src="<?= $h($img($l['imagem'])) ?>" alt="<?= $h($l['imagem_legenda'] ?: $l['titulo']) ?>" loading="lazy" decoding="async">
                                <?php if ($l['subtitulo']): ?><span class="mem-foto__data"><?= $h($l['subtitulo']) ?></span><?php endif; ?>
                            </button>
                        <?php endif; ?>
                        <figcaption>
                            <h3><?= $h($l['titulo']) ?></h3>
                            <p><?= $h($l['texto']) ?></p>
                            <?php if ($l['imagem_credito']): ?><p class="mem-credito"><i class="bi bi-camera" aria-hidden="true"></i> <?= $h($l['imagem_credito']) ?></p><?php endif; ?>
                            <?= $fonte($l) ?>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($s['lenda'])): ?>
        <section class="mem-sec mem-lendas" aria-labelledby="memLendas">
            <div class="mem-cab mem-cab--claro">
                <span class="freguesia-kicker"><?= te('memoria.lendas.kicker') ?></span>
                <h2 id="memLendas"><?= te('memoria.lendas.titulo') ?></h2>
                <p><?= te('memoria.lendas.texto') ?></p>
            </div>
            <div class="mem-lendas__grelha">
                <?php foreach ($s['lenda'] as $l): ?>
                    <article class="mem-lenda">
                        <span class="mem-lenda__ico" aria-hidden="true"><i class="bi <?= $h($l['icone'] ?: 'bi-book') ?>"></i></span>
                        <?php if ($l['subtitulo']): ?><span class="mem-etiqueta"><?= $h($l['subtitulo']) ?></span><?php endif; ?>
                        <h3><?= $h($l['titulo']) ?></h3>
                        <p><?= $h($l['texto']) ?></p>
                        <?= $fonte($l) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($s['fe'])): ?>
        <section class="mem-sec mem-fe" aria-labelledby="memFe">
            <div class="mem-cab">
                <span class="freguesia-kicker"><?= te('memoria.fe.kicker') ?></span>
                <h2 id="memFe"><?= te('memoria.fe.titulo') ?></h2>
            </div>
            <div class="mem-fe__grelha" data-galeria-lightbox="Fé e devoção">
                <?php foreach ($s['fe'] as $l): ?>
                    <article class="mem-fe__item<?= $l['imagem'] ? ' com-foto' : '' ?>">
                        <?php if ($l['imagem']): ?>
                            <div class="mem-fe__img"><img src="<?= $h($img($l['imagem'])) ?>" alt="<?= $h($l['imagem_legenda'] ?: $l['titulo']) ?>" loading="lazy" decoding="async">
                                <?php if ($l['imagem_credito']): ?><span class="mem-credito-foto"><?= $h($l['imagem_credito']) ?></span><?php endif; ?></div>
                        <?php endif; ?>
                        <div class="mem-fe__corpo">
                            <div class="mem-fe__topo">
                                <span class="mem-lenda__ico" aria-hidden="true"><i class="bi <?= $h($l['icone'] ?: 'bi-brightness-high') ?>"></i></span>
                                <?php if ($l['subtitulo']): ?><span class="mem-etiqueta"><?= $h($l['subtitulo']) ?></span><?php endif; ?>
                            </div>
                            <h3><?= $h($l['titulo']) ?></h3>
                            <p><?= $h($l['texto']) ?></p>
                            <?= $fonte($l) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($s['tradicao'])): ?>
        <section class="mem-sec mem-tradicoes" aria-labelledby="memTrad">
            <div class="mem-cab">
                <span class="freguesia-kicker"><?= te('memoria.tradicoes.kicker') ?></span>
                <h2 id="memTrad"><?= te('memoria.tradicoes.titulo') ?></h2>
            </div>
            <ol class="mem-calendario">
                <?php foreach ($s['tradicao'] as $l): ?>
                    <li class="mem-cal__item">
                        <div class="mem-cal__marca" aria-hidden="true"><i class="bi <?= $h($l['icone'] ?: 'bi-calendar-event') ?>"></i></div>
                        <div class="mem-cal__corpo<?= $l['imagem'] ? ' com-foto' : '' ?>">
                            <div>
                                <?php if ($l['subtitulo']): ?><span class="mem-cal__quando"><?= $h($l['subtitulo']) ?></span><?php endif; ?>
                                <h3><?= $h($l['titulo']) ?></h3>
                                <p><?= $h($l['texto']) ?></p>
                                <?= $fonte($l) ?>
                            </div>
                            <?php if ($l['imagem']): ?>
                                <div class="mem-cal__img"><img src="<?= $h($img($l['imagem'])) ?>" alt="<?= $h($l['imagem_legenda'] ?: $l['titulo']) ?>" loading="lazy" decoding="async">
                                    <?php if ($l['imagem_credito']): ?><span class="mem-credito-foto"><?= $h($l['imagem_credito']) ?></span><?php endif; ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
        <?php endif; ?>

        <?php if (!empty($s['tempo'])): ?>
        <section class="mem-sec mem-tempo" aria-labelledby="memTempo">
            <div class="mem-cab">
                <span class="freguesia-kicker"><?= te('memoria.tempo.kicker') ?></span>
                <h2 id="memTempo"><?= te('memoria.tempo.titulo') ?></h2>
            </div>
            <div class="mem-tempo__grelha">
                <?php foreach ($s['tempo'] as $l): ?>
                    <article class="mem-tempo__item">
                        <?php if ($l['imagem']): ?><img src="<?= $h($img($l['imagem'])) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
                        <div class="mem-tempo__texto">
                            <span class="mem-tempo__fase"><i class="bi <?= $h($l['icone'] ?: 'bi-clock') ?>" aria-hidden="true"></i> <?= $h($l['subtitulo']) ?></span>
                            <h3><?= $h($l['titulo']) ?></h3>
                            <p><?= $h($l['texto']) ?></p>
                            <?php if ($l['imagem_credito']): ?><p class="mem-credito"><i class="bi bi-camera" aria-hidden="true"></i> <?= $h($l['imagem_credito']) ?></p><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($fontes): ?>
        <details class="mem-fontes">
            <summary><i class="bi bi-journal-bookmark" aria-hidden="true"></i> <?= te('memoria.fontes.titulo') ?> (<?= count($fontes) ?>)</summary>
            <ul>
                <?php foreach ($fontes as $nome => $url): ?>
                    <li><?= $url ? '<a href="' . $h($url) . '" target="_blank" rel="noopener">' . $h($nome) . '</a>' : $h($nome) ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
    </div>
    <?php
}
