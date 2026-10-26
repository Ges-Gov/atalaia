<?php
/**
 * Cartões imersivos (CORE) — fotografia de fundo, véu escuro→verde institucional, ícone em
 * vidro, título, texto curto e "Saber mais". Sem fotografia, usa uma textura institucional
 * (gradiente + padrão + ícone grande em marca d'água) — nunca uma imagem genérica.
 *
 * cartaoImersivo([
 *   'titulo' => '', 'texto' => '', 'url' => '', 'imagem' => 'ficheiro em assets/img' | '',
 *   'icone' => 'bi-…', 'etiqueta' => '', 'acao' => 'Saber mais', 'externo' => false,
 * ])
 * secaoImersiva('id', 'Kicker', 'Título', 'Texto', [cartões…]) imprime a secção completa.
 */
function cartaoImersivo(array $c): string
{
    $h = fn($s) => htmlspecialchars((string)$s);
    $titulo = trim((string)($c['titulo'] ?? ''));
    if ($titulo === '' || empty($c['url'])) return '';
    $img = !empty($c['imagem']) && is_file(__DIR__ . '/../assets/img/' . $c['imagem']) ? '/assets/img/' . $c['imagem'] : '';
    $icone = $c['icone'] ?? 'bi-arrow-up-right';
    $ext = !empty($c['externo']);
    return '<a class="imersivo' . ($img ? '' : ' imersivo--textura') . '" href="' . $h($c['url']) . '"' . ($ext ? ' target="_blank" rel="noopener"' : '') . '>'
        . ($img ? '<img class="imersivo__img" src="' . $h($img) . '" alt="" loading="lazy" decoding="async">'
                : '<i class="bi ' . $h($icone) . ' imersivo__marca" aria-hidden="true"></i>')
        . (!empty($c['etiqueta']) ? '<span class="imersivo__etq">' . $h($c['etiqueta']) . '</span>' : '')
        . '<span class="imersivo__corpo">'
        . '<span class="imersivo__ico" aria-hidden="true"><i class="bi ' . $h($icone) . '"></i></span>'
        . '<h3 class="imersivo__titulo">' . $h($titulo) . '</h3>'
        . (!empty($c['texto']) ? '<span class="imersivo__texto">' . $h($c['texto']) . '</span>' : '')
        . '<span class="imersivo__acao">' . $h($c['acao'] ?? 'Saber mais') . ' <i class="bi ' . ($ext ? 'bi-box-arrow-up-right' : 'bi-arrow-right') . '" aria-hidden="true"></i>'
        . ($ext ? '<span class="sr-only"> (abre numa nova janela)</span>' : '') . '</span>'
        . '</span></a>';
}

function secaoImersiva(string $id, string $kicker, string $titulo, string $texto, array $cartoes, string $spy = ''): void
{
    $html = implode('', array_map('cartaoImersivo', $cartoes));
    if ($html === '') return;
    ?>
    <section class="section imersivos" id="<?= htmlspecialchars($id) ?>" aria-labelledby="<?= htmlspecialchars($id) ?>-t"<?= $spy ? ' data-spy="' . htmlspecialchars($spy) . '"' : '' ?>>
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-kicker"><?= htmlspecialchars($kicker) ?></span>
                    <h2 id="<?= htmlspecialchars($id) ?>-t"><?= htmlspecialchars($titulo) ?></h2>
                    <?php if ($texto): ?><p class="imersivos__intro"><?= htmlspecialchars($texto) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="imersivos__grelha"><?= $html ?></div>
        </div>
    </section>
    <?php
}
