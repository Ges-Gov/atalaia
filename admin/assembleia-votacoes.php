<?php
$adminPageTitle = "Votações da Assembleia";
$adminActive = "assembleia_votacoes";
require_once "includes/header.php";
requireAssembleiaManager();

$erro = '';
$sucesso = '';

function estadoVotacaoLabel($estado) {
    if ($estado === 'aberta') return 'Aberta';
    if ($estado === 'fechada') return 'Fechada';
    return 'Rascunho';
}

function totaisVotacao($pdo, $id) {
    $stmt = $pdo->prepare("SELECT voto, COUNT(*) total FROM assembleia_votos WHERE votacao_id=? GROUP BY voto");
    $stmt->execute([$id]);
    $out = ['favor'=>0,'contra'=>0,'abstencao'=>0,'total'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[$r['voto']] = (int)$r['total'];
        $out['total'] += (int)$r['total'];
    }
    return $out;
}

function resultadoFinal($pdo, $id) {
    $t = totaisVotacao($pdo, $id);
    if ($t['favor'] > $t['contra']) return 'Aprovada';
    if ($t['contra'] > $t['favor']) return 'Rejeitada';
    return 'Empatada';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $sessaoId = (int)($_POST['sessao_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $estado = $_POST['estado'] ?? 'rascunho';
        $tipo = $_POST['tipo_votacao'] ?? 'publica';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!$sessaoId || !$titulo) {
            $erro = "Escolha a sessão e escreva o título.";
        } else {
            if ($id > 0) {
                $resultado = $estado === 'fechada' ? resultadoFinal($pdo, $id) : null;
                $stmt = $pdo->prepare("UPDATE assembleia_votacoes SET sessao_id=?,titulo=?,descricao=?,estado=?,tipo_votacao=?,resultado_final=?,ativo=?,atualizado_em=NOW() WHERE id=?");
                $stmt->execute([$sessaoId,$titulo,$descricao,$estado,$tipo,$resultado,$ativo,$id]);
                $sucesso = "Votação atualizada.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO assembleia_votacoes (sessao_id,titulo,descricao,estado,tipo_votacao,ativo) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$sessaoId,$titulo,$descricao,$estado,$tipo,$ativo]);
                $sucesso = "Votação criada.";
            }
        }
    }

    if ($acao === 'estado') {
        $id = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? 'rascunho';
        if ($id && in_array($estado, ['rascunho','aberta','fechada'])) {
            $resultado = $estado === 'fechada' ? resultadoFinal($pdo, $id) : null;
            $stmt = $pdo->prepare("UPDATE assembleia_votacoes SET estado=?,resultado_final=?,atualizado_em=NOW() WHERE id=?");
            $stmt->execute([$estado,$resultado,$id]);
            $sucesso = "Estado atualizado.";
        }
    }

    if ($acao === 'apagar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM assembleia_votos WHERE votacao_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM assembleia_votacoes WHERE id=?")->execute([$id]);
            $sucesso = "Votação apagada.";
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM assembleia_votacoes WHERE id=?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$sessoes = $pdo->query("SELECT id,titulo,data_sessao,hora_sessao FROM assembleia_sessoes WHERE ativo=1 ORDER BY data_sessao DESC")->fetchAll(PDO::FETCH_ASSOC);
$votacoes = $pdo->query("SELECT v.*, s.titulo sessao_titulo, s.data_sessao FROM assembleia_votacoes v LEFT JOIN assembleia_sessoes s ON s.id=v.sessao_id ORDER BY v.criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.vote-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}
.vote-hero h2{margin:8px 0;font-size:34px}.vote-hero p{margin:0;color:#dbeafe}
.vote-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.vote-grid{display:grid;grid-template-columns:390px 1fr;gap:24px;align-items:start}
.vote-card{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.06)}
.vote-card h3{margin:0 0 16px;color:#11151B;font-size:24px}
.vote-form{display:grid;gap:14px}.vote-form label{font-weight:900;color:#11151B;font-size:13px}
.vote-form input,.vote-form select,.vote-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}
.vote-form textarea{min-height:110px;padding:14px}
.vote-item{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:16px;margin-bottom:12px;display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center}
.vote-item h4{margin:0 0 8px;color:#11151B}.vote-item p{margin:0;color:#64748b;font-weight:800;font-size:13px}
.vote-badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}
.vote-results{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.vote-results span{background:white;border:1px solid #e5e7eb;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:900;color:#334155}
.vote-actions{display:flex;gap:8px;flex-wrap:wrap}.vote-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.ok{background:#dcfce7;color:#166534}.err{background:#fee2e2;color:#991b1b}
@media(max-width:1100px){.vote-grid,.vote-hero{grid-template-columns:1fr}.vote-item{grid-template-columns:1fr}}
</style>

<div class="vote-hero">
    <div><span class="vote-kicker">Parlamento Digital</span><h2>Votações da Assembleia</h2><p>Crie votações por sessão, abra/feche votação e acompanhe resultados.</p></div>
    <a class="btn" href="assembleia-sessoes.php">Sessões</a>
</div>

<?php if ($sucesso): ?><div class="vote-alert ok"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="vote-alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="vote-grid">
    <div class="vote-card">
        <h3><?= $editar ? 'Editar votação' : 'Nova votação' ?></h3>
        <form method="POST" class="vote-form">
            <input type="hidden" name="acao" value="guardar">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? 0) ?>">

            <label>Sessão *</label>
            <select name="sessao_id" required>
                <option value="">Escolher sessão</option>
                <?php foreach ($sessoes as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (($editar['sessao_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['titulo']) ?> — <?= date('d/m/Y', strtotime($s['data_sessao'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Título *</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($editar['titulo'] ?? '') ?>" required>

            <label>Descrição</label>
            <textarea name="descricao"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea>

            <label>Estado</label>
            <select name="estado">
                <option value="rascunho" <?= (($editar['estado'] ?? '') === 'rascunho') ? 'selected' : '' ?>>Rascunho</option>
                <option value="aberta" <?= (($editar['estado'] ?? '') === 'aberta') ? 'selected' : '' ?>>Aberta</option>
                <option value="fechada" <?= (($editar['estado'] ?? '') === 'fechada') ? 'selected' : '' ?>>Fechada</option>
            </select>

            <label>Tipo</label>
            <select name="tipo_votacao">
                <option value="publica" <?= (($editar['tipo_votacao'] ?? '') === 'publica') ? 'selected' : '' ?>>Pública</option>
                <option value="secreta" <?= (($editar['tipo_votacao'] ?? '') === 'secreta') ? 'selected' : '' ?>>Secreta</option>
            </select>

            <label style="font-weight:900;"><input type="checkbox" name="ativo" <?= !isset($editar['ativo']) || !empty($editar['ativo']) ? 'checked' : '' ?>> Ativa</label>

            <div class="vote-actions">
                <button class="btn" type="submit"><?= $editar ? 'Guardar' : 'Criar votação' ?></button>
                <?php if ($editar): ?><a class="btn secondary" href="assembleia-votacoes.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="vote-card">
        <h3>Votações existentes</h3>
        <?php foreach ($votacoes as $v): ?>
            <?php $totais = totaisVotacao($pdo, $v['id']); ?>
            <div class="vote-item">
                <div>
                    <h4><?= htmlspecialchars($v['titulo']) ?></h4>
                    <p><?= htmlspecialchars($v['sessao_titulo'] ?: 'Sem sessão') ?> <?= !empty($v['data_sessao']) ? '· '.date('d/m/Y', strtotime($v['data_sessao'])) : '' ?></p>
                    <p><span class="vote-badge"><?= htmlspecialchars(estadoVotacaoLabel($v['estado'])) ?></span><span class="vote-badge"><?= htmlspecialchars($v['tipo_votacao']) ?></span><?php if (!empty($v['resultado_final'])): ?><span class="vote-badge"><?= htmlspecialchars($v['resultado_final']) ?></span><?php endif; ?></p>
                    <div class="vote-results"><span><i class="bi bi-hand-thumbs-up"></i> Favor: <?= (int)$totais['favor'] ?></span><span><i class="bi bi-hand-thumbs-down"></i> Contra: <?= (int)$totais['contra'] ?></span><span><i class="bi bi-dash"></i> Abstenção: <?= (int)$totais['abstencao'] ?></span><span><i class="bi bi-receipt"></i> Total: <?= (int)$totais['total'] ?></span></div>
                </div>
                <div class="vote-actions">
                    <a class="btn secondary" href="assembleia-votacoes.php?editar=<?= (int)$v['id'] ?>">Editar</a>
                    <form method="POST"><input type="hidden" name="acao" value="estado"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="estado" value="aberta"><button class="btn secondary" type="submit">Abrir</button></form>
                    <form method="POST"><input type="hidden" name="acao" value="estado"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="estado" value="fechada"><button class="btn secondary" type="submit">Fechar</button></form>
                    <form method="POST" onsubmit="return confirm('Apagar esta votação?')"><input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><button class="btn danger" type="submit">Apagar</button></form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($votacoes)): ?><p>Ainda não existem votações.</p><?php endif; ?>
    </div>
</div>
<?php require_once "includes/footer.php"; ?>
