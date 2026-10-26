<?php
/*
 * Ordem dos slides da página inicial (migração 038).
 * Põe um slide na posição pedida (1 = primeiro) e renumera os outros 1, 2, 3… sem buracos nem repetidos:
 * quem já estava nessa posição e as seguintes chegam-se uma casa para trás.
 */
function reordenarSlides(PDO $pdo, int $idMovido, ?int $posicao): void
{
    $ids = $pdo->query("SELECT id FROM slides_homepage ORDER BY COALESCE(ordem, 999999), id")->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_values(array_filter(array_map('intval', $ids), fn($i) => $i !== $idMovido));

    $total = count($ids) + 1;
    $posicao = ($posicao === null || $posicao < 1 || $posicao > $total) ? $total : $posicao;
    array_splice($ids, $posicao - 1, 0, [$idMovido]);

    $upd = $pdo->prepare("UPDATE slides_homepage SET ordem = ? WHERE id = ?");
    foreach ($ids as $i => $id) {
        $upd->execute([$i + 1, $id]);
    }
}

function posicaoSlidePedida(): ?int
{
    $v = trim((string)($_POST['ordem'] ?? ''));
    return ($v !== '' && ctype_digit($v)) ? (int)$v : null;
}

/**
 * Slides de vídeo (12/10/2026): lê do formulário o tipo, o link do YouTube, o ajuste e o formato.
 * Devolve [tipo, video_url, video_ajuste, video_formato, erro].
 */
function slideVideoDoPost(): array
{
    require_once __DIR__ . '/../../includes/video.php';
    $tipo = ($_POST['tipo'] ?? 'imagem') === 'video' ? 'video' : 'imagem';
    $url = trim($_POST['video_url'] ?? '');
    $ajuste = ($_POST['video_ajuste'] ?? '') === 'ajustar' ? 'ajustar' : 'cobrir';
    $formato = ($_POST['video_formato'] ?? '') === 'cinema' ? 2.4 : 1.7778;
    $erro = '';
    if ($tipo === 'video' && youtubeId($url) === '') $erro = 'O link do YouTube não é válido. Copie o endereço do vídeo (ex.: https://www.youtube.com/watch?v=...).';
    return [$tipo, $tipo === 'video' ? $url : null, $ajuste, $formato, $erro];
}

/** Campos do formulário (tipo, link, ajuste, formato) — usados em adicionar/editar slide */
function slideVideoCampos(array $s = []): void
{
    $tipo = $s['tipo'] ?? 'imagem';
    $cinema = (float)($s['video_formato'] ?? 1.7778) > 2;
    ?>
    <fieldset class="slide-tipo">
        <legend>Tipo de slide</legend>
        <label class="check"><input type="radio" name="tipo" value="imagem" <?= $tipo !== 'video' ? 'checked' : '' ?>> Fotografia</label>
        <label class="check"><input type="radio" name="tipo" value="video" <?= $tipo === 'video' ? 'checked' : '' ?>> Vídeo do YouTube</label>
    </fieldset>
    <div class="slide-video" data-slide-video <?= $tipo === 'video' ? '' : 'hidden' ?>>
        <label for="videoUrl">Link do vídeo no YouTube</label>
        <input type="url" id="videoUrl" name="video_url" value="<?= htmlspecialchars($s['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        <small>O vídeo passa sem som por trás do título; o botão «Ver com som» abre o leitor do YouTube, onde se escolhe a qualidade (até 4K). O slide seguinte aparece quando o vídeo acaba. Sem imagem própria, usa-se a miniatura do YouTube enquanto o vídeo carrega.</small>
        <label>Como mostrar o vídeo</label>
        <label class="check"><input type="radio" name="video_ajuste" value="cobrir" <?= ($s['video_ajuste'] ?? 'cobrir') !== 'ajustar' ? 'checked' : '' ?>> Ocupar o ecrã todo (corta as margens se for preciso)</label>
        <label class="check"><input type="radio" name="video_ajuste" value="ajustar" <?= ($s['video_ajuste'] ?? '') === 'ajustar' ? 'checked' : '' ?>> Mostrar o vídeo inteiro (fundo desfocado à volta)</label>
        <label>Formato da imagem do vídeo</label>
        <label class="check"><input type="radio" name="video_formato" value="normal" <?= $cinema ? '' : 'checked' ?>> Normal (16:9)</label>
        <label class="check"><input type="radio" name="video_formato" value="cinema" <?= $cinema ? 'checked' : '' ?>> Cinema — o vídeo tem barras pretas em cima e em baixo (ficam escondidas)</label>
    </div>
    <script>
    document.querySelectorAll('input[name="tipo"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelector('[data-slide-video]').hidden = document.querySelector('input[name="tipo"]:checked').value !== 'video';
        });
    });
    </script>
    <?php
}
