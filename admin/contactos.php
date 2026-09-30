<?php
$adminPageTitle = "Contactos";
$adminActive = "contactos";
require_once "includes/header.php";

$erro = '';
$sucesso = '';

function getContactosConfig($pdo) {
    $stmt = $pdo->query("SELECT * FROM contactos_pagina_config ORDER BY id ASC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$config) {
        $pdo->query("INSERT INTO contactos_pagina_config (hero_titulo, hero_subtitulo, bloco_titulo, bloco_texto) VALUES ('Contactos', 'Informações úteis e contactos importantes.', 'Junta de Freguesia', 'Texto introdutório.')");
        $stmt = $pdo->query("SELECT * FROM contactos_pagina_config ORDER BY id ASC LIMIT 1");
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
        $bloco_titulo = trim($_POST['bloco_titulo'] ?? '');
        $bloco_texto = trim($_POST['bloco_texto'] ?? '');
        $mapa_embed = trim($_POST['mapa_embed'] ?? '');
        $horario_titulo = trim($_POST['horario_titulo'] ?? '');
        $horario_texto = trim($_POST['horario_texto'] ?? '');

        if (!$hero_titulo) {
            $erro = "O título principal é obrigatório.";
        } else {
            $stmt = $pdo->prepare("UPDATE contactos_pagina_config SET hero_kicker=?, hero_titulo=?, hero_subtitulo=?, bloco_titulo=?, bloco_texto=?, mapa_embed=?, horario_titulo=?, horario_texto=?, atualizado_em=NOW() WHERE id=?");
            $stmt->execute([$hero_kicker,$hero_titulo,$hero_subtitulo,$bloco_titulo,$bloco_texto,$mapa_embed,$horario_titulo,$horario_texto,$id]);
            $sucesso = "Conteúdo principal atualizado.";
        }
    }

    if ($acao === 'contacto') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $morada = trim($_POST['morada'] ?? '');
        $horario = trim($_POST['horario'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $icone = trim($_POST['icone'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);
        $destaque = isset($_POST['destaque']) ? 1 : 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!$nome) {
            $erro = "O nome do contacto é obrigatório.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE contactos_uteis SET nome=?, categoria=?, telefone=?, email=?, morada=?, horario=?, descricao=?, icone=?, ordem=?, destaque=?, ativo=?, atualizado_em=NOW() WHERE id=?");
                $stmt->execute([$nome,$categoria,$telefone,$email,$morada,$horario,$descricao,$icone,$ordem,$destaque,$ativo,$id]);
                $sucesso = "Contacto atualizado.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO contactos_uteis (nome, categoria, telefone, email, morada, horario, descricao, icone, ordem, destaque, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome,$categoria,$telefone,$email,$morada,$horario,$descricao,$icone,$ordem,$destaque,$ativo]);
                $sucesso = "Contacto criado.";
            }
        }
    }
}

if (isset($_GET['apagar'])) {
    $stmt = $pdo->prepare("DELETE FROM contactos_uteis WHERE id=?");
    $stmt->execute([(int)$_GET['apagar']]);
    header("Location: contactos.php");
    exit;
}

if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE contactos_uteis SET ativo=IF(ativo=1,0,1), atualizado_em=NOW() WHERE id=?");
    $stmt->execute([(int)$_GET['toggle']]);
    header("Location: contactos.php");
    exit;
}

