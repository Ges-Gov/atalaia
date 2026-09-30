<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/separadores.php";

$uploadDir = "../uploads/separadores/";
$erro = "";

// Garantir a tabela (defensivo — também vem no sqlfix)
$pdo->exec("CREATE TABLE IF NOT EXISTS separadores_fundo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(60) NOT NULL UNIQUE,
    imagem VARCHAR(255) DEFAULT NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Remover imagem
if (isset($_GET['remover'])) {
    $chave = $_GET['remover'];
    $stmt = $pdo->prepare("SELECT imagem FROM separadores_fundo WHERE chave = ?");
    $stmt->execute([$chave]);
    $img = $stmt->fetchColumn();
    if ($img && is_file($uploadDir . $img)) {
        @unlink($uploadDir . $img);
    }
    $pdo->prepare("UPDATE separadores_fundo SET imagem = NULL WHERE chave = ?")->execute([$chave]);
    header("Location: separadores-fundo.php?ok=1");
    exit;
}

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chave = $_POST['chave'] ?? '';
    if (!isset($GLOBALS['SEPARADORES_FUNDO'][$chave])) {
        $erro = "Separador inválido.";
    } elseif (empty($_FILES['imagem']['name'])) {
        $erro = "Escolha uma imagem.";
    } else {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $erro = "Apenas JPG, PNG ou WEBP.";
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $nome = "sep-" . $chave . "-" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadDir . $nome)) {
                $stmt = $pdo->prepare("SELECT imagem FROM separadores_fundo WHERE chave = ?");
                $stmt->execute([$chave]);
                $old = $stmt->fetchColumn();
                if ($old && is_file($uploadDir . $old)) {
                    @unlink($uploadDir . $old);
                }
                $pdo->prepare("INSERT INTO separadores_fundo (chave, imagem) VALUES (?, ?) ON DUPLICATE KEY UPDATE imagem = VALUES(imagem)")
                    ->execute([$chave, $nome]);
                header("Location: separadores-fundo.php?ok=1");
                exit;
            } else {
                $erro = "Não foi possível enviar a imagem para o servidor.";
            }
        }
    }
}

$atual = [];
foreach ($pdo->query("SELECT chave, imagem FROM separadores_fundo")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $atual[$r['chave']] = $r['imagem'];
}

$adminPageTitle = "Fundos dos Separadores";
$adminActive = "separadores-fundo";
require_once "includes/header.php";
?>

<style>
.sep-intro{color:#475569;font-weight:700;margin:0 0 18px;max-width:760px;}
.sep-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;}
.sep-card{background:white;border:1px solid #e5e7eb;border-radius:20px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,.06);display:flex;flex-direction:column;}

/* Pré-visualização: o nome do separador vai SOBRE a imagem, em branco e com um véu
   escuro por baixo — tal como fica no site. Antes o título era escuro e ficava logo
   por baixo da fotografia: sobre fotos claras ou movimentadas era ilegível.
   O height fixo + object-fit impedem que a imagem estique o cartão. */
.sep-prev{position:relative;height:150px;flex:0 0 150px;overflow:hidden;background:linear-gradient(135deg,#242A30,#11151B);display:grid;place-items:center;color:rgba(255,255,255,.55);font-weight:800;}
.sep-prev img{width:100%;height:100%;object-fit:cover;display:block;}

.sep-title{position:absolute;left:0;right:0;bottom:0;padding:28px 14px 10px;
    background:linear-gradient(to top, rgba(0,0,0,.82), rgba(0,0,0,.35) 55%, transparent);
    color:#fff;font-weight:900;font-size:16px;line-height:1.25;
    text-shadow:0 1px 3px rgba(0,0,0,.6);}

.sep-body{padding:16px;display:flex;flex-direction:column;gap:10px;}

/* O input de ficheiro apanhava a regra genérica do admin.css (width:100%, padding:13px,
   borda) e ficava esmagado contra os botões. Aqui fica com respiro próprio. */
.sep-body input[type=file]{width:100%;font-size:13px;margin:0;padding:10px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;}
.sep-acts{display:flex;gap:8px;flex-wrap:wrap;margin:0;}
</style>

<div class="admin-topbar">
    <h1>Fundos dos Separadores</h1>
    <p>Defina uma imagem de fundo para o cabeçalho (hero) de cada separador do site. A imagem fica com um véu escuro por cima para o texto continuar legível. Sem imagem, mantém-se o fundo padrão.</p>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="alerta-sucesso">Alterações guardadas com sucesso.</div>
<?php endif; ?>
<?php if ($erro !== ''): ?>
    <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="sep-grid">
    <?php foreach ($GLOBALS['SEPARADORES_FUNDO'] as $chave => $nome): ?>
        <?php $img = $atual[$chave] ?? null; ?>
        <div class="sep-card">
            <div class="sep-prev">
                <?php if (!empty($img)): ?>
                    <img src="/uploads/separadores/<?= htmlspecialchars($img) ?>" alt="">
                <?php else: ?>
                    Sem imagem
                <?php endif; ?>

                <!-- O nome vai SOBRE a imagem, em branco sobre véu escuro: é assim que
                     o separador fica no site, e garante que se lê sempre. -->
                <span class="sep-title"><?= htmlspecialchars($nome) ?></span>
            </div>
            <div class="sep-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="chave" value="<?= htmlspecialchars($chave) ?>">
                    <input type="file" name="imagem" accept="image/*" required>
                    <div class="sep-acts">
                        <button class="btn"><?= !empty($img) ? 'Substituir' : 'Carregar' ?></button>
                        <?php if (!empty($img)): ?>
                            <a class="btn danger" href="separadores-fundo.php?remover=<?= htmlspecialchars($chave) ?>" onclick="return confirm('Remover a imagem de fundo deste separador?')">Remover</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once "includes/footer.php"; ?>
