<?php
$adminPageTitle = "Heráldica";
$adminActive = "heraldica";
require_once "includes/header.php";

$erro = '';
$sucesso = '';
$uploadDir = __DIR__ . "/../uploads/heraldica/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

function getHeraldicaConfig($pdo) {
    $stmt = $pdo->query("SELECT * FROM heraldica_pagina ORDER BY id ASC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        $pdo->query("
            INSERT INTO heraldica_pagina
            (hero_titulo, hero_subtitulo, titulo, texto_intro, imagem)
            VALUES
            ('Heráldica', 'Brasão, bandeira e elementos simbólicos da Freguesia de Atalaia e Alto Estanqueiro-Jardia.', 'Brasão da Freguesia de Atalaia e Alto Estanqueiro-Jardia', 'Texto introdutório.', 'heraldica-granho.webp')
        ");
        $stmt = $pdo->query("SELECT * FROM heraldica_pagina ORDER BY id ASC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $config;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'config') {
        $id = (int)($_POST['id'] ?? 0);
        $hero_kicker = trim($_POST['hero_kicker'] ?? '');
        $hero_titulo = trim($_POST['hero_titulo'] ?? '');
        $hero_subtitulo = trim($_POST['hero_subtitulo'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $texto_intro = trim($_POST['texto_intro'] ?? '');
        $fonte_texto = trim($_POST['fonte_texto'] ?? '');
        $fonte_url = trim($_POST['fonte_url'] ?? '');
        $imagemAtual = $_POST['imagem_atual'] ?? '';

        if (!empty($_FILES['imagem']['name'])) {
            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $erro = "Formato inválido. Use JPG, PNG ou WEBP.";
            } else {
                $novo = "heraldica_" . date("YmdHis") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadDir . $novo)) {
                    $imagemAtual = $novo;
                } else {
                    $erro = "Erro ao enviar imagem.";
                }
            }
        }

        if (!$hero_titulo || !$titulo) {
            $erro = "O título principal e o título do bloco são obrigatórios.";
        }

        if (!$erro) {
            $stmt = $pdo->prepare("
                UPDATE heraldica_pagina
                SET hero_kicker=?, hero_titulo=?, hero_subtitulo=?, titulo=?, texto_intro=?, imagem=?, fonte_texto=?, fonte_url=?, atualizado_em=NOW()
                WHERE id=?
            ");
            $stmt->execute([$hero_kicker,$hero_titulo,$hero_subtitulo,$titulo,$texto_intro,$imagemAtual,$fonte_texto,$fonte_url,$id]);
            $sucesso = "Conteúdo principal atualizado.";
        }
    }

    if ($acao === 'elemento') {
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $icone = trim($_POST['icone'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!$titulo) {
            $erro = "O título do elemento é obrigatório.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE heraldica_elementos SET titulo=?, descricao=?, icone=?, ordem=?, ativo=?, atualizado_em=NOW() WHERE id=?");
                $stmt->execute([$titulo,$descricao,$icone,$ordem,$ativo,$id]);
                $sucesso = "Elemento atualizado.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO heraldica_elementos (titulo, descricao, icone, ordem, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$titulo,$descricao,$icone,$ordem,$ativo]);
                $sucesso = "Elemento criado.";
            }
        }
    }
}

if (isset($_GET['apagar'])) {
    $stmt = $pdo->prepare("DELETE FROM heraldica_elementos WHERE id=?");
    $stmt->execute([(int)$_GET['apagar']]);
    header("Location: heraldica.php");
    exit;
}

if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE heraldica_elementos SET ativo=IF(ativo=1,0,1), atualizado_em=NOW() WHERE id=?");
    $stmt->execute([(int)$_GET['toggle']]);
    header("Location: heraldica.php");
    exit;
}