$config = getContactosConfig($pdo);

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM contactos_uteis WHERE id=?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$contactos = $pdo->query("SELECT * FROM contactos_uteis ORDER BY destaque DESC, ordem ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$total = count($contactos);
$ativos = 0;
$destaques = 0;
$categorias = [];
foreach ($contactos as $c) {
    if ($c['ativo']) $ativos++;
    if ($c['destaque']) $destaques++;
    $cat = trim($c['categoria'] ?? '');
    if ($cat !== '') $categorias[$cat] = true;
}
?>

<style>
.contactadmin-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}
.contactadmin-hero h2{margin:8px 0;font-size:34px}.contactadmin-hero p{margin:0;color:#dbeafe}
.contact-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.contact-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.contact-kpi,.contact-card-admin{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.06)}
.contact-kpi strong{font-size:32px;color:#11151B;display:block}.contact-kpi span{color:#64748b;font-weight:900}
.contact-grid-admin{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}
.contact-card-admin h3{margin:0 0 16px;color:#11151B;font-size:24px}
.contact-form{display:grid;gap:14px;background:none;padding:0;box-shadow:none;border-radius:0}.contact-form label{font-weight:900;color:#11151B;font-size:13px}
.contact-form input,.contact-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}
.contact-form textarea{min-height:110px;padding:14px;line-height:1.6}
.contact-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.contact-checks,.contact-actions{display:flex;gap:8px;flex-wrap:wrap}
.contact-checks label{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px 12px;border-radius:14px}.contact-checks input{width:auto;min-height:auto}
.contact-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.contact-alert.ok{background:#dcfce7;color:#166534}.contact-alert.err{background:#fee2e2;color:#991b1b}
.contact-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;display:grid;grid-template-columns:54px 1fr auto;gap:14px;align-items:center;margin-bottom:12px}
.contact-icon-admin{width:54px;height:54px;border-radius:16px;background:#242A30;color:white;display:grid;place-items:center;font-size:24px}
.contact-row h4{margin:0 0 5px;color:#11151B}.contact-row p{margin:0;color:#64748b;font-weight:800;font-size:13px}
.badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}.badge.off{background:#fee2e2;color:#991b1b}.badge.gold{background:#fef3c7;color:#92400e}
@media(max-width:1100px){.contact-grid-admin,.contactadmin-hero{grid-template-columns:1fr}.contact-kpis{grid-template-columns:1fr 1fr}.contact-row{grid-template-columns:1fr}}
@media(max-width:700px){.contact-kpis,.contact-form-grid{grid-template-columns:1fr}}
</style>

<div class="contactadmin-hero">
    <div><span class="contact-kicker">Centro de Contactos</span><h2>Contactos</h2><p>Gerir página pública, contactos úteis, categorias, mapa e horários.</p></div>
    <a class="btn" href="../contactos.php" target="_blank">Ver página pública</a>
</div>

<div class="contact-kpis">
    <div class="contact-kpi"><strong><?= (int)$total ?></strong><span>Total contactos</span></div>
    <div class="contact-kpi"><strong><?= (int)$ativos ?></strong><span>Ativos</span></div>
    <div class="contact-kpi"><strong><?= (int)$destaques ?></strong><span>Destaques</span></div>
    <div class="contact-kpi"><strong><?= (int)count($categorias) ?></strong><span>Categorias</span></div>
</div>

<?php if ($sucesso): ?><div class="contact-alert ok"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="contact-alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="contact-card-admin" style="margin-bottom:24px;">
    <h3>Conteúdo principal da página</h3>
    <form method="POST" class="contact-form">
        <input type="hidden" name="acao" value="config">
        <input type="hidden" name="id" value="<?= (int)$config['id'] ?>">
        <div class="contact-form-grid">
            <div><label>Kicker</label><input type="text" name="hero_kicker" value="<?= htmlspecialchars($config['hero_kicker'] ?? '') ?>"></div>
            <div><label>Título principal *</label><input type="text" name="hero_titulo" value="<?= htmlspecialchars($config['hero_titulo'] ?? '') ?>" required></div>
        </div>
        <div><label>Subtítulo do hero</label><textarea name="hero_subtitulo"><?= htmlspecialchars($config['hero_subtitulo'] ?? '') ?></textarea></div>
        <div class="contact-form-grid">
            <div><label>Título bloco institucional</label><input type="text" name="bloco_titulo" value="<?= htmlspecialchars($config['bloco_titulo'] ?? '') ?>"></div>
            <div><label>Texto bloco institucional</label><textarea name="bloco_texto"><?= htmlspecialchars($config['bloco_texto'] ?? '') ?></textarea></div>
        </div>
        <div class="contact-form-grid">
            <div><label>Título horário</label><input type="text" name="horario_titulo" value="<?= htmlspecialchars($config['horario_titulo'] ?? '') ?>"></div>
            <div><label>Texto horário</label><textarea name="horario_texto"><?= htmlspecialchars($config['horario_texto'] ?? '') ?></textarea></div>
        </div>
        <div><label>Google Maps iframe/embed</label><textarea name="mapa_embed" placeholder="<iframe ...></iframe>"><?= htmlspecialchars($config['mapa_embed'] ?? '') ?></textarea></div>
        <div class="contact-actions"><button class="btn" type="submit">Guardar conteúdo principal</button></div>
    </form>
</div>

<div class="contact-grid-admin">
    <div class="contact-card-admin">
        <h3><?= $editar ? 'Editar contacto' : 'Novo contacto' ?></h3>
        <form method="POST" class="contact-form">
            <input type="hidden" name="acao" value="contacto">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? 0) ?>">
            <div><label>Nome *</label><input type="text" name="nome" value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" required></div>
            <div class="contact-form-grid">
                <div><label>Categoria</label><input type="text" name="categoria" placeholder="Ex: Saúde, Segurança, Institucional" value="<?= htmlspecialchars($editar['categoria'] ?? '') ?>"></div>
                <div><label>Ícone (classe Bootstrap)</label><input type="text" name="icone" placeholder="Ex: bi-telephone" value="<?= htmlspecialchars($editar['icone'] ?? '') ?>"></div>
            </div>
            <div class="contact-form-grid">
                <div><label>Telefone</label><input type="text" name="telefone" value="<?= htmlspecialchars($editar['telefone'] ?? '') ?>"></div>
                <div><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editar['email'] ?? '') ?>"></div>
            </div>
            <div><label>Morada</label><textarea name="morada"><?= htmlspecialchars($editar['morada'] ?? '') ?></textarea></div>
            <div><label>Horário</label><textarea name="horario"><?= htmlspecialchars($editar['horario'] ?? '') ?></textarea></div>
            <div><label>Descrição</label><textarea name="descricao"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea></div>
            <div><label>Ordem</label><input type="number" name="ordem" value="<?= htmlspecialchars($editar['ordem'] ?? 0) ?>"></div>
            <div class="contact-checks">
                <label><input type="checkbox" name="destaque" <?= !empty($editar['destaque']) ? 'checked' : '' ?>> Destaque</label>
                <label><input type="checkbox" name="ativo" <?= !isset($editar['ativo']) || !empty($editar['ativo']) ? 'checked' : '' ?>> Ativo</label>
            </div>
            <div class="contact-actions">
                <button class="btn" type="submit"><?= $editar ? 'Guardar contacto' : 'Criar contacto' ?></button>
                <?php if ($editar): ?><a class="btn secondary" href="contactos.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="contact-card-admin">
        <h3>Contactos existentes</h3>
        <?php foreach($contactos as $c): ?>
            <div class="contact-row">
                <div class="contact-icon-admin"><i class="bi <?= htmlspecialchars(!empty($c['icone']) ? $c['icone'] : 'bi-telephone') ?>"></i></div>
                <div>
                    <h4><?= htmlspecialchars($c['nome']) ?></h4>
                    <p>
                        <?php if(!empty($c['categoria'])): ?><span class="badge"><?= htmlspecialchars($c['categoria']) ?></span><?php endif; ?>
                        <?php if(!empty($c['telefone'])): ?><span class="badge"><?= htmlspecialchars($c['telefone']) ?></span><?php endif; ?>
                        <?php if(!empty($c['email'])): ?><span class="badge"><?= htmlspecialchars($c['email']) ?></span><?php endif; ?>
                        <?php if(!$c['ativo']): ?><span class="badge off">Inativo</span><?php endif; ?>
                        <?php if($c['destaque']): ?><span class="badge gold">Destaque</span><?php endif; ?>
                    </p>
                </div>
                <div class="contact-actions">
                    <a class="btn secondary" href="contactos.php?editar=<?= (int)$c['id'] ?>">Editar</a>
                    <a class="btn secondary" href="contactos.php?toggle=<?= (int)$c['id'] ?>"><?= $c['ativo'] ? 'Ocultar' : 'Mostrar' ?></a>
                    <a class="btn danger" href="contactos.php?apagar=<?= (int)$c['id'] ?>" onclick="return confirm('Apagar este contacto?')">Apagar</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($contactos)): ?><p>Ainda não existem contactos.</p><?php endif; ?>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
