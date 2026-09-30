<?php
$adminPageTitle = "Executivo";
$adminActive = "executivo";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/membros.php";

$erro = "";
$editar = null;

if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM executivo_membros WHERE id = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    $stmt = $pdo->prepare("SELECT foto FROM executivo_membros WHERE id = ?");
    $stmt->execute([$id]);
    $membro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($membro && !empty($membro['foto'])) {
        $caminho = __DIR__ . "/../assets/img/" . $membro['foto'];
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM executivo_membros WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: executivo.php?ok=eliminado");
    exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE executivo_membros
        SET ativo = IF(ativo = 1, 0, 1)
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    header("Location: executivo.php?ok=estado");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $pelouros = trim($_POST['pelouros'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $fotoAtual = $_POST['foto_atual'] ?? null;

    if ($nome === '' || $cargo === '') {
        $erro = "O nome e o cargo são obrigatórios.";
    } else {
        // Upload partilhado com a Assembleia (includes/membros.php): mesma pasta,
        // mesma validação e mensagens de erro que dizem o que realmente falhou.
        [$foto, $erroFoto] = guardarFotoMembro($_FILES['foto'] ?? [], (string)$fotoAtual);

        if ($erroFoto !== '') {
            $erro = $erroFoto;
        }

        if ($erro === "") {
            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE executivo_membros
                    SET nome = ?, cargo = ?, pelouros = ?, biografia = ?, email = ?, foto = ?, ordem = ?, ativo = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $nome,
                    $cargo,
                    $pelouros,
                    $biografia,
                    $email,
                    $foto,
                    $ordem,
                    $ativo,
                    $id
                ]);

                header("Location: executivo.php?ok=editado");
                exit;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO executivo_membros
                    (nome, cargo, pelouros, biografia, email, foto, ordem, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $nome,
                    $cargo,
                    $pelouros,
                    $biografia,
                    $email,
                    $foto,
                    $ordem,
                    $ativo
                ]);

                header("Location: executivo.php?ok=1");
                exit;
            }
        }
    }
}

$stmt = $pdo->query("
    SELECT *
    FROM executivo_membros
    ORDER BY ordem ASC, id DESC
");
$membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/includes/header.php";
?>

<div class="admin-topbar">
    <h1>Executivo</h1>
    <p>Gerir os membros do executivo da Junta de Freguesia.</p>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="alerta-sucesso">Alterações guardadas com sucesso.</div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="table-box" style="margin-bottom:25px;">
    <h2><?= $editar ? 'Editar membro' : 'Adicionar membro' ?></h2>

    <form method="POST" enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
        <input type="hidden" name="foto_atual" value="<?= htmlspecialchars($editar['foto'] ?? '') ?>">

        <label>Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" required>

        <label>Cargo</label>
        <input type="text" name="cargo" value="<?= htmlspecialchars($editar['cargo'] ?? '') ?>" required>

        <label>Pelouros</label>
        <textarea name="pelouros"><?= htmlspecialchars($editar['pelouros'] ?? '') ?></textarea>

        <label>Biografia / nota curricular</label>
        <textarea name="biografia"><?= htmlspecialchars($editar['biografia'] ?? '') ?></textarea>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($editar['email'] ?? '') ?>">

        <?php if (!empty($editar['foto'])): ?>
            <p><strong>Foto atual:</strong></p>
            <img src="/assets/img/<?= htmlspecialchars($editar['foto']) ?>" style="width:140px;border-radius:14px;margin-bottom:15px;">
        <?php endif; ?>

        <label>Fotografia</label>
        <input type="file" name="foto" accept="image/*">

        <label>Ordem</label>
        <input type="number" name="ordem" value="<?= htmlspecialchars($editar['ordem'] ?? 0) ?>">

        <label>
            <input type="checkbox" name="ativo" <?= !isset($editar['ativo']) || !empty($editar['ativo']) ? 'checked' : '' ?>>
            Membro ativo
        </label>

        <br><br>

        <button type="submit" class="btn">
            <?= $editar ? 'Guardar alterações' : 'Adicionar membro' ?>
        </button>

        <?php if ($editar): ?>
            <a href="executivo.php" class="btn secondary">Cancelar edição</a>
        <?php endif; ?>

    </form>
</div>

<div class="table-box">
    <h2>Membros atuais</h2>

    <?php if (!empty($membros)): ?>

        <div class="executivo-admin-grid">

            <?php foreach ($membros as $m): ?>

                <div class="executivo-admin-card <?= empty($m['ativo']) ? 'off' : '' ?>">

                    <div class="executivo-admin-photo">
                        <?php if (!empty($m['foto'])): ?>
                            <img src="/assets/img/<?= htmlspecialchars($m['foto']) ?>" alt="<?= htmlspecialchars($m['nome']) ?>">
                        <?php else: ?>
                            <span><?= strtoupper(mb_substr($m['nome'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="executivo-admin-info">
                        <h3><?= htmlspecialchars($m['nome']) ?></h3>
                        <strong><?= htmlspecialchars($m['cargo']) ?></strong>

                        <?php if (!empty($m['pelouros'])): ?>
                            <p><?= nl2br(htmlspecialchars($m['pelouros'])) ?></p>
                        <?php endif; ?>

                        <small>
                            Ordem: <?= (int)$m['ordem'] ?> ·
                            <?= !empty($m['ativo']) ? 'Ativo' : 'Inativo' ?>
                        </small>

                        <div class="executivo-admin-actions">
                            <a class="btn" href="executivo.php?editar=<?= (int)$m['id'] ?>">
                                Editar
                            </a>

                            <a class="btn secondary" href="executivo.php?toggle=<?= (int)$m['id'] ?>">
                                <?= !empty($m['ativo']) ? 'Desativar' : 'Ativar' ?>
                            </a>

                            <a
                                class="btn danger"
                                href="executivo.php?eliminar=<?= (int)$m['id'] ?>"
                                onclick="return confirm('Eliminar este membro?')"
                            >
                                Eliminar
                            </a>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p>Ainda não existem membros registados.</p>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>