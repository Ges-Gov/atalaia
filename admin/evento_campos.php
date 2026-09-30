<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$eventoId = (int)($_GET['evento_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$eventoId]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    die("Evento não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_campo'])) {
    $label = trim($_POST['label'] ?? '');
    $tipo = in_array($_POST['tipo'] ?? '', ['texto', 'checkbox'], true) ? $_POST['tipo'] : 'texto';
    $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;

    if ($label !== '') {
        $stmtOrdem = $pdo->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM eventos_campos_extra WHERE evento_id = ?");
        $stmtOrdem->execute([$eventoId]);
        $ordem = (int)$stmtOrdem->fetchColumn();

        $ins = $pdo->prepare("INSERT INTO eventos_campos_extra (evento_id, label, tipo, obrigatorio, ordem) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$eventoId, $label, $tipo, $obrigatorio, $ordem]);
    }

    header("Location: evento_campos.php?evento_id=" . $eventoId);
    exit;
}

if (isset($_GET['apagar'])) {
    $del = $pdo->prepare("DELETE FROM eventos_campos_extra WHERE id = ? AND evento_id = ?");
    $del->execute([(int)$_GET['apagar'], $eventoId]);
    header("Location: evento_campos.php?evento_id=" . $eventoId);
    exit;
}

$campos = $pdo->prepare("SELECT * FROM eventos_campos_extra WHERE evento_id = ? ORDER BY ordem ASC, id ASC");
$campos->execute([$eventoId]);
$campos = $campos->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = "Campos extra — " . $evento['titulo'];
$adminActive = "eventos";
require_once "includes/header.php";
?>

<style>
.campos-extra-box{background:white;border:1px solid #eef2f7;border-radius:20px;padding:22px;box-shadow:0 14px 34px rgba(0,0,0,.06);margin-bottom:20px;}
.campos-extra-item{display:flex;justify-content:space-between;align-items:center;gap:14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:12px 16px;margin-bottom:10px;}
.campos-extra-item strong{color:#11151B;}
.campos-extra-item small{display:block;color:#64748b;font-weight:700;margin-top:2px;}
.campos-extra-form{display:grid;grid-template-columns:1.6fr 1fr auto auto;gap:12px;align-items:center;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:16px;}
.campos-extra-form input[type=text]{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:white;border-radius:12px;padding:11px 13px;font-weight:700;}
.campos-extra-form select{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:white;border-radius:12px;padding:11px 13px;font-weight:700;}
.campos-extra-form label{display:flex;align-items:center;gap:6px;font-weight:800;color:#11151B;white-space:nowrap;}
@media(max-width:800px){.campos-extra-form{grid-template-columns:1fr 1fr;}}
</style>

<div class="admin-actions">
    <a class="btn secondary" href="editar_evento.php?id=<?= $eventoId ?>"><i class="bi bi-arrow-left"></i> Voltar ao evento</a>
</div>

<div class="campos-extra-box">
    <h3 style="margin-top:0;"><?= htmlspecialchars($evento['titulo']) ?> — Campos extra da inscrição</h3>
    <p style="color:#6b7280;font-weight:700;">Além de nome, email, telefone, nº de pessoas e observações, este evento pode pedir informação própria (ex.: "Precisa de almoço?", "Tem alguma restrição alimentar?").</p>

    <?php if (!empty($campos)): ?>
        <?php foreach ($campos as $c): ?>
            <div class="campos-extra-item">
                <div>
                    <strong><?= htmlspecialchars($c['label']) ?></strong>
                    <small><?= $c['tipo'] === 'checkbox' ? 'Checkbox (sim/não)' : 'Texto' ?><?= $c['obrigatorio'] ? ' · obrigatório' : '' ?></small>
                </div>
                <a class="btn danger" href="evento_campos.php?evento_id=<?= $eventoId ?>&apagar=<?= (int)$c['id'] ?>" onclick="return confirm('Remover este campo? As respostas já dadas mantêm-se guardadas, mas o campo deixa de aparecer no formulário.')">Remover</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#94a3b8;font-weight:700;">Ainda não há campos extra — o formulário usa só os campos fixos.</p>
    <?php endif; ?>

    <form method="POST" class="campos-extra-form" style="margin-top:16px;">
        <input type="text" name="label" placeholder="Ex.: Precisa de almoço?" required>
        <select name="tipo">
            <option value="texto">Texto</option>
            <option value="checkbox">Checkbox (sim/não)</option>
        </select>
        <label><input type="checkbox" name="obrigatorio" style="width:auto;"> Obrigatório</label>
        <button class="btn" type="submit" name="adicionar_campo" value="1">Adicionar campo</button>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>
