<?php
$adminPageTitle = "Galeria — Álbuns";
$adminActive = "galeria-albuns";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/galeria.php";

$mensagem = "";
$erro = "";

// Criar álbum manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_album'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome === '') {
        $erro = "Dê um nome ao álbum.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ativo, na_apresentacao, ordem)
            VALUES (?, ?, 'manual', NULL, 1, 1, (SELECT o FROM (SELECT COALESCE(MAX(ordem), 0) + 1 AS o FROM galeria_albuns) t))
        ");
        $stmt->execute([$nome, $descricao]);

        header("Location: galeria-album.php?id=" . (int)$pdo->lastInsertId() . "&ok=criado");
        exit;
    }
}

// Eliminar álbum (as imagens caem por ON DELETE CASCADE; apagamos os ficheiros antes)
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    foreach (imagensDoAlbum($pdo, $id, false) as $img) {
        $caminho = __DIR__ . "/.." . GALERIA_DIR . $img['ficheiro'];
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    $pdo->prepare("DELETE FROM galeria_albuns WHERE id = ?")->execute([$id]);

    header("Location: galeria-albuns.php?ok=eliminado");
    exit;
}

// Ordem dos álbuns e apresentação da página inicial (13/10/2026): arrastar/setas e interruptor,
// guardados em segundo plano (admin/assets/ordenar.js). A ordem vale para a galeria do site e para
// a apresentação de fotografias da página inicial (só álbuns com «Na apresentação» ligado).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['acao'] ?? '', ['ordenar', 'apresentacao'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_POST['acao'] === 'ordenar') {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))))));
        $existentes = array_map('intval', $pdo->query("SELECT id FROM galeria_albuns")->fetchAll(PDO::FETCH_COLUMN));
        sort($existentes); $cmp = $ids; sort($cmp);
        if ($cmp !== $existentes) { http_response_code(409); echo json_encode(['ok' => false, 'erro' => 'A lista mudou entretanto. Atualize a página.']); exit; }
        $up = $pdo->prepare("UPDATE galeria_albuns SET ordem = ? WHERE id = ?");
        foreach ($ids as $i => $aid) $up->execute([$i + 1, $aid]);
    } else {
        $pdo->prepare("UPDATE galeria_albuns SET na_apresentacao = ? WHERE id = ?")
            ->execute([($_POST['valor'] ?? '') === '1' ? 1 : 0, (int)($_POST['id'] ?? 0)]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

$albuns = $pdo->query("
    SELECT a.*, COUNT(gi.id) AS n_imagens
    FROM galeria_albuns a
    LEFT JOIN galeria_imagens gi ON gi.album_id = a.id
    GROUP BY a.id
    ORDER BY a.ordem ASC, a.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/textos_bloco.php";
textosBloco("Texto do topo da página «Galeria»", ['galeria.hero.kicker','galeria.hero.texto_inicio','galeria.hero.texto_fim'], '/galeria.php');
?>

<?php if (!empty($_GET['ok'])): ?>
    <div class="admin-ok" style="background:#dcfce7;color:#166534;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        Álbum <?= $_GET['ok'] === 'eliminado' ? 'eliminado' : 'guardado' ?> com sucesso.
    </div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="table-box" style="margin-bottom:22px;">
    <h2 style="margin-top:0;">Novo álbum</h2>

    <form method="POST">
        <label>Nome do álbum *</label>
        <input type="text" name="nome" placeholder="Ex.: Festas da Freguesia 2026" required>

        <label>Descrição</label>
        <textarea name="descricao" placeholder="Descrição breve (opcional)"></textarea>

        <button class="btn" name="criar_album" value="1">Criar álbum</button>
    </form>
</div>

<p style="margin:0 0 14px;color:var(--adm-texto-2);font-size:14.5px"><i class="bi bi-arrows-move" aria-hidden="true"></i> Arraste pela pega <i class="bi bi-grip-vertical" aria-hidden="true"></i> ou use as setas para mudar a ordem dos álbuns (galeria do site e apresentação da página inicial). Ligue «Na apresentação» nos álbuns cujas fotografias devem passar na apresentação da página inicial. Fica tudo guardado automaticamente.</p>

<div class="table-box">
    <table data-sem-filtro>
        <thead><tr>
            <th>Ordem</th>
            <th>Capa</th>
            <th>Álbum</th>
            <th>Origem</th>
            <th>Imagens</th>
            <th>Na apresentação</th>
            <th>Ações</th>
        </tr></thead>
        <tbody data-ordenar="galeria-albuns.php">
        <?php foreach ($albuns as $k => $a): ?>
            <tr data-id="<?= (int)$a['id'] ?>">
                <td class="sl-ordem">
                    <span class="sl-pega" title="Arrastar para mudar a ordem" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
                    <b class="sl-num"><?= $k + 1 ?>º</b>
                    <span class="sl-setas">
                        <button type="button" data-mover="-1" aria-label="Subir «<?= htmlspecialchars($a['nome']) ?>»"><i class="bi bi-chevron-up"></i></button>
                        <button type="button" data-mover="1" aria-label="Descer «<?= htmlspecialchars($a['nome']) ?>»"><i class="bi bi-chevron-down"></i></button>
                    </span>
                </td>
                <td>
                    <?php if (!empty($a['capa'])): ?>
                        <img src="<?= htmlspecialchars(imagemGaleriaUrl($a['capa'])) ?>" alt="" style="width:74px;height:52px;object-fit:cover;border-radius:8px;">
                    <?php else: ?>
                        <span style="color:#94a3b8;">—</span>
                    <?php endif; ?>
                </td>

                <td>
                    <strong><?= htmlspecialchars($a['nome']) ?></strong>
                    <?php if (!empty($a['descricao'])): ?>
                        <br><small style="color:#64748b;"><?= htmlspecialchars(mb_substr($a['descricao'], 0, 70)) ?></small>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($a['origem'] === 'manual'): ?>
                        Manual
                    <?php else: ?>
                        <span title="Este álbum é gerido automaticamente pelo <?= htmlspecialchars($a['origem']) ?> a que pertence.">
                            <?= $a['origem'] === 'evento' ? 'Evento' : 'Ponto de interesse' ?> (automático)
                        </span>
                    <?php endif; ?>
                </td>

                <td><?= (int)$a['n_imagens'] ?></td>
                <td>
                    <label class="sl-interruptor" title="As fotografias deste álbum passam na apresentação da página inicial">
                        <input type="checkbox" data-alternar="apresentacao" data-txt-on="Sim" data-txt-off="Não"
                               data-msg-on="Fotografias incluídas na apresentação da página inicial" data-msg-off="Fotografias retiradas da apresentação da página inicial"
                               <?= !empty($a['na_apresentacao']) ? 'checked' : '' ?> aria-label="Incluir «<?= htmlspecialchars($a['nome']) ?>» na apresentação da página inicial">
                        <span class="sl-interruptor__trilho" aria-hidden="true"></span>
                        <span class="sl-interruptor__txt"><?= !empty($a['na_apresentacao']) ? 'Sim' : 'Não' ?></span>
                    </label>
                </td>

                <td>
                    <a class="btn secondary" href="galeria-album.php?id=<?= (int)$a['id'] ?>">Gerir imagens</a>
                    <a class="btn danger" href="galeria-albuns.php?eliminar=<?= (int)$a['id'] ?>"
                       onclick="return confirm('Eliminar o álbum e TODAS as suas imagens?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($albuns)): ?>
            <tr><td colspan="7">Ainda não existem álbuns. Crie o primeiro acima.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="assets/ordenar.js?v=20261013a" defer></script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
