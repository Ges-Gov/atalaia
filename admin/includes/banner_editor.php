<?php
/**
 * Editor do banner do topo de uma página (13/10/2026).
 * Mostra a fotografia de fundo como fica no site — em computador e em telemóvel, com o mesmo véu
 * escuro e um título de exemplo — e deixa arrastar a imagem para escolher que parte aparece.
 * Guarda sozinho (admin/banner.php). Pode ser posto dentro de qualquer página/formulário (não usa
 * <form> próprio). A fotografia e a posição valem para o banner do site (tabela separadores_fundo).
 *
 *   bannerEditor('freguesia', 'Nome da freguesia', 'A Freguesia');
 */
require_once __DIR__ . '/../../includes/separadores.php';

function bannerEditor(string $chave, string $titulo = '', string $kicker = ''): void
{
    global $pdo;
    $img = ''; $x = 50; $y = 50;
    try {
        $s = $pdo->prepare("SELECT imagem, foco_x, foco_y FROM separadores_fundo WHERE chave = ?");
        $s->execute([$chave]);
        if ($r = $s->fetch(PDO::FETCH_ASSOC)) { $img = (string)$r['imagem']; $x = (int)$r['foco_x']; $y = (int)$r['foco_y']; }
    } catch (Throwable $e) {}
    $url = $img !== '' ? '/uploads/separadores/' . $img : '';
    $nome = $GLOBALS['SEPARADORES_FUNDO'][$chave] ?? $chave;
    static $recursos = false;
    if (!$recursos) { $recursos = true; ?>
        <link rel="stylesheet" href="assets/banner-editor.css?v=20261013a">
        <script src="assets/banner-editor.js?v=20261013a" defer></script>
    <?php } ?>
    <div class="bn" data-banner-editor data-chave="<?= htmlspecialchars($chave) ?>" data-url="<?= htmlspecialchars($url) ?>" data-x="<?= $x ?>" data-y="<?= $y ?>">
        <div class="bn__topo">
            <div>
                <strong class="bn__titulo"><i class="bi bi-image" aria-hidden="true"></i> Banner do topo — <?= htmlspecialchars($nome) ?></strong>
                <p class="bn__ajuda">Arraste a fotografia dentro de uma das pré-visualizações para escolher a parte que aparece. Fica guardado sozinho e é assim que aparece no site.</p>
            </div>
            <div class="bn__acoes">
                <button type="button" class="btn secondary" data-bn-centrar><i class="bi bi-bullseye" aria-hidden="true"></i> Centrar</button>
                <label class="btn secondary bn__trocar"><i class="bi bi-upload" aria-hidden="true"></i> <?= $url ? 'Trocar imagem' : 'Carregar imagem' ?>
                    <input type="file" accept="image/*" data-bn-ficheiro hidden></label>
            </div>
        </div>
        <div class="bn__vistas" <?= $url ? '' : 'hidden' ?>>
            <figure class="bn__vista bn__vista--pc">
                <div class="bn__quadro" data-bn-quadro data-largura="1440" data-altura-ecra="900" tabindex="0" role="slider"
                     aria-label="Posição da fotografia (setas do teclado para ajustar)" aria-valuetext="<?= $x ?>% horizontal, <?= $y ?>% vertical">
                    <span class="bn__texto"><?php if ($kicker): ?><small><?= htmlspecialchars($kicker) ?></small><?php endif; ?><b><?= htmlspecialchars($titulo ?: $nome) ?></b></span>
                    <span class="bn__mao" aria-hidden="true"><i class="bi bi-arrows-move"></i></span>
                </div>
                <figcaption><i class="bi bi-display" aria-hidden="true"></i> Computador</figcaption>
            </figure>
            <figure class="bn__vista bn__vista--tel">
                <div class="bn__quadro" data-bn-quadro data-largura="390" data-altura-ecra="844" tabindex="-1">
                    <span class="bn__texto"><?php if ($kicker): ?><small><?= htmlspecialchars($kicker) ?></small><?php endif; ?><b><?= htmlspecialchars($titulo ?: $nome) ?></b></span>
                </div>
                <figcaption><i class="bi bi-phone" aria-hidden="true"></i> Telemóvel</figcaption>
            </figure>
        </div>
        <p class="bn__vazio" <?= $url ? 'hidden' : '' ?>>Esta página ainda não tem fotografia no banner — usa o fundo de cor padrão. Carregue uma imagem para a pré-visualizar.</p>
        <p class="bn__estado" role="status" aria-live="polite"></p>
    </div>
    <?php
}
