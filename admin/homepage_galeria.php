<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$erro = "";
$ok = isset($_GET["ok"]) ? "Alterações guardadas com sucesso." : "";

function nomeImagemSeguro($nome) {
    $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    $base = pathinfo($nome, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $base);
    $base = trim($base, '-');
    if ($base === '') $base = 'imagem';
    return $base . '-' . time() . '.' . $ext;
}

function apagarImagemAntiga($imagem) {
    if (!empty($imagem)) {
        $ficheiro = "../assets/img/" . $imagem;
        if (file_exists($ficheiro)) {
            @unlink($ficheiro);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST["acao"] ?? "";

    if ($acao === "adicionar") {
        $titulo = trim($_POST["titulo"] ?? "");
        $ordem = (int)($_POST["ordem"] ?? 0);
        $ativo = isset($_POST["ativo"]) ? 1 : 0;
        $imagem = "";

        if (!$titulo) {
            $erro = "Indique o título da imagem.";
        }

        if (!$erro && empty($_FILES["imagem"]["name"])) {
            $erro = "Selecione uma imagem.";
        }

        if (!$erro) {
            $ext = strtolower(pathinfo($_FILES["imagem"]["name"], PATHINFO_EXTENSION));
            $permitidas = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($ext, $permitidas)) {
                $erro = "Formato inválido. Use JPG, PNG ou WEBP.";
            } else {
                if (!is_dir("../assets/img")) {
                    mkdir("../assets/img", 0777, true);
                }

                $imagem = nomeImagemSeguro($_FILES["imagem"]["name"]);
                move_uploaded_file($_FILES["imagem"]["tmp_name"], "../assets/img/" . $imagem);

                $stmt = $pdo->prepare("
                    INSERT INTO homepage_galeria (imagem, titulo, ordem, ativo)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$imagem, $titulo, $ordem, $ativo]);

                header("Location: homepage_galeria.php?ok=1");
                exit;
            }
        }
    }

    if ($acao === "editar") {
        $id = (int)($_POST["id"] ?? 0);
        $titulo = trim($_POST["titulo"] ?? "");
        $ordem = (int)($_POST["ordem"] ?? 0);
        $ativo = isset($_POST["ativo"]) ? 1 : 0;

        $stmt = $pdo->prepare("SELECT * FROM homepage_galeria WHERE id = ?");
        $stmt->execute([$id]);
        $atual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atual) {
            $erro = "Imagem não encontrada.";
        } else {
            $imagem = $atual["imagem"];

            if (!empty($_FILES["imagem"]["name"])) {
                $ext = strtolower(pathinfo($_FILES["imagem"]["name"], PATHINFO_EXTENSION));
                $permitidas = ["jpg", "jpeg", "png", "webp"];

                if (!in_array($ext, $permitidas)) {
                    $erro = "Formato inválido. Use JPG, PNG ou WEBP.";
                } else {
                    $novaImagem = nomeImagemSeguro($_FILES["imagem"]["name"]);
                    move_uploaded_file($_FILES["imagem"]["tmp_name"], "../assets/img/" . $novaImagem);
                    apagarImagemAntiga($imagem);
                    $imagem = $novaImagem;
                }
            }

            if (!$erro) {
                $stmt = $pdo->prepare("
                    UPDATE homepage_galeria
                    SET imagem = ?, titulo = ?, ordem = ?, ativo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$imagem, $titulo, $ordem, $ativo, $id]);

                header("Location: homepage_galeria.php?ok=1");
                exit;
            }
        }
    }

    if ($acao === "toggle") {
        $id = (int)($_POST["id"] ?? 0);
        $stmt = $pdo->prepare("UPDATE homepage_galeria SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: homepage_galeria.php?ok=1");
        exit;
    }

    if ($acao === "eliminar") {
        $id = (int)($_POST["id"] ?? 0);

        $stmt = $pdo->prepare("SELECT imagem FROM homepage_galeria WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($img) {
            apagarImagemAntiga($img["imagem"]);
        }

        $stmt = $pdo->prepare("DELETE FROM homepage_galeria WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: homepage_galeria.php?ok=1");
        exit;
    }
}

