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
