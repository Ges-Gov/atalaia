<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/eventos_campos.php";

$eventoId = (int)($_GET['evento_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$eventoId]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    die("Evento não encontrado.");
}

$erro = '';
$voltar = "evento_campos.php?evento_id=" . $eventoId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $campoId = (int)($_POST['campo_id'] ?? 0);

    if ($acao === 'adicionar' || $acao === 'guardar') {
        [$label, $tipo, $opcoes, $erro] = eventoCampoDefinicao($_POST['label'] ?? '', $_POST['tipo'] ?? '', $_POST['opcoes'] ?? '');
        $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;

        if (!$erro && $label === '') {
            $erro = "Escreva o nome do campo.";
        }
        if (!$erro && $acao === 'adicionar') {
            $stmtOrdem = $pdo->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM eventos_campos_extra WHERE evento_id = ?");
            $stmtOrdem->execute([$eventoId]);
            $ordem = (int)$stmtOrdem->fetchColumn();

            $ins = $pdo->prepare("INSERT INTO eventos_campos_extra (evento_id, label, tipo, opcoes, obrigatorio, ordem) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$eventoId, $label, $tipo, $opcoes, $obrigatorio, $ordem]);
        } elseif (!$erro) {
            $upd = $pdo->prepare("UPDATE eventos_campos_extra SET label = ?, tipo = ?, opcoes = ?, obrigatorio = ? WHERE id = ? AND evento_id = ?");
            $upd->execute([$label, $tipo, $opcoes, $obrigatorio, $campoId, $eventoId]);
        }
    }

    if ($acao === 'subir' || $acao === 'descer') {
        // Renumera tudo (1, 2, 3...) e troca o campo com o vizinho.
        $ids = $pdo->prepare("SELECT id FROM eventos_campos_extra WHERE evento_id = ? ORDER BY ordem ASC, id ASC");
        $ids->execute([$eventoId]);
        $ids = $ids->fetchAll(PDO::FETCH_COLUMN);
        $pos = array_search($campoId, array_map('intval', $ids), true);
        $vizinho = $acao === 'subir' ? $pos - 1 : $pos + 1;
        if ($pos !== false && isset($ids[$vizinho])) {
            [$ids[$pos], $ids[$vizinho]] = [$ids[$vizinho], $ids[$pos]];
            $upd = $pdo->prepare("UPDATE eventos_campos_extra SET ordem = ? WHERE id = ? AND evento_id = ?");
            foreach ($ids as $i => $idCampo) $upd->execute([$i + 1, $idCampo, $eventoId]);
        }
    }

    if ($acao === 'apagar') {
        $del = $pdo->prepare("DELETE FROM eventos_campos_extra WHERE id = ? AND evento_id = ?");
        $del->execute([$campoId, $eventoId]);
    }

    if (!$erro) {
        header("Location: " . $voltar . "&ok=1");
        exit;
    }
}

$campos = $pdo->prepare("SELECT * FROM eventos_campos_extra WHERE evento_id = ? ORDER BY ordem ASC, id ASC");
$campos->execute([$eventoId]);
$campos = $campos->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = "Campos extra — " . $evento['titulo'];
$adminActive = "eventos";
require_once "includes/header.php";

/** Linha de edição de um campo (também usada, vazia, para adicionar). */
function campoExtraForm(array $c, string $acao): void
{
    $tipo = eventoCampoTipo($c['tipo'] ?? 'texto');
    $sufixo = (int)($c['id'] ?? 0) ?: 'Novo';
    ?>
    <form method="POST" class="campos-extra-form">
        <input type="hidden" name="acao" value="<?= $acao ?>">
        <input type="hidden" name="campo_id" value="<?= (int)($c['id'] ?? 0) ?>">
        <div class="cx-cel">
            <label for="cxNome<?= $sufixo ?>">Nome do campo</label>
            <input type="text" id="cxNome<?= $sufixo ?>" name="label" placeholder="Ex.: Precisa de almoço?" value="<?= htmlspecialchars($c['label'] ?? '') ?>" required>
        </div>
        <div class="cx-cel">
            <label for="cxTipo<?= $sufixo ?>">Tipo</label>
            <select id="cxTipo<?= $sufixo ?>" name="tipo" class="campos-extra-tipo">
                <?php foreach (EVENTO_CAMPO_TIPOS as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $valor === $tipo ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="cx-obrig"><input type="checkbox" name="obrigatorio" <?= !empty($c['obrigatorio']) ? 'checked' : '' ?>> Obrigatório</label>
        <button class="btn" type="submit"><?= $acao === 'adicionar' ? '<i class="bi bi-plus-lg"></i> Adicionar' : 'Guardar' ?></button>
        <div class="cx-cel campos-extra-opcoes" <?= $tipo === 'escolha' ? '' : 'hidden' ?>>
            <label for="cxOpcoes<?= $sufixo ?>">Opções — uma por linha</label>
            <textarea id="cxOpcoes<?= $sufixo ?>" name="opcoes" rows="3" placeholder="Ex.:&#10;Paragem da igreja&#10;Paragem do largo"><?= htmlspecialchars(implode("
", eventoCampoOpcoes($c))) ?></textarea>
        </div>
    </form>
    <?php
}
?>

<style>
.campos-extra-box{background:white;border:1px solid #eef2f7;border-radius:20px;padding:22px;box-shadow:0 14px 34px rgba(0,0,0,.06);margin-bottom:20px;}
.campos-extra-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;margin-bottom:10px;}
.campos-extra-box .campos-extra-form{display:grid;grid-template-columns:minmax(0,1fr) 220px auto auto;gap:12px;align-items:end;margin:0;padding:0;background:none;border:0;box-shadow:none;border-radius:0;}
.campos-extra-box .campos-extra-novo{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:14px;padding:14px 16px;}
.cx-cel{display:flex;flex-direction:column;gap:5px;min-width:0;}
.cx-cel label{font-weight:800;color:#475569;font-size:12px;}
.campos-extra-form input[type=text],.campos-extra-form select,.campos-extra-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:white;border-radius:12px;padding:10px 12px;font-weight:700;font-family:inherit;margin:0;}
.campos-extra-form .cx-obrig{display:flex;align-items:center;gap:6px;font-weight:800;color:#11151B;white-space:nowrap;padding-bottom:11px;}
.campos-extra-form .cx-obrig input{width:auto;margin:0;}
.campos-extra-form .btn{white-space:nowrap;}
.campos-extra-opcoes{grid-column:1/-1;}
.campos-extra-opcoes[hidden]{display:none;}
.campos-extra-acoes{display:flex;gap:6px;align-items:center;justify-content:flex-end;min-width:196px;}
.campos-extra-acoes form{margin:0;padding:0;background:none;border:0;box-shadow:none;}
.campos-extra-acoes .btn{padding:9px 11px;}
.campos-extra-alerta{background:#dcfce7;color:#166534;padding:12px 16px;border-radius:14px;font-weight:800;margin-bottom:16px;}
.campos-extra-alerta.erro{background:#fee2e2;color:#991b1b;}
.campos-extra-tipos{color:#6b7280;font-weight:700;font-size:13px;margin:0 0 16px;}
@media(max-width:900px){.campos-extra-box .campos-extra-form{grid-template-columns:1fr 1fr;}.campos-extra-item{grid-template-columns:1fr;}.campos-extra-acoes{justify-content:flex-start;min-width:0;}}
</style>

<div class="admin-actions">
    <a class="btn secondary" href="editar_evento.php?id=<?= $eventoId ?>"><i class="bi bi-arrow-left"></i> Voltar ao evento</a>
</div>

<?php if ($erro): ?>
    <div class="campos-extra-alerta erro"><?= htmlspecialchars($erro) ?></div>
<?php elseif (isset($_GET['ok'])): ?>
    <div class="campos-extra-alerta">Campos do formulário guardados.</div>
<?php endif; ?>

<div class="campos-extra-box">
    <h3 style="margin-top:0;"><?= htmlspecialchars($evento['titulo']) ?> — Campos extra da inscrição</h3>
    <p style="color:#6b7280;font-weight:700;">Além de nome, email, telefone, nº de pessoas e observações, este evento pode pedir informação própria (ex.: "Precisa de almoço?", "Em que paragem apanha o autocarro?", "Data de nascimento").</p>
    <p class="campos-extra-tipos">Tipos: <strong>texto curto</strong> (uma linha), <strong>texto longo</strong> (várias linhas), <strong>número</strong>, <strong>data</strong>, <strong>escolha</strong> (uma opção de uma lista que escreve), <strong>sim/não</strong> (caixa para marcar — se for obrigatória, tem de ser marcada, ex.: "Li e aceito o regulamento") e <strong>foto/ficheiro</strong> (fotografia ou PDF, até <?= EVENTO_FICHEIRO_MAX_MB ?> MB, que só se vê aqui no backoffice).</p>

    <?php if (!empty($campos)): ?>
        <?php foreach ($campos as $i => $c): ?>
            <div class="campos-extra-item">
                <?php campoExtraForm($c, 'guardar'); ?>
                <div class="campos-extra-acoes">
                    <?php foreach (['subir' => 'bi-arrow-up', 'descer' => 'bi-arrow-down'] as $acao => $icone):
                        if (($acao === 'subir' && $i === 0) || ($acao === 'descer' && $i === count($campos) - 1)) continue; ?>
                        <form method="POST">
                            <input type="hidden" name="acao" value="<?= $acao ?>"><input type="hidden" name="campo_id" value="<?= (int)$c['id'] ?>">
                            <button class="btn secondary" type="submit" title="<?= $acao === 'subir' ? 'Passar para cima' : 'Passar para baixo' ?>"><i class="bi <?= $icone ?>"></i></button>
                        </form>
                    <?php endforeach; ?>
                    <form method="POST" onsubmit="return confirm('Remover este campo? As respostas já dadas mantêm-se guardadas, mas o campo deixa de aparecer no formulário.')">
                        <input type="hidden" name="acao" value="apagar"><input type="hidden" name="campo_id" value="<?= (int)$c['id'] ?>">
                        <button class="btn danger" type="submit">Remover</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#94a3b8;font-weight:700;">Ainda não há campos extra — o formulário usa só os campos fixos.</p>
    <?php endif; ?>

    <h4 style="margin:22px 0 10px;">Adicionar campo</h4>
    <div class="campos-extra-novo">
    <?php campoExtraForm($erro && ($_POST['acao'] ?? '') === 'adicionar'
        ? array_merge($_POST, ['opcoes' => json_encode(preg_split('/\R/u', (string)($_POST['opcoes'] ?? '')))]) : [], 'adicionar'); ?>
    </div>
</div>

<script>
// As opções só aparecem quando o tipo é «escolha».
document.querySelectorAll('.campos-extra-tipo').forEach(function (sel) {
    sel.addEventListener('change', function () {
        var caixa = sel.form.querySelector('.campos-extra-opcoes');
        caixa.hidden = sel.value !== 'escolha';
        if (!caixa.hidden) caixa.querySelector('textarea').focus();
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
