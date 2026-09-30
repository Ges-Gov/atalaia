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
            INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ativo)
            VALUES (?, ?, 'manual', NULL, 1)
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

$albuns = $pdo->query("
    SELECT a.*, COUNT(gi.id) AS n_imagens
    FROM galeria_albuns a
    LEFT JOIN galeria_imagens gi ON gi.album_id = a.id
    GROUP BY a.id
    ORDER BY a.origem = 'manual' DESC, a.ordem ASC, a.nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/includes/header.php";
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

<div class="table-box">
    <table>
        <tr>
            <th>Capa</th>
            <th>Álbum</th>
            <th>Origem</th>
            <th>Imagens</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($albuns as $a): ?>
            <tr>
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
                    <a class="btn secondary" href="galeria-album.php?id=<?= (int)$a['id'] ?>">Gerir imagens</a>
                    <a class="btn danger" href="galeria-albuns.php?eliminar=<?= (int)$a['id'] ?>"
                       onclick="return confirm('Eliminar o álbum e TODAS as suas imagens?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($albuns)): ?>
            <tr><td colspan="5">Ainda não existem álbuns. Crie o primeiro acima.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
