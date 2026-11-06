<?php
/*
 * Candidaturas à divulgação na Economia Local (Atalaia, 06/11/2026). Chegam pelo formulário de /comercio.php quando a
 * página está em modo de candidaturas (includes/comercio_candidaturas.php). Aqui a Junta vê os dados e a imagem,
 * muda o estado, escreve notas internas ou apaga.
 */
$adminPageTitle = "Candidaturas — Economia Local";
$adminActive = "comercio_candidaturas";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/comercio_candidaturas.php";

if (!(podeAceder('freguesia') || podeAceder('virtual'))) {
    die("Sem acesso a esta secção.");
}

if (empty($_SESSION['comercio_cand_admin_token'])) {
    $_SESSION['comercio_cand_admin_token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['comercio_cand_admin_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($token, (string)($_POST['token'] ?? ''))) {
    $id = (int)($_POST['id'] ?? 0);
    $acao = (string)($_POST['acao'] ?? '');
    if ($acao === 'guardar') {
        $estado = array_key_exists($_POST['estado'] ?? '', COMERCIO_CAND_ESTADOS) ? $_POST['estado'] : 'nova';
        $notas = mb_substr(trim((string)($_POST['notas'] ?? '')), 0, 5000);
        $pdo->prepare("UPDATE comercio_candidaturas SET estado = ?, notas = ?, atualizado_em = NOW() WHERE id = ?")->execute([$estado, $notas !== '' ? $notas : null, $id]);
        $_SESSION['cc_aviso'] = ['ok', 'Candidatura guardada.'];
    } elseif ($acao === 'apagar') {
        $st = $pdo->prepare("SELECT imagem FROM comercio_candidaturas WHERE id = ?");
        $st->execute([$id]);
        $img = comercioCandImagemNome($st->fetchColumn());
        if ($img !== '' && is_file(COMERCIO_CAND_DIR . $img)) @unlink(COMERCIO_CAND_DIR . $img);
        $pdo->prepare("DELETE FROM comercio_candidaturas WHERE id = ?")->execute([$id]);
        $_SESSION['cc_aviso'] = ['ok', 'Candidatura apagada.'];
    }
    header("Location: comercio-candidaturas.php" . ($acao === 'guardar' && $id ? "#candidatura-$id" : ''));
    exit;
}

$aviso = $_SESSION['cc_aviso'] ?? null;
unset($_SESSION['cc_aviso']);

$cands = [];
try {
    $cands = $pdo->query("SELECT * FROM comercio_candidaturas ORDER BY (estado = 'nova') DESC, criado_em DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo '<div class="content-box">A tabela das candidaturas ainda não existe (falta correr a migração 088).</div>';
}
$novas = count(array_filter($cands, fn($c) => $c['estado'] === 'nova'));
?>

<div class="content-box">
    <h3>Candidaturas à divulgação na Economia Local</h3>
    <p style="margin:0;color:#64748b">
        Pedidos enviados pelo formulário da página <a href="/comercio.php" target="_blank">Economia Local</a> do site.
        <?= count($cands) ?> no total<?= $novas ? ", <strong>$novas nova" . ($novas > 1 ? 's' : '') . "</strong>" : '' ?>.
        <?php if (comercioEmCandidaturas()): ?>
            A página está em modo de candidaturas: os estabelecimentos não aparecem no site.
        <?php endif; ?>
    </p>
</div>

<?php if ($aviso): ?>
    <div class="cc-aviso <?= $aviso[0] === 'ok' ? 'ok' : 'erro' ?>"><?= htmlspecialchars($aviso[1]) ?></div>
<?php endif; ?>

<div class="cc-lista">
    <?php foreach ($cands as $c): $img = comercioCandImagemNome($c['imagem']); ?>
        <details class="cc-item <?= $c['estado'] === 'nova' ? 'nova' : '' ?>" id="candidatura-<?= (int)$c['id'] ?>" <?= $c['estado'] === 'nova' ? 'open' : '' ?>>
            <summary>
                <span class="cc-nome"><?= $c['estado'] === 'nova' ? '<span class="cc-ponto" title="Nova"></span>' : '' ?><?= htmlspecialchars($c['nome_estabelecimento']) ?>
                    <small><?= htmlspecialchars($c['atividade']) ?></small></span>
                <span class="cc-meta"><span class="cc-estado cc-estado-<?= htmlspecialchars($c['estado']) ?>"><?= htmlspecialchars(COMERCIO_CAND_ESTADOS[$c['estado']] ?? $c['estado']) ?></span>
                    <?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></span>
            </summary>
            <div class="cc-corpo">
                <div class="cc-imagem">
                    <?php if ($img !== ''): ?>
                        <a href="comercio_candidatura_imagem.php?id=<?= (int)$c['id'] ?>" target="_blank" title="Abrir a imagem">
                            <img src="comercio_candidatura_imagem.php?id=<?= (int)$c['id'] ?>" alt="Imagem enviada por <?= htmlspecialchars($c['nome_estabelecimento']) ?>" loading="lazy">
                        </a>
                        <a class="cc-abrir" href="comercio_candidatura_imagem.php?id=<?= (int)$c['id'] ?>&amp;descarregar=1"><i class="bi bi-download"></i> Descarregar imagem</a>
                    <?php else: ?>
                        <div class="cc-sem-img"><i class="bi bi-image"></i> Sem imagem</div>
                    <?php endif; ?>
                </div>
                <div class="cc-dados">
                    <dl>
                        <dt>Morada / localidade</dt><dd><?= htmlspecialchars($c['morada']) ?></dd>
                        <dt>Responsável</dt><dd><?= htmlspecialchars($c['responsavel']) ?></dd>
                        <dt>Contactos</dt>
                        <dd><a href="mailto:<?= htmlspecialchars($c['email']) ?>?subject=<?= rawurlencode('Divulgação – Economia Local') ?>"><?= htmlspecialchars($c['email']) ?></a>
                            · <a href="tel:<?= htmlspecialchars($c['telefone']) ?>"><i class="bi bi-telephone"></i> <?= htmlspecialchars($c['telefone']) ?></a></dd>
                        <?php if (!empty($c['website'])): ?><dt>Website / redes</dt><dd><?= htmlspecialchars($c['website']) ?></dd><?php endif; ?>
                        <?php if (!empty($c['descricao'])): ?><dt>Descrição</dt><dd><?= nl2br(htmlspecialchars($c['descricao'])) ?></dd><?php endif; ?>
                    </dl>
                    <form method="post" class="cc-form">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <label>Estado
                            <select name="estado">
                                <?php foreach (COMERCIO_CAND_ESTADOS as $k => $rot): ?>
                                    <option value="<?= $k ?>" <?= $c['estado'] === $k ? 'selected' : '' ?>><?= $rot ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Notas internas <small>(só a equipa vê)</small>
                            <textarea name="notas" rows="2"><?= htmlspecialchars((string)$c['notas']) ?></textarea>
                        </label>
                        <div class="cc-botoes">
                            <button class="btn" name="acao" value="guardar"><i class="bi bi-check2"></i> Guardar</button>
                            <button class="btn danger" name="acao" value="apagar" onclick="return confirm('Apagar esta candidatura e a imagem? Não pode ser revertido.')">Apagar</button>
                        </div>
                    </form>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
    <?php if (!$cands): ?>
        <div class="content-box">Ainda não chegaram candidaturas.</div>
    <?php endif; ?>
</div>

<style>
.cc-aviso{padding:12px 16px;border-radius:12px;margin:12px 0;font-weight:700;}
.cc-aviso.ok{background:#dcfce7;color:#166534;}
.cc-aviso.erro{background:#fee2e2;color:#991b1b;}
.cc-lista{display:flex;flex-direction:column;gap:12px;margin-top:16px;}
.cc-item{background:#fff;border-radius:16px;box-shadow:0 6px 20px rgba(15,23,42,.06);border:1px solid #e5e7eb;}
.cc-item.nova{border-color:var(--cor-principal);}
.cc-item summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:16px;align-items:center;padding:16px 20px;flex-wrap:wrap;}
.cc-item summary::-webkit-details-marker{display:none;}
.cc-nome{font-weight:800;color:#11151B;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.cc-nome small{font-weight:600;color:#64748b;}
.cc-ponto{width:9px;height:9px;border-radius:50%;background:var(--cor-principal);display:inline-block;}
.cc-meta{font-size:13px;color:#64748b;display:flex;gap:10px;align-items:center;}
.cc-estado{border-radius:999px;padding:4px 10px;font-weight:800;font-size:12px;background:#f1f5f9;color:#334155;}
.cc-estado-nova{background:color-mix(in srgb, var(--cor-principal) 14%, #fff);color:var(--cor-principal);}
.cc-estado-aceite{background:#dcfce7;color:#166534;}
.cc-estado-recusada{background:#fee2e2;color:#991b1b;}
.cc-estado-analise{background:#fef3c7;color:#92400e;}
.cc-corpo{display:grid;grid-template-columns:280px 1fr;gap:22px;padding:4px 20px 20px;border-top:1px solid #eef2f7;}
.cc-imagem{padding-top:16px;}
.cc-imagem img{width:100%;aspect-ratio:3/2;object-fit:contain;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;}
.cc-abrir{display:inline-flex;gap:6px;margin-top:8px;font-size:13px;}
.cc-sem-img{aspect-ratio:3/2;display:grid;place-items:center;background:#f8fafc;border-radius:12px;color:#94a3b8;}
.cc-dados dl{display:grid;grid-template-columns:160px 1fr;gap:6px 14px;margin:16px 0 8px;}
.cc-dados dt{font-weight:800;color:#475569;font-size:14px;}
.cc-dados dd{margin:0;color:#1f2933;line-height:1.5;}
.cc-form{display:flex;flex-direction:column;gap:10px;margin-top:10px;}
.cc-form label{display:flex;flex-direction:column;gap:6px;font-weight:800;font-size:14px;color:#475569;}
.cc-form select{max-width:240px;}
.cc-botoes{display:flex;gap:10px;flex-wrap:wrap;}
@media(max-width:820px){.cc-corpo{grid-template-columns:1fr;}.cc-dados dl{grid-template-columns:1fr;}}
</style>

<?php require_once "includes/footer.php"; ?>