$config = getHeraldicaConfig($pdo);

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM heraldica_elementos WHERE id=?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$elementos = $pdo->query("SELECT * FROM heraldica_elementos ORDER BY ordem ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$total = count($elementos);
$ativos = 0;
foreach($elementos as $e){ if($e['ativo']) $ativos++; }
?>

<style>
.heraldica-admin-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}
.heraldica-admin-hero h2{margin:8px 0;font-size:34px}.heraldica-admin-hero p{margin:0;color:#dbeafe}
.heraldica-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.heraldica-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px}
.heraldica-kpi,.heraldica-card-admin{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.06)}
.heraldica-kpi strong{font-size:32px;color:#11151B;display:block}.heraldica-kpi span{color:#64748b;font-weight:900}
.heraldica-grid-admin{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}
.heraldica-card-admin h3{margin:0 0 16px;color:#11151B;font-size:24px}
.heraldica-form{display:grid;gap:14px}.heraldica-form label{font-weight:900;color:#11151B;font-size:13px}
.heraldica-form input,.heraldica-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}
.heraldica-form textarea{min-height:120px;padding:14px;line-height:1.6}
.heraldica-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.heraldica-checks,.heraldica-actions{display:flex;gap:8px;flex-wrap:wrap}
.heraldica-checks label{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px 12px;border-radius:14px}.heraldica-checks input{width:auto;min-height:auto}
.heraldica-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.heraldica-alert.ok{background:#dcfce7;color:#166534}.heraldica-alert.err{background:#fee2e2;color:#991b1b}
.elemento-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;display:grid;grid-template-columns:54px 1fr auto;gap:14px;align-items:center;margin-bottom:12px}
.elemento-icon{width:54px;height:54px;border-radius:16px;background:#242A30;color:white;display:grid;place-items:center;font-size:24px}
.elemento-row h4{margin:0 0 5px;color:#11151B}.elemento-row p{margin:0;color:#64748b;font-weight:800;font-size:13px}
.badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}.badge.off{background:#fee2e2;color:#991b1b}
@media(max-width:1100px){.heraldica-grid-admin,.heraldica-admin-hero{grid-template-columns:1fr}.elemento-row{grid-template-columns:1fr}.heraldica-kpis{grid-template-columns:1fr}}
@media(max-width:700px){.heraldica-form-grid{grid-template-columns:1fr}}
</style>

<div class="heraldica-admin-hero">
    <div>
        <span class="heraldica-kicker">Identidade da Freguesia</span>
        <h2>Heráldica</h2>
        <p>Gerir brasão, descrição e elementos heráldicos da página pública.</p>
    </div>
    <a class="btn" href="../heraldica.php" target="_blank">Ver página pública</a>
</div>

<div class="heraldica-kpis">
    <div class="heraldica-kpi"><strong><?= (int)$total ?></strong><span>Total elementos</span></div>
    <div class="heraldica-kpi"><strong><?= (int)$ativos ?></strong><span>Elementos ativos</span></div>
    <div class="heraldica-kpi"><strong>100%</strong><span>Editável</span></div>
</div>

<?php if($sucesso): ?><div class="heraldica-alert ok"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if($erro): ?><div class="heraldica-alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="heraldica-card-admin" style="margin-bottom:24px;">
    <h3>Conteúdo principal da página</h3>

    <form method="POST" enctype="multipart/form-data" class="heraldica-form">
        <input type="hidden" name="acao" value="config">
        <input type="hidden" name="id" value="<?= (int)$config['id'] ?>">
        <input type="hidden" name="imagem_atual" value="<?= htmlspecialchars($config['imagem'] ?? '') ?>">

        <div class="heraldica-form-grid">
            <div><label>Kicker</label><input type="text" name="hero_kicker" value="<?= htmlspecialchars($config['hero_kicker'] ?? '') ?>"></div>
            <div><label>Título hero *</label><input type="text" name="hero_titulo" value="<?= htmlspecialchars($config['hero_titulo'] ?? '') ?>" required></div>
        </div>

        <div><label>Subtítulo hero</label><textarea name="hero_subtitulo"><?= htmlspecialchars($config['hero_subtitulo'] ?? '') ?></textarea></div>

        <div class="heraldica-form-grid">
            <div><label>Título bloco *</label><input type="text" name="titulo" value="<?= htmlspecialchars($config['titulo'] ?? '') ?>" required></div>
            <div><label>Imagem / Brasão</label><input type="file" name="imagem" accept="image/*"></div>
        </div>

        <div><label>Texto introdutório</label><textarea name="texto_intro"><?= htmlspecialchars($config['texto_intro'] ?? '') ?></textarea></div>

        <div class="heraldica-form-grid">
            <div><label>Texto da fonte</label><input type="text" name="fonte_texto" value="<?= htmlspecialchars($config['fonte_texto'] ?? '') ?>"></div>
            <div><label>URL da fonte</label><input type="url" name="fonte_url" value="<?= htmlspecialchars($config['fonte_url'] ?? '') ?>"></div>
        </div>

        <div class="heraldica-actions">
            <button class="btn" type="submit">Guardar conteúdo principal</button>
        </div>
    </form>
</div>

<div class="heraldica-grid-admin">
    <div class="heraldica-card-admin">
        <h3><?= $editar ? 'Editar elemento' : 'Novo elemento' ?></h3>

        <form method="POST" class="heraldica-form">
            <input type="hidden" name="acao" value="elemento">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? 0) ?>">

            <div><label>Título *</label><input type="text" name="titulo" value="<?= htmlspecialchars($editar['titulo'] ?? '') ?>" required></div>

            <div class="heraldica-form-grid">
                <div><label>Ícone (classe Bootstrap)</label><input type="text" name="icone" value="<?= htmlspecialchars($editar['icone'] ?? '') ?>" placeholder="Ex: bi-shield"></div>
                <div><label>Ordem</label><input type="number" name="ordem" value="<?= htmlspecialchars($editar['ordem'] ?? 0) ?>"></div>
            </div>

            <div><label>Descrição</label><textarea name="descricao"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea></div>

            <div class="heraldica-checks">
                <label><input type="checkbox" name="ativo" <?= !isset($editar['ativo']) || !empty($editar['ativo']) ? 'checked' : '' ?>> Ativo</label>
            </div>

            <div class="heraldica-actions">
                <button class="btn" type="submit"><?= $editar ? 'Guardar elemento' : 'Criar elemento' ?></button>
                <?php if($editar): ?><a class="btn secondary" href="heraldica.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="heraldica-card-admin">
        <h3>Elementos existentes</h3>

        <?php foreach($elementos as $e): ?>
            <div class="elemento-row">
                <div class="elemento-icon"><i class="bi <?= htmlspecialchars($e['icone'] ?: 'bi-shield') ?>"></i></div>
                <div>
                    <h4><?= htmlspecialchars($e['titulo']) ?></h4>
                    <p>
                        <span class="badge">Ordem <?= (int)$e['ordem'] ?></span>
                        <?php if(!$e['ativo']): ?><span class="badge off">Inativo</span><?php endif; ?>
                    </p>
                </div>
                <div class="heraldica-actions">
                    <a class="btn secondary" href="heraldica.php?editar=<?= (int)$e['id'] ?>">Editar</a>
                    <a class="btn secondary" href="heraldica.php?toggle=<?= (int)$e['id'] ?>"><?= $e['ativo'] ? 'Ocultar' : 'Mostrar' ?></a>
                    <a class="btn danger" href="heraldica.php?apagar=<?= (int)$e['id'] ?>" onclick="return confirm('Apagar este elemento?')">Apagar</a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(empty($elementos)): ?>
            <p>Ainda não existem elementos.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