$editar = null;
if (!empty($_GET["editar"])) {
    $stmt = $pdo->prepare("SELECT * FROM homepage_galeria WHERE id = ?");
    $stmt->execute([(int)$_GET["editar"]]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$imagens = $pdo->query("SELECT * FROM homepage_galeria ORDER BY ordem ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalAtivas = $pdo->query("SELECT COUNT(*) FROM homepage_galeria WHERE ativo = 1")->fetchColumn();

require_once "includes/header.php";
?>

<style>
.galeria-admin-page{padding:28px;}
.galeria-hero-admin{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:28px;padding:30px;margin-bottom:24px;display:flex;justify-content:space-between;gap:22px;align-items:center;box-shadow:0 20px 45px rgba(0,0,0,.14);overflow:hidden;position:relative;}
.galeria-hero-admin::after{content:"";position:absolute;width:360px;height:360px;border-radius:50%;right:-120px;top:-180px;background:rgba(255,255,255,.06);}
.galeria-hero-admin>*{position:relative;z-index:2;}
.galeria-hero-admin span{color:#D4AA00;text-transform:uppercase;font-weight:900;letter-spacing:.8px;font-size:13px;}
.galeria-hero-admin h1{margin:8px 0;font-size:34px;}
.galeria-hero-admin p{margin:0;color:#dbeafe;}
.galeria-hero-stats{display:flex;gap:12px;}
.galeria-hero-stats div{min-width:130px;padding:18px;border-radius:18px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);text-align:center;}
.galeria-hero-stats strong{display:block;font-size:30px;}

.galeria-admin-grid{display:grid;grid-template-columns:390px 1fr;gap:24px;align-items:start;}
.galeria-admin-card{background:white;border-radius:24px;padding:24px;box-shadow:0 16px 40px rgba(0,0,0,.08);border:1px solid #eef2f7;}
.galeria-admin-card h2{margin:0 0 18px;color:#11151B;}

/* Neutraliza o estilo global de "form como cartão" do admin.css */
.galeria-form,.galeria-actions form{background:none;padding:0;box-shadow:none;border-radius:0;}
.galeria-actions form{display:inline;margin:0;}
.galeria-form label{display:block;font-weight:900;color:#11151B;margin:14px 0 7px;}
.galeria-form input[type="text"],.galeria-form input[type="number"],.galeria-form input[type="file"]{width:100%;min-height:48px;border:1px solid #dbe3ea;border-radius:14px;padding:10px 14px;box-sizing:border-box;background:#f8fafc;}
.galeria-form input:focus{outline:none;border-color:#242A30;box-shadow:0 0 0 4px rgba(36,42,50,.10);background:white;}
.galeria-check{display:flex;gap:10px;align-items:center;background:#f8fafc;padding:13px;border-radius:14px;margin:14px 0;}
.galeria-check input{width:18px;height:18px;}
.galeria-form-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;}

.galeria-btn{border:0;background:#242A30;color:white;padding:13px 18px;border-radius:14px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:.25s;}
.galeria-btn:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(36,42,50,.25);}
.galeria-btn.secondary{background:#64748b;}

.galeria-alert{padding:14px 16px;border-radius:14px;margin-bottom:18px;font-weight:900;}
.galeria-alert.ok{background:#dcfce7;color:#166534;}
.galeria-alert.erro{background:#fee2e2;color:#991b1b;}

.galeria-lista{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:18px;}
.galeria-item{background:#f8fafc;border:1px solid #e5e7eb;border-radius:22px;overflow:hidden;box-shadow:0 10px 26px rgba(0,0,0,.05);transition:.25s;}
.galeria-item:hover{transform:translateY(-5px);box-shadow:0 18px 42px rgba(0,0,0,.10);background:white;}
.galeria-img{height:160px;background:#e5e7eb;position:relative;overflow:hidden;}
.galeria-img img{width:100%;height:100%;object-fit:cover;display:block;transition:.35s;}
.galeria-item:hover .galeria-img img{transform:scale(1.06);}
.galeria-badge{position:absolute;top:12px;left:12px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:900;background:#dcfce7;color:#166534;}
.galeria-badge.off{background:#e5e7eb;color:#374151;}
.galeria-body{padding:16px;}
.galeria-body h3{margin:0 0 8px;color:#11151B;font-size:17px;}
.galeria-meta{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;}
.galeria-meta span{background:#e5e7eb;color:#334155;padding:5px 8px;border-radius:999px;font-size:12px;font-weight:800;}
.galeria-actions{display:flex;gap:8px;flex-wrap:wrap;}
.galeria-actions form{display:inline;}
.galeria-mini-btn{border:0;border-radius:10px;padding:8px 10px;background:#242A30;color:white;font-weight:800;cursor:pointer;text-decoration:none;font-size:13px;}
.galeria-mini-btn.off{background:#64748b;}
.galeria-mini-btn.danger{background:#dc2626;}
.galeria-preview-edit{width:100%;height:170px;object-fit:cover;border-radius:16px;margin-bottom:12px;box-shadow:0 10px 26px rgba(0,0,0,.10);}
.galeria-empty{padding:30px;text-align:center;background:#f8fafc;border-radius:22px;color:#64748b;font-weight:800;}

@media(max-width:1050px){
    .galeria-admin-grid{grid-template-columns:1fr;}
    .galeria-hero-admin{flex-direction:column;align-items:flex-start;}
    .galeria-hero-stats{width:100%;flex-wrap:wrap;}
}
</style>

<div class="galeria-admin-page">

    <div class="galeria-hero-admin">
        <div>
            <span>Homepage</span>
            <h1>Galeria da Homepage</h1>
            <p>Gerir as imagens da secção “A freguesia em imagens”.</p>
        </div>

        <div class="galeria-hero-stats">
            <div>
                <strong><?= count($imagens) ?></strong>
                <small>Total</small>
            </div>
            <div>
                <strong><?= (int)$totalAtivas ?></strong>
                <small>Ativas</small>
            </div>
        </div>
    </div>

    <?php if ($ok): ?>
        <div class="galeria-alert ok"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="galeria-alert erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="galeria-admin-grid">

        <div class="galeria-admin-card">
            <h2><?= $editar ? 'Editar imagem' : 'Adicionar imagem' ?></h2>

            <?php if ($editar && !empty($editar["imagem"])): ?>
                <img class="galeria-preview-edit" src="../assets/img/<?= htmlspecialchars($editar["imagem"]) ?>" alt="">
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="galeria-form">
                <input type="hidden" name="acao" value="<?= $editar ? 'editar' : 'adicionar' ?>">

                <?php if ($editar): ?>
                    <input type="hidden" name="id" value="<?= (int)$editar["id"] ?>">
                <?php endif; ?>

                <label>Título</label>
                <input type="text" name="titulo" placeholder="Ex: Património local" value="<?= htmlspecialchars($editar["titulo"] ?? "") ?>" required>

                <label>Ordem</label>
                <input type="number" name="ordem" value="<?= htmlspecialchars($editar["ordem"] ?? (count($imagens) + 1)) ?>" min="0">

                <label><?= $editar ? 'Substituir imagem' : 'Imagem' ?></label>
                <input type="file" name="imagem" accept="image/*" <?= $editar ? '' : 'required' ?>>

                <div class="galeria-check">
                    <input type="checkbox" name="ativo" id="ativo" <?= !isset($editar["ativo"]) || (int)$editar["ativo"] === 1 ? "checked" : "" ?>>
                    <label for="ativo" style="margin:0;">Imagem ativa na homepage</label>
                </div>

                <div class="galeria-form-actions">
                    <button class="galeria-btn" type="submit">
                        <?= $editar ? 'Guardar alterações' : 'Adicionar imagem' ?>
                    </button>

                    <?php if ($editar): ?>
                        <a class="galeria-btn secondary" href="homepage_galeria.php">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="galeria-admin-card">
            <h2>Imagens atuais</h2>

            <?php if (!empty($imagens)): ?>
                <div class="galeria-lista">
                    <?php foreach ($imagens as $img): ?>
                        <article class="galeria-item">
                            <div class="galeria-img">
                                <?php if (!empty($img["imagem"])): ?>
                                    <img src="../assets/img/<?= htmlspecialchars($img["imagem"]) ?>" alt="<?= htmlspecialchars($img["titulo"]) ?>">
                                <?php endif; ?>

                                <span class="galeria-badge <?= (int)$img["ativo"] === 1 ? "" : "off" ?>">
                                    <?= (int)$img["ativo"] === 1 ? "Ativa" : "Inativa" ?>
                                </span>
                            </div>

                            <div class="galeria-body">
                                <h3><?= htmlspecialchars($img["titulo"]) ?></h3>

                                <div class="galeria-meta">
                                    <span>Ordem: <?= (int)$img["ordem"] ?></span>
                                    <span>ID: <?= (int)$img["id"] ?></span>
                                </div>

                                <div class="galeria-actions">
                                    <a class="galeria-mini-btn" href="homepage_galeria.php?editar=<?= (int)$img["id"] ?>">Editar</a>

                                    <form method="POST">
                                        <input type="hidden" name="acao" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int)$img["id"] ?>">
                                        <button class="galeria-mini-btn off" type="submit">
                                            <?= (int)$img["ativo"] === 1 ? "Desativar" : "Ativar" ?>
                                        </button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Tem a certeza que pretende eliminar esta imagem?');">
                                        <input type="hidden" name="acao" value="eliminar">
                                        <input type="hidden" name="id" value="<?= (int)$img["id"] ?>">
                                        <button class="galeria-mini-btn danger" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="galeria-empty">Ainda não existem imagens na galeria da homepage.</div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once "includes/footer.php"; ?>
