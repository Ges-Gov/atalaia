<?php
$adminPageTitle = "Galeria da Homepage";
$adminActive = "galeria-homepage";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$mensagem = "";
$erro = "";

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    $stmt = $pdo->prepare("SELECT imagem FROM homepage_galeria WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($img) {
        $caminho = __DIR__ . "/../assets/img/" . $img['imagem'];

        if (is_file($caminho)) {
            @unlink($caminho);
        }

        $stmt = $pdo->prepare("DELETE FROM homepage_galeria WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: galeria-homepage.php?ok=eliminado");
        exit;
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE homepage_galeria 
        SET ativo = IF(ativo = 1, 0, 1) 
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    header("Location: galeria-homepage.php?ok=estado");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!empty($_FILES['imagem']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $permitidas)) {
            $erro = "Formato inválido. Use JPG, PNG ou WEBP.";
        } else {
            $nomeImagem = "galeria_home_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $destino = __DIR__ . "/../assets/img/" . $nomeImagem;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $stmt = $pdo->prepare("
                    INSERT INTO homepage_galeria 
                    (imagem, titulo, ordem, ativo)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nomeImagem, $titulo, $ordem, $ativo]);

                header("Location: galeria-homepage.php?ok=1");
                exit;
            } else {
                $erro = "Erro ao enviar imagem.";
            }
        }
    } else {
        $erro = "Escolha uma imagem.";
    }
}

$stmt = $pdo->query("
    SELECT *
    FROM homepage_galeria
    ORDER BY ordem ASC, id DESC
");
$imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/includes/header.php";
?>

<div class="admin-topbar">
    <h1>Galeria da Homepage</h1>
    <p>Gerir as imagens da secção “Uma freguesia com identidade”.</p>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="alerta-sucesso">Alterações guardadas com sucesso.</div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="table-box" style="margin-bottom:25px;">
    <h2>Adicionar imagem</h2>

    <form method="POST" enctype="multipart/form-data">
        <label>Título da imagem</label>
        <input type="text" name="titulo" placeholder="Ex: Vista da freguesia">

        <label>Imagem</label>
        <input type="file" name="imagem" accept="image/*" required>

        <label>Ordem</label>
        <input type="number" name="ordem" value="0">

        <label>
            <input type="checkbox" name="ativo" checked>
            Imagem ativa
        </label>

        <br><br>

        <button type="submit" class="btn">
            Adicionar imagem
        </button>
    </form>
</div>

<div class="table-box">
    <h2>Imagens atuais</h2>

    <?php if (!empty($imagens)): ?>
        <div class="galeria-admin-grid">

            <?php foreach ($imagens as $img): ?>
                <div class="galeria-admin-card <?= empty($img['ativo']) ? 'off' : '' ?>">

                    <img src="/assets/img/<?= htmlspecialchars($img['imagem']) ?>" alt="">

                    <div>
                        <h3><?= htmlspecialchars($img['titulo'] ?: 'Sem título') ?></h3>
                        <p>Ordem: <?= (int)$img['ordem'] ?></p>
                        <p>Estado: <?= !empty($img['ativo']) ? 'Ativa' : 'Inativa' ?></p>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                            <a class="btn secondary" href="galeria-homepage.php?toggle=<?= (int)$img['id'] ?>">
                                <?= !empty($img['ativo']) ? 'Desativar' : 'Ativar' ?>
                            </a>

                            <a 
                                class="btn danger" 
                                href="galeria-homepage.php?eliminar=<?= (int)$img['id'] ?>"
                                onclick="return confirm('Eliminar esta imagem?')"
                            >
                                Eliminar
                            </a>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>
    <?php else: ?>
        <p>Ainda não existem imagens na galeria.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>